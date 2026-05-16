<?php

declare(strict_types=1);

namespace Swarm\Adapters;

use Swarm\Models\Setting;

/**
 * DirectAdminAdapter — Manages subdomains via the DirectAdmin API.
 *
 * Creates subdomains via CMD_API_SUBDOMAINS (Login Key compatible), then
 * sets a custom document root via CMD_API_CUSTOM_HTTPD using the SDOCROOT
 * token. CMD_SUBDOMAIN (which accepts public_html natively) is a browser-
 * session endpoint and returns 404 with Login Key authentication.
 *
 * Authentication via Login Keys (HTTP Basic). Creates subdomains under the
 * operator's DirectAdmin account — same isolation model as the cPanel
 * adapter. End users never receive DirectAdmin credentials.
 *
 * Docs: https://docs.directadmin.com/developer/api/
 */
class DirectAdminAdapter implements ControlPanelAdapter
{
    private string $hostname;
    private int $port;
    private string $username;
    private string $loginKey;
    private string $baseDomain;

    public function __construct(array $config)
    {
        $this->hostname   = rtrim($config['da_hostname'] ?? '', '/');
        $this->port       = (int) ($config['da_port'] ?? 2222);
        $this->username   = $config['da_username'] ?? '';
        $this->loginKey   = $config['da_login_key'] ?? '';
        $this->baseDomain = Setting::get('base_domain', '');
    }

    public function createSubdomain(string $slug, string $documentRoot): void
    {
        // Step 1: Create the subdomain via CMD_API_SUBDOMAINS.
        // This is the Login Key–compatible endpoint. It creates the
        // subdomain with the default document root.
        $response = $this->apiRequest('CMD_API_SUBDOMAINS', [
            'action'    => 'create',
            'domain'    => $this->baseDomain,
            'subdomain' => $slug,
        ]);

        if ($this->hasError($response)) {
            $text = $response['text'] ?? $response['details'] ?? 'Unknown error';

            // "already exists" is not an error for us — idempotent create
            if (stripos($text, 'already exists') !== false) {
                \Swarm\Logger::info('adapter', 'DirectAdmin subdomain already exists (idempotent)', [
                    'slug' => $slug,
                ]);
            } else {
                throw new \RuntimeException("DirectAdmin create subdomain failed: {$text}");
            }
        }

        \Swarm\Logger::info('adapter', 'DirectAdmin subdomain created', [
            'slug'      => $slug,
            'subdomain' => "{$slug}.{$this->baseDomain}",
        ]);

        // Step 2: Set the custom document root via CMD_API_CUSTOM_HTTPD.
        // DirectAdmin's CMD_API_SUBDOMAINS does not accept a custom
        // document root. We override it using the SDOCROOT token in the
        // domain's custom HTTPD configuration.
        $this->setSubdomainDocumentRoot($slug, $documentRoot);
    }

    public function removeSubdomain(string $slug): void
    {
        // Best-effort cleanup of custom HTTPD config — must not block
        // the subdomain deletion itself.
        try {
            $this->removeSubdomainDocumentRoot($slug);
        } catch (\Throwable $e) {
            \Swarm\Logger::warning('adapter', 'DirectAdmin custom HTTPD cleanup failed (continuing with delete)', [
                'slug'  => $slug,
                'error' => $e->getMessage(),
            ]);
        }

        $response = $this->apiRequest('CMD_API_SUBDOMAINS', [
            'action'  => 'delete',
            'domain'  => $this->baseDomain,
            'select0' => $slug,
            'delete'  => 'yes',
        ]);

        // If the subdomain doesn't exist, treat as success (idempotent).
        if ($this->hasError($response)) {
            $text = $response['text'] ?? $response['details'] ?? 'Unknown error';

            if (stripos($text, 'does not exist') !== false
                || stripos($text, 'not found') !== false) {
                \Swarm\Logger::info('adapter', 'DirectAdmin subdomain already removed (idempotent)', [
                    'slug' => $slug,
                ]);
                return;
            }

            throw new \RuntimeException("DirectAdmin remove subdomain failed: {$text}");
        }

        \Swarm\Logger::info('adapter', 'DirectAdmin subdomain removed', [
            'slug' => $slug,
        ]);
    }

    public function pauseSubdomain(string $slug): void
    {
        // DirectAdmin has no native maintenance mode API for subdomains.
        // We place a .maintenance marker and an index.php that serves a
        // 503 holding page, blocking access to the real site.
        $instance = \Swarm\Models\Instance::findBySlug($slug);
        if (!$instance || empty($instance['document_root'])) {
            \Swarm\Logger::warning('adapter', 'DirectAdmin pause: cannot find document root', ['slug' => $slug]);
            return;
        }

        $docRoot = $instance['document_root'];
        $marker  = $docRoot . '/.maintenance';

        // Write marker file (presence = paused)
        file_put_contents($marker, json_encode([
            'paused_at' => date('c'),
            'by'        => 'directadmin_adapter',
        ]));

        // Write a maintenance index.php that short-circuits all requests
        $holdingPage = $docRoot . '/.maintenance_page.php';
        file_put_contents($holdingPage, $this->maintenancePagePhp());

        // Prepend .htaccess rule to route all traffic to the holding page
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

        \Swarm\Logger::info('adapter', 'DirectAdmin subdomain paused via maintenance page', [
            'slug' => $slug,
        ]);
    }

    public function resumeSubdomain(string $slug): void
    {
        $instance = \Swarm\Models\Instance::findBySlug($slug);
        if (!$instance || empty($instance['document_root'])) {
            \Swarm\Logger::warning('adapter', 'DirectAdmin resume: cannot find document root', ['slug' => $slug]);
            return;
        }

        $docRoot = $instance['document_root'];

        // Remove the maintenance marker — .htaccess rule becomes a no-op
        $marker = $docRoot . '/.maintenance';
        if (file_exists($marker)) {
            unlink($marker);
        }

        // Remove the holding page
        $holdingPage = $docRoot . '/.maintenance_page.php';
        if (file_exists($holdingPage)) {
            unlink($holdingPage);
        }

        // Remove .htaccess maintenance block
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

        \Swarm\Logger::info('adapter', 'DirectAdmin subdomain resumed', [
            'slug' => $slug,
        ]);
    }

    public function verify(): array
    {
        if (empty($this->hostname)) {
            return ['ok' => false, 'message' => 'DirectAdmin hostname is required.'];
        }
        if (empty($this->username)) {
            return ['ok' => false, 'message' => 'DirectAdmin username is required.'];
        }
        if (empty($this->loginKey)) {
            return ['ok' => false, 'message' => 'DirectAdmin login key is required.'];
        }

        try {
            // CMD_API_SHOW_DOMAINS is a lightweight read-only call that
            // confirms authentication and basic API access.
            $response = $this->apiRequest('CMD_API_SHOW_DOMAINS', []);

            // A successful response contains a list[] of domains.
            // Even if the list is empty, a non-error response means
            // auth succeeded and the API is reachable.
            if ($this->hasError($response)) {
                $text = $response['text'] ?? $response['details'] ?? 'Authentication failed';
                return ['ok' => false, 'message' => "DirectAdmin connection failed: {$text}"];
            }

            return ['ok' => true, 'message' => 'Connected to DirectAdmin successfully.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'DirectAdmin connection failed: ' . $e->getMessage()];
        }
    }

    // ─── Private ────────────────────────────────────────────────

    /**
     * Set a custom document root for a subdomain via Custom HTTPD Config.
     *
     * Uses CMD_API_CUSTOM_HTTPD to inject an SDOCROOT override for the
     * specific subdomain. This is the only Login Key–compatible way to
     * set a non-default document root in DirectAdmin.
     *
     * The config uses DA template syntax:
     *   |*if SUB="slug"|
     *   |?SDOCROOT=/path/to/document/root|
     *   |*endif|
     */
    private function setSubdomainDocumentRoot(string $slug, string $documentRoot): void
    {
        // Fetch existing custom HTTPD config to avoid overwriting it
        $existing = $this->getCustomHttpdConfig();

        // Build the SDOCROOT block for this subdomain
        $marker = "# SWARM_DOCROOT_{$slug}";
        $block  = "{$marker}\n"
                . "|*if SUB=\"{$slug}\"|\n"
                . "|?SDOCROOT={$documentRoot}|\n"
                . "|*endif|\n"
                . "{$marker}_END\n";

        // If we already wrote a block for this slug, replace it
        if (str_contains($existing, $marker)) {
            $existing = preg_replace(
                '/' . preg_quote($marker, '/') . '\n.*?' . preg_quote("{$marker}_END", '/') . '\n/s',
                '',
                $existing
            );
        }

        $config = trim($existing) . "\n" . $block;

        // POST field is 'config' per DA docs (version 1.26.0+).
        $response = $this->apiRequest('CMD_API_CUSTOM_HTTPD', [
            'domain' => $this->baseDomain,
            'action' => 'save',
            'save'   => 'yes',
            'config' => $config,
        ]);

        if ($this->hasError($response)) {
            $text = $response['text'] ?? $response['details'] ?? 'Unknown error';
            throw new \RuntimeException(
                "DirectAdmin set document root failed for {$slug}: {$text}"
            );
        }

        \Swarm\Logger::info('adapter', 'DirectAdmin document root set via custom HTTPD', [
            'slug'         => $slug,
            'document_root' => $documentRoot,
        ]);
    }

    /**
     * Remove the custom HTTPD document root override for a subdomain.
     */
    private function removeSubdomainDocumentRoot(string $slug): void
    {
        $existing = $this->getCustomHttpdConfig();
        $marker   = "# SWARM_DOCROOT_{$slug}";

        if (!str_contains($existing, $marker)) {
            return; // Nothing to remove
        }

        $config = preg_replace(
            '/' . preg_quote($marker, '/') . '\n.*?' . preg_quote("{$marker}_END", '/') . '\n/s',
            '',
            $existing
        );

        $response = $this->apiRequest('CMD_API_CUSTOM_HTTPD', [
            'domain' => $this->baseDomain,
            'action' => 'save',
            'save'   => 'yes',
            'config' => trim($config) . "\n",
        ]);

        if ($this->hasError($response)) {
            \Swarm\Logger::warning('adapter', 'DirectAdmin remove custom HTTPD failed (non-fatal)', [
                'slug'  => $slug,
                'error' => $response['text'] ?? 'unknown',
            ]);
        }
    }

    /**
     * Fetch the existing custom HTTPD config for the base domain.
     *
     * CMD_API_CUSTOM_HTTPD GET dumps the raw .cust_httpd file body
     * as plain text. Using apiRequest() would parse_str() it, mangling
     * DA template syntax (pipes, equals). We use rawApiGet() instead.
     */
    private function getCustomHttpdConfig(): string
    {
        return $this->rawApiGet('CMD_API_CUSTOM_HTTPD', [
            'domain' => $this->baseDomain,
        ]);
    }

    /**
     * Make a request to the DirectAdmin API.
     *
     * Uses HTTP Basic auth with username + login key.
     * Returns the parsed response as an associative array.
     */
    protected function apiRequest(string $command, array $params): array
    {
        $base = $this->hostname;

        // Ensure scheme
        if (!preg_match('#^https?://#i', $base)) {
            $base = 'https://' . $base;
        }

        // Ensure port
        $port = parse_url($base, PHP_URL_PORT);
        if ($port === null) {
            $base .= ':' . $this->port;
        }

        $url = "{$base}/{$command}";

        $postData = http_build_query($params);
        $auth     = base64_encode("{$this->username}:{$this->loginKey}");

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Authorization: Basic {$auth}\r\n"
                                 . "Content-Type: application/x-www-form-urlencoded\r\n",
                'content'       => $postData,
                'timeout'       => 30,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException(
                "DirectAdmin API request failed: could not connect to {$base}/{$command}"
            );
        }

        \Swarm\Logger::info('adapter', "DirectAdmin API: {$command}", [
            'status' => $http_response_header[0] ?? 'unknown',
            'params' => array_diff_key($params, ['action' => true, 'config' => true]),
        ]);

        // Validate HTTP status — a 404/403 body must not be parsed as
        // a valid API response.
        $httpStatus = $this->extractHttpStatus($http_response_header ?? []);
        if ($httpStatus >= 400) {
            throw new \RuntimeException(
                "DirectAdmin API {$command} returned HTTP {$httpStatus}"
            );
        }

        // DirectAdmin returns URL-encoded responses by default.
        // Try JSON first (modern DA versions), fall back to URL-encoded.
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Parse URL-encoded response: error=0&text=...&details=...
        parse_str($response, $parsed);
        return $parsed;
    }

    /**
     * Make a GET request to the DirectAdmin API and return the raw body.
     *
     * Some DA endpoints (like CMD_API_CUSTOM_HTTPD GET) dump raw file
     * data that would be mangled by parse_str(). This method returns
     * the response as-is.
     */
    protected function rawApiGet(string $command, array $params): string
    {
        $base = $this->hostname;

        if (!preg_match('#^https?://#i', $base)) {
            $base = 'https://' . $base;
        }

        $port = parse_url($base, PHP_URL_PORT);
        if ($port === null) {
            $base .= ':' . $this->port;
        }

        $query = http_build_query($params);
        $url   = "{$base}/{$command}?{$query}";
        $auth  = base64_encode("{$this->username}:{$this->loginKey}");

        $context = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'header'        => "Authorization: Basic {$auth}\r\n",
                'timeout'       => 30,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException(
                "DirectAdmin API GET failed: could not connect to {$base}/{$command}"
            );
        }

        \Swarm\Logger::info('adapter', "DirectAdmin API GET: {$command}", [
            'status' => $http_response_header[0] ?? 'unknown',
        ]);

        // Validate HTTP status — a 404/403 body (HTML error page) must
        // not be returned as raw config text.
        $httpStatus = $this->extractHttpStatus($http_response_header ?? []);
        if ($httpStatus >= 400) {
            throw new \RuntimeException(
                "DirectAdmin API GET {$command} returned HTTP {$httpStatus}"
            );
        }

        return $response;
    }

    /**
     * Extract the HTTP status code from the $http_response_header array.
     *
     * PHP's file_get_contents() populates $http_response_header with
     * the raw response headers. The first line is e.g. "HTTP/1.1 200 OK".
     */
    protected function extractHttpStatus(array $headers): int
    {
        if (empty($headers[0])) {
            return 0;
        }

        // Match "HTTP/1.1 404 Not Found" or "HTTP/2 200"
        if (preg_match('#HTTP/\S+\s+(\d{3})#', $headers[0], $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    /**
     * Check if a DirectAdmin API response indicates an error.
     */
    private function hasError(array $response): bool
    {
        // JSON response format: {"error": 1, "text": "..."}
        if (isset($response['error'])) {
            return (int) $response['error'] !== 0;
        }

        // URL-encoded response: error=1
        return false;
    }

    /**
     * Return inline PHP for a 503 maintenance page.
     *
     * Matches the visual style of the Nginx adapter's paused block.
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
}
