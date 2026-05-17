<?php

declare(strict_types=1);

namespace Swarm\Adapters;

use Swarm\Helpers\Logger;
use Swarm\Models\Setting;

/**
 * CloudPanelAdapter — Manages sites via the CloudPanel API/CLI.
 *
 * Status: Planned — community contributions welcome.
 * Docs: https://www.cloudpanel.io/docs/
 */
class CloudPanelAdapter implements ControlPanelAdapter
{
    private string $hostname;
    private string $apiKey;
    private string $baseDomain;

    public function __construct(array $config)
    {
        $this->hostname = $config['cloudpanel_hostname'] ?? '';
        $this->apiKey = $config['cloudpanel_api_key'] ?? '';
        $this->baseDomain = Setting::get('base_domain', '');
    }

    public function createSubdomain(string $slug, string $documentRoot): void
    {
        // TODO: Implement via CloudPanel API or CLI
        throw new \RuntimeException(
            'CloudPanel adapter is not yet implemented. ' .
            'Contributions welcome: https://github.com/NowSquare/VoxelSwarm/issues'
        );
    }

    public function removeSubdomain(string $slug): void
    {
        throw new \RuntimeException('CloudPanel adapter is not yet implemented.');
    }

    public function pauseSubdomain(string $slug): void
    {
        throw new \RuntimeException('CloudPanel adapter is not yet implemented.');
    }

    public function resumeSubdomain(string $slug): void
    {
        throw new \RuntimeException('CloudPanel adapter is not yet implemented.');
    }

    public function verify(): array
    {
        if (empty($this->hostname)) {
            return ['ok' => false, 'message' => 'CloudPanel hostname is required.'];
        }

        return ['ok' => false, 'message' => 'CloudPanel adapter is not yet implemented. Contributions welcome.'];
    }

    public function addDomain(string $slug, string $domain): void
    {
        throw new \RuntimeException('CloudPanel adapter is not yet implemented.');
    }

    public function removeDomain(string $slug, string $domain): void
    {
        throw new \RuntimeException('CloudPanel adapter is not yet implemented.');
    }
}
