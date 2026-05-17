<?php

declare(strict_types=1);

namespace Swarm\Adapters;

/**
 * ForgeAdapter — Laravel Forge REST API integration.
 * Creates/manages subdomains via Forge's API.
 */
class ForgeAdapter implements ControlPanelAdapter
{
    private string $token;
    private string $serverId;
    private string $baseDomain;

    public function __construct(array $config)
    {
        $this->token      = $config['api_token'] ?? '';
        $this->serverId   = $config['server_id'] ?? '';
        $this->baseDomain = \Swarm\Models\Setting::get('base_domain', 'localhost');
    }

    public function createSubdomain(string $slug, string $documentRoot): void
    {
        $this->forgeRequest('POST', "/servers/{$this->serverId}/sites", [
            'domain'       => "{$slug}.{$this->baseDomain}",
            'project_type' => 'php',
            'directory'    => $documentRoot,
        ]);
    }

    public function removeSubdomain(string $slug): void
    {
        $siteId = $this->findSiteId($slug);
        if ($siteId) {
            $this->forgeRequest('DELETE', "/servers/{$this->serverId}/sites/{$siteId}");
        }
    }

    public function pauseSubdomain(string $slug): void
    {
        // Forge doesn't have a native pause — we could remove the site
        // or toggle maintenance mode. For v1.0, log a warning.
        \Swarm\Logger::warning('adapter', 'Forge pause: not fully implemented', ['slug' => $slug]);
    }

    public function resumeSubdomain(string $slug): void
    {
        \Swarm\Logger::warning('adapter', 'Forge resume: not fully implemented', ['slug' => $slug]);
    }

    public function verify(): array
    {
        if (empty($this->token) || empty($this->serverId)) {
            return ['ok' => false, 'message' => 'Forge API token and Server ID are required.'];
        }

        try {
            $response = $this->forgeRequest('GET', "/servers/{$this->serverId}");
            return ['ok' => true, 'message' => 'Connected to Forge. Server: ' . ($response['server']['name'] ?? 'unknown')];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Forge connection failed: ' . $e->getMessage()];
        }
    }

    private function forgeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = 'https://forge.laravel.com/api/v1' . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $context = stream_context_create([
            'http' => [
                'method'  => $method,
                'header'  => implode("\r\n", $headers),
                'content' => $data ? json_encode($data) : null,
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException("Forge API request failed: {$method} {$endpoint}");
        }

        // Validate HTTP status
        $httpStatus = 0;
        if (!empty($http_response_header[0]) && preg_match('#^HTTP/\S+\s+(\d{3})#', $http_response_header[0], $m)) {
            $httpStatus = (int) $m[1];
        }

        if ($httpStatus >= 400) {
            $body = json_decode($response, true);
            $msg  = $body['message'] ?? $body['error'] ?? "HTTP {$httpStatus}";
            throw new \RuntimeException("Forge API {$method} {$endpoint} failed: {$msg}");
        }

        \Swarm\Logger::info('adapter', "Forge API: {$method} {$endpoint}", [
            'status' => $http_response_header[0] ?? 'unknown',
        ]);

        return json_decode($response, true) ?: [];
    }

    private function findSiteId(string $slug): ?string
    {
        $domain = "{$slug}.{$this->baseDomain}";
        $response = $this->forgeRequest('GET', "/servers/{$this->serverId}/sites");

        foreach ($response['sites'] ?? [] as $site) {
            if ($site['name'] === $domain) {
                return (string) $site['id'];
            }
        }

        return null;
    }

    public function addDomain(string $slug, string $domain): void
    {
        $siteId = $this->findSiteId($slug);
        if (!$siteId) {
            throw new \RuntimeException("Cannot add domain: Forge site not found for '{$slug}'");
        }

        $this->forgeRequest('POST', "/servers/{$this->serverId}/sites/{$siteId}/aliases", [
            'domain' => $domain,
        ]);

        \Swarm\Logger::info('adapter', 'Forge domain alias added', [
            'slug'   => $slug,
            'domain' => $domain,
        ]);
    }

    public function removeDomain(string $slug, string $domain): void
    {
        $siteId = $this->findSiteId($slug);
        if (!$siteId) {
            return; // Idempotent: site doesn't exist
        }

        // Find the alias ID
        $response = $this->forgeRequest('GET', "/servers/{$this->serverId}/sites/{$siteId}/aliases");
        foreach ($response['aliases'] ?? [] as $alias) {
            if ($alias['domain'] === $domain) {
                $this->forgeRequest('DELETE', "/servers/{$this->serverId}/sites/{$siteId}/aliases/{$alias['id']}");
                \Swarm\Logger::info('adapter', 'Forge domain alias removed', [
                    'slug'   => $slug,
                    'domain' => $domain,
                ]);
                return;
            }
        }
    }
}
