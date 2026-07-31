<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Contracts\DatabaseInterface;
use PDO;

class ShippingRepository {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    // --- CARGO COMPANIES ---

    public function getCompany(int $id): ?array {
        $rows = $this->db->query(
            "SELECT * FROM shipping_companies WHERE id = :id AND deleted_at IS NULL LIMIT 1",
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }

    public function getCompanyByCode(string $code): ?array {
        $rows = $this->db->query(
            "SELECT * FROM shipping_companies WHERE code = :code AND deleted_at IS NULL LIMIT 1",
            [':code' => $code]
        );
        return $rows[0] ?? null;
    }

    public function createCompany(array $data): int {
        $this->db->execute(
            "INSERT INTO shipping_companies (name, code, tax_number, is_active, created_at)
             VALUES (:name, :code, :tax_number, :is_active, NOW())",
            [
                ':name' => $data['name'],
                ':code' => $data['code'],
                ':tax_number' => $data['tax_number'] ?? null,
                ':is_active' => $data['is_active'] ?? 1
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function updateCompany(int $id, array $data): bool {
        $this->db->execute(
            "UPDATE shipping_companies 
             SET name = :name, code = :code, tax_number = :tax_number, is_active = :is_active, updated_at = NOW() 
             WHERE id = :id AND deleted_at IS NULL",
            [
                ':id' => $id,
                ':name' => $data['name'],
                ':code' => $data['code'],
                ':tax_number' => $data['tax_number'] ?? null,
                ':is_active' => $data['is_active'] ?? 1
            ]
        );
        return true;
    }

    public function deleteCompany(int $id): bool {
        $this->db->execute(
            "UPDATE shipping_companies SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL",
            [':id' => $id]
        );
        return true;
    }

    public function restoreCompany(int $id): bool {
        $this->db->execute(
            "UPDATE shipping_companies SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL",
            [':id' => $id]
        );
        return true;
    }

    public function listCompanies(array $filters = [], int $page = 1, int $limit = 50): array {
        $sql = "SELECT * FROM shipping_companies WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE :search OR code LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = :is_active";
            $params[':is_active'] = (int)$filters['is_active'];
        }

        $offset = ($page - 1) * $limit;
        $sql .= " ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";

        return $this->db->query($sql, $params);
    }

    // --- CARGO SERVICES ---

    public function getService(int $id): ?array {
        $rows = $this->db->query(
            "SELECT * FROM shipping_services WHERE id = :id AND deleted_at IS NULL LIMIT 1",
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }

    public function createService(array $data): int {
        $this->db->execute(
            "INSERT INTO shipping_services (company_id, name, code, is_active, created_at)
             VALUES (:company_id, :name, :code, :is_active, NOW())",
            [
                ':company_id' => $data['company_id'],
                ':name' => $data['name'],
                ':code' => $data['code'],
                ':is_active' => $data['is_active'] ?? 1
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function deleteService(int $id): bool {
        $this->db->execute(
            "UPDATE shipping_services SET deleted_at = NOW() WHERE id = :id",
            [':id' => $id]
        );
        return true;
    }

    // --- SHIPPING ZONES & PRICES ---

    public function getZone(int $id): ?array {
        $rows = $this->db->query(
            "SELECT * FROM shipping_zones WHERE id = :id AND deleted_at IS NULL LIMIT 1",
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }

    public function createZone(array $data): int {
        $this->db->execute(
            "INSERT INTO shipping_zones (name, country_code, city_name, district_name, zip_code, is_active, created_at)
             VALUES (:name, :country_code, :city_name, :district_name, :zip_code, :is_active, NOW())",
            [
                ':name' => $data['name'],
                ':country_code' => $data['country_code'] ?? 'TR',
                ':city_name' => $data['city_name'] ?? null,
                ':district_name' => $data['district_name'] ?? null,
                ':zip_code' => $data['zip_code'] ?? null,
                ':is_active' => $data['is_active'] ?? 1
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function deleteZone(int $id): bool {
        $this->db->execute(
            "UPDATE shipping_zones SET deleted_at = NOW() WHERE id = :id",
            [':id' => $id]
        );
        return true;
    }

    public function createZonePrice(array $data): int {
        $this->db->execute(
            "INSERT INTO shipping_zone_prices (zone_id, service_id, min_desi, max_desi, price, created_at)
             VALUES (:zone_id, :service_id, :min_desi, :max_desi, :price, NOW())",
            [
                ':zone_id' => $data['zone_id'],
                ':service_id' => $data['service_id'],
                ':min_desi' => $data['min_desi'],
                ':max_desi' => $data['max_desi'],
                ':price' => $data['price']
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function getMatchingZonePrice(int $serviceId, string $countryCode, ?string $cityName, float $desi): ?array {
        $sql = "SELECT zp.* 
                FROM shipping_zone_prices zp
                JOIN shipping_zones z ON zp.zone_id = z.id
                WHERE zp.service_id = :service_id
                  AND z.country_code = :country_code
                  AND (z.city_name IS NULL OR z.city_name = :city_name)
                  AND :desi_min >= zp.min_desi
                  AND :desi_max <= zp.max_desi
                  AND z.deleted_at IS NULL
                  AND zp.deleted_at IS NULL
                ORDER BY z.city_name DESC, zp.price ASC LIMIT 1";

        $rows = $this->db->query($sql, [
            ':service_id' => $serviceId,
            ':country_code' => $countryCode,
            ':city_name' => $cityName ?? '',
            ':desi_min' => $desi,
            ':desi_max' => $desi
        ]);
        return $rows[0] ?? null;
    }

    // --- SHIPPING RULES ---

    public function createRule(array $data): int {
        $this->db->execute(
            "INSERT INTO shipping_rules (name, min_order_amount, max_order_amount, min_weight, max_weight, min_desi, max_desi, free_shipping_limit, is_active, created_at)
             VALUES (:name, :min_order_amount, :max_order_amount, :min_weight, :max_weight, :min_desi, :max_desi, :free_shipping_limit, :is_active, NOW())",
            [
                ':name' => $data['name'],
                ':min_order_amount' => $data['min_order_amount'] ?? null,
                ':max_order_amount' => $data['max_order_amount'] ?? null,
                ':min_weight' => $data['min_weight'] ?? null,
                ':max_weight' => $data['max_weight'] ?? null,
                ':min_desi' => $data['min_desi'] ?? null,
                ':max_desi' => $data['max_desi'] ?? null,
                ':free_shipping_limit' => $data['free_shipping_limit'] ?? null,
                ':is_active' => $data['is_active'] ?? 1
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function listRules(): array {
        return $this->db->query("SELECT * FROM shipping_rules WHERE deleted_at IS NULL ORDER BY id DESC");
    }

    // --- PACKAGES / GÖNDERİLER ---

    public function getPackage(int $id): ?array {
        $rows = $this->db->query(
            "SELECT p.*, s.name as service_name, c.name as company_name 
             FROM shipping_packages p
             JOIN shipping_services s ON p.service_id = s.id
             JOIN shipping_companies c ON s.company_id = c.id
             WHERE p.id = :id AND p.deleted_at IS NULL LIMIT 1",
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }

    public function getPackageByTrackingNumber(string $trackingNumber): ?array {
        $rows = $this->db->query(
            "SELECT p.*, s.name as service_name, c.name as company_name 
             FROM shipping_packages p
             JOIN shipping_services s ON p.service_id = s.id
             JOIN shipping_companies c ON s.company_id = c.id
             WHERE p.tracking_number = :track AND p.deleted_at IS NULL LIMIT 1",
            [':track' => $trackingNumber]
        );
        return $rows[0] ?? null;
    }

    public function createPackage(array $data, array $items = []): int {
        $this->db->execute(
            "INSERT INTO shipping_packages (order_id, service_id, tracking_number, desi, weight, package_count, shipping_cost, status, qr_code, barcode, created_at)
             VALUES (:order_id, :service_id, :tracking_number, :desi, :weight, :package_count, :shipping_cost, :status, :qr_code, :barcode, NOW())",
            [
                ':order_id' => $data['order_id'],
                ':service_id' => $data['service_id'],
                ':tracking_number' => $data['tracking_number'] ?? null,
                ':desi' => $data['desi'] ?? 1.0,
                ':weight' => $data['weight'] ?? 1.0,
                ':package_count' => $data['package_count'] ?? 1,
                ':shipping_cost' => $data['shipping_cost'] ?? 0.00,
                ':status' => $data['status'] ?? 'pending',
                ':qr_code' => $data['qr_code'] ?? null,
                ':barcode' => $data['barcode'] ?? null
            ]
        );
        $packageId = (int)$this->db->lastInsertId();

        foreach ($items as $item) {
            $this->db->execute(
                "INSERT INTO shipping_package_items (package_id, product_id, quantity)
                 VALUES (:package_id, :product_id, :quantity)",
                [
                    ':package_id' => $packageId,
                    ':product_id' => $item['product_id'],
                    ':quantity' => $item['quantity'] ?? 1
                ]
            );
        }

        return $packageId;
    }

    public function updatePackageStatus(int $id, string $status): bool {
        $this->db->execute(
            "UPDATE shipping_packages SET status = :status, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL",
            [':id' => $id, ':status' => $status]
        );
        return true;
    }

    public function listPackages(array $filters = [], int $page = 1, int $limit = 50): array {
        $sql = "SELECT p.*, s.name as service_name, c.name as company_name 
                FROM shipping_packages p
                JOIN shipping_services s ON p.service_id = s.id
                JOIN shipping_companies c ON s.company_id = c.id
                WHERE p.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['company_id'])) {
            $sql .= " AND c.id = :comp_id";
            $params[':comp_id'] = (int)$filters['company_id'];
        }

        $offset = ($page - 1) * $limit;
        $sql .= " ORDER BY p.id DESC LIMIT {$limit} OFFSET {$offset}";

        return $this->db->query($sql, $params);
    }

    // --- TRACKING & EVENTS ---

    public function upsertTracking(array $data): int {
        $rows = $this->db->query(
            "SELECT id FROM shipping_tracking WHERE tracking_number = :track LIMIT 1",
            [':track' => $data['tracking_number']]
        );

        if (count($rows) > 0) {
            $trackingId = (int)$rows[0]['id'];
            $this->db->execute(
                "UPDATE shipping_tracking 
                 SET latest_status = :latest_status, estimated_delivery = :est, updated_at = NOW() 
                 WHERE id = :id",
                [
                    ':id' => $trackingId,
                    ':latest_status' => $data['latest_status'],
                    ':est' => $data['estimated_delivery'] ?? null
                ]
            );
            return $trackingId;
        } else {
            $this->db->execute(
                "INSERT INTO shipping_tracking (package_id, tracking_number, latest_status, estimated_delivery, created_at)
                 VALUES (:package_id, :tracking_number, :latest_status, :est, NOW())",
                [
                    ':package_id' => $data['package_id'],
                    ':tracking_number' => $data['tracking_number'],
                    ':latest_status' => $data['latest_status'],
                    ':est' => $data['estimated_delivery'] ?? null
                ]
            );
            return (int)$this->db->lastInsertId();
        }
    }

    public function addTrackingEvent(int $trackingId, array $data): int {
        $this->db->execute(
            "INSERT INTO shipping_tracking_events (tracking_id, status, location, description, event_date, created_at)
             VALUES (:tracking_id, :status, :location, :description, :event_date, NOW())",
            [
                ':tracking_id' => $trackingId,
                ':status' => $data['status'],
                ':location' => $data['location'] ?? null,
                ':description' => $data['description'] ?? null,
                ':event_date' => $data['event_date'] ?? date('Y-m-d H:i:s')
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function getTrackingHistory(string $trackingNumber): array {
        return $this->db->query(
            "SELECT te.*, t.latest_status 
             FROM shipping_tracking_events te
             JOIN shipping_tracking t ON te.tracking_id = t.id
             WHERE t.tracking_number = :track
             ORDER BY te.event_date DESC",
            [':track' => $trackingNumber]
        );
    }

    // --- İADE YÖNETİMİ (RETURNS) ---

    public function createReturn(array $data, array $items = []): int {
        $this->db->execute(
            "INSERT INTO shipping_returns (order_id, return_number, reason, status, created_at)
             VALUES (:order_id, :return_number, :reason, :status, NOW())",
            [
                ':order_id' => $data['order_id'],
                ':return_number' => $data['return_number'],
                ':reason' => $data['reason'] ?? null,
                ':status' => $data['status'] ?? 'requested'
            ]
        );
        $returnId = (int)$this->db->lastInsertId();

        foreach ($items as $item) {
            $this->db->execute(
                "INSERT INTO shipping_return_items (return_id, product_id, quantity)
                 VALUES (:return_id, :product_id, :quantity)",
                [
                    ':return_id' => $returnId,
                    ':product_id' => $item['product_id'],
                    ':quantity' => $item['quantity'] ?? 1
                ]
            );
        }

        return $returnId;
    }

    public function updateReturnStatus(int $id, string $status): bool {
        $this->db->execute(
            "UPDATE shipping_returns SET status = :status, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL",
            [':id' => $id, ':status' => $status]
        );
        return true;
    }

    public function getReturn(int $id): ?array {
        $rows = $this->db->query(
            "SELECT * FROM shipping_returns WHERE id = :id AND deleted_at IS NULL LIMIT 1",
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }

    public function listReturns(array $filters = []): array {
        $sql = "SELECT * FROM shipping_returns WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY id DESC";
        return $this->db->query($sql, $params);
    }

    // --- INTEGRATIONS & API LOGS ---

    public function upsertIntegration(array $data): int {
        $rows = $this->db->query(
            "SELECT id FROM shipping_integrations WHERE company_id = :comp_id LIMIT 1",
            [':comp_id' => $data['company_id']]
        );

        if (count($rows) > 0) {
            $id = (int)$rows[0]['id'];
            $this->db->execute(
                "UPDATE shipping_integrations 
                 SET api_url = :url, username = :user, password = :pass, api_key = :key, is_active = :act, updated_at = NOW() 
                 WHERE id = :id",
                [
                    ':id' => $id,
                    ':url' => $data['api_url'],
                    ':user' => $data['username'] ?? null,
                    ':pass' => $data['password'] ?? null,
                    ':key' => $data['api_key'] ?? null,
                    ':act' => $data['is_active'] ?? 1
                ]
            );
            return $id;
        } else {
            $this->db->execute(
                "INSERT INTO shipping_integrations (company_id, api_url, username, password, api_key, is_active, created_at)
                 VALUES (:comp_id, :url, :user, :pass, :key, :act, NOW())",
                [
                    ':comp_id' => $data['company_id'],
                    ':url' => $data['api_url'],
                    ':user' => $data['username'] ?? null,
                    ':pass' => $data['password'] ?? null,
                    ':key' => $data['api_key'] ?? null,
                    ':act' => $data['is_active'] ?? 1
                ]
            );
            return (int)$this->db->lastInsertId();
        }
    }

    public function getIntegration(int $companyId): ?array {
        $rows = $this->db->query(
            "SELECT * FROM shipping_integrations WHERE company_id = :comp_id AND deleted_at IS NULL LIMIT 1",
            [':comp_id' => $companyId]
        );
        return $rows[0] ?? null;
    }

    // --- BULK OPERATIONS & EXPORTS ---

    public function bulkUpdatePackageStatus(array $ids, string $status): bool {
        if (empty($ids)) return false;
        $inQuery = implode(',', array_map('intval', $ids));
        $this->db->execute(
            "UPDATE shipping_packages SET status = :status, updated_at = NOW() WHERE id IN ({$inQuery}) AND deleted_at IS NULL",
            [':status' => $status]
        );
        return true;
    }
}
