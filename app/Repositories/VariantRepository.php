<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;

class VariantRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function getAll(array $filters = []): array {
        $sql = "SELECT pv.*, p.slug as product_slug, pt.name as product_name,
                       (SELECT COALESCE(SUM(pvs.stock), 0) FROM product_variant_stocks pvs WHERE pvs.variant_id = pv.id) as total_stock,
                       ml.filepath as cover_path
                FROM product_variants pv
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
                LEFT JOIN media_library ml ON pv.image_id = ml.id
                WHERE pv.deleted_at IS NULL AND p.deleted_at IS NULL";
        
        $params = [];

        if (!empty($filters['q'])) {
            $sql .= " AND (pv.sku LIKE :search OR pv.barcode LIKE :search OR pt.name LIKE :search)";
            $params[':search'] = '%' . $filters['q'] . '%';
        }

        if (isset($filters['product_id'])) {
            $sql .= " AND pv.product_id = :product_id";
            $params[':product_id'] = (int)$filters['product_id'];
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND pv.is_active = :is_active";
            $params[':is_active'] = (int)$filters['is_active'];
        }

        if (isset($filters['sku'])) {
            $sql .= " AND pv.sku = :sku";
            $params[':sku'] = $filters['sku'];
        }

        if (isset($filters['barcode'])) {
            $sql .= " AND pv.barcode = :barcode";
            $params[':barcode'] = $filters['barcode'];
        }

        if (isset($filters['category_id'])) {
            $sql .= " AND p.id IN (SELECT product_id FROM product_category_relations WHERE category_id = :cat_id)";
            $params[':cat_id'] = (int)$filters['category_id'];
        }

        if (isset($filters['brand_id'])) {
            $sql .= " AND p.brand_id = :brand_id";
            $params[':brand_id'] = (int)$filters['brand_id'];
        }

        // Color and size option value filters if provided
        if (!empty($filters['attribute_value_ids'])) {
            // E.g. array of attribute value IDs
            $valueIds = array_map('intval', $filters['attribute_value_ids']);
            if (!empty($valueIds)) {
                $valCsv = implode(',', $valueIds);
                $sql .= " AND pv.id IN (SELECT variant_id FROM product_variant_options WHERE attribute_value_id IN ({$valCsv}))";
            }
        }

        $sql .= " ORDER BY pv.id DESC";
        return $this->db->query($sql, $params);
    }

    public function getById(int $id): ?array {
        $sql = "SELECT pv.*, p.slug as product_slug, pt.name as product_name,
                       (SELECT COALESCE(SUM(pvs.stock), 0) FROM product_variant_stocks pvs WHERE pvs.variant_id = pv.id) as total_stock,
                       ml.filepath as cover_path
                FROM product_variants pv
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
                LEFT JOIN media_library ml ON pv.image_id = ml.id
                WHERE pv.id = :id AND pv.deleted_at IS NULL LIMIT 1";
        
        $rows = $this->db->query($sql, [':id' => $id]);
        $variant = $rows[0] ?? null;
        if ($variant) {
            $variant['options'] = $this->getOptions($id);
            $variant['images'] = $this->getImages($id);
            $variant['stocks'] = $this->getStocks($id);
            $variant['prices'] = $this->getPrices($id);
        }
        return $variant;
    }

    public function getByProductId(int $productId): array {
        return $this->getAll(['product_id' => $productId]);
    }

    public function getBySku(string $sku): ?array {
        $sql = "SELECT pv.* FROM product_variants pv WHERE pv.sku = :sku AND pv.deleted_at IS NULL LIMIT 1";
        $rows = $this->db->query($sql, [':sku' => $sku]);
        return $rows[0] ?? null;
    }

    public function getByBarcode(string $barcode): ?array {
        $sql = "SELECT pv.* FROM product_variants pv WHERE pv.barcode = :barcode AND pv.deleted_at IS NULL LIMIT 1";
        $rows = $this->db->query($sql, [':barcode' => $barcode]);
        return $rows[0] ?? null;
    }

    public function getOptions(int $variantId): array {
        $sql = "SELECT pvo.*, a.code as attribute_code, at.name as attribute_name, av.code as value_code, avt.name as value_name
                FROM product_variant_options pvo
                JOIN attributes a ON pvo.attribute_id = a.id
                LEFT JOIN attribute_translations at ON a.id = at.attribute_id AND at.language_id = 1
                JOIN attribute_values av ON pvo.attribute_value_id = av.id
                LEFT JOIN attribute_value_translations avt ON av.id = avt.attribute_value_id AND avt.language_id = 1
                WHERE pvo.variant_id = :vid";
        
        return $this->db->query($sql, [':vid' => $variantId]);
    }

    public function getImages(int $variantId): array {
        $sql = "SELECT pvi.*, ml.filepath, ml.filename
                FROM product_variant_images pvi
                JOIN media_library ml ON pvi.image_id = ml.id
                WHERE pvi.variant_id = :vid
                ORDER BY pvi.sort_order ASC";
        
        return $this->db->query($sql, [':vid' => $variantId]);
    }

    public function getStocks(int $variantId): array {
        $sql = "SELECT * FROM product_variant_stocks WHERE variant_id = :vid";
        return $this->db->query($sql, [':vid' => $variantId]);
    }

    public function getPrices(int $variantId): array {
        $sql = "SELECT * FROM product_variant_prices WHERE variant_id = :vid";
        return $this->db->query($sql, [':vid' => $variantId]);
    }

    public function getStockMovements(int $variantId): array {
        // Retrieve stock logs from inventories and inventory movements if matching variant
        $sql = "SELECT im.*, i.variant_id, i.product_id
                FROM inventory_movements im
                JOIN inventories i ON im.inventory_id = i.id
                WHERE i.variant_id = :vid
                ORDER BY im.id DESC";
        
        return $this->db->query($sql, [':vid' => $variantId]);
    }
}
