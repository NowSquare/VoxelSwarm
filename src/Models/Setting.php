<?php

declare(strict_types=1);

namespace Swarm\Models;

use Swarm\Database;
use Swarm\Helpers\Crypt;

/**
 * Setting — Key-value access to the settings table.
 *
 * All Swarm configuration lives here: base_domain, adapter config,
 * mail config, operator credentials, feature flags.
 */
class Setting
{
    /**
     * Get a setting value by key. Returns null if not found.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $stmt = Database::query('SELECT value FROM settings WHERE key = ?', [$key]);
        $row  = $stmt->fetch();

        if (!$row || $row['value'] === null) {
            return $default;
        }

        return $row['value'];
    }

    /**
     * Get a JSON-decoded setting. Decrypts sensitive fields in adapter_config and mail_config.
     */
    public static function getJson(string $key, mixed $default = null): mixed
    {
        $raw = self::get($key);
        if ($raw === null) {
            return $default;
        }

        $data = json_decode($raw, true);
        if ($data === null) {
            return $default;
        }

        // Decrypt sensitive fields
        $sensitiveKeys = ['api_key', 'api_token', 'password', 'smtp_password', 'da_login_key'];
        foreach ($sensitiveKeys as $sk) {
            if (isset($data[$sk]) && is_string($data[$sk]) && str_starts_with($data[$sk], 'enc:')) {
                $data[$sk] = Crypt::decrypt(substr($data[$sk], 4));
            }
        }

        return $data;
    }

    /**
     * Set a setting value. Creates or updates.
     */
    public static function set(string $key, mixed $value): void
    {
        Database::query(
            "INSERT INTO settings (key, value, updated_at) VALUES (?, ?, datetime('now'))
             ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at",
            [$key, is_string($value) ? $value : json_encode($value)]
        );
    }

    /**
     * Set a JSON setting. Encrypts sensitive fields before storage.
     */
    public static function setJson(string $key, array $data): void
    {
        $sensitiveKeys = ['api_key', 'api_token', 'password', 'smtp_password', 'da_login_key'];
        foreach ($sensitiveKeys as $sk) {
            if (isset($data[$sk]) && is_string($data[$sk]) && !str_starts_with($data[$sk], 'enc:')) {
                $data[$sk] = 'enc:' . Crypt::encrypt($data[$sk]);
            }
        }

        self::set($key, json_encode($data));
    }

    /**
     * Get all settings as a key-value array.
     */
    public static function all(): array
    {
        $stmt = Database::query('SELECT key, value FROM settings ORDER BY key');
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }
}
