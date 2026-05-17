<?php

declare(strict_types=1);

namespace Swarm\Adapters;

/**
 * PleskAdapter — Plesk REST API integration.
 * Creates/manages subdomains via Plesk API.
 */
class PleskAdapter implements ControlPanelAdapter
{
    private string $hostname;
    private string $apiKey;
    private string $baseDomain;

    public function __construct(array $config)
    {
        $this->hostname   = rtrim($config['hostname'] ?? '', '/');
        $this->apiKey     = $config['api_key'] ?? '';
        $this->baseDomain = \Swarm\Models\Setting::get('base_domain', 'localhost');
    }

    public function createSubdomain(string $slug, string $documentRoot): void
    {
        $this->pleskRequest('POST', '/api/v2/domains', [
            'name'          => "{$slug}.{$this->baseDomain}",
            'hosting_type'  => 'virtual',
            'base_domain'   => ['name' => $this->baseDomain],
            'hosting'       => ['www_root' => $documentRoot],
        ]);
    }

    public function removeSubdomain(string $slug): void
    {
        $domain = "{$slug}.{$this->baseDomain}";
        $this->pleskRequest('DELETE', "/api/v2/domains/{$domain}");
    }

    public function pauseSubdomain(string $slug): void
    {
        \Swarm\Logger::warning('adapter', 'Plesk pause: via maintenance mode', ['slug' => $slug]);
    }

    public function resumeSubdomain(string $slug): void
    {
        \Swarm\Logger::warning('adapter', 'Plesk resume: via maintenance mode', ['slug' => $slug]);
    }

    public function verify(): array
    {
        if (empty($this->hostname) || empty($this->apiKey)) {
            return ['ok' => false, 'message' => 'Plesk hostname and API key are required.'];
        }

        try {
            $this->pleskRequest('GET', '/api/v2/server');
            return ['ok' => true, 'message' => 'Connected to Plesk successfully.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Plesk connection failed: ' . $e->getMessage()];
        }
    }

    private function pleskRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->hostname . $endpoint;

        $headers = [
            'X-API-Key: ' . $this->apiKey,
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
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException("Plesk API request failed: {$method} {$endpoint}");
        }

        // Validate HTTP status
        $httpStatus = 0;
        if (!empty($http_response_header[0]) && preg_match('#^HTTP/\S+\s+(\d{3})#', $http_response_header[0], $m)) {
            $httpStatus = (int) $m[1];
        }

        if ($httpStatus >= 400) {
            $body = json_decode($response, true);
            $msg  = $body['message'] ?? $body['error'] ?? "HTTP {$httpStatus}";
            throw new \RuntimeException("Plesk API {$method} {$endpoint} failed: {$msg}");
        }

        \Swarm\Logger::info('adapter', "Plesk API: {$method} {$endpoint}", [
            'status' => $http_response_header[0] ?? 'unknown',
        ]);

        return json_decode($response, true) ?: [];
    }

    public function addDomain(string $slug, string $domain): void
    {
        $subdomain = "{$slug}.{$this->baseDomain}";

        $this->pleskRequest('POST', "/api/v2/domains/{$subdomain}/aliases", [
            'name' => $domain,
        ]);

        \Swarm\Logger::info('adapter', 'Plesk domain alias added', [
            'slug'   => $slug,
            'domain' => $domain,
        ]);
    }

    public function removeDomain(string $slug, string $domain): void
    {
        $subdomain = "{$slug}.{$this->baseDomain}";

        try {
            $this->pleskRequest('DELETE', "/api/v2/domains/{$subdomain}/aliases/{$domain}");
        } catch (\Throwable $e) {
            // Idempotent: alias may not exist
            if (stripos($e->getMessage(), 'not found') === false) {
                throw $e;
            }
        }

        \Swarm\Logger::info('adapter', 'Plesk domain alias removed', [
            'slug'   => $slug,
            'domain' => $domain,
        ]);
    }
}
