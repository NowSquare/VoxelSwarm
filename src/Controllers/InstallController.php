<?php

declare(strict_types=1);

namespace Swarm\Controllers;

use Swarm\Database;
use Swarm\Helpers\Crypt;
use Swarm\Helpers\Response;
use Swarm\Models\Setting;

/**
 * InstallController — Web-based setup wizard.
 *
 * Only accessible when VoxelSwarm is not yet installed.
 * Once installation completes, these routes become inaccessible.
 *
 * GET  /install              — Show the install wizard
 * POST /install/check        — Run system requirements check
 * POST /install/test-adapter — Test control panel adapter connection
 * POST /install/complete     — Execute the full installation
 */
class InstallController
{
    /**
     * GET /install — Show the install wizard page.
     */
    public static function index(): void
    {
        if (isInstalled()) {
            Response::redirect('/operator');
            return;
        }

        Response::view('install');
    }

    /**
     * POST /install/check — Run system requirements check.
     */
    public static function check(): void
    {
        if (isInstalled()) {
            Response::json(['ok' => false, 'error' => 'Already installed.'], 403);
            return;
        }

        $checks = [];

        // PHP version
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '8.2.0', '>=');
        $checks[] = [
            'name'     => 'PHP ≥ 8.2',
            'detail'   => "PHP {$phpVersion}" . ($phpOk ? '' : ' (requires 8.2+)'),
            'status'   => $phpOk ? 'pass' : 'fail',
            'required' => true,
        ];

        // SQLite
        $sqliteOk = extension_loaded('pdo_sqlite');
        $sqliteVersion = $sqliteOk ? (new \PDO('sqlite::memory:'))->query('SELECT sqlite_version()')->fetchColumn() : 'not available';
        $checks[] = [
            'name'     => 'SQLite',
            'detail'   => $sqliteOk ? "SQLite {$sqliteVersion}" : 'PDO SQLite extension not loaded',
            'status'   => $sqliteOk ? 'pass' : 'fail',
            'required' => true,
        ];

        // OpenSSL
        $opensslOk = extension_loaded('openssl');
        $checks[] = [
            'name'     => 'OpenSSL',
            'detail'   => $opensslOk ? 'Available' : 'Required for encryption',
            'status'   => $opensslOk ? 'pass' : 'fail',
            'required' => true,
        ];

        // mbstring
        $mbstringOk = extension_loaded('mbstring');
        $checks[] = [
            'name'     => 'mbstring',
            'detail'   => $mbstringOk ? 'Available' : 'Required for text handling',
            'status'   => $mbstringOk ? 'pass' : 'fail',
            'required' => true,
        ];

        // cURL
        $curlOk = extension_loaded('curl');
        $checks[] = [
            'name'     => 'cURL',
            'detail'   => $curlOk ? 'Available' : 'Required for health checks and API calls',
            'status'   => $curlOk ? 'pass' : 'fail',
            'required' => true,
        ];

        // ZipArchive
        $zipOk = class_exists('ZipArchive');
        $checks[] = [
            'name'     => 'Zip Archive',
            'detail'   => $zipOk ? 'Available' : 'Required for template extraction',
            'status'   => $zipOk ? 'pass' : 'fail',
            'required' => true,
        ];

        // fileinfo
        $fileinfoOk = extension_loaded('fileinfo');
        $checks[] = [
            'name'     => 'fileinfo',
            'detail'   => $fileinfoOk ? 'Available' : 'Required for MIME detection',
            'status'   => $fileinfoOk ? 'pass' : 'fail',
            'required' => true,
        ];

        // storage/ writable
        $storageWritable = is_dir(SWARM_STORAGE) && is_writable(SWARM_STORAGE);
        $checks[] = [
            'name'     => 'Storage Directory',
            'detail'   => $storageWritable ? 'Writable' : 'storage/ is not writable',
            'status'   => $storageWritable ? 'pass' : 'fail',
            'required' => true,
        ];

        // template/ writable
        $templateDir = SWARM_ROOT . '/template/voxelsite';
        $templateWritable = is_dir($templateDir) && is_writable($templateDir);
        $checks[] = [
            'name'     => 'Template Directory',
            'detail'   => $templateWritable ? 'Writable' : 'template/voxelsite/ is not writable',
            'status'   => $templateWritable ? 'pass' : 'fail',
            'required' => true,
        ];

        $allRequired = array_filter($checks, fn($c) => $c['required'] && $c['status'] === 'fail');

        Response::json([
            'ok'          => true,
            'checks'      => $checks,
            'can_proceed' => empty($allRequired),
        ]);
    }

    /**
     * POST /install/test-adapter — Test control panel adapter connection.
     */
    public static function testAdapter(): void
    {
        if (isInstalled()) {
            Response::json(['ok' => false, 'error' => 'Already installed.'], 403);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $adapterName = $body['adapter'] ?? 'local';
        $config = $body['config'] ?? [];

        try {
            $adapter = match ($adapterName) {
                'local'       => new \Swarm\Adapters\LocalAdapter($config),
                'nginx'       => new \Swarm\Adapters\NginxAdapter($config),
                'forge'       => new \Swarm\Adapters\ForgeAdapter($config),
                'cpanel'      => new \Swarm\Adapters\CpanelAdapter($config),
                'plesk'       => new \Swarm\Adapters\PleskAdapter($config),
                'directadmin' => new \Swarm\Adapters\DirectAdminAdapter($config),
                'cloudpanel'  => new \Swarm\Adapters\CloudPanelAdapter($config),
                'hestiacp'    => new \Swarm\Adapters\HestiaCPAdapter($config),
                'cyberpanel'  => new \Swarm\Adapters\CyberPanelAdapter($config),
                default       => throw new \RuntimeException("Unknown adapter: {$adapterName}"),
            };

            $result = $adapter->verify();
            Response::json($result);
        } catch (\Throwable $e) {
            Response::json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /install/complete — Execute the full installation.
     */
    public static function complete(): void
    {
        if (isInstalled()) {
            Response::json(['ok' => false, 'error' => 'Already installed.'], 403);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?: [];

        // ── Validate ──
        $errors = [];

        if (empty($body['base_domain'])) {
            $errors[] = 'Base domain is required.';
        }
        if (empty($body['operator_email']) || !filter_var($body['operator_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid operator email is required.';
        }
        if (empty($body['operator_password']) || strlen($body['operator_password']) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (($body['operator_password'] ?? '') !== ($body['operator_password_confirm'] ?? '')) {
            $errors[] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            Response::json(['ok' => false, 'error' => implode(' ', $errors)], 422);
            return;
        }

        try {
            // ── Step 1: Run migrations ──
            Database::migrate(SWARM_ROOT . '/migrations');

            // ── Step 2: Generate app key ──
            $existingKey = Setting::get('app_key');
            if (!$existingKey) {
                Setting::set('app_key', Crypt::generateKey());
            }

            // ── Step 3: Store settings ──
            $baseDomain = trim($body['base_domain']);
            Setting::set('base_domain', $baseDomain);
            Setting::set('operator_email', trim($body['operator_email']));
            Setting::set('operator_password_hash', password_hash($body['operator_password'], PASSWORD_BCRYPT));

            // Instances path
            $instancesPath = SWARM_STORAGE . '/instances';
            Setting::set('instances_path', $instancesPath);
            if (!is_dir($instancesPath)) {
                mkdir($instancesPath, 0755, true);
            }

            // Template path
            Setting::set('template_path', SWARM_ROOT . '/template/voxelsite/active');

            // ── Step 4: Adapter ──
            $adapter = $body['adapter'] ?? 'local';
            $adapterConfig = $body['adapter_config'] ?? [];
            Setting::set('control_panel_adapter', $adapter);
            Setting::setJson('adapter_config', $adapterConfig);

            // ── Step 5: Email (optional) ──
            $mailDriver = $body['mail_driver'] ?? 'log';
            Setting::set('mail_driver', $mailDriver);
            if ($mailDriver === 'smtp' && !empty($body['mail_config'])) {
                Setting::setJson('mail_config', $body['mail_config']);
            }

            // ── Step 6: Defaults ──
            Setting::set('max_instances', '100');
            Setting::set('public_site_enabled', 'false');
            Setting::set('signups_enabled', 'false');
            Setting::set('version', SWARM_VERSION);
            Setting::set('installed_at', date('c'));

            // ── Step 7: Auto-login operator ──
            \Swarm\Models\Session::create();

            Response::json([
                'ok'       => true,
                'message'  => 'Installation complete.',
                'redirect' => '/operator',
            ]);
        } catch (\Throwable $e) {
            Response::json([
                'ok'    => false,
                'error' => 'Installation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
