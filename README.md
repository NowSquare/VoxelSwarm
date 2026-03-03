# VoxelSwarm

**Deploy VoxelSite for everyone.**

VoxelSwarm is a free, open-source deployment layer that turns one [VoxelSite](https://voxelsite.com) installation into a multi-tenant platform. Each user gets their own isolated hosting account, their own AI website builder, their own API key. No SaaS. No shared infrastructure. No middlemen.

> ⚠️ **Early Access** — VoxelSwarm requires extensive testing across different hosting environments and control panels. Please [report issues](https://github.com/NowSquare/VoxelSwarm/issues). A regular VoxelSite license is all you need for testing.

---

## Why not just build a SaaS?

You could run VoxelSite as a centralized SaaS — one app, shared database, monthly subscriptions. But:

- You'd need to build and maintain a multi-tenant codebase
- You'd handle all user data (GDPR, compliance, security breaches)
- One shared AI API key — one user violating terms gets your entire platform suspended
- Billing, subscriptions, churn — revenue depends on retaining paying users month to month
- One bug affects everyone

**VoxelSwarm takes a different approach.** Each client gets a full, isolated VoxelSite installation. Each brings their own AI API key. One VPS, one license, predictable costs. No billing system needed — charge per project, per month, or give it away. Your business model.

---

## How it works

### 1. Clone VoxelSwarm

```bash
git clone https://github.com/NowSquare/VoxelSwarm.git
cd VoxelSwarm
```

That's it — all dependencies are included in the repo. No `composer install` or `npm install` needed.

### 2. Point your domain & open the wizard

Point your domain to the VoxelSwarm directory (see [installation docs](docs/installation.md) for Nginx/Apache config).

Then open `https://yourdomain.com` in your browser — VoxelSwarm detects it's not installed and launches the **setup wizard** automatically.

The wizard runs system checks, then lets you configure your domain, operator account, control panel adapter, and email — all from the browser. No SSH required.

> CLI alternative: `php scripts/install.php`

### 3. Prepare your VoxelSite template

Upload your VoxelSite ZIP (purchased from [CodeCanyon](https://voxelsite.com/buy)) to `template/voxelsite/` and process it from the **Templates** page in the operator dashboard.

This extracts VoxelSite, moves the image library to a shared location (saving ~15 MB per instance), and generates the image manifest. ZIPs can have any filename — VoxelSwarm reads the `VERSION` file inside.

> CLI alternative: `php scripts/prepare-template.php /path/to/voxelsite.zip`

### 4. Provision instances

By default, VoxelSwarm runs in **operator-only mode** — `GET /` redirects to the operator login. You provision instances from the dashboard at `/operator`. When you're ready for public self-service signups, enable the public site in Settings.

---

## Requirements

- **PHP 8.1+** with extensions: `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `zip`, `curl`
- **Web server:** Nginx (recommended) or Apache with `mod_rewrite`
- **Wildcard DNS:** `*.yourdomain.com → your server IP`
- **Wildcard SSL:** for `*.yourdomain.com` (Nginx adapter) or handled by your control panel
- **VoxelSite license:** [Regular](https://voxelsite.com/buy) for testing, [Extended](https://voxelsite.com/buy) for production multi-site
- No MySQL. No Node.js. No Redis. No Composer required — `vendor/` is included in the repo.

---

## Control Panel Adapters

VoxelSwarm uses an adapter system to create hosting accounts and configure your control panel. Each adapter implements the same interface:

| Adapter | Status | Configuration |
|---------|--------|---------------|
| **Filesystem** (Local) | ✅ Working | [docs/adapters/local.md](docs/adapters/local.md) |
| **Nginx** | ✅ Working | [docs/adapters/nginx.md](docs/adapters/nginx.md) |
| **Laravel Forge** | 🧪 Testing | [docs/adapters/forge.md](docs/adapters/forge.md) |
| **cPanel/WHM** | 🧪 Testing | [docs/adapters/cpanel.md](docs/adapters/cpanel.md) |
| **Plesk** | 🧪 Testing | [docs/adapters/plesk.md](docs/adapters/plesk.md) |
| **DirectAdmin** | 📋 Planned | [docs/adapters/directadmin.md](docs/adapters/directadmin.md) |
| **CloudPanel** | 📋 Planned | [docs/adapters/cloudpanel.md](docs/adapters/cloudpanel.md) |
| **HestiaCP** | 📋 Planned | [docs/adapters/hestiacp.md](docs/adapters/hestiacp.md) |
| **CyberPanel** | 📋 Planned | [docs/adapters/cyberpanel.md](docs/adapters/cyberpanel.md) |

**Missing your panel?** [Open an issue](https://github.com/NowSquare/VoxelSwarm/issues) or [contribute an adapter](#contributing).

---

## Architecture

```
VoxelSwarm/
├── index.php                    # Front controller
├── src/
│   ├── Adapters/                # Control panel adapter system
│   ├── Controllers/             # Route handlers
│   ├── Models/                  # SQLite data access
│   ├── Services/                # Provisioner, mailer, health checker
│   ├── Middleware/               # Auth, CSRF, throttle
│   └── Helpers/                 # Encryption, validation, response
├── views/                       # PHP templates
├── migrations/                  # SQLite schema
├── scripts/                     # install.php, prepare-template.php, migrate.php
├── storage/
│   ├── swarm.db                 # SQLite database
│   ├── instances/               # Provisioned VoxelSite installations
│   └── logs/                    # Provisioning + system logs
├── template/voxelsite/          # Prepared VoxelSite template
└── library/                     # Centralized image library
```

**Two users, one platform:**

- **The Operator** — installs VoxelSwarm, configures the adapter, manages instances from the dashboard
- **The Tenant** — signs up, gets a hosting account, builds their website with AI

---

## Documentation

| Topic | Link |
|-------|------|
| **Installation** | [docs/installation.md](docs/installation.md) |
| **Configuration** | [docs/configuration.md](docs/configuration.md) |
| **Template Preparation** | [docs/template-preparation.md](docs/template-preparation.md) |
| **Operator Dashboard** | [docs/operator-dashboard.md](docs/operator-dashboard.md) |
| **Adapters Overview** | [docs/adapters/README.md](docs/adapters/README.md) |
| **Writing an Adapter** | [docs/adapters/writing-an-adapter.md](docs/adapters/writing-an-adapter.md) |
| **Troubleshooting** | [docs/troubleshooting.md](docs/troubleshooting.md) |
| **Updating** | [docs/updating.md](docs/updating.md) |

---

## Licensing

VoxelSwarm itself is **free and open source** (MIT License).

VoxelSite is a commercial product sold on [CodeCanyon](https://voxelsite.com/buy). A **regular license** is all you need to test VoxelSwarm. For production multi-site use, an **extended license** is required per VPS/server.

> **Start with the regular license.** Get VoxelSwarm running on your setup first. Report issues. Help us stabilize it. Early testers are granted extended license use.

---

## Contributing

VoxelSwarm is open source because the only way to make it work on every hosting environment is to let people test it on their hosting environment. Contributions are welcome:

- **Report bugs** — [Open an issue](https://github.com/NowSquare/VoxelSwarm/issues) with your hosting setup details
- **Write an adapter** — See [docs/adapters/writing-an-adapter.md](docs/adapters/writing-an-adapter.md)
- **Fix edge cases** — Every control panel has quirks. Your PR helps everyone
- **Improve docs** — Clearer instructions save hours of debugging

Please read [CONTRIBUTING.md](CONTRIBUTING.md) before submitting a pull request.

---

## Support

- **Issues:** [github.com/NowSquare/VoxelSwarm/issues](https://github.com/NowSquare/VoxelSwarm/issues)
- **VoxelSite:** [voxelsite.com](https://voxelsite.com)
- **Multi-Site:** [voxelsite.com/multi-site](https://voxelsite.com/multi-site)

---

## License

MIT — see [LICENSE](LICENSE).

VoxelSite is a separate commercial product. VoxelSwarm is a deployment layer for VoxelSite, not a fork or redistribution.

---

*VoxelSite builds the website. VoxelSwarm puts it in front of the world.*
