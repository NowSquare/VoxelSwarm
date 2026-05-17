# DirectAdmin Adapter

**Status:** 🧪 Testing — implementation complete, seeking community validation.

## Overview

[DirectAdmin](https://www.directadmin.com/) is a lightweight web hosting control panel popular as a cPanel alternative. The adapter uses the [DirectAdmin API](https://docs.directadmin.com/developer/api/) to create and manage subdomains under the operator's account.

Like the cPanel adapter, DirectAdmin uses the **subdomain model**: all VoxelSite instances are created as subdomains of the operator's domain within a single DirectAdmin account. End users never receive DirectAdmin login credentials. See [ADR-0001](../decisions/0001-cpanel-subdomain-vs-full-account.md) for the full rationale.

## How It Works

- **createSubdomain:** `POST CMD_API_SUBDOMAINS` with `action=create` creates the subdomain `{slug}.{baseDomain}`. DirectAdmin creates a default subdomain directory at `/home/{user}/domains/{domain}/public_html/{slug}/`. The adapter then moves the provisioned VoxelSite files from `storage/instances/{slug}` into that DA directory and updates the instance's `document_root` in the database.
- **removeSubdomain:** `POST CMD_API_SUBDOMAINS` with `action=delete` removes the subdomain. Does not error if the subdomain doesn't exist (idempotent). DA handles its own directory cleanup.
- **pauseSubdomain:** Places a `.maintenance` marker file and a `.maintenance_page.php` holding page in the instance's document root, then prepends `.htaccess` rewrite rules that route all traffic to the 503 holding page while the marker exists.
- **resumeSubdomain:** Removes the `.maintenance` marker, `.maintenance_page.php`, and the `.htaccess` maintenance block — restoring normal traffic.
- **verify:** `POST CMD_API_SHOW_DOMAINS` — confirms the credentials are valid and the API is reachable.
- **addDomain:** `POST CMD_API_DOMAIN_POINTER` with `action=add` — creates a domain pointer from the custom domain to `{slug}.{baseDomain}` (the instance's subdomain), routing traffic to the correct tenant.
- **removeDomain:** `POST CMD_API_DOMAIN_POINTER` with `action=delete` on the instance's subdomain. Idempotent — "does not exist" errors are silently suppressed.

**Maintenance file contract:** Pause/resume writes and removes files inside the tenant's VoxelSite document root (`.maintenance`, `.maintenance_page.php`, and a fenced block in `.htaccess`). These filenames are reserved by VoxelSwarm and must not be used by VoxelSite itself. The `.htaccess` block is delimited by `# SWARM_MAINTENANCE_START` / `# SWARM_MAINTENANCE_END` markers and is cleanly removed on resume.

## Required Configuration

| Field | Setting Key | Description |
|-------|------------|-------------|
| Hostname | `da_hostname` | DirectAdmin server hostname or IP (without port) |
| Port | `da_port` | DirectAdmin API port (default: `2222`) |
| Username | `da_username` | DirectAdmin admin or reseller username that owns the domain |
| Login Key | `da_login_key` | Login Key for API authentication (not the account password) |
| Docroot Base | `da_docroot_base` | *(Optional)* Override the base path for subdomain directories. Default: `/home/{da_username}/domains/{base_domain}/public_html` |

## Setting Up Login Keys

Login Keys are DirectAdmin's preferred method for API authentication. They are more secure than using your account password because you can restrict them to specific API commands and IP addresses.

1. Log in to DirectAdmin at `https://your-server:2222`
2. Navigate to **User Level** → **Login Keys**
3. Click **Create Key**
4. Configure the key:
   - **Key Name:** `voxelswarm` (or any descriptive name)
   - **Allowed Commands:** Select `CMD_API_SUBDOMAINS`, `CMD_API_SHOW_DOMAINS`, and `CMD_API_DOMAIN_POINTER` (or allow all commands). `CMD_API_SUBDOMAINS` is used for subdomain creation and deletion; `CMD_API_SHOW_DOMAINS` for connection verification; `CMD_API_DOMAIN_POINTER` for custom domain management.
   - **Allowed IPs:** Restrict to your VoxelSwarm server's IP for security (recommended)
   - **Allow HTM:** Leave unchecked (API-only access)
5. Save the key and copy the generated value
6. Paste it into the **Login Key** field in VoxelSwarm's Deployment settings

## DNS Setup

Before using the adapter, configure a wildcard DNS record:

```
*.yourdomain.com  A  →  your-server-ip
```

This ensures all subdomains (e.g., `client-name.yourdomain.com`) resolve to your DirectAdmin server.

## SSL

DirectAdmin can issue Let's Encrypt certificates for subdomains. Depending on your setup:

- **Wildcard cert (recommended):** Issue a single wildcard certificate for `*.yourdomain.com` via DNS challenge. All subdomains are covered automatically.
- **Per-subdomain:** DirectAdmin can auto-issue individual Let's Encrypt certs, but this adds latency during provisioning.

## Known Limitations

1. **No native pause/resume API.** DirectAdmin doesn't expose a maintenance mode toggle for subdomains. The adapter implements pause/resume at the application level using a `.maintenance` marker file and `.htaccess` redirect to a 503 holding page. This works on both Apache and OpenLiteSpeed (the two web servers DirectAdmin supports).
2. **Files are moved, not symlinked.** DirectAdmin's permission model prevents setting custom document roots via `CMD_API_CUSTOM_HTTPD` (requires admin-level access that conflicts with user-level domain ownership). Instead, the adapter moves provisioned files into the subdomain directory that DA creates. This means VoxelSite files live in DA's directory tree rather than `storage/instances/`.
3. **URL-encoded responses.** DirectAdmin's legacy API returns URL-encoded strings rather than JSON. The adapter handles both formats, but error messages from older DirectAdmin versions may be less descriptive.
4. **Port is separate from hostname.** Unlike cPanel/WHM (which embeds the port in the hostname URL), DirectAdmin expects the port as a separate config value. The adapter constructs the URL internally.

## Why Not CMD_API_CUSTOM_HTTPD?

DirectAdmin's `CMD_API_CUSTOM_HTTPD` endpoint can set custom document roots using the `SDOCROOT` token. However, field testing revealed it is unusable for VoxelSwarm's operator model:

- `CMD_API_CUSTOM_HTTPD` requires **admin-level** access
- Subdomain creation via `CMD_API_SUBDOMAINS` requires **user-level** domain ownership
- A single Login Key cannot satisfy both requirements
- Even admin-level Login Keys with `user=` parameter passthrough are blocked from writing custom HTTPD configs

The file-move approach works reliably across all DA permission setups that support `CMD_API_SUBDOMAINS`.

## Troubleshooting

**"Could not connect" error:**
- Verify the hostname and port are correct (`2222` is default, some servers use `2223`)
- Ensure the DirectAdmin API is accessible from your VoxelSwarm server's IP
- Check firewall rules — port 2222 must be open

**"Authentication failed" error:**
- Confirm the username is the DirectAdmin account owner, not a sub-user
- Regenerate the Login Key and paste the full value (no trailing spaces)
- If using IP restrictions on the key, verify your VoxelSwarm server's outbound IP matches

**Subdomain not resolving after creation:**
- Verify the wildcard DNS record exists: `dig *.yourdomain.com`
- DirectAdmin may need a few seconds to write the Apache/Nginx vhost — the health check retry handles this
- Check DirectAdmin's error log at `/var/log/directadmin/error.log`

**Files not appearing at subdomain:**
- Check that `da_docroot_base` matches where DA creates subdomain directories on your server
- Default path is `/home/{da_username}/domains/{base_domain}/public_html/{slug}`
- Verify directory permissions allow the web server to read the moved files
