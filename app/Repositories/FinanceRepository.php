<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;
use PDO;

class FinanceRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    /**
     * Cari hesap veya finansal hesap oluşturur/günceller.
     */
    public function upsertFinancialAccount(array $data): bool {
        return $this->db->execute(
            "INSERT INTO financial_accounts (code, name, type, balance, currency, is_active, created_at, updated_at)
             VALUES (:code, :name, :type, :balance, :currency, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE 
                name = VALUES(name), 
                type = VALUES(type), 
                balance = VALUES(balance),
                currency = VALUES(currency),
                updated_at = NOW()",
            [
                ':code' => $data['code'],
                ':name' => $data['name'],
                ':type' => $data['type'],
                ':balance' => (float)($data['balance'] ?? 0.0000),
                ':currency' => $data['currency'] ?? 'TRY'
            ]
        );
    }

    public function getFinancialAccount(string $code): ?array {
        $rows = $this->db->query("SELECT * FROM financial_accounts WHERE code = :code AND deleted_at IS NULL", [':code' => $code]);
        return $rows[0] ?? null;
    }

    public function listFinancialAccounts(): array {
        return $this->db->query("SELECT * FROM financial_accounts WHERE deleted_at IS NULL ORDER BY code ASC");
    }

    /**
     * Fatura Kaydeder (satış/alış/iade/proforma).
     */
    public function createInvoice(array $data, array $items): int {
        $this->db->execute(
            "INSERT INTO invoices (invoice_number, order_id, customer_id, invoice_type, sub_total, tax_total, grand_total, status, invoice_date, uuid, qr_code, created_at, updated_at)
             VALUES (:num, :order_id, :cust_id, :type, :sub, :tax, :grand, :status, :inv_date, :uuid, :qr, NOW(), NOW())",
            [
                ':num' => $data['invoice_number'],
                ':order_id' => $data['order_id'] ?? null,
                ':cust_id' => $data['customer_id'] ?? null,
                ':type' => $data['invoice_type'] ?? 'sales',
                ':sub' => (float)$data['sub_total'],
                ':tax' => (float)$data['tax_total'],
                ':grand' => (float)$data['grand_total'],
                ':status' => $data['status'] ?? 'draft',
                ':inv_date' => $data['invoice_date'] ?? date('Y-m-d'),
                ':uuid' => $data['uuid'] ?? null,
                ':qr' => $data['qr_code'] ?? null
            ]
        );

        $invoiceId = (int)$this->db->query("SELECT LAST_INSERT_ID() as id")[0]['id'];

        foreach ($items as $item) {
            $this->db->execute(
                "INSERT INTO invoice_items (invoice_id, item_name, quantity, unit_price, tax_rate, tax_amount, total_amount)
                 VALUES (:inv_id, :name, :qty, :price, :rate, :tax, :total)",
                [
                    ':inv_id' => $invoiceId,
                    ':name' => $item['item_name'],
                    ':qty' => (int)$item['quantity'],
                    ':price' => (float)$item['unit_price'],
                    ':rate' => (float)$item['tax_rate'],
                    ':tax' => (float)$item['tax_amount'],
                    ':total' => (float)$item['total_amount']
                ]
            );
        }

        return $invoiceId;
    }

    public function getInvoice(int $id): ?array {
        $rows = $this->db->query("SELECT * FROM invoices WHERE id = :id AND deleted_at IS NULL", [':id' => $id]);
        if (empty($rows)) return null;
        $invoice = $rows[0];
        $invoice['items'] = $this->db->query("SELECT * FROM invoice_items WHERE invoice_id = :id", [':id' => $id]);
        return $invoice;
    }

    public function getInvoiceByNumber(string $num): ?array {
        $rows = $this->db->query("SELECT * FROM invoices WHERE invoice_number = :num AND deleted_at IS NULL", [':num' => $num]);
        if (empty($rows)) return null;
        $invoice = $rows[0];
        $invoice['items'] = $this->db->query("SELECT * FROM invoice_items WHERE invoice_id = :id", [':id' => (int)$invoice['id']]);
        return $invoice;
    }

    public function listInvoices(array $filters = [], int $page = 1, int $perPage = 10): array {
        $sql = "SELECT * FROM invoices WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND invoice_type = :type";
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = ($page - 1) * $perPage;

        return $this->db->query($sql, $params);
    }

    public function deleteInvoice(int $id): bool {
        return $this->db->execute("UPDATE invoices SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);
    }

    public function restoreInvoice(int $id): bool {
        return $this->db->execute("UPDATE invoices SET deleted_at = NULL WHERE id = :id", [':id' => $id]);
    }

    /**
     * Masraf / Gider Kayıtları.
     */
    public function createExpense(array $data): int {
        $this->db->execute(
            "INSERT INTO expenses (category_id, amount, tax_amount, description, expense_date, created_at, updated_at)
             VALUES (:cat_id, :amount, :tax, :desc, :exp_date, NOW(), NOW())",
            [
                ':cat_id' => (int)$data['category_id'],
                ':amount' => (float)$data['amount'],
                ':tax' => (float)($data['tax_amount'] ?? 0.0000),
                ':desc' => $data['description'] ?? null,
                ':exp_date' => $data['expense_date'] ?? date('Y-m-d')
            ]
        );
        return (int)$this->db->query("SELECT LAST_INSERT_ID() as id")[0]['id'];
    }

    public function listExpenses(): array {
        return $this->db->query(
            "SELECT e.*, ec.name as category_name 
             FROM expenses e
             JOIN expense_categories ec ON e.category_id = ec.id
             WHERE e.deleted_at IS NULL
             ORDER BY e.expense_date DESC"
        );
    }

    /**
     * Gelir Kayıtları.
     */
    public function createRevenue(array $data): int {
        $this->db->execute(
            "INSERT INTO revenues (category_id, amount, tax_amount, description, revenue_date, created_at, updated_at)
             VALUES (:cat_id, :amount, :tax, :desc, :rev_date, NOW(), NOW())",
            [
                ':cat_id' => (int)$data['category_id'],
                ':amount' => (float)$data['amount'],
                ':tax' => (float)($data['tax_amount'] ?? 0.0000),
                ':desc' => $data['description'] ?? null,
                ':rev_date' => $data['revenue_date'] ?? date('Y-m-d')
            ]
        );
        return (int)$this->db->query("SELECT LAST_INSERT_ID() as id")[0]['id'];
    }

    public function listRevenues(): array {
        return $this->db->query(
            "SELECT r.*, rc.name as category_name 
             FROM revenues r
             JOIN revenue_categories rc ON r.category_id = rc.id
             WHERE r.deleted_at IS NULL
             ORDER BY r.revenue_date DESC"
        );
    }

    /**
     * Yevmiye Fişi & Muhasebe Fişi Oluşturma.
     */
    public function createAccountingEntry(string $journalCode, string $entryNumber, string $description, string $date, array $lines): int {
        // Journal bul veya oluştur
        $journals = $this->db->query("SELECT id FROM accounting_journals WHERE code = :code", [':code' => $journalCode]);
        if (empty($journals)) {
            $this->db->execute(
                "INSERT INTO accounting_journals (name, code, is_active, created_at, updated_at)
                 VALUES (:name, :code, 1, NOW(), NOW())",
                [':name' => $journalCode . ' Yevmiye Defteri', ':code' => $journalCode]
            );
            $journalId = (int)$this->db->query("SELECT LAST_INSERT_ID() as id")[0]['id'];
        } else {
            $journalId = (int)$journals[0]['id'];
        }

        // Fiş kaydet
        $this->db->execute(
            "INSERT INTO accounting_entries (journal_id, entry_number, description, entry_date, created_at, updated_at)
             VALUES (:journal_id, :num, :desc, :edate, NOW(), NOW())",
            [
                ':journal_id' => $journalId,
                ':num' => $entryNumber,
                ':desc' => $description,
                ':edate' => $date
            ]
        );
        $entryId = (int)$this->db->query("SELECT LAST_INSERT_ID() as id")[0]['id'];

        // Fiş satırlarını (debit/credit) financial_transactions'a yansıt
        foreach ($lines as $line) {
            $accCode = $line['account_code'];
            // Hesap bul veya oluştur
            $acc = $this->getFinancialAccount($accCode);
            if (!$acc) {
                $this->upsertFinancialAccount([
                    'code' => $accCode,
                    'name' => $line['account_name'] ?? 'Hesap ' . $accCode,
                    'type' => $line['type'] ?? 'asset',
                    'balance' => 0.0
                ]);
                $acc = $this->getFinancialAccount($accCode);
            }

            $accId = (int)$acc['id'];
            $amount = (float)$line['amount'];
            $type = $line['direction']; // debit veya credit

            // İşlemi kaydet
            $this->db->execute(
                "INSERT INTO financial_transactions (account_id, type, amount, description, transaction_date, created_at, updated_at)
                 VALUES (:acc_id, :type, :amount, :desc, :tdate, NOW(), NOW())",
                [
                    ':acc_id' => $accId,
                    ':type' => $type,
                    ':amount' => $amount,
                    ':desc' => $description,
                    ':tdate' => $date . ' ' . date('H:i:s')
                ]
            );

            // Bakiye Güncelle
            if ($type === 'debit') {
                $this->db->execute("UPDATE financial_accounts SET balance = balance + :amt WHERE id = :id", [':amt' => $amount, ':id' => $accId]);
            } else {
                $this->db->execute("UPDATE financial_accounts SET balance = balance - :amt WHERE id = :id", [':amt' => $amount, ':id' => $accId]);
            }
        }

        return $entryId;
    }

    /**
     * Müşteri Cari Bakiye sorguları.
     */
    public function getCustomerAccountBalance(int $customerId): float {
        $rows = $this->db->query("SELECT balance FROM customer_accounts WHERE customer_id = :id AND deleted_at IS NULL", [':id' => $customerId]);
        return isset($rows[0]) ? (float)$rows[0]['balance'] : 0.0000;
    }

    public function updateCustomerAccountBalance(int $customerId, float $amount, string $direction = 'debit'): void {
        // Cari hesap kaydı var mı kontrol et
        $rows = $this->db->query("SELECT id FROM customer_accounts WHERE customer_id = :id", [':id' => $customerId]);
        if (empty($rows)) {
            $this->db->execute(
                "INSERT INTO customer_accounts (customer_id, account_code, balance, currency, created_at, updated_at)
                 VALUES (:cust_id, :code, 0.0000, 'TRY', NOW(), NOW())",
                [':cust_id' => $customerId, ':code' => '120.' . sprintf('%06d', $customerId)]
            );
        }

        if ($direction === 'debit') {
            $this->db->execute(
                "UPDATE customer_accounts SET balance = balance + :amt, updated_at = NOW() WHERE customer_id = :cust_id",
                [':amt' => $amount, ':cust_id' => $customerId]
            );
        } else {
            $this->db->execute(
                "UPDATE customer_accounts SET balance = balance - :amt, updated_at = NOW() WHERE customer_id = :cust_id",
                [':amt' => $amount, ':cust_id' => $customerId]
            );
        }
    }

    /**
     * Kasa veya banka hesabı bakiyesini günceller.
     */
    public function updateBankBalance(int $bankAccountId, float $amount, string $type = 'deposit'): void {
        if ($type === 'deposit') {
            $this->db->execute("UPDATE bank_accounts SET balance = balance + :amt WHERE id = :id", [':amt' => $amount, ':id' => $bankAccountId]);
        } else {
            $this->db->execute("UPDATE bank_accounts SET balance = balance - :amt WHERE id = :id", [':amt' => $amount, ':id' => $bankAccountId]);
        }
    }

    public function updateCashBalance(int $cashAccountId, float $amount, string $type = 'in'): void {
        if ($type === 'in') {
            $this->db->execute("UPDATE cash_accounts SET balance = balance + :amt WHERE id = :id", [':amt' => $amount, ':id' => $cashAccountId]);
        } else {
            $this->db->execute("UPDATE cash_accounts SET balance = balance - :amt WHERE id = :id", [':amt' => $amount, ':id' => $cashAccountId]);
        }
    }

    /**
     * Vergi oranlarını çeker.
     */
    public function getTaxRate(string $name): float {
        $rows = $this->db->query("SELECT rate FROM tax_rates WHERE name = :name AND is_active = 1 LIMIT 1", [':name' => $name]);
        return isset($rows[0]) ? (float)$rows[0]['rate'] : 20.00;
    }
}
