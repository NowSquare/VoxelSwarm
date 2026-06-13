# Template Preparation

Before VoxelSwarm can provision instances, you must prepare a VoxelSite template. This takes a VoxelSite ZIP file and optimizes it for multi-instance deployment.

## Why?

VoxelSite ships with an `assets/library/` directory (~15 MB of curated images used by the AI during site generation). Duplicating this into every instance wastes storage — 100 instances = 1.5 GB of identical images.

VoxelSwarm solves this by hosting the image library once in a centralized `library/` directory, served statically. Each instance receives a `library.json` manifest instead of the physical files.

## ZIP File Naming

VoxelSite ZIPs can have any filename. VoxelSwarm reads the `VERSION` file inside the ZIP to determine the version — it does **not** parse the filename. Both of these work:

- `voxelsite-v1.8.0.zip`
- `my-voxelsite-download.zip`

If no `VERSION` file is found inside the ZIP, VoxelSwarm falls back to parsing the filename (e.g., extracting `1.8.0` from `voxelsite-v1.8.0.zip`).

## Via Operator Dashboard (recommended)

The easiest way to manage templates is through the operator dashboard:

1. Upload your VoxelSite ZIP to `template/voxelsite/` via FTP/SFTP/SSH
2. Visit `/operator/templates` in the dashboard
3. You'll see your ZIP listed under "Available ZIPs"
4. Click **Process** — VoxelSwarm extracts it, reads the VERSION file, prepares the template, and activates it
5. If the version already exists, you'll get a clear message

From the dashboard you can also:
- **Activate** a different version for new instances
- **Delete** old ZIPs to free up space
- **Delete** old prepared versions you no longer need

## Via CLI

### First-Time Setup

```bash
php scripts/prepare-template.php /path/to/voxelsite.zip
```

Or if the ZIP is already in `template/voxelsite/`:

```bash
php scripts/prepare-template.php template/voxelsite/your-voxelsite.zip
```

### List Prepared Versions

```bash
php scripts/prepare-template.php --list
```

### Switch Active Version

```bash
php scripts/prepare-template.php --activate v1.8.0
```

### Regenerate Library Manifest

```bash
php scripts/prepare-template.php --regenerate
```

## Version Management

VoxelSwarm supports multiple VoxelSite versions simultaneously:

```
template/voxelsite/
├── my-voxelsite-download.zip       ← Source ZIPs (any name)
├── voxelsite-v1.9.0.zip            ← Another ZIP
├── v1.8.0/                         ← Prepared version
│   ├── VERSION
│   ├── _studio/
│   ├── assets/
│   │   └── library.json
│   └── index.php
├── v1.9.0/                         ← Another prepared version
│   └── ...
└── active → v1.9.0                 ← Symlink to active version
```

- The **active** symlink determines which version is used for new instances
- Existing instances are not affected by version changes — they contain their own VoxelSite installation
- You can keep multiple versions prepared and switch between them

## The library.json Format

```json
{
  "base_url": "https://yourdomain.com/library",
  "images": [
    "business/office-modern.jpg",
    "food/restaurant-interior.jpg"
  ]
}
```

## When to Re-Run

| Scenario | Dashboard | CLI |
|----------|-----------|-----|
| First-time setup | Upload ZIP → Process | `php scripts/prepare-template.php /path/to/voxelsite.zip` |
| New VoxelSite version | Upload new ZIP → Process | `php scripts/prepare-template.php /path/to/new.zip` |
| Images changed in `library/` | — | `php scripts/prepare-template.php --regenerate` |
| Base domain changed | — | `php scripts/prepare-template.php --regenerate` |
| Switch active version | Click Activate | `php scripts/prepare-template.php --activate v1.9.0` |
