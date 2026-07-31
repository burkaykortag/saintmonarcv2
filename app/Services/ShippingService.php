<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShippingRepository;
use Core\Contracts\CacheInterface;
use Exception;

class ShippingService {
    private ShippingRepository $repository;
    private CacheInterface $cache;

    public function __construct(ShippingRepository $repository, CacheInterface $cache) {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    /**
     * Hacimsel Desi Hesaplaması yapar: (En * Boy * Yükseklik) / 3000
     */
    public function calculateDesi(float $width, float $height, float $length): float {
        if ($width <= 0 || $height <= 0 || $length <= 0) {
            return 1.0;
        }
        return ($width * $height * $length) / 3000.0;
    }

    /**
     * Kargo Fiyatı ve Limit Kurallarını Hesaplar.
     */
    public function calculateShippingCost(
        int $serviceId,
        string $countryCode,
        ?string $cityName,
        float $desi,
        float $orderAmount = 0.00
    ): float {
        $cacheKey = "ship_cost_{$serviceId}_{$countryCode}_" . ($cityName ?? 'all') . "_{$desi}_{$orderAmount}";
        if ($this->cache->has($cacheKey)) {
            return (float)$this->cache->get($cacheKey);
        }

        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);

        // Kural Kontrolleri (Ücretsiz Kargo vb.)
        $rules = $db->query("SELECT * FROM shipping_rules WHERE is_active = 1 AND deleted_at IS NULL");
        foreach ($rules as $rule) {
            // Ücretsiz kargo barajı aşılmış mı?
            if ($rule['free_shipping_limit'] !== null && $orderAmount >= (float)$rule['free_shipping_limit']) {
                $this->cache->set($cacheKey, 0.00, 3600);
                return 0.00;
            }
        }

        // Bölge Matrisinden Fiyat Sorgulama
        $priceRow = $this->repository->getMatchingZonePrice($serviceId, $countryCode, $cityName, $desi);
        $cost = $priceRow ? (float)$priceRow['price'] : 49.90; // Default fallback cargo fee

        $this->cache->set($cacheKey, $cost, 3600);
        return $cost;
    }

    /**
     * Eşsiz kargo takip numarası üretir (SM-TRK-XXXXXXXXX).
     */
    public function generateTrackingNumber(): string {
        return 'SM-TRK-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Yeni Sevkiyat/Gönderi oluşturur.
     */
    public function createShipment(array $data, array $items = []): int {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $db->beginTransaction();

        try {
            if (empty($data['tracking_number'])) {
                $data['tracking_number'] = $this->generateTrackingNumber();
            }

            // Kargo etiket barkodunu ata
            $data['barcode'] = 'BC-' . time() . '-' . rand(100, 999);
            $data['qr_code'] = 'QR-' . $data['tracking_number'];

            $packageId = $this->repository->createPackage($data, $items);

            // Takip Kartını Oluştur
            $trackingId = $this->repository->upsertTracking([
                'package_id' => $packageId,
                'tracking_number' => $data['tracking_number'],
                'latest_status' => 'pending',
                'estimated_delivery' => date('Y-m-d', strtotime('+3 days'))
            ]);

            // İlk Takip Hareketini Ekle
            $this->repository->addTrackingEvent($trackingId, [
                'status' => 'pending',
                'location' => 'Ana Depo',
                'description' => 'Gönderi kaydı oluşturuldu, kurye bekleniyor.',
                'event_date' => date('Y-m-d H:i:s')
            ]);

            // Audit log yaz
            $adminId = $_SESSION['admin_id'] ?? 1;
            $db->execute(
                "INSERT INTO audit_logs (user_type, user_id, event, auditable_type, auditable_id, new_values, created_at)
                 VALUES ('Admin', :user_id, 'create_shipment', 'ShippingPackage', :pkg_id, :payload, NOW())",
                [
                    ':user_id' => $adminId,
                    ':pkg_id' => $packageId,
                    ':payload' => json_encode(['tracking_number' => $data['tracking_number']])
                ]
            );

            $db->commit();
            $this->clearShippingCache();
            return $packageId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Takip durumunu ve olaylarını günceller.
     */
    public function updateTracking(string $trackingNumber, string $status, ?string $location, ?string $description): bool {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $db->beginTransaction();

        try {
            $pkg = $this->repository->getPackageByTrackingNumber($trackingNumber);
            if (!$pkg) {
                $db->rollBack();
                return false;
            }

            $packageId = (int)$pkg['id'];
            $this->repository->updatePackageStatus($packageId, $status);

            $trackingId = $this->repository->upsertTracking([
                'package_id' => $packageId,
                'tracking_number' => $trackingNumber,
                'latest_status' => $status,
                'estimated_delivery' => date('Y-m-d', strtotime('+1 days'))
            ]);

            $this->repository->addTrackingEvent($trackingId, [
                'status' => $status,
                'location' => $location,
                'description' => $description,
                'event_date' => date('Y-m-d H:i:s')
            ]);

            // Finans ve Entegrasyon Senkronizasyonu
            if ($status === 'delivered') {
                $expCatId = 1;
                $expCatRow = $db->query("SELECT id FROM expense_categories LIMIT 1");
                if (!empty($expCatRow)) {
                    $expCatId = (int)$expCatRow[0]['id'];
                } else {
                    $db->execute("INSERT IGNORE INTO expense_categories (id, name, code) VALUES (1, 'Genel Gider', 'GENEL')");
                }

                // Kargo teslim edildiğinde kargo maliyetinin finans modülüne yansıtılması (Gider olarak işlenmesi)
                $db->execute(
                    "INSERT INTO expenses (category_id, amount, tax_amount, description, expense_date, created_at)
                     VALUES (:cat_id, :amount, :tax, :desc, NOW(), NOW())",
                    [
                        ':cat_id' => $expCatId,
                        ':amount' => $pkg['shipping_cost'],
                        ':tax' => $pkg['shipping_cost'] * 0.20,
                        ':desc' => "Kargo Teslimat Maliyeti: {$trackingNumber}"
                    ]
                );
            }

            $db->commit();
            $this->clearShippingCache();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Mock PDF kargo barkod etiketi oluşturur.
     */
    public function generateLabel(int $packageId): string {
        $pkg = $this->repository->getPackage($packageId);
        if (!$pkg) return '';

        $labelDir = 'public/uploads/labels';
        if (!is_dir(ROOT_DIR . '/' . $labelDir)) {
            @mkdir(ROOT_DIR . '/' . $labelDir, 0777, true);
        }

        $fileName = "label_{$packageId}_" . time() . ".pdf";
        $filePath = $labelDir . '/' . $fileName;

        // Mock PDF content representation
        $content = "SAINTMONARC SHIP LABEL\n";
        $content .= "TRACKING: " . ($pkg['tracking_number'] ?? '-') . "\n";
        $content .= "BARCODE: " . ($pkg['barcode'] ?? '-') . "\n";
        $content .= "COST: " . ($pkg['shipping_cost'] ?? '0.00') . " TRY\n";

        file_put_contents(ROOT_DIR . '/' . $filePath, $content);

        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $db->execute(
            "INSERT INTO shipping_labels (package_id, label_path, format, created_at)
             VALUES (:package_id, :path, 'pdf', NOW())",
            [
                ':package_id' => $packageId,
                ':path' => $filePath
            ]
        );

        return $filePath;
    }

    /**
     * Kargo İade Talebi oluşturur.
     */
    public function createReturnRequest(array $data, array $items = []): int {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $db->beginTransaction();

        try {
            $data['return_number'] = 'SM-RET-' . rand(100000, 999999);
            $data['status'] = 'requested';

            $returnId = $this->repository->createReturn($data, $items);

            $db->commit();
            $this->clearShippingCache();
            return $returnId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * İade Durumunu Günceller.
     */
    public function updateReturn(int $returnId, string $status): bool {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $db->beginTransaction();

        try {
            $ret = $this->repository->getReturn($returnId);
            if (!$ret) {
                $db->rollBack();
                return false;
            }

            $this->repository->updateReturnStatus($returnId, $status);

            // Depoya giriş veya iade tamamlandı durumlarında finans iadesi tetiklenebilir
            if ($status === 'completed') {
                $catId = 1;
                $catRow = $db->query("SELECT id FROM revenue_categories LIMIT 1");
                if (!empty($catRow)) {
                    $catId = (int)$catRow[0]['id'];
                } else {
                    $db->execute("INSERT IGNORE INTO revenue_categories (id, name, code) VALUES (1, 'Genel Gelir', 'GENEL')");
                }

                // Siparişin finansal iadesini tetikle
                $db->execute(
                    "INSERT INTO revenues (category_id, amount, tax_amount, description, revenue_date, created_at)
                     VALUES (:cat_id, :amount, 0, :desc, NOW(), NOW())",
                    [
                        ':cat_id' => $catId,
                        ':amount' => -100.00, // Örnek ters gelir kaydı
                        ':desc' => "İade Gelir Düzeltmesi: {$ret['return_number']}"
                    ]
                );
            }

            $db->commit();
            $this->clearShippingCache();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Toplu kargo gönderisi oluşturur.
     */
    public function bulkShip(array $orders): array {
        $pkgIds = [];
        foreach ($orders as $order) {
            $pkgIds[] = $this->createShipment([
                'order_id' => $order['order_id'],
                'service_id' => $order['service_id'],
                'desi' => $order['desi'] ?? 1.0,
                'weight' => $order['weight'] ?? 1.0,
                'shipping_cost' => $order['shipping_cost'] ?? 29.90
            ], $order['items'] ?? []);
        }
        return $pkgIds;
    }

    /**
     * Kargo Önbelleğini temizler.
     */
    public function clearShippingCache(): void {
        $this->cache->clear();
    }
}
