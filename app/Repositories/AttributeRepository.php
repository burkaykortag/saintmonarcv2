<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;

class AttributeRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function getAll(array $filters = []): array {
        $sql = "SELECT a.*, at.name,
                       (SELECT COUNT(*) FROM attribute_values av WHERE av.attribute_id = a.id) as value_count
                FROM attributes a
                LEFT JOIN attribute_translations at ON a.id = at.attribute_id AND at.language_id = 1
                WHERE a.deleted_at IS NULL";
        
        $params = [];

        if (isset($filters['q']) && $filters['q'] !== '') {
            $sql .= " AND (at.name LIKE :search OR a.code LIKE :search)";
            $params[':search'] = '%' . $filters['q'] . '%';
        }

        if (isset($filters['type']) && $filters['type'] !== '') {
            $sql .= " AND a.type = :type";
            $params[':type'] = $filters['type'];
        }

        $sql .= " ORDER BY a.id DESC";
        return $this->db->query($sql, $params);
    }

    public function getById(int $id): ?array {
        $sql = "SELECT a.*, at.name
                FROM attributes a
                LEFT JOIN attribute_translations at ON a.id = at.attribute_id AND at.language_id = 1
                WHERE a.id = :id AND a.deleted_at IS NULL LIMIT 1";
        
        $rows = $this->db->query($sql, [':id' => $id]);
        $attribute = $rows[0] ?? null;
        if ($attribute) {
            $attribute['values'] = $this->getValues($id);
            $attribute['translations'] = $this->getTranslations($id);
        }
        return $attribute;
    }

    public function getByCode(string $code): ?array {
        $sql = "SELECT a.*, at.name
                FROM attributes a
                LEFT JOIN attribute_translations at ON a.id = at.attribute_id AND at.language_id = 1
                WHERE a.code = :code AND a.deleted_at IS NULL LIMIT 1";
        
        $rows = $this->db->query($sql, [':code' => $code]);
        return $rows[0] ?? null;
    }

    public function getValues(int $attributeId): array {
        $sql = "SELECT av.*, avt.name
                FROM attribute_values av
                LEFT JOIN attribute_value_translations avt ON av.id = avt.attribute_value_id AND avt.language_id = 1
                WHERE av.attribute_id = :attribute_id
                ORDER BY av.id ASC";
        
        $rows = $this->db->query($sql, [':attribute_id' => $attributeId]);
        foreach ($rows as &$row) {
            $row['translations'] = $this->getValueTranslations((int)$row['id']);
        }
        return $rows;
    }

    public function getValueById(int $valueId): ?array {
        $sql = "SELECT av.*, avt.name
                FROM attribute_values av
                LEFT JOIN attribute_value_translations avt ON av.id = avt.attribute_value_id AND avt.language_id = 1
                WHERE av.id = :id LIMIT 1";
        
        $rows = $this->db->query($sql, [':id' => $valueId]);
        return $rows[0] ?? null;
    }

    public function getTranslations(int $attributeId): array {
        $sql = "SELECT * FROM attribute_translations WHERE attribute_id = :id";
        return $this->db->query($sql, [':id' => $attributeId]);
    }

    public function getValueTranslations(int $valueId): array {
        $sql = "SELECT * FROM attribute_value_translations WHERE attribute_value_id = :id";
        return $this->db->query($sql, [':id' => $valueId]);
    }

    // --- Attribute Sets ---
    public function getSets(array $filters = []): array {
        $sql = "SELECT s.*, st.name,
                       (SELECT COUNT(*) FROM product_attribute_set_items si WHERE si.set_id = s.id) as attribute_count
                FROM product_attribute_sets s
                LEFT JOIN product_attribute_set_translations st ON s.id = st.set_id AND st.language_id = 1
                WHERE s.deleted_at IS NULL";
        
        $params = [];
        if (isset($filters['q']) && $filters['q'] !== '') {
            $sql .= " AND (st.name LIKE :search OR s.code LIKE :search)";
            $params[':search'] = '%' . $filters['q'] . '%';
        }

        $sql .= " ORDER BY s.id DESC";
        return $this->db->query($sql, $params);
    }

    public function getSetById(int $id): ?array {
        $sql = "SELECT s.*, st.name
                FROM product_attribute_sets s
                LEFT JOIN product_attribute_set_translations st ON s.id = st.set_id AND st.language_id = 1
                WHERE s.id = :id AND s.deleted_at IS NULL LIMIT 1";
        
        $rows = $this->db->query($sql, [':id' => $id]);
        $set = $rows[0] ?? null;
        if ($set) {
            $set['attributes'] = $this->getSetAttributes($id);
            $set['translations'] = $this->getSetTranslations($id);
        }
        return $set;
    }

    public function getSetAttributes(int $setId): array {
        $sql = "SELECT a.*, at.name
                FROM attributes a
                JOIN product_attribute_set_items si ON a.id = si.attribute_id
                LEFT JOIN attribute_translations at ON a.id = at.attribute_id AND at.language_id = 1
                WHERE si.set_id = :set_id AND a.deleted_at IS NULL";
        
        return $this->db->query($sql, [':set_id' => $setId]);
    }

    public function getSetTranslations(int $setId): array {
        $sql = "SELECT * FROM product_attribute_set_translations WHERE set_id = :id";
        return $this->db->query($sql, [':id' => $setId]);
    }
}
