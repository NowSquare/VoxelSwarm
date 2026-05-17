# cPanel / WHM Adapter

**Status:** 🧪 Testing — we need community testing across different cPanel versions and hosting setups.

The cPanel adapter uses the [WHM API](https://api.docs.cpanel.net/) to create subdomains and manage hosting accounts.

## How It Works

- **createSubdomain:** Creates a subdomain under a cPanel account via WHM API and sets the document root
- **removeSubdomain:** Removes the subdomain via WHM API
- **pauseSubdomain:** Places a `.maintenance` marker file and a `.maintenance_page.php` holding page in the instance's document root, then prepends `.htaccess` rewrite rules that route all traffic to the 503 holding page while the marker exists. cPanel has no native maintenance mode API for subdomains.
- **resumeSubdomain:** Removes the `.maintenance` marker, `.maintenance_page.php`, and the `.htaccess` maintenance block — restoring normal traffic.
- **addDomain:** Adds a domain via WHM `adddomain` API, pointing to the instance's document root. Triggers AutoSSL for the new domain (best-effort). HTTP status and WHM response body are validated — API errors propagate as exceptions.
- **removeDomain:** Removes the domain via WHM `removedomainbyname` API. Idempotent: \"does not exist\" / \"not found\" responses are silently suppressed; other errors propagate.

**Maintenance file contract:** Pause/resume writes and removes files inside the tenant's VoxelSite document root (`.maintenance`, `.maintenance_page.php`, and a fenced block in `.htaccess`). These filenames are reserved by VoxelSwarm and must not be used by VoxelSite itself. The `.htaccess` block is delimited by `# SWARM_MAINTENANCE_START` / `# SWARM_MAINTENANCE_END` markers and is cleanly removed on resume.

## Prerequisites

- cPanel/WHM server (cPanel version 11.68+)
- WHM root or reseller access with subdomain management privileges
- WHM API token (not a cPanel token — WHM has broader permissions)
- Wildcard DNS: `*.yourdomain.com` → your server IP

⚠️ **Important:** You need a WHM API token, not a cPanel API token. WHM tokens have the privilege level needed to manage subdomains across accounts.

## Configuration

| Field | Description | Example |
|-------|-------------|---------|
| `hostname` | WHM base URL. `https://server.example.com` and `https://server.example.com:2087` are both accepted. | `https://server.example.com:2087` |
| `whm_username` | The WHM user that owns the API token. Defaults to `root`. If your token was created under a reseller or secondary admin account, enter that username. | `root` |
| `api_token` | WHM API token | `ABCDEF123456...` |

### Getting a WHM API Token

1. Log in to WHM (usually `https://yourdomain.com:2087`)
2. Go to Development → Manage API Tokens
3. Create a new token
4. Copy the token

The token must have `create-subdomains` and `delete-subdomains` in its ACL.

### WHM Username

The API token is tied to a specific WHM user. VoxelSwarm sends the token as that user in the `Authorization` header. If you created the token while logged in as `root`, leave the username as `root`. If you created it under a reseller account, enter the reseller's username.

A mismatch between the username and the token owner produces a `403 Forbidden` error on every API call.

## Troubleshooting

- **`403 Forbidden Access denied`:** The username and token don't match. Check which WHM user owns the token and set `whm_username` accordingly. Also verify the token has `create-subdomains` and `delete-subdomains` ACL permissions.
- **AutoSSL not issuing certificates:** Check WHM → SSL/TLS → Manage AutoSSL. Ensure all providers are enabled
- **Subdomain not resolving:** Verify wildcard DNS points to the cPanel server
- **504 Gateway Timeout:** WHM API can be slow on shared hosting — health check retries should handle this

## Help Us Test

If you're running cPanel, please test VoxelSwarm and [report any issues](https://github.com/NowSquare/VoxelSwarm/issues) with:
- Your cPanel version
- Hosting type (dedicated, VPS, shared, reseller)
- Any error messages from `storage/logs/adapter-YYYY-MM-DD.log`

See [../testing-feedback.md](../testing-feedback.md) for the full reporting checklist and the reasoning behind the logging model.
