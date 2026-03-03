<?php

declare(strict_types=1);

namespace Swarm\Adapters;

use Swarm\Logger;
use Swarm\Models\Setting;

/**
 * LocalAdapter — Filesystem-only adapter for testing and custom setups.
 *
 * Does not create real subdomains or configure a web server.
 * Instances are deployed to a configurable directory path.
 * Perfect for local development (Herd, Valet), testing the provisioning
 * flow, or custom setups where you handle routing yourself.
 *
 * Config:
 *   instances_root — Absolute path where instances are deployed.
 *                    Defaults to storage/instances.
 */
class LocalAdapter implements ControlPanelAdapter
{
    private string $baseDomain;
    private string $instancesRoot;

    public function __construct(array $config = [])
    {
        $this->baseDomain    = Setting::get('base_domain', 'localhost');
        $this->instancesRoot = $config['instances_root'] ?? '';

        // If a custom instances root is configured, persist it
        // so the Provisioner uses it as the deployment path.
        if (!empty($this->instancesRoot)) {
            Setting::set('instances_path', $this->instancesRoot);
        }
    }

    public function createSubdomain(string $slug, string $documentRoot): void
    {
        Logger::info('adapter', 'LocalAdapter: instance registered (no subdomain created)', [
            'slug'          => $slug,
            'subdomain'     => "{$slug}.{$this->baseDomain}",
            'document_root' => $documentRoot,
        ]);
    }

    public function removeSubdomain(string $slug): void
    {
        Logger::info('adapter', 'LocalAdapter: instance removed (no subdomain to clean up)', ['slug' => $slug]);
    }

    public function pauseSubdomain(string $slug): void
    {
        Logger::info('adapter', 'LocalAdapter: instance paused (no subdomain to disable)', ['slug' => $slug]);
    }

    public function resumeSubdomain(string $slug): void
    {
        Logger::info('adapter', 'LocalAdapter: instance resumed (no subdomain to enable)', ['slug' => $slug]);
    }

    public function verify(): array
    {
        $path = $this->instancesRoot ?: Setting::get('instances_path', SWARM_STORAGE . '/instances');

        // Check if the path is writable
        if (!is_dir($path)) {
            if (!@mkdir($path, 0755, true)) {
                return [
                    'ok'      => false,
                    'message' => "Cannot create instances directory: {$path}. Check permissions.",
                ];
            }
        }

        if (!is_writable($path)) {
            return [
                'ok'      => false,
                'message' => "Instances directory is not writable: {$path}. Check permissions.",
            ];
        }

        return [
            'ok'      => true,
            'message' => "Filesystem adapter verified. Instances deploy to: {$path}. No subdomain management — handle routing manually or use for testing.",
        ];
    }
}

