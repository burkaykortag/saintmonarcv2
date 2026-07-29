<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;

class MediaRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function getAll(array $filters = [], int $limit = 24, int $offset = 0): array {
        $sql = "SELECT m.*, GROUP_CONCAT(t.name) as tags_list 
                FROM media_library m
                LEFT JOIN media_tag_relations mtr ON m.id = mtr.media_id
                LEFT JOIN media_tags t ON mtr.tag_id = t.id
                WHERE 1=1";
        
        $params = [];

        // Folder filter
        if (isset($filters['folder_id'])) {
            $sql .= " AND m.folder_id = :folder_id";
            $params[':folder_id'] = $filters['folder_id'];
        } elseif (!isset($filters['q']) || $filters['q'] === '') {
            // Default to root directory if no search query is active
            $sql .= " AND m.folder_id IS NULL";
        }

        // Tag filter
        if (!empty($filters['tag_id'])) {
            $sql .= " AND m.id IN (SELECT media_id FROM media_tag_relations WHERE tag_id = :tag_id)";
            $params[':tag_id'] = (int)$filters['tag_id'];
        }

        // Extension Filter
        if (!empty($filters['extension'])) {
            $sql .= " AND m.extension = :ext";
            $params[':ext'] = $filters['extension'];
        }

        // Date Filter
        if (!empty($filters['date'])) {
            if ($filters['date'] === 'today') {
                $sql .= " AND m.created_at >= DATE(NOW())";
            } elseif ($filters['date'] === 'week') {
                $sql .= " AND m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } elseif ($filters['date'] === 'month') {
                $sql .= " AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            }
        }

        // Size Filter
        if (!empty($filters['size'])) {
            if ($filters['size'] === 'small') {
                $sql .= " AND m.file_size < 500000"; // < 500KB
            } elseif ($filters['size'] === 'medium') {
                $sql .= " AND m.file_size BETWEEN 500000 AND 2000000"; // 500KB - 2MB
            } elseif ($filters['size'] === 'large') {
                $sql .= " AND m.file_size > 2000000"; // > 2MB
            }
        }

        // Search Query
        if (!empty($filters['q'])) {
            $sql .= " AND (m.filename LIKE :search OR m.original_name LIKE :search OR m.title LIKE :search OR t.name LIKE :search)";
            $params[':search'] = '%' . $filters['q'] . '%';
        }

        $sql .= " GROUP BY m.id";

        // Sorting
        $sortField = 'm.id';
        $sortOrder = 'DESC';

        if (!empty($filters['sort_by'])) {
            if ($filters['sort_by'] === 'name') {
                $sortField = 'm.original_name';
            } elseif ($filters['sort_by'] === 'size') {
                $sortField = 'm.file_size';
            } elseif ($filters['sort_by'] === 'date') {
                $sortField = 'm.created_at';
            }
        }

        if (!empty($filters['sort_order']) && in_array(strtoupper($filters['sort_order']), ['ASC', 'DESC'], true)) {
            $sortOrder = strtoupper($filters['sort_order']);
        }

        $sql .= " ORDER BY {$sortField} {$sortOrder}";
        
        // Paging
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        // Custom query to execute limits with exact integer types for PDO emulation support
        return $this->db->query($sql, $params);
    }

    public function getById(int $id): ?array {
        $sql = "SELECT m.*, GROUP_CONCAT(t.name) as tags_list 
                FROM media_library m
                LEFT JOIN media_tag_relations mtr ON m.id = mtr.media_id
                LEFT JOIN media_tags t ON mtr.tag_id = t.id
                WHERE m.id = :id GROUP BY m.id LIMIT 1";
        $rows = $this->db->query($sql, [':id' => $id]);
        return $rows[0] ?? null;
    }
}
