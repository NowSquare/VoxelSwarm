#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * VoxelSwarm — Interactive Setup Wizard
 *
 * First-run configuration script. Walks the operator through:
 * 1. System check (PHP, extensions, permissions)
 * 2. App key generation
 * 3. Database migrations
 * 4. Base config (domain, email, password)
 * 5. Instances storage path
 * 6. Control panel adapter selection + config
 * 7. Email driver (optional)
 * 8. Summary
 *
 * Usage: php scripts/install.php
 */

require_once __DIR__ . '/../src/bootstrap.php';

use Swarm\Database;
use Swarm\Helpers\Crypt;
use Swarm\Models\Setting;

echo "\n";
echo "  ╔══════════════════════════════════════╗\n";
echo "  ║     VoxelSwarm — Setup Wizard        ║\n";
echo "  ║     v" . SWARM_VERSION . str_repeat(' ', 31 - strlen(SWARM_VERSION)) . "║\n";
echo "  ╚══════════════════════════════════════╝\n\n";

// ── Step 1: System check ──
echo "Step 1: System Check\n";
echo str_repeat('─', 40) . "\n";

$checks = [
    ['PHP >= 8.1', version_compare(PHP_VERSION, '8.1.0', '>=')],
    ['pdo_sqlite',  extension_loaded('pdo_sqlite')],
    ['mbstring',    extension_loaded('mbstring')],
    ['openssl',     extension_loaded('openssl')],
    ['fileinfo',    extension_loaded('fileinfo')],
    ['curl',        extension_loaded('curl')],
    ['storage/ writable', is_writable(SWARM_STORAGE)],
];

$allPassed = true;
foreach ($checks as [$label, $ok]) {
    echo '  ' . ($ok ? '✓' : '✗') . " {$label}\n";
    if (!$ok) $allPassed = false;
}

if (!$allPassed) {
    echo "\n❌ Some requirements are not met. Please fix the issues above.\n";
    exit(1);
}
echo "\n";

// ── Step 2: App key ──
echo "Step 2: App Key\n";
echo str_repeat('─', 40) . "\n";

// Run migrations first so settings table exists
Database::migrate(SWARM_ROOT . '/migrations');
echo "  ✓ Database migrations complete\n";

$existingKey = Setting::get('app_key');
if ($existingKey) {
    echo "  ✓ App key already exists\n";
} else {
    $key = Crypt::generateKey();
    Setting::set('app_key', $key);
    echo "  ✓ App key generated\n";
}
echo "\n";

// ── Step 3: Base config ──
echo "Step 3: Base Configuration\n";
echo str_repeat('─', 40) . "\n";

$baseDomain = prompt('Base domain (e.g., voxelsite.com)', Setting::get('base_domain', ''));
Setting::set('base_domain', $baseDomain);

$operatorEmail = prompt('Operator email', Setting::get('operator_email', ''));
Setting::set('operator_email', $operatorEmail);

$password = prompt('Operator password (min 8 chars)', '');
while (strlen($password) < 8) {
    echo "  ⚠ Password must be at least 8 characters.\n";
    $password = prompt('Operator password', '');
}
Setting::set('operator_password_hash', password_hash($password, PASSWORD_BCRYPT));

echo "\n";

// ── Step 4: Instances path ──
echo "Step 4: Instances Path\n";
echo str_repeat('─', 40) . "\n";

$defaultPath = SWARM_STORAGE . '/instances';
$instancesPath = prompt('Instances storage path', Setting::get('instances_path', $defaultPath));
Setting::set('instances_path', $instancesPath);

if (!is_dir($instancesPath)) {
    mkdir($instancesPath, 0755, true);
    echo "  ✓ Created: {$instancesPath}\n";
}

// Template path
$templatePath = SWARM_ROOT . '/template/voxelsite/active';
Setting::set('template_path', $templatePath);
echo "  ✓ Template path: {$templatePath}\n";
echo "\n";

// ── Step 5: Control panel adapter ──
echo "Step 5: Control Panel Adapter\n";
echo str_repeat('─', 40) . "\n";
echo "  Available adapters:\n";
echo "  0. local  — Filesystem only (custom path, no subdomain management)\n";
echo "  1. nginx  — Direct Nginx conf management\n";
echo "  2. forge  — Laravel Forge API\n";
echo "  3. cpanel — cPanel/WHM API\n";
echo "  4. plesk  — Plesk API\n";

$adapterChoice = prompt('Select adapter (0-4)', '0');
$adapterMap = ['0' => 'local', '1' => 'nginx', '2' => 'forge', '3' => 'cpanel', '4' => 'plesk'];
$adapter = $adapterMap[$adapterChoice] ?? 'local';
Setting::set('control_panel_adapter', $adapter);

echo "  ✓ Selected: {$adapter}\n";

// Adapter-specific config
$adapterConfig = [];
switch ($adapter) {
    case 'nginx':
        $adapterConfig['conf_dir']      = prompt('Nginx conf directory', '/etc/nginx/conf.d');
        $adapterConfig['reload_cmd']    = prompt('Reload command', 'nginx -t && systemctl reload nginx');
        $adapterConfig['ssl_cert_path'] = prompt('SSL cert path (wildcard)', '');
        $adapterConfig['ssl_key_path']  = prompt('SSL key path', '');
        break;
    case 'forge':
        $adapterConfig['api_token'] = prompt('Forge API token', '');
        $adapterConfig['server_id'] = prompt('Forge Server ID', '');
        break;
    case 'cpanel':
        $adapterConfig['hostname']  = prompt('WHM hostname (https://...)', '');
        $adapterConfig['api_token'] = prompt('WHM API token', '');
        break;
    case 'plesk':
        $adapterConfig['hostname'] = prompt('Plesk hostname (https://...)', '');
        $adapterConfig['api_key']  = prompt('Plesk API key', '');
        break;
}
Setting::setJson('adapter_config', $adapterConfig);
echo "\n";

// ── Step 6: Email ──
echo "Step 6: Email Configuration (optional)\n";
echo str_repeat('─', 40) . "\n";
echo "  Drivers: smtp, log (dev), null (disabled)\n";

$mailDriver = prompt('Mail driver', Setting::get('mail_driver', 'log'));
Setting::set('mail_driver', $mailDriver);

if ($mailDriver === 'smtp') {
    $mailConfig = [];
    $mailConfig['host']         = prompt('SMTP host', 'smtp.gmail.com');
    $mailConfig['port']         = prompt('SMTP port', '587');
    $mailConfig['username']     = prompt('SMTP username', '');
    $mailConfig['smtp_password'] = prompt('SMTP password', '');
    $mailConfig['encryption']   = prompt('Encryption (tls/ssl)', 'tls');
    $mailConfig['from_address'] = prompt('From address', $operatorEmail);
    $mailConfig['from_name']    = prompt('From name', 'VoxelSwarm');
    Setting::setJson('mail_config', $mailConfig);
}

echo "\n";

// ── Step 7: Defaults ──
Setting::set('max_instances', Setting::get('max_instances', '100'));
Setting::set('public_site_enabled', Setting::get('public_site_enabled', 'false'));
Setting::set('signups_enabled', Setting::get('signups_enabled', 'false'));
Setting::set('gallery_enabled', Setting::get('gallery_enabled', 'false'));
Setting::set('version', SWARM_VERSION);
Setting::set('installed_at', date('c'));

// ── Summary ──
echo "╔══════════════════════════════════════╗\n";
echo "║           Setup Complete ✓           ║\n";
echo "╚══════════════════════════════════════╝\n\n";
echo "  Base domain:     {$baseDomain}\n";
echo "  Operator email:  {$operatorEmail}\n";
echo "  Adapter:         {$adapter}\n";
echo "  Mail driver:     {$mailDriver}\n";
echo "  Instances path:  {$instancesPath}\n";
echo "  Database:        " . SWARM_DB_PATH . "\n";
echo "\n";
echo "  Next steps:\n";
echo "  1. Point wildcard DNS: *.{$baseDomain} → your VPS IP\n";
echo "  2. Visit: https://{$baseDomain}/operator\n";
echo "  3. Log in and provision a demo instance\n";
echo "\n";

// ── Helper ──
function prompt(string $label, string $default = ''): string
{
    $defaultDisplay = $default ? " [{$default}]" : '';
    echo "  {$label}{$defaultDisplay}: ";
    $input = trim(fgets(STDIN) ?: '');
    return $input !== '' ? $input : $default;
}
