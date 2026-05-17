<?php

declare(strict_types=1);

namespace Swarm\Adapters;

use Swarm\Helpers\Logger;
use Swarm\Models\Setting;

/**
 * HestiaCPAdapter — Manages web domains via the HestiaCP API/CLI.
 *
 * Status: Planned — community contributions welcome.
 * Docs: https://docs.hestiacp.com/server_administration/rest_api.html
 */
class HestiaCPAdapter implements ControlPanelAdapter
{
    private string $hostname;
    private int $port;
    private string $username;
    private string $password;
    private string $baseDomain;

    public function __construct(array $config)
    {
        $this->hostname = $config['hestia_hostname'] ?? '';
        $this->port = (int) ($config['hestia_port'] ?? 8083);
        $this->username = $config['hestia_username'] ?? '';
        $this->password = $config['hestia_password'] ?? '';
        $this->baseDomain = Setting::get('base_domain', '');
    }

    public function createSubdomain(string $slug, string $documentRoot): void
    {
        // TODO: Implement via HestiaCP API
        // POST to https://hostname:8083/api/
        // cmd=v-add-web-domain&user=admin&domain=slug.baseDomain
        throw new \RuntimeException(
            'HestiaCP adapter is not yet implemented. ' .
            'Contributions welcome: https://github.com/NowSquare/VoxelSwarm/issues'
        );
    }

    public function removeSubdomain(string $slug): void
    {
        throw new \RuntimeException('HestiaCP adapter is not yet implemented.');
    }

    public function pauseSubdomain(string $slug): void
    {
        throw new \RuntimeException('HestiaCP adapter is not yet implemented.');
    }

    public function resumeSubdomain(string $slug): void
    {
        throw new \RuntimeException('HestiaCP adapter is not yet implemented.');
    }

    public function verify(): array
    {
        if (empty($this->hostname) || empty($this->username) || empty($this->password)) {
            return ['ok' => false, 'message' => 'HestiaCP hostname, username, and password are required.'];
        }

        return ['ok' => false, 'message' => 'HestiaCP adapter is not yet implemented. Contributions welcome.'];
    }

    public function addDomain(string $slug, string $domain): void
    {
        throw new \RuntimeException('HestiaCP adapter is not yet implemented.');
    }

    public function removeDomain(string $slug, string $domain): void
    {
        throw new \RuntimeException('HestiaCP adapter is not yet implemented.');
    }
}
