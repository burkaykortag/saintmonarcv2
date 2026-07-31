<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\PromotionService;
use App\Repositories\PromotionRepository;
use App\Repositories\CustomerRepository;
use Core\Contracts\DatabaseInterface;
use Core\View\View;
use Exception;

class PromotionController extends Controller {
    private PromotionService $service;
    private PromotionRepository $repository;
    private CustomerRepository $customerRepository;
    private DatabaseInterface $db;

    public function __construct(
        View $view,
        PromotionService $service,
        PromotionRepository $repository,
        CustomerRepository $customerRepository,
        DatabaseInterface $db
    ) {
        parent::__construct($view);
        $this->service = $service;
        $this->repository = $repository;
        $this->customerRepository = $customerRepository;
        $this->db = $db;
    }

    /**
     * Kampanya listesi
     */
    public function index(Request $request, Response $response): string {
        $filters = [];
        $keys = ['search', 'status', 'type'];
        foreach ($keys as $key) {
            if ($request->get($key) !== null && $request->get($key) !== '') {
                $filters[$key] = $request->get($key);
            }
        }

        $promotions = $this->repository->getAll($filters, false);
        $trash = $this->repository->getAll($filters, true);
        
        $viewMode = $request->get('view') === 'card' ? 'card' : 'list';

        return $this->render('admin/promotions/index', [
            'promotions' => $promotions,
            'trash' => $trash,
            'filters' => $filters,
            'viewMode' => $viewMode
        ]);
    }

    /**
     * Yeni kampanya oluşturma formu
     */
    public function showCreate(Request $request, Response $response): string {
        $products = $this->db->query("SELECT id, sku FROM products WHERE deleted_at IS NULL");
        $categories = $this->db->query("SELECT c.id, ct.name FROM categories c JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1 WHERE c.deleted_at IS NULL");
        $brands = $this->db->query("SELECT b.id, bt.name FROM brands b JOIN brand_translations bt ON b.id = bt.brand_id AND bt.language_id = 1 WHERE b.deleted_at IS NULL");
        $groups = $this->customerRepository->getGroups();
        $segments = $this->customerRepository->getSegments();

        return $this->render('admin/promotions/create', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'groups' => $groups,
            'segments' => $segments
        ]);
    }

    /**
     * Kampanya kaydetme
     */
    public function store(Request $request, Response $response): void {
        try {
            $data = $request->post();
            
            // Koşul verilerini topla
            $conditions = [];
            if (!empty($data['rule_min_cart'])) {
                $conditions[] = ['rule_type' => 'min_cart', 'operator' => '>=', 'value' => $data['rule_min_cart'], 'group_operator' => 'AND'];
            }
            if (!empty($data['rule_max_cart'])) {
                $conditions[] = ['rule_type' => 'max_cart', 'operator' => '<=', 'value' => $data['rule_max_cart'], 'group_operator' => 'AND'];
            }
            if (!empty($data['rule_min_items'])) {
                $conditions[] = ['rule_type' => 'min_items', 'operator' => '>=', 'value' => $data['rule_min_items'], 'group_operator' => 'AND'];
            }
            if (!empty($data['rule_day_of_week'])) {
                $conditions[] = ['rule_type' => 'day_of_week', 'operator' => 'IN', 'value' => implode(',', $data['rule_day_of_week']), 'group_operator' => 'AND'];
            }
            $data['conditions'] = $conditions;

            // Aksiyon verilerini topla
            $actions = [];
            $actions[] = [
                'type' => $data['action_type'], // discount_percentage, discount_fixed, free_shipping
                'amount' => (float)($data['action_amount'] ?? 0),
                'target_type' => $data['action_target'] ?? 'cart',
                'target_ids' => !empty($data['action_target_ids']) ? implode(',', $data['action_target_ids']) : null
            ];
            $data['actions'] = $actions;

            // Hediye verilerini topla
            $gifts = [];
            if (!empty($data['gift_product_id'])) {
                $gifts[] = [
                    'gift_type' => 'product',
                    'target_id' => (int)$data['gift_product_id'],
                    'quantity' => (int)($data['gift_qty'] ?? 1)
                ];
            }
            if (!empty($data['gift_points'])) {
                $gifts[] = [
                    'gift_type' => 'points',
                    'points' => (int)$data['gift_points']
                ];
            }
            $data['gifts'] = $gifts;

            $id = $this->service->create($data);
            $response->redirect('/admin/promotions?success=' . urlencode('Kampanya kaydı başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/promotions/create?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Kampanya düzenleme formu
     */
    public function showEdit(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $promotion = $this->repository->getById($id);

        if (!$promotion) {
            $response->redirect('/admin/promotions?error=' . urlencode('Kampanya bulunamadı.'));
            exit;
        }

        $conditions = $this->repository->getConditions($id);
        $actions = $this->repository->getActions($id);
        $gifts = $this->repository->getGifts($id);

        // İlişkili ID'ler
        $productsSelected = array_column($this->db->query("SELECT product_id FROM promotion_products WHERE promotion_id = :pid", [':pid' => $id]), 'product_id');
        $categoriesSelected = array_column($this->db->query("SELECT category_id FROM promotion_categories WHERE promotion_id = :pid", [':pid' => $id]), 'category_id');
        $brandsSelected = array_column($this->db->query("SELECT brand_id FROM promotion_brands WHERE promotion_id = :pid", [':pid' => $id]), 'brand_id');
        $groupsSelected = array_column($this->db->query("SELECT customer_group_id FROM promotion_customer_groups WHERE promotion_id = :pid", [':pid' => $id]), 'customer_group_id');
        $segmentsSelected = array_column($this->db->query("SELECT segment_id FROM promotion_segments WHERE promotion_id = :pid", [':pid' => $id]), 'segment_id');

        // Bütün listeler
        $products = $this->db->query("SELECT id, sku FROM products WHERE deleted_at IS NULL");
        $categories = $this->db->query("SELECT c.id, ct.name FROM categories c JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1 WHERE c.deleted_at IS NULL");
        $brands = $this->db->query("SELECT b.id, bt.name FROM brands b JOIN brand_translations bt ON b.id = bt.brand_id AND bt.language_id = 1 WHERE b.deleted_at IS NULL");
        $groups = $this->customerRepository->getGroups();
        $segments = $this->customerRepository->getSegments();

        return $this->render('admin/promotions/edit', [
            'promotion' => $promotion,
            'conditions' => $conditions,
            'actions' => $actions,
            'gifts' => $gifts,
            'productsSelected' => $productsSelected,
            'categoriesSelected' => $categoriesSelected,
            'brandsSelected' => $brandsSelected,
            'groupsSelected' => $groupsSelected,
            'segmentsSelected' => $segmentsSelected,
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'groups' => $groups,
            'segments' => $segments
        ]);
    }

    /**
     * Kampanya güncelleme
     */
    public function update(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $data = $request->post();
            
            // Koşul verilerini topla
            $conditions = [];
            if (!empty($data['rule_min_cart'])) {
                $conditions[] = ['rule_type' => 'min_cart', 'operator' => '>=', 'value' => $data['rule_min_cart'], 'group_operator' => 'AND'];
            }
            if (!empty($data['rule_max_cart'])) {
                $conditions[] = ['rule_type' => 'max_cart', 'operator' => '<=', 'value' => $data['rule_max_cart'], 'group_operator' => 'AND'];
            }
            if (!empty($data['rule_min_items'])) {
                $conditions[] = ['rule_type' => 'min_items', 'operator' => '>=', 'value' => $data['rule_min_items'], 'group_operator' => 'AND'];
            }
            if (!empty($data['rule_day_of_week'])) {
                $conditions[] = ['rule_type' => 'day_of_week', 'operator' => 'IN', 'value' => implode(',', $data['rule_day_of_week']), 'group_operator' => 'AND'];
            }
            $data['conditions'] = $conditions;

            // Aksiyon verilerini topla
            $actions = [];
            $actions[] = [
                'type' => $data['action_type'],
                'amount' => (float)($data['action_amount'] ?? 0),
                'target_type' => $data['action_target'] ?? 'cart',
                'target_ids' => !empty($data['action_target_ids']) ? implode(',', $data['action_target_ids']) : null
            ];
            $data['actions'] = $actions;

            // Hediye verilerini topla
            $gifts = [];
            if (!empty($data['gift_product_id'])) {
                $gifts[] = [
                    'gift_type' => 'product',
                    'target_id' => (int)$data['gift_product_id'],
                    'quantity' => (int)($data['gift_qty'] ?? 1)
                ];
            }
            if (!empty($data['gift_points'])) {
                $gifts[] = [
                    'gift_type' => 'points',
                    'points' => (int)$data['gift_points']
                ];
            }
            $data['gifts'] = $gifts;

            $this->service->update($id, $data);
            $response->redirect('/admin/promotions?success=' . urlencode('Kampanya kaydı güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/promotions/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Kampanyayı kopyalar
     */
    public function duplicate(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $newId = $this->service->duplicate($id);
            $response->redirect('/admin/promotions?success=' . urlencode('Kampanya kopyalandı. Taslak olarak kaydedildi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/promotions?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Kampanya silme (Soft delete)
     */
    public function delete(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->repository->delete($id);
            $response->redirect('/admin/promotions?success=' . urlencode('Kampanya çöp kutusuna taşındı.'));
        } catch (Exception $e) {
            $response->redirect('/admin/promotions?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Geri yükleme
     */
    public function restore(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->repository->restore($id);
            $response->redirect('/admin/promotions?success=' . urlencode('Kampanya başarıyla geri yüklendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/promotions?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Kalıcı silme
     */
    public function forceDelete(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->repository->forceDelete($id);
            $response->redirect('/admin/promotions?success=' . urlencode('Kampanya kalıcı olarak silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/promotions?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Kuponlar yönetim ekranı
     */
    public function indexCoupons(Request $request, Response $response): string {
        $coupons = $this->db->query("SELECT pc.*, pt.name as promotion_name FROM promotion_coupons pc JOIN promotion_translations pt ON pc.promotion_id = pt.promotion_id AND pt.language_id = 1 WHERE pc.deleted_at IS NULL ORDER BY pc.id DESC");
        $promotions = $this->repository->getAll();

        return $this->render('admin/coupons/index', [
            'coupons' => $coupons,
            'promotions' => $promotions
        ]);
    }

    /**
     * Yeni kupon kaydetme
     */
    public function storeCoupon(Request $request, Response $response): void {
        try {
            $data = $request->post();
            
            // Otomatik rastgele kod üretimi
            if ($request->post('auto_code') == '1' || empty($data['code'])) {
                $data['code'] = 'SM-' . strtoupper(substr(md5((string)time() . rand()), 0, 8));
            }

            $this->repository->createCoupon($data);
            $response->redirect('/admin/coupons?success=' . urlencode('Kupon kodu başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/coupons?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Kupon silme
     */
    public function deleteCoupon(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->db->execute("UPDATE promotion_coupons SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);
            $response->redirect('/admin/coupons?success=' . urlencode('Kupon başarıyla silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/coupons?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Kampanya Takvim Görünümü (Calendar view)
     */
    public function calendar(Request $request, Response $response): string {
        $promotions = $this->repository->getAll();
        return $this->render('admin/promotions/calendar', [
            'promotions' => $promotions
        ]);
    }

    /**
     * Raporlama ekranı
     */
    public function reports(Request $request, Response $response): string {
        $reports = $this->repository->getReports();
        
        $totalDiscount = 0.0;
        $totalRevenue = 0.0;
        foreach ($reports as $r) {
            $totalDiscount += (float)($r['total_discount_given'] ?? 0.0);
            $totalRevenue += (float)($r['total_revenue_generated'] ?? 0.0);
        }

        $avgRoi = $totalDiscount > 0 ? ($totalRevenue / $totalDiscount) * 100 : 0.0;

        return $this->render('admin/promotions/reports', [
            'reports' => $reports,
            'totalDiscount' => $totalDiscount,
            'totalRevenue' => $totalRevenue,
            'avgRoi' => $avgRoi
        ]);
    }

    /**
     * Simülatör ve Önizleme
     */
    public function preview(Request $request, Response $response): string {
        $products = $this->db->query("SELECT id, sku, price FROM products WHERE deleted_at IS NULL LIMIT 20");
        $customers = $this->customerRepository->getAll();

        return $this->render('admin/promotions/preview', [
            'products' => $products,
            'customers' => $customers
        ]);
    }

    /**
     * Toplu İşlemler
     */
    public function bulk(Request $request, Response $response): void {
        $action = $request->post('action') ?? '';
        $ids = $request->post('promotion_ids') ?? [];

        if (empty($ids)) {
            $response->redirect('/admin/promotions?error=' . urlencode('Hiçbir kampanya seçilmedi.'));
            return;
        }

        try {
            $this->db->beginTransaction();

            if ($action === 'delete') {
                foreach ($ids as $id) {
                    $this->repository->delete((int)$id);
                }
            } elseif ($action === 'status') {
                $targetStatus = $request->post('target_status') ?? '';
                if ($targetStatus !== '') {
                    foreach ($ids as $id) {
                        $this->db->execute("UPDATE promotions SET status = :status WHERE id = :id", [':status' => $targetStatus, ':id' => (int)$id]);
                    }
                }
            }

            $this->db->commit();
            $this->service->clearCache();
            $response->redirect('/admin/promotions?success=' . urlencode('Toplu işlem başarıyla uygulandı.'));
        } catch (Exception $e) {
            $this->db->rollBack();
            $response->redirect('/admin/promotions?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * CSV ve Excel Dışa Aktarma
     */
    public function export(Request $request, Response $response): void {
        $format = $request->get('format') ?? 'csv';
        $promotions = $this->repository->getAll();

        if ($format === 'excel') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename=promotions_export_' . date('Ymd_His') . '.xls');
            echo "<table border='1'>
                    <tr>
                        <th>Kampanya</th>
                        <th>Tür</th>
                        <th>Kod</th>
                        <th>Durum</th>
                        <th>Öncelik</th>
                        <th>Başlangıç Tarihi</th>
                        <th>Bitiş Tarihi</th>
                    </tr>";
            foreach ($promotions as $p) {
                echo "<tr>
                        <td>" . htmlspecialchars($p['name']) . "</td>
                        <td>" . htmlspecialchars($p['type']) . "</td>
                        <td>" . htmlspecialchars($p['code'] ?? '-') . "</td>
                        <td>" . htmlspecialchars($p['status']) . "</td>
                        <td>{$p['priority']}</td>
                        <td>{$p['start_date']}</td>
                        <td>{$p['end_date']}</td>
                      </tr>";
            }
            echo "</table>";
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=promotions_export_' . date('Ymd_His') . '.csv');
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['ID', 'Kampanya Adı', 'Tür', 'Kod', 'Durum', 'Öncelik', 'Başlangıç', 'Bitiş']);
            foreach ($promotions as $p) {
                fputcsv($out, [
                    $p['id'],
                    $p['name'],
                    $p['type'],
                    $p['code'] ?? '-',
                    $p['status'],
                    $p['priority'],
                    $p['start_date'],
                    $p['end_date']
                ]);
            }
            fclose($out);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // REST API ENDPOINTS
    // ─────────────────────────────────────────────────────────────

    public function apiIndex(Request $request, Response $response): void {
        try {
            $promotions = $this->repository->getAll();
            $response->json(['success' => true, 'data' => $promotions]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiShow(Request $request, Response $response): void {
        $path = $request->getUri();
        $parts = explode('/', trim($path, '/'));
        $id = (int)end($parts);

        try {
            $promotion = $this->repository->getById($id);
            if (!$promotion) {
                throw new Exception("Kampanya bulunamadı.", 404);
            }
            $promotion['conditions'] = $this->repository->getConditions($id);
            $promotion['actions'] = $this->repository->getActions($id);
            
            $response->json(['success' => true, 'data' => $promotion]);
        } catch (Exception $e) {
            $code = $e->getCode() === 404 ? 404 : 500;
            $response->json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    public function apiCalculate(Request $request, Response $response): void {
        try {
            $cart = $request->post('cart') ?? [];
            $customerId = (int)$request->post('customer_id');
            $couponCode = $request->post('coupon_code');

            $customer = null;
            if ($customerId > 0) {
                $customer = $this->customerRepository->getById($customerId);
            }

            $result = $this->service->calculate($cart, $customer, $couponCode);
            $response->json(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiPreview(Request $request, Response $response): void {
        $this->apiCalculate($request, $response);
    }

    public function apiCoupons(Request $request, Response $response): void {
        try {
            $coupons = $this->db->query("SELECT * FROM promotion_coupons WHERE deleted_at IS NULL");
            $response->json(['success' => true, 'data' => $coupons]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiCouponsValidate(Request $request, Response $response): void {
        try {
            $code = $request->post('code') ?? '';
            $cart = $request->post('cart') ?? [];
            $customerId = (int)$request->post('customer_id');

            $customer = null;
            if ($customerId > 0) {
                $customer = $this->customerRepository->getById($customerId);
            }

            $res = $this->service->validateCoupon($code, $cart, $customer);
            $response->json($res);
        } catch (Exception $e) {
            $response->json(['valid' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiCouponsHistory(Request $request, Response $response): void {
        try {
            $usages = $this->db->query(
                "SELECT pcu.*, pc.code, u.email as customer_email 
                 FROM promotion_coupon_usages pcu
                 JOIN promotion_coupons pc ON pcu.coupon_id = pc.id
                 JOIN users u ON pcu.user_id = u.id
                 ORDER BY pcu.id DESC"
            );
            $response->json(['success' => true, 'data' => $usages]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
