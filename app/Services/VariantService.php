<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\VariantRepository;
use App\Repositories\AttributeRepository;
use App\Services\AuditLogger;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;
use Core\Validation\Validator;
use Exception;

class VariantService {
    private VariantRepository $repository;
    private AttributeRepository $attributeRepository;
    private DatabaseInterface $db;
    private CacheInterface $cache;
    private AuditLogger $auditLogger;

    private const CACHE_KEY = 'variants_list';

    public function __construct(
        VariantRepository $repository,
        AttributeRepository $attributeRepository,
        DatabaseInterface $db,
        CacheInterface $cache,
        AuditLogger $auditLogger
    ) {
        $this->repository = $repository;
        $this->attributeRepository = $attributeRepository;
        $this->db = $db;
        $this->cache = $cache;
        $this->auditLogger = $auditLogger;
    }

    public function getAllCached(array $filters = []): array {
        if (!empty($filters['q']) || isset($filters['product_id']) || isset($filters['brand_id']) || isset($filters['category_id'])) {
            return $this->repository->getAll($filters);
        }

        if ($this->cache->has(self::CACHE_KEY)) {
            return $this->cache->get(self::CACHE_KEY);
        }

        $variants = $this->repository->getAll();
        $this->cache->set(self::CACHE_KEY, $variants, 3600);
        return $variants;
    }

    public function create(array $data): int {
        $validator = Validator::make($data, [
            'product_id' => 'required|integer',
            'sku' => 'required|unique:product_variants,sku',
            'price' => 'required|numeric',
            'cost_price' => 'numeric',
            'weight' => 'numeric',
            'desi' => 'numeric'
        ]);

        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()));
            throw new Exception(implode(' ', $errors));
        }

        $productId = (int)$data['product_id'];
        $sku = trim($data['sku']);
        $barcode = !empty($data['barcode']) ? trim($data['barcode']) : null;
        $imageId = !empty($data['image_id']) ? (int)$data['image_id'] : null;
        $price = (float)$data['price'];
        $compareAtPrice = !empty($data['compare_at_price']) ? (float)$data['compare_at_price'] : null;
        $costPrice = !empty($data['cost_price']) ? (float)$data['cost_price'] : 0.0;
        $specialPrice = !empty($data['special_price']) ? (float)$data['special_price'] : null;
        $specialPriceStart = !empty($data['special_price_start']) ? $data['special_price_start'] : null;
        $specialPriceEnd = !empty($data['special_price_end']) ? $data['special_price_end'] : null;
        $weight = !empty($data['weight']) ? (float)$data['weight'] : 0.0;
        $desi = !empty($data['desi']) ? (float)$data['desi'] : 0.0;
        $width = !empty($data['width']) ? (float)$data['width'] : null;
        $height = !empty($data['height']) ? (float)$data['height'] : null;
        $length = !empty($data['length']) ? (float)$data['length'] : null;
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        $this->db->beginTransaction();
        try {
            // Check product exists
            $prod = $this->db->query("SELECT id FROM products WHERE id = :id AND deleted_at IS NULL LIMIT 1", [':id' => $productId]);
            if (empty($prod)) {
                throw new Exception("İlgili ürün bulunamadı.");
            }

            // Insert into product_variants
            $this->db->execute(
                "INSERT INTO product_variants 
                (product_id, sku, barcode, image_id, price, compare_at_price, cost_price, special_price, special_price_start, special_price_end, weight, desi, width, height, length, is_active)
                VALUES 
                (:product_id, :sku, :barcode, :image_id, :price, :compare_at_price, :cost_price, :special_price, :special_price_start, :special_price_end, :weight, :desi, :width, :height, :length, :is_active)",
                [
                    ':product_id' => $productId,
                    ':sku' => $sku,
                    ':barcode' => $barcode,
                    ':image_id' => $imageId,
                    ':price' => $price,
                    ':compare_at_price' => $compareAtPrice,
                    ':cost_price' => $costPrice,
                    ':special_price' => $specialPrice,
                    ':special_price_start' => $specialPriceStart,
                    ':special_price_end' => $specialPriceEnd,
                    ':weight' => $weight,
                    ':desi' => $desi,
                    ':width' => $width,
                    ':height' => $height,
                    ':length' => $length,
                    ':is_active' => $isActive
                ]
            );
            $variantId = (int)$this->db->lastInsertId();

            // Sync Options mapping (product_variant_options)
            if (!empty($data['options'])) {
                foreach ($data['options'] as $attrId => $valId) {
                    $this->db->execute(
                        "INSERT INTO product_variant_options (variant_id, attribute_id, attribute_value_id)
                         VALUES (:vid, :aid, :valid)",
                        [':vid' => $variantId, ':aid' => (int)$attrId, ':valid' => (int)$valId]
                    );
                }
            }

            // Sync images gallery (product_variant_images)
            if (!empty($data['image_ids'])) {
                foreach ($data['image_ids'] as $idx => $imgId) {
                    $this->db->execute(
                        "INSERT INTO product_variant_images (variant_id, image_id, sort_order) VALUES (:vid, :img_id, :sort)",
                        [':vid' => $variantId, ':img_id' => (int)$imgId, ':sort' => $idx]
                    );
                }
            }

            // Sync Stocks (product_variant_stocks)
            $initialStock = isset($data['stock']) ? (int)$data['stock'] : 0;
            $this->db->execute(
                "INSERT INTO product_variant_stocks (variant_id, warehouse_id, stock, reserved) VALUES (:vid, 1, :stock, 0)",
                [':vid' => $variantId, ':stock' => $initialStock]
            );
            $stockId = (int)$this->db->lastInsertId();

            // Track Stock Movement using inventories & movements
            // Resolve inventory table record
            $this->db->execute(
                "INSERT INTO inventories (product_id, variant_id, stock, reserved_stock) VALUES (:pid, :vid, :stock, 0)",
                [':pid' => $productId, ':vid' => $variantId, ':stock' => $initialStock]
            );
            $invId = (int)$this->db->lastInsertId();

            if ($initialStock > 0) {
                $this->db->execute(
                    "INSERT INTO inventory_movements (inventory_id, quantity, type, description) VALUES (:inv_id, :qty, 'initial_entry', 'İlk stok girişi')",
                    [':inv_id' => $invId, ':qty' => $initialStock]
                );
            }

            // Sync Prices Matrix (product_variant_prices)
            $pricesMatrix = !empty($data['prices']) ? $data['prices'] : [['currency_code' => 'TRY', 'price' => $price, 'compare_at_price' => $compareAtPrice, 'special_price' => $specialPrice]];
            foreach ($pricesMatrix as $pRow) {
                if (empty($pRow['currency_code'])) continue;
                $this->db->execute(
                    "INSERT INTO product_variant_prices (variant_id, currency_code, price, compare_at_price, special_price)
                     VALUES (:vid, :currency, :price, :compare, :special)",
                    [
                        ':vid' => $variantId,
                        ':currency' => trim($pRow['currency_code']),
                        ':price' => (float)$pRow['price'],
                        ':compare' => !empty($pRow['compare_at_price']) ? (float)$pRow['compare_at_price'] : null,
                        ':special' => !empty($pRow['special_price']) ? (float)$pRow['special_price'] : null
                    ]
                );
            }

            // Audit
            $this->auditLogger->logActivity('variant_create', "Yeni ürün varyantı oluşturuldu: SKU {$sku}");
            $this->auditLogger->logAudit('create', 'ProductVariant', $variantId, null, $data);

            $this->db->commit();
            $this->clearCache($productId);
            return $variantId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): void {
        $validator = Validator::make($data, [
            'sku' => "required|unique:product_variants,sku,{$id},id",
            'price' => 'required|numeric',
            'cost_price' => 'numeric',
            'weight' => 'numeric',
            'desi' => 'numeric'
        ]);

        if ($validator->fails()) {
            $errors = array_merge(...array_values($validator->errors()));
            throw new Exception(implode(' ', $errors));
        }

        $current = $this->repository->getById($id);
        if (!$current) {
            throw new Exception("Varyant bulunamadı.");
        }

        $sku = trim($data['sku']);
        $barcode = !empty($data['barcode']) ? trim($data['barcode']) : null;
        $imageId = !empty($data['image_id']) ? (int)$data['image_id'] : null;
        $price = (float)$data['price'];
        $compareAtPrice = !empty($data['compare_at_price']) ? (float)$data['compare_at_price'] : null;
        $costPrice = !empty($data['cost_price']) ? (float)$data['cost_price'] : 0.0;
        $specialPrice = !empty($data['special_price']) ? (float)$data['special_price'] : null;
        $specialPriceStart = !empty($data['special_price_start']) ? $data['special_price_start'] : null;
        $specialPriceEnd = !empty($data['special_price_end']) ? $data['special_price_end'] : null;
        $weight = !empty($data['weight']) ? (float)$data['weight'] : 0.0;
        $desi = !empty($data['desi']) ? (float)$data['desi'] : 0.0;
        $width = !empty($data['width']) ? (float)$data['width'] : null;
        $height = !empty($data['height']) ? (float)$data['height'] : null;
        $length = !empty($data['length']) ? (float)$data['length'] : null;
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        $this->db->beginTransaction();
        try {
            // Update product_variants table
            $this->db->execute(
                "UPDATE product_variants 
                 SET sku = :sku, barcode = :barcode, image_id = :image_id, price = :price, compare_at_price = :compare, cost_price = :cost,
                     special_price = :special, special_price_start = :special_start, special_price_end = :special_end,
                     weight = :weight, desi = :desi, width = :width, height = :height, length = :length, is_active = :is_active, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id",
                [
                    ':sku' => $sku,
                    ':barcode' => $barcode,
                    ':image_id' => $imageId,
                    ':price' => $price,
                    ':compare' => $compareAtPrice,
                    ':cost' => $costPrice,
                    ':special' => $specialPrice,
                    ':special_start' => $specialPriceStart,
                    ':special_end' => $specialPriceEnd,
                    ':weight' => $weight,
                    ':desi' => $desi,
                    ':width' => $width,
                    ':height' => $height,
                    ':length' => $length,
                    ':is_active' => $isActive,
                    ':id' => $id
                ]
            );

            // Sync Options mapping
            $this->db->execute("DELETE FROM product_variant_options WHERE variant_id = :vid", [':vid' => $id]);
            if (!empty($data['options'])) {
                foreach ($data['options'] as $attrId => $valId) {
                    $this->db->execute(
                        "INSERT INTO product_variant_options (variant_id, attribute_id, attribute_value_id)
                         VALUES (:vid, :aid, :valid)",
                        [':vid' => $id, ':aid' => (int)$attrId, ':valid' => (int)$valId]
                    );
                }
            }

            // Sync images gallery
            $this->db->execute("DELETE FROM product_variant_images WHERE variant_id = :vid", [':vid' => $id]);
            if (!empty($data['image_ids'])) {
                foreach ($data['image_ids'] as $idx => $imgId) {
                    $this->db->execute(
                        "INSERT INTO product_variant_images (variant_id, image_id, sort_order) VALUES (:vid, :img_id, :sort)",
                        [':vid' => $id, ':img_id' => (int)$imgId, ':sort' => $idx]
                    );
                }
            }

            // Sync Prices Matrix
            $this->db->execute("DELETE FROM product_variant_prices WHERE variant_id = :vid", [':vid' => $id]);
            if (!empty($data['prices'])) {
                foreach ($data['prices'] as $pRow) {
                    if (empty($pRow['currency_code'])) continue;
                    $this->db->execute(
                        "INSERT INTO product_variant_prices (variant_id, currency_code, price, compare_at_price, special_price)
                         VALUES (:vid, :currency, :price, :compare, :special)",
                        [
                            ':vid' => $id,
                            ':currency' => trim($pRow['currency_code']),
                            ':price' => (float)$pRow['price'],
                            ':compare' => !empty($pRow['compare_at_price']) ? (float)$pRow['compare_at_price'] : null,
                            ':special' => !empty($pRow['special_price']) ? (float)$pRow['special_price'] : null
                        ]
                    );
                }
            }

            // Logs
            $this->auditLogger->logActivity('variant_update', "Ürün varyantı güncellendi: SKU {$sku}");
            $this->auditLogger->logAudit('update', 'ProductVariant', $id, $current, $data);

            $this->db->commit();
            $this->clearCache((int)$current['product_id']);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void {
        $current = $this->repository->getById($id);
        if (!$current) {
            throw new Exception("Varyant bulunamadı.");
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute("UPDATE product_variants SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id", [':id' => $id]);

            // Soft delete inventories associated
            $this->db->execute("UPDATE inventories SET deleted_at = CURRENT_TIMESTAMP WHERE variant_id = :vid", [':vid' => $id]);

            $this->auditLogger->logActivity('variant_delete', "Ürün varyantı silindi: SKU {$current['sku']}");
            $this->auditLogger->logAudit('delete', 'ProductVariant', $id, $current, null);

            $this->db->commit();
            $this->clearCache((int)$current['product_id']);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // --- Stock Movements & Logging ---
    public function adjustStock(int $variantId, int $quantity, string $type, string $description = ''): void {
        if (!in_array($type, ['inlet', 'outlet', 'order', 'return', 'manual_adjustment', 'initial_entry'])) {
            throw new Exception("Geçersiz stok hareket tipi.");
        }

        $current = $this->repository->getById($variantId);
        if (!$current) {
            throw new Exception("Varyant bulunamadı.");
        }

        $this->db->beginTransaction();
        try {
            // Resolve stocks row
            $stocks = $this->db->query("SELECT * FROM product_variant_stocks WHERE variant_id = :vid LIMIT 1", [':vid' => $variantId]);
            $currentStock = 0;
            if (empty($stocks)) {
                $this->db->execute("INSERT INTO product_variant_stocks (variant_id, warehouse_id, stock, reserved) VALUES (:vid, 1, 0, 0)", [':vid' => $variantId]);
            } else {
                $currentStock = (int)$stocks[0]['stock'];
            }

            // Resolve inventories row
            $inv = $this->db->query("SELECT * FROM inventories WHERE variant_id = :vid LIMIT 1", [':vid' => $variantId]);
            if (empty($inv)) {
                $this->db->execute("INSERT INTO inventories (product_id, variant_id, stock, reserved_stock) VALUES (:pid, :vid, 0, 0)", [':pid' => $current['product_id'], ':vid' => $variantId]);
                $invId = (int)$this->db->lastInsertId();
            } else {
                $invId = (int)$inv[0]['id'];
            }

            // Adjust
            $newStock = $currentStock + $quantity;
            if ($newStock < 0) {
                // If the product doesn't allow backorder, check it
                $prod = $this->db->query("SELECT allow_backorder FROM products WHERE id = :pid LIMIT 1", [':pid' => $current['product_id']]);
                $allowBackorder = (int)($prod[0]['allow_backorder'] ?? 0);
                if (!$allowBackorder) {
                    throw new Exception("Stok seviyesi negatif olamaz.");
                }
            }

            // Update tables
            $this->db->execute("UPDATE product_variant_stocks SET stock = :stock WHERE variant_id = :vid", [':stock' => $newStock, ':vid' => $variantId]);
            $this->db->execute("UPDATE inventories SET stock = :stock WHERE id = :id", [':stock' => $newStock, ':id' => $invId]);

            // Add movement log
            $desc = $description !== '' ? $description : "Stok Düzeltmesi ({$type})";
            $this->db->execute(
                "INSERT INTO inventory_movements (inventory_id, quantity, type, description) VALUES (:inv_id, :qty, :type, :desc)",
                [':inv_id' => $invId, ':qty' => $quantity, ':type' => $type, ':desc' => $desc]
            );

            // Update main product total_stock summary
            $this->db->execute(
                "UPDATE products p 
                 SET p.total_stock = (SELECT COALESCE(SUM(pvs.stock), 0) FROM product_variant_stocks pvs JOIN product_variants pv ON pvs.variant_id = pv.id WHERE pv.product_id = p.id AND pv.deleted_at IS NULL)
                 WHERE p.id = :pid",
                [':pid' => $current['product_id']]
            );

            $this->auditLogger->logActivity('stock_adjustment', "SKU {$current['sku']} için stok hareketi: {$quantity} ({$type})");

            $this->db->commit();
            $this->clearCache((int)$current['product_id']);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // --- Autogenerated combination generator ---
    public function generateCombinations(int $productId, array $attributesMap): array {
        // attributesMap is array: [attribute_id => [val_id_1, val_id_2]]
        // Validate attributes map is not empty
        if (empty($attributesMap)) {
            return [];
        }

        // Get attribute and value names for generating SKU and combinations
        $attributeIds = array_keys($attributesMap);
        $attrKeys = [];
        foreach ($attributeIds as $aid) {
            $attr = $this->attributeRepository->getById((int)$aid);
            if ($attr) {
                $attrKeys[$aid] = $attr;
            }
        }

        // Cartesian product
        $combinations = [[]];
        foreach ($attributesMap as $attrId => $valIds) {
            if (empty($valIds)) continue;
            $temp = [];
            foreach ($combinations as $combination) {
                foreach ($valIds as $valId) {
                    $temp[] = $combination + [$attrId => $valId];
                }
            }
            $combinations = $temp;
        }

        // Get product details to generate code prefixes
        $prodRow = $this->db->query("SELECT slug FROM products WHERE id = :id AND deleted_at IS NULL LIMIT 1", [':id' => $productId]);
        if (empty($prodRow)) {
            throw new Exception("Ürün bulunamadı.");
        }
        $prodSlug = $prodRow[0]['slug'];

        $results = [];
        foreach ($combinations as $index => $comb) {
            // comb is [attr_id_1 => val_id_1, attr_id_2 => val_id_2]
            $skuParts = ['SM', strtoupper(str_replace('-', '', $prodSlug))];
            $nameParts = [];
            $options = [];

            foreach ($comb as $aid => $vid) {
                $attr = $attrKeys[$aid];
                $valName = '';
                $valCode = '';
                foreach ($attr['values'] as $v) {
                    if ((int)$v['id'] === (int)$vid) {
                        $valName = $v['name'];
                        $valCode = $v['code'];
                        break;
                    }
                }
                $skuParts[] = strtoupper($valCode);
                $nameParts[] = $valName;
                $options[$aid] = $vid;
            }

            $sku = implode('-', $skuParts);
            $sku = $this->makeSkuUnique($sku);

            $results[] = [
                'sku' => $sku,
                'barcode' => $this->generateEan13(),
                'name' => implode(' ', $nameParts),
                'options' => $options,
                'price' => 0.0,
                'cost_price' => 0.0,
                'stock' => 0,
                'is_active' => 1
            ];
        }

        return $results;
    }

    // --- Auto SKU generator unique check ---
    public function makeSkuUnique(string $sku): string {
        $cleanSku = $sku;
        $counter = 1;
        while (true) {
            $row = $this->db->query("SELECT id FROM product_variants WHERE sku = :sku LIMIT 1", [':sku' => $cleanSku]);
            if (empty($row)) {
                return $cleanSku;
            }
            $cleanSku = $sku . '-' . $counter;
            $counter++;
        }
    }

    // --- Barcode Generators ---
    public function generateEan13(): string {
        // EAN13 is 13 digits: 12 random/sequence digits + 1 check digit
        $digits = '869'; // Turkish manufacturer prefix as default
        for ($i = 0; $i < 9; $i++) {
            $digits .= rand(0, 9);
        }
        return $digits . $this->calculateEan13CheckDigit($digits);
    }

    public function generateEan8(): string {
        // EAN8 is 8 digits: 7 random digits + 1 check digit
        $digits = '869';
        for ($i = 0; $i < 4; $i++) {
            $digits .= rand(0, 9);
        }
        return $digits . $this->calculateEan8CheckDigit($digits);
    }

    public function generateCode128(): string {
        // Standard unique alphanumeric sequence
        return 'C128' . strtoupper(uniqid());
    }

    public function generateQrCode(string $content = ''): string {
        if ($content === '') {
            $content = 'QR' . strtoupper(uniqid());
        }
        return $content;
    }

    private function calculateEan13CheckDigit(string $digits): int {
        // Sum positions
        $oddSum = 0;
        $evenSum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$digits[$i];
            if ($i % 2 === 0) {
                $oddSum += $digit;
            } else {
                $evenSum += $digit;
            }
        }
        $total = $oddSum + ($evenSum * 3);
        return (10 - ($total % 10)) % 10;
    }

    private function calculateEan8CheckDigit(string $digits): int {
        $oddSum = 0;
        $evenSum = 0;
        for ($i = 0; $i < 7; $i++) {
            $digit = (int)$digits[$i];
            if ($i % 2 === 0) {
                $oddSum += $digit;
            } else {
                $evenSum += $digit;
            }
        }
        $total = ($oddSum * 3) + $evenSum;
        return (10 - ($total % 10)) % 10;
    }

    // --- Bulk Operations ---
    public function bulkUpdatePrices(array $ids, float $price, ?float $comparePrice = null, ?float $specialPrice = null): void {
        if (empty($ids)) return;
        $idList = implode(',', array_map('intval', $ids));
        $this->db->beginTransaction();
        try {
            $this->db->execute("UPDATE product_variants SET price = :p, compare_at_price = :c, special_price = :s WHERE id IN ({$idList})", [':p' => $price, ':c' => $comparePrice, ':s' => $specialPrice]);
            
            // Also update currency matrix
            foreach ($ids as $vid) {
                $this->db->execute(
                    "INSERT INTO product_variant_prices (variant_id, currency_code, price, compare_at_price, special_price)
                     VALUES (:vid, 'TRY', :p, :c, :s) ON DUPLICATE KEY UPDATE price = VALUES(price), compare_at_price = VALUES(compare_at_price), special_price = VALUES(special_price)",
                    [':vid' => $vid, ':p' => $price, ':c' => $comparePrice, ':s' => $specialPrice]
                );
            }

            $this->db->commit();
            $this->clearCache();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function bulkUpdateStocks(array $ids, int $stock): void {
        if (empty($ids)) return;
        foreach ($ids as $vid) {
            $stocks = $this->db->query("SELECT stock FROM product_variant_stocks WHERE variant_id = :vid LIMIT 1", [':vid' => $vid]);
            $current = !empty($stocks) ? (int)$stocks[0]['stock'] : 0;
            $diff = $stock - $current;
            $this->adjustStock((int)$vid, $diff, 'manual_adjustment', 'Toplu stok güncellemesi');
        }
        $this->clearCache();
    }

    public function bulkToggleActive(array $ids, int $isActive): void {
        if (empty($ids)) return;
        $idList = implode(',', array_map('intval', $ids));
        $this->db->execute("UPDATE product_variants SET is_active = :active WHERE id IN ({$idList})", [':active' => $isActive]);
        $this->clearCache();
    }

    public function bulkDelete(array $ids): void {
        if (empty($ids)) return;
        foreach ($ids as $id) {
            $this->delete((int)$id);
        }
        $this->clearCache();
    }

    public function bulkSkuGenerate(array $ids): void {
        if (empty($ids)) return;
        $this->db->beginTransaction();
        try {
            foreach ($ids as $id) {
                $var = $this->repository->getById((int)$id);
                if (!$var) continue;
                
                $options = $this->repository->getOptions((int)$id);
                $skuParts = ['SM', strtoupper(str_replace('-', '', $var['product_slug']))];
                foreach ($options as $opt) {
                    $skuParts[] = strtoupper($opt['value_code']);
                }
                $sku = implode('-', $skuParts);
                $sku = $this->makeSkuUnique($sku);

                $this->db->execute("UPDATE product_variants SET sku = :sku WHERE id = :id", [':sku' => $sku, ':id' => $id]);
            }
            $this->db->commit();
            $this->clearCache();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function bulkBarcodeGenerate(array $ids, string $format = 'EAN13'): void {
        if (empty($ids)) return;
        $this->db->beginTransaction();
        try {
            foreach ($ids as $id) {
                $barcode = '';
                if ($format === 'EAN13') $barcode = $this->generateEan13();
                elseif ($format === 'EAN8') $barcode = $this->generateEan8();
                elseif ($format === 'Code128') $barcode = $this->generateCode128();
                else $barcode = $this->generateQrCode();

                $this->db->execute("UPDATE product_variants SET barcode = :barcode WHERE id = :id", [':barcode' => $barcode, ':id' => $id]);
            }
            $this->db->commit();
            $this->clearCache();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // --- Export Functions ---
    public function exportCsv(array $filters = []): string {
        $variants = $this->repository->getAll($filters);
        $output = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel Turkish character support
        $output .= "ID,Ürün Adı,SKU,Barkod,Stok,Fiyat,İndirimli Fiyat,Ağırlık,Desi,Aktiflik\n";
        foreach ($variants as $v) {
            $activeText = (int)$v['is_active'] === 1 ? 'Aktif' : 'Pasif';
            $output .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",%d,%.2f,%.2f,%.2f,%.2f,\"%s\"\n",
                $v['id'],
                str_replace('"', '""', $v['product_name']),
                str_replace('"', '""', $v['sku']),
                str_replace('"', '""', $v['barcode'] ?? ''),
                $v['total_stock'],
                $v['price'],
                $v['special_price'] ?? 0.0,
                $v['weight'] ?? 0.0,
                $v['desi'] ?? 0.0,
                $activeText
            );
        }
        return $output;
    }

    public function exportExcel(array $filters = []): string {
        // Generate XML Spreadsheet 2003 format (native, no dependency, opens perfectly in Excel with formatting)
        $variants = $this->repository->getAll($filters);
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Worksheet ss:Name="Varyant Listesi">
  <Table>
   <Row>
    <Cell><Data ss:Type="String">ID</Data></Cell>
    <Cell><Data ss:Type="String">Ürün Adı</Data></Cell>
    <Cell><Data ss:Type="String">SKU</Data></Cell>
    <Cell><Data ss:Type="String">Barkod</Data></Cell>
    <Cell><Data ss:Type="String">Stok</Data></Cell>
    <Cell><Data ss:Type="String">Fiyat</Data></Cell>
    <Cell><Data ss:Type="String">İndirimli Fiyat</Data></Cell>
    <Cell><Data ss:Type="String">Ağırlık (kg)</Data></Cell>
    <Cell><Data ss:Type="String">Desi</Data></Cell>
    <Cell><Data ss:Type="String">Durum</Data></Cell>
   </Row>';
        
        foreach ($variants as $v) {
            $activeText = (int)$v['is_active'] === 1 ? 'Aktif' : 'Pasif';
            $xml .= sprintf('
   <Row>
    <Cell><Data ss:Type="Number">%d</Data></Cell>
    <Cell><Data ss:Type="String">%s</Data></Cell>
    <Cell><Data ss:Type="String">%s</Data></Cell>
    <Cell><Data ss:Type="String">%s</Data></Cell>
    <Cell><Data ss:Type="Number">%d</Data></Cell>
    <Cell><Data ss:Type="Number">%.2f</Data></Cell>
    <Cell><Data ss:Type="Number">%.2f</Data></Cell>
    <Cell><Data ss:Type="Number">%.2f</Data></Cell>
    <Cell><Data ss:Type="Number">%.2f</Data></Cell>
    <Cell><Data ss:Type="String">%s</Data></Cell>
   </Row>',
                $v['id'],
                htmlspecialchars($v['product_name'] ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($v['sku'] ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($v['barcode'] ?? '', ENT_QUOTES, 'UTF-8'),
                $v['total_stock'],
                $v['price'],
                $v['special_price'] ?? 0.0,
                $v['weight'] ?? 0.0,
                $v['desi'] ?? 0.0,
                $activeText
            );
        }

        $xml .= '
  </Table>
 </Worksheet>
</Workbook>';
        return $xml;
    }

    private function clearCache(?int $productId = null): void {
        $this->cache->delete(self::CACHE_KEY);
        if ($productId) {
            $this->cache->delete("product_variants_{$productId}");
        }
    }
}
