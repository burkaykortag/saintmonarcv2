<?php

declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;

class ProductRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function getAll(array $filters = [], bool $onlyDeleted = false): array {
        $sql = "SELECT p.*, pt.name, pt.subtitle, pt.summary, pt.technical_specs, pt.instructions, pt.warranty, pt.delivery_info, pt.short_description, pt.description,
                       pt.box_content, pt.return_policy,
                       bt.name as brand_name,
                       ct.name as category_name, pcr.category_id,
                       ml.filepath as cover_path,
                       ml2.filepath as video_path
                FROM products p
                LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
                LEFT JOIN brand_translations bt ON p.brand_id = bt.brand_id AND bt.language_id = 1
                LEFT JOIN product_category_relations pcr ON p.id = pcr.product_id
                LEFT JOIN category_translations ct ON pcr.category_id = ct.category_id AND ct.language_id = 1
                LEFT JOIN media_library ml ON p.cover_image_id = ml.id
                LEFT JOIN media_library ml2 ON p.promo_video_id = ml2.id";

        // If tag filter is provided, join tag relations
        if (!empty($filters['tag'])) {
            $sql .= " JOIN product_tag_relations ptr ON p.id = ptr.product_id
                      JOIN product_tags ptg ON ptr.tag_id = ptg.id";
        }

        $sql .= " WHERE 1=1";
        $params = [];

        if ($onlyDeleted) {
            $sql .= " AND p.deleted_at IS NOT NULL";
        } else {
            $sql .= " AND p.deleted_at IS NULL";
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND p.is_active = :active";
            $params[':active'] = (int)$filters['is_active'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $sql .= " AND pcr.category_id = :cat_id";
            $params[':cat_id'] = (int)$filters['category_id'];
        }

        if (isset($filters['brand_id']) && $filters['brand_id'] !== '') {
            $sql .= " AND p.brand_id = :brand_id";
            $params[':brand_id'] = (int)$filters['brand_id'];
        }

        if (isset($filters['is_featured']) && $filters['is_featured'] !== '') {
            $sql .= " AND p.is_featured = :featured";
            $params[':featured'] = (int)$filters['is_featured'];
        }

        if (isset($filters['is_new']) && $filters['is_new'] !== '') {
            $sql .= " AND p.is_new = :new";
            $params[':new'] = (int)$filters['is_new'];
        }

        if (isset($filters['is_bestseller']) && $filters['is_bestseller'] !== '') {
            $sql .= " AND p.is_bestseller = :bestseller";
            $params[':bestseller'] = (int)$filters['is_bestseller'];
        }

        if (isset($filters['price_min']) && $filters['price_min'] !== '') {
            $sql .= " AND p.price >= :price_min";
            $params[':price_min'] = (float)$filters['price_min'];
        }

        if (isset($filters['price_max']) && $filters['price_max'] !== '') {
            $sql .= " AND p.price <= :price_max";
            $params[':price_max'] = (float)$filters['price_max'];
        }

        if (isset($filters['sku']) && $filters['sku'] !== '') {
            $sql .= " AND p.sku LIKE :sku_filter";
            $params[':sku_filter'] = '%' . $filters['sku'] . '%';
        }

        if (isset($filters['barcode']) && $filters['barcode'] !== '') {
            $sql .= " AND p.barcode LIKE :barcode_filter";
            $params[':barcode_filter'] = '%' . $filters['barcode'] . '%';
        }

        if (isset($filters['vendor_id']) && $filters['vendor_id'] !== '') {
            $sql .= " AND p.vendor_id = :vendor_id";
            $params[':vendor_id'] = (int)$filters['vendor_id'];
        }

        if (!empty($filters['tag'])) {
            $sql .= " AND ptg.name = :tag";
            $params[':tag'] = $filters['tag'];
        }

        if (!empty($filters['date_start'])) {
            $sql .= " AND p.created_at >= :date_start";
            $params[':date_start'] = $filters['date_start'] . ' 00:00:00';
        }

        if (!empty($filters['date_end'])) {
            $sql .= " AND p.created_at <= :date_end";
            $params[':date_end'] = $filters['date_end'] . ' 23:59:59';
        }

        if (isset($filters['q']) && $filters['q'] !== '') {
            $sql .= " AND (pt.name LIKE :search OR p.sku LIKE :search OR p.barcode LIKE :search OR p.slug LIKE :search OR p.gtin LIKE :search OR p.ean LIKE :search OR pt.short_description LIKE :search)";
            $params[':search'] = '%' . $filters['q'] . '%';
        }

        $sql .= " GROUP BY p.id ORDER BY p.id DESC";
        return $this->db->query($sql, $params);
    }

    public function getById(int $id, bool $includeDeleted = false): ?array {
        $sql = "SELECT p.*, pt.name, pt.subtitle, pt.summary, pt.technical_specs, pt.instructions, pt.warranty, pt.delivery_info, pt.short_description, pt.description,
                       pt.box_content, pt.return_policy,
                       bt.name as brand_name,
                       ct.name as category_name, pcr.category_id,
                       ml.filepath as cover_path,
                       ml2.filepath as video_path
                FROM products p
                LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
                LEFT JOIN brand_translations bt ON p.brand_id = bt.brand_id AND bt.language_id = 1
                LEFT JOIN product_category_relations pcr ON p.id = pcr.product_id
                LEFT JOIN category_translations ct ON pcr.category_id = ct.category_id AND ct.language_id = 1
                LEFT JOIN media_library ml ON p.cover_image_id = ml.id
                LEFT JOIN media_library ml2 ON p.promo_video_id = ml2.id
                WHERE p.id = :id";
        
        if (!$includeDeleted) {
            $sql .= " AND p.deleted_at IS NULL";
        }
        
        $sql .= " LIMIT 1";
        $rows = $this->db->query($sql, [':id' => $id]);
        return $rows[0] ?? null;
    }

    public function getGalleryImages(int $productId): array {
        return $this->db->query(
            "SELECT pi.*, ml.filename, ml.filepath, ml.original_name, ml.file_size, ml.mime_type
             FROM product_images pi
             JOIN media_library ml ON pi.image_id = ml.id
             WHERE pi.product_id = :pid ORDER BY pi.sort_order ASC",
            [':pid' => $productId]
        );
    }

    public function getTags(int $productId): array {
        return $this->db->query(
            "SELECT pt.* FROM product_tags pt
             JOIN product_tag_relations ptr ON pt.id = ptr.tag_id
             WHERE ptr.product_id = :pid",
            [':pid' => $productId]
        );
    }

    public function getFiles(int $productId): array {
        return $this->db->query(
            "SELECT * FROM product_files WHERE product_id = :pid ORDER BY id ASC",
            [':pid' => $productId]
        );
    }

    public function getRelations(int $productId): array {
        return $this->db->query(
            "SELECT pr.*, pt.name as related_name, p.sku as related_sku, ml.filepath as related_cover
             FROM product_relations pr
             JOIN products p ON pr.related_product_id = p.id
             LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             LEFT JOIN media_library ml ON p.cover_image_id = ml.id
             WHERE pr.product_id = :pid",
            [':pid' => $productId]
        );
    }

    public function getVariants(int $productId): array {
        $variants = $this->db->query(
            "SELECT pv.*, i.stock, ml.filepath as image_path
             FROM product_variants pv
             LEFT JOIN inventories i ON pv.id = i.variant_id
             LEFT JOIN media_library ml ON pv.image_id = ml.id
             WHERE pv.product_id = :pid AND pv.deleted_at IS NULL",
            [':pid' => $productId]
        );

        foreach ($variants as &$var) {
            $var['options'] = $this->db->query(
                "SELECT av.*, avt.value as option_value, a.id as attribute_id, at.name as attribute_name
                 FROM product_variant_option_values pvov
                 JOIN attribute_values av ON pvov.attribute_value_id = av.id
                 LEFT JOIN attribute_value_translations avt ON av.id = avt.attribute_value_id AND avt.language_id = 1
                 JOIN attributes a ON av.attribute_id = a.id
                 LEFT JOIN attribute_translations at ON a.id = at.attribute_id AND at.language_id = 1
                 WHERE pvov.variant_id = :vid",
                [':vid' => $var['id']]
            );
        }

        return $variants;
    }
}
