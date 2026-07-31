-- Sprint 23: Enterprise Workflow Automation & Business Process Engine Migration
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 1. workflows table
CREATE TABLE IF NOT EXISTS `workflows` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `status` ENUM('active', 'draft', 'paused') NOT NULL DEFAULT 'draft',
    `trigger_type` VARCHAR(100) NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_workflows_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. workflow_triggers table
CREATE TABLE IF NOT EXISTS `workflow_triggers` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `event_name` VARCHAR(100) NOT NULL,
    `conditions_json` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_triggers_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. workflow_actions table
CREATE TABLE IF NOT EXISTS `workflow_actions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` VARCHAR(100) NOT NULL COMMENT 'mail, sms, crm_note, slack, webhook, delay, etc.',
    `config_json` JSON NULL,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_actions_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. workflow_conditions table
CREATE TABLE IF NOT EXISTS `workflow_conditions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `field` VARCHAR(100) NOT NULL,
    `operator` VARCHAR(50) NOT NULL,
    `value` VARCHAR(255) NULL,
    `match_type` ENUM('AND', 'OR') NOT NULL DEFAULT 'AND',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_conditions_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. workflow_variables table
CREATE TABLE IF NOT EXISTS `workflow_variables` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'string',
    `value` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_variables_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. workflow_executions table
CREATE TABLE IF NOT EXISTS `workflow_executions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `trigger_payload` JSON NULL,
    `status` ENUM('running', 'completed', 'failed', 'paused') NOT NULL DEFAULT 'running',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_executions_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. workflow_logs table
CREATE TABLE IF NOT EXISTS `workflow_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `execution_id` BIGINT(20) UNSIGNED NULL,
    `level` VARCHAR(50) NOT NULL DEFAULT 'info',
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_logs_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. workflow_history table
CREATE TABLE IF NOT EXISTS `workflow_history` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `execution_id` BIGINT(20) UNSIGNED NOT NULL,
    `status` ENUM('success', 'failed', 'retrying') NOT NULL,
    `started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    `error_message` TEXT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_history_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. workflow_templates table
CREATE TABLE IF NOT EXISTS `workflow_templates` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `trigger_type` VARCHAR(100) NOT NULL,
    `config_json` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. workflow_schedules table
CREATE TABLE IF NOT EXISTS `workflow_schedules` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `cron_expression` VARCHAR(100) NOT NULL,
    `next_run_at` TIMESTAMP NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_schedules_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. workflow_queue table
CREATE TABLE IF NOT EXISTS `workflow_queue` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `action_id` BIGINT(20) UNSIGNED NOT NULL,
    `payload_json` JSON NULL,
    `status` ENUM('pending', 'processing', 'failed', 'completed') NOT NULL DEFAULT 'pending',
    `retry_count` INT(11) NOT NULL DEFAULT 0,
    `run_at` TIMESTAMP NULL,
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_queue_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. workflow_errors table
CREATE TABLE IF NOT EXISTS `workflow_errors` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `execution_id` BIGINT(20) UNSIGNED NOT NULL,
    `action_id` BIGINT(20) UNSIGNED NULL,
    `error_message` TEXT NOT NULL,
    `stack_trace` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_errors_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. workflow_webhooks table
CREATE TABLE IF NOT EXISTS `workflow_webhooks` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `endpoint_url` VARCHAR(500) NOT NULL,
    `token` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_webhooks_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. workflow_notifications table
CREATE TABLE IF NOT EXISTS `workflow_notifications` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `user_id` BIGINT(20) UNSIGNED NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'alert',
    `subject` VARCHAR(200) NOT NULL,
    `body` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_notifications_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. workflow_versions table
CREATE TABLE IF NOT EXISTS `workflow_versions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `version_number` INT(11) NOT NULL DEFAULT 1,
    `config_json` JSON NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_versions_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. workflow_statistics table
CREATE TABLE IF NOT EXISTS `workflow_statistics` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL UNIQUE,
    `total_runs` INT(11) NOT NULL DEFAULT 0,
    `total_success` INT(11) NOT NULL DEFAULT 0,
    `total_failed` INT(11) NOT NULL DEFAULT 0,
    `avg_duration_ms` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_statistics_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. workflow_permissions table
CREATE TABLE IF NOT EXISTS `workflow_permissions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `role_id` INT(11) NOT NULL,
    `permission_type` VARCHAR(50) NOT NULL DEFAULT 'read',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_permissions_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. workflow_ai_rules table
CREATE TABLE IF NOT EXISTS `workflow_ai_rules` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT(20) UNSIGNED NOT NULL,
    `prompt` TEXT NOT NULL,
    `expected_output` TEXT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_ai_rules_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. workflow_delays table
CREATE TABLE IF NOT EXISTS `workflow_delays` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `queue_id` BIGINT(20) UNSIGNED NOT NULL,
    `delay_seconds` INT(11) NOT NULL DEFAULT 0,
    `resume_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. workflow_wait_states table
CREATE TABLE IF NOT EXISTS `workflow_wait_states` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `execution_id` BIGINT(20) UNSIGNED NOT NULL,
    `event_name` VARCHAR(100) NOT NULL,
    `status` ENUM('waiting', 'resumed', 'timed_out') NOT NULL DEFAULT 'waiting',
    `resume_payload` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_workflow_wait_states_execution` FOREIGN KEY (`execution_id`) REFERENCES `workflow_executions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Seed Permissions
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
    ('view_workflows', 'İş Akışlarını Listele'),
    ('create_workflows', 'Yeni İş Akışı Ekle'),
    ('edit_workflows', 'İş Akışı Düzenle'),
    ('delete_workflows', 'İş Akışı Sil'),
    ('run_workflows', 'İş Akışı Çalıştır'),
    ('workflow_templates', 'Şablon Merkezi Erişimi'),
    ('workflow_reports', 'Otomasyon İstatistikleri'),
    ('workflow_logs', 'Çalışma Logları İnceleme'),
    ('workflow_queue', 'İş Akış Kuyruğu Yönetimi'),
    ('workflow_settings', 'Otomasyon Motor Ayarları');

-- Give permissions to super_admin and admin role
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'admin') AND p.name LIKE '%workflow%';
