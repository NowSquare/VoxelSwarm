<?php

declare(strict_types=1);

namespace Swarm\Controllers;

use Swarm\Database;
use Swarm\Helpers\Response;
use Swarm\Models\Instance;

/**
 * DashboardController — Operator dashboard (default view after login).
 */
class DashboardController
{
    /**
     * GET /operator — Dashboard with summary cards and recent activity.
     */
    public function index(): void
    {
        $counts = Instance::countByStatus();

        // Calculate total storage used
        $instancesPath = \Swarm\Models\Setting::get('instances_path', SWARM_STORAGE . '/instances');
        $storageUsed   = 0;
        if (is_dir($instancesPath)) {
            $storageUsed = $this->getDirectorySize($instancesPath);
        }

        // Recent activity (last 10 provision logs)
        $recentLogs = Database::query(
            "SELECT pl.*, i.slug, i.name as instance_name
             FROM provision_logs pl
             JOIN instances i ON i.id = pl.instance_id
             ORDER BY pl.created_at DESC
             LIMIT 10"
        )->fetchAll();

        Response::view('operator/dashboard', [
            'counts'        => $counts,
            'storageUsed'   => $this->formatBytes($storageUsed),
            'recentLogs'    => $recentLogs,
            'adapter'       => \Swarm\Models\Setting::get('control_panel_adapter', 'local'),
            'baseDomain'    => \Swarm\Models\Setting::get('base_domain', 'localhost'),
            'instancesPath' => $instancesPath,
            'operatorEmail' => \Swarm\Models\Setting::get('operator_email', ''),
        ], 'operator');
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $size;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 1) . ' ' . $units[$i];
    }
}
