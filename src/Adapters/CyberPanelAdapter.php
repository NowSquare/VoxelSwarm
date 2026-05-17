<?php

declare(strict_types=1);

namespace Swarm\Adapters;

use Swarm\Helpers\Logger;
use Swarm\Models\Setting;

/**
 * CyberPanelAdapter — Manages child domains via the CyberPanel API.
 *
 * Status: Planned — community contributions welcome.
 * Docs: https://cyberpanel.net/docs/api-guide/
 *
 * Note: CyberPanel uses OpenLiteSpeed by default.
 * VoxelSite is compatible with LiteSpeed/OpenLiteSpeed.
 */
class CyberPanelAdapter implements ControlPanelAdapter
{
    private string $hostname;
    private int $port;
    private string $username;
    private string $password;
    private string $baseDomain;

    public function __construct(array $config)
    {
        $this->hostname = $config['cyberpanel_hostname'] ?? '';
        $this->port = (int) ($config['cyberpanel_port'] ?? 8090);
        $this->username = $config['cyberpanel_username'] ?? '';
        $this->password = $config['cyberpanel_password'] ?? '';
        $this->baseDomain = Setting::get('base_domain', '');
    }

    public function createSubdomain(string $slug, string $documentRoot): void
    {
        // TODO: Implement via CyberPanel API
        // POST to https://hostname:8090/api/createChildDomain
        throw new \RuntimeException(
            'CyberPanel adapter is not yet implemented. ' .
            'Contributions welcome: https://github.com/NowSquare/VoxelSwarm/issues'
        );
    }

    public function removeSubdomain(string $slug): void
    {
        throw new \RuntimeException('CyberPanel adapter is not yet implemented.');
    }

    public function pauseSubdomain(string $slug): void
    {
        throw new \RuntimeException('CyberPanel adapter is not yet implemented.');
    }

    public function resumeSubdomain(string $slug): void
    {
        throw new \RuntimeException('CyberPanel adapter is not yet implemented.');
    }

    public function verify(): array
    {
        if (empty($this->hostname) || empty($this->username) || empty($this->password)) {
            return ['ok' => false, 'message' => 'CyberPanel hostname, username, and password are required.'];
        }

        return ['ok' => false, 'message' => 'CyberPanel adapter is not yet implemented. Contributions welcome.'];
    }

    public function addDomain(string $slug, string $domain): void
    {
        throw new \RuntimeException('CyberPanel adapter is not yet implemented.');
    }

    public function removeDomain(string $slug, string $domain): void
    {
        throw new \RuntimeException('CyberPanel adapter is not yet implemented.');
    }
}
