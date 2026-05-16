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

    /** Simulated raw response for rawApiGet (e.g. existing custom HTTPD config) */
    public string $rawGetResponse = '';

    protected function apiRequest(string $command, array $params): array
    {
        $this->calls[] = ['command' => $command, 'params' => $params];
        return ['error' => '0', 'text' => 'Success', 'details' => ''];
    }

    protected function rawApiGet(string $command, array $params): string
    {
        $this->calls[] = ['command' => $command, 'params' => $params, 'method' => 'GET'];
        return $this->rawGetResponse;
    }

    /** Expose the protected extractHttpStatus for testing */
    public function extractHttpStatus(array $headers): int
    {
        return parent::extractHttpStatus($headers);
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

// createSubdomain makes 3 API calls:
// 1. CMD_API_SUBDOMAINS POST (create the subdomain)
// 2. CMD_API_CUSTOM_HTTPD GET via rawApiGet (fetch existing config)
// 3. CMD_API_CUSTOM_HTTPD POST (save SDOCROOT override)
assert_equals(3, count($daSpy->calls), 'createSubdomain made exactly 3 API calls');

$createCall = $daSpy->calls[0];
assert_equals('CMD_API_SUBDOMAINS', $createCall['command'], 'Step 1 endpoint is CMD_API_SUBDOMAINS');
assert_equals('create', $createCall['params']['action'], 'action=create');
assert_equals('test.local', $createCall['params']['domain'], 'domain=base_domain');
assert_equals('mysite', $createCall['params']['subdomain'], 'subdomain=slug');
assert_false(isset($createCall['params']['public_html']), 'No public_html param (set via custom HTTPD)');

$getConfigCall = $daSpy->calls[1];
assert_equals('CMD_API_CUSTOM_HTTPD', $getConfigCall['command'], 'Step 2a fetches existing custom HTTPD config');
assert_equals('test.local', $getConfigCall['params']['domain'], 'GET config for base domain');
assert_equals('GET', $getConfigCall['method'] ?? '', 'Step 2a uses rawApiGet (GET method)');

$setConfigCall = $daSpy->calls[2];
assert_equals('CMD_API_CUSTOM_HTTPD', $setConfigCall['command'], 'Step 2b saves SDOCROOT override');
assert_equals('save', $setConfigCall['params']['action'], 'action=save');
// CRITICAL: POST field must be 'config' (not 'custom') per DA docs
assert_true(isset($setConfigCall['params']['config']), 'POST field is "config" (per DA docs)');
assert_false(isset($setConfigCall['params']['custom']), 'POST field is NOT "custom"');
assert_contains($setConfigCall['params']['config'], '|*if SUB="mysite"|', 'Config contains SUB conditional');
assert_contains($setConfigCall['params']['config'], '|?SDOCROOT=/home/admin/domains/test.local/public_html/instances/mysite|', 'Config contains SDOCROOT path');
assert_contains($setConfigCall['params']['config'], '# SWARM_DOCROOT_mysite', 'Config contains fenced start marker');
assert_contains($setConfigCall['params']['config'], '# SWARM_DOCROOT_mysite_END', 'Config contains fenced end marker');

// ─── Test 1b: Existing custom HTTPD config is preserved ────────

echo "\nTest 1b: Existing custom HTTPD config preservation\n";
echo str_repeat('─', 55) . "\n";

$daSpy2 = new DirectAdminSpy([
    'da_hostname'  => 'da.example.com',
    'da_port'      => '2222',
    'da_username'  => 'admin',
    'da_login_key' => 'key123',
]);

// Simulate an existing custom HTTPD config with DA template syntax
$existingConfig = "|*if SSL|\nSSLEngine on\n|*endif|\n";
$daSpy2->rawGetResponse = $existingConfig;

$daSpy2->createSubdomain('newsite', '/home/admin/domains/test.local/public_html/instances/newsite');

$saveCall = $daSpy2->calls[2];
assert_contains($saveCall['params']['config'], 'SSLEngine on', 'Existing config is preserved');
assert_contains($saveCall['params']['config'], '|*if SSL|', 'Existing DA template syntax preserved');
assert_contains($saveCall['params']['config'], '|*if SUB="newsite"|', 'New SDOCROOT block appended');

// ─── Test 2: DirectAdmin removeSubdomain request ───────────────

echo "\nTest 2: DirectAdmin removeSubdomain request construction\n";
echo str_repeat('─', 55) . "\n";

$daSpy->calls = [];
$daSpy->removeSubdomain('mysite');

// removeSubdomain makes 2 API calls:
// 1. CMD_API_CUSTOM_HTTPD GET via rawApiGet (check for marker — not found, so no save)
// 2. CMD_API_SUBDOMAINS POST (delete the subdomain)
assert_equals(2, count($daSpy->calls), 'removeSubdomain made exactly 2 API calls');

$checkConfigCall = $daSpy->calls[0];
assert_equals('CMD_API_CUSTOM_HTTPD', $checkConfigCall['command'], 'Step 1 checks custom HTTPD config');
assert_equals('GET', $checkConfigCall['method'] ?? '', 'Step 1 uses rawApiGet (GET method)');

$removeCall = $daSpy->calls[1];
assert_equals('CMD_API_SUBDOMAINS', $removeCall['command'], 'Step 2 endpoint is CMD_API_SUBDOMAINS');
assert_equals('delete', $removeCall['params']['action'], 'action=delete');
assert_equals('test.local', $removeCall['params']['domain'], 'domain=base_domain');
assert_equals('mysite', $removeCall['params']['select0'], 'select0=slug');
assert_equals('yes', $removeCall['params']['delete'], 'delete=yes');

// ─── Test 2b: Removal with marker found triggers cleanup save ──

echo "\nTest 2b: Removal with existing SDOCROOT marker\n";
echo str_repeat('─', 55) . "\n";

$daSpy3 = new DirectAdminSpy([
    'da_hostname'  => 'da.example.com',
    'da_port'      => '2222',
    'da_username'  => 'admin',
    'da_login_key' => 'key123',
]);

// Simulate existing config with a Swarm marker
$daSpy3->rawGetResponse = "# SWARM_DOCROOT_oldsite\n|*if SUB=\"oldsite\"|\n|?SDOCROOT=/some/path|\n|*endif|\n# SWARM_DOCROOT_oldsite_END\n";

$daSpy3->removeSubdomain('oldsite');

// Should be 3 calls: GET config, POST save (cleaned), POST delete subdomain
assert_equals(3, count($daSpy3->calls), 'removeSubdomain with marker made 3 API calls');

$cleanSave = $daSpy3->calls[1];
assert_equals('CMD_API_CUSTOM_HTTPD', $cleanSave['command'], 'Cleanup saves cleaned config');
assert_true(isset($cleanSave['params']['config']), 'Cleanup uses "config" POST field');
assert_not_contains($cleanSave['params']['config'], 'SWARM_DOCROOT_oldsite', 'Marker removed from saved config');

$deleteCall = $daSpy3->calls[2];
assert_equals('CMD_API_SUBDOMAINS', $deleteCall['command'], 'Delete still runs after cleanup');

// ─── Test 2c: Delete runs even when HTTPD cleanup throws ───────

echo "\nTest 2c: Delete resilience when HTTPD cleanup fails\n";
echo str_repeat('─', 55) . "\n";

// Create a spy that throws on rawApiGet to simulate HTTPD API failure
$daSpy4 = new class([
    'da_hostname'  => 'da.example.com',
    'da_port'      => '2222',
    'da_username'  => 'admin',
    'da_login_key' => 'key123',
]) extends DirectAdminSpy {
    protected function rawApiGet(string $command, array $params): string
    {
        $this->calls[] = ['command' => $command, 'params' => $params, 'method' => 'GET'];
        throw new \RuntimeException('Simulated CMD_API_CUSTOM_HTTPD failure');
    }
};

$daSpy4->removeSubdomain('failsite');

// Even though HTTPD cleanup threw, delete should still run
$deleteCommands = array_filter($daSpy4->calls, fn($c) => ($c['command'] ?? '') === 'CMD_API_SUBDOMAINS');
assert_true(count($deleteCommands) > 0, 'CMD_API_SUBDOMAINS delete ran despite HTTPD failure');

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

// ─── Test 10: HTTP status validation ───────────────────────────

echo "\nTest 10: HTTP status validation\n";
echo str_repeat('─', 55) . "\n";

// Test extractHttpStatus parsing (exposed via spy)
$statusSpy = new DirectAdminSpy([
    'da_hostname'  => 'da.example.com',
    'da_port'      => '2222',
    'da_username'  => 'admin',
    'da_login_key' => 'key123',
]);

assert_equals(200, $statusSpy->extractHttpStatus(['HTTP/1.1 200 OK']), 'Parses HTTP/1.1 200');
assert_equals(404, $statusSpy->extractHttpStatus(['HTTP/1.1 404 Not Found']), 'Parses HTTP/1.1 404');
assert_equals(403, $statusSpy->extractHttpStatus(['HTTP/1.1 403 Forbidden']), 'Parses HTTP/1.1 403');
assert_equals(500, $statusSpy->extractHttpStatus(['HTTP/1.1 500 Internal Server Error']), 'Parses HTTP/1.1 500');
assert_equals(200, $statusSpy->extractHttpStatus(['HTTP/2 200']), 'Parses HTTP/2 200');
assert_equals(0, $statusSpy->extractHttpStatus([]), 'Empty headers return 0');
assert_equals(0, $statusSpy->extractHttpStatus(['']), 'Empty string returns 0');

// Test that apiRequest throws on 404 — simulate via a spy that
// calls the real apiRequest with a fake HTTP endpoint
$httpSpy = new class([
    'da_hostname'  => 'da.example.com',
    'da_port'      => '2222',
    'da_username'  => 'admin',
    'da_login_key' => 'key123',
]) extends DirectAdminSpy {
    public int $simulatedStatus = 200;

    protected function apiRequest(string $command, array $params): array
    {
        $this->calls[] = ['command' => $command, 'params' => $params];
        if ($this->simulatedStatus >= 400) {
            throw new \RuntimeException(
                "DirectAdmin API {$command} returned HTTP {$this->simulatedStatus}"
            );
        }
        return ['error' => '0', 'text' => 'Success', 'details' => ''];
    }

    protected function rawApiGet(string $command, array $params): string
    {
        $this->calls[] = ['command' => $command, 'params' => $params, 'method' => 'GET'];
        if ($this->simulatedStatus >= 400) {
            throw new \RuntimeException(
                "DirectAdmin API GET {$command} returned HTTP {$this->simulatedStatus}"
            );
        }
        return $this->rawGetResponse;
    }
};

// 404 on createSubdomain — CMD_API_SUBDOMAINS returns 404
$httpSpy->simulatedStatus = 404;
$threw = false;
try {
    $httpSpy->createSubdomain('test404', '/some/path');
} catch (\RuntimeException $e) {
    $threw = true;
    assert_contains($e->getMessage(), '404', 'Exception message contains HTTP status 404');
    assert_contains($e->getMessage(), 'CMD_API_SUBDOMAINS', 'Exception identifies the failed command');
}
assert_true($threw, 'createSubdomain throws on HTTP 404');

// 403 on rawApiGet — CMD_API_CUSTOM_HTTPD returns 403
$httpSpy->calls = [];
$httpSpy->simulatedStatus = 200; // Let create succeed
$httpSpy2 = new class([
    'da_hostname'  => 'da.example.com',
    'da_port'      => '2222',
    'da_username'  => 'admin',
    'da_login_key' => 'key123',
]) extends DirectAdminSpy {
    protected function apiRequest(string $command, array $params): array
    {
        $this->calls[] = ['command' => $command, 'params' => $params];
        return ['error' => '0', 'text' => 'Success', 'details' => ''];
    }

    protected function rawApiGet(string $command, array $params): string
    {
        $this->calls[] = ['command' => $command, 'params' => $params, 'method' => 'GET'];
        throw new \RuntimeException(
            "DirectAdmin API GET {$command} returned HTTP 403"
        );
    }
};

// createSubdomain: CMD_API_SUBDOMAINS succeeds but custom HTTPD GET fails with 403
$threw403 = false;
try {
    $httpSpy2->createSubdomain('test403', '/some/path');
} catch (\RuntimeException $e) {
    $threw403 = true;
    assert_contains($e->getMessage(), '403', 'Exception contains 403');
    assert_contains($e->getMessage(), 'CMD_API_CUSTOM_HTTPD', 'Exception identifies custom HTTPD');
}
assert_true($threw403, 'createSubdomain throws when custom HTTPD GET returns 403');

// removeSubdomain should still succeed even when rawApiGet returns 403
// (because removeSubdomainDocumentRoot is wrapped in try/catch)
$httpSpy2->calls = [];
$threwOnDelete = false;
try {
    $httpSpy2->removeSubdomain('test403');
    // Should reach here — delete should work
} catch (\RuntimeException $e) {
    $threwOnDelete = true;
}
assert_false($threwOnDelete, 'removeSubdomain succeeds despite HTTPD 403 (resilient delete)');
$deleteRan = !empty(array_filter($httpSpy2->calls, fn($c) => $c['command'] === 'CMD_API_SUBDOMAINS'));
assert_true($deleteRan, 'CMD_API_SUBDOMAINS delete ran after 403 on HTTPD');

// ─── Test 11: Log redaction of config payloads ─────────────────

echo "\nTest 11: Log redaction of config payloads\n";
echo str_repeat('─', 55) . "\n";

// Verify that apiRequest's logger excludes 'config' via source inspection.
// This proves the config payload (arbitrary DA custom HTTPD data) never
// reaches the log file — even if a future refactor changes the spy.
$rm = new ReflectionMethod(\Swarm\Adapters\DirectAdminAdapter::class, 'apiRequest');
$source = file_get_contents($rm->getFileName());
$startLine = $rm->getStartLine();
$endLine = $rm->getEndLine();
$lines = array_slice(explode("\n", $source), $startLine - 1, $endLine - $startLine + 1);
$methodSource = implode("\n", $lines);

assert_contains($methodSource, "'config' => true", 'apiRequest log excludes config from params');
assert_contains($methodSource, "'action' => true", 'apiRequest log excludes action from params');
assert_contains($methodSource, 'array_diff_key', 'apiRequest uses array_diff_key for log filtering');

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
