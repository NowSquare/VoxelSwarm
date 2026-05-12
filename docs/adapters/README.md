# Control Panel Adapters

VoxelSwarm uses an adapter system to create hosting accounts and configure your control panel. Each adapter implements the `ControlPanelAdapter` interface, making the provisioning engine panel-agnostic.

## Available Adapters

| Adapter | Status | Best For |
|---------|--------|----------|
| [Local](local.md) | ✅ Working | Development with Laravel Herd or Valet |
| [Nginx](nginx.md) | ✅ Working | Direct server access, raw VPS |
| [Laravel Forge](forge.md) | 🧪 Testing | Forge-managed servers |
| [cPanel/WHM](cpanel.md) | 🧪 Testing | Shared/reseller hosting with cPanel |
| [Plesk](plesk.md) | 🧪 Testing | Plesk-managed hosting |
| [DirectAdmin](directadmin.md) | 🧪 Testing | DirectAdmin hosting |
| [CloudPanel](cloudpanel.md) | 📋 Planned | CloudPanel hosting |
| [HestiaCP](hestiacp.md) | 📋 Planned | HestiaCP hosting |
| [CyberPanel](cyberpanel.md) | 📋 Planned | OpenLiteSpeed with CyberPanel |

## How Adapters Work

Every adapter must implement five methods:

```php
interface ControlPanelAdapter
{
    public function createSubdomain(string $slug, string $documentRoot): void;
    public function removeSubdomain(string $slug): void;
    public function pauseSubdomain(string $slug): void;
    public function resumeSubdomain(string $slug): void;
    public function verify(): array; // ['ok' => bool, 'message' => string]
}
```

The provisioner calls these methods during the instance lifecycle. It never knows which panel is running underneath — the adapter handles all panel-specific logic.

## Choosing an Adapter

- **Local development:** Use the `local` adapter with Laravel Herd or Valet
- **Raw VPS with Nginx:** Use the `nginx` adapter (requires write access to Nginx conf)
- **Managed server:** Use the adapter matching your control panel
- **Testing:** Use `local` first, then switch to your target adapter

## Configuring an Adapter

Via the operator dashboard (`/operator/deployment` → Adapter section):

1. Select your adapter from the dropdown
2. Fill in adapter-specific fields (shown dynamically)
3. Click "Test Connection" to verify
4. Save

Or during installation via `php scripts/install.php`.

## Testing Your Adapter

After configuring:

1. Use "Test Connection" in Deployment to verify connectivity
2. Provision a demo instance from the dashboard
3. Check `storage/logs/adapter-YYYY-MM-DD.log` for detailed API interactions
4. Verify the instance is accessible via its subdomain

## Writing a New Adapter

See [writing-an-adapter.md](writing-an-adapter.md) for a complete guide.
