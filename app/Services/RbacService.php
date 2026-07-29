<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\DatabaseInterface;
use Core\Contracts\CacheInterface;
use Exception;

class RbacService {
    private DatabaseInterface $db;
    private CacheInterface $cache;

    public function __construct(DatabaseInterface $db, CacheInterface $cache) {
        $this->db = $db;
        $this->cache = $cache;
    }

    public function adminHasPermission(int $adminId, string $permissionName): bool {
        $admin = $this->db->query("SELECT is_super FROM admins WHERE id = :id LIMIT 1", [':id' => $adminId]);
        if (!empty($admin) && $admin[0]['is_super']) {
            return true;
        }

        $permissions = $this->getAdminPermissions($adminId);
        return in_array($permissionName, $permissions, true);
    }

    public function userHasPermission(int $userId, string $permissionName): bool {
        $permissions = $this->getUserPermissions($userId);
        return in_array($permissionName, $permissions, true);
    }

    public function getAdminPermissions(int $adminId): array {
        $cacheKey = "admin_perms_{$adminId}";
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $sql = "SELECT DISTINCT p.name FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN admin_roles ar ON rp.role_id = ar.role_id
                JOIN roles r ON ar.role_id = r.id
                WHERE ar.admin_id = :admin_id AND r.is_active = 1";
        
        $rows = $this->db->query($sql, [':admin_id' => $adminId]);
        $permissions = array_column($rows, 'name');

        $this->cache->set($cacheKey, $permissions, 3600);
        return $permissions;
    }

    public function getUserPermissions(int $userId): array {
        $cacheKey = "user_perms_{$userId}";
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        $permissions = [];
        $this->cache->set($cacheKey, $permissions, 3600);
        return $permissions;
    }

    public function duplicateRole(int $roleId, string $newRoleName, string $newRoleDesc = null): int {
        $role = $this->db->query("SELECT * FROM roles WHERE id = :id LIMIT 1", [':id' => $roleId]);
        if (empty($role)) {
            throw new Exception("Kopyalanacak rol bulunamadı.");
        }
        $role = $role[0];

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO roles (name, description, is_active, priority) VALUES (:name, :desc, 1, :priority)",
                [
                    ':name' => $newRoleName,
                    ':desc' => $newRoleDesc ?? ($role['description'] . ' (Kopya)'),
                    ':priority' => $role['priority']
                ]
            );
            $newRoleId = (int)$this->db->lastInsertId();

            $permissions = $this->db->query("SELECT permission_id FROM role_permissions WHERE role_id = :role_id", [':role_id' => $roleId]);
            foreach ($permissions as $perm) {
                $this->db->execute(
                    "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :perm_id)",
                    [':role_id' => $newRoleId, ':perm_id' => $perm['permission_id']]
                );
            }

            $this->db->commit();
            return $newRoleId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateRolePermissions(int $roleId, array $permissionIds): void {
        if ($roleId === 1) {
            throw new Exception("Süper yönetici yetkileri değiştirilemez.");
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute("DELETE FROM role_permissions WHERE role_id = :role_id", [':role_id' => $roleId]);
            foreach ($permissionIds as $permId) {
                $this->db->execute(
                    "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :perm_id)",
                    [':role_id' => $roleId, ':perm_id' => $permId]
                );
            }
            $this->db->commit();

            $this->clearCacheForRole($roleId);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteRole(int $roleId): void {
        if ($roleId === 1) {
            throw new Exception("Süper yönetici rolü silinemez.");
        }

        $this->clearCacheForRole($roleId);
        $this->db->execute("DELETE FROM roles WHERE id = :id", [':id' => $roleId]);
    }

    public function clearCacheForRole(int $roleId): void {
        $admins = $this->db->query("SELECT admin_id FROM admin_roles WHERE role_id = :role_id", [':role_id' => $roleId]);
        foreach ($admins as $admin) {
            $this->cache->delete("admin_perms_{$admin['admin_id']}");
        }
    }

    public function clearPermissionsCache(int $userId = null, int $adminId = null): void {
        if ($userId) {
            $this->cache->delete("user_perms_{$userId}");
        }
        if ($adminId) {
            $this->cache->delete("admin_perms_{$adminId}");
        }
    }
}
