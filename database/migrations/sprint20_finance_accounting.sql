-- Sprint 20: Enterprise Finans, Muhasebe & Faturalandırma Sistemi (Finance & Accounting)
-- UTF-8 MB4 destekli, Türkçe karakter uyumlu

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. financial_accounts (Finansal Hesaplar)
CREATE TABLE IF NOT EXISTS `financial_accounts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL COMMENT 'Hesap Planı Kodu (örn: 100, 102, 120)',
    `name` VARCHAR(150) NOT NULL COMMENT 'Hesap Adı',
    `type` VARCHAR(50) NOT NULL COMMENT 'asset, liability, equity, revenue, expense',
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_fin_acc_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. financial_transactions (Finansal Hareketler)
CREATE TABLE IF NOT EXISTS `financial_transactions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` VARCHAR(20) NOT NULL COMMENT 'debit (borç), credit (alacak)',
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `description` VARCHAR(255) NULL,
    `transaction_date` DATETIME NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_fin_tx_acc` (`account_id`),
    CONSTRAINT `fk_fin_tx_acc` FOREIGN KEY (`account_id`) REFERENCES `financial_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. accounting_journals (Yevmiye Defterleri)
CREATE TABLE IF NOT EXISTS `accounting_journals` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_acc_journal_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. accounting_entries (Muhasebe Fişleri / Yevmiye Kayıtları)
CREATE TABLE IF NOT EXISTS `accounting_entries` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `journal_id` BIGINT(20) UNSIGNED NOT NULL,
    `entry_number` VARCHAR(50) NOT NULL COMMENT 'Yevmiye Fiş No',
    `description` VARCHAR(255) NULL,
    `entry_date` DATE NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_acc_entry_journal` (`journal_id`),
    CONSTRAINT `fk_acc_entry_journal` FOREIGN KEY (`journal_id`) REFERENCES `accounting_journals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. customer_accounts (Müşteri Cari Hesapları)
CREATE TABLE IF NOT EXISTS `customer_accounts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT(20) UNSIGNED NOT NULL,
    `account_code` VARCHAR(50) NOT NULL,
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_cust_acc_cust` (`customer_id`),
    KEY `idx_cust_acc_code` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. supplier_accounts (Satıcı/Tedarikçi Cari Hesapları)
CREATE TABLE IF NOT EXISTS `supplier_accounts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_name` VARCHAR(150) NOT NULL,
    `tax_number` VARCHAR(50) NULL,
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. bank_accounts (Banka Hesapları)
CREATE TABLE IF NOT EXISTS `bank_accounts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_name` VARCHAR(100) NOT NULL,
    `branch_name` VARCHAR(100) NULL,
    `account_number` VARCHAR(100) NOT NULL,
    `iban` VARCHAR(100) NOT NULL,
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_bank_iban` (`iban`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. bank_transactions (Banka Hareketleri)
CREATE TABLE IF NOT EXISTS `bank_transactions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_account_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` VARCHAR(20) NOT NULL COMMENT 'deposit (giriş), withdraw (çıkış)',
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `description` VARCHAR(255) NULL,
    `transaction_date` DATETIME NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_bank_tx_acc` (`bank_account_id`),
    CONSTRAINT `fk_bank_tx_acc` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. cash_accounts (Kasa Hesapları)
CREATE TABLE IF NOT EXISTS `cash_accounts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. cash_transactions (Kasa/Nakit Hareketleri)
CREATE TABLE IF NOT EXISTS `cash_transactions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `cash_account_id` BIGINT(20) UNSIGNED NOT NULL,
    `type` VARCHAR(20) NOT NULL COMMENT 'in (giriş), out (çıkış)',
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `description` VARCHAR(255) NULL,
    `transaction_date` DATETIME NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_cash_tx_acc` (`cash_account_id`),
    CONSTRAINT `fk_cash_tx_acc` FOREIGN KEY (`cash_account_id`) REFERENCES `cash_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. expense_categories (Masraf / Gider Kategorileri)
CREATE TABLE IF NOT EXISTS `expense_categories` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. expenses (Gider Kayıtları)
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `tax_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `description` VARCHAR(255) NULL,
    `expense_date` DATE NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_expense_cat` (`category_id`),
    CONSTRAINT `fk_expense_cat` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. revenue_categories (Gelir Kategorileri)
CREATE TABLE IF NOT EXISTS `revenue_categories` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. revenues (Gelir Kayıtları)
CREATE TABLE IF NOT EXISTS `revenues` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `tax_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `description` VARCHAR(255) NULL,
    `revenue_date` DATE NOT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_revenue_cat` (`category_id`),
    CONSTRAINT `fk_revenue_cat` FOREIGN KEY (`category_id`) REFERENCES `revenue_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. payment_methods (Ödeme Yöntemleri)
CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_pay_method_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. payment_transactions (Ödeme İşlemleri)
CREATE TABLE IF NOT EXISTS `payment_transactions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_method_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY',
    `status` VARCHAR(50) NOT NULL DEFAULT 'completed',
    `reference_code` VARCHAR(100) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pay_tx_method` (`payment_method_id`),
    CONSTRAINT `fk_pay_tx_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. installments (Taksit Bilgileri)
CREATE TABLE IF NOT EXISTS `installments` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_transaction_id` BIGINT(20) UNSIGNED NOT NULL,
    `installment_number` INT NOT NULL,
    `due_date` DATE NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_inst_tx` (`payment_transaction_id`),
    CONSTRAINT `fk_inst_tx` FOREIGN KEY (`payment_transaction_id`) REFERENCES `payment_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. tax_rates (Vergi Oranları)
CREATE TABLE IF NOT EXISTS `tax_rates` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_tax_rate_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. tax_rules (Vergi Kuralları)
CREATE TABLE IF NOT EXISTS `tax_rules` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tax_rate_id` BIGINT(20) UNSIGNED NOT NULL,
    `country_code` VARCHAR(2) NOT NULL DEFAULT 'TR',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_tax_rule_rate` (`tax_rate_id`),
    CONSTRAINT `fk_tax_rule_rate` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. invoices (Faturalar)
CREATE TABLE IF NOT EXISTS `invoices` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_number` VARCHAR(50) NOT NULL,
    `order_id` BIGINT(20) UNSIGNED NULL,
    `customer_id` BIGINT(20) UNSIGNED NULL,
    `invoice_type` VARCHAR(50) NOT NULL DEFAULT 'sales' COMMENT 'sales, purchase, return, proforma',
    `sub_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `tax_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `grand_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
    `invoice_date` DATE NOT NULL,
    `uuid` VARCHAR(100) NULL,
    `qr_code` TEXT NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_invoice_num` (`invoice_number`),
    KEY `idx_invoice_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `invoices`
    MODIFY COLUMN `order_id` BIGINT(20) UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `customer_id` BIGINT(20) UNSIGNED NULL AFTER `order_id`,
    ADD COLUMN IF NOT EXISTS `invoice_type` VARCHAR(50) NOT NULL DEFAULT 'sales' AFTER `customer_id`,
    ADD COLUMN IF NOT EXISTS `sub_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 AFTER `invoice_type`,
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(50) NOT NULL DEFAULT 'draft' AFTER `grand_total`,
    ADD COLUMN IF NOT EXISTS `invoice_date` DATE NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `uuid` VARCHAR(100) NULL AFTER `invoice_date`,
    ADD COLUMN IF NOT EXISTS `qr_code` TEXT NULL AFTER `uuid`,
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
    ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL AFTER `updated_at`;

-- 21. invoice_items (Fatura Kalemleri)
CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT(20) UNSIGNED NOT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    `tax_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `total_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    PRIMARY KEY (`id`),
    KEY `idx_inv_item_inv` (`invoice_id`),
    CONSTRAINT `fk_inv_item_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. invoice_payments (Fatura Ödemeleri)
CREATE TABLE IF NOT EXISTS `invoice_payments` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT(20) UNSIGNED NOT NULL,
    `payment_transaction_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL,
    `payment_date` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_inv_pay_inv` (`invoice_id`),
    CONSTRAINT `fk_inv_pay_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_inv_pay_tx` FOREIGN KEY (`payment_transaction_id`) REFERENCES `payment_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. credit_notes (Alacak Dekontları)
CREATE TABLE IF NOT EXISTS `credit_notes` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL,
    `reason` VARCHAR(255) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_credit_note_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. debit_notes (Borç Dekontları)
CREATE TABLE IF NOT EXISTS `debit_notes` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT(20) UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL,
    `reason` VARCHAR(255) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_debit_note_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. einvoice_logs (E-Fatura Gönderim Logları)
CREATE TABLE IF NOT EXISTS `einvoice_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT(20) UNSIGNED NOT NULL,
    `gib_status` VARCHAR(100) NULL,
    `gib_code` VARCHAR(50) NULL,
    `response_payload` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_einv_log_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. earchive_logs (E-Arşiv Logları)
CREATE TABLE IF NOT EXISTS `earchive_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` BIGINT(20) UNSIGNED NOT NULL,
    `archive_status` VARCHAR(100) NULL,
    `response_payload` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_earch_log_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. edispatch_logs (E-İrsaliye Gönderim Logları)
CREATE TABLE IF NOT EXISTS `edispatch_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `gib_status` VARCHAR(100) NULL,
    `response_payload` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 28. financial_reports (Genel Finans Raporları)
CREATE TABLE IF NOT EXISTS `financial_reports` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `report_name` VARCHAR(150) NOT NULL,
    `file_path` VARCHAR(255) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 29. profit_loss_reports (Gelir Tablosu / Kar Zarar Raporları)
CREATE TABLE IF NOT EXISTS `profit_loss_reports` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `total_revenue` DECIMAL(15,4) NOT NULL,
    `total_expense` DECIMAL(15,4) NOT NULL,
    `net_profit` DECIMAL(15,4) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 30. balance_sheet_reports (Bilanço Raporları)
CREATE TABLE IF NOT EXISTS `balance_sheet_reports` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `report_date` DATE NOT NULL,
    `total_assets` DECIMAL(15,4) NOT NULL,
    `total_liabilities` DECIMAL(15,4) NOT NULL,
    `total_equity` DECIMAL(15,4) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 31. trial_balance (Mizan Tablosu)
CREATE TABLE IF NOT EXISTS `trial_balance` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_code` VARCHAR(50) NOT NULL,
    `debit_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `credit_total` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `balance` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `period` VARCHAR(20) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 32. budget_plans (Bütçe Planları)
CREATE TABLE IF NOT EXISTS `budget_plans` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `updated_by` BIGINT(20) UNSIGNED NULL,
    `deleted_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 33. budget_items (Bütçe Kalemleri)
CREATE TABLE IF NOT EXISTS `budget_items` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `budget_plan_id` BIGINT(20) UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `allocated_amount` DECIMAL(15,4) NOT NULL,
    `spent_amount` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_budget_item_plan` FOREIGN KEY (`budget_plan_id`) REFERENCES `budget_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 34. currencies_history (Döviz Kurları Geçmişi)
CREATE TABLE IF NOT EXISTS `currencies_history` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(3) NOT NULL,
    `rate` DECIMAL(15,6) NOT NULL,
    `recorded_date` DATE NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_curr_code_date` (`code`, `recorded_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 35. financial_logs (Finansal Audit Denetim Logları)
CREATE TABLE IF NOT EXISTS `financial_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `action` VARCHAR(100) NOT NULL,
    `payload` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_by` BIGINT(20) UNSIGNED NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RBAC İzinleri
INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
('view_finance', 'Finans Panelini Görüntüle'),
('manage_finance', 'Finans Sistemini Yönet'),
('manage_accounts', 'Hesapları Yönet'),
('manage_cash', 'Kasaları Yönet'),
('manage_bank', 'Bankaları Yönet'),
('manage_expenses', 'Giderleri Yönet'),
('manage_revenues', 'Gelirleri Yönet'),
('manage_invoices', 'Faturaları Yönet'),
('manage_payments', 'Ödemeleri Yönet'),
('financial_reports', 'Finansal Raporlama'),
('tax_management', 'Vergi Yönetimi');

-- Süper admin ve admin'e finans izinlerinin atanması
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name IN ('super_admin', 'administrator')
  AND p.name IN (
    'view_finance', 'manage_finance', 'manage_accounts', 'manage_cash', 'manage_bank',
    'manage_expenses', 'manage_revenues', 'manage_invoices', 'manage_payments',
    'financial_reports', 'tax_management'
  );

SET FOREIGN_KEY_CHECKS = 1;
