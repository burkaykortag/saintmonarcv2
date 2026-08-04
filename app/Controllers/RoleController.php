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

class RoleController extends Controller {
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

        $sql = "SELECT r.*, p.name as parent_name,
                       (SELECT COUNT(*) FROM admin_roles ar WHERE ar.role_id = r.id) as user_count
                FROM roles r
                LEFT JOIN roles p ON r.parent_id = p.id
                ORDER BY r.priority DESC, r.id ASC";
        
        $roles = $this->db->query($sql);

        foreach ($roles as &$r) {
            $r['can_manage'] = $this->rbacService->canManageRole($actorId, (int)$r['id']);
        }
        unset($r);

        return $this->render('admin/roles/index', [
            'roles' => $roles,
            'actorMaxPriority' => $actorMaxPriority
        ]);
    }

    public function showCreate(Request $request, Response $response): string {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $actorMaxPriority = $this->rbacService->getAdminMaxPriority($actorId);

        // Allowed parent roles are those with priority <= actorMaxPriority
        $parentRoles = $this->db->query(
            "SELECT * FROM roles WHERE priority <= :prio ORDER BY priority DESC",
            [':prio' => $actorMaxPriority]
        );

        $grantablePermIds = $this->rbacService->getGrantablePermissionIds($actorId);
        $permissions = [];
        if (!empty($grantablePermIds)) {
            $inClause = implode(',', array_map('intval', $grantablePermIds));
            $permissions = $this->db->query("SELECT * FROM permissions WHERE id IN ({$inClause}) ORDER BY id ASC");
        }

        return $this->render('admin/roles/create', [
            'parentRoles' => $parentRoles,
            'permissions' => $permissions,
            'actorMaxPriority' => $actorMaxPriority
        ]);
    }

    public function store(Request $request, Response $response): void {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $actorMaxPriority = $this->rbacService->getAdminMaxPriority($actorId);

        $name = trim((string)$request->post('name'));
        $desc = trim((string)$request->post('description'));
        $parentId = (int)($request->post('parent_id') ?? 0);
        $priority = (int)($request->post('priority') ?? 10);
        $perms = $request->post('permissions') ?? [];

        if (empty($name)) {
            $response->redirect('/admin/roles/create?error=' . urlencode('Rol adı boş bırakılamaz.'));
            return;
        }

        // Boundary Check: Actor cannot create a role with priority >= actorMaxPriority (unless Super/DevAdmin)
        if ($actorMaxPriority < 100 && $priority >= $actorMaxPriority) {
            $response->redirect('/admin/roles/create?error=' . urlencode("Yetki Aşımı Hatası: Kendi seviyenize (Priority: {$actorMaxPriority}) eşit veya daha yüksek seviyede rol oluşturamazsınız."));
            return;
        }

        if ($parentId > 0 && !$this->rbacService->canManageRole($actorId, $parentId)) {
            $response->redirect('/admin/roles/create?error=' . urlencode('Seçilen üst role erişim yetkiniz bulunmuyor.'));
            return;
        }

        try {
            // Validate permission delegation
            $permIds = array_map('intval', (array)$perms);
            $this->rbacService->validatePermissionGrant($actorId, $permIds);

            $this->db->beginTransaction();

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $name), '_'));
            $this->db->execute(
                "INSERT INTO roles (name, slug, description, parent_id, is_active, priority, is_system, created_at)
                 VALUES (:name, :slug, :desc, :parent_id, 1, :priority, 0, NOW())",
                [
                    ':name' => $name,
                    ':slug' => $slug,
                    ':desc' => $desc,
                    ':parent_id' => ($parentId > 0 ? $parentId : null),
                    ':priority' => $priority
                ]
            );
            $newRoleId = (int)$this->db->lastInsertId();

            foreach ($permIds as $pid) {
                $this->db->execute(
                    "INSERT INTO role_permissions (role_id, permission_id) VALUES (:rid, :pid)",
                    [':rid' => $newRoleId, ':pid' => $pid]
                );
            }

            $this->rbacService->logAudit($actorId, 'ROLE_CREATED', 'Role', $newRoleId, [], ['name' => $name, 'priority' => $priority]);

            $this->db->commit();
            $response->redirect('/admin/roles?success=' . urlencode('Rol başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $this->db->rollBack();
            $response->redirect('/admin/roles/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function showEdit(Request $request, Response $response): string {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $roleId = (int)($request->get('id') ?? 0);

        $role = $this->db->query("SELECT * FROM roles WHERE id = :id LIMIT 1", [':id' => $roleId]);
        if (empty($role)) {
            $response->redirect('/admin/roles?error=' . urlencode('Rol bulunamadı.'));
        }
        $role = $role[0];

        if (!$this->rbacService->canManageRole($actorId, $roleId)) {
            $response->redirect('/admin/roles?error=' . urlencode('Bu rolü düzenleme yetkiniz yok. (Eşit veya üst seviyedeki hiyerarşik roller düzenlenemez)'));
        }

        $actorMaxPriority = $this->rbacService->getAdminMaxPriority($actorId);
        $parentRoles = $this->db->query("SELECT * FROM roles WHERE id != :id AND priority <= :prio ORDER BY priority DESC", [':id' => $roleId, ':prio' => $actorMaxPriority]);

        $grantablePermIds = $this->rbacService->getGrantablePermissionIds($actorId);
        $permissions = [];
        if (!empty($grantablePermIds)) {
            $inClause = implode(',', array_map('intval', $grantablePermIds));
            $permissions = $this->db->query("SELECT * FROM permissions WHERE id IN ({$inClause}) ORDER BY id ASC");
        }

        $assignedPerms = $this->db->query("SELECT permission_id FROM role_permissions WHERE role_id = :role_id", [':role_id' => $roleId]);
        $assignedIds = array_column($assignedPerms, 'permission_id');

        return $this->render('admin/roles/edit', [
            'role' => $role,
            'parentRoles' => $parentRoles,
            'permissions' => $permissions,
            'assignedIds' => $assignedIds,
            'actorMaxPriority' => $actorMaxPriority
        ]);
    }

    public function update(Request $request, Response $response): void {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $actorMaxPriority = $this->rbacService->getAdminMaxPriority($actorId);
        $roleId = (int)($request->post('id') ?? 0);
        $name = trim((string)$request->post('name'));
        $desc = trim((string)$request->post('description'));
        $parentId = (int)($request->post('parent_id') ?? 0);
        $priority = (int)($request->post('priority') ?? 10);
        $perms = $request->post('permissions') ?? [];

        if (!$this->rbacService->canManageRole($actorId, $roleId)) {
            $response->redirect('/admin/roles?error=' . urlencode('Yetkisiz işlem. Bu rolü güncelleyemezsiniz.'));
            return;
        }

        if ($actorMaxPriority < 100 && $priority >= $actorMaxPriority) {
            $response->redirect('/admin/roles?error=' . urlencode("Yetki Aşımı Hatası: Rol seviyesini kendi seviyenize ({$actorMaxPriority}) veya üstüne yükseltemezsiniz."));
            return;
        }

        try {
            $permIds = array_map('intval', (array)$perms);
            $this->rbacService->updateRolePermissions($roleId, $permIds, $actorId);

            $this->db->execute(
                "UPDATE roles SET name = :name, description = :desc, parent_id = :parent_id, priority = :priority WHERE id = :id",
                [
                    ':name' => $name,
                    ':desc' => $desc,
                    ':parent_id' => ($parentId > 0 ? $parentId : null),
                    ':priority' => $priority,
                    ':id' => $roleId
                ]
            );

            $this->rbacService->logAudit($actorId, 'ROLE_UPDATED', 'Role', $roleId, [], ['name' => $name, 'priority' => $priority]);

            $response->redirect('/admin/roles?success=' . urlencode('Rol ve yetkiler başarıyla güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/roles?error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(Request $request, Response $response): void {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $roleId = (int)($request->post('id') ?? 0);

        try {
            $this->rbacService->deleteRole($roleId, $actorId);
            $this->rbacService->logAudit($actorId, 'ROLE_DELETED', 'Role', $roleId);
            $response->redirect('/admin/roles?success=' . urlencode('Rol başarıyla silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/roles?error=' . urlencode($e->getMessage()));
        }
    }

    public function toggleStatus(Request $request, Response $response): void {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $roleId = (int)($request->post('id') ?? 0);

        if (!$this->rbacService->canManageRole($actorId, $roleId)) {
            $response->redirect('/admin/roles?error=' . urlencode('Bu rolün durumunu değiştirme yetkiniz yok.'));
            return;
        }

        $role = $this->db->query("SELECT is_active, is_system FROM roles WHERE id = :id LIMIT 1", [':id' => $roleId]);
        if (empty($role)) {
            $response->redirect('/admin/roles?error=' . urlencode('Rol bulunamadı.'));
            return;
        }

        if (!empty($role[0]['is_system'])) {
            $response->redirect('/admin/roles?error=' . urlencode('Sistem rolü pasifleştirilemez.'));
            return;
        }

        $newStatus = empty($role[0]['is_active']) ? 1 : 0;
        $this->db->execute("UPDATE roles SET is_active = :act WHERE id = :id", [':act' => $newStatus, ':id' => $roleId]);
        $this->rbacService->clearCacheForRole($roleId);
        $this->rbacService->logAudit($actorId, 'ROLE_STATUS_TOGGLED', 'Role', $roleId, [], ['is_active' => $newStatus]);

        $response->redirect('/admin/roles?success=' . urlencode('Rol durumu güncellendi.'));
    }

    public function duplicate(Request $request, Response $response): void {
        $actorId = (int)($_SESSION['admin_id'] ?? 0);
        $roleId = (int)($request->post('id') ?? 0);
        $newName = trim((string)$request->post('name'));

        try {
            $newRoleId = $this->rbacService->duplicateRole($roleId, $newName ?: 'Kopya Rol', null, $actorId);
            $this->rbacService->logAudit($actorId, 'ROLE_DUPLICATED', 'Role', $newRoleId);
            $response->redirect('/admin/roles?success=' . urlencode('Rol kopyalandı.'));
        } catch (Exception $e) {
            $response->redirect('/admin/roles?error=' . urlencode($e->getMessage()));
        }
    }
}
