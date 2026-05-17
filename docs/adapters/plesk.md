# Plesk Adapter

**Status:** 🧪 Testing — we need community testing across different Plesk editions and server setups.

The Plesk adapter uses the [Plesk REST API](https://docs.plesk.com/en-US/obsidian/api-rpc/) to create subdomains and manage hosting.

## How It Works

- **createSubdomain:** Creates a subdomain under the operator's Plesk subscription via REST API
- **removeSubdomain:** Removes the subdomain via REST API
- **pauseSubdomain / resumeSubdomain:** Currently log warnings only. The adapter does not yet toggle maintenance mode remotely.
- **addDomain:** Creates a domain alias under the subscription's subdomain via Plesk REST API. HTTP status is validated — 4xx/5xx responses propagate as exceptions.
- **removeDomain:** Removes the alias via Plesk REST API. Idempotent — "not found" errors are silently ignored.

SSL is handled via Plesk's built-in Let's Encrypt integration.

## Prerequisites

- Plesk Obsidian (latest recommended)
- Plesk API key with subscription management permissions
- Wildcard DNS: `*.yourdomain.com` → your server IP

## Configuration

| Field | Description | Example |
|-------|-------------|---------|
| `hostname` | Plesk base URL, including the port if required | `https://server.yourdomain.com:8443` |
| `api_key` | Plesk API key | `your-api-key-here` |

### Getting a Plesk API Key

1. Log in to Plesk
2. Go to Tools & Settings → API
3. Create a new API key
4. Copy the key

## Troubleshooting

- **Authentication errors:** Verify API key is valid and has correct permissions
- **SSL not provisioning:** Check Plesk → Let's Encrypt → ensure the extension is enabled
- **Subdomain limit reached:** Check your Plesk subscription limits

## Help Us Test

If you're running Plesk, please test VoxelSwarm and [report any issues](https://github.com/NowSquare/VoxelSwarm/issues) with:
- Your Plesk version and edition
- Server OS
- Any error messages from `storage/logs/adapter-YYYY-MM-DD.log`

See [../testing-feedback.md](../testing-feedback.md) for the full reporting checklist and the reasoning behind the logging model.
