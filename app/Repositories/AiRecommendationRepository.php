<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;
use PDO;

class AiRecommendationRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    /**
     * Tüm önerileri getirir.
     */
    public function getAll(string $status = 'pending'): array {
        return $this->db->query(
            "SELECT * FROM ai_recommendations WHERE status = :status AND deleted_at IS NULL ORDER BY id DESC",
            [':status' => $status]
        );
    }

    /**
     * Öneri kaydeder.
     */
    public function save(array $data): int {
        $this->db->execute(
            "INSERT INTO ai_recommendations (type, title, description, payload, status, created_at, updated_at)
             VALUES (:type, :title, :description, :payload, :status, NOW(), NOW())",
            [
                ':type' => $data['type'],
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':payload' => $data['payload'] ? json_encode($data['payload'], JSON_UNESCAPED_UNICODE) : null,
                ':status' => $data['status'] ?? 'pending'
            ]
        );
        return (int)$this->db->query("SELECT LAST_INSERT_ID() as id")[0]['id'];
    }

    /**
     * Öneri durumunu günceller (applied / dismissed).
     */
    public function updateStatus(int $id, string $status): bool {
        return $this->db->execute(
            "UPDATE ai_recommendations SET status = :status, updated_at = NOW() WHERE id = :id",
            [':id' => $id, ':status' => $status]
        );
    }

    /**
     * Öneriyi ID'ye göre getirir.
     */
    public function getById(int $id): ?array {
        $rows = $this->db->query("SELECT * FROM ai_recommendations WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
        return $rows[0] ?? null;
    }

    /**
     * Son 30 günde en çok birlikte satılan ürün ikililerini bulur (Market Basket Analysis).
     */
    public function getFrequentlyBoughtTogether(int $limit = 10): array {
        // Aynı siparişte birlikte yer alan farklı ürün çiftlerini ve adedini gruplar
        $sql = "SELECT oi1.product_id as prod1_id, oi1.product_sku as prod1_sku, oi1.product_name as prod1_name,
                       oi2.product_id as prod2_id, oi2.product_sku as prod2_sku, oi2.product_name as prod2_name,
                       COUNT(*) as pair_count
                FROM order_items oi1
                JOIN order_items oi2 ON oi1.order_id = oi2.order_id AND oi1.product_id < oi2.product_id
                GROUP BY oi1.product_id, oi2.product_id
                ORDER BY pair_count DESC, oi1.product_id ASC
                LIMIT :limit";
        return $this->db->query($sql, [':limit' => $limit]);
    }

    /**
     * Son 60 günde hiç satılmamış fakat stoğu yüksek olan (bekleyen stoklar) ürünleri listeler.
     */
    public function getAgingStockProducts(int $days = 60, int $minStock = 10, int $limit = 10): array {
        $sql = "SELECT p.id, p.sku, p.price, p.total_stock, p.created_at,
                       (SELECT MAX(oi.created_at) FROM order_items oi WHERE oi.product_id = p.id) as last_sold_at,
                       pt.name as product_name
                FROM products p
                JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
                WHERE p.deleted_at IS NULL AND p.total_stock >= :minStock
                  AND p.id NOT IN (
                      SELECT DISTINCT product_id 
                      FROM order_items 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                  )
                ORDER BY p.total_stock DESC, p.id ASC
                LIMIT :limit";
        return $this->db->query($sql, [
            ':days' => $days,
            ':minStock' => $minStock,
            ':limit' => $limit
        ]);
    }

    /**
     * Görüntüleme sayısı yüksek ama son 30 gündeki satışı/dönüşümü düşük ürünleri bulur.
     */
    public function getHighViewsLowSalesProducts(int $minViews = 50, float $maxConversionRate = 1.0, int $limit = 10): array {
        // conversion_rate = (son 30 günde satılan adet / view_count) * 100
        $sql = "SELECT p.id, p.sku, p.price, p.total_stock, p.view_count,
                       pt.name as product_name,
                       COALESCE(SUM(oi.quantity), 0) as total_sold_qty,
                       CASE 
                         WHEN p.view_count > 0 THEN (COALESCE(SUM(oi.quantity), 0) / p.view_count) * 100 
                         ELSE 0 
                       END as conversion_rate
                FROM products p
                JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
                LEFT JOIN order_items oi ON p.id = oi.product_id AND oi.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                WHERE p.deleted_at IS NULL AND p.view_count >= :minViews
                GROUP BY p.id
                HAVING conversion_rate <= :maxConv
                ORDER BY p.view_count DESC, conversion_rate ASC
                LIMIT :limit";
        return $this->db->query($sql, [
            ':minViews' => $minViews,
            ':maxConv' => $maxConversionRate,
            ':limit' => $limit
        ]);
    }

    /**
     * Son 30 günde stoğu yüksek olup ciro payı çok düşük olan kategorileri analiz eder.
     */
    public function getAgingCategories(int $limit = 5): array {
        $sql = "SELECT c.id, ct.name as category_name,
                       SUM(p.total_stock) as total_category_stock,
                       COALESCE(SUM(oi.total), 0) as total_revenue
                FROM categories c
                JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
                JOIN product_category_relations pcr ON c.id = pcr.category_id
                JOIN products p ON pcr.product_id = p.id
                LEFT JOIN order_items oi ON p.id = oi.product_id AND oi.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                WHERE c.deleted_at IS NULL AND p.deleted_at IS NULL
                GROUP BY c.id
                ORDER BY total_category_stock DESC, total_revenue ASC
                LIMIT :limit";
        return $this->db->query($sql, [':limit' => $limit]);
    }
}
