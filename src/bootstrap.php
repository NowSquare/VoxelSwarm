<?php

declare(strict_types=1);

/**
 * VoxelSwarm — Bootstrap
 *
 * Loaded by the front controller (public/index.php) and CLI scripts.
 * Sets up autoloading, error handling, config, and database connection.
 */

// ── Composer autoloader ──
require_once __DIR__ . '/../vendor/autoload.php';

// ── Constants ──
define('SWARM_ROOT', dirname(__DIR__));
define('SWARM_STORAGE', SWARM_ROOT . '/storage');
define('SWARM_DB_PATH', SWARM_STORAGE . '/swarm.db');
define('SWARM_VERSION', trim(file_get_contents(SWARM_ROOT . '/VERSION') ?: '1.0.0'));

// ── Ensure storage directories exist ──
$storageDirs = [
    SWARM_STORAGE,
    SWARM_STORAGE . '/logs',
    SWARM_STORAGE . '/instances',
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ── Load .env if it exists ──
if (file_exists(SWARM_ROOT . '/.env')) {
    $lines = file(SWARM_ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);
            // Strip surrounding quotes
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// ── Error handling ──
$isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';

if ($isDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

set_exception_handler(function (\Throwable $e) {
    \Swarm\Logger::error('swarm', $e->getMessage(), [
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Error: {$e->getMessage()}\n");
        exit(1);
    }

    http_response_code(500);
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        echo "<pre>Error: {$e->getMessage()}\n{$e->getTraceAsString()}</pre>";
    } else {
        echo '<h1>Something went wrong</h1><p>The team has been notified.</p>';
    }
    exit;
});

// ── Database connection (lazy — only when first accessed) ──
\Swarm\Database::init(SWARM_DB_PATH);

// ── Session start for CSRF ──
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Quick helper: is VoxelSwarm installed?
 * Checks if the database has at least the settings table with an installed_at value.
 */
function isInstalled(): bool
{
    try {
        $db = \Swarm\Database::connection();
        $stmt = $db->query("SELECT value FROM settings WHERE key = 'installed_at'");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row && !empty($row['value']);
    } catch (\Throwable) {
        return false;
    }
}
