<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;

class CategoryRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function getAll(array $filters = []): array {
        $sql = "SELECT c.*, ct.name, ct.description, cp_trans.name as parent_name,
                       (SELECT COUNT(*) FROM product_category_relations pcr WHERE pcr.category_id = c.id) as product_count,
                       mc.filepath as cover_path, mb.filepath as banner_path, mi.filepath as icon_path
                FROM categories c
                LEFT JOIN categories cp ON c.parent_id = cp.id
                LEFT JOIN category_translations cp_trans ON cp.id = cp_trans.category_id AND cp_trans.language_id = 1
                LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
                LEFT JOIN media_library mc ON c.cover_image_id = mc.id
                LEFT JOIN media_library mb ON c.banner_image_id = mb.id
                LEFT JOIN media_library mi ON c.icon_image_id = mi.id
                WHERE c.deleted_at IS NULL";
        
        $params = [];

        if (isset($filters['is_active'])) {
            $sql .= " AND c.is_active = :active";
            $params[':active'] = (int)$filters['is_active'];
        }

        if (isset($filters['q']) && $filters['q'] !== '') {
            $sql .= " AND (ct.name LIKE :search OR c.slug LIKE :search OR ct.description LIKE :search)";
            $params[':search'] = '%' . $filters['q'] . '%';
        }

        if (isset($filters['parent_only']) && $filters['parent_only']) {
            $sql .= " AND c.parent_id IS NULL";
        }

        if (isset($filters['sub_only']) && $filters['sub_only']) {
            $sql .= " AND c.parent_id IS NOT NULL";
        }

        $sql .= " ORDER BY c.sort_order ASC, c.id ASC";
        return $this->db->query($sql, $params);
    }

    public function getById(int $id): ?array {
        $sql = "SELECT c.*, ct.name, ct.description,
                       mc.filepath as cover_path, mb.filepath as banner_path, mi.filepath as icon_path
                FROM categories c
                LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
                LEFT JOIN media_library mc ON c.cover_image_id = mc.id
                LEFT JOIN media_library mb ON c.banner_image_id = mb.id
                LEFT JOIN media_library mi ON c.icon_image_id = mi.id
                WHERE c.id = :id AND c.deleted_at IS NULL LIMIT 1";
        
        $rows = $this->db->query($sql, [':id' => $id]);
        return $rows[0] ?? null;
    }

    public function buildTree(array $categories, ?int $parentId = null): array {
        $tree = [];
        foreach ($categories as $cat) {
            $parentVal = $cat['parent_id'] !== null ? (int)$cat['parent_id'] : null;
            if ($parentVal === $parentId) {
                $children = $this->buildTree($categories, (int)$cat['id']);
                $cat['children'] = $children;
                $tree[] = $cat;
            }
        }
        return $tree;
    }

    public function getBreadcrumbs(int $categoryId): array {
        $crumbs = [];
        $currentId = $categoryId;
        while ($currentId) {
            $row = $this->db->query(
                "SELECT c.id, c.parent_id, ct.name 
                 FROM categories c
                 JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
                 WHERE c.id = :id AND c.deleted_at IS NULL LIMIT 1",
                [':id' => $currentId]
            );
            if (!empty($row)) {
                $crumbs[] = [
                    'id' => (int)$row[0]['id'],
                    'name' => $row[0]['name']
                ];
                $currentId = $row[0]['parent_id'] ? (int)$row[0]['parent_id'] : null;
            } else {
                break;
            }
        }
        return array_reverse($crumbs);
    }
}
