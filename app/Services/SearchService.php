<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\SearchRepository;
use App\Services\AiRecommendationServiceInterface;
use Core\Contracts\CacheInterface;
use PDO;

class SearchService {
    private SearchRepository $repository;
    private AiRecommendationServiceInterface $aiService;
    private CacheInterface $cache;

    // Türkçe karakter haritası
    private const TURKISH_MAP = [
        'ş' => 's', 'Ş' => 's',
        'ı' => 'i', 'İ' => 'i', 'I' => 'i',
        'ç' => 'c', 'Ç' => 'c',
        'ö' => 'o', 'Ö' => 'o',
        'ü' => 'u', 'Ü' => 'u',
        'ğ' => 'g', 'Ğ' => 'g'
    ];

    public function __construct(
        SearchRepository $repository, 
        AiRecommendationServiceInterface $aiService,
        CacheInterface $cache
    ) {
        $this->repository = $repository;
        $this->aiService = $aiService;
        $this->cache = $cache;
    }

    /**
     * Türkçe karakterleri normalize eder (ı -> i, ş -> s vb.) ve küçük harfe çevirir.
     */
    public function normalizeText(string $text): string {
        $text = strtr($text, self::TURKISH_MAP);
        return mb_strtolower(trim($text), 'UTF-8');
    }

    /**
     * Stop words kelimelerini filtreler.
     */
    public function filterStopWords(array $tokens): array {
        $stopWords = $this->repository->getStopWords();
        $stopWordsList = array_column($stopWords, 'word');
        
        // Varsayılan stop-words fallback listesi
        $defaultStopWords = ['ve', 'ile', 'icin', 'bir', 'cok', 'en', 'gibi', 'de', 'da', 'ise'];
        $allStopWords = array_unique(array_merge($stopWordsList, $defaultStopWords));

        return array_filter($tokens, function($t) use ($allStopWords) {
            return !in_array($this->normalizeText($t), $allStopWords);
        });
    }

    /**
     * Eş anlamlı kelimeleri çözer.
     */
    public function resolveSynonyms(array $tokens): array {
        $synonyms = $this->repository->getSynonyms();
        $resolved = $tokens;

        foreach ($synonyms as $syn) {
            $source = $this->normalizeText($syn['source_word']);
            if (in_array($source, $tokens)) {
                $targets = explode(',', $syn['target_words']);
                foreach ($targets as $t) {
                    $normTarget = $this->normalizeText($t);
                    if (!in_array($normTarget, $resolved)) {
                        $resolved[] = $normTarget;
                    }
                }
            }
        }
        return $resolved;
    }

    /**
     * Arama motorunun ana sorgu işleyicisi (Enterprise Search).
     */
    public function search(string $query, array $filters = [], int $page = 1, int $perPage = 12): array {
        $cacheKey = 'search_' . md5($query . json_encode($filters) . '_' . $page . '_' . $perPage);
        $cached = $this->repository->getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // 1. Arama terimini temizle ve tokenize et
        $queryNormalized = $this->normalizeText($query);
        
        // 2. Redirect Kontrolü (301 Yönlendirme)
        $redirects = $this->repository->getRedirects();
        foreach ($redirects as $r) {
            if ($this->normalizeText($r['keyword']) === $queryNormalized) {
                return [
                    'redirect' => true,
                    'url' => $r['redirect_url'],
                    'code' => $r['redirect_code']
                ];
            }
        }

        $tokens = explode(' ', $queryNormalized);
        $tokens = $this->filterStopWords($tokens);
        $tokens = $this->resolveSynonyms($tokens);

        if (empty($tokens)) {
            return [
                'query' => $query,
                'total' => 0,
                'results' => [],
                'suggestions' => $this->getSuggestions($query)
            ];
        }

        // 3. Boost kurallarını yükle
        $boostRules = $this->repository->getBoostRules();

        // 4. SQL Arama Sorgusunu Oluştur
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        
        $sql = "SELECT si.* FROM search_index si WHERE si.is_active = 1 AND si.deleted_at IS NULL";
        $params = [];

        // Kelime eşleşme koşulu
        $conditions = [];
        $idx = 0;
        foreach ($tokens as $t) {
            $conditions[] = "(si.title LIKE :t{$idx}_1 OR si.content LIKE :t{$idx}_2 OR si.sku LIKE :t{$idx}_3 OR si.barcode LIKE :t{$idx}_4 OR si.tags LIKE :t{$idx}_5)";
            $params[":t{$idx}_1"] = '%' . $t . '%';
            $params[":t{$idx}_2"] = '%' . $t . '%';
            $params[":t{$idx}_3"] = '%' . $t . '%';
            $params[":t{$idx}_4"] = '%' . $t . '%';
            $params[":t{$idx}_5"] = '%' . $t . '%';
            $idx++;
        }
        $sql .= " AND (" . implode(' OR ', $conditions) . ")";

        // Filtre Uygulamaları
        if (!empty($filters['price_min'])) {
            $sql .= " AND si.price >= :price_min";
            $params[':price_min'] = (float)$filters['price_min'];
        }
        if (!empty($filters['price_max'])) {
            $sql .= " AND si.price <= :price_max";
            $params[':price_max'] = (float)$filters['price_max'];
        }
        if (!empty($filters['stock_status'])) {
            $sql .= " AND si.stock_status = :stock";
            $params[':stock'] = $filters['stock_status'];
        }
        if (!empty($filters['is_featured'])) {
            $sql .= " AND si.is_featured = 1";
        }
        if (!empty($filters['is_new'])) {
            $sql .= " AND si.is_new = 1";
        }
        if (!empty($filters['is_deal'])) {
            $sql .= " AND si.is_deal = 1";
        }

        $allResults = $db->query($sql, $params);

        // 5. Skorlama ve Boost Kurallarının Hesaplanması
        $scoredResults = [];
        foreach ($allResults as $row) {
            $score = 0.0;
            
            // Temel başlık ve içerik eşleşme skoru
            $titleNorm = $this->normalizeText($row['title']);
            $contentNorm = $this->normalizeText($row['content'] ?? '');
            
            foreach ($tokens as $t) {
                if (str_contains($titleNorm, $t)) {
                    $score += 10.0; // Başlıkta eşleşme yüksek puan
                }
                if (str_contains($contentNorm, $t)) {
                    $score += 2.0; // İçerikte eşleşme düşük puan
                }
                if ($this->normalizeText($row['sku'] ?? '') === $t) {
                    $score += 25.0; // Birebir SKU eşleşmesi
                }
            }

            // Boost Kurallarını Uygula
            foreach ($boostRules as $rule) {
                if ($rule['target_type'] === 'product' && $row['item_type'] === 'product' && (int)$rule['target_id'] === (int)$row['item_id']) {
                    $score *= (float)$rule['boost_value'];
                }
                if ($rule['target_type'] === 'keyword' && !empty($rule['keyword'])) {
                    if (str_contains($queryNormalized, $this->normalizeText($rule['keyword']))) {
                        $score *= (float)$rule['boost_value'];
                    }
                }
            }

            $row['score'] = $score;
            $scoredResults[] = $row;
        }

        // Skora göre sırala
        usort($scoredResults, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // 6. Sayfalama (Pagination)
        $total = count($scoredResults);
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($scoredResults, $offset, $perPage);

        $response = [
            'query' => $query,
            'total' => $total,
            'results' => $paginated,
            'page' => $page,
            'per_page' => $perPage,
            'suggestions' => $total === 0 ? $this->getSuggestions($query) : []
        ];

        // Arama kaydı loglama
        $this->repository->logSearch($query, $total);

        // Önbelleğe kaydet
        $this->repository->setCache($cacheKey, $response, 300);

        return $response;
    }

    public function autocomplete(string $prefix, int $limit = 5): array {
        $prefixNorm = $this->normalizeText($prefix);
        if (empty($prefixNorm)) {
            return [];
        }

        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $rows = $db->query(
            "SELECT title as text, item_type as type, item_id as id 
             FROM search_index 
             WHERE is_active = 1 AND deleted_at IS NULL
               AND (title LIKE :pref OR sku LIKE :pref2)
             ORDER BY is_featured DESC, title ASC 
             LIMIT :limit",
            [':pref' => $prefixNorm . '%', ':pref2' => $prefixNorm . '%', ':limit' => $limit]
        );
        return $rows;
    }

    /**
     * Yazım hatalarını tolere eden ve Levenshtein mesafesine göre öneri üreten motor (Suggestion Engine).
     */
    public function getSuggestions(string $query, int $limit = 5): array {
        $queryNorm = $this->normalizeText($query);
        if (empty($queryNorm)) {
            return [];
        }

        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        
        // Veritabanındaki tüm indeks kelimelerini çekelim
        $keywords = $db->query("SELECT DISTINCT keyword FROM search_keywords");
        $suggestions = [];

        foreach ($keywords as $kw) {
            $word = $kw['keyword'];
            $dist = levenshtein($queryNorm, $word);
            
            // Mesafe eşiği: Kelime uzunluğuna göre tolere edilen hata sınırı
            if ($dist > 0 && $dist <= 2) {
                $suggestions[] = [
                    'word' => $word,
                    'distance' => $dist
                ];
            }
        }

        usort($suggestions, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        return array_slice(array_column($suggestions, 'word'), 0, $limit);
    }

    /**
     * AI Akıllı Arama Entegrasyonu (Sprint 17 AI Engine entegre arama).
     */
    public function aiSearch(string $query): array {
        // AI niyet çözümleme simülasyonu
        $queryNorm = $this->normalizeText($query);
        $intent = "Genel Ürün Arama";
        $filters = [];

        if (str_contains($queryNorm, 'ucuz') || str_contains($queryNorm, 'indirim')) {
            $intent = "Fiyat Hassasiyetli Arama";
            $filters['is_deal'] = 1;
        }
        if (str_contains($queryNorm, 'yeni') || str_contains($queryNorm, 'sezon')) {
            $intent = "Yeni Ürün Eğilimi";
            $filters['is_new'] = 1;
        }

        // AI arama sonucunu logla
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $db->execute(
            "INSERT INTO search_ai_queries (original_query, resolved_intent, proposed_filters, created_at)
             VALUES (:q, :intent, :filters, NOW())",
            [
                ':q' => $query,
                ':intent' => $intent,
                ':filters' => json_encode($filters)
            ]
        );

        return $this->search($query, $filters);
    }

    /**
     * Arama indeksini sıfırdan yeniden oluşturur (Rebuild Index).
     */
    public function rebuildIndex(): int {
        $logId = $this->repository->startRebuildLog();
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);

        try {
            // Önce indeksi temizleyelim
            $db->execute("DELETE FROM search_index");
            $total = 0;

            // 1. Ürünleri İndeksle
            $products = $db->query("SELECT id FROM products WHERE deleted_at IS NULL");
            foreach ($products as $p) {
                $pData = $this->repository->getProductData((int)$p['id']);
                if ($pData) {
                    $this->repository->upsertIndex([
                        'item_type' => 'product',
                        'item_id' => $pData['id'],
                        'title' => $pData['title'],
                        'content' => $pData['content'] . ' ' . $pData['category_names'],
                        'sku' => $pData['sku'],
                        'barcode' => $pData['barcode'],
                        'tags' => $pData['category_names'],
                        'price' => (float)$pData['price'],
                        'stock_status' => $pData['stock_status'],
                        'is_active' => (int)$pData['is_active'],
                        'is_featured' => (int)$pData['is_featured'],
                        'is_new' => (int)$pData['is_new'],
                        'is_deal' => (int)$pData['is_deal']
                    ]);
                    $total++;

                    // Arama kelimeleri tablosuna başlık kelimelerini ekle
                    $words = explode(' ', $this->normalizeText($pData['title']));
                    foreach ($words as $w) {
                        if (strlen($w) > 2) {
                            $db->execute(
                                "INSERT INTO search_keywords (keyword, frequency, created_at, updated_at)
                                 VALUES (:kw, 1, NOW(), NOW())
                                 ON DUPLICATE KEY UPDATE frequency = frequency + 1, updated_at = NOW()",
                                [':kw' => $w]
                            );
                        }
                    }
                }
            }

            // 2. Kategorileri İndeksle
            $categories = $db->query(
                "SELECT c.id, ct.name, ct.description 
                 FROM categories c 
                 JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
                 WHERE c.deleted_at IS NULL"
            );
            foreach ($categories as $cat) {
                $this->repository->upsertIndex([
                    'item_type' => 'category',
                    'item_id' => $cat['id'],
                    'title' => $cat['name'],
                    'content' => $cat['description'],
                    'price' => 0.0,
                    'stock_status' => 'in_stock',
                    'is_active' => 1
                ]);
                $total++;
            }

            $this->repository->finishRebuildLog($logId, $total, 'success');
            $this->repository->clearCache();

            return $total;

        } catch (\Throwable $e) {
            $this->repository->finishRebuildLog($logId, 0, 'failed', $e->getMessage());
            throw $e;
        }
    }
}
