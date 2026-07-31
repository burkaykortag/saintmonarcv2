<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\CustomerService;
use App\Repositories\CustomerRepository;
use Core\Contracts\DatabaseInterface;
use Core\View\View;
use Exception;

class CustomerController extends Controller {
    private CustomerService $service;
    private CustomerRepository $repository;
    private DatabaseInterface $db;

    public function __construct(
        View $view,
        CustomerService $service,
        CustomerRepository $repository,
        DatabaseInterface $db
    ) {
        parent::__construct($view);
        $this->service = $service;
        $this->repository = $repository;
        $this->db = $db;
    }

    /**
     * Müşteri listesi ve gelişmiş filtreler
     */
    public function index(Request $request, Response $response): string {
        $filters = [];
        $keys = ['search', 'customer_group_id', 'status', 'kvkk_consent', 'min_spent', 'max_spent', 'min_orders', 'segment_id', 'tag_id', 'city'];
        foreach ($keys as $key) {
            if ($request->get($key) !== null && $request->get($key) !== '') {
                $filters[$key] = $request->get($key);
            }
        }

        $customers = $this->repository->getAll($filters, false);
        $trash = $this->repository->getAll($filters, true);
        
        $groups = $this->repository->getGroups();
        $segments = $this->repository->getSegments();
        $tags = $this->repository->getTags();

        $viewMode = $request->get('view') === 'card' ? 'card' : 'list';

        return $this->render('admin/customers/index', [
            'customers' => $customers,
            'trash' => $trash,
            'groups' => $groups,
            'segments' => $segments,
            'tags' => $tags,
            'filters' => $filters,
            'viewMode' => $viewMode
        ]);
    }

    /**
     * Müşteri detay sayfası
     */
    public function show(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $customer = $this->repository->getById($id, true);

        if (!$customer) {
            $response->redirect('/admin/customers?error=' . urlencode('Müşteri bulunamadı.'));
            exit;
        }

        $addresses = $this->repository->getAddresses($id);
        $notes = $this->repository->getNotes($id);
        $loginHistory = $this->repository->getLoginHistory($id);
        $wallet = $this->repository->getWallet($id);
        $walletTransactions = $this->repository->getWalletTransactions($id);
        $rewardHistory = $this->repository->getRewardPointHistory($id);
        $documents = $this->repository->getDocuments($id);
        $activities = $this->repository->getActivityLogs($id);
        $customerTags = $this->repository->getCustomerTags($id);
        $groups = $this->repository->getGroups();

        // Müşteri sipariş geçmişini orders tablosundan topla
        $orders = $this->db->query(
            "SELECT o.*, os.name as status_name, os.color as status_color 
             FROM orders o
             LEFT JOIN order_statuses os ON o.status = os.code
             WHERE o.user_id = (SELECT id FROM users WHERE email = :email LIMIT 1) AND o.deleted_at IS NULL ORDER BY o.id DESC",
            [':email' => $customer['email']]
        );

        // Müşteri iadelerini refunds tablosundan topla
        $refunds = $this->db->query(
            "SELECT r.*, o.order_number 
             FROM refunds r
             JOIN orders o ON r.order_id = o.id
             WHERE o.user_id = (SELECT id FROM users WHERE email = :email LIMIT 1) ORDER BY r.id DESC",
            [':email' => $customer['email']]
        );

        // Müşteri destek talepleri (CMS/Ticket entegrasyonu varsa çeker)
        $tickets = $this->db->query(
            "SELECT * FROM ai_conversations WHERE user_id = (SELECT id FROM users WHERE email = :email LIMIT 1) ORDER BY id DESC LIMIT 5",
            [':email' => $customer['email']]
        );

        $tags = $this->repository->getTags();

        // Log view
        $auditLogger = \Core\Application::getInstance()->getContainer()->get(\App\Services\AuditLogger::class);
        $auditLogger->logActivity('customer_view', "Müşteri kartı görüntülendi: " . trim($customer['first_name'] . ' ' . $customer['last_name']) . " (ID: {$id})");

        return $this->render('admin/customers/show', [
            'customer' => $customer,
            'addresses' => $addresses,
            'notes' => $notes,
            'loginHistory' => $loginHistory,
            'wallet' => $wallet,
            'walletTransactions' => $walletTransactions,
            'rewardHistory' => $rewardHistory,
            'documents' => $documents,
            'activities' => $activities,
            'customerTags' => $customerTags,
            'groups' => $groups,
            'orders' => $orders,
            'refunds' => $refunds,
            'tickets' => $tickets,
            'tags' => $tags
        ]);
    }

    /**
     * Müşteri oluşturma formu
     */
    public function showCreate(Request $request, Response $response): string {
        $groups = $this->repository->getGroups();
        $tags = $this->repository->getTags();

        return $this->render('admin/customers/create', [
            'groups' => $groups,
            'tags' => $tags
        ]);
    }

    /**
     * Müşteri kaydetme
     */
    public function store(Request $request, Response $response): void {
        try {
            $data = $request->post();
            
            // Avatar yükleme (Opsiyonel)
            if (!empty($_FILES['avatar']['tmp_name'])) {
                $avatarPath = '/uploads/avatars/' . time() . '_' . $_FILES['avatar']['name'];
                @mkdir(dirname(dirname(__DIR__)) . '/public/uploads/avatars', 0755, true);
                if (@move_uploaded_file($_FILES['avatar']['tmp_name'], dirname(dirname(__DIR__)) . '/public' . $avatarPath)) {
                    $data['avatar'] = $avatarPath;
                }
            }

            $id = $this->service->create($data);
            $response->redirect('/admin/customers/show?id=' . $id . '&success=' . urlencode('Müşteri kaydı başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers/create?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Müşteri düzenleme sayfası
     */
    public function showEdit(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $customer = $this->repository->getById($id);

        if (!$customer) {
            $response->redirect('/admin/customers?error=' . urlencode('Müşteri bulunamadı.'));
            exit;
        }

        $groups = $this->repository->getGroups();
        $tags = $this->repository->getTags();
        $customerTags = array_column($this->repository->getCustomerTags($id), 'id');

        return $this->render('admin/customers/edit', [
            'customer' => $customer,
            'groups' => $groups,
            'tags' => $tags,
            'customerTags' => $customerTags
        ]);
    }

    /**
     * Müşteri güncelleme
     */
    public function update(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $data = $request->post();
            
            // Avatar yükleme
            if (!empty($_FILES['avatar']['tmp_name'])) {
                $avatarPath = '/uploads/avatars/' . time() . '_' . $_FILES['avatar']['name'];
                @mkdir(dirname(dirname(__DIR__)) . '/public/uploads/avatars', 0755, true);
                if (@move_uploaded_file($_FILES['avatar']['tmp_name'], dirname(dirname(__DIR__)) . '/public' . $avatarPath)) {
                    $data['avatar'] = $avatarPath;
                }
            }

            $this->service->update($id, $data);
            $response->redirect('/admin/customers/show?id=' . $id . '&success=' . urlencode('Müşteri bilgileri güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Müşteri silme (Soft delete)
     */
    public function delete(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->repository->delete($id);
            $response->redirect('/admin/customers?success=' . urlencode('Müşteri başarıyla silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Müşteri geri yükleme
     */
    public function restore(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->repository->restore($id);
            $response->redirect('/admin/customers?success=' . urlencode('Müşteri başarıyla geri yüklendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Kalıcı silme
     */
    public function forceDelete(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->repository->forceDelete($id);
            $response->redirect('/admin/customers?success=' . urlencode('Müşteri kaydı kalıcı olarak silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Müşteri grupları yönetim ekranı
     */
    public function indexGroups(Request $request, Response $response): string {
        $groups = $this->repository->getGroups();
        return $this->render('admin/customers/groups', [
            'groups' => $groups
        ]);
    }

    /**
     * Müşteri grubu ekleme
     */
    public function storeGroup(Request $request, Response $response): void {
        try {
            $name = $request->post('name') ?? '';
            $rate = (float)($request->post('discount_rate') ?? 0.0);
            if (trim($name) === '') {
                throw new Exception("Grup adı boş olamaz.");
            }
            $this->db->execute(
                "INSERT INTO customer_groups (name, discount_rate, created_at, updated_at) VALUES (:name, :rate, NOW(), NOW())",
                [':name' => trim($name), ':rate' => $rate]
            );
            $response->redirect('/admin/customers/groups?success=' . urlencode('Grup başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers/groups?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Müşteri grubu düzenleme
     */
    public function updateGroup(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $name = $request->post('name') ?? '';
            $rate = (float)($request->post('discount_rate') ?? 0.0);
            if (trim($name) === '') {
                throw new Exception("Grup adı boş olamaz.");
            }
            $this->db->execute(
                "UPDATE customer_groups SET name = :name, discount_rate = :rate, updated_at = NOW() WHERE id = :id",
                [':name' => trim($name), ':rate' => $rate, ':id' => $id]
            );
            $response->redirect('/admin/customers/groups?success=' . urlencode('Grup başarıyla güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers/groups?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Müşteri grubu silme
     */
    public function deleteGroup(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            if ($id <= 1) {
                throw new Exception("Varsayılan grup silinemez.");
            }
            $this->db->execute("DELETE FROM customer_groups WHERE id = :id", [':id' => $id]);
            $response->redirect('/admin/customers/groups?success=' . urlencode('Grup başarıyla silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers/groups?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Segmentasyon yönetim ekranı
     */
    public function indexSegments(Request $request, Response $response): string {
        $segments = $this->repository->getSegments();
        return $this->render('admin/customers/segments', [
            'segments' => $segments
        ]);
    }

    /**
     * Yeni segment oluşturma
     */
    public function storeSegment(Request $request, Response $response): void {
        try {
            $name = $request->post('name') ?? '';
            $desc = $request->post('description') ?? '';
            
            // Kural oluşturma
            $rules = [];
            if ($request->post('rule_days_since_last_order') !== '') {
                $rules['days_since_last_order'] = (int)$request->post('rule_days_since_last_order');
            }
            if ($request->post('rule_min_total_spent') !== '') {
                $rules['min_total_spent'] = (float)$request->post('rule_min_total_spent');
            }
            if ($request->post('rule_min_orders_count') !== '') {
                $rules['min_orders_count'] = (int)$request->post('rule_min_orders_count');
            }
            if ($request->post('rule_orders_count') !== '') {
                $rules['orders_count'] = (int)$request->post('rule_orders_count');
            }

            if (empty($rules)) {
                throw new Exception("Segment için en az 1 kural tanımlamalısınız.");
            }

            $rulesJson = json_encode($rules);

            $this->db->execute(
                "INSERT INTO customer_segments (name, description, rules, created_at) VALUES (:name, :desc, :rules, NOW())",
                [':name' => trim($name), ':desc' => trim($desc), ':rules' => $rulesJson]
            );

            // Segmentasyonu tetikle
            $this->service->runSegmentationEngine();

            $response->redirect('/admin/customers/segments?success=' . urlencode('Dinamik segment oluşturuldu ve uygulandı.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers/segments?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Segment silme
     */
    public function deleteSegment(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->db->execute("DELETE FROM customer_segments WHERE id = :id", [':id' => $id]);
            $response->redirect('/admin/customers/segments?success=' . urlencode('Segment başarıyla silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers/segments?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Cüzdan Bakiye İşlemleri (Yükleme / Çekme)
     */
    public function handleWallet(Request $request, Response $response): void {
        $customerId = (int)$request->post('customer_id');
        $amount = (float)$request->post('amount');
        $type = $request->post('type') ?? 'deposit';
        $desc = $request->post('description') ?? 'Yönetici işlemi';

        try {
            if ($type === 'deposit') {
                $this->service->depositWallet($customerId, $amount, $desc);
            } else {
                $this->service->withdrawWallet($customerId, $amount, $desc);
            }
            $response->redirect('/admin/customers/show?id=' . $customerId . '&success=' . urlencode('Cüzdan işlemi başarıyla uygulandı.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers/show?id=' . $customerId . '&error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Sadakat Puan İşlemleri
     */
    public function handleReward(Request $request, Response $response): void {
        $customerId = (int)$request->post('customer_id');
        $points = (int)$request->post('points');
        $type = $request->post('type') ?? 'add';
        $desc = $request->post('description') ?? 'Yönetici puan işlemi';

        try {
            if ($type === 'add') {
                $this->service->addRewardPoints($customerId, $points, $desc);
            } else {
                $this->service->spendRewardPoints($customerId, $points, $desc);
            }
            $response->redirect('/admin/customers/show?id=' . $customerId . '&success=' . urlencode('Puan işlemi başarıyla uygulandı.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers/show?id=' . $customerId . '&error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Müşteri Notu Ekleme
     */
    public function addNote(Request $request, Response $response): void {
        $customerId = (int)$request->post('customer_id');
        $note = $request->post('note') ?? '';

        try {
            if (trim($note) === '') {
                throw new Exception("Not boş olamaz.");
            }
            $this->service->addNote($customerId, $note);
            $response->redirect('/admin/customers/show?id=' . $customerId . '&success=' . urlencode('Not başarıyla eklendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers/show?id=' . $customerId . '&error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Müşteri Dökümanı Yükleme
     */
    public function uploadDocument(Request $request, Response $response): void {
        $customerId = (int)$request->post('customer_id');
        $name = $request->post('name') ?? 'Müşteri Belgesi';

        try {
            if (empty($_FILES['document']['tmp_name'])) {
                throw new Exception("Yüklenecek belge seçilmedi.");
            }

            $docPath = '/uploads/documents/' . time() . '_' . $_FILES['document']['name'];
            @mkdir(dirname(dirname(__DIR__)) . '/public/uploads/documents', 0755, true);
            
            if (@move_uploaded_file($_FILES['document']['tmp_name'], dirname(dirname(__DIR__)) . '/public' . $docPath)) {
                $this->db->execute(
                    "INSERT INTO customer_documents (customer_id, name, file_path, file_size, created_at) 
                     VALUES (:cid, :name, :path, :size, NOW())",
                    [
                        ':cid' => $customerId,
                        ':name' => trim($name),
                        ':path' => $docPath,
                        ':size' => (int)$_FILES['document']['size']
                    ]
                );
                $this->service->logActivity($customerId, 'document_upload', "Müşteri belgesi yüklendi: " . trim($name));
                $response->redirect('/admin/customers/show?id=' . $customerId . '&success=' . urlencode('Belge başarıyla yüklendi.'));
            } else {
                throw new Exception("Dosya yükleme hatası.");
            }
        } catch (Exception $e) {
            $response->redirect('/admin/customers/show?id=' . $customerId . '&error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Müşteri Adresi Ekleme
     */
    public function addAddress(Request $request, Response $response): void {
        $customerId = (int)$request->post('customer_id');
        try {
            $data = $request->post();
            $this->service->addAddress($customerId, $data);
            $response->redirect('/admin/customers/show?id=' . $customerId . '&success=' . urlencode('Adres başarıyla kaydedildi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/customers/show?id=' . $customerId . '&error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Toplu İşlemler
     */
    public function bulk(Request $request, Response $response): void {
        $action = $request->post('action') ?? '';
        $ids = $request->post('customer_ids') ?? [];

        if (empty($ids)) {
            $response->redirect('/admin/customers?error=' . urlencode('Hiçbir müşteri seçilmedi.'));
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
                        $this->db->execute("UPDATE customers SET status = :status WHERE id = :id", [':status' => $targetStatus, ':id' => (int)$id]);
                    }
                }
            } elseif ($action === 'group') {
                $targetGroupId = $request->post('target_group_id') ?? '';
                if ($targetGroupId !== '') {
                    foreach ($ids as $id) {
                        $this->db->execute("UPDATE customers SET customer_group_id = :gid WHERE id = :id", [':gid' => (int)$targetGroupId, ':id' => (int)$id]);
                    }
                }
            }

            $this->db->commit();
            $this->service->clearCache();
            $response->redirect('/admin/customers?success=' . urlencode('Toplu işlem başarıyla uygulandı.'));
        } catch (Exception $e) {
            $this->db->rollBack();
            $response->redirect('/admin/customers?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Dışa Aktarma (Excel, CSV)
     */
    public function export(Request $request, Response $response): void {
        $format = $request->get('format') ?? 'csv';
        $customers = $this->repository->getAll();

        if ($format === 'excel') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename=customers_export_' . date('Ymd_His') . '.xls');
            
            echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
            echo "<table border='1'>
                    <tr>
                        <th>Müşteri</th>
                        <th>E-Posta</th>
                        <th>Telefon</th>
                        <th>Grup</th>
                        <th>Durum</th>
                        <th>Toplam Harcama</th>
                        <th>Cüzdan Bakiyesi</th>
                        <th>Puanlar</th>
                        <th>Kayıt Tarihi</th>
                    </tr>";
            foreach ($customers as $c) {
                echo "<tr>
                        <td>" . htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) . "</td>
                        <td>" . htmlspecialchars($c['email']) . "</td>
                        <td>" . htmlspecialchars($c['phone'] ?? '-') . "</td>
                        <td>" . htmlspecialchars($c['group_name'] ?? 'Perakende') . "</td>
                        <td>" . htmlspecialchars($c['status']) . "</td>
                        <td>{$c['total_spent']} TRY</td>
                        <td>{$c['wallet_balance']} TRY</td>
                        <td>{$c['total_points']}</td>
                        <td>{$c['created_at']}</td>
                      </tr>";
            }
            echo "</table>";
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=customers_export_' . date('Ymd_His') . '.csv');
            
            $out = fopen('php://output', 'w');
            // BOM for UTF-8 compatibility in Excel
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($out, ['ID', 'Adı', 'Soyadı', 'E-Posta', 'Telefon', 'Grup', 'Durum', 'Toplam Harcama', 'Cüzdan Bakiyesi', 'Sadakat Puanı', 'Kayıt Tarihi']);
            foreach ($customers as $c) {
                fputcsv($out, [
                    $c['id'],
                    $c['first_name'],
                    $c['last_name'],
                    $c['email'],
                    $c['phone'] ?? '-',
                    $c['group_name'] ?? 'Perakende',
                    $c['status'],
                    $c['total_spent'],
                    $c['wallet_balance'] ?? 0.00,
                    $c['total_points'] ?? 0,
                    $c['created_at']
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
            $customers = $this->repository->getAll();
            $response->json(['success' => true, 'data' => $customers]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiShow(Request $request, Response $response): void {
        $path = $request->getUri();
        $parts = explode('/', trim($path, '/'));
        $id = (int)end($parts);

        try {
            $customer = $this->repository->getById($id);
            if (!$customer) {
                throw new Exception("Müşteri bulunamadı.", 404);
            }
            $customer['addresses'] = $this->repository->getAddresses($id);
            $customer['wallet'] = $this->repository->getWallet($id);
            
            $response->json(['success' => true, 'data' => $customer]);
        } catch (Exception $e) {
            $code = $e->getCode() === 404 ? 404 : 500;
            $response->json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    public function apiSearch(Request $request, Response $response): void {
        $q = $request->get('q') ?? '';
        try {
            $customers = $this->repository->getAll(['search' => $q]);
            $response->json(['success' => true, 'data' => $customers]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiSegments(Request $request, Response $response): void {
        try {
            $segments = $this->repository->getSegments();
            $response->json(['success' => true, 'data' => $segments]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiExport(Request $request, Response $response): void {
        try {
            $customers = $this->repository->getAll();
            $response->json(['success' => true, 'data' => $customers]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
