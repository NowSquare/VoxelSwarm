#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * VoxelSwarm — Adapter Behavioral Verification
 *
 * Fully isolated: uses a temp SQLite database in sys_get_temp_dir(),
 * redirects SWARM_STORAGE to a temp directory (so Logger writes there
 * instead of real storage/logs/), and cleans up everything on exit.
 *
 * Request-construction tests use spy subclasses that override the
 * protected apiRequest/whmRequest methods to capture the actual
 * endpoint and parameters the adapter would send.
 *
 * Usage: php scripts/verify-adapters.php
 */

// ─── Fully isolated bootstrap ──────────────────────────────────
// Do NOT require bootstrap.php — it defines SWARM_STORAGE as the
// real storage/ dir. We define everything ourselves.

require_once __DIR__ . '/../vendor/autoload.php';

define('SWARM_ROOT', dirname(__DIR__));
define('SWARM_VERSION', trim(file_get_contents(SWARM_ROOT . '/VERSION') ?: '1.0.0'));

// Temp directories — nothing touches the real project
$testTmpBase = sys_get_temp_dir() . '/swarm-verify-' . uniqid();
mkdir($testTmpBase, 0755, true);
mkdir($testTmpBase . '/logs', 0755, true);

define('SWARM_STORAGE', $testTmpBase);

$testDbPath = $testTmpBase . '/test.db';
\Swarm\Database::init($testDbPath);

// Run migrations to create tables
\Swarm\Database::migrate(SWARM_ROOT . '/migrations');

// Set app_key (required for encryption)
\Swarm\Models\Setting::set('app_key', \Swarm\Helpers\Crypt::generateKey());
\Swarm\Models\Setting::set('base_domain', 'test.local');

// ─── Test helpers ──────────────────────────────────────────────

$passed = 0;
$failed = 0;

function assert_true(bool $condition, string $label): void
{
    global $passed, $failed;
    if ($condition) {
        echo "  ✓ {$label}\n";
        $passed++;
    } else {
        echo "  ✗ FAIL: {$label}\n";
        $failed++;
    }
}

function assert_false(bool $condition, string $label): void
{
    assert_true(!$condition, $label);
}

function assert_contains(string $haystack, string $needle, string $label): void
{
    assert_true(str_contains($haystack, $needle), $label);
}

function assert_not_contains(string $haystack, string $needle, string $label): void
{
    assert_true(!str_contains($haystack, $needle), $label);
}

function assert_equals(mixed $expected, mixed $actual, string $label): void
{
    assert_true($expected === $actual, "{$label}");
}

// ─── Spy subclasses ────────────────────────────────────────────
// Override the protected API methods to capture calls without HTTP.

class DirectAdminSpy extends \Swarm\Adapters\DirectAdminAdapter
{
    /** @var array{command: string, params: array}[] */
    public array $calls = [];

    protected function apiRequest(string $command, array $params): array
    {
        $this->calls[] = ['command' => $command, 'params' => $params];
        return ['error' => '0', 'text' => 'Success', 'details' => ''];
    }
}

class CpanelSpy extends \Swarm\Adapters\CpanelAdapter
{
    /** @var array{function: string, params: array}[] */
    public array $calls = [];

    protected function whmRequest(string $function, array $params = []): array
    {
        $this->calls[] = ['function' => $function, 'params' => $params];
        return ['result' => [['status' => 1]]];
    }
}

// ─── Test 1: DirectAdmin createSubdomain request ───────────────

echo "\nTest 1: DirectAdmin createSubdomain request construction\n";
echo str_repeat('─', 55) . "\n";

$daSpy = new DirectAdminSpy([
    'da_hostname'  => 'da.example.com',
    'da_port'      => '2222',
    'da_username'  => 'admin',
    'da_login_key' => 'key123',
]);

$daSpy->createSubdomain('mysite', '/home/admin/domains/test.local/public_html/instances/mysite');

assert_equals(1, count($daSpy->calls), 'createSubdomain made exactly 1 API call');

$createCall = $daSpy->calls[0];
assert_equals('CMD_SUBDOMAIN', $createCall['command'], 'Endpoint is CMD_SUBDOMAIN (not CMD_API_SUBDOMAINS)');
assert_equals('create', $createCall['params']['action'], 'action=create');
assert_equals('test.local', $createCall['params']['domain'], 'domain=base_domain');
assert_equals('mysite', $createCall['params']['subdomain'], 'subdomain=slug');
assert_equals(
    '/home/admin/domains/test.local/public_html/instances/mysite',
    $createCall['params']['public_html'],
    'public_html=documentRoot'
);

// ─── Test 2: DirectAdmin removeSubdomain request ───────────────

echo "\nTest 2: DirectAdmin removeSubdomain request construction\n";
echo str_repeat('─', 55) . "\n";

$daSpy->calls = [];
$daSpy->removeSubdomain('mysite');

assert_equals(1, count($daSpy->calls), 'removeSubdomain made exactly 1 API call');

$removeCall = $daSpy->calls[0];
assert_equals('CMD_API_SUBDOMAINS', $removeCall['command'], 'Endpoint is CMD_API_SUBDOMAINS');
assert_equals('delete', $removeCall['params']['action'], 'action=delete');
assert_equals('test.local', $removeCall['params']['domain'], 'domain=base_domain');
assert_equals('mysite', $removeCall['params']['select0'], 'select0=slug');
assert_equals('yes', $removeCall['params']['delete'], 'delete=yes');

// ─── Test 3: cPanel createSubdomain request ────────────────────

echo "\nTest 3: cPanel createSubdomain request construction\n";
echo str_repeat('─', 55) . "\n";

$cpSpy = new CpanelSpy([
    'hostname'     => 'https://whm.example.com',
    'api_token'    => 'tok123',
    'whm_username' => 'root',
]);

$cpSpy->createSubdomain('tenant1', '/home/operator/public_html/instances/tenant1');

assert_equals(1, count($cpSpy->calls), 'createSubdomain made exactly 1 WHM call');

$cpCreateCall = $cpSpy->calls[0];
assert_equals('create_subdomain', $cpCreateCall['function'], 'WHM function is create_subdomain');
assert_equals('tenant1', $cpCreateCall['params']['domain'], 'domain=slug');
assert_equals('test.local', $cpCreateCall['params']['rootdomain'], 'rootdomain=base_domain');
assert_equals(
    '/home/operator/public_html/instances/tenant1',
    $cpCreateCall['params']['dir'],
    'dir=documentRoot'
);

// ─── Test 4: DirectAdmin pause writes correct files ────────────

echo "\nTest 4: DirectAdmin pause file mutations\n";
echo str_repeat('─', 55) . "\n";

$instanceDir = $testTmpBase . '/instance-da';
mkdir($instanceDir, 0755, true);

$originalHtaccess = "RewriteEngine On\nRewriteRule ^index\\.php$ - [L]\nRewriteRule . /index.php [L]\n";
file_put_contents($instanceDir . '/.htaccess', $originalHtaccess);

$instanceId = \Swarm\Models\Instance::create([
    'slug'          => 'da-pause-test',
    'name'          => 'DA Pause Test',
    'email'         => 'test@test.local',
    'status'        => 'active',
    'type'          => 'instance',
    'document_root' => $instanceDir,
]);

$daSpy->pauseSubdomain('da-pause-test');

assert_true(file_exists($instanceDir . '/.maintenance'), '.maintenance marker created');
assert_true(file_exists($instanceDir . '/.maintenance_page.php'), '.maintenance_page.php created');

$htaccess = file_get_contents($instanceDir . '/.htaccess');
assert_contains($htaccess, '# SWARM_MAINTENANCE_START', 'Start marker in .htaccess');
assert_contains($htaccess, '# SWARM_MAINTENANCE_END', 'End marker in .htaccess');
assert_contains($htaccess, 'RewriteRule ^ .maintenance_page.php [L]', 'Rewrite to holding page');
assert_contains($htaccess, '!^/\\.maintenance_page\\.php', 'Exclusion has leading / for REQUEST_URI');
assert_not_contains($htaccess, "!^\\.maintenance_page", 'No broken pattern without leading /');
assert_contains($htaccess, 'RewriteRule . /index.php [L]', 'Original rules preserved');

$page = file_get_contents($instanceDir . '/.maintenance_page.php');
assert_contains($page, 'http_response_code(503)', '503 status in holding page');
assert_contains($page, 'Retry-After: 3600', 'Retry-After header');

// ─── Test 5: Double-pause idempotency ──────────────────────────

echo "\nTest 5: Double-pause idempotency\n";
echo str_repeat('─', 55) . "\n";

$daSpy->pauseSubdomain('da-pause-test');
$htaccessDouble = file_get_contents($instanceDir . '/.htaccess');
$count = substr_count($htaccessDouble, '# SWARM_MAINTENANCE_START');
assert_equals(1, $count, 'Maintenance block appears exactly once');

// ─── Test 6: Resume removes all maintenance files ──────────────

echo "\nTest 6: Resume cleanup\n";
echo str_repeat('─', 55) . "\n";

$daSpy->resumeSubdomain('da-pause-test');

assert_false(file_exists($instanceDir . '/.maintenance'), '.maintenance removed');
assert_false(file_exists($instanceDir . '/.maintenance_page.php'), '.maintenance_page.php removed');

$htaccessResumed = file_get_contents($instanceDir . '/.htaccess');
assert_not_contains($htaccessResumed, 'SWARM_MAINTENANCE', 'Maintenance block removed');
assert_equals(trim($originalHtaccess), trim($htaccessResumed), '.htaccess restored to original');

// ─── Test 7: Resume without prior pause is safe ────────────────

echo "\nTest 7: Resume without pause (no-op)\n";
echo str_repeat('─', 55) . "\n";

try {
    $daSpy->resumeSubdomain('da-pause-test');
    assert_true(true, 'No exception on no-op resume');
} catch (\Throwable $e) {
    assert_true(false, 'No exception on no-op resume: ' . $e->getMessage());
}

$htaccessAfter = file_get_contents($instanceDir . '/.htaccess');
assert_equals(trim($originalHtaccess), trim($htaccessAfter), '.htaccess unchanged after no-op');

// ─── Test 8: cPanel pause/resume parity ────────────────────────

echo "\nTest 8: cPanel pause/resume behavioral parity\n";
echo str_repeat('─', 55) . "\n";

$cpInstanceDir = $testTmpBase . '/instance-cpanel';
mkdir($cpInstanceDir, 0755, true);
file_put_contents($cpInstanceDir . '/.htaccess', $originalHtaccess);

$cpInstanceId = \Swarm\Models\Instance::create([
    'slug'          => 'cp-pause-test',
    'name'          => 'cPanel Pause Test',
    'email'         => 'test@test.local',
    'status'        => 'active',
    'type'          => 'instance',
    'document_root' => $cpInstanceDir,
]);

$cpSpy->pauseSubdomain('cp-pause-test');
assert_true(file_exists($cpInstanceDir . '/.maintenance'), 'cPanel: .maintenance created');
assert_true(file_exists($cpInstanceDir . '/.maintenance_page.php'), 'cPanel: holding page created');

$cpHtaccess = file_get_contents($cpInstanceDir . '/.htaccess');
assert_contains($cpHtaccess, '# SWARM_MAINTENANCE_START', 'cPanel: start marker');
assert_contains($cpHtaccess, '!^/\\.maintenance_page\\.php', 'cPanel: correct exclusion');

$cpSpy->resumeSubdomain('cp-pause-test');
assert_false(file_exists($cpInstanceDir . '/.maintenance'), 'cPanel: .maintenance removed');
assert_false(file_exists($cpInstanceDir . '/.maintenance_page.php'), 'cPanel: holding page removed');
assert_equals(
    trim($originalHtaccess),
    trim(file_get_contents($cpInstanceDir . '/.htaccess')),
    'cPanel: .htaccess restored'
);

// ─── Test 9: da_login_key encryption round-trip ────────────────

echo "\nTest 9: da_login_key encryption\n";
echo str_repeat('─', 55) . "\n";

\Swarm\Models\Setting::setJson('adapter_config', [
    'da_hostname'  => 'example.com',
    'da_port'      => '2222',
    'da_username'  => 'admin',
    'da_login_key' => 'secret-key-abc',
]);

$raw = \Swarm\Models\Setting::get('adapter_config');
$rawData = json_decode($raw, true);

assert_true(
    str_starts_with($rawData['da_login_key'] ?? '', 'enc:'),
    'da_login_key stored with enc: prefix'
);
assert_equals('example.com', $rawData['da_hostname'], 'da_hostname not encrypted');
assert_equals('2222', $rawData['da_port'], 'da_port not encrypted');

$decrypted = \Swarm\Models\Setting::getJson('adapter_config');
assert_equals('secret-key-abc', $decrypted['da_login_key'], 'da_login_key decrypts correctly');

// ─── Cleanup ───────────────────────────────────────────────────

$cleanup = function (string $dir) use (&$cleanup): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        is_dir($path) ? $cleanup($path) : unlink($path);
    }
    rmdir($dir);
};
$cleanup($testTmpBase);

// WAL/SHM files (in case SQLite created them)
foreach ([$testDbPath . '-wal', $testDbPath . '-shm'] as $f) {
    if (file_exists($f)) unlink($f);
}

// ─── Summary ───────────────────────────────────────────────────

echo "\n" . str_repeat('═', 55) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
echo str_repeat('═', 55) . "\n\n";

exit($failed > 0 ? 1 : 0);
