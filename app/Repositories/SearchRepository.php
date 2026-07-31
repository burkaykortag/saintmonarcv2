<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;
use PDO;

class SearchRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    /**
     * İndekse yeni bir içerik ekler veya günceller.
     */
    public function upsertIndex(array $data): bool {
        return $this->db->execute(
            "INSERT INTO search_index (item_type, item_id, title, content, sku, barcode, tags, price, stock_status, is_active, is_featured, is_new, is_deal, created_at, updated_at)
             VALUES (:type, :id, :title, :content, :sku, :barcode, :tags, :price, :stock, :is_active, :is_featured, :is_new, :is_deal, NOW(), NOW())
             ON DUPLICATE KEY UPDATE 
                title = VALUES(title), 
                content = VALUES(content), 
                sku = VALUES(sku), 
                barcode = VALUES(barcode), 
                tags = VALUES(tags), 
                price = VALUES(price), 
                stock_status = VALUES(stock_status), 
                is_active = VALUES(is_active), 
                is_featured = VALUES(is_featured), 
                is_new = VALUES(is_new), 
                is_deal = VALUES(is_deal),
                updated_at = NOW()",
            [
                ':type' => $data['item_type'],
                ':id' => (int)$data['item_id'],
                ':title' => $data['title'],
                ':content' => $data['content'] ?? null,
                ':sku' => $data['sku'] ?? null,
                ':barcode' => $data['barcode'] ?? null,
                ':tags' => $data['tags'] ?? null,
                ':price' => (float)($data['price'] ?? 0.0000),
                ':stock' => $data['stock_status'] ?? 'in_stock',
                ':is_active' => (int)($data['is_active'] ?? 1),
                ':is_featured' => (int)($data['is_featured'] ?? 0),
                ':is_new' => (int)($data['is_new'] ?? 0),
                ':is_deal' => (int)($data['is_deal'] ?? 0)
            ]
        );
    }

    /**
     * İndeksten içerik kaldırır.
     */
    public function deleteFromIndex(string $itemType, int $itemId): bool {
        return $this->db->execute(
            "DELETE FROM search_index WHERE item_type = :type AND item_id = :id",
            [':type' => $itemType, ':id' => $itemId]
        );
    }

    /**
     * Arama terimini kaydeder ve popüler aramalarda frekansı artırır.
     */
    public function logSearch(string $query, int $resultsCount, ?int $userId = null): void {
        $this->db->execute(
            "INSERT INTO search_logs (query, results_count, ip_address, user_agent, user_id, created_at)
             VALUES (:query, :count, :ip, :ua, :uid, NOW())",
            [
                ':query' => $query,
                ':count' => $resultsCount,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
                ':uid' => $userId
            ]
        );

        // search_popular güncelle
        $this->db->execute(
            "INSERT INTO search_popular (keyword, search_count, click_count, created_at, updated_at)
             VALUES (:kw, 1, 0, NOW(), NOW())
             ON DUPLICATE KEY UPDATE search_count = search_count + 1, updated_at = NOW()",
            [':kw' => $query]
        );

        // search_statistics güncelle
        $this->db->execute(
            "INSERT INTO search_statistics (keyword, total_searches, total_clicks, total_conversions, total_cart_additions, no_result_count, created_at, updated_at)
             VALUES (:kw, 1, 0, 0, 0, :no_res, NOW(), NOW())
             ON DUPLICATE KEY UPDATE 
                total_searches = total_searches + 1, 
                no_result_count = no_result_count + :no_res_update, 
                updated_at = NOW()",
            [
                ':kw' => $query,
                ':no_res' => $resultsCount === 0 ? 1 : 0,
                ':no_res_update' => $resultsCount === 0 ? 1 : 0
            ]
        );
    }

    /**
     * Arama tıklamasını kaydeder.
     */
    public function logClick(string $query, string $itemType, int $itemId): void {
        $this->db->execute(
            "INSERT INTO search_clicks (query, item_type, item_id, created_at)
             VALUES (:query, :type, :id, NOW())",
            [
                ':query' => $query,
                ':type' => $itemType,
                ':id' => $itemId
            ]
        );

        $this->db->execute(
            "UPDATE search_popular SET click_count = click_count + 1, updated_at = NOW() WHERE keyword = :kw",
            [':kw' => $query]
        );

        $this->db->execute(
            "UPDATE search_statistics SET total_clicks = total_clicks + 1, updated_at = NOW() WHERE keyword = :kw",
            [':kw' => $query]
        );
    }

    /**
     * Eş anlamlı kelimeleri çeker.
     */
    public function getSynonyms(): array {
        return $this->db->query("SELECT * FROM search_synonyms WHERE deleted_at IS NULL");
    }

    /**
     * Eş anlamlı kelime ekler / günceller.
     */
    public function saveSynonym(string $source, string $targets): bool {
        return $this->db->execute(
            "INSERT INTO search_synonyms (source_word, target_words, created_at, updated_at)
             VALUES (:source, :targets, NOW(), NOW())
             ON DUPLICATE KEY UPDATE target_words = VALUES(target_words), deleted_at = NULL, updated_at = NOW()",
            [':source' => trim($source), ':targets' => trim($targets)]
        );
    }

    /**
     * Eş anlamlı kelime siler (soft delete).
     */
    public function deleteSynonym(int $id): bool {
        return $this->db->execute(
            "UPDATE search_synonyms SET deleted_at = NOW() WHERE id = :id",
            [':id' => $id]
        );
    }

    /**
     * Stop words kelimelerini çeker.
     */
    public function getStopWords(): array {
        return $this->db->query("SELECT * FROM search_stop_words WHERE is_active = 1 AND deleted_at IS NULL");
    }

    /**
     * Stop word ekler.
     */
    public function saveStopWord(string $word): bool {
        return $this->db->execute(
            "INSERT INTO search_stop_words (word, is_active, created_at, updated_at)
             VALUES (:word, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE is_active = 1, deleted_at = NULL, updated_at = NOW()",
            [':word' => trim(mb_strtolower($word, 'UTF-8'))]
        );
    }

    /**
     * Stop word siler (soft delete).
     */
    public function deleteStopWord(int $id): bool {
        return $this->db->execute(
            "UPDATE search_stop_words SET deleted_at = NOW() WHERE id = :id",
            [':id' => $id]
        );
    }

    /**
     * Arama yönlendirmelerini yönetir (301 redirect).
     */
    public function getRedirects(): array {
        return $this->db->query("SELECT * FROM search_redirects WHERE is_active = 1 AND deleted_at IS NULL");
    }

    public function saveRedirect(string $keyword, string $url, int $code = 301): bool {
        return $this->db->execute(
            "INSERT INTO search_redirects (keyword, redirect_url, redirect_code, is_active, created_at, updated_at)
             VALUES (:keyword, :url, :code, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE redirect_url = VALUES(redirect_url), redirect_code = VALUES(redirect_code), is_active = 1, deleted_at = NULL, updated_at = NOW()",
            [
                ':keyword' => trim(mb_strtolower($keyword, 'UTF-8')),
                ':url' => trim($url),
                ':code' => $code
            ]
        );
    }

    public function deleteRedirect(int $id): bool {
        return $this->db->execute("UPDATE search_redirects SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);
    }

    /**
     * Boost kurallarını yönetir.
     */
    public function getBoostRules(): array {
        return $this->db->query("SELECT * FROM search_boost_rules WHERE is_active = 1 AND deleted_at IS NULL");
    }

    public function saveBoostRule(string $targetType, ?int $targetId, ?string $keyword, float $boostValue): bool {
        return $this->db->execute(
            "INSERT INTO search_boost_rules (target_type, target_id, keyword, boost_value, is_active, created_at, updated_at)
             VALUES (:type, :id, :keyword, :boost, 1, NOW(), NOW())",
            [
                ':type' => $targetType,
                ':id' => $targetId,
                ':keyword' => $keyword ? trim(mb_strtolower($keyword, 'UTF-8')) : null,
                ':boost' => $boostValue
            ]
        );
    }

    public function deleteBoostRule(int $id): bool {
        return $this->db->execute("UPDATE search_boost_rules SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);
    }

    /**
     * Kuyruk işlemleri (search_index_queue).
     */
    public function addToQueue(string $itemType, int $itemId, string $action = 'index'): bool {
        return $this->db->execute(
            "INSERT INTO search_index_queue (item_type, item_id, action, created_at)
             VALUES (:type, :id, :action, NOW())
             ON DUPLICATE KEY UPDATE action = VALUES(action), created_at = NOW()",
            [':type' => $itemType, ':id' => $itemId, ':action' => $action]
        );
    }

    public function getQueue(int $limit = 50): array {
        return $this->db->query("SELECT * FROM search_index_queue ORDER BY id ASC LIMIT :limit", [':limit' => $limit]);
    }

    public function removeFromQueue(int $id): bool {
        return $this->db->execute("DELETE FROM search_index_queue WHERE id = :id", [':id' => $id]);
    }

    /**
     * Yeniden oluşturma günlük kaydı.
     */
    public function startRebuildLog(): int {
        $this->db->execute("INSERT INTO search_rebuild_logs (started_at, status, created_at) VALUES (NOW(), 'running', NOW())");
        return (int)$this->db->query("SELECT LAST_INSERT_ID() as id")[0]['id'];
    }

    public function finishRebuildLog(int $logId, int $totalIndexed, string $status = 'success', ?string $error = null): bool {
        return $this->db->execute(
            "UPDATE search_rebuild_logs SET finished_at = NOW(), total_indexed = :total, status = :status, error_message = :err WHERE id = :id",
            [
                ':id' => $logId,
                ':total' => $totalIndexed,
                ':status' => $status,
                ':err' => $error
            ]
        );
    }

    public function getRebuildLogs(int $limit = 10): array {
        return $this->db->query("SELECT * FROM search_rebuild_logs ORDER BY id DESC LIMIT :limit", [':limit' => $limit]);
    }

    /**
     * Arama motoru cache yönetimi.
     */
    public function getCache(string $key): ?array {
        $rows = $this->db->query("SELECT * FROM search_cache WHERE cache_key = :key AND expires_at > NOW()", [':key' => $key]);
        return isset($rows[0]) ? json_decode($rows[0]['payload'], true) : null;
    }

    public function setCache(string $key, array $data, int $ttl = 3600): void {
        $this->db->execute(
            "INSERT INTO search_cache (cache_key, payload, expires_at)
             VALUES (:key, :payload, DATE_ADD(NOW(), INTERVAL :ttl SECOND))
             ON DUPLICATE KEY UPDATE payload = VALUES(payload), expires_at = VALUES(expires_at)",
            [
                ':key' => $key,
                ':payload' => json_encode($data, JSON_UNESCAPED_UNICODE),
                ':ttl' => $ttl
            ]
        );
    }

    public function clearCache(): void {
        $this->db->execute("DELETE FROM search_cache");
    }

    /**
     * Popüler aramaları çeker.
     */
    public function getPopular(int $limit = 10): array {
        return $this->db->query("SELECT * FROM search_popular ORDER BY search_count DESC LIMIT :limit", [':limit' => $limit]);
    }

    /**
     * İndekslenecek ürün verilerini çeker.
     */
    public function getProductData(int $id): ?array {
        $rows = $this->db->query(
            "SELECT p.id, p.sku, p.barcode, p.price, p.total_stock, p.stock_status, p.is_active, p.is_featured, p.is_new, p.is_deal,
                    pt.name as title, pt.short_description as content,
                    (SELECT GROUP_CONCAT(cat_t.name SEPARATOR ', ') 
                     FROM product_category_relations pcr 
                     JOIN category_translations cat_t ON pcr.category_id = cat_t.category_id AND cat_t.language_id = 1
                     WHERE pcr.product_id = p.id) as category_names
             FROM products p
             JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.id = :id AND p.deleted_at IS NULL",
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }
}
