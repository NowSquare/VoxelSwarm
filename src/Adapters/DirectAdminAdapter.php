<?php

declare(strict_types=1);

namespace Swarm\Adapters;

use Swarm\Models\Setting;

/**
 * DirectAdminAdapter — Manages subdomains via the DirectAdmin API.
 *
 * Uses the legacy CMD_API_SUBDOMAINS endpoint with Login Key authentication.
 * Creates subdomains under the operator's DirectAdmin account — same
 * isolation model as the cPanel adapter. End users never receive
 * DirectAdmin credentials.
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
        $response = $this->apiRequest('CMD_API_SUBDOMAINS', [
            'action'    => 'create',
            'domain'    => $this->baseDomain,
            'subdomain' => $slug,
        ]);

        // DirectAdmin returns error=0 on success, error=1 on failure.
        // If the subdomain already exists, treat as success (idempotent).
        if ($this->hasError($response)) {
            $text = $response['text'] ?? $response['details'] ?? 'Unknown error';

            // "already exists" is not an error for us — idempotent create
            if (stripos($text, 'already exists') !== false) {
                \Swarm\Logger::info('adapter', 'DirectAdmin subdomain already exists (idempotent)', [
                    'slug' => $slug,
                ]);
                return;
            }

            throw new \RuntimeException("DirectAdmin create subdomain failed: {$text}");
        }

        \Swarm\Logger::info('adapter', 'DirectAdmin subdomain created', [
            'slug'      => $slug,
            'subdomain' => "{$slug}.{$this->baseDomain}",
        ]);
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
        // DirectAdmin has no native maintenance mode for subdomains.
        // The Provisioner handles pause by swapping the document root
        // to a holding page directory — same approach as cPanel.
        \Swarm\Logger::warning('adapter', 'DirectAdmin pause: no native API, relies on doc root swap', [
            'slug' => $slug,
        ]);
    }

    public function resumeSubdomain(string $slug): void
    {
        \Swarm\Logger::warning('adapter', 'DirectAdmin resume: no native API, relies on doc root swap', [
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
     * Make a request to the DirectAdmin API.
     *
     * Uses HTTP Basic auth with username + login key.
     * Returns the parsed response as an associative array.
     */
    private function apiRequest(string $command, array $params): array
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
}
