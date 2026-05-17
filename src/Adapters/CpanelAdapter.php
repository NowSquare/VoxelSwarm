<?php

declare(strict_types=1);

namespace Swarm\Adapters;

/**
 * CpanelAdapter — WHM/cPanel API integration.
 * Creates/manages subdomains via WHM API.
 */
class CpanelAdapter implements ControlPanelAdapter
{
    private string $hostname;
    private string $apiToken;
    private string $whmUsername;
    private string $baseDomain;

    public function __construct(array $config)
    {
        $this->hostname    = rtrim($config['hostname'] ?? '', '/');
        $this->apiToken    = $config['api_token'] ?? '';
        $this->whmUsername = trim($config['whm_username'] ?? '') ?: 'root';
        $this->baseDomain  = \Swarm\Models\Setting::get('base_domain', 'localhost');
    }

    public function createSubdomain(string $slug, string $documentRoot): void
    {
        $this->whmRequest('create_subdomain', [
            'domain'       => $slug,
            'rootdomain'   => $this->baseDomain,
            'dir'          => $documentRoot,
        ]);
    }

    public function removeSubdomain(string $slug): void
    {
        $this->whmRequest('delete_subdomain', [
            'domain' => "{$slug}.{$this->baseDomain}",
        ]);
    }

    public function pauseSubdomain(string $slug): void
    {
        // cPanel has no native maintenance mode for subdomains.
        // We place a .maintenance marker and route traffic to a 503 page
        // via .htaccess — same mechanism as the DirectAdmin adapter.
        $instance = \Swarm\Models\Instance::findBySlug($slug);
        if (!$instance || empty($instance['document_root'])) {
            \Swarm\Logger::warning('adapter', 'cPanel pause: cannot find document root', ['slug' => $slug]);
            return;
        }

        $docRoot = $instance['document_root'];
        $marker  = $docRoot . '/.maintenance';

        file_put_contents($marker, json_encode([
            'paused_at' => date('c'),
            'by'        => 'cpanel_adapter',
        ]));

        $holdingPage = $docRoot . '/.maintenance_page.php';
        file_put_contents($holdingPage, $this->maintenancePagePhp());

        $htaccessPath = $docRoot . '/.htaccess';
        $existing = file_exists($htaccessPath) ? file_get_contents($htaccessPath) : '';
        if (strpos($existing, '# SWARM_MAINTENANCE_START') === false) {
            $rule = "# SWARM_MAINTENANCE_START\n"
                  . "RewriteEngine On\n"
                  . "RewriteCond %{REQUEST_URI} !^/\.maintenance_page\.php\n"
                  . "RewriteCond %{DOCUMENT_ROOT}/.maintenance -f\n"
                  . "RewriteRule ^ .maintenance_page.php [L]\n"
                  . "# SWARM_MAINTENANCE_END\n";
            file_put_contents($htaccessPath, $rule . $existing);
        }

        \Swarm\Logger::info('adapter', 'cPanel subdomain paused via maintenance page', ['slug' => $slug]);
    }

    public function resumeSubdomain(string $slug): void
    {
        $instance = \Swarm\Models\Instance::findBySlug($slug);
        if (!$instance || empty($instance['document_root'])) {
            \Swarm\Logger::warning('adapter', 'cPanel resume: cannot find document root', ['slug' => $slug]);
            return;
        }

        $docRoot = $instance['document_root'];

        $marker = $docRoot . '/.maintenance';
        if (file_exists($marker)) {
            unlink($marker);
        }

        $holdingPage = $docRoot . '/.maintenance_page.php';
        if (file_exists($holdingPage)) {
            unlink($holdingPage);
        }

        $htaccessPath = $docRoot . '/.htaccess';
        if (file_exists($htaccessPath)) {
            $content = file_get_contents($htaccessPath);
            $content = preg_replace(
                '/# SWARM_MAINTENANCE_START\n.*?# SWARM_MAINTENANCE_END\n/s',
                '',
                $content
            );
            file_put_contents($htaccessPath, $content);
        }

        \Swarm\Logger::info('adapter', 'cPanel subdomain resumed', ['slug' => $slug]);
    }

    public function verify(): array
    {
        if (empty($this->hostname) || empty($this->apiToken)) {
            return ['ok' => false, 'message' => 'WHM hostname and API token are required.'];
        }

        try {
            $this->whmRequest('version');
            return ['ok' => true, 'message' => 'Connected to WHM/cPanel successfully.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'WHM connection failed: ' . $e->getMessage()];
        }
    }

    protected function whmRequest(string $function, array $params = []): array
    {
        $query = http_build_query($params);
        $base  = $this->hostname;

        if (!preg_match('#^https?://#i', $base)) {
            $base = 'https://' . $base;
        }

        $port = parse_url($base, PHP_URL_PORT);
        if ($port === null) {
            $base .= ':2087';
        }

        $url = "{$base}/json-api/{$function}?{$query}";

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "Authorization: whm {$this->whmUsername}:{$this->apiToken}\r\n",
                'timeout' => 30,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException("WHM API request failed: {$function}");
        }

        // Validate HTTP status
        $httpStatus = $this->extractHttpStatusFromHeaders($http_response_header ?? []);
        if ($httpStatus >= 400) {
            throw new \RuntimeException(
                "WHM API {$function} returned HTTP {$httpStatus}"
            );
        }

        \Swarm\Logger::info('adapter', "WHM API: {$function}", [
            'status' => $http_response_header[0] ?? 'unknown',
        ]);

        $decoded = json_decode($response, true) ?: [];

        // Check WHM response body for errors
        $result = $decoded['result'] ?? $decoded['data'] ?? null;
        if (is_array($result) && isset($result[0]['status']) && (int) $result[0]['status'] === 0) {
            $statusMsg = $result[0]['statusmsg'] ?? 'Unknown WHM error';
            throw new \RuntimeException("WHM API {$function} failed: {$statusMsg}");
        }

        return $decoded;
    }

    /**
     * Extract HTTP status code from response headers.
     */
    private function extractHttpStatusFromHeaders(array $headers): int
    {
        if (empty($headers) || empty($headers[0])) {
            return 0;
        }
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $headers[0], $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    /**
     * Return inline PHP for a 503 maintenance page.
     */
    private function maintenancePagePhp(): string
    {
        return <<<'PHP'
<?php
http_response_code(503);
header('Retry-After: 3600');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Under Maintenance</title>
<style>
body{font-family:Inter,system-ui,-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#09090B;color:#FAFAFA;}
div{text-align:center}
h1{font-size:32px;font-weight:700;margin:0}
p{color:#71717A;margin-top:8px}
</style>
</head>
<body>
<div>
<h1>Under Maintenance</h1>
<p>This site is temporarily paused.</p>
</div>
</body>
</html>
PHP;
    }

    public function addDomain(string $slug, string $domain): void
    {
        $instance = \Swarm\Models\Instance::findBySlug($slug);
        if (!$instance || empty($instance['document_root'])) {
            throw new \RuntimeException("Cannot add domain: instance '{$slug}' has no document root");
        }

        $this->whmRequest('adddomain', [
            'domain'    => $domain,
            'trueowner' => $this->whmUsername,
            'dir'       => $instance['document_root'],
        ]);

        // Trigger AutoSSL for the new domain (best-effort)
        try {
            $this->whmRequest('start_autossl_check_for_one_user', [
                'username' => $this->whmUsername,
            ]);
        } catch (\Throwable $e) {
            \Swarm\Logger::warning('adapter', 'cPanel AutoSSL trigger failed (non-fatal)', [
                'domain' => $domain,
                'error'  => $e->getMessage(),
            ]);
        }

        \Swarm\Logger::info('adapter', 'cPanel domain added', [
            'slug'   => $slug,
            'domain' => $domain,
        ]);
    }

    public function removeDomain(string $slug, string $domain): void
    {
        try {
            $this->whmRequest('removedomainbyname', [
                'domain' => $domain,
            ]);
        } catch (\Throwable $e) {
            // Idempotent: suppress "does not exist" / "not found" errors.
            // Any other failure is a real problem and should propagate.
            $msg = strtolower($e->getMessage());
            if (strpos($msg, 'not found') === false && strpos($msg, 'does not exist') === false) {
                throw $e;
            }
            \Swarm\Logger::info('adapter', 'cPanel removeDomain: domain already absent (idempotent)', [
                'slug'   => $slug,
                'domain' => $domain,
            ]);
        }

        \Swarm\Logger::info('adapter', 'cPanel domain removed', [
            'slug'   => $slug,
            'domain' => $domain,
        ]);
    }
}
