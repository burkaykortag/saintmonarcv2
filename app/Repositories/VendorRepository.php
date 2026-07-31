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

    // --- VENDOR ONBOARDING APPLICATIONS ---

    public function createApplication(array $data): int
    {
        $this->db->query(
            "INSERT INTO vendor_applications (company_name, contact_name, email, phone, tax_number, tax_office, city, district, address, iban, category, status, created_at)
             VALUES (:company_name, :contact_name, :email, :phone, :tax_number, :tax_office, :city, :district, :address, :iban, :category, 'pending', NOW())",
            [
                ':company_name' => $data['company_name'],
                ':contact_name' => $data['contact_name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'],
                ':tax_number' => $data['tax_number'] ?? null,
                ':tax_office' => $data['tax_office'] ?? null,
                ':city' => $data['city'] ?? null,
                ':district' => $data['district'] ?? null,
                ':address' => $data['address'] ?? null,
                ':iban' => $data['iban'] ?? null,
                ':category' => $data['category'] ?? null
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function getApplications(array $filters = []): array
    {
        $sql = "SELECT * FROM vendor_applications WHERE 1=1";
        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        $sql .= " ORDER BY id DESC";
        return $this->db->query($sql, $params);
    }

    public function getApplication(int $id): ?array
    {
        $res = $this->db->query("SELECT * FROM vendor_applications WHERE id = :id LIMIT 1", [':id' => $id]);
        return $res[0] ?? null;
    }

    public function updateApplicationStatus(int $id, string $status, ?string $reason = null): bool
    {
        $this->db->query(
            "UPDATE vendor_applications SET status = :status, rejection_reason = :reason, updated_at = NOW() WHERE id = :id",
            [':status' => $status, ':reason' => $reason, ':id' => $id]
        );
        return true;
    }

    // --- SPLIT ORDERS (VENDOR ORDERS) ---

    public function createVendorOrder(array $data): int
    {
        $this->db->query(
            "INSERT INTO vendor_orders (vendor_id, order_id, order_number, item_price, quantity, subtotal, tax_total, commission_rate, commission_amount, payout_amount, status, created_at)
             VALUES (:vid, :oid, :onum, :price, :qty, :sub, :tax, :crate, :camount, :pamount, :status, NOW())",
            [
                ':vid' => $data['vendor_id'],
                ':oid' => $data['order_id'],
                ':onum' => $data['order_number'],
                ':price' => $data['item_price'],
                ':qty' => $data['quantity'],
                ':sub' => $data['subtotal'],
                ':tax' => $data['tax_total'] ?? 0.00,
                ':crate' => $data['commission_rate'] ?? 10.00,
                ':camount' => $data['commission_amount'] ?? 0.00,
                ':pamount' => $data['payout_amount'] ?? 0.00,
                ':status' => $data['status'] ?? 'pending'
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function listVendorOrders(int $vendorId, array $filters = []): array
    {
        $sql = "SELECT vo.*, o.billing_first_name, o.billing_last_name, o.created_at as order_date
                FROM vendor_orders vo
                JOIN orders o ON vo.order_id = o.id
                WHERE vo.vendor_id = :vid";
        $params = [':vid' => $vendorId];

        if (!empty($filters['status'])) {
            $sql .= " AND vo.status = :status";
            $params[':status'] = $filters['status'];
        }
        $sql .= " ORDER BY vo.id DESC";
        return $this->db->query($sql, $params);
    }

    public function getVendorOrderById(int $vendorId, int $vendorOrderId): ?array
    {
        $sql = "SELECT vo.*, o.billing_first_name, o.billing_last_name, o.billing_address, o.billing_city, o.shipping_address, o.shipping_city
                FROM vendor_orders vo
                JOIN orders o ON vo.order_id = o.id
                WHERE vo.id = :void AND vo.vendor_id = :vid LIMIT 1";
        $res = $this->db->query($sql, [':void' => $vendorOrderId, ':vid' => $vendorId]);
        return $res[0] ?? null;
    }

    public function updateVendorOrderStatus(int $vendorId, int $vendorOrderId, string $status, ?string $trackingNumber = null, ?string $cargoCompany = null): bool
    {
        $sql = "UPDATE vendor_orders SET status = :status";
        $params = [':status' => $status, ':void' => $vendorOrderId, ':vid' => $vendorId];

        if ($trackingNumber) {
            $sql .= ", tracking_number = :tn";
            $params[':tn'] = $trackingNumber;
        }
        if ($cargoCompany) {
            $sql .= ", cargo_company = :cc";
            $params[':cc'] = $cargoCompany;
        }
        $sql .= " WHERE id = :void AND vendor_id = :vid";

        $this->db->query($sql, $params);
        return true;
    }

    // --- PAYOUT REQUESTS ---

    public function createPayoutRequest(array $data): int
    {
        $this->db->query(
            "INSERT INTO vendor_payouts (vendor_id, amount, iban, bank_name, account_holder, status, notes, created_at)
             VALUES (:vid, :amount, :iban, :bname, :aholder, 'pending', :notes, NOW())",
            [
                ':vid' => $data['vendor_id'],
                ':amount' => $data['amount'],
                ':iban' => $data['iban'],
                ':bname' => $data['bank_name'] ?? null,
                ':aholder' => $data['account_holder'] ?? null,
                ':notes' => $data['notes'] ?? null
            ]
        );
        $payoutId = (int)$this->db->lastInsertId();

        // Increment pending_payout in wallet
        $this->db->query(
            "UPDATE vendor_wallet SET pending_payout = pending_payout + :amount WHERE vendor_id = :vid",
            [':amount' => $data['amount'], ':vid' => $data['vendor_id']]
        );

        return $payoutId;
    }

    public function getPayouts(int $vendorId = null): array
    {
        $sql = "SELECT vp.*, v.name as vendor_name 
                FROM vendor_payouts vp
                JOIN vendors v ON vp.vendor_id = v.id";
        $params = [];
        if ($vendorId) {
            $sql .= " WHERE vp.vendor_id = :vid";
            $params[':vid'] = $vendorId;
        }
        $sql .= " ORDER BY vp.id DESC";
        return $this->db->query($sql, $params);
    }

    public function updatePayoutStatus(int $payoutId, string $status, ?string $receiptFile = null): bool
    {
        $payout = $this->db->query("SELECT * FROM vendor_payouts WHERE id = :id LIMIT 1", [':id' => $payoutId]);
        if (empty($payout)) return false;

        $oldStatus = $payout[0]['status'];
        $amount = (float)$payout[0]['amount'];
        $vendorId = (int)$payout[0]['vendor_id'];

        $sql = "UPDATE vendor_payouts SET status = :status, updated_at = NOW()";
        $params = [':status' => $status, ':id' => $payoutId];
        if ($receiptFile !== null) {
            $sql .= ", receipt_file = :receipt";
            $params[':receipt'] = $receiptFile;
        }
        $sql .= " WHERE id = :id";

        $this->db->execute($sql, $params);

        if ($oldStatus === 'pending' && $status === 'paid') {
            // Deduct pending_payout in wallet
            $this->db->execute(
                "UPDATE vendor_wallet 
                 SET pending_payout = GREATEST(0, pending_payout - :amount),
                     last_payout_at = NOW()
                 WHERE vendor_id = :vid",
                [':amount' => $amount, ':vid' => $vendorId]
            );

            // Log wallet transaction (addWalletTransaction will debit balance from 900 -> 400)
            $this->addWalletTransaction([
                'vendor_id' => $vendorId,
                'type' => 'debit',
                'amount' => $amount,
                'reference_type' => 'payout',
                'reference_id' => $payoutId,
                'description' => 'Satıcı hakediş ödemesi (Paid)'
            ]);
        } elseif ($oldStatus === 'pending' && $status === 'rejected') {
            // Revert pending_payout
            $this->db->execute(
                "UPDATE vendor_wallet SET pending_payout = GREATEST(0, pending_payout - :amount) WHERE vendor_id = :vid",
                [':amount' => $amount, ':vid' => $vendorId]
            );
        }

        return true;
    }

    // --- PRODUCT MODERATION ---

    public function getPendingProducts(): array
    {
        return $this->db->query(
            "SELECT p.*, pt.name, v.name as vendor_name, b.name as brand_name
             FROM products p
             LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             LEFT JOIN vendors v ON p.vendor_id = v.id
             LEFT JOIN brands b ON p.brand_id = b.id
             WHERE p.approval_status = 'pending_review' AND p.deleted_at IS NULL
             ORDER BY p.id DESC"
        );
    }

    public function updateProductApprovalStatus(int $productId, string $status): bool
    {
        $this->db->query(
            "UPDATE products SET approval_status = :status, updated_at = NOW() WHERE id = :id",
            [':status' => $status, ':id' => $productId]
        );
        return true;
    }
}
