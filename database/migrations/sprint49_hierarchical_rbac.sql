-- Sprint 49: Hierarchical RBAC & Impersonation Database Updates

SET NAMES utf8mb4;

-- 1. Update roles table for hierarchy & system flags
ALTER TABLE `roles`
    ADD COLUMN `parent_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `id`,
    ADD COLUMN `slug` VARCHAR(50) NULL DEFAULT NULL AFTER `name`,
    ADD COLUMN `is_system` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: System Role (DevAdmin/SuperAdmin)' AFTER `priority`;

-- Add FK for parent_id if not exists
ALTER TABLE `roles`
    ADD CONSTRAINT `fk_roles_parent` FOREIGN KEY (`parent_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

-- 2. Update admins table for impersonation protection
ALTER TABLE `admins`
    ADD COLUMN `is_impersonatable` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0: Super/Dev admin cannot be impersonated' AFTER `is_super`;

-- 3. Update audit_logs table for tracking impersonator context
ALTER TABLE `audit_logs`
    ADD COLUMN `impersonator_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL COMMENT 'Real admin ID during impersonation' AFTER `user_id`,
    ADD COLUMN `target_user_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL COMMENT 'Target user ID being impersonated' AFTER `impersonator_id`,
    ADD COLUMN `is_impersonated` TINYINT(1) NOT NULL DEFAULT 0 AFTER `target_user_id`;

-- Seed default system roles hierarchy if not present
-- Ensure Dev Admin, Super Admin, Admin, Manager, Finance, Editor, Operator exist with correct priority and parent_id
