<?php

declare(strict_types=1);

namespace Swarm\Models;

use Swarm\Database;

/**
 * Instance — Query methods for the instances table.
 */
class Instance
{
    /**
     * Create a new instance record. Returns the ID.
     */
    public static function create(array $data): int
    {
        $baseDomain = Setting::get('base_domain', 'localhost');

        return Database::insert(
            "INSERT INTO instances (slug, subdomain, name, email, status, type, document_root, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
            [
                $data['slug'],
                $data['slug'] . '.' . $baseDomain,
                $data['name'],
                $data['email'],
                $data['status'] ?? 'queued',
                $data['type'] ?? 'tenant',
                $data['document_root'] ?? null,
            ]
        );
    }

    /**
     * Find an instance by ID. Returns null if not found.
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::query('SELECT * FROM instances WHERE id = ?', [$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find an instance by slug.
     */
    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::query('SELECT * FROM instances WHERE slug = ?', [$slug]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find an instance by email.
     */
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::query(
            'SELECT * FROM instances WHERE email = ?',
            [$email]
        );
        return $stmt->fetch() ?: null;
    }

    /**
     * Update instance fields.
     */
    public static function update(int $id, array $data): void
    {
        $sets   = [];
        $params = [];

        foreach ($data as $key => $value) {
            $sets[]   = "{$key} = ?";
            $params[] = $value;
        }

        $sets[]   = "updated_at = datetime('now')";
        $params[] = $id;

        Database::query(
            'UPDATE instances SET ' . implode(', ', $sets) . ' WHERE id = ?',
            $params
        );
    }

    /**
     * Get filtered instance list for the operator dashboard.
     */
    public static function list(array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $where[]  = 'type = ?';
            $params[] = $filters['type'];
        }

        if (!empty($filters['search'])) {
            $where[]  = '(name LIKE ? OR email LIKE ?)';
            $term     = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $sql = 'SELECT * FROM instances WHERE ' . implode(' AND ', $where)
             . ' ORDER BY created_at DESC';

        return Database::query($sql, $params)->fetchAll();
    }

    /**
     * Count instances by status.
     */
    public static function countByStatus(): array
    {
        $stmt = Database::query(
            "SELECT status, COUNT(*) as count FROM instances GROUP BY status"
        );

        $counts = ['total' => 0, 'active' => 0, 'paused' => 0, 'provisioning' => 0, 'queued' => 0, 'failed' => 0];
        while ($row = $stmt->fetch()) {
            $counts[$row['status']] = (int) $row['count'];
            $counts['total'] += (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Check if a slug is already taken.
     */
    public static function slugExists(string $slug): bool
    {
        $stmt = Database::query('SELECT 1 FROM instances WHERE slug = ?', [$slug]);
        return (bool) $stmt->fetch();
    }

    /**
     * Hard-delete an instance (removes the row entirely).
     */
    public static function hardDelete(int $id): void
    {
        Database::query('DELETE FROM instances WHERE id = ?', [$id]);
    }
}
