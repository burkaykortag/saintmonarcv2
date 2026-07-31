<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\DatabaseInterface;
use App\Repositories\WarehouseRepository;
use Exception;

class WarehouseService {
    private WarehouseRepository $repository;
    private DatabaseInterface $db;
    private AuditLogger $auditLogger;

    public function __construct(
        WarehouseRepository $repository,
        DatabaseInterface $db,
        AuditLogger $auditLogger
    ) {
        $this->repository = $repository;
        $this->db = $db;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Stok Miktarını ve Hareketlerini Günceller (Giriş/Çıkış/Transfer/Sayım)
     */
    public function adjustStock(
        int $productId,
        ?int $variantId,
        int $warehouseId,
        int $qtyDelta,
        string $type,
        string $description
    ): int {
        // Envanter kaydını getir veya oluştur
        $inv = $this->db->query(
            "SELECT id, stock FROM inventories 
             WHERE product_id = :pid AND COALESCE(variant_id, 0) = :vid AND warehouse_id = :wid LIMIT 1",
            [
                ':pid' => $productId,
                ':vid' => $variantId ?? 0,
                ':wid' => $warehouseId
            ]
        );

        if (!empty($inv)) {
            $inventoryId = (int)$inv[0]['id'];
            $newStock = (int)$inv[0]['stock'] + $qtyDelta;
            if ($newStock < 0) {
                throw new Exception("Yetersiz stok! Mevcut: {$inv[0]['stock']}, Düşülmek İstenen: " . abs($qtyDelta));
            }
            $this->db->execute(
                "UPDATE inventories SET stock = :stock, updated_at = NOW() WHERE id = :id",
                [':stock' => $newStock, ':id' => $inventoryId]
            );
        } else {
            if ($qtyDelta < 0) {
                throw new Exception("Depoda ürün kaydı bulunmamaktadır, eksi stok yapılamaz!");
            }
            $this->db->execute(
                "INSERT INTO inventories (product_id, variant_id, warehouse_id, stock, reserved_stock, created_at, updated_at)
                 VALUES (:pid, :vid, :wid, :stock, 0, NOW(), NOW())",
                [
                    ':pid' => $productId,
                    ':vid' => $variantId,
                    ':wid' => $warehouseId,
                    ':stock' => $qtyDelta
                ]
            );
            $inventoryId = (int)$this->db->lastInsertId();
        }

        // Stok Hareket Kaydı Ekle
        $this->db->execute(
            "INSERT INTO inventory_movements (inventory_id, quantity, type, description, created_at)
             VALUES (:iid, :qty, :type, :desc, NOW())",
            [
                ':iid' => $inventoryId,
                ':qty' => $qtyDelta,
                ':type' => $type,
                ':desc' => $description
            ]
        );

        // Toplam ürün tablosu stok adedini de güncelle
        $this->recalculateProductStock($productId);

        return $inventoryId;
    }

    /**
     * Depolar Arası Transfer Başlatır
     */
    public function initiateTransfer(int $fromWid, int $toWid, array $items, ?int $adminId): int {
        if ($fromWid === $toWid) {
            throw new Exception("Aynı depo içine transfer yapılamaz.");
        }
        if (empty($items)) {
            throw new Exception("Transfer edilecek en az bir ürün seçilmelidir.");
        }

        $this->db->beginTransaction();
        try {
            // Transfer kaydını oluştur
            $this->db->execute(
                "INSERT INTO warehouse_transfers (from_warehouse_id, to_warehouse_id, status, created_by_admin, created_at, updated_at)
                 VALUES (:from, :to, 'pending', :admin, NOW(), NOW())",
                [
                    ':from' => $fromWid,
                    ':to' => $toWid,
                    ':admin' => $adminId
                ]
            );
            $transferId = (int)$this->db->lastInsertId();

            // Transfer kalemlerini oluştur
            foreach ($items as $item) {
                $pid = (int)$item['product_id'];
                $vid = isset($item['variant_id']) ? (int)$item['variant_id'] : null;
                $qty = (int)$item['quantity'];

                if ($qty <= 0) continue;

                // Kaynak depoda yeterli stok var mı doğrula
                $stockCheck = $this->db->query(
                    "SELECT stock FROM inventories WHERE product_id = :pid AND COALESCE(variant_id, 0) = :vid AND warehouse_id = :wid LIMIT 1",
                    [':pid' => $pid, ':vid' => $vid ?? 0, ':wid' => $fromWid]
                );
                $available = (int)($stockCheck[0]['stock'] ?? 0);
                if ($qty > $available) {
                    throw new Exception("Kaynak depoda yeterli stok yok. Mevcut: {$available}, İstenen: {$qty}");
                }

                $this->db->execute(
                    "INSERT INTO warehouse_transfer_items (transfer_id, product_id, variant_id, quantity, created_at)
                     VALUES (:tid, :pid, :vid, :qty, NOW())",
                    [
                        ':tid' => $transferId,
                        ':pid' => $pid,
                        ':vid' => $vid,
                        ':qty' => $qty
                    ]
                );
            }

            $this->auditLogger->logActivity('wms_transfer_init', "Depolar arası transfer talebi oluşturuldu (No: #{$transferId})");
            $this->db->commit();
            return $transferId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Transfer Durumunu Günceller
     */
    public function updateTransferStatus(int $transferId, string $status, ?int $adminId): void {
        $transfer = $this->db->query("SELECT * FROM warehouse_transfers WHERE id = :id LIMIT 1", [':id' => $transferId]);
        if (empty($transfer)) {
            throw new Exception("Transfer kaydı bulunamadı.");
        }
        $t = $transfer[0];

        $this->db->beginTransaction();
        try {
            if ($status === 'approved') {
                $this->db->execute(
                    "UPDATE warehouse_transfers SET status = 'approved', approved_by_admin = :admin, updated_at = NOW() WHERE id = :id",
                    [':admin' => $adminId, ':id' => $transferId]
                );
            } elseif ($status === 'shipped') {
                // Stokları kaynak depodan düş (Sevk Etme)
                $items = $this->db->query("SELECT * FROM warehouse_transfer_items WHERE transfer_id = :tid", [':tid' => $transferId]);
                foreach ($items as $item) {
                    $this->adjustStock(
                        (int)$item['product_id'],
                        $item['variant_id'] ? (int)$item['variant_id'] : null,
                        (int)$t['from_warehouse_id'],
                        -(int)$item['quantity'],
                        'transfer',
                        "Transfer Sevk Edildi (Transfer No: #{$transferId})"
                    );
                }

                $this->db->execute(
                    "UPDATE warehouse_transfers SET status = 'shipped', shipped_at = NOW(), updated_at = NOW() WHERE id = :id",
                    [':id' => $transferId]
                );
            } elseif ($status === 'completed') {
                // Stokları hedef depoya ekle (Tamamlama)
                $items = $this->db->query("SELECT * FROM warehouse_transfer_items WHERE transfer_id = :tid", [':tid' => $transferId]);
                foreach ($items as $item) {
                    $this->adjustStock(
                        (int)$item['product_id'],
                        $item['variant_id'] ? (int)$item['variant_id'] : null,
                        (int)$t['to_warehouse_id'],
                        (int)$item['quantity'],
                        'transfer',
                        "Transfer Kabul Edildi (Transfer No: #{$transferId})"
                    );
                }

                $this->db->execute(
                    "UPDATE warehouse_transfers SET status = 'completed', received_at = NOW(), updated_at = NOW() WHERE id = :id",
                    [':id' => $transferId]
                );
            } else {
                $this->db->execute(
                    "UPDATE warehouse_transfers SET status = :status, updated_at = NOW() WHERE id = :id",
                    [':status' => $status, ':id' => $transferId]
                );
            }

            $this->auditLogger->logActivity('wms_transfer_status', "Transfer durumu güncellendi: {$status} (No: #{$transferId})");
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Stok Sayımı Başlatır / Reconcile Eder
     */
    public function executeCount(int $warehouseId, string $type, array $items, ?int $adminId): int {
        if (empty($items)) {
            throw new Exception("Sayılacak en az bir ürün envanteri olmalıdır.");
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO inventory_counts (warehouse_id, status, type, created_by_admin, created_at, updated_at)
                 VALUES (:wid, 'completed', :type, :admin, NOW(), NOW())",
                [
                    ':wid' => $warehouseId,
                    ':type' => $type,
                    ':admin' => $adminId
                ]
            );
            $countId = (int)$this->db->lastInsertId();

            foreach ($items as $invId => $actualQty) {
                $invId = (int)$invId;
                $actualQty = (int)$actualQty;

                $inv = $this->db->query("SELECT product_id, variant_id, stock FROM inventories WHERE id = :id LIMIT 1", [':id' => $invId]);
                if (empty($inv)) continue;

                $expected = (int)$inv[0]['stock'];
                $diff = $actualQty - $expected;

                // Sayım kalem detayını ekle
                $this->db->execute(
                    "INSERT INTO inventory_count_items (count_id, inventory_id, expected_quantity, actual_quantity, difference_quantity, created_at)
                     VALUES (:cid, :iid, :expected, :actual, :diff, NOW())",
                    [
                        ':cid' => $countId,
                        ':iid' => $invId,
                        ':expected' => $expected,
                        ':actual' => $actualQty,
                        ':diff' => $diff
                    ]
                );

                // Eğer fark varsa stoğu sayım sonucuna eşitlemek için düzelt
                if ($diff !== 0) {
                    $this->adjustStock(
                        (int)$inv[0]['product_id'],
                        $inv[0]['variant_id'] ? (int)$inv[0]['variant_id'] : null,
                        $warehouseId,
                        $diff,
                        'sayım',
                        "Sayımdan Doğan Envanter Düzeltmesi (Sayım No: #{$countId})"
                    );
                }
            }

            $this->auditLogger->logActivity('wms_count_completed', "Stok sayımı tamamlandı ve farklar düzeltildi (No: #{$countId})");
            $this->db->commit();
            return $countId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ürün tablosundaki toplam stoğu alt envanter stoklarının toplamı olarak günceller
     */
    private function recalculateProductStock(int $productId): void {
        $totals = $this->db->query(
            "SELECT COALESCE(SUM(stock), 0) as tot FROM inventories WHERE product_id = :pid",
            [':pid' => $productId]
        );
        $totalQty = (int)($totals[0]['tot'] ?? 0);

        $this->db->execute(
            "UPDATE products SET total_stock = :tot, updated_at = NOW() WHERE id = :pid",
            [':tot' => $totalQty, ':pid' => $productId]
        );
    }
}
