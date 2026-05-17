# Filesystem Adapter (Local / Custom Path)

**Status:** ✅ Working

The Filesystem adapter deploys instances directly to a folder on disk. No subdomain management, no web server configuration — you handle routing yourself. Ideal for local development (Herd, Valet), testing, or custom setups where you manage your own web server.

## How It Works

- **createSubdomain:** Logs the instance registration only. The provisioner already copied the files and no web server configuration is performed.
- **removeSubdomain:** Logs the removal (directory cleanup is handled by the provisioner)
- **pauseSubdomain / resumeSubdomain:** Logs the action (no web server changes)
- **verify:** Checks that the configured instances root path exists and is writable
- **addDomain / removeDomain:** Logs the action only. No web server changes — custom domain routing must be handled manually.

The adapter focuses on filesystem operations only. If you need automatic subdomain routing, use the Nginx, Forge, cPanel, Plesk, or DirectAdmin adapter instead.

## Configuration

| Field | Value |
|-------|-------|
| Adapter | `local` |
| `instances_root` | Absolute path where instance directories are created (optional; defaults to `storage/instances/`) |

Set the instances root path in **Deployment → Adapter** when `Filesystem (Local)` is selected.

## Setup

### For local development (Herd / Valet)

1. Install [Laravel Herd](https://herd.laravel.com/) (macOS) or [Laravel Valet](https://laravel.com/docs/valet)
2. Set `base_domain` to your local domain (e.g., `voxelsite-swarm.test`)
3. Select the `Filesystem` adapter in Deployment
4. Optionally configure a custom instances root path
5. Provision a test instance

Herd/Valet handle DNS and SSL for `*.test` domains automatically when symlinks are present.

### For custom server setups

1. Select the `Filesystem` adapter in Deployment
2. Set the instances root path to your desired deployment directory (e.g., `/var/www/instances/`)
3. Configure your web server to serve each subdirectory as a separate site
4. Provision instances — files are deployed to `{instances_root}/{slug}/`

## Limitations

- No automatic subdomain creation — you manage web server config yourself
- No SSL provisioning — handle certificates through your own setup
- "Test Connection" verifies directory writability only
