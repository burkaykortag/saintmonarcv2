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
        $roles = $this->db->query("SELECT * FROM roles ORDER BY priority DESC, id ASC");
        return $this->render('admin/roles/index', ['roles' => $roles]);
    }

    public function showCreate(Request $request, Response $response): string {
        return $this->render('admin/roles/create');
    }

    public function store(Request $request, Response $response): void {
        $name = $request->post('name') ?? '';
        $desc = $request->post('description') ?? '';
        $priority = (int)($request->post('priority') ?? 0);

        try {
            $this->db->execute(
                "INSERT INTO roles (name, description, is_active, priority) VALUES (:name, :desc, 1, :priority)",
                [':name' => $name, ':desc' => $desc, ':priority' => $priority]
            );
            $response->redirect('/admin/roles?success=' . urlencode('Rol başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/roles/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function showEdit(Request $request, Response $response): string {
        $id = (int)($request->get('id') ?? 0);
        $role = $this->db->query("SELECT * FROM roles WHERE id = :id LIMIT 1", [':id' => $id]);
        
        if (empty($role)) {
            $response->redirect('/admin/roles?error=' . urlencode('Rol bulunamadı.'));
        }

        $allPermissions = $this->db->query("SELECT * FROM permissions ORDER BY id ASC");
        
        $assignedPerms = $this->db->query(
            "SELECT permission_id FROM role_permissions WHERE role_id = :role_id",
            [':role_id' => $id]
        );
        $assignedIds = array_column($assignedPerms, 'permission_id');

        return $this->render('admin/roles/edit', [
            'role' => $role[0],
            'permissions' => $allPermissions,
            'assignedIds' => $assignedIds
        ]);
    }

    public function update(Request $request, Response $response): void {
        $id = (int)($request->post('id') ?? 0);
        $name = $request->post('name') ?? '';
        $desc = $request->post('description') ?? '';
        $priority = (int)($request->post('priority') ?? 0);
        $perms = $request->post('permissions') ?? []; // Array of permission IDs

        if ($id === 1) {
            $response->redirect('/admin/roles?error=' . urlencode('Süper yönetici rolü düzenlenemez.'));
            return;
        }

        try {
            $this->db->beginTransaction();
            
            $this->db->execute(
                "UPDATE roles SET name = :name, description = :desc, priority = :priority WHERE id = :id",
                [':name' => $name, ':desc' => $desc, ':priority' => $priority, ':id' => $id]
            );

            // Convert to array of integers
            $permIds = array_map('intval', (array)$perms);
            $this->rbacService->updateRolePermissions($id, $permIds);

            $this->db->commit();
            $response->redirect('/admin/roles?success=' . urlencode('Rol ve yetkiler başarıyla güncellendi.'));
        } catch (Exception $e) {
            $this->db->rollBack();
            $response->redirect("/admin/roles/edit?id={$id}&error=" . urlencode($e->getMessage()));
        }
    }

    public function duplicate(Request $request, Response $response): void {
        $id = (int)($request->post('id') ?? 0);
        $name = $request->post('name') ?? '';

        try {
            $this->rbacService->duplicateRole($id, $name);
            $response->redirect('/admin/roles?success=' . urlencode('Rol başarıyla kopyalandı.'));
        } catch (Exception $e) {
            $response->redirect('/admin/roles?error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(Request $request, Response $response): void {
        $id = (int)($request->post('id') ?? 0);

        try {
            $this->rbacService->deleteRole($id);
            $response->redirect('/admin/roles?success=' . urlencode('Rol başarıyla silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/roles?error=' . urlencode($e->getMessage()));
        }
    }

    public function toggleStatus(Request $request, Response $response): void {
        $id = (int)($request->post('id') ?? 0);
        
        if ($id === 1) {
            $response->redirect('/admin/roles?error=' . urlencode('Süper yönetici rol durumu değiştirilemez.'));
            return;
        }

        try {
            $role = $this->db->query("SELECT is_active FROM roles WHERE id = :id LIMIT 1", [':id' => $id]);
            if (!empty($role)) {
                $newStatus = $role[0]['is_active'] ? 0 : 1;
                $this->db->execute(
                    "UPDATE roles SET is_active = :status WHERE id = :id",
                    [':status' => $newStatus, ':id' => $id]
                );
                
                // Clear cache on disable/enable
                $this->rbacService->clearCacheForRole($id);
            }
            $response->redirect('/admin/roles?success=' . urlencode('Rol durumu güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/roles?error=' . urlencode($e->getMessage()));
        }
    }
}
