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

    /**
     * Check if admin has specific permission
     */
    public function adminHasPermission(int $adminId, string $permissionName): bool {
        // Check if impersonating and permission is restricted during impersonation
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['impersonation']['active'])) {
            $restrictedPermissions = [
                'manage_roles', 'edit_roles', 'delete_roles', 'create_roles',
                'manage_users', 'delete_users', 'impersonate_users', 'manage_settings',
                'edit_payment_settings', 'audit_logs'
            ];
            if (in_array($permissionName, $restrictedPermissions, true)) {
                return false;
            }
        }

        $admin = $this->db->query("SELECT is_super FROM admins WHERE id = :id LIMIT 1", [':id' => $adminId]);
        if (!empty($admin) && (bool)$admin[0]['is_super']) {
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

    /**
     * Get maximum role priority for an admin
     */
    public function getAdminMaxPriority(int $adminId): int {
        $admin = $this->db->query("SELECT is_super FROM admins WHERE id = :id LIMIT 1", [':id' => $adminId]);
        if (!empty($admin) && (bool)$admin[0]['is_super']) {
            return 100;
        }

        $sql = "SELECT MAX(r.priority) as max_priority 
                FROM roles r 
                JOIN admin_roles ar ON r.id = ar.role_id 
                WHERE ar.admin_id = :aid AND r.is_active = 1";
        $row = $this->db->query($sql, [':aid' => $adminId]);
        return (int)($row[0]['max_priority'] ?? 0);
    }

    /**
     * Check if actor can manage a role
     */
    public function canManageRole(int $actorAdminId, int $targetRoleId): bool {
        $actorMaxPriority = $this->getAdminMaxPriority($actorAdminId);
        
        $targetRole = $this->db->query("SELECT * FROM roles WHERE id = :id LIMIT 1", [':id' => $targetRoleId]);
        if (empty($targetRole)) {
            return false;
        }
        $targetRole = $targetRole[0];

        // System roles (DevAdmin, SuperAdmin) can only be managed by DevAdmin/SuperAdmin (priority 100)
        if (!empty($targetRole['is_system']) && $actorMaxPriority < 100) {
            return false;
        }

        return $actorMaxPriority > (int)$targetRole['priority'];
    }

    /**
     * Check if actor can manage a target admin
     */
    public function canManageAdmin(int $actorAdminId, int $targetAdminId): bool {
        if ($actorAdminId === $targetAdminId) {
            return true; // Self management allowed for editing own profile (subject to controller rules)
        }

        $actorMaxPriority = $this->getAdminMaxPriority($actorAdminId);
        $targetMaxPriority = $this->getAdminMaxPriority($targetAdminId);

        // Cannot manage equal or higher rank
        return $actorMaxPriority > $targetMaxPriority;
    }

    /**
     * Check if actor can impersonate target admin
     */
    public function canImpersonate(int $actorAdminId, int $targetAdminId): bool {
        // Prevent self impersonation
        if ($actorAdminId === $targetAdminId) {
            return false;
        }

        // Prevent nested impersonation
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['impersonation']['active'])) {
            return false;
        }

        $target = $this->db->query("SELECT is_super, is_impersonatable FROM admins WHERE id = :id LIMIT 1", [':id' => $targetAdminId]);
        if (empty($target)) {
            return false;
        }
        $target = $target[0];

        // Check if target is explicitly marked as non-impersonatable
        if (isset($target['is_impersonatable']) && !(bool)$target['is_impersonatable']) {
            return false;
        }

        if (!empty($target['is_super'])) {
            return false;
        }

        $actorMaxPriority = $this->getAdminMaxPriority($actorAdminId);
        $targetMaxPriority = $this->getAdminMaxPriority($targetAdminId);

        // Must have higher priority to impersonate
        return $actorMaxPriority > $targetMaxPriority;
    }

    /**
     * Get permission IDs that actor can grant to others
     */
    public function getGrantablePermissionIds(int $adminId): array {
        $actorMaxPriority = $this->getAdminMaxPriority($adminId);
        if ($actorMaxPriority >= 100) {
            // Super Admin can grant all permissions
            $allPerms = $this->db->query("SELECT id FROM permissions");
            return array_column($allPerms, 'id');
        }

        $sql = "SELECT DISTINCT p.id FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN admin_roles ar ON rp.role_id = ar.role_id
                JOIN roles r ON ar.role_id = r.id
                WHERE ar.admin_id = :aid AND r.is_active = 1";
        $rows = $this->db->query($sql, [':aid' => $adminId]);
        return array_column($rows, 'id');
    }

    /**
     * Validate permission delegation
     */
    public function validatePermissionGrant(int $actorAdminId, array $requestedPermIds): void {
        $grantableIds = $this->getGrantablePermissionIds($actorAdminId);
        foreach ($requestedPermIds as $permId) {
            if (!in_array((int)$permId, $grantableIds, true)) {
                throw new Exception("Yetki devri hatası: Sahip olmadığınız yetkileri (Permission ID: {$permId}) alt rollere aktaramazsınız.", 403);
            }
        }
    }

    public function duplicateRole(int $roleId, string $newRoleName, string $newRoleDesc = null, int $actorAdminId = 0): int {
        $role = $this->db->query("SELECT * FROM roles WHERE id = :id LIMIT 1", [':id' => $roleId]);
        if (empty($role)) {
            throw new Exception("Kopyalanacak rol bulunamadı.");
        }
        $role = $role[0];

        if ($actorAdminId > 0 && !$this->canManageRole($actorAdminId, $roleId)) {
            throw new Exception("Bu rolü kopyalama yetkiniz yok.", 403);
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO roles (name, description, parent_id, is_active, priority, is_system) VALUES (:name, :desc, :parent_id, 1, :priority, 0)",
                [
                    ':name' => $newRoleName,
                    ':desc' => $newRoleDesc ?? ($role['description'] . ' (Kopya)'),
                    ':parent_id' => $role['parent_id'],
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

    public function updateRolePermissions(int $roleId, array $permissionIds, int $actorAdminId = 0): void {
        $role = $this->db->query("SELECT * FROM roles WHERE id = :id LIMIT 1", [':id' => $roleId]);
        if (empty($role)) {
            throw new Exception("Rol bulunamadı.");
        }
        $role = $role[0];

        if (!empty($role['is_system']) && $roleId <= 2) {
            throw new Exception("Sistem rol yetkileri değiştirilemez.");
        }

        if ($actorAdminId > 0) {
            if (!$this->canManageRole($actorAdminId, $roleId)) {
                throw new Exception("Bu rolün yetkilerini değiştirme izniniz yok.", 403);
            }
            $this->validatePermissionGrant($actorAdminId, $permissionIds);
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute("DELETE FROM role_permissions WHERE role_id = :role_id", [':role_id' => $roleId]);
            foreach ($permissionIds as $permId) {
                $this->db->execute(
                    "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :perm_id)",
                    [':role_id' => $roleId, ':perm_id' => (int)$permId]
                );
            }
            $this->db->commit();

            $this->clearCacheForRole($roleId);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteRole(int $roleId, int $actorAdminId = 0): void {
        $role = $this->db->query("SELECT * FROM roles WHERE id = :id LIMIT 1", [':id' => $roleId]);
        if (empty($role)) {
            throw new Exception("Silinecek rol bulunamadı.");
        }
        $role = $role[0];

        if (!empty($role['is_system'])) {
            throw new Exception("Sistem rolleri silinemez.", 403);
        }

        if ($actorAdminId > 0 && !$this->canManageRole($actorAdminId, $roleId)) {
            throw new Exception("Bu rolü silme yetkiniz yok.", 403);
        }

        $this->clearCacheForRole($roleId);
        $this->db->execute("DELETE FROM roles WHERE id = :id", [':id' => $roleId]);
    }

    public function logAudit(
        int $userId,
        string $event,
        string $type,
        int $id,
        array $old = [],
        array $new = [],
        ?int $impersonatorId = null,
        ?int $targetUserId = null
    ): void {
        $isImpersonated = ($impersonatorId !== null) ? 1 : 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System';

        $this->db->execute(
            "INSERT INTO audit_logs (user_type, user_id, impersonator_id, target_user_id, is_impersonated, event, auditable_type, auditable_id, old_values, new_values, ip_address, user_agent, created_at)
             VALUES ('admin', :uid, :imp_id, :target_id, :is_imp, :event, :atype, :aid, :old, :new, :ip, :ua, NOW())",
            [
                ':uid' => $userId,
                ':imp_id' => $impersonatorId,
                ':target_id' => $targetUserId,
                ':is_imp' => $isImpersonated,
                ':event' => $event,
                ':atype' => $type,
                ':aid' => $id,
                ':old' => json_encode($old),
                ':new' => json_encode($new),
                ':ip' => $ip,
                ':ua' => $ua
            ]
        );
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
