# Writing a Custom Adapter

VoxelSwarm's adapter system is designed to be extensible. If your control panel isn't supported yet, you can write an adapter in about 100-200 lines of PHP.

## The Interface

Every adapter must implement `Swarm\Adapters\ControlPanelAdapter`:

```php
<?php

declare(strict_types=1);

namespace Swarm\Adapters;

interface ControlPanelAdapter
{
    /**
     * Create routing for a new subdomain pointing to documentRoot.
     * Must be idempotent — safe to call if subdomain already exists.
     */
    public function createSubdomain(string $slug, string $documentRoot): void;

    /**
     * Remove all routing for a subdomain.
     * Must be idempotent — safe to call if subdomain doesn't exist.
     */
    public function removeSubdomain(string $slug): void;

    /**
     * Replace instance routing with a holding page response.
     */
    public function pauseSubdomain(string $slug): void;

    /**
     * Restore instance routing from paused state.
     */
    public function resumeSubdomain(string $slug): void;

    /**
     * Verify adapter can connect and has required permissions.
     * @return array{ok: bool, message: string}
     */
    public function verify(): array;

    /**
     * Add a custom domain pointing to an existing instance's document root.
     * Must be idempotent — safe to call if domain already exists.
     */
    public function addDomain(string $slug, string $domain): void;

    /**
     * Remove a custom domain from an instance.
     * Must be idempotent — safe to call if domain doesn't exist.
     */
    public function removeDomain(string $slug, string $domain): void;
}
```

## Step-by-Step

### 1. Create the Adapter Class

Create `src/Adapters/YourPanelAdapter.php`:

```php
<?php

declare(strict_types=1);

namespace Swarm\Adapters;

use Swarm\Helpers\Logger;

class YourPanelAdapter implements ControlPanelAdapter
{
    private string $hostname;
    private string $apiKey;
    private string $baseDomain;

    public function __construct(array $config)
    {
        $this->hostname = $config['hostname'] ?? '';
        $this->apiKey = $config['api_key'] ?? '';
        $this->baseDomain = \Swarm\Models\Setting::get('base_domain', '');
    }

    public function createSubdomain(string $slug, string $documentRoot): void
    {
        $subdomain = "{$slug}.{$this->baseDomain}";
        
        // Call your panel's API to create the subdomain
        $response = $this->apiRequest('POST', '/api/subdomains', [
            'subdomain' => $subdomain,
            'document_root' => $documentRoot,
        ]);

        Logger::info('adapter', "Created subdomain {$subdomain}", [
            'adapter' => 'yourpanel',
            'slug' => $slug,
        ]);
    }

    public function removeSubdomain(string $slug): void
    {
        $subdomain = "{$slug}.{$this->baseDomain}";
        
        // Call your panel's API to remove the subdomain
        $this->apiRequest('DELETE', "/api/subdomains/{$subdomain}");
    }

    public function pauseSubdomain(string $slug): void
    {
        // Option A: Set subdomain to maintenance mode via API
        // Option B: Change document root to a holding page directory
    }

    public function resumeSubdomain(string $slug): void
    {
        // Reverse whatever pauseSubdomain() did
    }

    public function verify(): array
    {
        try {
            $response = $this->apiRequest('GET', '/api/status');
            return ['ok' => true, 'message' => 'Connected to YourPanel successfully'];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function apiRequest(string $method, string $endpoint, array $data = []): array
    {
        // Your panel's API implementation
        // Log all API calls to the adapter channel
    }

    public function addDomain(string $slug, string $domain): void
    {
        $subdomain = "{$slug}.{$this->baseDomain}";

        // Call your panel's API to add a domain alias/pointer
        $this->apiRequest('POST', "/api/domains/{$subdomain}/aliases", [
            'domain' => $domain,
        ]);

        Logger::info('adapter', "Added domain alias {$domain}", [
            'adapter' => 'yourpanel',
            'slug' => $slug,
        ]);
    }

    public function removeDomain(string $slug, string $domain): void
    {
        // Call your panel's API to remove the domain alias/pointer
        // Must be idempotent: no error if alias doesn't exist
        $this->apiRequest('DELETE', "/api/domains/aliases/{$domain}");
    }
}
```

### 2. Register in AdapterFactory

Add your adapter to `src/Adapters/AdapterFactory.php`:

```php
return match ($adapter) {
    'local'     => new LocalAdapter($config),
    'nginx'     => new NginxAdapter($config),
    'forge'     => new ForgeAdapter($config),
    'cpanel'    => new CpanelAdapter($config),
    'plesk'     => new PleskAdapter($config),
    'yourpanel' => new YourPanelAdapter($config),  // Add this line
    default     => throw new \RuntimeException("Unknown adapter: {$adapter}"),
};
```

### 3. Add Deployment UI Fields

Add your fields to `views/operator/deployment.php` and expose the adapter in the same page's select list. Match the existing `adapter-fields` pattern so `onAdapterChange()` can reveal the correct panel:

```html
<div id="adapter-yourpanel" class="adapter-fields hidden">
    <div class="bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50">
        <label>Hostname</label>
        <input type="text" name="adapter_config[hostname]" />

        <label>API Key</label>
        <input type="password" name="adapter_config[api_key]" />
    </div>
</div>
```

### 4. Write Documentation

Create `docs/adapters/yourpanel.md` with:
- What the adapter does
- Required configuration (API credentials, permissions)
- How to obtain API credentials
- Known limitations
- Troubleshooting tips

### 5. Submit a PR

See [CONTRIBUTING.md](../../CONTRIBUTING.md) for PR guidelines.

## Important Rules

1. **Idempotent methods** — `createSubdomain()` must be safe to call if the subdomain already exists. `removeSubdomain()` must not error if it doesn't exist. Same for `addDomain()` and `removeDomain()`.
2. **Log everything** — Use `Logger::info('adapter', ...)` for all API calls. Operators need visibility.
3. **Never throw generic exceptions** — Use descriptive error messages that help the operator debug.
4. **Validate API responses** — Check HTTP status codes and response body for errors. Do not assume success after a non-failing network call.
5. **No shell commands if avoidable** — Prefer API calls over CLI tools. If you must use shell commands, use Symfony Process with explicit command arrays.
6. **Keep config minimal** — Only require what's strictly necessary. Derive the rest from `base_domain` and other settings.

## Reference Implementations

Study these existing adapters for patterns:

- **[NginxAdapter](../../src/Adapters/NginxAdapter.php)** — Shell-based, writes config files
- **[ForgeAdapter](../../src/Adapters/ForgeAdapter.php)** — REST API based
- **[CpanelAdapter](../../src/Adapters/CpanelAdapter.php)** — WHM API based
- **[PleskAdapter](../../src/Adapters/PleskAdapter.php)** — REST API based
