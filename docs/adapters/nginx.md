# Nginx Adapter

**Status:** ✅ Working

The Nginx adapter manages subdomain routing by writing per-instance server blocks to the Nginx configuration directory. It also supports custom domain management with per-domain SSL.

## How It Works

- **createSubdomain:** Writes a server block to `/etc/nginx/conf.d/{slug}.conf`, then runs `nginx -t && systemctl reload nginx`
- **removeSubdomain:** Deletes the conf file and reloads Nginx
- **pauseSubdomain:** Replaces the conf file with one that returns `503` with a holding page
- **resumeSubdomain:** Restores the original conf file and reloads
- **addDomain:** Writes a domain-specific server block to `/etc/nginx/conf.d/domain-{slug}.conf`, reloads Nginx, then runs Certbot to obtain a per-domain SSL certificate. On Certbot success, rewrites the domain block with the Let's Encrypt cert paths and reloads again.
- **removeDomain:** Deletes the domain conf file and reloads Nginx

## Prerequisites

- Nginx installed and running
- Wildcard DNS: `*.yourdomain.com` → your server IP
- Wildcard SSL certificate for `*.yourdomain.com`
- PHP process with write access to Nginx conf directory
- Permission to reload Nginx (via `sudo` or dedicated user)

### Wildcard SSL with Certbot

```bash
# Using Cloudflare DNS challenge (recommended)
certbot certonly --dns-cloudflare \
  -d "yourdomain.com" \
  -d "*.yourdomain.com"

# Certificate files will be at:
# /etc/letsencrypt/live/yourdomain.com/fullchain.pem
# /etc/letsencrypt/live/yourdomain.com/privkey.pem
```

## Configuration

| Field | Description | Example |
|-------|-------------|---------|
| `conf_dir` | Path to the writable Nginx config directory | `/etc/nginx/conf.d` |
| `reload_cmd` | Command used after config changes | `nginx -t && systemctl reload nginx` |
| `ssl_cert_path` | Path to SSL fullchain | `/etc/letsencrypt/live/yourdomain.com/fullchain.pem` |
| `ssl_key_path` | Path to SSL private key | `/etc/letsencrypt/live/yourdomain.com/privkey.pem` |

`base_domain` is configured separately on the Deployment page.

## Permissions

The PHP-FPM user (typically `www-data`) needs:

```bash
# Write access to Nginx conf directory
sudo chown www-data:www-data /etc/nginx/conf.d/swarm/
# OR use a dedicated sudoers entry for nginx reload only
echo "www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload nginx" | sudo tee /etc/sudoers.d/swarm-nginx
```

## Troubleshooting

- **"Permission denied" on conf write:** Check ownership of `conf_dir`
- **"nginx: [emerg] could not build server...":** The generated conf has an error — check `storage/logs/adapter-YYYY-MM-DD.log`
- **Subdomain not resolving:** Verify wildcard DNS with `dig test.yourdomain.com`
- **SSL errors:** Verify certificate covers `*.yourdomain.com`
