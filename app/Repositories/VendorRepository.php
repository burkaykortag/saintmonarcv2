<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database\Database;
use PDO;

class VendorRepository
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // --- VENDOR CRUD ---

    public function createVendor(array $data): int
    {
        $this->db->query(
            "INSERT INTO vendors (name, slug, logo, banner, rating, email, phone, status, commission_type, commission_rate, notes, created_at)
             VALUES (:name, :slug, :logo, :banner, :rating, :email, :phone, :status, :commission_type, :commission_rate, :notes, NOW())",
            [
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':logo' => $data['logo'] ?? null,
                ':banner' => $data['banner'] ?? null,
                ':rating' => $data['rating'] ?? 5.00,
                ':email' => $data['email'],
                ':phone' => $data['phone'] ?? null,
                ':status' => $data['status'] ?? 'pending',
                ':commission_type' => $data['commission_type'] ?? 'percentage',
                ':commission_rate' => $data['commission_rate'] ?? 10.00,
                ':notes' => $data['notes'] ?? null
            ]
        );
        $vendorId = (int)$this->db->lastInsertId();

        // Initialize wallet
        $this->db->query(
            "INSERT INTO vendor_wallet (vendor_id, balance, pending_payout, created_at) VALUES (:vid, 0, 0, NOW())",
            [':vid' => $vendorId]
        );

        // Initialize statistics
        $this->db->query(
            "INSERT INTO vendor_statistics (vendor_id, total_sales, total_orders, total_earnings, total_commission, active_products, created_at) 
             VALUES (:vid, 0, 0, 0, 0, 0, NOW())",
            [':vid' => $vendorId]
        );

        return $vendorId;
    }

    public function getVendor(int $id): ?array
    {
        $stmt = $this->db->query("SELECT * FROM vendors WHERE id = :id AND deleted_at IS NULL LIMIT 1", [':id' => $id]);
        return $stmt[0] ?? null;
    }

    public function updateVendor(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $val) {
            $fields[] = "`$key` = :$key";
            $params[":$key"] = $val;
        }
        if (empty($fields)) {
            return false;
        }
        $this->db->query("UPDATE vendors SET " . implode(', ', $fields) . " WHERE id = :id", $params);
        return true;
    }

    public function deleteVendor(int $id): bool
    {
        $this->db->query("UPDATE vendors SET deleted_at = NOW() WHERE id = :id", [':id' => $id]);
        return true;
    }

    public function listVendors(array $filters = []): array
    {
        $sql = "SELECT * FROM vendors WHERE deleted_at IS NULL";
        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        $sql .= " ORDER BY id DESC";
        return $this->db->query($sql, $params);
    }

    // --- VENDOR USER ---

    public function createVendorUser(array $data): int
    {
        $this->db->query(
            "INSERT INTO vendor_users (vendor_id, username, password, email, role, status, created_at)
             VALUES (:vendor_id, :username, :password, :email, :role, :status, NOW())",
            [
                ':vendor_id' => $data['vendor_id'],
                ':username' => $data['username'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':email' => $data['email'],
                ':role' => $data['role'] ?? 'owner',
                ':status' => $data['status'] ?? 1
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function getVendorUserByUsername(string $username): ?array
    {
        $stmt = $this->db->query("SELECT * FROM vendor_users WHERE username = :u AND deleted_at IS NULL LIMIT 1", [':u' => $username]);
        return $stmt[0] ?? null;
    }

    // --- VENDOR PRODUCTS ---

    public function associateProduct(int $vendorId, int $productId): bool
    {
        $this->db->query(
            "INSERT IGNORE INTO vendor_products (vendor_id, product_id, created_at) VALUES (:vid, :pid, NOW())",
            [':vid' => $vendorId, ':pid' => $productId]
        );
        return true;
    }

    public function listVendorProducts(int $vendorId): array
    {
        return $this->db->query(
            "SELECT p.*, pt.name 
             FROM products p
             JOIN vendor_products vp ON p.id = vp.product_id
             LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE vp.vendor_id = :vid AND p.deleted_at IS NULL",
            [':vid' => $vendorId]
        );
    }

    // --- WALLET & TRANSACTIONS ---

    public function getWallet(int $vendorId): ?array
    {
        $stmt = $this->db->query("SELECT * FROM vendor_wallet WHERE vendor_id = :vid LIMIT 1", [':vid' => $vendorId]);
        return $stmt[0] ?? null;
    }

    public function addWalletTransaction(array $data): int
    {
        $this->db->query(
            "INSERT INTO vendor_wallet_transactions (vendor_id, type, amount, reference_type, reference_id, description, created_at)
             VALUES (:vid, :type, :amount, :ref_type, :ref_id, :desc, NOW())",
            [
                ':vid' => $data['vendor_id'],
                ':type' => $data['type'],
                ':amount' => $data['amount'],
                ':ref_type' => $data['reference_type'],
                ':ref_id' => $data['reference_id'],
                ':desc' => $data['description'] ?? null
            ]
        );

        // Update balance in wallet
        if ($data['type'] === 'credit') {
            $this->db->query(
                "UPDATE vendor_wallet SET balance = balance + :amount WHERE vendor_id = :vid",
                [':amount' => $data['amount'], ':vid' => $data['vendor_id']]
            );
        } else {
            $this->db->query(
                "UPDATE vendor_wallet SET balance = balance - :amount WHERE vendor_id = :vid",
                [':amount' => $data['amount'], ':vid' => $data['vendor_id']]
            );
        }

        return (int)$this->db->lastInsertId();
    }

    // --- PAYMENTS ---

    public function createPayment(array $data): int
    {
        $this->db->query(
            "INSERT INTO vendor_payments (vendor_id, bank_account_id, amount, status, created_at)
             VALUES (:vid, :bank_id, :amount, :status, NOW())",
            [
                ':vid' => $data['vendor_id'],
                ':bank_id' => $data['bank_account_id'],
                ':amount' => $data['amount'],
                ':status' => $data['status'] ?? 'pending'
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function listPayments(int $vendorId = null): array
    {
        $sql = "SELECT p.*, v.name as vendor_name, b.iban, b.bank_name 
                FROM vendor_payments p
                JOIN vendors v ON p.vendor_id = v.id
                JOIN vendor_bank_accounts b ON p.bank_account_id = b.id";
        $params = [];
        if ($vendorId) {
            $sql .= " WHERE p.vendor_id = :vid";
            $params[':vid'] = $vendorId;
        }
        $sql .= " ORDER BY p.id DESC";
        return $this->db->query($sql, $params);
    }

    // --- COMMISSIONS ---

    public function createCommission(array $data): int
    {
        $this->db->query(
            "INSERT INTO vendor_commissions (vendor_id, order_id, rate, calculated_amount, status, created_at)
             VALUES (:vid, :oid, :rate, :amount, :status, NOW())",
            [
                ':vid' => $data['vendor_id'],
                ':oid' => $data['order_id'],
                ':rate' => $data['rate'],
                ':amount' => $data['calculated_amount'],
                ':status' => $data['status'] ?? 'pending'
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    // --- STATISTICS ---

    public function getStatistics(int $vendorId): ?array
    {
        $stmt = $this->db->query("SELECT * FROM vendor_statistics WHERE vendor_id = :vid LIMIT 1", [':vid' => $vendorId]);
        return $stmt[0] ?? null;
    }

    public function updateStatistics(int $vendorId, array $data): bool
    {
        $fields = [];
        $params = [':vid' => $vendorId];
        foreach ($data as $key => $val) {
            $fields[] = "`$key` = :$key";
            $params[":$key"] = $val;
        }
        if (empty($fields)) {
            return false;
        }
        $this->db->query("UPDATE vendor_statistics SET " . implode(', ', $fields) . " WHERE vendor_id = :vid", $params);
        return true;
    }

    // --- BANK ACCOUNT ---

    public function createBankAccount(array $data): int
    {
        $this->db->query(
            "INSERT INTO vendor_bank_accounts (vendor_id, bank_name, account_holder, iban, status, created_at)
             VALUES (:vid, :bank, :holder, :iban, :status, NOW())",
            [
                ':vid' => $data['vendor_id'],
                ':bank' => $data['bank_name'],
                ':holder' => $data['account_holder'],
                ':iban' => $data['iban'],
                ':status' => $data['status'] ?? 1
            ]
        );
        return (int)$this->db->lastInsertId();
    }
}
