<?php

declare(strict_types=1);

namespace Swarm\Adapters;

use Swarm\Models\Setting;

/**
 * DirectAdminAdapter — Manages subdomains via the DirectAdmin API.
 *
 * Creates subdomains via CMD_API_SUBDOMAINS (Login Key compatible), then
 * moves the provisioned VoxelSite files into the subdomain directory that
 * DirectAdmin creates automatically.
 *
 * CMD_API_CUSTOM_HTTPD (which can set custom document roots via SDOCROOT)
 * requires admin-level access that conflicts with user-level domain
 * ownership. Field testing confirmed this is unusable in real DA setups.
 * Instead, we work with DA's default directory structure.
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

    /**
     * Optional override for the base path where DA creates subdomain
     * directories. Defaults to /home/{user}/domains/{domain}/public_html.
     */
    private string $docrootBase;

    public function __construct(array $config)
    {
        $this->hostname   = rtrim($config['da_hostname'] ?? '', '/');
        $this->port       = (int) ($config['da_port'] ?? 2222);
        $this->username   = $config['da_username'] ?? '';
        $this->loginKey   = $config['da_login_key'] ?? '';
        $this->baseDomain = Setting::get('base_domain', '');

        // Auto-detect: /home/{user}/domains/{domain}/public_html
        // Operators with non-standard layouts can override via da_docroot_base.
        $this->docrootBase = rtrim(
            $config['da_docroot_base']
                ?? "/home/{$this->username}/domains/{$this->baseDomain}/public_html",
            '/'
        );
    }

    public function createSubdomain(string $slug, string $documentRoot): void
    {
        // Step 1: Create the subdomain via CMD_API_SUBDOMAINS.
        // This is the Login Key–compatible endpoint. It creates the
        // subdomain record and its default directory.
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

        // Step 2: Move provisioned files into DA's subdomain directory.
        // DA created its own directory (with a default index.html) at
        // {docrootBase}/{slug}. We replace it with the VoxelSite files.
        $daDir = $this->getSubdomainDir($slug);

        if (is_dir($documentRoot) && realpath($documentRoot) !== realpath($daDir)) {
            // Clear DA's default directory (may contain index.html)
            if (is_dir($daDir)) {
                self::recursiveDelete($daDir);
            }

            // Move the provisioned instance into the DA directory
            if (!@rename($documentRoot, $daDir)) {
                // Cross-device move: copy then delete source
                self::recursiveCopy($documentRoot, $daDir);
                self::recursiveDelete($documentRoot);
            }

            // Update the database so pause/resume/cleanup use the real path
            $instance = \Swarm\Models\Instance::findBySlug($slug);
            if ($instance) {
                \Swarm\Models\Instance::update(
                    (int) $instance['id'],
                    ['document_root' => $daDir]
                );
            }

            \Swarm\Logger::info('adapter', 'DirectAdmin files moved to subdomain directory', [
                'slug' => $slug,
                'from' => $documentRoot,
                'to'   => $daDir,
            ]);
        }
    }

    public function removeSubdomain(string $slug): void
    {
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
                  . "RewriteCond %{REQUEST_URI} !^/\\.maintenance_page\\.php\n"
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
     * Return the path where DA creates a subdomain's directory.
     *
     * Default convention: /home/{user}/domains/{domain}/public_html/{slug}
     * Overridable via da_docroot_base config field.
     */
    protected function getSubdomainDir(string $slug): string
    {
        return $this->docrootBase . '/' . $slug;
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
            'params' => array_diff_key($params, ['action' => true]),
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
     * Recursive directory copy (for cross-device moves).
     */
    private static function recursiveCopy(string $source, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath  = $source . '/' . $file;
            $destPath = $dest . '/' . $file;

            if (is_dir($srcPath)) {
                self::recursiveCopy($srcPath, $destPath);
            } else {
                copy($srcPath, $destPath);
            }
        }
        closedir($dir);
    }

    /**
     * Recursive directory delete.
     */
    private static function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                self::recursiveDelete($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
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

    public function addDomain(string $slug, string $domain): void
    {
        // The 'domain' parameter must be the subdomain the pointer targets,
        // not the base domain. Custom domains must route to the specific
        // tenant instance at {slug}.{baseDomain}.
        $targetDomain = "{$slug}.{$this->baseDomain}";

        $response = $this->apiRequest('CMD_API_DOMAIN_POINTER', [
            'action' => 'add',
            'domain' => $targetDomain,
            'from'   => $domain,
        ]);

        if ($this->hasError($response)) {
            $text = $response['text'] ?? $response['details'] ?? 'Unknown error';

            // "already exists" is idempotent success
            if (stripos($text, 'already exists') !== false) {
                \Swarm\Logger::info('adapter', 'DirectAdmin domain pointer already exists (idempotent)', [
                    'slug'   => $slug,
                    'domain' => $domain,
                ]);
            } else {
                throw new \RuntimeException("DirectAdmin add domain pointer failed: {$text}");
            }
        }

        \Swarm\Logger::info('adapter', 'DirectAdmin domain pointer added', [
            'slug'   => $slug,
            'domain' => $domain,
            'target' => $targetDomain,
        ]);
    }

    public function removeDomain(string $slug, string $domain): void
    {
        $targetDomain = "{$slug}.{$this->baseDomain}";

        $response = $this->apiRequest('CMD_API_DOMAIN_POINTER', [
            'action'  => 'delete',
            'domain'  => $targetDomain,
            'select0' => $domain,
        ]);

        if ($this->hasError($response)) {
            $text = $response['text'] ?? $response['details'] ?? 'Unknown error';

            if (stripos($text, 'does not exist') !== false
                || stripos($text, 'not found') !== false) {
                \Swarm\Logger::info('adapter', 'DirectAdmin domain pointer already removed (idempotent)', [
                    'slug'   => $slug,
                    'domain' => $domain,
                ]);
                return;
            }

            throw new \RuntimeException("DirectAdmin remove domain pointer failed: {$text}");
        }

        \Swarm\Logger::info('adapter', 'DirectAdmin domain pointer removed', [
            'slug'   => $slug,
            'domain' => $domain,
        ]);
    }
}
