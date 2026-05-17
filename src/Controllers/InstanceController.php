<?php

declare(strict_types=1);

namespace Swarm\Controllers;

use Swarm\Database;
use Swarm\Helpers\Response;
use Swarm\Middleware\Csrf;
use Swarm\Models\Instance;
use Swarm\Models\Setting;
use Swarm\Services\Provisioner;
use Swarm\Services\SubdomainGenerator;
use Swarm\Services\DnsVerifier;
use Swarm\Adapters\AdapterFactory;
use Swarm\Adapters\ControlPanelAdapter;

/**
 * InstanceController — Instance CRUD for the operator dashboard.
 */
class InstanceController
{
    /**
     * GET /operator/instances — Filterable instance list.
     */
    public function index(): void
    {
        $filters = [
            'status' => $_GET['status'] ?? null,
            'type'   => $_GET['type']   ?? null,
            'search' => $_GET['search'] ?? null,
        ];

        $instances = Instance::list($filters);

        // Check if at least one template version is prepared
        $tm = new \Swarm\Services\TemplateManager();
        $hasTemplates = !empty($tm->listVersions());

        Response::view('operator/instances', [
            'instances'     => $instances,
            'filters'       => $filters,
            'csrfField'     => Csrf::field(),
            'adapter'       => Setting::get('control_panel_adapter', 'local'),
            'baseDomain'    => Setting::get('base_domain', 'localhost'),
            'instancesPath' => Setting::get('instances_path', SWARM_STORAGE . '/instances'),
            'operatorEmail' => Setting::get('operator_email', ''),
            'hasTemplates'  => $hasTemplates,
        ], 'operator');
    }

    /**
     * POST /operator/instances — Create a new instance.
     */
    public function store(): void
    {
        Csrf::validate();

        // Guard: require at least one processed template
        $tm = new \Swarm\Services\TemplateManager();
        if (empty($tm->listVersions())) {
            \Swarm\Logger::warning('swarm', 'Instance creation blocked: no templates processed');
            Response::json(['error' => 'No VoxelSite template is prepared yet. Process a template first at Templates → Process.'], 422);
        }

        // Slug can be provided directly, or generated from name
        $slug  = !empty($_POST['slug']) ? trim($_POST['slug']) : '';
        $name  = !empty($_POST['name']) ? trim($_POST['name']) : '';
        $email = !empty($_POST['email']) ? trim($_POST['email']) : Setting::get('operator_email', 'operator@localhost');

        // Require at least a slug or name
        if (empty($slug) && empty($name)) {
            Response::json(['error' => 'Provide an identifier or name for the instance.'], 422);
        }

        // Generate slug from name if not provided
        if (empty($slug)) {
            $slug = SubdomainGenerator::generate($name);
        } else {
            // Sanitize manually-entered slug
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
            $slug = preg_replace('/-+/', '-', trim($slug, '-'));
            if (empty($slug)) {
                Response::json(['error' => 'Invalid identifier. Use only lowercase letters, numbers, and hyphens.'], 422);
            }
            if (Instance::slugExists($slug)) {
                Response::json(['error' => 'This identifier is already in use.'], 422);
            }
        }

        // Default name to slug if not provided
        if (empty($name)) {
            $name = $slug;
        }

        $instanceId = Instance::create([
            'slug'   => $slug,
            'name'   => $name,
            'email'  => $email,
            'status' => 'queued',
            'type'   => 'instance',
        ]);

        // Send response, then provision in background
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['id' => $instanceId, 'slug' => $slug]);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            ignore_user_abort(true);
            header('Content-Length: 0');
            header('Connection: close');
            flush();
            if (function_exists('ob_end_flush')) {
                @ob_end_flush();
            }
            flush();
        }

        Provisioner::run($instanceId);
    }

    /**
     * GET /operator/instances/{id} — Instance detail.
     */
    public function show(string $id): void
    {
        $instance = Instance::find((int) $id);
        if (!$instance) {
            Response::redirect('/operator/instances');
        }

        $logs = Database::query(
            'SELECT * FROM provision_logs WHERE instance_id = ? ORDER BY created_at ASC',
            [(int) $id]
        )->fetchAll();

        $baseDomain = Setting::get('base_domain', 'localhost');
        $serverIp   = Setting::get('server_ip', '');

        Response::view('operator/instance-detail', [
            'instance'   => $instance,
            'logs'       => $logs,
            'baseDomain' => $baseDomain,
            'serverIp'   => $serverIp,
            'csrfField'  => Csrf::field(),
        ], 'operator');
    }

    /**
     * PATCH /operator/instances/{id} — Update instance notes.
     */
    public function update(string $id): void
    {
        Csrf::validate();

        $instance = Instance::find((int) $id);
        if (!$instance) {
            Response::json(['error' => 'Instance not found'], 404);
        }

        $updates = [];
        if (isset($_POST['notes'])) {
            $updates['notes'] = $_POST['notes'];
        }

        if (!empty($updates)) {
            Instance::update((int) $id, $updates);
        }

        Response::json(['ok' => true]);
    }

    /**
     * DELETE /operator/instances/{id} — Delete an instance permanently.
     */
    public function destroy(string $id): void
    {
        Csrf::validate();

        $instance = Instance::find((int) $id);
        if (!$instance) {
            Response::json(['error' => 'Instance not found'], 404);
        }

        // Remove custom domain routing (best-effort)
        if (!empty($instance['custom_domain'])) {
            try {
                $adapter = AdapterFactory::create();
                $adapter->removeDomain($instance['slug'], $instance['custom_domain']);
            } catch (\Throwable $e) {
                \Swarm\Logger::error('adapter', 'Failed to remove custom domain on delete', [
                    'slug'   => $instance['slug'],
                    'domain' => $instance['custom_domain'],
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        // Remove subdomain routing (best-effort — don't block deletion)
        try {
            $adapter = AdapterFactory::create();
            $adapter->removeSubdomain($instance['slug']);
        } catch (\Throwable $e) {
            \Swarm\Logger::error('adapter', 'Failed to remove subdomain on delete', [
                'slug'  => $instance['slug'],
                'error' => $e->getMessage(),
            ]);
        }

        // Remove instance directory from disk
        $instancePath = $instance['document_root']
            ?? (Setting::get('instances_path', SWARM_STORAGE . '/instances') . '/' . $instance['slug']);
        if (is_dir($instancePath)) {
            Provisioner::deleteDirectory($instancePath);
            \Swarm\Logger::info('instance', 'Deleted instance directory', ['slug' => $instance['slug']]);
        }

        // Remove gallery thumbnail if it exists
        $galleryThumb = SWARM_STORAGE . '/gallery/' . $instance['slug'] . '.jpg';
        if (file_exists($galleryThumb)) {
            unlink($galleryThumb);
        }

        // Delete database records (instance + provision logs, transactional)
        try {
            Instance::hardDelete((int) $id);
            \Swarm\Logger::info('swarm', 'Instance deleted', ['slug' => $instance['slug']]);
        } catch (\Throwable $e) {
            \Swarm\Logger::error('swarm', 'Failed to delete instance from database', [
                'slug'  => $instance['slug'],
                'error' => $e->getMessage(),
            ]);
            Response::json(['error' => 'Failed to remove instance records. Check the logs.'], 500);
        }

        Response::json(['ok' => true]);
    }

    /**
     * POST /operator/instances/{id}/pause — Pause an instance.
     */
    public function pause(string $id): void
    {
        Csrf::validate();

        $instance = Instance::find((int) $id);
        if (!$instance || $instance['status'] !== 'active') {
            Response::json(['error' => 'Cannot pause this instance'], 422);
        }

        try {
            $adapter = AdapterFactory::create();
            $adapter->pauseSubdomain($instance['slug']);
        } catch (\Throwable $e) {
            Response::json(['error' => 'Failed to pause: ' . $e->getMessage()], 500);
        }

        Instance::update((int) $id, ['status' => 'paused']);

        Response::json(['ok' => true, 'status' => 'paused']);
    }

    /**
     * POST /operator/instances/{id}/resume — Resume a paused instance.
     */
    public function resume(string $id): void
    {
        Csrf::validate();

        $instance = Instance::find((int) $id);
        if (!$instance || $instance['status'] !== 'paused') {
            Response::json(['error' => 'Cannot resume this instance'], 422);
        }

        try {
            $adapter = AdapterFactory::create();
            $adapter->resumeSubdomain($instance['slug']);
        } catch (\Throwable $e) {
            Response::json(['error' => 'Failed to resume: ' . $e->getMessage()], 500);
        }

        Instance::update((int) $id, ['status' => 'active']);

        Response::json(['ok' => true, 'status' => 'active']);
    }

    /**
     * POST /operator/instances/{id}/domain — Set a custom domain.
     */
    public function setDomain(string $id): void
    {
        Csrf::validate();

        $instance = Instance::find((int) $id);
        if (!$instance || $instance['status'] !== 'active') {
            Response::json(['error' => 'Instance must be active to add a custom domain.'], 422);
        }

        // ── Guard: reject if a domain is already attached ──
        // The operator must remove the existing domain first (which triggers
        // adapter cleanup) before attaching a new one.
        if (!empty($instance['custom_domain'])) {
            Response::json([
                'error' => 'A custom domain is already attached. Remove it before adding a new one.',
            ], 422);
        }

        $domain = trim($_POST['domain'] ?? '');
        $force  = !empty($_POST['force']);

        // Validate domain format: bare FQDN, no protocol, no path
        $domain = strtolower($domain);
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');

        if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $domain)) {
            Response::json(['error' => 'Invalid domain format. Enter a bare domain like "example.com".'], 422);
        }

        // Check uniqueness
        $existing = Database::query(
            'SELECT id FROM instances WHERE custom_domain = ? AND id != ?',
            [$domain, (int) $id]
        )->fetch();

        if ($existing) {
            Response::json(['error' => 'This domain is already attached to another instance.'], 422);
        }

        // DNS verification (skip if force-add)
        if (!$force) {
            $serverIps = DnsVerifier::getServerIps();
            if (empty($serverIps)) {
                Response::json([
                    'error'       => 'Server IP not configured. Set it in Deployment → Server IP.',
                    'needs_ip'    => true,
                ], 422);
            }

            $dnsOk = DnsVerifier::verify($domain, $serverIps);
            if (!$dnsOk) {
                $ip = $serverIps[0] ?? 'YOUR_SERVER_IP';
                Response::json([
                    'error'       => "DNS not pointing to this server yet.",
                    'dns_pending' => true,
                    'record_type' => 'A',
                    'record_name' => $domain,
                    'record_value' => $ip,
                    'instruction' => "Point your domain's A record to {$ip}, then try again.",
                ], 422);
            }
        }

        // Add domain via adapter
        try {
            $adapter = AdapterFactory::create();
            $adapter->addDomain($instance['slug'], $domain);
        } catch (\Throwable $e) {
            \Swarm\Logger::error('adapter', 'Failed to add custom domain', [
                'slug'   => $instance['slug'],
                'domain' => $domain,
                'error'  => $e->getMessage(),
            ]);
            Response::json(['error' => 'Adapter failed to add domain: ' . $e->getMessage()], 500);
        }

        // Update database
        $now = date('c');
        Instance::update((int) $id, [
            'custom_domain'      => $domain,
            'domain_verified_at' => $now,
        ]);

        \Swarm\Logger::info('swarm', 'Custom domain added', [
            'slug'   => $instance['slug'],
            'domain' => $domain,
            'forced' => $force,
        ]);

        // Verify SSL with real certificate validation
        $sslActive = self::verifySsl($domain);
        if ($sslActive) {
            Instance::update((int) $id, ['domain_ssl_at' => date('c')]);
        }

        Response::json([
            'ok'         => true,
            'domain'     => $domain,
            'ssl_active' => $sslActive,
        ]);
    }

    /**
     * DELETE /operator/instances/{id}/domain — Remove a custom domain.
     *
     * Unlike destroy() (which uses best-effort cleanup), explicit removal
     * requires confirmed adapter cleanup before clearing DB state. If the
     * adapter fails, the domain stays tracked so the operator can retry.
     */
    public function removeDomain(string $id): void
    {
        Csrf::validate();

        $result = self::executeRemoveDomain((int) $id, AdapterFactory::create());
        Response::json($result['body'], $result['status']);
    }

    /**
     * Core domain-removal logic, separated from HTTP concerns for testability.
     *
     * Returns ['body' => [...], 'status' => int] instead of calling
     * Response::json() so the adapter-failure path can be exercised in tests
     * without process termination.
     *
     * @return array{body: array, status: int}
     */
    public static function executeRemoveDomain(int $id, ControlPanelAdapter $adapter): array
    {
        $instance = Instance::find($id);
        if (!$instance) {
            return ['body' => ['error' => 'Instance not found'], 'status' => 404];
        }

        $domain = $instance['custom_domain'] ?? null;
        if (!$domain) {
            return ['body' => ['error' => 'No custom domain to remove.'], 'status' => 422];
        }

        // Remove from adapter — failure blocks DB cleanup
        try {
            $adapter->removeDomain($instance['slug'], $domain);
        } catch (\Throwable $e) {
            \Swarm\Logger::error('adapter', 'Failed to remove custom domain', [
                'slug'   => $instance['slug'],
                'domain' => $domain,
                'error'  => $e->getMessage(),
            ]);
            return [
                'body' => [
                    'error' => 'Adapter failed to remove domain: ' . $e->getMessage()
                        . '. The domain is still tracked — retry or remove it manually from the control panel, then try again.',
                ],
                'status' => 500,
            ];
        }

        // Adapter confirmed removal — safe to clear DB state
        Instance::update($id, [
            'custom_domain'      => null,
            'domain_verified_at' => null,
            'domain_ssl_at'      => null,
        ]);

        \Swarm\Logger::info('swarm', 'Custom domain removed', [
            'slug'   => $instance['slug'],
            'domain' => $domain,
        ]);

        return ['body' => ['ok' => true], 'status' => 200];
    }

    /**
     * POST /operator/instances/{id}/domain/recheck — Re-check SSL for an active domain.
     */
    public function recheckSsl(string $id): void
    {
        Csrf::validate();

        $instance = Instance::find((int) $id);
        if (!$instance || empty($instance['custom_domain'])) {
            Response::json(['error' => 'No custom domain set.'], 422);
        }

        $domain = $instance['custom_domain'];
        $sslActive = self::verifySsl($domain);

        if ($sslActive) {
            Instance::update((int) $id, ['domain_ssl_at' => date('c')]);
            Response::json(['ok' => true, 'ssl_active' => true]);
        } else {
            Response::json(['ok' => true, 'ssl_active' => false]);
        }
    }

    /**
     * Verify that a domain has a valid, trusted SSL certificate whose
     * CN or SAN matches the domain. Uses a real TLS handshake with
     * peer and hostname verification enabled.
     */
    private static function verifySsl(string $domain): bool
    {
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'peer_name'         => $domain,
                'capture_peer_cert' => true,
                'allow_self_signed' => false,
            ],
        ]);

        $fp = @stream_socket_client(
            "ssl://{$domain}:443",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        if (!$fp) {
            \Swarm\Logger::info('swarm', 'SSL check failed for domain', [
                'domain' => $domain,
                'errno'  => $errno,
                'error'  => $errstr,
            ]);
            return false;
        }

        // Extract certificate and verify the domain appears in CN or SAN
        $params  = stream_context_get_params($fp);
        $certRaw = $params['options']['ssl']['peer_certificate'] ?? null;
        fclose($fp);

        if (!$certRaw) {
            return false;
        }

        $certInfo = openssl_x509_parse($certRaw);
        if (!$certInfo) {
            return false;
        }

        // Check CN
        $cn = $certInfo['subject']['CN'] ?? '';
        if (self::domainMatchesCert($domain, $cn)) {
            return true;
        }

        // Check Subject Alternative Names
        $san = $certInfo['extensions']['subjectAltName'] ?? '';
        foreach (explode(',', $san) as $entry) {
            $entry = trim($entry);
            if (str_starts_with($entry, 'DNS:')) {
                $dnsName = trim(substr($entry, 4));
                if (self::domainMatchesCert($domain, $dnsName)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a domain matches a certificate name, supporting wildcard certs.
     */
    private static function domainMatchesCert(string $domain, string $certName): bool
    {
        $domain   = strtolower($domain);
        $certName = strtolower($certName);

        if ($domain === $certName) {
            return true;
        }

        // Wildcard match: *.example.com matches foo.example.com
        // substr($certName, 2) gives 'example.com'
        // We need the parent of $domain: 'foo.example.com' → 'example.com'
        // strpos gives the position of the first '.', +1 to skip it
        if (str_starts_with($certName, '*.')) {
            $wildcardBase = substr($certName, 2);  // 'example.com'
            $dotPos = strpos($domain, '.');
            if ($dotPos === false) {
                return false;  // bare TLD, can't match wildcard
            }
            $domainParent = substr($domain, $dotPos + 1);  // 'example.com'
            return $domainParent === $wildcardBase;
        }

        return false;
    }
}
