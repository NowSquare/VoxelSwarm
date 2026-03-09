# Configuration

VoxelSwarm stores runtime configuration in the SQLite `settings` table inside `storage/swarm.db`. The web install wizard creates the first values, then the operator updates them from the dashboard.

The settings UI is split across:

- `/operator/deployment` for infrastructure, public-site, and mail settings
- `/operator/account` for operator email and password

For a route-by-route map of where each setting is used, see [page-map.md](page-map.md).

## Settings Reference

### Operator-Editable Settings

| Setting | Edited In | Description | Install Default |
|---------|-----------|-------------|-----------------|
| `base_domain` | `/operator/deployment` | Base domain used for instance subdomains and public links | Value entered during install |
| `max_instances` | `/operator/deployment` | Maximum total instances allowed for public signup flow | `100` |
| `public_site_enabled` | `/operator/deployment` | Whether `/` renders the landing page instead of redirecting to operator login | `false` |
| `signups_enabled` | `/operator/deployment` | Whether the public signup form is active | `false` |
| `control_panel_adapter` | `/operator/deployment` | Active adapter: `local`, `nginx`, `forge`, `cpanel`, `plesk` | Adapter chosen during install |
| `adapter_config` | `/operator/deployment` | JSON payload for adapter-specific config | Empty or install input |
| `mail_driver` | `/operator/deployment` | `smtp`, `log`, or `null` | `log` unless changed during install |
| `mail_config` | `/operator/deployment` | JSON payload for SMTP settings | Empty unless SMTP was configured |
| `operator_email` | `/operator/account` | Address used for failure notifications and test mail | Value entered during install |
| `operator_password_hash` | `/operator/account` | bcrypt hash used for operator login | Generated during install |

### System-Managed Settings

| Setting | Description |
|---------|-------------|
| `instances_path` | Root directory where provisioned instances are stored. Created during install and updated when the local adapter is verified with a custom root. |
| `template_path` | Path used by the provisioner when copying the active VoxelSite template. Defaults to `template/voxelsite/active`. |
| `app_key` | AES-256-CBC encryption key for sensitive stored values. Generated once during install. |
| `version` | Installed VoxelSwarm version. |
| `installed_at` | ISO-8601 install timestamp. |

## Control Panel Settings

`control_panel_adapter` and `adapter_config` are stored separately:

- `control_panel_adapter` chooses the adapter class
- `adapter_config` stores the adapter's fields as JSON

See [adapters/README.md](adapters/README.md) for the current field names used by each adapter.

## Email Settings

When `mail_driver` is `log`, VoxelSwarm writes mail activity to `storage/logs/mail-YYYY-MM-DD.log` instead of sending it.

When `mail_driver` is `null`, welcome emails, failure notifications, and test mail are skipped entirely.

## Environment File

VoxelSwarm keeps `.env` intentionally small:

```env
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

Everything else lives in the database so the operator can manage it from the dashboard.

## Changing Settings

### Via Dashboard

- Visit `/operator/deployment` for deployment, public-site, and mail settings.
- Visit `/operator/account` for operator email and password.

Changes take effect immediately.

### Via CLI

```bash
php -r "
require 'src/bootstrap.php';
Swarm\Models\Setting::set('signups_enabled', 'false');
echo 'Done.';
"
```

## Sensitive Data

Adapter credentials and SMTP secrets are encrypted at rest with `app_key` by `Setting::setJson()`.

Implementation detail:

- Adapter credentials are decrypted when the Deployment page renders, then shown in password-style inputs.
- The SMTP password field is not re-populated on the Deployment page, so re-enter it only when you want to change it.
- Secrets are not written to the log files.
