# Troubleshooting

## Provisioning Issues

### Instance stuck in "provisioning" status

If the PHP process was interrupted during provisioning (server restart, timeout), the instance may be stuck. In the operator dashboard, instances stuck for over 5 minutes show a "Retry" button.

**Manual fix:**
1. Check `storage/logs/provision-YYYY-MM-DD.log` for the last step completed
2. If the subdomain was created but health check failed, check DNS and SSL
3. Delete the failed instance from the dashboard and re-provision

### Health check fails after subdomain creation

The health checker makes an HTTP GET to `https://{slug}.{base_domain}/_studio/install.php` — it needs:

- ✅ DNS: wildcard record `*.yourdomain.com` pointing to your server
- ✅ SSL: wildcard certificate covering `*.yourdomain.com`
- ✅ Web server: subdomain routing correctly configured
- ✅ PHP: running and accessible

**Debug steps:**
1. Check if the subdomain resolves: `dig {slug}.yourdomain.com`
2. Check SSL: `curl -I https://{slug}.yourdomain.com`
3. Check the adapter log: `storage/logs/adapter-YYYY-MM-DD.log`

### Template copy fails

Usually a permissions issue. Ensure the PHP process has write access to `storage/instances/`.

```bash
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

## Adapter Issues

### "Test Connection" fails

Each adapter has specific requirements. Check [docs/adapters/](adapters/README.md) for your adapter's configuration guide.

Common issues:
- **Nginx:** PHP process needs write access to conf directory and permission to reload Nginx
- **Forge:** API token must have site management permissions
- **cPanel:** WHM API token required (not cPanel token) with subdomain management privileges
- **Plesk:** API key with subscription management permissions

### Subdomain not accessible after creation

1. Check `storage/logs/adapter-YYYY-MM-DD.log` for the API response
2. Verify DNS propagation: `dig {slug}.yourdomain.com`
3. Some control panels need a few seconds for configuration to take effect — the health checker retries 3 times with 3-second intervals

## Email Issues

### Emails not sending

1. Check `storage/logs/mail-YYYY-MM-DD.log`
2. Verify SMTP settings in `/operator/deployment`
3. Use "Send Test Email" to test the connection
4. If using Gmail: enable "App Passwords" (2FA required) or use an App Password

### Using the log driver for testing

Set email driver to `log` in Deployment → Notifications. Emails are written to `storage/logs/mail-YYYY-MM-DD.log` instead of being sent. Useful for local development.

## Database Issues

### Database locked errors

VoxelSwarm uses SQLite with WAL mode. If you see "database is locked" errors:

1. Ensure only one server process accesses the database at a time
2. Check that `storage/swarm.db-wal` and `storage/swarm.db-shm` files exist (WAL mode)
3. SQLite handles VoxelSwarm's concurrency profile (rate-limited signups, single provisioning) easily

### Resetting the database

⚠️ This deletes all instance records (not the instance files):

```bash
rm storage/swarm.db
```

After this, opening VoxelSwarm in your browser will redirect to the web install wizard. Alternatively, re-run the CLI: `php scripts/install.php`

You can also use the operator dashboard: System → Danger Zone → Reset Installation.

## Installation Issues

### Stuck on "Redirecting to /install"

The install guard redirects all requests to `/install` when VoxelSwarm is not installed. If the wizard page doesn't load:

1. Check that your web server routes all requests to `index.php`
2. Verify PHP is running: `php -v`
3. Check that `storage/` exists and is writable: `chmod -R 775 storage/`

### System check shows failures

The wizard checks PHP extensions before allowing installation. Install missing extensions through your OS package manager:

```bash
# Ubuntu/Debian
sudo apt install php8.2-sqlite3 php8.2-mbstring php8.2-curl php8.2-zip

# CentOS/RHEL
sudo dnf install php-pdo php-mbstring php-curl php-zip
```

Restart PHP-FPM or Apache after installing extensions.

### Installation fails at "Setting up VoxelSwarm"

Check the browser console for error details. Common causes:
- `storage/` not writable — fix with `chmod -R 775 storage/`
- SQLite extension not loaded — install `php-pdo` and `php-sqlite3`
- PHP memory limit too low — set `memory_limit = 128M` in `php.ini`

---

## Log Files

VoxelSwarm logs every significant operation to structured, plaintext log files. When something goes wrong, these files are your first stop — and the most helpful thing you can include when reporting an issue.

### Where logs live

```
storage/logs/
├── provision-2026-03-04.log     ← Provisioning steps, health checks
├── adapter-2026-03-04.log       ← Control panel API calls (Nginx, Forge, cPanel, Plesk)
├── mail-2026-03-04.log          ← Email sends, failures, test emails
└── swarm-2026-03-04.log         ← Settings changes, operator actions, system events
```

Each channel writes to its own file. Files rotate daily (`{channel}-YYYY-MM-DD.log`).

### What each log captures

| Channel | File | Records |
|---------|------|---------|
| **provision** | `provision-*.log` | Every provisioning step — template copy, config write, subdomain creation, health check attempts and results, completion or failure with error details |
| **adapter** | `adapter-*.log` | All control panel API calls — Nginx conf writes and reloads, Forge/cPanel/Plesk API requests and responses, connection test results |
| **mail** | `mail-*.log` | Welcome emails, failure notifications, test emails, SMTP errors. When the email driver is set to `log`, full email content is written here |
| **swarm** | `swarm-*.log` | Settings saves, password changes, instance deletions, system refresh/reset actions, unhandled errors |

### Log format

Each line follows this pattern:

```
[2026-03-04 14:32:07] INFO: Step completed: copy_template {"slug":"acme-corp","duration_ms":342}
[2026-03-04 14:32:08] ERROR: Health check attempt failed {"slug":"acme-corp","attempt":2,"status":503}
```

Format: `[timestamp] LEVEL: message {JSON context}`

Levels: `INFO` (normal operations), `WARNING` (non-fatal issues — email failures, adapter quirks), `ERROR` (failures that need attention).

### Managing logs

- **View in the dashboard:** System → System Status shows recent log activity
- **Download:** System → download individual log files
- **Delete:** System → delete old log files, or delete all at once
- **Manual access:** `cat storage/logs/provision-2026-03-04.log` via SSH
- **Retention:** No automatic cleanup. Delete old logs manually or via a cron job

---

## Reporting an Issue

VoxelSwarm runs on hosting environments we can't all test ourselves — your bug reports keep the project alive. A well-structured report saves hours of back-and-forth.

### Before you report

1. **Check this page first.** Many common issues have solutions above.
2. **Reproduce the issue.** Note the exact steps that trigger the problem.
3. **Check the logs.** Nine out of ten issues leave a trail in `storage/logs/`.

### What to include

Open an issue at [github.com/NowSquare/VoxelSwarm/issues](https://github.com/NowSquare/VoxelSwarm/issues) with:

**1. Environment**

```
VoxelSwarm version: (check VERSION file or System page)
PHP version: (php -v)
Web server: (Nginx / Apache / LiteSpeed / etc.)
OS: (Ubuntu 22.04 / CentOS 9 / etc.)
Control panel: (None / Forge / cPanel / Plesk / etc.)
Adapter: (Local / Nginx / Forge / cPanel / Plesk)
```

You can find your VoxelSwarm version and PHP version on the System page (`/operator/system`).

**2. What happened**

Describe what you did, what you expected, and what actually happened. Be specific — "provisioning failed" is hard to debug; "provisioning fails at the `create_subdomain` step with a 403 error after switching to the Forge adapter" is actionable.

**3. Relevant log entries**

Copy the relevant lines from `storage/logs/`. Focus on the time window when the issue occurred. The most useful logs depend on the issue:

| Issue type | Check these logs |
|-----------|-----------------|
| Provisioning failure | `provision-*.log` and `adapter-*.log` |
| Subdomain not working | `adapter-*.log` |
| Emails not sending | `mail-*.log` |
| Settings not saving | `swarm-*.log` |
| Test Connection failure | `adapter-*.log` |
| General errors / crashes | `swarm-*.log` |

**Tip:** If you're not sure which log matters, include the last 50 lines from all log files for the day the issue occurred:

```bash
tail -50 storage/logs/*-$(date +%Y-%m-%d).log
```

**4. Screenshots** (optional but helpful)

A screenshot of the error state in the dashboard or browser console errors can clarify things faster than a paragraph of description.

### Sensitive data

Log files may contain hostnames, email addresses, and API endpoint URLs. They do **not** contain passwords or API tokens (those are never logged). Review the log excerpts before pasting them publicly. If your report contains sensitive info, mention that in the issue and a maintainer will follow up privately.

### VoxelSite vs. VoxelSwarm issues

- **VoxelSwarm issue:** Provisioning fails, adapter errors, dashboard bugs, email delivery, subdomain routing → report on [VoxelSwarm](https://github.com/NowSquare/VoxelSwarm/issues)
- **VoxelSite issue:** The AI builder itself, template rendering, Studio bugs → report on [VoxelSite support](https://voxelsite.com/support)
