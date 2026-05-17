<?php

declare(strict_types=1);

namespace Swarm\Services;

/**
 * DnsVerifier — Validates that a domain's DNS resolves to the expected server IP.
 *
 * Used before attaching a custom domain to an instance to ensure the
 * domain actually points to this server, preventing SSL issuance failures
 * and stale domain pointers.
 *
 * Checks both A (IPv4) and AAAA (IPv6) records.
 */
class DnsVerifier
{
    /**
     * Check if a domain resolves to the expected server IP(s).
     * Returns true if any A/AAAA record matches.
     *
     * @param string   $domain      The FQDN to check (e.g. "bakery-anna.com")
     * @param string[] $expectedIps One or more server IPs to match against
     */
    public static function verify(string $domain, array $expectedIps): bool
    {
        if (empty($expectedIps)) {
            return false;
        }

        // Check A records (IPv4)
        $aRecords = @dns_get_record($domain, DNS_A);
        if (is_array($aRecords)) {
            foreach ($aRecords as $record) {
                if (isset($record['ip']) && in_array($record['ip'], $expectedIps, true)) {
                    return true;
                }
            }
        }

        // Check AAAA records (IPv6)
        $aaaaRecords = @dns_get_record($domain, DNS_AAAA);
        if (is_array($aaaaRecords)) {
            foreach ($aaaaRecords as $record) {
                if (isset($record['ipv6']) && in_array($record['ipv6'], $expectedIps, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the server's public IP addresses for DNS comparison.
     * Reads from the `server_ip` setting first, falls back to auto-detection.
     *
     * @return string[] Array of IP addresses
     */
    public static function getServerIps(): array
    {
        $configured = \Swarm\Models\Setting::get('server_ip', '');

        if (!empty($configured)) {
            // Support comma-separated IPs (IPv4 + IPv6)
            return array_filter(array_map('trim', explode(',', $configured)));
        }

        // Auto-detect: try common methods
        $detected = [];

        // Method 1: hostname resolution
        $hostname = gethostname();
        if ($hostname) {
            $ip = gethostbyname($hostname);
            if ($ip !== $hostname && !self::isPrivateIp($ip)) {
                $detected[] = $ip;
            }
        }

        // Method 2: external service (best-effort, short timeout)
        if (empty($detected)) {
            $ctx = stream_context_create(['http' => ['timeout' => 3]]);
            $publicIp = @file_get_contents('https://api.ipify.org', false, $ctx);
            if ($publicIp && filter_var(trim($publicIp), FILTER_VALIDATE_IP)) {
                $detected[] = trim($publicIp);
            }
        }

        return $detected;
    }

    /**
     * Check if an IP is in a private/reserved range.
     */
    private static function isPrivateIp(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
