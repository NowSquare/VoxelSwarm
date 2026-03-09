# Operator Dashboard

This guide explains the operator-facing UI page by page, including the important empty states and behavior changes that depend on your settings.

For the full route inventory and visibility conditions, see [page-map.md](page-map.md).

## Authentication And Layout

- Operator pages live under `/operator/*`.
- Authentication is a single operator password set during installation.
- Sessions last 30 days.
- Login is rate-limited to 5 attempts per IP per 15 minutes.
- The shared operator layout includes:
  - Sidebar navigation: `Dashboard`, `Instances`, `Templates`, `Deployment`, `Account`, `System`
  - Mobile top bar and slide-in sidebar
  - Theme toggle
  - Logout button
  - Version badge in the sidebar footer

## Dashboard (`/operator`)

The dashboard has two distinct states.

### Onboarding State

Visible when there are no instances yet.

What you see:

- A centered welcome screen
- A 3-step onboarding checklist:
  - `Prepare a template`
  - `Configure deployment`
  - `Create your first instance`

Behavior details:

- The template step is marked complete only when at least one prepared template version exists.
- Deployment completion is not auto-detected. The dashboard keeps showing deployment as the next recommended step even after you save Deployment settings.
- The dashboard does not show stats or recent activity in onboarding mode.
- If you already have a template and want to create the first instance, go to `/operator/instances` and use `New Instance`.

### Active Dashboard State

Visible once at least one instance exists.

What you see:

- Summary cards:
  - `Total Instances`
  - `Active`
  - `Paused`
  - `Storage Used`
- `Recent Activity` table showing the last 10 provisioning log rows
- `New Instance` button

If there is no recent activity yet, the table is replaced with an empty state.

## New Instance Modal

The `New Instance` modal appears from the active dashboard and the Instances page.

Important gating behavior:

- If no prepared template exists yet, the modal does not open.
- Instead, the UI shows a confirmation dialog that sends the operator to `Templates`.

### Local Adapter Variant

When `Filesystem (Local)` is selected in Deployment:

- Title: `New Instance`
- Subtitle: deploy to the filesystem
- Required field: `Folder Name`
- Optional fields:
  - `Name`
  - `Email`
- The modal shows the current instances root path as a prefix before the folder name input.
- Folder names are limited to lowercase letters, numbers, and hyphens.
- If `Email` is left empty, the operator email is used as the fallback value.

### Domain Adapter Variant

When `Nginx`, `Forge`, `cPanel`, or `Plesk` is selected:

- Required field: `Subdomain`
- The modal shows `.{base_domain}` as the suffix
- A live preview updates as you type
- Optional fields remain `Name` and `Email`
- Subdomains are limited to lowercase letters, numbers, and hyphens.
- If `Email` is left empty, the operator email is used as the fallback value.

On successful submit:

- The instance is created immediately
- The browser redirects to `/operator/instances/{id}`

## Instances (`/operator/instances`)

This is the main instance management page.

What you see:

- Header with `New Instance`
- Search field for name or email
- Status filter dropdown
- Current result count
- Clickable table rows when instances exist

Table columns:

- `Identifier`
- `Name`
- `Email`
- `Status`
- `Type`
- `Created`

Empty states:

- `No instances yet` when nothing exists
- `No matches` when filters/search exclude all rows

Implementation note:

- The controller supports a `type` filter in the query string, but the current UI does not expose a type filter control.

## Instance Detail (`/operator/instances/{id}`)

This page is the operator view for one instance.

### Header

- Instance name
- Status badge with colored dot
- Slug in monospace
- Live URL link when the instance is active

### Actions

- `Pause` appears only when status is `active`
- `Resume` appears only when status is `paused`
- `Delete` is always available

Adapter caveat:

- Nginx pause/resume rewrites the server config into and out of a maintenance response
- The local adapter only logs pause/resume
- Forge, cPanel, and Plesk currently log warnings instead of fully toggling site availability

### Instance Details Card

Rows shown in the card:

- `Identifier`
- `URL`
- `Email`
- `Type`
- `Created`
- `Provisioned`

### Notes

- Private operator-only notes
- Saved in place with `Save Notes`

### Provision Log

Shows step-by-step deployment history for the instance.

Columns:

- `Step`
- `Status`
- `Duration`
- `Error`
- `Time`

If no rows exist yet, the page shows a log empty state instead of the table.

## Templates (`/operator/templates`)

This page has two cards: `Prepared Versions` and `Available ZIPs`.

### Prepared Versions

This is the top card.

When no prepared template exists yet:

- The page shows a `No template yet` empty state
- It explains the 3 steps:
  - upload a VoxelSite ZIP
  - process it
  - activate it

When prepared versions exist:

- Each version row shows:
  - version number
  - backing directory
  - size
  - active badge when applicable
- Non-active versions can be:
  - activated
  - deleted
- The active version cannot be deleted

### Available ZIPs

This is the lower card.

When no ZIP files exist in `template/voxelsite/`:

- The page shows a `No ZIP files found` empty state

When ZIPs exist:

- Each row shows:
  - filename
  - size
  - modified timestamp
- Available actions:
  - `Process`
  - `Delete`

Processing a ZIP:

- extracts it
- reads the internal `VERSION` file
- prepares the template
- activates that version for future instances

## Deployment (`/operator/deployment`)

This page controls how instances are provisioned, what visitors see, and how the system sends email.

It has three sections.

### Adapter

This section always shows:

- `Control Panel` dropdown
- `Test Connection` button

The currently exposed adapter choices are:

- `Filesystem (Local)`
- `Nginx (Direct Config)`
- `Laravel Forge`
- `cPanel / WHM`
- `Plesk`

The fields below the dropdown change by adapter.

#### Filesystem (Local)

Shows:

- `Instances Path`
- `Instance Limit`

No `Base Domain` field is shown in the current local-adapter Deployment UI.

#### Nginx

Shows:

- `Conf Directory`
- `Reload Command`
- `SSL Certificate`
- `SSL Key`
- `Base Domain`
- `Instance Limit`

#### Forge

Shows:

- `API Token`
- `Server ID`
- `Base Domain`
- `Instance Limit`

#### cPanel / WHM

Shows:

- `WHM Hostname`
- `API Token`
- `Base Domain`
- `Instance Limit`

#### Plesk

Shows:

- `Hostname`
- `API Key`
- `Base Domain`
- `Instance Limit`

Behavior details:

- `Test Connection` tests the values currently visible in the form, even if they are not saved yet.
- Switching between local and domain adapters changes whether the page shows a shared `Base Domain` block or the simplified local instance-limit field.

### Public Site

This section controls the public-facing root URL.

Fields:

- `Show landing page`
- `Accept signups`

Behavior matrix:

| Show landing page | Accept signups | Result |
|-------------------|----------------|--------|
| Off | Off or On | `/` redirects to `/operator/login`. The signups toggle is visually disabled. |
| On | Off | `/` shows the landing page, but signup CTAs switch to a disabled/coming-soon state and `/signup` shows a `Coming soon` message. |
| On | On | `/` shows the landing page and `/signup` shows the public signup form. |

### Notifications

This section controls email behavior.

Fields:

- `Email Driver`
- `Send Test Email`

When `Email Driver` is `SMTP`, extra fields appear:

- `SMTP Host`
- `Port`
- `Username`
- `Password`
- `From Address`
- `From Name`

When `Email Driver` is `Log to file` or `Disabled`, the SMTP fields stay hidden.

## Account (`/operator/account`)

This page has two cards.

### Email Address

Shows:

- `Email`

This address receives failure alerts and system notifications.

### Password

Shows:

- `Current Password`
- `New Password`

Behavior details:

- Leave both fields empty to keep the current password
- A new password must be at least 8 characters
- The current password is required only when changing the password

## System (`/operator/system`)

This page has four sections.

### System Status

Shows:

- `VoxelSwarm`
- `PHP`
- `SQLite`
- `Database`

### Update

Shows:

- Current app version badge
- Git availability state
- `Pull Latest` when the app is running inside a Git repository

If Git is available:

- Pull output is shown inline after the action runs

If Git is not available:

- The page shows a note that the installation is not a Git repository

### Server Logs

When no `.log` files exist yet:

- The page shows a `No log files yet` empty state

When logs exist:

- A table appears with:
  - `File`
  - `Size`
  - `Modified`
  - row actions
- Row actions:
  - download
  - delete
- `Delete All` appears only when at least one log file exists

### Danger Zone

Two destructive actions are available:

- `Refresh Installation`
  - Deletes instances, logs, ZIPs, and processed templates
  - Keeps the account and settings
- `Reset Installation`
  - Wipes the database and files
  - Sends the app back to the install wizard

Both actions require password confirmation in a modal before execution.

## Public Pages Controlled By Deployment

These pages are not part of the operator layout. The landing page and signup flow are controlled from `Deployment > Public Site`.

### Landing Page (`/`)

Visible only when `Show landing page` is enabled.

Main content:

- Hero
- marketing sections
- CTA buttons
- `Operator` link

The CTA behavior depends on `Accept signups`.

### Signup Page (`/signup`)

Visible only when the public site is enabled.

States:

- `Create your workspace` form when signups are enabled
- `Coming soon` message when signups are disabled

### Status Page (`/status/{id}`)

Visible after a public signup creates an instance.

States:

- in-progress polling state
- success state with workspace CTA
- failure state
- not-found state

## Logging

Log files are written to `storage/logs/` with daily rotation by channel:

- `provision-YYYY-MM-DD.log`
- `adapter-YYYY-MM-DD.log`
- `swarm-YYYY-MM-DD.log`
- `mail-YYYY-MM-DD.log`

See [troubleshooting.md](troubleshooting.md) for channel-by-channel details.
