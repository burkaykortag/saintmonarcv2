<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;

class BrandRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function getAll(array $filters = []): array {
        $sql = "SELECT b.*, bt.name, bt.short_description, bt.description,
                       (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id AND p.deleted_at IS NULL) as product_count,
                       (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id IN (SELECT id FROM products p2 WHERE p2.brand_id = b.id) AND o.status != 'cancelled') as total_sales,
                       (SELECT COALESCE(SUM(oi.total), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id IN (SELECT id FROM products p2 WHERE p2.brand_id = b.id) AND o.status != 'cancelled') as total_revenue,
                       ml.filepath as logo_path, mc.filepath as cover_path, mb.filepath as banner_path
                FROM brands b
                LEFT JOIN brand_translations bt ON b.id = bt.brand_id AND bt.language_id = 1
                LEFT JOIN media_library ml ON b.logo_image_id = ml.id
                LEFT JOIN media_library mc ON b.cover_image_id = mc.id
                LEFT JOIN media_library mb ON b.banner_image_id = mb.id
                WHERE b.deleted_at IS NULL";
        
        $params = [];

        if (isset($filters['is_active'])) {
            $sql .= " AND b.is_active = :active";
            $params[':active'] = (int)$filters['is_active'];
        }

        if (isset($filters['is_featured'])) {
            $sql .= " AND b.is_featured = :featured";
            $params[':featured'] = (int)$filters['is_featured'];
        }

        if (isset($filters['show_in_home'])) {
            $sql .= " AND b.show_in_home = :home";
            $params[':home'] = (int)$filters['show_in_home'];
        }

        if (isset($filters['q']) && $filters['q'] !== '') {
            $sql .= " AND (bt.name LIKE :search OR b.slug LIKE :search OR bt.description LIKE :search)";
            $params[':search'] = '%' . $filters['q'] . '%';
        }

        $sql .= " ORDER BY b.sort_order ASC, b.id DESC";
        return $this->db->query($sql, $params);
    }

    public function getById(int $id): ?array {
        $sql = "SELECT b.*, bt.name, bt.short_description, bt.description,
                       ml.filepath as logo_path, mc.filepath as cover_path, mb.filepath as banner_path
                FROM brands b
                LEFT JOIN brand_translations bt ON b.id = bt.brand_id AND bt.language_id = 1
                LEFT JOIN media_library ml ON b.logo_image_id = ml.id
                LEFT JOIN media_library mc ON b.cover_image_id = mc.id
                LEFT JOIN media_library mb ON b.banner_image_id = mb.id
                WHERE b.id = :id AND b.deleted_at IS NULL LIMIT 1";
        
        $rows = $this->db->query($sql, [':id' => $id]);
        return $rows[0] ?? null;
    }

    public function getBySlug(string $slug): ?array {
        $sql = "SELECT b.*, bt.name, bt.short_description, bt.description,
                       ml.filepath as logo_path, mc.filepath as cover_path, mb.filepath as banner_path
                FROM brands b
                LEFT JOIN brand_translations bt ON b.id = bt.brand_id AND bt.language_id = 1
                LEFT JOIN media_library ml ON b.logo_image_id = ml.id
                LEFT JOIN media_library mc ON b.cover_image_id = mc.id
                LEFT JOIN media_library mb ON b.banner_image_id = mb.id
                WHERE b.slug = :slug AND b.deleted_at IS NULL LIMIT 1";
        
        $rows = $this->db->query($sql, [':slug' => $slug]);
        return $rows[0] ?? null;
    }
}
