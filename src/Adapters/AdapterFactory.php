<?php

declare(strict_types=1);

namespace Swarm\Adapters;

use Swarm\Models\Setting;

/**
 * AdapterFactory — Resolves the active control panel adapter from settings.
 */
class AdapterFactory
{
    /**
     * Create from saved settings (production use).
     */
    public static function create(): ControlPanelAdapter
    {
        $adapter = Setting::get('control_panel_adapter', 'nginx');
        $config  = Setting::getJson('adapter_config', []);

        return self::createFrom($adapter, $config);
    }

    /**
     * Create from explicit values (e.g. form preview / test connection).
     */
    public static function createFrom(string $adapter, array $config): ControlPanelAdapter
    {
        return match ($adapter) {
            'local'       => new LocalAdapter($config),
            'nginx'       => new NginxAdapter($config),
            'forge'       => new ForgeAdapter($config),
            'cpanel'      => new CpanelAdapter($config),
            'plesk'       => new PleskAdapter($config),
            'directadmin' => new DirectAdminAdapter($config),
            'cloudpanel'  => new CloudPanelAdapter($config),
            'hestiacp'    => new HestiaCPAdapter($config),
            'cyberpanel'  => new CyberPanelAdapter($config),
            default       => throw new \RuntimeException("Unknown adapter: {$adapter}"),
        };
    }
}
