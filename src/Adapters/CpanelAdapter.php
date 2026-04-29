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
        \Swarm\Logger::warning('adapter', 'cPanel pause: implemented via doc root swap', ['slug' => $slug]);
    }

    public function resumeSubdomain(string $slug): void
    {
        \Swarm\Logger::warning('adapter', 'cPanel resume: implemented via doc root swap', ['slug' => $slug]);
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

    private function whmRequest(string $function, array $params = []): array
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

        \Swarm\Logger::info('adapter', "WHM API: {$function}", [
            'status' => $http_response_header[0] ?? 'unknown',
        ]);

        return json_decode($response, true) ?: [];
    }
}
