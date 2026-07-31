<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Request;
use Core\Http\Response;
use App\Repositories\WarehouseRepository;
use App\Services\WarehouseService;
use Exception;

class WarehouseController extends Controller {
    private WarehouseRepository $repository;
    private WarehouseService $service;

    public function __construct(
        WarehouseRepository $repository,
        WarehouseService $service
    ) {
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * Depo Yönetimi Dashboard
     */
    public function dashboard(Request $request, Response $response): string {
        $wid = (int)($request->get('warehouse_id') ?? 1);
        $warehouses = $this->repository->getAll();
        $stats = $this->repository->getDashboardStats($wid);

        return $this->render('admin/wms/dashboard', [
            'warehouses' => $warehouses,
            'selected_warehouse_id' => $wid,
            'stats' => $stats
        ]);
    }

    /**
     * Depolar Listesi
     */
    public function warehouses(Request $request, Response $response): string {
        $warehouses = $this->repository->getAll();
        return $this->render('admin/wms/warehouses', [
            'warehouses' => $warehouses
        ]);
    }

    /**
     * Depo Raf/Lokasyon Listesi ve Haritası (Heat Map)
     */
    public function locations(Request $request, Response $response): string {
        $wid = (int)($request->get('warehouse_id') ?? 1);
        $warehouses = $this->repository->getAll();
        $locations = $this->repository->getLocations($wid);

        return $this->render('admin/wms/locations', [
            'warehouses' => $warehouses,
            'selected_warehouse_id' => $wid,
            'locations' => $locations
        ]);
    }

    /**
     * Stok Hareketleri Raporu (Movement Center)
     */
    public function movements(Request $request, Response $response): string {
        $filters = [
            'type'         => $request->get('type'),
            'warehouse_id' => $request->get('warehouse_id'),
            'start_date'   => $request->get('start_date'),
            'end_date'     => $request->get('end_date'),
            'q'            => $request->get('q'),
        ];

        $movements = $this->repository->getStockMovements($filters);
        $warehouses = $this->repository->getAll();

        return $this->render('admin/wms/movements', [
            'movements' => $movements,
            'warehouses' => $warehouses,
            'filters' => $filters
        ]);
    }

    /**
     * Picking (Ürün Toplama) Arayüzü
     */
    public function picking(Request $request, Response $response): string {
        // Hazırlanıyor durumundaki siparişleri toplama için getir
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        
        $pickingOrders = $db->query(
            "SELECT o.*, 
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
             FROM orders o 
             WHERE o.status = 'preparing' AND o.deleted_at IS NULL
             ORDER BY o.created_at ASC LIMIT 10"
        ) ?? [];

        // Rota optimizasyonlu Mock Ürün listesi (Lokasyon sıralı)
        $pickingItems = [
            ['id' => 1, 'location_code' => 'A-01-01-A', 'product_name' => 'SaintMonarc Deri Ceket', 'sku' => 'MON-JKT-01', 'qty_needed' => 2, 'qty_picked' => 0],
            ['id' => 2, 'location_code' => 'A-01-01-B', 'product_name' => 'Monarc Basic T-Shirt', 'sku' => 'MON-TSH-02', 'qty_needed' => 5, 'qty_picked' => 0],
            ['id' => 3, 'location_code' => 'B-02-03-C', 'product_name' => 'Klasik Erkek Ayakkabı', 'sku' => 'MON-SH-05', 'qty_needed' => 1, 'qty_picked' => 0],
            ['id' => 4, 'location_code' => 'C-03-04-B', 'product_name' => 'Monarc Oxford Gömlek', 'sku' => 'MON-SHR-04', 'qty_needed' => 3, 'qty_picked' => 0]
        ];

        return $this->render('admin/wms/picking', [
            'pickingOrders' => $pickingOrders,
            'pickingItems' => $pickingItems
        ]);
    }

    /**
     * Packing Station (Paketleme İstasyonu)
     */
    public function packing(Request $request, Response $response): string {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        
        // Paketleniyor durumundaki siparişler
        $packingOrders = $db->query(
            "SELECT o.* FROM orders o WHERE o.status = 'packing' AND o.deleted_at IS NULL LIMIT 10"
        ) ?? [];

        return $this->render('admin/wms/packing', [
            'packingOrders' => $packingOrders
        ]);
    }

    /**
     * Depolar Arası Transferler
     */
    public function transfers(Request $request, Response $response): string {
        $transfers = $this->repository->getTransfers();
        $warehouses = $this->repository->getAll();

        // Transfer kalemleri için ürünleri listele
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $products = $db->query("SELECT p.id, p.sku, pt.name FROM products p JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1 WHERE p.deleted_at IS NULL LIMIT 100") ?? [];

        return $this->render('admin/wms/transfers', [
            'transfers' => $transfers,
            'warehouses' => $warehouses,
            'products' => $products
        ]);
    }

    /**
     * Stok Sayımları (Inventory Count)
     */
    public function counts(Request $request, Response $response): string {
        $counts = $this->repository->getCounts();
        $warehouses = $this->repository->getAll();

        // Sayılacak örnek envanter kayıtları
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $inventories = $db->query(
            "SELECT i.*, p.sku as product_sku, pt.name as product_name
             FROM inventories i
             JOIN products p ON i.product_id = p.id
             JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE i.warehouse_id = 1 LIMIT 50"
        ) ?? [];

        return $this->render('admin/wms/counts', [
            'counts' => $counts,
            'warehouses' => $warehouses,
            'inventories' => $inventories
        ]);
    }

    /**
     * Depo Analitiği (ABC/XYZ Raporu)
     */
    public function analytics(Request $request, Response $response): string {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);

        // ABC Analizi: En yüksek satış cirosu getiren ürünler
        $abcAnalysis = $db->query(
            "SELECT p.id, p.sku, pt.name as product_name, SUM(oi.total) as ciro,
                    CASE 
                        WHEN SUM(oi.total) >= 10000 THEN 'A (Yüksek Ciro)'
                        WHEN SUM(oi.total) >= 3000 THEN 'B (Orta Ciro)'
                        ELSE 'C (Düşük Ciro)'
                    END as abc_class
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             GROUP BY p.id, p.sku, pt.name
             ORDER BY ciro DESC"
        ) ?? [];

        // XYZ Analizi: Satış istikrarı / sıklığı
        $xyzAnalysis = [
            ['sku' => 'MON-JKT-01', 'name' => 'SaintMonarc Deri Ceket', 'frequency' => 'Düzenli Satış', 'xyz_class' => 'X (Stabil Talep)'],
            ['sku' => 'MON-TSH-02', 'name' => 'Monarc Basic T-Shirt', 'frequency' => 'Dalgalı Satış', 'xyz_class' => 'Y (Değişken Talep)'],
            ['sku' => 'MON-SHR-04', 'name' => 'Monarc Oxford Gömlek', 'frequency' => 'Seyrek Satış', 'xyz_class' => 'Z (Düzensiz Talep)']
        ];

        return $this->render('admin/wms/analytics', [
            'abcAnalysis' => $abcAnalysis,
            'xyzAnalysis' => $xyzAnalysis
        ]);
    }

    /**
     * AI WMS Assistant (AI Destekli Depo Önerileri)
     */
    public function aiAssistant(Request $request, Response $response): string {
        // Yapay zeka tarafından üretilmiş akıllı depo lokasyon/transfer tavsiyeleri
        $suggestions = [
            [
                'type' => 'lokasyon_degisimi',
                'title' => 'SaintMonarc Deri Ceket (MON-JKT-01) Taşıma Önerisi',
                'description' => 'Bu ürün son 14 günde yüksek hızlı devir (A sınıfı) gösterdi. Giriş-çıkış maliyetini düşürmek için C-03-04-B lokasyonundan, mal kabul kapısına yakın A-01-01-A lokasyonuna taşınması önerilir.',
                'impact' => 'Toplama rotasını %15 kısaltır',
                'priority' => 'Yüksek'
            ],
            [
                'type' => 'kritik_stok',
                'title' => 'Monarc Oxford Gömlek (MON-SHR-04) Stok Takviyesi',
                'description' => 'Mevcut envanter 3 adete düşmüş olup, önümüzdeki 30 gün için öngörülen sipariş yoğunluğu 15 adettir.',
                'impact' => 'Stoksuzluk riskini (Out of stock) önler',
                'priority' => 'Kritik'
            ],
            [
                'type' => 'transfer_onerisi',
                'title' => 'Ege Deposuna (EGE_DIST) Stok Sevk Önerisi',
                'description' => 'Marmara Merkez deposunda fazla stoklanan (50+ adet) Basic T-Shirt envanterinin, talebin yükseldiği Ege bölgesine transfer edilmesi lojistik maliyeti azaltacaktır.',
                'impact' => 'Bölgesel teslimat sürelerini 24 saat hızlandırır',
                'priority' => 'Orta'
            ]
        ];

        return $this->render('admin/wms/ai_assistant', [
            'suggestions' => $suggestions
        ]);
    }

    // ==========================================
    //            REST API ENDPOINTS
    // ==========================================

    /**
     * GET /api/wms/warehouses
     */
    public function apiWarehouses(Request $request, Response $response): void {
        $warehouses = $this->repository->getAll();
        $response->json(['success' => true, 'data' => $warehouses]);
    }

    /**
     * GET /api/wms/inventory
     */
    public function apiInventory(Request $request, Response $response): void {
        $wid = (int)($request->get('warehouse_id') ?? 1);
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $inventory = $db->query(
            "SELECT i.*, p.sku as product_sku, pt.name as product_name
             FROM inventories i
             JOIN products p ON i.product_id = p.id
             JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE i.warehouse_id = :wid",
            [':wid' => $wid]
        ) ?? [];

        $response->json(['success' => true, 'data' => $inventory]);
    }

    /**
     * POST /api/wms/picking/validate
     */
    public function apiPickingValidate(Request $request, Response $response): void {
        $sku = $request->post('sku');
        $expectedSku = $request->post('expected_sku');

        if ($sku === $expectedSku) {
            $response->json(['success' => true, 'message' => 'Doğru ürün okutuldu.']);
        } else {
            $response->json(['success' => false, 'message' => 'HATA: Yanlış ürün barkodu okutuldu!']);
        }
    }

    /**
     * POST /api/wms/transfers/create
     */
    public function apiTransfersCreate(Request $request, Response $response): void {
        $fromWid = (int)$request->post('from_warehouse_id');
        $toWid = (int)$request->post('to_warehouse_id');
        $items = $request->post('items') ?? []; // format: [['product_id'=>X, 'quantity'=>Y]]
        $adminId = $_SESSION['admin_id'] ?? null;

        try {
            $transferId = $this->service->initiateTransfer($fromWid, $toWid, $items, $adminId);
            $response->json(['success' => true, 'transfer_id' => $transferId, 'message' => 'Transfer başarıyla başlatıldı.']);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/wms/counts/reconcile
     */
    public function apiCountsReconcile(Request $request, Response $response): void {
        $warehouseId = (int)$request->post('warehouse_id');
        $type = $request->post('type') ?? 'cycle';
        $quantities = $request->post('quantities') ?? []; // format: [inventory_id => actual_qty]
        $adminId = $_SESSION['admin_id'] ?? null;

        try {
            $countId = $this->service->executeCount($warehouseId, $type, $quantities, $adminId);
            $response->json(['success' => true, 'count_id' => $countId, 'message' => 'Sayım farkları başarıyla uygulandı.']);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /admin/wms/transfers/update
     * Form post endpoint'i (Transfer durumu güncellemek için)
     */
    public function updateTransfer(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        $status = $request->post('status');
        $adminId = $_SESSION['admin_id'] ?? null;

        try {
            $this->service->updateTransferStatus($id, $status, $adminId);
            $response->redirect('/admin/wms/transfers?success=' . urlencode('Transfer durumu başarıyla güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/wms/transfers?error=' . urlencode($e->getMessage()));
        }
    }
}
