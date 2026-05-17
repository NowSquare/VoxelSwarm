# Laravel Forge Adapter

**Status:** 🧪 Testing

The Forge adapter uses the [Laravel Forge API](https://forge.laravel.com/api-documentation) to create sites on Forge-managed servers.

## How It Works

- **createSubdomain:** Creates a new site via Forge API with the subdomain and document root
- **removeSubdomain:** Deletes the site via Forge API
- **pauseSubdomain / resumeSubdomain:** Currently log warnings only. The operator UI still shows the buttons, but the Forge adapter does not yet toggle site availability.
- **addDomain:** Adds a domain alias to the Forge site via REST API. HTTP status is validated — 4xx/5xx responses propagate as exceptions with the Forge error message.
- **removeDomain:** Finds and removes the alias by domain name via Forge API. Idempotent — no error if the site or alias doesn't exist.

SSL is handled automatically by Forge via Let's Encrypt.

## Prerequisites

- A server managed by [Laravel Forge](https://forge.laravel.com/)
- Forge API token with site management permissions
- Forge Server ID
- Wildcard DNS: `*.yourdomain.com` → your Forge server IP

## Configuration

| Field | Description | Example |
|-------|-------------|---------|
| `api_token` | Forge API bearer token | `eyJ0eXAi...` |
| `server_id` | Forge server ID (visible in Forge dashboard URL) | `123456` |

### Getting Your Forge API Token

1. Log in to [forge.laravel.com](https://forge.laravel.com)
2. Go to Account → API Tokens
3. Create a new token with site management permissions
4. Copy the token — it won't be shown again

### Finding Your Server ID

The Server ID is in the Forge dashboard URL when viewing your server:
`https://forge.laravel.com/servers/123456` → ID is `123456`

## Troubleshooting

- **401 Unauthorized:** Your API token is invalid or expired
- **403 Forbidden:** Token doesn't have site management permissions
- **404 Not Found:** Server ID is incorrect
- **SSL not provisioning:** Forge handles this automatically, but DNS must resolve first

## Known Limitations

- Forge API rate limits apply (60 requests/minute)
- Site creation can take 10-30 seconds
- Custom Nginx configuration templates may conflict with Forge defaults
