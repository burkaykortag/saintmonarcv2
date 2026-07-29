<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Services\AuditLogger;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;
use Core\Validation\Validator;
use Exception;

class ProductService {
    private ProductRepository $repository;
    private DatabaseInterface $db;
    private CacheInterface $cache;
    private AuditLogger $auditLogger;
    private const CACHE_KEY = 'active_products_list';

    public function __construct(
        ProductRepository $repository, 
        DatabaseInterface $db, 
        CacheInterface $cache,
        AuditLogger $auditLogger
    ) {
        $this->repository = $repository;
        $this->db = $db;
        $this->cache = $cache;
        $this->auditLogger = $auditLogger;
    }

    public function getTreeCached(array $filters = []): array {
        if (!empty($filters['q']) || isset($filters['category_id']) || isset($filters['brand_id'])) {
            return $this->repository->getAll($filters);
        }

        if ($this->cache->has(self::CACHE_KEY)) {
            return $this->cache->get(self::CACHE_KEY);
        }

        $products = $this->repository->getAll();
        $this->cache->set(self::CACHE_KEY, $products, 3600);
        return $products;
    }

    public function create(array $data): int {
        // Validation using our custom Validator
        $validator = Validator::make($data, [
            'name' => 'required',
            'sku' => 'required|unique:products,sku',
            'price' => 'required|numeric',
            'cost_price' => 'numeric',
            'status' => 'required|in:draft,published,passive,archived,coming_soon,out_of_stock'
        ]);

        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()));
            throw new Exception(implode(' ', $errors));
        }

        $name = trim($data['name']);
        $sku = trim($data['sku']);
        $barcode = trim($data['barcode'] ?? '');

        // Calculations
        $cost = (float)($data['cost_price'] ?? 0.0);
        $price = (float)($data['price'] ?? 0.0);
        $profit = $price - $cost;
        $profitMargin = $price > 0 ? ($profit / $price) * 100 : 0.0;
        $profitRate = $cost > 0 ? ($profit / $cost) * 100 : 0.0;

        $slug = !empty($data['slug']) ? $this->slugify($data['slug']) : $this->generateUniqueSlug($name);
        // Ensure manual slug is unique too
        $slug = $this->generateUniqueSlug($slug);

        $isTransactionOwner = !$this->db->inTransaction();
        if ($isTransactionOwner) {
            $this->db->beginTransaction();
        }
        try {
            $this->db->execute(
                "INSERT INTO products 
                (brand_id, cover_image_id, images_360, promo_video_id, youtube_url, vimeo_url, mp4_url, sku, barcode, gtin, ean, upc, mpn, model_no, product_type, status,
                 price, compare_at_price, cost_price, profit, profit_margin, profit_rate, currency_code, special_price, special_price_start, special_price_end,
                 total_stock, critical_stock, min_order, max_order, track_stock, stock_status, unlimited_stock, min_stock, max_stock, allow_backorder, is_preorder,
                 weight, desi, width, height, length, delivery_time, preparation_time,
                 is_active, is_new, is_bestseller, is_featured, is_deal, available_from, available_to, show_in_home, show_in_slider, show_in_banner, free_shipping, tax_included, is_taxable,
                 is_discount, is_editors_choice, is_campaign, is_new_arrival, is_premium, slug)
                VALUES 
                (:brand_id, :cover_image_id, :images_360, :promo_video_id, :youtube_url, :vimeo_url, :mp4_url, :sku, :barcode, :gtin, :ean, :upc, :mpn, :model_no, :product_type, :status,
                 :price, :compare_at_price, :cost_price, :profit, :profit_margin, :profit_rate, :currency_code, :special_price, :special_price_start, :special_price_end,
                 :total_stock, :critical_stock, :min_order, :max_order, :track_stock, :stock_status, :unlimited_stock, :min_stock, :max_stock, :allow_backorder, :is_preorder,
                 :weight, :desi, :width, :height, :length, :delivery_time, :preparation_time,
                 :is_active, :is_new, :is_bestseller, :is_featured, :is_deal, :available_from, :available_to, :show_in_home, :show_in_slider, :show_in_banner, :free_shipping, :tax_included, :is_taxable,
                 :is_discount, :is_editors_choice, :is_campaign, :is_new_arrival, :is_premium, :slug)",
                [
                    ':brand_id' => !empty($data['brand_id']) ? (int)$data['brand_id'] : null,
                    ':cover_image_id' => !empty($data['cover_image_id']) ? (int)$data['cover_image_id'] : null,
                    ':images_360' => !empty($data['images_360']) ? (is_array($data['images_360']) ? json_encode($data['images_360']) : $data['images_360']) : null,
                    ':promo_video_id' => !empty($data['promo_video_id']) ? (int)$data['promo_video_id'] : null,
                    ':youtube_url' => trim($data['youtube_url'] ?? '') !== '' ? trim($data['youtube_url']) : null,
                    ':vimeo_url' => trim($data['vimeo_url'] ?? '') !== '' ? trim($data['vimeo_url']) : null,
                    ':mp4_url' => trim($data['mp4_url'] ?? '') !== '' ? trim($data['mp4_url']) : null,
                    ':sku' => $sku,
                    ':barcode' => $barcode !== '' ? $barcode : null,
                    ':gtin' => trim($data['gtin'] ?? '') !== '' ? trim($data['gtin']) : null,
                    ':ean' => trim($data['ean'] ?? '') !== '' ? trim($data['ean']) : null,
                    ':upc' => trim($data['upc'] ?? '') !== '' ? trim($data['upc']) : null,
                    ':mpn' => trim($data['mpn'] ?? '') !== '' ? trim($data['mpn']) : null,
                    ':model_no' => trim($data['model_no'] ?? '') !== '' ? trim($data['model_no']) : null,
                    ':product_type' => $data['product_type'] ?? 'physical',
                    ':status' => $data['status'] ?? 'draft',
                    ':price' => $price,
                    ':compare_at_price' => !empty($data['compare_at_price']) ? (float)$data['compare_at_price'] : null,
                    ':cost_price' => $cost,
                    ':profit' => $profit,
                    ':profit_margin' => $profitMargin,
                    ':profit_rate' => $profitRate,
                    ':currency_code' => $data['currency_code'] ?? 'TRY',
                    ':special_price' => !empty($data['special_price']) ? (float)$data['special_price'] : null,
                    ':special_price_start' => !empty($data['special_price_start']) ? $data['special_price_start'] : null,
                    ':special_price_end' => !empty($data['special_price_end']) ? $data['special_price_end'] : null,
                    ':total_stock' => (int)($data['total_stock'] ?? 0),
                    ':critical_stock' => (int)($data['critical_stock'] ?? 5),
                    ':min_order' => (int)($data['min_order'] ?? 1),
                    ':max_order' => !empty($data['max_order']) ? (int)$data['max_order'] : null,
                    ':track_stock' => (int)($data['track_stock'] ?? 1),
                    ':stock_status' => $data['stock_status'] ?? 'in_stock',
                    ':unlimited_stock' => (int)($data['unlimited_stock'] ?? 0),
                    ':min_stock' => (int)($data['min_stock'] ?? 0),
                    ':max_stock' => !empty($data['max_stock']) ? (int)$data['max_stock'] : null,
                    ':allow_backorder' => (int)($data['allow_backorder'] ?? 0),
                    ':is_preorder' => (int)($data['is_preorder'] ?? 0),
                    ':weight' => !empty($data['weight']) ? (float)$data['weight'] : null,
                    ':desi' => !empty($data['desi']) ? (float)$data['desi'] : null,
                    ':width' => !empty($data['width']) ? (float)$data['width'] : null,
                    ':height' => !empty($data['height']) ? (float)$data['height'] : null,
                    ':length' => !empty($data['length']) ? (float)$data['length'] : null,
                    ':delivery_time' => trim($data['delivery_time'] ?? '') !== '' ? trim($data['delivery_time']) : null,
                    ':preparation_time' => !empty($data['preparation_time']) ? (int)$data['preparation_time'] : null,
                    ':is_active' => (int)($data['is_active'] ?? 1),
                    ':is_new' => (int)($data['is_new'] ?? 0),
                    ':is_bestseller' => (int)($data['is_bestseller'] ?? 0),
                    ':is_featured' => (int)($data['is_featured'] ?? 0),
                    ':is_deal' => (int)($data['is_deal'] ?? 0),
                    ':available_from' => !empty($data['available_from']) ? $data['available_from'] : null,
                    ':available_to' => !empty($data['available_to']) ? $data['available_to'] : null,
                    ':show_in_home' => (int)($data['show_in_home'] ?? 0),
                    ':show_in_slider' => (int)($data['show_in_slider'] ?? 0),
                    ':show_in_banner' => (int)($data['show_in_banner'] ?? 0),
                    ':free_shipping' => (int)($data['free_shipping'] ?? 0),
                    ':tax_included' => (int)($data['tax_included'] ?? 0),
                    ':is_taxable' => (int)($data['is_taxable'] ?? 1),
                    ':is_discount' => (int)($data['is_discount'] ?? 0),
                    ':is_editors_choice' => (int)($data['is_editors_choice'] ?? 0),
                    ':is_campaign' => (int)($data['is_campaign'] ?? 0),
                    ':is_new_arrival' => (int)($data['is_new_arrival'] ?? 0),
                    ':is_premium' => (int)($data['is_premium'] ?? 0),
                    ':slug' => $slug
                ]
            );
            $productId = (int)$this->db->lastInsertId();

            // Insert translation
            $this->db->execute(
                "INSERT INTO product_translations (product_id, language_id, name, subtitle, summary, technical_specs, instructions, warranty, delivery_info, box_content, return_policy, short_description, description) 
                 VALUES (:product_id, 1, :name, :subtitle, :summary, :tech_specs, :instructions, :warranty, :delivery_info, :box_content, :return_policy, :short_desc, :desc)",
                [
                    ':product_id' => $productId,
                    ':name' => $name,
                    ':subtitle' => trim($data['subtitle'] ?? '') !== '' ? trim($data['subtitle']) : null,
                    ':summary' => trim($data['summary'] ?? '') !== '' ? trim($data['summary']) : null,
                    ':tech_specs' => trim($data['technical_specs'] ?? '') !== '' ? trim($data['technical_specs']) : null,
                    ':instructions' => trim($data['instructions'] ?? '') !== '' ? trim($data['instructions']) : null,
                    ':warranty' => trim($data['warranty'] ?? '') !== '' ? trim($data['warranty']) : null,
                    ':delivery_info' => trim($data['delivery_info'] ?? '') !== '' ? trim($data['delivery_info']) : null,
                    ':box_content' => trim($data['box_content'] ?? '') !== '' ? trim($data['box_content']) : null,
                    ':return_policy' => trim($data['return_policy'] ?? '') !== '' ? trim($data['return_policy']) : null,
                    ':short_desc' => trim($data['short_description'] ?? '') !== '' ? trim($data['short_description']) : null,
                    ':desc' => trim($data['description'] ?? '') !== '' ? trim($data['description']) : null
                ]
            );

            // Category Relation
            if (!empty($data['category_id'])) {
                $this->db->execute(
                    "INSERT INTO product_category_relations (product_id, category_id) VALUES (:pid, :cid)",
                    [':pid' => $productId, ':cid' => (int)$data['category_id']]
                );
            }

            // Save SEO Meta
            if (!empty($data['seo'])) {
                $this->updateSeoMeta($productId, $data['seo']);
            }

            // Save Inventory Stock
            $totalStock = (int)($data['total_stock'] ?? 0);
            $this->db->execute(
                "INSERT INTO inventories (product_id, variant_id, stock, reserved_stock) 
                 VALUES (:pid, NULL, :stock, 0)",
                [':pid' => $productId, ':stock' => $totalStock]
            );

            // Log Inventory Movement
            $inventoryId = (int)$this->db->lastInsertId();
            $this->db->execute(
                "INSERT INTO inventory_movements (inventory_id, quantity, type, description) 
                 VALUES (:inv_id, :qty, 'initial_entry', 'İlk ürün girişi')",
                [':inv_id' => $inventoryId, ':qty' => $totalStock]
            );

            // Save Gallery Images
            if (!empty($data['gallery_image_ids'])) {
                foreach ($data['gallery_image_ids'] as $idx => $imgId) {
                    $media = $this->db->query("SELECT filepath FROM media_library WHERE id = :id LIMIT 1", [':id' => (int)$imgId]);
                    if (!empty($media)) {
                        $this->db->execute(
                            "INSERT INTO product_images (product_id, image_id, path, is_main, sort_order) 
                             VALUES (:pid, :img_id, :path, 0, :sort)",
                            [
                                ':pid' => $productId,
                                ':img_id' => (int)$imgId,
                                ':path' => $media[0]['filepath'],
                                ':sort' => $idx
                            ]
                        );
                    }
                }
            }

            // Save Product Tags
            if (!empty($data['tags'])) {
                $tags = is_string($data['tags']) ? explode(',', $data['tags']) : $data['tags'];
                foreach ($tags as $tagStr) {
                    $tagStr = trim($tagStr);
                    if ($tagStr === '') continue;
                    
                    $tagSlug = $this->slugify($tagStr);
                    // Find or create tag
                    $existingTag = $this->db->query("SELECT id FROM product_tags WHERE slug = :slug LIMIT 1", [':slug' => $tagSlug]);
                    if (!empty($existingTag)) {
                        $tagId = (int)$existingTag[0]['id'];
                    } else {
                        $this->db->execute("INSERT INTO product_tags (name, slug) VALUES (:name, :slug)", [':name' => $tagStr, ':slug' => $tagSlug]);
                        $tagId = (int)$this->db->lastInsertId();
                    }

                    $this->db->execute("INSERT IGNORE INTO product_tag_relations (product_id, tag_id) VALUES (:pid, :tid)", [
                        ':pid' => $productId,
                        ':tid' => $tagId
                    ]);
                }
            }

            // Save Additional Files (PDFs, etc.)
            if (!empty($data['product_files'])) {
                foreach ($data['product_files'] as $file) {
                    if (empty($file['path'])) continue;
                    $this->db->execute(
                        "INSERT INTO product_files (product_id, name, path, file_type) VALUES (:pid, :name, :path, :type)",
                        [
                            ':pid' => $productId,
                            ':name' => $file['name'] ?? basename($file['path']),
                            ':path' => $file['path'],
                            ':type' => $file['file_type'] ?? 'pdf'
                        ]
                    );
                }
            }

            // Save Product Relations
            if (!empty($data['relations'])) {
                foreach ($data['relations'] as $relType => $relIds) {
                    if (!is_array($relIds)) continue;
                    foreach ($relIds as $relId) {
                        $this->db->execute(
                            "INSERT IGNORE INTO product_relations (product_id, related_product_id, relation_type) 
                             VALUES (:pid, :rpid, :type)",
                            [
                                ':pid' => $productId,
                                ':rpid' => (int)$relId,
                                ':type' => $relType
                            ]
                        );
                    }
                }
            }

            // Save Product Variants
            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $varData) {
                    if (empty($varData['sku'])) continue;
                    
                    $this->db->execute(
                        "INSERT INTO product_variants (product_id, sku, barcode, price, compare_at_price, cost_price, weight, image_id) 
                         VALUES (:pid, :sku, :barcode, :price, :compare, :cost, :weight, :img_id)",
                        [
                            ':pid' => $productId,
                            ':sku' => $varData['sku'],
                            ':barcode' => $varData['barcode'] ?? null,
                            ':price' => !empty($varData['price']) ? (float)$varData['price'] : null,
                            ':compare' => !empty($varData['compare_at_price']) ? (float)$varData['compare_at_price'] : null,
                            ':cost' => !empty($varData['cost_price']) ? (float)$varData['cost_price'] : null,
                            ':weight' => !empty($varData['weight']) ? (float)$varData['weight'] : null,
                            ':img_id' => !empty($varData['image_id']) ? (int)$varData['image_id'] : null
                        ]
                    );
                    $variantId = (int)$this->db->lastInsertId();

                    // Save stock for variant in inventories
                    $varStock = (int)($varData['stock'] ?? 0);
                    $this->db->execute(
                        "INSERT INTO inventories (product_id, variant_id, stock, reserved_stock) 
                         VALUES (:pid, :vid, :stock, 0)",
                        [':pid' => $productId, ':vid' => $variantId, ':stock' => $varStock]
                    );
                    $varInvId = (int)$this->db->lastInsertId();

                    $this->db->execute(
                        "INSERT INTO inventory_movements (inventory_id, quantity, type, description) 
                         VALUES (:inv_id, :qty, 'initial_entry', 'Varyant ilk stok girişi')",
                        [':inv_id' => $varInvId, ':qty' => $varStock]
                    );

                    // Option values mapping (Color, Size etc.)
                    if (!empty($varData['attributes'])) {
                        foreach ($varData['attributes'] as $attrId => $valId) {
                            $this->db->execute(
                                "INSERT INTO product_variant_option_values (variant_id, attribute_value_id) 
                                 VALUES (:vid, :valid)",
                                [
                                    ':vid' => $variantId,
                                    ':valid' => (int)$valId
                                ]
                            );
                        }
                    }
                }
            }

            // Write Activity & Audit Log
            $this->auditLogger->logActivity('product_create', "Yeni ürün oluşturuldu: {$name} (SKU: {$sku})");
            $this->auditLogger->logAudit('create', 'Product', $productId, null, $data);

            if ($isTransactionOwner) {
                $this->db->commit();
            }
            $this->clearCache();
            return $productId;
        } catch (Exception $e) {
            if ($isTransactionOwner && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function update(int $id, array $data): void {
        $validator = Validator::make($data, [
            'name' => 'required',
            'sku' => "required|unique:products,sku,{$id},id",
            'price' => 'required|numeric',
            'cost_price' => 'numeric',
            'status' => 'required|in:draft,published,passive,archived,coming_soon,out_of_stock'
        ]);

        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()));
            throw new Exception(implode(' ', $errors));
        }

        $name = trim($data['name']);
        $sku = trim($data['sku']);
        $barcode = trim($data['barcode'] ?? '');

        // Calculations
        $cost = (float)($data['cost_price'] ?? 0.0);
        $price = (float)($data['price'] ?? 0.0);
        $profit = $price - $cost;
        $profitMargin = $price > 0 ? ($profit / $price) * 100 : 0.0;
        $profitRate = $cost > 0 ? ($profit / $cost) * 100 : 0.0;

        $current = $this->repository->getById($id, true);
        if (!$current) {
            throw new Exception("Ürün bulunamadı.");
        }

        $slug = $current['slug'];
        if (!empty($data['slug']) && $data['slug'] !== $current['slug']) {
            $slug = $this->slugify($data['slug']);
            $slug = $this->generateUniqueSlug($slug, $id);
        } elseif ($name !== $current['name']) {
            $slug = $this->generateUniqueSlug($name, $id);
        }

        $isTransactionOwner = !$this->db->inTransaction();
        if ($isTransactionOwner) {
            $this->db->beginTransaction();
        }
        try {
            // Write Redirect 301 if slug changes
            if ($slug !== $current['slug']) {
                $oldUrl = '/products/' . $current['slug'];
                $newUrl = '/products/' . $slug;
                
                $this->db->execute("DELETE FROM redirects WHERE source_url = :url1 OR target_url = :url2", [':url1' => $newUrl, ':url2' => $newUrl]);
                
                $this->db->execute(
                    "INSERT INTO redirects (source_url, target_url, status_code) VALUES (:src, :tgt, 301)
                     ON DUPLICATE KEY UPDATE target_url = :tgt_update",
                    [':src' => $oldUrl, ':tgt' => $newUrl, ':tgt_update' => $newUrl]
                );
            }

            // Update main table
            $this->db->execute(
                "UPDATE products 
                 SET brand_id = :brand_id, cover_image_id = :cover_image_id, images_360 = :images_360, promo_video_id = :promo_video_id, 
                     youtube_url = :youtube_url, vimeo_url = :vimeo_url, mp4_url = :mp4_url, sku = :sku, barcode = :barcode, 
                     gtin = :gtin, ean = :ean, upc = :upc, mpn = :mpn, model_no = :model_no, product_type = :product_type, status = :status,
                     price = :price, compare_at_price = :compare_at_price, cost_price = :cost_price, profit = :profit, 
                     profit_margin = :profit_margin, profit_rate = :profit_rate, currency_code = :currency_code, special_price = :special_price, 
                     special_price_start = :special_price_start, special_price_end = :special_price_end,
                     total_stock = :total_stock, critical_stock = :critical_stock, min_order = :min_order, max_order = :max_order, 
                     track_stock = :track_stock, stock_status = :stock_status, unlimited_stock = :unlimited_stock, min_stock = :min_stock, max_stock = :max_stock, 
                     allow_backorder = :allow_backorder, is_preorder = :is_preorder,
                     weight = :weight, desi = :desi, width = :width, height = :height, length = :length, 
                     delivery_time = :delivery_time, preparation_time = :preparation_time,
                     is_active = :is_active, is_new = :is_new, is_bestseller = :is_bestseller, is_featured = :is_featured, 
                     is_deal = :is_deal, available_from = :available_from, available_to = :available_to,
                     show_in_home = :show_in_home, show_in_slider = :show_in_slider, show_in_banner = :show_in_banner, 
                     free_shipping = :free_shipping, tax_included = :tax_included, is_taxable = :is_taxable, 
                     is_discount = :is_discount, is_editors_choice = :is_editors_choice, is_campaign = :is_campaign, is_new_arrival = :is_new_arrival, is_premium = :is_premium,
                     slug = :slug
                 WHERE id = :id",
                [
                    ':brand_id' => !empty($data['brand_id']) ? (int)$data['brand_id'] : null,
                    ':cover_image_id' => !empty($data['cover_image_id']) ? (int)$data['cover_image_id'] : null,
                    ':images_360' => !empty($data['images_360']) ? (is_array($data['images_360']) ? json_encode($data['images_360']) : $data['images_360']) : null,
                    ':promo_video_id' => !empty($data['promo_video_id']) ? (int)$data['promo_video_id'] : null,
                    ':youtube_url' => trim($data['youtube_url'] ?? '') !== '' ? trim($data['youtube_url']) : null,
                    ':vimeo_url' => trim($data['vimeo_url'] ?? '') !== '' ? trim($data['vimeo_url']) : null,
                    ':mp4_url' => trim($data['mp4_url'] ?? '') !== '' ? trim($data['mp4_url']) : null,
                    ':sku' => $sku,
                    ':barcode' => $barcode !== '' ? $barcode : null,
                    ':gtin' => trim($data['gtin'] ?? '') !== '' ? trim($data['gtin']) : null,
                    ':ean' => trim($data['ean'] ?? '') !== '' ? trim($data['ean']) : null,
                    ':upc' => trim($data['upc'] ?? '') !== '' ? trim($data['upc']) : null,
                    ':mpn' => trim($data['mpn'] ?? '') !== '' ? trim($data['mpn']) : null,
                    ':model_no' => trim($data['model_no'] ?? '') !== '' ? trim($data['model_no']) : null,
                    ':product_type' => $data['product_type'] ?? 'physical',
                    ':status' => $data['status'] ?? 'draft',
                    ':price' => $price,
                    ':compare_at_price' => !empty($data['compare_at_price']) ? (float)$data['compare_at_price'] : null,
                    ':cost_price' => $cost,
                    ':profit' => $profit,
                    ':profit_margin' => $profitMargin,
                    ':profit_rate' => $profitRate,
                    ':currency_code' => $data['currency_code'] ?? 'TRY',
                    ':special_price' => !empty($data['special_price']) ? (float)$data['special_price'] : null,
                    ':special_price_start' => !empty($data['special_price_start']) ? $data['special_price_start'] : null,
                    ':special_price_end' => !empty($data['special_price_end']) ? $data['special_price_end'] : null,
                    ':total_stock' => (int)($data['total_stock'] ?? 0),
                    ':critical_stock' => (int)($data['critical_stock'] ?? 5),
                    ':min_order' => (int)($data['min_order'] ?? 1),
                    ':max_order' => !empty($data['max_order']) ? (int)$data['max_order'] : null,
                    ':track_stock' => (int)($data['track_stock'] ?? 1),
                    ':stock_status' => $data['stock_status'] ?? 'in_stock',
                    ':unlimited_stock' => (int)($data['unlimited_stock'] ?? 0),
                    ':min_stock' => (int)($data['min_stock'] ?? 0),
                    ':max_stock' => !empty($data['max_stock']) ? (int)$data['max_stock'] : null,
                    ':allow_backorder' => (int)($data['allow_backorder'] ?? 0),
                    ':is_preorder' => (int)($data['is_preorder'] ?? 0),
                    ':weight' => !empty($data['weight']) ? (float)$data['weight'] : null,
                    ':desi' => !empty($data['desi']) ? (float)$data['desi'] : null,
                    ':width' => !empty($data['width']) ? (float)$data['width'] : null,
                    ':height' => !empty($data['height']) ? (float)$data['height'] : null,
                    ':length' => !empty($data['length']) ? (float)$data['length'] : null,
                    ':delivery_time' => trim($data['delivery_time'] ?? '') !== '' ? trim($data['delivery_time']) : null,
                    ':preparation_time' => !empty($data['preparation_time']) ? (int)$data['preparation_time'] : null,
                    ':is_active' => (int)($data['is_active'] ?? 1),
                    ':is_new' => (int)($data['is_new'] ?? 0),
                    ':is_bestseller' => (int)($data['is_bestseller'] ?? 0),
                    ':is_featured' => (int)($data['is_featured'] ?? 0),
                    ':is_deal' => (int)($data['is_deal'] ?? 0),
                    ':available_from' => !empty($data['available_from']) ? $data['available_from'] : null,
                    ':available_to' => !empty($data['available_to']) ? $data['available_to'] : null,
                    ':show_in_home' => (int)($data['show_in_home'] ?? 0),
                    ':show_in_slider' => (int)($data['show_in_slider'] ?? 0),
                    ':show_in_banner' => (int)($data['show_in_banner'] ?? 0),
                    ':free_shipping' => (int)($data['free_shipping'] ?? 0),
                    ':tax_included' => (int)($data['tax_included'] ?? 0),
                    ':is_taxable' => (int)($data['is_taxable'] ?? 1),
                    ':is_discount' => (int)($data['is_discount'] ?? 0),
                    ':is_editors_choice' => (int)($data['is_editors_choice'] ?? 0),
                    ':is_campaign' => (int)($data['is_campaign'] ?? 0),
                    ':is_new_arrival' => (int)($data['is_new_arrival'] ?? 0),
                    ':is_premium' => (int)($data['is_premium'] ?? 0),
                    ':slug' => $slug,
                    ':id' => $id
                ]
            );

            // Update translations
            $this->db->execute(
                "UPDATE product_translations 
                 SET name = :name, subtitle = :subtitle, summary = :summary, technical_specs = :tech_specs, 
                     instructions = :instructions, warranty = :warranty, delivery_info = :delivery_info, 
                     box_content = :box_content, return_policy = :return_policy,
                     short_description = :short_desc, description = :desc 
                 WHERE product_id = :product_id AND language_id = 1",
                [
                    ':name' => $name,
                    ':subtitle' => trim($data['subtitle'] ?? '') !== '' ? trim($data['subtitle']) : null,
                    ':summary' => trim($data['summary'] ?? '') !== '' ? trim($data['summary']) : null,
                    ':tech_specs' => trim($data['technical_specs'] ?? '') !== '' ? trim($data['technical_specs']) : null,
                    ':instructions' => trim($data['instructions'] ?? '') !== '' ? trim($data['instructions']) : null,
                    ':warranty' => trim($data['warranty'] ?? '') !== '' ? trim($data['warranty']) : null,
                    ':delivery_info' => trim($data['delivery_info'] ?? '') !== '' ? trim($data['delivery_info']) : null,
                    ':box_content' => trim($data['box_content'] ?? '') !== '' ? trim($data['box_content']) : null,
                    ':return_policy' => trim($data['return_policy'] ?? '') !== '' ? trim($data['return_policy']) : null,
                    ':short_desc' => trim($data['short_description'] ?? '') !== '' ? trim($data['short_description']) : null,
                    ':desc' => trim($data['description'] ?? '') !== '' ? trim($data['description']) : null,
                    ':product_id' => $id
                ]
            );

            // Update category relation
            $this->db->execute("DELETE FROM product_category_relations WHERE product_id = :id", [':id' => $id]);
            if (!empty($data['category_id'])) {
                $this->db->execute(
                    "INSERT INTO product_category_relations (product_id, category_id) VALUES (:pid, :cid)",
                    [':pid' => $id, ':cid' => (int)$data['category_id']]
                );
            }

            // Save SEO
            if (!empty($data['seo'])) {
                $this->updateSeoMeta($id, $data['seo']);
            }

            // Update stock
            $totalStock = (int)($data['total_stock'] ?? 0);
            
            // Get current stock to log movement if changed
            $currentStockRow = $this->db->query("SELECT id, stock FROM inventories WHERE product_id = :pid AND variant_id IS NULL LIMIT 1", [':pid' => $id]);
            if (!empty($currentStockRow)) {
                $invId = (int)$currentStockRow[0]['id'];
                $oldStock = (int)$currentStockRow[0]['stock'];
                if ($oldStock !== $totalStock) {
                    $diff = $totalStock - $oldStock;
                    $this->db->execute("UPDATE inventories SET stock = :stock WHERE id = :id", [':stock' => $totalStock, ':id' => $invId]);
                    $this->db->execute(
                        "INSERT INTO inventory_movements (inventory_id, quantity, type, description) 
                         VALUES (:inv_id, :qty, 'manual_adjustment', 'Kullanıcı stok güncellemesi')",
                        [':inv_id' => $invId, ':qty' => $diff]
                    );
                }
            } else {
                $this->db->execute(
                    "INSERT INTO inventories (product_id, variant_id, stock, reserved_stock) 
                     VALUES (:pid, NULL, :stock, 0)",
                    [':pid' => $id, ':stock' => $totalStock]
                );
                $invId = (int)$this->db->lastInsertId();
                $this->db->execute(
                    "INSERT INTO inventory_movements (inventory_id, quantity, type, description) 
                     VALUES (:inv_id, :qty, 'initial_entry', 'İlk ürün girişi')",
                    [':inv_id' => $invId, ':qty' => $totalStock]
                );
            }

            // Sync Gallery Images
            $this->db->execute("DELETE FROM product_images WHERE product_id = :pid", [':pid' => $id]);
            if (!empty($data['gallery_image_ids'])) {
                foreach ($data['gallery_image_ids'] as $idx => $imgId) {
                    $media = $this->db->query("SELECT filepath FROM media_library WHERE id = :id LIMIT 1", [':id' => (int)$imgId]);
                    if (!empty($media)) {
                        $this->db->execute(
                            "INSERT INTO product_images (product_id, image_id, path, is_main, sort_order) 
                             VALUES (:pid, :img_id, :path, 0, :sort)",
                            [
                                ':pid' => $id,
                                ':img_id' => (int)$imgId,
                                ':path' => $media[0]['filepath'],
                                ':sort' => $idx
                            ]
                        );
                    }
                }
            }

            // Sync Product Tags
            $this->db->execute("DELETE FROM product_tag_relations WHERE product_id = :pid", [':pid' => $id]);
            if (!empty($data['tags'])) {
                $tags = is_string($data['tags']) ? explode(',', $data['tags']) : $data['tags'];
                foreach ($tags as $tagStr) {
                    $tagStr = trim($tagStr);
                    if ($tagStr === '') continue;
                    
                    $tagSlug = $this->slugify($tagStr);
                    $existingTag = $this->db->query("SELECT id FROM product_tags WHERE slug = :slug LIMIT 1", [':slug' => $tagSlug]);
                    if (!empty($existingTag)) {
                        $tagId = (int)$existingTag[0]['id'];
                    } else {
                        $this->db->execute("INSERT INTO product_tags (name, slug) VALUES (:name, :slug)", [':name' => $tagStr, ':slug' => $tagSlug]);
                        $tagId = (int)$this->db->lastInsertId();
                    }

                    $this->db->execute("INSERT IGNORE INTO product_tag_relations (product_id, tag_id) VALUES (:pid, :tid)", [
                        ':pid' => $id,
                        ':tid' => $tagId
                    ]);
                }
            }

            // Sync Product Additional Files
            $this->db->execute("DELETE FROM product_files WHERE product_id = :pid", [':pid' => $id]);
            if (!empty($data['product_files'])) {
                foreach ($data['product_files'] as $file) {
                    if (empty($file['path'])) continue;
                    $this->db->execute(
                        "INSERT INTO product_files (product_id, name, path, file_type) VALUES (:pid, :name, :path, :type)",
                        [
                            ':pid' => $id,
                            ':name' => $file['name'] ?? basename($file['path']),
                            ':path' => $file['path'],
                            ':type' => $file['file_type'] ?? 'pdf'
                        ]
                    );
                }
            }

            // Sync Product Relations
            $this->db->execute("DELETE FROM product_relations WHERE product_id = :pid", [':pid' => $id]);
            if (!empty($data['relations'])) {
                foreach ($data['relations'] as $relType => $relIds) {
                    if (!is_array($relIds)) continue;
                    foreach ($relIds as $relId) {
                        $this->db->execute(
                            "INSERT IGNORE INTO product_relations (product_id, related_product_id, relation_type) 
                             VALUES (:pid, :rpid, :type)",
                            [
                                ':pid' => $id,
                                ':rpid' => (int)$relId,
                                ':type' => $relType
                            ]
                        );
                    }
                }
            }

            // Sync Product Variants
            // Soft delete previous variants
            $this->db->execute("UPDATE product_variants SET deleted_at = NOW() WHERE product_id = :pid", [':pid' => $id]);
            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $varData) {
                    if (empty($varData['sku'])) continue;

                    // Check if variant SKU was previously deleted and restore, or create new
                    $existingVar = $this->db->query("SELECT id FROM product_variants WHERE sku = :sku AND product_id = :pid LIMIT 1", [':sku' => $varData['sku'], ':pid' => $id]);
                    if (!empty($existingVar)) {
                        $variantId = (int)$existingVar[0]['id'];
                        $this->db->execute(
                            "UPDATE product_variants 
                             SET deleted_at = NULL, barcode = :barcode, price = :price, compare_at_price = :compare, cost_price = :cost, weight = :weight, image_id = :img_id 
                             WHERE id = :vid",
                            [
                                ':barcode' => $varData['barcode'] ?? null,
                                ':price' => !empty($varData['price']) ? (float)$varData['price'] : null,
                                ':compare' => !empty($varData['compare_at_price']) ? (float)$varData['compare_at_price'] : null,
                                ':cost' => !empty($varData['cost_price']) ? (float)$varData['cost_price'] : null,
                                ':weight' => !empty($varData['weight']) ? (float)$varData['weight'] : null,
                                ':img_id' => !empty($varData['image_id']) ? (int)$varData['image_id'] : null,
                                ':vid' => $variantId
                            ]
                        );
                    } else {
                        $this->db->execute(
                            "INSERT INTO product_variants (product_id, sku, barcode, price, compare_at_price, cost_price, weight, image_id) 
                             VALUES (:pid, :sku, :barcode, :price, :compare, :cost, :weight, :img_id)",
                            [
                                ':pid' => $id,
                                ':sku' => $varData['sku'],
                                ':barcode' => $varData['barcode'] ?? null,
                                ':price' => !empty($varData['price']) ? (float)$varData['price'] : null,
                                ':compare' => !empty($varData['compare_at_price']) ? (float)$varData['compare_at_price'] : null,
                                ':cost' => !empty($varData['cost_price']) ? (float)$varData['cost_price'] : null,
                                ':weight' => !empty($varData['weight']) ? (float)$varData['weight'] : null,
                                ':img_id' => !empty($varData['image_id']) ? (int)$varData['image_id'] : null
                            ]
                        );
                        $variantId = (int)$this->db->lastInsertId();
                    }

                    // Sync variant stock in inventories
                    $varStock = (int)($varData['stock'] ?? 0);
                    $varInvRow = $this->db->query("SELECT id, stock FROM inventories WHERE variant_id = :vid LIMIT 1", [':vid' => $variantId]);
                    if (!empty($varInvRow)) {
                        $varInvId = (int)$varInvRow[0]['id'];
                        $oldVarStock = (int)$varInvRow[0]['stock'];
                        if ($oldVarStock !== $varStock) {
                            $diff = $varStock - $oldVarStock;
                            $this->db->execute("UPDATE inventories SET stock = :stock WHERE id = :id", [':stock' => $varStock, ':id' => $varInvId]);
                            $this->db->execute(
                                "INSERT INTO inventory_movements (inventory_id, quantity, type, description) 
                                 VALUES (:inv_id, :qty, 'manual_adjustment', 'Varyant stok güncellemesi')",
                                [':inv_id' => $varInvId, ':qty' => $diff]
                            );
                        }
                    } else {
                        $this->db->execute(
                            "INSERT INTO inventories (product_id, variant_id, stock, reserved_stock) 
                             VALUES (:pid, :vid, :stock, 0)",
                            [':pid' => $id, ':vid' => $variantId, ':stock' => $varStock]
                        );
                        $varInvId = (int)$this->db->lastInsertId();
                        $this->db->execute(
                            "INSERT INTO inventory_movements (inventory_id, quantity, type, description) 
                             VALUES (:inv_id, :qty, 'initial_entry', 'Varyant ilk stok girişi')",
                            [':inv_id' => $varInvId, ':qty' => $varStock]
                        );
                    }

                    // Sync Option value mapping
                    $this->db->execute("DELETE FROM product_variant_option_values WHERE variant_id = :vid", [':vid' => $variantId]);
                    if (!empty($varData['attributes'])) {
                        foreach ($varData['attributes'] as $attrId => $valId) {
                            $this->db->execute(
                                "INSERT INTO product_variant_option_values (variant_id, attribute_value_id) 
                                 VALUES (:vid, :valid)",
                                [
                                    ':vid' => $variantId,
                                    ':valid' => (int)$valId
                                ]
                            );
                        }
                    }
                }
            }

            // Write Activity & Audit Log
            $this->auditLogger->logActivity('product_update', "Ürün güncellendi: {$name} (ID: {$id})");
            $this->auditLogger->logAudit('update', 'Product', $id, $current, $data);

            if ($isTransactionOwner) {
                $this->db->commit();
            }
            $this->clearCache();
        } catch (Exception $e) {
            if ($isTransactionOwner && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function softDelete(int $id): void {
        $product = $this->repository->getById($id, true);
        $this->db->execute("UPDATE products SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);
        $this->auditLogger->logActivity('product_delete', "Ürün çöpe taşındı: " . ($product['name'] ?? '') . " (ID: {$id})");
        $this->clearCache();
    }

    public function restore(int $id): void {
        $product = $this->repository->getById($id, true);
        $this->db->execute("UPDATE products SET deleted_at = NULL WHERE id = :id", [':id' => $id]);
        $this->auditLogger->logActivity('product_restore', "Ürün çöpten geri yüklendi: " . ($product['name'] ?? '') . " (ID: {$id})");
        $this->clearCache();
    }

    public function forceDelete(int $id): void {
        $product = $this->repository->getById($id, true);
        $this->db->beginTransaction();
        try {
            // Delete all variant associations
            $variants = $this->db->query("SELECT id FROM product_variants WHERE product_id = :pid", [':pid' => $id]);
            foreach ($variants as $v) {
                $this->db->execute("DELETE FROM product_variant_option_values WHERE variant_id = :vid", [':vid' => $v['id']]);
                $this->db->execute("DELETE FROM inventories WHERE variant_id = :vid", [':vid' => $v['id']]);
                $this->db->execute("DELETE FROM product_variants WHERE id = :vid", [':vid' => $v['id']]);
            }

            $this->db->execute("DELETE FROM product_translations WHERE product_id = :id", [':id' => $id]);
            $this->db->execute("DELETE FROM product_category_relations WHERE product_id = :id", [':id' => $id]);
            $this->db->execute("DELETE FROM seo_meta WHERE model_type = 'Product' AND model_id = :id", [':id' => $id]);
            $this->db->execute("DELETE FROM product_tag_relations WHERE product_id = :id", [':id' => $id]);
            $this->db->execute("DELETE FROM product_files WHERE product_id = :id", [':id' => $id]);
            $this->db->execute("DELETE FROM product_relations WHERE product_id = :id", [':id' => $id]);
            
            // Delete main product inventories
            $mainInv = $this->db->query("SELECT id FROM inventories WHERE product_id = :pid AND variant_id IS NULL LIMIT 1", [':pid' => $id]);
            if (!empty($mainInv)) {
                $this->db->execute("DELETE FROM inventory_movements WHERE inventory_id = :id", [':id' => $mainInv[0]['id']]);
            }
            $this->db->execute("DELETE FROM inventories WHERE product_id = :id", [':id' => $id]);
            $this->db->execute("DELETE FROM product_images WHERE product_id = :id", [':id' => $id]);
            $this->db->execute("DELETE FROM products WHERE id = :id", [':id' => $id]);

            $this->auditLogger->logActivity('product_force_delete', "Ürün kalıcı olarak silindi: " . ($product['name'] ?? '') . " (ID: {$id})");

            $this->db->commit();
            $this->clearCache();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function duplicate(int $id): int {
        $product = $this->repository->getById($id, true);
        if (!$product) {
            throw new Exception("Kopyalanacak ürün bulunamadı.");
        }

        // Generate unique values
        $newSku = $product['sku'] . '-COPY-' . mt_rand(100, 999);
        $newBarcode = $product['barcode'] ? $product['barcode'] . '-COPY-' . mt_rand(100, 999) : null;
        $newName = $product['name'] . ' (Kopya)';

        $seo = $this->getSeoMeta($id);

        $tags = array_map(function($t) { return $t['name']; }, $this->repository->getTags($id));
        $files = array_map(function($f) { 
            return ['name' => $f['name'], 'path' => $f['path'], 'file_type' => $f['file_type']]; 
        }, $this->repository->getFiles($id));

        $relations = [];
        $rawRels = $this->repository->getRelations($id);
        foreach ($rawRels as $r) {
            $relations[$r['relation_type']][] = $r['related_product_id'];
        }

        $variants = [];
        $rawVars = $this->repository->getVariants($id);
        foreach ($rawVars as $rv) {
            $varAttrs = [];
            foreach ($rv['options'] as $opt) {
                $varAttrs[$opt['attribute_id']] = $opt['id'];
            }
            $variants[] = [
                'sku' => $rv['sku'] . '-COPY-' . mt_rand(100, 999),
                'barcode' => $rv['barcode'] ? $rv['barcode'] . '-COPY-' . mt_rand(100, 999) : null,
                'price' => $rv['price'],
                'compare_at_price' => $rv['compare_at_price'],
                'cost_price' => $rv['cost_price'],
                'weight' => $rv['weight'],
                'image_id' => $rv['image_id'],
                'stock' => $rv['stock'],
                'attributes' => $varAttrs
            ];
        }

        $data = array_merge($product, [
            'name' => $newName,
            'sku' => $newSku,
            'barcode' => $newBarcode,
            'category_id' => $product['category_id'],
            'seo' => $seo,
            'tags' => $tags,
            'product_files' => $files,
            'relations' => $relations,
            'variants' => $variants
        ]);

        return $this->create($data);
    }

    public function getSeoMeta(int $productId): array {
        $sql = "SELECT * FROM seo_meta WHERE model_type = 'Product' AND model_id = :id LIMIT 1";
        $rows = $this->db->query($sql, [':id' => $productId]);
        return $rows[0] ?? [
            'title' => '', 'description' => '', 'keywords' => '',
            'canonical_url' => '', 'og_title' => '', 'og_description' => '', 'og_image' => '', 'robots' => 'index, follow'
        ];
    }

    private function updateSeoMeta(int $productId, array $seo): void {
        $exists = $this->db->query("SELECT id FROM seo_meta WHERE model_type = 'Product' AND model_id = :id LIMIT 1", [':id' => $productId]);
        $params = [
            ':title' => trim($seo['title'] ?? ''),
            ':desc' => trim($seo['description'] ?? ''),
            ':keywords' => trim($seo['keywords'] ?? ''),
            ':canonical' => trim($seo['canonical_url'] ?? ''),
            ':og_title' => trim($seo['og_title'] ?? ''),
            ':og_desc' => trim($seo['og_description'] ?? ''),
            ':og_image' => trim($seo['og_image'] ?? ''),
            ':robots' => trim($seo['robots'] ?? 'index, follow'),
            ':id' => $productId
        ];

        if (!empty($exists)) {
            $this->db->execute(
                "UPDATE seo_meta 
                 SET title = :title, description = :desc, keywords = :keywords, canonical_url = :canonical, 
                     og_title = :og_title, og_description = :og_desc, og_image = :og_image, robots = :robots 
                 WHERE model_type = 'Product' AND model_id = :id",
                $params
            );
        } else {
            $this->db->execute(
                "INSERT INTO seo_meta (model_type, model_id, title, description, keywords, canonical_url, og_title, og_description, og_image, robots) 
                 VALUES ('Product', :id, :title, :desc, :keywords, :canonical, :og_title, :og_desc, :og_image, :robots)",
                $params
            );
        }
    }

    private function generateUniqueSlug(string $name, ?int $excludeId = null): string {
        $slug = $this->slugify($name);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $sql = "SELECT id FROM products WHERE slug = :slug AND deleted_at IS NULL";
            $params = [':slug' => $slug];
            if ($excludeId) {
                $sql .= " AND id != :id";
                $params[':id'] = $excludeId;
            }
            $exists = $this->db->query($sql, $params);
            if (empty($exists)) {
                break;
            }
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugify(string $text): string {
        $find = ['Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö', 'ç', 'ş', 'ğ', 'ü', 'ı', 'ö'];
        $replace = ['c', 's', 'g', 'u', 'i', 'o', 'c', 's', 'g', 'u', 'i', 'o'];
        $text = str_replace($find, $replace, $text);
        $text = preg_replace('/[^A-Za-z0-9-]+/', '-', $text);
        $text = trim($text, '-');
        return strtolower($text);
    }

    public function generateXmlSitemap(): string {
        $products = $this->repository->getAll(['status' => 'published']);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($products as $p) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>http://localhost/products/" . htmlspecialchars($p['slug']) . "</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d', strtotime($p['updated_at'] ?? $p['created_at'])) . "</lastmod>\n";
            $xml .= "    <changefreq>daily</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';
        return $xml;
    }

    public function parseHeaders(string $filePath, string $ext): array {
        $headers = [];
        $ext = strtolower($ext);

        if ($ext === 'csv') {
            $handle = fopen($filePath, 'r');
            if ($handle !== false) {
                // Skip BOM
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }
                $row = fgetcsv($handle, 4096, ',');
                if (!$row) {
                    rewind($handle);
                    $row = fgetcsv($handle, 4096, ';');
                }
                if ($row) {
                    foreach ($row as $idx => $val) {
                        $headers[$idx] = trim($val);
                    }
                }
                fclose($handle);
            }
        } elseif ($ext === 'json') {
            $content = file_get_contents($filePath);
            $data = json_decode($content, true);
            if (is_array($data)) {
                $first = current($data);
                if (is_array($first)) {
                    $headers = array_keys($first);
                }
            }
        } elseif ($ext === 'xml') {
            $xml = simplexml_load_file($filePath);
            if ($xml !== false) {
                $children = $xml->children();
                if ($children->count() > 0) {
                    $first = $children[0];
                    foreach ($first->children() as $child) {
                        $headers[] = $child->getName();
                    }
                }
            }
        } elseif ($ext === 'xls' || $ext === 'xlsx') {
            // Check if Spreadsheet XML 2003
            $content = file_get_contents($filePath);
            if (str_contains($content, 'ss:Worksheet') || str_contains($content, 'Worksheet')) {
                $xml = simplexml_load_string($content);
                if ($xml !== false) {
                    $namespaces = $xml->getDocNamespaces(true);
                    $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
                    $rows = $xml->xpath('//ss:Row');
                    if (!empty($rows)) {
                        $firstRow = $rows[0];
                        $idx = 0;
                        foreach ($firstRow->Cell as $cell) {
                            $val = (string)$cell->Data;
                            $headers[$idx++] = trim($val);
                        }
                    }
                }
            } else {
                // Fallback to simple tab/comma parsing
                $handle = fopen($filePath, 'r');
                if ($handle !== false) {
                    $row = fgetcsv($handle, 4096, "\t");
                    if ($row) {
                        foreach ($row as $idx => $val) {
                            $headers[$idx] = trim($val);
                        }
                    }
                    fclose($handle);
                }
            }
        }

        return $headers;
    }

    public function importMappedData(string $filePath, string $ext, array $mapping): array {
        $ext = strtolower($ext);
        $records = [];

        if ($ext === 'csv') {
            $handle = fopen($filePath, 'r');
            if ($handle !== false) {
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") rewind($handle);
                $headerRow = fgetcsv($handle, 4096, ',');
                $delimiter = ',';
                if (!$headerRow) {
                    rewind($handle);
                    $headerRow = fgetcsv($handle, 4096, ';');
                    $delimiter = ';';
                }
                while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
                    $records[] = $row;
                }
                fclose($handle);
            }
        } elseif ($ext === 'json') {
            $content = file_get_contents($filePath);
            $records = json_decode($content, true) ?: [];
        } elseif ($ext === 'xml') {
            $xml = simplexml_load_file($filePath);
            if ($xml !== false) {
                foreach ($xml->children() as $item) {
                    $row = [];
                    foreach ($item->children() as $child) {
                        $row[$child->getName()] = (string)$child;
                    }
                    $records[] = $row;
                }
            }
        } elseif ($ext === 'xls' || $ext === 'xlsx') {
            $content = file_get_contents($filePath);
            if (str_contains($content, 'ss:Worksheet') || str_contains($content, 'Worksheet')) {
                $xml = simplexml_load_string($content);
                if ($xml !== false) {
                    $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
                    $rows = $xml->xpath('//ss:Row');
                    if (count($rows) > 1) {
                        for ($i = 1; $i < count($rows); $i++) {
                            $row = [];
                            $idx = 0;
                            foreach ($rows[$i]->Cell as $cell) {
                                $row[$idx++] = (string)$cell->Data;
                            }
                            $records[] = $row;
                        }
                    }
                }
            } else {
                $handle = fopen($filePath, 'r');
                if ($handle !== false) {
                    fgetcsv($handle, 4096, "\t"); // skip header
                    while (($row = fgetcsv($handle, 4096, "\t")) !== false) {
                        $records[] = $row;
                    }
                    fclose($handle);
                }
            }
        }

        $imported = 0;
        $updated = 0;

        $this->db->beginTransaction();
        try {
            foreach ($records as $record) {
                // Map record fields based on mapping keys
                $mapped = [];
                foreach ($mapping as $dbField => $fileKey) {
                    if ($fileKey !== '') {
                        $mapped[$dbField] = $record[$fileKey] ?? '';
                    }
                }

                $name = trim($mapped['name'] ?? '');
                $sku = trim($mapped['sku'] ?? '');
                if ($name === '' || $sku === '') continue;

                $price = (float)($mapped['price'] ?? 0.0);
                $cost = (float)($mapped['cost_price'] ?? 0.0);
                $stock = (int)($mapped['total_stock'] ?? 0);
                $barcode = trim($mapped['barcode'] ?? '');
                $status = trim($mapped['status'] ?? 'draft');

                // Determine if product exists
                $existing = $this->db->query("SELECT id FROM products WHERE sku = :sku AND deleted_at IS NULL LIMIT 1", [':sku' => $sku]);
                if (!empty($existing)) {
                    $pid = (int)$existing[0]['id'];
                    $this->update($pid, [
                        'name' => $name,
                        'sku' => $sku,
                        'price' => $price,
                        'cost_price' => $cost,
                        'total_stock' => $stock,
                        'barcode' => $barcode,
                        'status' => $status
                    ]);
                    $updated++;
                } else {
                    $this->create([
                        'name' => $name,
                        'sku' => $sku,
                        'price' => $price,
                        'cost_price' => $cost,
                        'total_stock' => $stock,
                        'barcode' => $barcode,
                        'status' => $status
                    ]);
                    $imported++;
                }
            }
            $this->db->commit();
            $this->clearCache();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return ['imported' => $imported, 'updated' => $updated];
    }

    public function clearCache(): void {
        $this->cache->delete(self::CACHE_KEY);
        $this->cache->delete('category_tree');
        $this->cache->delete('active_brands');
        $this->cache->delete('attributes_list');
        $this->cache->delete('attribute_sets_list');
    }
}
