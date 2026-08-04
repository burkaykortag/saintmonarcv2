<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\RbacService;
use Core\Contracts\DatabaseInterface;
use Core\View\View;
use Exception;

class AdminUserController extends Controller {
    private RbacService $rbacService;
    private DatabaseInterface $db;

    public function __construct(View $view, RbacService $rbacService, DatabaseInterface $db) {
        parent::__construct($view);
        $this->rbacService = $rbacService;
        $this->db = $db;
    }

    public function index(Request $request, Response $response): string {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $actorMaxPriority = $this->rbacService->getAdminMaxPriority($actorId);

        $sql = "SELECT a.*, r.id as role_id, r.name as role_name, r.priority as role_priority
                FROM admins a
                LEFT JOIN admin_roles ar ON a.id = ar.admin_id
                LEFT JOIN roles r ON ar.role_id = r.id
                WHERE a.deleted_at IS NULL
                ORDER BY r.priority DESC, a.id ASC";
        
        $users = $this->db->query($sql);

        // Append permissions for UI display
        foreach ($users as &$u) {
            $u['can_manage'] = $this->rbacService->canManageAdmin($actorId, (int)$u['id']);
            $u['can_impersonate'] = $this->rbacService->canImpersonate($actorId, (int)$u['id']);
        }
        unset($u);

        return $this->render('admin/users/index', [
            'users' => $users,
            'actorMaxPriority' => $actorMaxPriority
        ]);
    }

    public function showCreate(Request $request, Response $response): string {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $actorMaxPriority = $this->rbacService->getAdminMaxPriority($actorId);

        // Get roles that actor is allowed to assign (priority < actor max priority)
        $roles = $this->db->query(
            "SELECT * FROM roles WHERE is_active = 1 AND priority < :prio ORDER BY priority DESC",
            [':prio' => $actorMaxPriority]
        );

        return $this->render('admin/users/create', ['roles' => $roles]);
    }

    public function store(Request $request, Response $response): void {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $username = trim((string)$request->post('username'));
        $email = trim((string)$request->post('email'));
        $password = (string)$request->post('password');
        $roleId = (int)$request->post('role_id');

        if (empty($username) || empty($email) || empty($password)) {
            $response->redirect('/admin/users/create?error=' . urlencode('Lütfen tüm zorunlu alanları doldurun.'));
            return;
        }

        // Validate role assignment hierarchy
        if (!$this->rbacService->canManageRole($actorId, $roleId)) {
            $response->redirect('/admin/users/create?error=' . urlencode('Bu rolü atama yetkiniz bulunmuyor. (Sadece kendi seviyenizin altındaki rolleri atayabilirsiniz)'));
            return;
        }

        try {
            $this->db->beginTransaction();

            $hash = password_hash($password, PASSWORD_ARGON2ID);
            $this->db->execute(
                "INSERT INTO admins (username, email, password, is_super, is_active, is_impersonatable, created_at) VALUES (:u, :e, :p, 0, 1, 1, NOW())",
                [':u' => $username, ':e' => $email, ':p' => $hash]
            );
            $newAdminId = (int)$this->db->lastInsertId();

            $this->db->execute(
                "INSERT INTO admin_roles (admin_id, role_id) VALUES (:aid, :rid)",
                [':aid' => $newAdminId, ':rid' => $roleId]
            );

            $this->rbacService->logAudit($actorId, 'ADMIN_USER_CREATED', 'Admin', $newAdminId, [], ['username' => $username, 'role_id' => $roleId]);

            $this->db->commit();
            $response->redirect('/admin/users?success=' . urlencode('Yönetici kullanıcısı başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $this->db->rollBack();
            $response->redirect('/admin/users/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function showEdit(Request $request, Response $response): string {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $targetId = (int)($request->get('id') ?? 0);

        if (!$this->rbacService->canManageAdmin($actorId, $targetId)) {
            $response->redirect('/admin/users?error=' . urlencode('Bu kullanıcıyı düzenleme yetkiniz yok. (Eşit veya üst seviyedeki hesaplar düzenlenemez)'));
        }

        $user = $this->db->query("SELECT * FROM admins WHERE id = :id LIMIT 1", [':id' => $targetId]);
        if (empty($user)) {
            $response->redirect('/admin/users?error=' . urlencode('Kullanıcı bulunamadı.'));
        }

        $actorMaxPriority = $this->rbacService->getAdminMaxPriority($actorId);
        $roles = $this->db->query("SELECT * FROM roles WHERE is_active = 1 AND priority < :prio ORDER BY priority DESC", [':prio' => $actorMaxPriority]);

        $assignedRole = $this->db->query("SELECT role_id FROM admin_roles WHERE admin_id = :aid LIMIT 1", [':aid' => $targetId]);
        $assignedRoleId = $assignedRole[0]['role_id'] ?? null;

        return $this->render('admin/users/edit', [
            'user' => $user[0],
            'roles' => $roles,
            'assignedRoleId' => $assignedRoleId
        ]);
    }

    public function update(Request $request, Response $response): void {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $targetId = (int)($request->post('id') ?? 0);
        $username = trim((string)$request->post('username'));
        $email = trim((string)$request->post('email'));
        $roleId = (int)$request->post('role_id');
        $isActive = (int)($request->post('is_active') ?? 1);

        if (!$this->rbacService->canManageAdmin($actorId, $targetId)) {
            $response->redirect('/admin/users?error=' . urlencode('Yetkisiz işlem. Bu kullanıcıyı güncelleyemezsiniz.'));
            return;
        }

        if ($roleId > 0 && !$this->rbacService->canManageRole($actorId, $roleId)) {
            $response->redirect('/admin/users?error=' . urlencode('Bu rolü atama yetkiniz bulunmuyor.'));
            return;
        }

        try {
            $this->db->beginTransaction();

            $this->db->execute(
                "UPDATE admins SET username = :u, email = :e, is_active = :act WHERE id = :id",
                [':u' => $username, ':e' => $email, ':act' => $isActive, ':id' => $targetId]
            );

            if ($roleId > 0) {
                $this->db->execute("DELETE FROM admin_roles WHERE admin_id = :aid", [':aid' => $targetId]);
                $this->db->execute("INSERT INTO admin_roles (admin_id, role_id) VALUES (:aid, :rid)", [':aid' => $targetId, ':rid' => $roleId]);
            }

            $this->rbacService->clearPermissionsCache(null, $targetId);
            $this->rbacService->logAudit($actorId, 'ADMIN_USER_UPDATED', 'Admin', $targetId, [], ['username' => $username, 'role_id' => $roleId]);

            $this->db->commit();
            $response->redirect('/admin/users?success=' . urlencode('Kullanıcı başarıyla güncellendi.'));
        } catch (Exception $e) {
            $this->db->rollBack();
            $response->redirect('/admin/users?error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(Request $request, Response $response): void {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $targetId = (int)($request->post('id') ?? 0);

        if (!$this->rbacService->canManageAdmin($actorId, $targetId) || $actorId === $targetId) {
            $response->redirect('/admin/users?error=' . urlencode('Bu kullanıcıyı silme yetkiniz bulunmuyor.'));
            return;
        }

        $target = $this->db->query("SELECT is_super FROM admins WHERE id = :id LIMIT 1", [':id' => $targetId]);
        if (!empty($target) && (bool)$target[0]['is_super']) {
            $response->redirect('/admin/users?error=' . urlencode('Süper yönetici hesabı silinemez.'));
            return;
        }

        $this->db->execute("UPDATE admins SET deleted_at = NOW(), is_active = 0 WHERE id = :id", [':id' => $targetId]);
        $this->rbacService->logAudit($actorId, 'ADMIN_USER_DELETED', 'Admin', $targetId);

        $response->redirect('/admin/users?success=' . urlencode('Kullanıcı başarıyla silindi.'));
    }

    /**
     * Start Impersonation ("Kullanıcıya Geç")
     */
    public function impersonate(Request $request, Response $response): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $targetId = (int)($request->post('id') ?? $request->get('id') ?? 0);

        if (!$this->rbacService->canImpersonate($actorId, $targetId)) {
            $response->redirect('/admin/users?error=' . urlencode('Güvenlik İhlali: Bu kullanıcı hesabına geçiş yapamazsınız. (Yetersiz hiyerarşi veya yasaklı hesap)'));
            return;
        }

        $targetUser = $this->db->query("SELECT id, username, is_super FROM admins WHERE id = :id AND is_active = 1 LIMIT 1", [':id' => $targetId]);
        if (empty($targetUser)) {
            $response->redirect('/admin/users?error=' . urlencode('Hedef kullanıcı bulunamadı veya pasif durumda.'));
            return;
        }
        $targetUser = $targetUser[0];

        // Backup current admin session state
        $originalAdminId = $actorId;
        $originalAdminUsername = $_SESSION['admin_username'] ?? 'Admin';
        $originalIsSuper = $_SESSION['is_super_admin'] ?? false;

        // Set impersonation session structure
        $_SESSION['impersonation'] = [
            'active' => true,
            'original_admin_id' => $originalAdminId,
            'original_admin_username' => $originalAdminUsername,
            'original_is_super' => $originalIsSuper,
            'target_user_id' => (int)$targetUser['id'],
            'target_username' => $targetUser['username'],
            'started_at' => time()
        ];

        // Context switch to target user
        $_SESSION['admin_id'] = (int)$targetUser['id'];
        $_SESSION['admin_username'] = $targetUser['username'];
        $_SESSION['is_super_admin'] = (bool)$targetUser['is_super'];

        // Regenerate session ID to protect against session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }

        // Audit Log
        $this->rbacService->logAudit(
            (int)$targetUser['id'],
            'IMPERSONATION_STARTED',
            'Admin',
            (int)$targetUser['id'],
            [],
            ['target_username' => $targetUser['username']],
            $originalAdminId,
            (int)$targetUser['id']
        );

        $response->redirect('/admin?success=' . urlencode("Kullanıcıya geçiş sağlandı: {$targetUser['username']} hesabındasınız."));
    }

    /**
     * Revert Impersonation (Admin Hesabına Geri Dön)
     */
    public function revertImpersonation(Request $request, Response $response): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['impersonation']['active'])) {
            $response->redirect('/admin');
            return;
        }

        $impData = $_SESSION['impersonation'];
        $originalAdminId = (int)$impData['original_admin_id'];
        $originalUsername = (string)$impData['original_admin_username'];
        $originalIsSuper = (bool)$impData['original_is_super'];
        $targetUserId = (int)$impData['target_user_id'];

        // Audit Log
        $this->rbacService->logAudit(
            $targetUserId,
            'IMPERSONATION_ENDED',
            'Admin',
            $targetUserId,
            [],
            ['reverted_to' => $originalUsername],
            $originalAdminId,
            $targetUserId
        );

        // Restore original admin context
        $_SESSION['admin_id'] = $originalAdminId;
        $_SESSION['admin_username'] = $originalUsername;
        $_SESSION['is_super_admin'] = $originalIsSuper;
        unset($_SESSION['impersonation']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }

        $response->redirect('/admin/users?success=' . urlencode('Admin oturumunuza güvenle geri dönüldü.'));
    }
}
