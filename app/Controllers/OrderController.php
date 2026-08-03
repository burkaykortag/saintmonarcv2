<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\OrderService;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\BrandRepository;
use Core\Contracts\DatabaseInterface;
use Core\View\View;
use Exception;

class OrderController extends Controller {
    private OrderService $service;
    private OrderRepository $repository;
    private ProductRepository $productRepository;
    private CategoryRepository $categoryRepository;
    private BrandRepository $brandRepository;
    private DatabaseInterface $db;

    public function __construct(
        View $view,
        OrderService $service,
        OrderRepository $repository,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        BrandRepository $brandRepository,
        DatabaseInterface $db
    ) {
        parent::__construct($view);
        $this->service = $service;
        $this->repository = $repository;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->brandRepository = $brandRepository;
        $this->db = $db;
    }

    /**
     * Sipariş listeleme sayfası
     */
    public function index(Request $request, Response $response): string {
        $filters = [];
        $keys = [
            'order_number', 'customer', 'phone', 'email', 'status', 'city',
            'payment_method_id', 'shipping_method_id', 'min_amount', 'max_amount',
            'date_start', 'date_end', 'product_id', 'category_id', 'brand_id'
        ];

        foreach ($keys as $key) {
            if ($request->get($key) !== null && $request->get($key) !== '') {
                $filters[$key] = $request->get($key);
            }
        }

        // Aktif siparişler
        $orders = $this->repository->getAll($filters, false);
        // Silinmiş siparişler
        $trash = $this->repository->getAll($filters, true);

        $statuses = $this->repository->getStatuses();
        $products = $this->productRepository->getAll();
        $categories = $this->categoryRepository->getAll();
        $brands = $this->brandRepository->getAll();
        
        $paymentMethods = $this->db->query("SELECT * FROM payment_methods");
        $shippingMethods = $this->db->query("SELECT * FROM shipping_methods");

        return $this->render('admin/orders/index', [
            'orders' => $orders,
            'trash' => $trash,
            'statuses' => $statuses,
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'paymentMethods' => $paymentMethods,
            'shippingMethods' => $shippingMethods,
            'filters' => $filters
        ]);
    }

    /**
     * Sipariş detay sayfası
     */
    public function show(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $order = $this->repository->getById($id, true);

        if (!$order) {
            $response->redirect('/admin/orders?error=' . urlencode('Sipariş bulunamadı.'));
            exit;
        }

        $items = $this->repository->getItems($id);
        $notes = $this->repository->getNotes($id);
        $history = $this->repository->getStatusHistory($id);
        $shipments = $this->repository->getShipments($id);
        $refunds = $this->repository->getRefunds($id);
        $transactions = $this->repository->getPaymentTransactions($id);
        
        $statuses = $this->repository->getStatuses();
        $shippingMethods = $this->db->query("SELECT * FROM shipping_methods");
        $paymentMethods = $this->db->query("SELECT * FROM payment_methods");

        // Görüntüleme günlüğü logla
        $auditLogger = \Core\Application::getInstance()->getContainer()->get(\App\Services\AuditLogger::class);
        $auditLogger->logActivity('order_view', "Sipariş detayları görüntülendi: {$order['order_number']} (ID: {$id})");

        return $this->render('admin/orders/show', [
            'order' => $order,
            'items' => $items,
            'notes' => $notes,
            'history' => $history,
            'shipments' => $shipments,
            'refunds' => $refunds,
            'transactions' => $transactions,
            'statuses' => $statuses,
            'shippingMethods' => $shippingMethods,
            'paymentMethods' => $paymentMethods
        ]);
    }

    /**
     * Manuel sipariş oluşturma formu
     */
    public function showCreate(Request $request, Response $response): string {
        $products = $this->productRepository->getAll();
        $paymentMethods = $this->db->query("SELECT * FROM payment_methods");
        $shippingMethods = $this->db->query("SELECT * FROM shipping_methods");
        $users = $this->db->query("SELECT id, name, email FROM users WHERE deleted_at IS NULL LIMIT 100");

        return $this->render('admin/orders/create', [
            'products' => $products,
            'paymentMethods' => $paymentMethods,
            'shippingMethods' => $shippingMethods,
            'users' => $users
        ]);
    }

    /**
     * Manuel sipariş kaydetme
     */
    public function store(Request $request, Response $response): void {
        try {
            $data = $request->post();
            
            // Post verilerini düzenle
            $items = [];
            if (!empty($data['product_ids'])) {
                foreach ($data['product_ids'] as $idx => $pid) {
                    $qty = (int)$data['quantities'][$idx];
                    $prod = $this->productRepository->getById((int)$pid);
                    if ($prod) {
                        $items[] = [
                            'product_id' => $prod['id'],
                            'product_sku' => $prod['sku'],
                            'product_name' => $prod['name'],
                            'quantity' => $qty,
                            'price' => (float)$prod['price'],
                            'tax_amount' => (float)($prod['price'] * 0.18), // Varsayılan %18 KDV
                            'total' => ($prod['price'] * $qty) * 1.18
                        ];
                    }
                }
            }
            $data['items'] = $items;

            // Fiyat hesapla
            $subtotal = 0.0;
            $taxTotal = 0.0;
            foreach ($items as $item) {
                $subtotal += $item['price'] * $item['quantity'];
                $taxTotal += $item['tax_amount'] * $item['quantity'];
            }
            $discount = (float)($data['discount_total'] ?? 0.0);
            $shipping = (float)($data['shipping_total'] ?? 0.0);
            
            $data['subtotal'] = $subtotal;
            $data['tax_total'] = $taxTotal;
            $data['grand_total'] = $subtotal + $taxTotal + $shipping - $discount;

            $id = $this->service->create($data);
            $response->redirect('/admin/orders/show?id=' . $id . '&success=' . urlencode('Sipariş başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders/create?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Sipariş düzenleme sayfası
     */
    public function showEdit(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $order = $this->repository->getById($id);

        if (!$order) {
            $response->redirect('/admin/orders?error=' . urlencode('Sipariş bulunamadı.'));
            exit;
        }

        $items = $this->repository->getItems($id);
        $products = $this->productRepository->getAll();
        $shippingMethods = $this->db->query("SELECT * FROM shipping_methods");
        $paymentMethods = $this->db->query("SELECT * FROM payment_methods");

        return $this->render('admin/orders/edit', [
            'order' => $order,
            'items' => $items,
            'products' => $products,
            'shippingMethods' => $shippingMethods,
            'paymentMethods' => $paymentMethods
        ]);
    }

    /**
     * Sipariş güncelleme
     */
    public function update(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $data = $request->post();
            
            // Adres, fatura ve genel sipariş bilgilerini güncelle
            $this->service->update($id, $data);

            // Kalemleri güncelle
            $items = [];
            if (!empty($data['product_ids'])) {
                foreach ($data['product_ids'] as $idx => $pid) {
                    $qty = (int)$data['quantities'][$idx];
                    $prod = $this->productRepository->getById((int)$pid);
                    if ($prod) {
                        $items[] = [
                            'product_id' => $prod['id'],
                            'product_sku' => $prod['sku'],
                            'product_name' => $prod['name'],
                            'quantity' => $qty,
                            'price' => (float)$prod['price'],
                            'tax_amount' => (float)($prod['price'] * 0.18),
                            'total' => ($prod['price'] * $qty) * 1.18
                        ];
                    }
                }
                $this->service->updateItems($id, $items);
            }

            $response->redirect('/admin/orders/show?id=' . $id . '&success=' . urlencode('Sipariş başarıyla güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Sipariş silme (Soft delete)
     */
    public function delete(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->service->softDelete($id);
            $response->redirect('/admin/orders?success=' . urlencode('Sipariş başarıyla çöpe taşındı.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Sipariş geri yükleme
     */
    public function restore(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->service->restore($id);
            $response->redirect('/admin/orders?success=' . urlencode('Sipariş başarıyla geri yüklendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Kalıcı silme
     */
    public function forceDelete(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->service->forceDelete($id);
            $response->redirect('/admin/orders?success=' . urlencode('Sipariş kalıcı olarak silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Sipariş kopyalama
     */
    public function duplicate(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $order = $this->repository->getById($id);
            if (!$order) {
                throw new Exception("Kopyalanacak sipariş bulunamadı.");
            }
            $items = $this->repository->getItems($id);
            
            $data = $order;
            $data['items'] = $items;
            unset($data['id'], $data['order_number'], $data['created_at'], $data['updated_at']);

            $newId = $this->service->create($data);
            $response->redirect('/admin/orders/show?id=' . $newId . '&success=' . urlencode('Sipariş başarıyla kopyalandı.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Kargo ekleme formu/post işlemi
     */
    public function addShipment(Request $request, Response $response): void {
        $orderId = (int)$request->post('order_id');
        try {
            $data = $request->post();
            $data['update_order_status'] = isset($data['update_order_status']) ? 1 : 0;
            
            $this->service->addShipment($orderId, $data);
            $response->redirect('/admin/orders/show?id=' . $orderId . '&success=' . urlencode('Kargo kaydı başarıyla eklendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders/show?id=' . $orderId . '&error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * İade kaydı ekleme
     */
    public function addRefund(Request $request, Response $response): void {
        $orderId = (int)$request->post('order_id');
        try {
            $data = $request->post();
            
            // Eğer dosya yüklendiyse (Opsiyonel kanıtlar)
            // Biz basitçe path kaydı yapalım
            if (!empty($_FILES['image']['tmp_name'])) {
                $data['image_path'] = '/uploads/refunds/' . time() . '_' . $_FILES['image']['name'];
                @move_uploaded_file($_FILES['image']['tmp_name'], dirname(dirname(__DIR__)) . '/public' . $data['image_path']);
            }
            
            $this->service->addRefund($orderId, $data);
            $response->redirect('/admin/orders/show?id=' . $orderId . '&success=' . urlencode('İade talebi başarıyla işlendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders/show?id=' . $orderId . '&error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Sipariş notu ekleme
     */
    public function addNote(Request $request, Response $response): void {
        $orderId = (int)$request->post('order_id');
        $note = $request->post('note') ?? '';
        $isInternal = isset($data['is_internal']) || $request->post('is_internal') == '1';

        try {
            if (trim($note) === '') {
                throw new Exception("Not içeriği boş olamaz.");
            }
            $this->service->addNote($orderId, $note, $isInternal);
            $response->redirect('/admin/orders/show?id=' . $orderId . '&success=' . urlencode('Sipariş notu başarıyla eklendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders/show?id=' . $orderId . '&error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Toplu İşlemler
     */
    public function bulk(Request $request, Response $response): void {
        $action = $request->post('action') ?? '';
        $ids = $request->post('order_ids') ?? [];

        if (empty($ids)) {
            $response->redirect('/admin/orders?error=' . urlencode('Hiçbir sipariş seçilmedi.'));
            return;
        }

        try {
            $this->db->beginTransaction();

            if ($action === 'delete') {
                foreach ($ids as $id) {
                    $this->service->softDelete((int)$id);
                }
            } elseif ($action === 'status') {
                $targetStatus = $request->post('target_status') ?? '';
                if ($targetStatus !== '') {
                    foreach ($ids as $id) {
                        $this->service->update((int)$id, [
                            'status' => $targetStatus,
                            'status_comment' => 'Toplu durum değişikliği.'
                        ]);
                    }
                }
            } elseif ($action === 'invoice') {
                // Toplu fatura oluşturma simülasyonu
                foreach ($ids as $id) {
                    $order = $this->repository->getById((int)$id);
                    if ($order) {
                        $this->db->execute(
                            "INSERT INTO invoices (order_id, invoice_number, issue_date, subtotal, tax_total, grand_total, created_at) 
                             VALUES (:oid, :num, CURDATE(), :sub, :tax, :grand, NOW())
                             ON DUPLICATE KEY UPDATE updated_at = NOW()",
                            [
                                ':oid' => $order['id'],
                                ':num' => 'INV-' . time() . '-' . $order['id'],
                                ':sub' => $order['subtotal'],
                                ':tax' => $order['tax_total'],
                                ':grand' => $order['grand_total']
                            ]
                        );
                    }
                }
            }

            $this->db->commit();
            $this->service->clearCache();
            $response->redirect('/admin/orders?success=' . urlencode('Toplu işlem başarıyla tamamlandı.'));
        } catch (Exception $e) {
            $this->db->rollBack();
            $response->redirect('/admin/orders?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Dışa Aktarma (Excel, CSV, XML, JSON)
     */
    public function export(Request $request, Response $response): void {
        $format = $request->get('format') ?? 'csv';
        $orders = $this->repository->getAll();

        $content = $this->service->exportData($format, $orders);

        if ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename=orders_export_' . date('Ymd_His') . '.json');
        } elseif ($format === 'xml') {
            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename=orders_export_' . date('Ymd_His') . '.xml');
        } elseif ($format === 'excel') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename=orders_export_' . date('Ymd_His') . '.xls');
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=orders_export_' . date('Ymd_His') . '.csv');
        }

        echo $content;
        exit;
    }

    /**
     * PDF Belgesi Üretme (Fatura, İrsaliye, Sipariş Formu, Kargo Etiketi)
     * Not: Native PHP + CSS / Print düzeni ile PDF indirmesi sağlar veya temiz bir print düzeni sunar.
     */
    public function generatePdf(Request $request, Response $response): void {
        $id = (int)$request->get('id');
        $type = $request->get('type') ?? 'invoice'; // invoice, packing_slip, order_form, shipping_label

        $order = $this->repository->getById($id, true);
        if (!$order) {
            echo "Sipariş bulunamadı.";
            exit;
        }

        $items = $this->repository->getItems($id);

        // PDF indirilmeli gibi davranarak HTML print sayfası render edelim.
        // Bu sayfa CSS @media print ile doğrudan yazdırmaya veya PDF olarak kaydetmeye tam hazırdır.
        header('Content-Type: text/html; charset=utf-8');
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title><?= htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars(ucfirst($type), ENT_QUOTES, 'UTF-8') ?></title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
                body { font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif; color: #333; margin: 20px; font-size: 13px; line-height: 1.5; }
                .container { max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 30px; border-radius: 8px; background: #fff; }
                .header { display: flex; justify-content: space-between; border-bottom: 2px solid #c5a880; padding-bottom: 20px; margin-bottom: 20px; }
                .logo { font-size: 24px; font-weight: bold; color: #c5a880; }
                .title { font-size: 18px; text-transform: uppercase; font-weight: bold; margin-top: 5px; }
                .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
                .box { border: 1px solid #eee; padding: 15px; border-radius: 6px; background: #fafafa; }
                .box h3 { margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 5px; font-size: 14px; color: #c5a880; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th { background: #f5f5f5; border-bottom: 2px solid #ddd; padding: 10px; text-align: left; font-size: 12px; }
                td { border-bottom: 1px solid #eee; padding: 10px; font-size: 12px; }
                .totals { float: right; width: 300px; margin-top: 20px; }
                .totals-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee; }
                .totals-row.grand { font-weight: bold; font-size: 15px; border-bottom: 2px solid #c5a880; padding-top: 8px; }
                .footer { text-align: center; font-size: 10px; color: #777; margin-top: 100px; border-top: 1px solid #ddd; padding-top: 15px; }
                .label-box { border: 2px dashed #333; padding: 20px; width: 400px; margin: 0 auto; border-radius: 10px; text-align: center; }
                @media print {
                    body { margin: 0; background: none; }
                    .container { border: none; padding: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body onload="window.print();">
            <div class="no-print" style="background: #f8f9fa; padding: 10px; text-align: center; margin-bottom: 20px; border-radius: 6px;">
                <button onclick="window.print();" style="padding: 8px 16px; background: #c5a880; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">YAZDIR / PDF KAYDET</button>
                <button onclick="window.close();" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">KAPAT</button>
            </div>

            <?php if ($type === 'shipping_label'): ?>
                <!-- KARGO ETİKETİ -->
                <div class="label-box">
                    <div style="font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #c5a880;">SAINTMONARC KARGO SEVK ETİKETİ</div>
                    <hr style="border-top: 1px solid #ddd;">
                    <div style="text-align: left; margin: 15px 0;">
                        <strong>ALICI:</strong><br>
                        <?= htmlspecialchars($order['shipping_first_name'] . ' ' . $order['shipping_last_name']) ?><br>
                        <?= htmlspecialchars($order['shipping_address']) ?><br>
                        <?= htmlspecialchars($order['shipping_city'] . ' / ' . $order['shipping_zip']) ?><br>
                        <strong>Tel:</strong> <?= htmlspecialchars($order['customer_phone'] ?? '-') ?>
                    </div>
                    <hr style="border-top: 1px solid #ddd;">
                    <div style="margin: 15px 0;">
                        <div style="font-family: 'Code39', monospace; font-size: 32px; letter-spacing: 5px;">*<?= $order['order_number'] ?>*</div>
                        <div style="font-size: 12px; margin-top: 5px;">Sipariş No: <?= htmlspecialchars($order['order_number']) ?></div>
                    </div>
                    <div style="font-size: 10px; color: #777;">Tarih: <?= $order['created_at'] ?></div>
                </div>
            <?php else: ?>
                <!-- FATURA / İRSALİYE / SİPARİŞ FORMU -->
                <div class="container">
                    <div class="header">
                        <div>
                            <div class="logo">SAINTMONARC</div>
                            <div style="font-size: 11px; color: #777;">Premium E-Commerce Systems</div>
                        </div>
                        <div style="text-align: right;">
                            <div class="title">
                                <?php
                                if ($type === 'invoice') echo "E-ARŞİV FATURA";
                                elseif ($type === 'packing_slip') echo "SEVK İRSALİYESİ";
                                else echo "SİPARİŞ FORMU";
                                ?>
                            </div>
                            <div style="font-weight: bold;">No: <?= htmlspecialchars($order['order_number']) ?></div>
                            <div style="color: #555;">Tarih: <?= date('d.m.Y', strtotime($order['created_at'])) ?></div>
                        </div>
                    </div>

                    <div class="grid">
                        <div class="box">
                            <h3>Fatura Bilgileri</h3>
                            <strong><?= htmlspecialchars($order['billing_first_name'] . ' ' . $order['billing_last_name']) ?></strong><br>
                            <?= htmlspecialchars($order['billing_address']) ?><br>
                            <?= htmlspecialchars($order['billing_city'] . ' / ' . $order['billing_country'] . ' ' . $order['billing_zip']) ?><br>
                            <strong>E-Posta:</strong> <?= htmlspecialchars($order['customer_email'] ?? '') ?><br>
                            <strong>Telefon:</strong> <?= htmlspecialchars($order['customer_phone'] ?? '') ?>
                        </div>
                        <div class="box">
                            <h3>Teslimat Bilgileri</h3>
                            <strong><?= htmlspecialchars($order['shipping_first_name'] . ' ' . $order['shipping_last_name']) ?></strong><br>
                            <?= htmlspecialchars($order['shipping_address']) ?><br>
                            <?= htmlspecialchars($order['shipping_city'] . ' / ' . $order['shipping_country'] . ' ' . $order['shipping_zip']) ?><br>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Ürün Adı</th>
                                <th>Adet</th>
                                <th>Birim Fiyat</th>
                                <th>KDV</th>
                                <th>Toplam</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_sku']) ?></td>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><?= number_format((float)$item['price'], 2) ?> TRY</td>
                                    <td><?= number_format((float)$item['tax_amount'], 2) ?> TRY</td>
                                    <td><?= number_format((float)$item['total'], 2) ?> TRY</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="totals">
                        <div class="totals-row">
                            <span>Ara Toplam</span>
                            <span><?= number_format((float)$order['subtotal'], 2) ?> TRY</span>
                        </div>
                        <div class="totals-row">
                            <span>KDV Toplamı</span>
                            <span><?= number_format((float)$order['tax_total'], 2) ?> TRY</span>
                        </div>
                        <?php if ((float)$order['discount_total'] > 0): ?>
                            <div class="totals-row" style="color: red;">
                                <span>İndirim</span>
                                <span>-<?= number_format((float)$order['discount_total'], 2) ?> TRY</span>
                            </div>
                        <?php endif; ?>
                        <?php if ((float)$order['shipping_total'] > 0): ?>
                            <div class="totals-row">
                                <span>Kargo Ücreti</span>
                                <span><?= number_format((float)$order['shipping_total'], 2) ?> TRY</span>
                            </div>
                        <?php endif; ?>
                        <div class="totals-row grand">
                            <span>Genel Toplam</span>
                            <span><?= number_format((float)$order['grand_total'], 2) ?> TRY</span>
                        </div>
                    </div>
                    <div style="clear: both;"></div>

                    <div class="footer">
                        Bu belge SaintMonarc Enterprise E-Ticaret altyapısı kullanılarak dijital ortamda otomatik olarak oluşturulmuştur.<br>
                        SaintMonarc Inc. - saintmonarc.test
                    </div>
                </div>
            <?php endif; ?>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Raporlama ve İstatistik Ekranı
     */
    public function reports(Request $request, Response $response): string {
        $period = $request->get('period') ?? 'this_month';
        $data = $this->service->getReports($period);

        return $this->render('admin/orders/reports', [
            'data' => $data,
            'period' => $period
        ]);
    }

    /**
     * Sipariş Durumları Yönetim Ekranı
     */
    public function showStatuses(Request $request, Response $response): string {
        $statuses = $this->repository->getStatuses();
        return $this->render('admin/orders/statuses', [
            'statuses' => $statuses
        ]);
    }

    /**
     * Yeni durum ekleme
     */
    public function storeStatus(Request $request, Response $response): void {
        try {
            $data = $request->post();
            if (empty($data['code']) || empty($data['name'])) {
                throw new Exception("Durum kodu ve adı zorunludur.");
            }
            $data['code'] = strtolower(preg_replace('/[^a-z0-9_]/', '', $data['code']));
            
            $this->repository->createStatus($data);
            $response->redirect('/admin/orders/statuses?success=' . urlencode('Sipariş durumu eklendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders/statuses?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Durum güncelleme
     */
    public function updateStatus(Request $request, Response $response): void {
        $code = $request->post('code') ?? '';
        try {
            $data = $request->post();
            $this->repository->updateStatus($code, $data);
            $response->redirect('/admin/orders/statuses?success=' . urlencode('Sipariş durumu güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders/statuses?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Durum silme
     */
    public function deleteStatus(Request $request, Response $response): void {
        $code = $request->post('code') ?? '';
        try {
            $res = $this->repository->deleteStatus($code);
            if (!$res) {
                throw new Exception("Sistem durumu silinemez veya durum bulunamadı.");
            }
            $response->redirect('/admin/orders/statuses?success=' . urlencode('Sipariş durumu başarıyla silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders/statuses?error=' . urlencode($e->getMessage()));
        }
    }

    // ─────────────────────────────────────────────────────────────
    // REST API ENDPOINTS
    // ─────────────────────────────────────────────────────────────

    public function apiIndex(Request $request, Response $response): void {
        try {
            $orders = $this->repository->getAll();
            $response->json(['success' => true, 'data' => $orders]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiShow(Request $request, Response $response): void {
        $path = $request->getUri();
        $parts = explode('/', trim($path, '/'));
        $orderNumber = end($parts);

        try {
            $sql = "SELECT id FROM orders WHERE order_number = :num AND deleted_at IS NULL LIMIT 1";
            $row = $this->db->query($sql, [':num' => $orderNumber]);
            if (empty($row)) {
                throw new Exception("Sipariş bulunamadı.", 404);
            }
            
            $id = (int)$row[0]['id'];
            $order = $this->repository->getById($id);
            $order['items'] = $this->repository->getItems($id);

            $response->json(['success' => true, 'data' => $order]);
        } catch (Exception $e) {
            $code = $e->getCode() === 404 ? 404 : 500;
            $response->json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    public function apiStore(Request $request, Response $response): void {
        try {
            $data = json_decode($request->getBody(), true) ?? $request->post();
            if (empty($data)) {
                throw new Exception("Boş veri gönderildi.");
            }
            $id = $this->service->create($data);
            $order = $this->repository->getById($id);
            $response->json(['success' => true, 'message' => 'Sipariş oluşturuldu.', 'order_id' => $id, 'order_number' => $order['order_number']], 201);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function apiUpdate(Request $request, Response $response): void {
        $path = $request->getUri();
        $parts = explode('/', trim($path, '/'));
        $orderNumber = end($parts);

        try {
            $sql = "SELECT id FROM orders WHERE order_number = :num AND deleted_at IS NULL LIMIT 1";
            $row = $this->db->query($sql, [':num' => $orderNumber]);
            if (empty($row)) {
                throw new Exception("Sipariş bulunamadı.", 404);
            }
            
            $id = (int)$row[0]['id'];
            $data = json_decode($request->getBody(), true) ?? $request->post();
            if (empty($data)) {
                throw new Exception("Boş veri gönderildi.");
            }

            $this->service->update($id, $data);
            $response->json(['success' => true, 'message' => 'Sipariş güncellendi.']);
        } catch (Exception $e) {
            $code = $e->getCode() === 404 ? 404 : 400;
            $response->json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    public function apiDelete(Request $request, Response $response): void {
        $path = $request->getUri();
        $parts = explode('/', trim($path, '/'));
        $orderNumber = end($parts);

        try {
            $sql = "SELECT id FROM orders WHERE order_number = :num AND deleted_at IS NULL LIMIT 1";
            $row = $this->db->query($sql, [':num' => $orderNumber]);
            if (empty($row)) {
                throw new Exception("Sipariş bulunamadı.", 404);
            }
            
            $id = (int)$row[0]['id'];
            $this->service->softDelete($id);
            $response->json(['success' => true, 'message' => 'Sipariş silindi (Soft Delete).']);
        } catch (Exception $e) {
            $code = $e->getCode() === 404 ? 404 : 400;
            $response->json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SPRINT 30 – ENTERPRISE OMS NEW ACTIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Executive Order Dashboard
     */
    public function dashboard(Request $request, Response $response): string {
        // Real stats from service
        $period = $request->get('period') ?? 'today';
        $reportData = $this->service->getReports($period);
        $statuses   = $this->repository->getStatuses();

        // Status-keyed counts (real data where available)
        $statusCounts = [];
        foreach ($statuses as $st) {
            $row = $this->db->query(
                "SELECT COUNT(*) as cnt FROM orders WHERE status = :s AND deleted_at IS NULL",
                [':s' => $st['code']]
            );
            $statusCounts[$st['code']] = (int)($row[0]['cnt'] ?? 0);
        }

        // Today's revenue
        $todayRevenue = $this->db->query(
            "SELECT COALESCE(SUM(grand_total),0) as rev FROM orders WHERE DATE(created_at)=CURDATE() AND deleted_at IS NULL"
        );
        $revenue = (float)($todayRevenue[0]['rev'] ?? 0);

        // Today's count
        $todayCount = $this->db->query(
            "SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at)=CURDATE() AND deleted_at IS NULL"
        );

        // Recent orders for live feed (last 20)
        $recentOrders = $this->db->query(
            "SELECT o.*, os.name as status_name, os.color as status_color, os.icon as status_icon 
             FROM orders o 
             LEFT JOIN order_statuses os ON o.status = os.code 
             WHERE o.deleted_at IS NULL 
             ORDER BY o.id DESC LIMIT 20"
        ) ?? [];

        return $this->render('admin/orders/dashboard', [
            'statusCounts'  => $statusCounts,
            'statuses'      => $statuses,
            'revenue'       => $revenue,
            'todayCount'    => (int)($todayCount[0]['cnt'] ?? 0),
            'recentOrders'  => $recentOrders,
            'reportData'    => $reportData,
            'period'        => $period,
        ]);
    }

    /**
     * Order Analytics & Intelligence
     */
    public function analytics(Request $request, Response $response): string {
        $period     = $request->get('period') ?? 'this_month';
        $reportData = $this->service->getReports($period);
        $statuses   = $this->repository->getStatuses();

        // Hourly distribution (today)
        $hourlyData = $this->db->query(
            "SELECT HOUR(created_at) as hr, COUNT(*) as cnt, COALESCE(SUM(grand_total),0) as rev 
             FROM orders WHERE DATE(created_at)=CURDATE() AND deleted_at IS NULL 
             GROUP BY HOUR(created_at) ORDER BY hr"
        ) ?? [];

        // Daily data (last 30 days)
        $dailyData = $this->db->query(
            "SELECT DATE(created_at) as day, COUNT(*) as cnt, COALESCE(SUM(grand_total),0) as rev 
             FROM orders WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) AND deleted_at IS NULL 
             GROUP BY DATE(created_at) ORDER BY day"
        ) ?? [];

        // Status distribution
        $statusDist = [];
        foreach ($statuses as $st) {
            $row = $this->db->query(
                "SELECT COUNT(*) as cnt FROM orders WHERE status=:s AND deleted_at IS NULL",
                [':s' => $st['code']]
            );
            $statusDist[] = ['name' => $st['name'], 'color' => $st['color'], 'cnt' => (int)($row[0]['cnt'] ?? 0)];
        }

        return $this->render('admin/orders/analytics', [
            'reportData'  => $reportData,
            'period'      => $period,
            'hourlyData'  => $hourlyData,
            'dailyData'   => $dailyData,
            'statusDist'  => $statusDist,
            'statuses'    => $statuses,
        ]);
    }

    /**
     * Packing Center
     */
    public function packing(Request $request, Response $response): string {
        // Orders ready for packing (preparing/pending statuses)
        $packingOrders = $this->db->query(
            "SELECT o.*, os.name as status_name, os.color as status_color, os.icon as status_icon,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
             FROM orders o 
             LEFT JOIN order_statuses os ON o.status = os.code 
             WHERE o.status IN ('preparing','pending','new','processing') AND o.deleted_at IS NULL
             ORDER BY o.created_at ASC LIMIT 50"
        ) ?? [];

        $shippingMethods = $this->db->query("SELECT * FROM shipping_methods") ?? [];

        return $this->render('admin/orders/packing', [
            'packingOrders'   => $packingOrders,
            'shippingMethods' => $shippingMethods,
        ]);
    }

    /**
     * Shipping Center
     */
    public function shipping(Request $request, Response $response): string {
        $filter = $request->get('filter') ?? 'all';

        // Shipments with order info
        $shipments = $this->db->query(
            "SELECT s.*, o.order_number, o.billing_first_name, o.billing_last_name, 
                    o.billing_city, o.grand_total, o.currency_code
             FROM shipments s 
             LEFT JOIN orders o ON s.order_id = o.id 
             WHERE o.deleted_at IS NULL 
             ORDER BY s.id DESC LIMIT 100"
        ) ?? [];

        $shippingMethods = $this->db->query("SELECT * FROM shipping_methods") ?? [];

        return $this->render('admin/orders/shipping', [
            'shipments'       => $shipments,
            'shippingMethods' => $shippingMethods,
            'filter'          => $filter,
        ]);
    }

    /**
     * Payment Center
     */
    public function payment(Request $request, Response $response): string {
        $filter = $request->get('filter') ?? 'all';

        // Payment transactions with order info
        $transactions = $this->db->query(
            "SELECT t.*, o.order_number, o.billing_first_name, o.billing_last_name,
                    o.grand_total, o.currency_code
             FROM payment_transactions t 
             LEFT JOIN orders o ON t.order_id = o.id 
             WHERE o.deleted_at IS NULL 
             ORDER BY t.id DESC LIMIT 100"
        ) ?? [];

        $paymentMethods = $this->db->query("SELECT * FROM payment_methods") ?? [];

        // Pending payment orders
        $pendingPayment = $this->db->query(
            "SELECT o.*, os.name as status_name, os.color as status_color
             FROM orders o 
             LEFT JOIN order_statuses os ON o.status = os.code 
             WHERE o.status IN ('pending','awaiting') AND o.deleted_at IS NULL 
             ORDER BY o.created_at DESC LIMIT 50"
        ) ?? [];

        return $this->render('admin/orders/payment', [
            'transactions'   => $transactions,
            'paymentMethods' => $paymentMethods,
            'pendingPayment' => $pendingPayment,
            'filter'         => $filter,
        ]);
    }

    /**
     * Sipariş Kanban Tahtası (Sprint 31)
     */
    public function kanban(Request $request, Response $response): string {
        $orders = $this->db->query(
            "SELECT o.*, os.name as status_name, os.color as status_color, os.icon as status_icon
             FROM orders o
             LEFT JOIN order_statuses os ON o.status = os.code
             WHERE o.deleted_at IS NULL AND o.status IN ('pending', 'processing', 'packing', 'shipped', 'delivered')
             ORDER BY o.id DESC LIMIT 100"
        ) ?? [];

        $board = [
            'pending' => [],
            'processing' => [],
            'packing' => [],
            'shipped' => [],
            'delivered' => []
        ];

        foreach ($orders as $o) {
            $createdAt = strtotime($o['created_at']);
            $slaLimit = $createdAt + (24 * 3600); // 24 Hours SLA
            $o['sla_remaining_seconds'] = $slaLimit - time();
            $o['is_delayed'] = ($o['sla_remaining_seconds'] < 0 && !in_array($o['status'], ['shipped', 'delivered']));
            
            $status = $o['status'];
            if ($status === 'new') $status = 'pending';
            if ($status === 'preparing') $status = 'processing';
            
            if (isset($board[$status])) {
                $board[$status][] = $o;
            } else {
                $board['pending'][] = $o;
            }
        }

        return $this->render('admin/orders/kanban', [
            'board' => $board
        ]);
    }

    /**
     * Sipariş Birleştirme Ekranı (Sprint 31)
     */
    public function showMerge(Request $request, Response $response): string {
        $candidates = $this->db->query(
            "SELECT billing_first_name, billing_last_name, COUNT(*) as cnt, GROUP_CONCAT(id) as ids, GROUP_CONCAT(order_number) as nums
             FROM orders 
             WHERE status = 'pending' AND deleted_at IS NULL 
             GROUP BY billing_first_name, billing_last_name, user_id
             HAVING cnt > 1"
        ) ?? [];

        $groups = [];
        foreach ($candidates as $c) {
            $idsArr = explode(',', $c['ids']);
            $ordersInGroup = [];
            foreach ($idsArr as $id) {
                $ord = $this->repository->getById((int)$id);
                if ($ord) {
                    $ord['items'] = $this->repository->getItems((int)$id);
                    $ordersInGroup[] = $ord;
                }
            }
            $groups[] = [
                'customer' => $c['billing_first_name'] . ' ' . $c['billing_last_name'],
                'orders' => $ordersInGroup
            ];
        }

        return $this->render('admin/orders/merge', [
            'groups' => $groups
        ]);
    }

    /**
     * Sipariş Birleştirme İşlemi (Sprint 31)
     */
    public function merge(Request $request, Response $response): void {
        $orderIds = $request->post('order_ids') ?? [];
        try {
            if (empty($orderIds) || count($orderIds) < 2) {
                throw new Exception("En az iki sipariş seçilmelidir.");
            }
            $parentId = $this->service->mergeOrders($orderIds);
            $response->redirect('/admin/orders/show?id=' . $parentId . '&success=' . urlencode('Siparişler başarıyla birleştirildi.'));
         } catch (Exception $e) {
             $response->redirect('/admin/orders/merge?error=' . urlencode($e->getMessage()));
         }
    }

    /**
     * Kısmi Gönderim Ekranı (Sprint 31)
     */
    public function showPartialShipment(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $order = $this->repository->getById($id);
        if (!$order) {
            $response->redirect('/admin/orders?error=' . urlencode('Sipariş bulunamadı.'));
            exit;
        }

        $items = $this->repository->getItems($id);
        foreach ($items as &$item) {
            $shipped = $this->db->query(
                "SELECT COALESCE(SUM(quantity_shipped), 0) as cnt 
                 FROM order_shipment_items WHERE order_item_id = :id",
                [':id' => $item['id']]
            );
            $item['quantity_shipped'] = (int)($shipped[0]['cnt'] ?? 0);
            $item['quantity_pending'] = $item['quantity'] - $item['quantity_shipped'];
        }

        $shippingMethods = $this->db->query("SELECT * FROM shipping_methods") ?? [];

        return $this->render('admin/orders/partial_shipment', [
            'order' => $order,
            'items' => $items,
            'shippingMethods' => $shippingMethods
        ]);
    }

    /**
     * Kısmi Gönderim Kaydetme (Sprint 31)
     */
    public function createPartialShipment(Request $request, Response $response): void {
        $orderId = (int)$request->post('order_id');
        $quantities = $request->post('quantities') ?? [];
        $carrier = $request->post('carrier_name') ?? 'Yurtiçi Kargo';
        $tracking = $request->post('tracking_number') ?? '';
        $methodId = (int)($request->post('shipping_method_id') ?? 1);

        try {
            if (empty($tracking)) {
                throw new Exception("Takip numarası zorunludur.");
            }
            
            $itemsToShip = [];
            foreach ($quantities as $itemId => $qty) {
                if ((int)$qty > 0) {
                    $itemsToShip[(int)$itemId] = (int)$qty;
                }
            }

            if (empty($itemsToShip)) {
                throw new Exception("En az bir üründen 1 adet gönderilmelidir.");
            }

            $this->service->createPartialShipment($orderId, $itemsToShip, $carrier, $tracking, $methodId);
            $response->redirect('/admin/orders/show?id=' . $orderId . '&success=' . urlencode('Kısmi gönderim oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/orders/partial-shipment?id=' . $orderId . '&error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Yazdırma Merkezi (Sprint 31)
     */
    public function printCenter(Request $request, Response $response): string {
        $filters = [];
        $orders = $this->repository->getAll($filters, false);

        return $this->render('admin/orders/print_center', [
            'orders' => $orders
        ]);
    }
}
