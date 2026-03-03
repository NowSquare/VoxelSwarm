# Operator Dashboard

The operator dashboard is your control center for managing VoxelSwarm. Access it at `https://yourdomain.com/operator`.

## Authentication

Single operator password, set during installation. Session lasts 30 days. Rate limited to 5 login attempts per IP per 15 minutes.

To change the password, visit `/operator/settings` → Account section.

## Dashboard Overview

The main dashboard shows:

- **Summary cards:** Total instances, Active, Paused, Storage used
- **Recent activity:** Last 10 provisioning events
- **Quick action:** "New Instance" button

## Instance Management

### Instance List (`/operator/instances`)

Filterable table showing all instances:
- Identifier, name, email, status badge, type badge, created date
- **Filters:** Status (all/active/paused/provisioning/failed), Search by name or email
- **Per-row actions:** Click row to view detail page

### Instance Detail (`/operator/instances/{id}`)

- Full metadata: subdomain link, email, created date, document root
- **Actions:** Pause/Resume, Mark as Gallery Demo, Delete (with confirmation)
- **Provision log:** Timeline of provisioning steps with timestamps and durations
- **Notes:** Free-text field for operator notes

### Instance Lifecycle

| Status | Meaning |
|--------|---------|
| `queued` | Instance created, provisioning not started |
| `provisioning` | Currently being set up |
| `active` | Live and accessible |
| `paused` | Temporarily disabled (shows holding page) |
| `failed` | Provisioning failed (see logs) |

### Creating Instances

From the dashboard or instances page: "New Instance" → enter an identifier (subdomain or folder name depending on configured adapter), optional name and email → Swarm provisions immediately.

The modal adapts to the configured adapter:
- **Domain adapters** (Nginx, Forge, cPanel, Plesk): Shows a subdomain input with `.basedomain.com` suffix and live preview
- **Filesystem adapter**: Shows the instances root path prefix with a folder name input

To promote an instance to the public gallery: open instance detail → "Mark as Gallery Demo".

## Template Management (`/operator/templates`)

Manage VoxelSite versions from the dashboard. Two sections:

### Prepared Versions

Shows all extracted VoxelSite versions:
- **Version number**, directory name, size on disk
- **Active badge** — the version used for new instances
- **Actions:** Activate (switch new instances to this version), Delete

The **active** version cannot be deleted. Existing instances are not affected by version changes — they contain their own VoxelSite installation.

### Available ZIPs

Shows VoxelSite ZIP files in `template/voxelsite/`:
- **Filename**, file size, modification date
- **Process** — extracts the ZIP, reads the `VERSION` file inside, prepares the template, sets up image library, activates the version
- **Delete** — removes the ZIP file

ZIPs can have any filename (e.g., `codecanyon-yi8z1J7A-...zip`). VoxelSwarm reads the `VERSION` file from inside the ZIP to determine the version.

Upload ZIPs to the server via FTP/SSH, then process them from this page.

## Settings (`/operator/settings`)

| Section | What you configure |
|---------|-------------------|
| **General** | Base domain, max instances, public site toggle, signups toggle, gallery toggle, operator email |
| **Control Panel** | Adapter selection + adapter-specific config + "Test Connection" button |
| **Email** | SMTP settings with presets (Gmail, Outlook, Mailpit, Custom) + "Send Test" button |
| **Account** | Password change |
| **System** | PHP version, SQLite size, VoxelSwarm version, disk usage |

## Logs

Logs are stored in `storage/logs/` with daily rotation:

| Log file | Contents |
|----------|----------|
| `provision-YYYY-MM-DD.log` | Every provisioning step |
| `adapter-YYYY-MM-DD.log` | Control panel API calls |
| `swarm-YYYY-MM-DD.log` | Settings changes, operator actions |
| `mail-YYYY-MM-DD.log` | Email sends and failures |

Old logs can be deleted manually. There is no automated retention or cleanup.
