<?php

declare(strict_types=1);

namespace Swarm\Controllers;

use Swarm\Helpers\Response;
use Swarm\Middleware\Csrf;
use Swarm\Models\Setting;

/**
 * SystemController — System maintenance: update, logs, reset.
 */
class SystemController
{
    /**
     * GET /operator/system — System status, update, logs, danger zone.
     */
    public function index(): void
    {
        $logFiles = $this->getLogFiles();

        Response::view('operator/system', [
            'logFiles'  => $logFiles,
            'csrfField' => Csrf::field(),
        ], 'operator');
    }

    /**
     * POST /operator/system/git-pull — Pull latest from Git remote.
     */
    public function gitPull(): void
    {
        Csrf::validate();

        if (!is_dir(SWARM_ROOT . '/.git')) {
            Response::json(['ok' => false, 'error' => 'Not a Git repository.'], 422);
        }

        $output = '';
        $returnCode = 0;

        // Execute git pull from the project root
        $cmd = 'cd ' . escapeshellarg(SWARM_ROOT) . ' && git pull 2>&1';
        exec($cmd, $outputLines, $returnCode);
        $output = implode("\n", $outputLines);

        \Swarm\Logger::info('swarm', 'Git pull executed', [
            'return_code' => $returnCode,
            'output'      => $output,
        ]);

        if ($returnCode === 0) {
            Response::json([
                'ok'      => true,
                'message' => 'Pull successful.',
                'output'  => $output,
            ]);
        } else {
            Response::json([
                'ok'     => false,
                'error'  => 'Git pull failed (exit code ' . $returnCode . ').',
                'output' => $output,
            ], 500);
        }
    }

    /**
     * GET /operator/system/logs/download — Download a log file.
     */
    public function downloadLog(): void
    {
        $file = $_GET['file'] ?? '';
        $path = SWARM_STORAGE . '/logs/' . basename($file);

        if (!file_exists($path) || !str_ends_with($path, '.log')) {
            http_response_code(404);
            echo 'Log file not found.';
            exit;
        }

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /**
     * POST /operator/system/logs/delete — Delete one or all log files.
     */
    public function deleteLog(): void
    {
        Csrf::validate();

        $file = $_POST['file'] ?? '';
        $logsDir = SWARM_STORAGE . '/logs';

        if ($file === '*') {
            // Delete all log files
            $deleted = 0;
            foreach (glob($logsDir . '/*.log') as $logPath) {
                if (unlink($logPath)) $deleted++;
            }
            \Swarm\Logger::info('swarm', "Deleted all log files ({$deleted} files)");
            Response::json(['ok' => true, 'deleted' => $deleted]);
        } else {
            $path = $logsDir . '/' . basename($file);
            if (!file_exists($path) || !str_ends_with($path, '.log')) {
                Response::json(['ok' => false, 'error' => 'File not found.'], 404);
            }
            unlink($path);
            \Swarm\Logger::info('swarm', 'Deleted log file', ['file' => $file]);
            Response::json(['ok' => true]);
        }
    }

    /**
     * POST /operator/system/refresh — Clear data but keep account & settings.
     *
     * Requires password confirmation. Deletes instances, logs, ZIPs, and
     * processed templates while preserving the database (operator account,
     * settings, deployment config).
     */
    public function refresh(): void
    {
        Csrf::validate();

        $password = trim($_POST['password'] ?? '');

        if ($password === '') {
            Response::json(['ok' => false, 'error' => 'Password is required.'], 422);
            return;
        }

        // Verify the operator password
        $hash = Setting::get('operator_password_hash', '');
        if ($hash === '' || !password_verify($password, $hash)) {
            Response::json(['ok' => false, 'error' => 'Incorrect password.'], 403);
            return;
        }

        \Swarm\Logger::warning('swarm', 'Installation refresh initiated by operator');

        try {
            // Clear all instances (DB records first to respect foreign keys, then files)
            \Swarm\Database::query("DELETE FROM provision_logs");
            \Swarm\Database::query("DELETE FROM instances");
            $instancesPath = Setting::get('instances_path', SWARM_STORAGE . '/instances');
            if (is_dir($instancesPath)) {
                $this->recursiveDelete($instancesPath);
                mkdir($instancesPath, 0755, true);
            }

            // Clear logs
            foreach (glob(SWARM_STORAGE . '/logs/*.log') as $logPath) {
                @unlink($logPath);
            }

            // Clear uploaded ZIPs
            $uploadsDir = SWARM_STORAGE . '/uploads';
            if (is_dir($uploadsDir)) {
                $this->recursiveDelete($uploadsDir);
                mkdir($uploadsDir, 0755, true);
            }

            // Clear processed templates
            $templatesDir = SWARM_STORAGE . '/templates';
            if (is_dir($templatesDir)) {
                $this->recursiveDelete($templatesDir);
                mkdir($templatesDir, 0755, true);
            }

            Response::json(['ok' => true, 'message' => 'Installation refreshed. All data cleared.']);
        } catch (\Throwable $e) {
            \Swarm\Logger::error('swarm', 'Refresh failed: ' . $e->getMessage());
            Response::json(['ok' => false, 'error' => 'Refresh failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /operator/system/reset — Full reset back to install wizard.
     *
     * Requires password confirmation. Wipes the database and all files.
     * The setup wizard will appear for fresh account creation.
     */
    public function reset(): void
    {
        Csrf::validate();

        $password = trim($_POST['password'] ?? '');

        if ($password === '') {
            Response::json(['ok' => false, 'error' => 'Password is required.'], 422);
            return;
        }

        // Verify the operator password before wiping
        $hash = Setting::get('operator_password_hash', '');
        if ($hash === '' || !password_verify($password, $hash)) {
            Response::json(['ok' => false, 'error' => 'Incorrect password.'], 403);
            return;
        }

        \Swarm\Logger::warning('swarm', 'Full installation reset initiated by operator');

        // Remove the database
        if (file_exists(SWARM_DB_PATH)) {
            unlink(SWARM_DB_PATH);
        }

        // Clear all instances
        $instancesPath = SWARM_STORAGE . '/instances';
        if (is_dir($instancesPath)) {
            $this->recursiveDelete($instancesPath);
            mkdir($instancesPath, 0755, true);
        }

        // Clear logs
        foreach (glob(SWARM_STORAGE . '/logs/*.log') as $logPath) {
            @unlink($logPath);
        }

        // Clear uploads and templates
        foreach (['uploads', 'templates'] as $dir) {
            $path = SWARM_STORAGE . '/' . $dir;
            if (is_dir($path)) {
                $this->recursiveDelete($path);
                mkdir($path, 0755, true);
            }
        }

        Response::json(['ok' => true, 'message' => 'Installation reset. Redirecting to install wizard.']);
    }

    /**
     * Get log files with metadata.
     */
    private function getLogFiles(): array
    {
        $logsDir = SWARM_STORAGE . '/logs';
        $files = [];

        if (!is_dir($logsDir)) return $files;

        foreach (glob($logsDir . '/*.log') as $path) {
            $files[] = [
                'name'     => basename($path),
                'size'     => filesize($path),
                'modified' => filemtime($path),
            ];
        }

        // Sort by modification time (newest first)
        usort($files, fn($a, $b) => $b['modified'] - $a['modified']);

        return $files;
    }

    /**
     * Recursively delete a directory.
     */
    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) return;

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }
}
