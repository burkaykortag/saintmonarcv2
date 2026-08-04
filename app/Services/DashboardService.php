<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\DatabaseInterface;
use DateTime;
use Exception;

class DashboardService {
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function getAnalytics(string $filter = 'last_30_days', ?string $startDate = null, ?string $endDate = null): array {
        list($start, $end) = $this->getDateBounds($filter, $startDate, $endDate);
        list($prevStart, $prevEnd) = $this->getPreviousPeriodBounds($filter, $start, $end);

        // 1. Current Period Sales Stats
        $salesStats = $this->getSalesStats($start, $end);
        
        // 2. Previous Period Sales Stats (for percentage changes)
        $prevSalesStats = $this->getSalesStats($prevStart, $prevEnd);
        
        // Calculate percentage changes
        $salesChange = $this->calculatePercentageChange($salesStats['total_sales'], $prevSalesStats['total_sales']);
        $orderCountChange = $this->calculatePercentageChange($salesStats['order_count'], $prevSalesStats['order_count']);
        $aovChange = $this->calculatePercentageChange($salesStats['aov'], $prevSalesStats['aov']);

        // 3. Order Status Distribution
        $orderStatusCounts = $this->getOrderStatusCounts($start, $end);

        // 4. Stock Status
        $stockStatus = $this->getStockStatus();

        // 5. Recent Orders
        $recentOrders = $this->getRecentOrders(5);

        // 6. Recent Members
        $recentMembers = $this->getRecentMembers(5);

        // 7. Category Distribution
        $categorySales = $this->getCategorySales($start, $end);

        // 8. Chart Data (Daily Sales Points inside the date range)
        $chartData = $this->getChartData($start, $end);

        // 9. Additional Real KPI Stats
        $additionalStats = $this->getAdditionalStats();

        // 10. Multi-period sales (daily / weekly / yearly)
        $multiPeriod = $this->getMultiPeriodSales();

        // 11. Workflow Stats
        $workflowStats = $this->getWorkflowStats();

        // 12. Recent Audit Logs (system activity feed)
        $auditLogs = $this->getRecentAuditLogs(10);

        return [
            'filter' => $filter,
            'bounds' => ['start' => $start, 'end' => $end],
            'sales' => [
                'total_sales' => $salesStats['total_sales'],
                'total_sales_change' => $salesChange,
                'order_count' => $salesStats['order_count'],
                'order_count_change' => $orderCountChange,
                'aov' => $salesStats['aov'],
                'aov_change' => $aovChange,
                'cost_total' => $salesStats['cost_total'],
                'profit_total' => $salesStats['total_sales'] - $salesStats['cost_total']
            ],
            'status_counts' => $orderStatusCounts,
            'stock' => $stockStatus,
            'recent_orders' => $recentOrders,
            'recent_members' => $recentMembers,
            'category_sales' => $categorySales,
            'chart_data' => $chartData,
            'procurement' => $this->getProcurementStats(),
            'additional' => $additionalStats,
            'multi_period' => $multiPeriod,
            'workflow' => $workflowStats,
            'audit_logs' => $auditLogs,
        ];
    }

    /**
     * Get additional real KPI stats:
     * - Active carts (updated within last hour)
     * - Abandoned carts (1-7 days old, still have items)
     * - Pending refunds
     * - Pending shipments
     * - New members (today / last 30 days)
     * - In-transit orders (shipped, not delivered)
     */
    private function getAdditionalStats(): array {
        try {
            // Active carts: updated in last 60 minutes
            $activeCarts = (int)($this->db->query(
                "SELECT COUNT(*) as cnt FROM carts WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 60 MINUTE)"
            )[0]['cnt'] ?? 0);

            // Abandoned carts: last updated 24h-7d ago (items still present)
            $abandonedCarts = (int)($this->db->query(
                "SELECT COUNT(DISTINCT c.id) as cnt 
                 FROM carts c 
                 JOIN cart_items ci ON c.id = ci.cart_id 
                 WHERE c.updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                 AND c.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )[0]['cnt'] ?? 0);

            // Pending refunds
            $pendingRefunds = (int)($this->db->query(
                "SELECT COUNT(*) as cnt FROM refunds WHERE status = 'pending'"
            )[0]['cnt'] ?? 0);

            // Pending shipments
            $pendingShipments = (int)($this->db->query(
                "SELECT COUNT(*) as cnt FROM shipments WHERE status = 'pending'"
            )[0]['cnt'] ?? 0);

            // New members today
            $newMembersToday = (int)($this->db->query(
                "SELECT COUNT(*) as cnt FROM users WHERE DATE(created_at) = CURDATE()"
            )[0]['cnt'] ?? 0);

            // New members last 30 days
            $newMembers30d = (int)($this->db->query(
                "SELECT COUNT(*) as cnt FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )[0]['cnt'] ?? 0);

            // Total members
            $totalMembers = (int)($this->db->query(
                "SELECT COUNT(*) as cnt FROM users"
            )[0]['cnt'] ?? 0);

            // Orders shipped but not delivered (in transit)
            $inTransitOrders = (int)($this->db->query(
                "SELECT COUNT(*) as cnt FROM orders WHERE status = 'shipped'"
            )[0]['cnt'] ?? 0);

            // Critical stock count
            $criticalStock = (int)($this->db->query(
                "SELECT COUNT(*) as count FROM products WHERE total_stock BETWEEN 1 AND critical_stock AND deleted_at IS NULL"
            )[0]['count'] ?? 0);

            return [
                'active_carts'       => $activeCarts,
                'abandoned_carts'    => $abandonedCarts,
                'pending_refunds'    => $pendingRefunds,
                'pending_shipments'  => $pendingShipments,
                'new_members_today'  => $newMembersToday,
                'new_members_30d'    => $newMembers30d,
                'total_members'      => $totalMembers,
                'in_transit_orders'  => $inTransitOrders,
                'critical_stock'     => $criticalStock,
            ];
        } catch (\Throwable $t) {
            return [
                'active_carts'       => 0,
                'abandoned_carts'    => 0,
                'pending_refunds'    => 0,
                'pending_shipments'  => 0,
                'new_members_today'  => 0,
                'new_members_30d'    => 0,
                'total_members'      => 0,
                'in_transit_orders'  => 0,
                'critical_stock'     => 0,
            ];
        }
    }

    /**
     * Get multi-period sales breakdown (daily, weekly, monthly, yearly totals)
     */
    private function getMultiPeriodSales(): array {
        try {
            $daily = (float)($this->db->query(
                "SELECT COALESCE(SUM(grand_total), 0) as total 
                 FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'"
            )[0]['total'] ?? 0.0);

            $weekly = (float)($this->db->query(
                "SELECT COALESCE(SUM(grand_total), 0) as total 
                 FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status != 'cancelled'"
            )[0]['total'] ?? 0.0);

            $monthly = (float)($this->db->query(
                "SELECT COALESCE(SUM(grand_total), 0) as total 
                 FROM orders WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW()) AND status != 'cancelled'"
            )[0]['total'] ?? 0.0);

            $yearly = (float)($this->db->query(
                "SELECT COALESCE(SUM(grand_total), 0) as total 
                 FROM orders WHERE YEAR(created_at) = YEAR(NOW()) AND status != 'cancelled'"
            )[0]['total'] ?? 0.0);

            // Prev week for change calculation
            $prevWeekly = (float)($this->db->query(
                "SELECT COALESCE(SUM(grand_total), 0) as total 
                 FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) 
                 AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) AND status != 'cancelled'"
            )[0]['total'] ?? 0.0);

            return [
                'daily'          => $daily,
                'weekly'         => $weekly,
                'monthly'        => $monthly,
                'yearly'         => $yearly,
                'weekly_change'  => $this->calculatePercentageChange($weekly, $prevWeekly),
            ];
        } catch (\Throwable $t) {
            return [
                'daily'         => 0.0,
                'weekly'        => 0.0,
                'monthly'       => 0.0,
                'yearly'        => 0.0,
                'weekly_change' => 0.0,
            ];
        }
    }

    /**
     * Get workflow statistics from workflows + workflow_logs tables
     */
    private function getWorkflowStats(): array {
        try {
            $totalWorkflows = (int)($this->db->query(
                "SELECT COUNT(*) as cnt FROM workflows WHERE deleted_at IS NULL"
            )[0]['cnt'] ?? 0);

            $activeWorkflows = (int)($this->db->query(
                "SELECT COUNT(*) as cnt FROM workflows WHERE status = 'active' AND deleted_at IS NULL"
            )[0]['cnt'] ?? 0);

            $totalExecutions = (int)($this->db->query(
                "SELECT COUNT(*) as cnt FROM workflow_logs"
            )[0]['cnt'] ?? 0);

            // Success rate: logs with level='error' vs total
            $errorCount = (int)($this->db->query(
                "SELECT COUNT(*) as cnt FROM workflow_logs WHERE level = 'error'"
            )[0]['cnt'] ?? 0);

            $successRate = $totalExecutions > 0
                ? round((($totalExecutions - $errorCount) / $totalExecutions) * 100, 1)
                : 100.0;

            // Executions today
            $todayExecutions = (int)($this->db->query(
                "SELECT COUNT(*) as cnt FROM workflow_logs WHERE DATE(created_at) = CURDATE()"
            )[0]['cnt'] ?? 0);

            return [
                'total'            => $totalWorkflows,
                'active'           => $activeWorkflows,
                'total_executions' => $totalExecutions,
                'today_executions' => $todayExecutions,
                'success_rate'     => $successRate,
                'error_count'      => $errorCount,
            ];
        } catch (\Throwable $t) {
            return [
                'total'            => 0,
                'active'           => 0,
                'total_executions' => 0,
                'today_executions' => 0,
                'success_rate'     => 0.0,
                'error_count'      => 0,
            ];
        }
    }

    /**
     * Get recent audit log entries for the system activity feed
     */
    private function getRecentAuditLogs(int $limit = 10): array {
        try {
            return $this->db->query(
                "SELECT al.event, al.auditable_type, al.auditable_id, al.ip_address, al.created_at
                 FROM audit_logs al
                 ORDER BY al.created_at DESC LIMIT {$limit}"
            );
        } catch (\Throwable $t) {
            return [];
        }
    }

    private function getProcurementStats(): array {
        try {
            $totalPurchasing = $this->db->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM purchase_orders WHERE status = 'completed' AND deleted_at IS NULL")[0]['total'] ?? 0;
            $pendingPOs = $this->db->query("SELECT COUNT(*) as cnt FROM purchase_orders WHERE status IN ('pending_approval', 'approved', 'sent') AND deleted_at IS NULL")[0]['cnt'] ?? 0;
            $pendingDeliveries = $this->db->query("SELECT COUNT(*) as cnt FROM purchase_orders WHERE status = 'sent' AND deleted_at IS NULL")[0]['cnt'] ?? 0;
            $delayedOrders = $this->db->query("SELECT COUNT(*) as cnt FROM purchase_orders WHERE status = 'sent' AND expected_delivery < CURDATE() AND deleted_at IS NULL")[0]['cnt'] ?? 0;
            
            $bestSupplier = $this->db->query("SELECT company_name FROM suppliers WHERE deleted_at IS NULL ORDER BY score DESC LIMIT 1")[0]['company_name'] ?? 'Tedarikçi Yok';
            $riskySupplier = $this->db->query("SELECT company_name FROM suppliers WHERE deleted_at IS NULL ORDER BY score ASC LIMIT 1")[0]['company_name'] ?? 'Tedarikçi Yok';

            return [
                'total_purchasing' => (float)$totalPurchasing,
                'pending_pos' => (int)$pendingPOs,
                'pending_deliveries' => (int)$pendingDeliveries,
                'delayed_orders' => (int)$delayedOrders,
                'best_supplier' => $bestSupplier,
                'risky_supplier' => $riskySupplier
            ];
        } catch (\Throwable $t) {
            return [
                'total_purchasing' => 0.0,
                'pending_pos' => 0,
                'pending_deliveries' => 0,
                'delayed_orders' => 0,
                'best_supplier' => 'Yok',
                'risky_supplier' => 'Yok'
            ];
        }
    }

    private function getDateBounds(string $filter, ?string $startDate, ?string $endDate): array {
        $end = date('Y-m-d H:i:s');
        $start = date('Y-m-d H:i:s', strtotime('-30 days'));

        switch ($filter) {
            case 'today':
                $start = date('Y-m-d 00:00:00');
                $end = date('Y-m-d 23:59:59');
                break;
            case 'yesterday':
                $start = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $end = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;
            case 'last_7_days':
                $start = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case 'last_30_days':
                $start = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            case 'this_month':
                $start = date('Y-m-01 00:00:00');
                break;
            case 'this_year':
                $start = date('Y-01-01 00:00:00');
                break;
            case 'custom':
                if ($startDate) {
                    $start = date('Y-m-d 00:00:00', strtotime($startDate));
                }
                if ($endDate) {
                    $end = date('Y-m-d 23:59:59', strtotime($endDate));
                }
                break;
        }

        return [$start, $end];
    }

    private function getPreviousPeriodBounds(string $filter, string $start, string $end): array {
        $startDate = new DateTime($start);
        $endDate = new DateTime($end);
        $diff = $endDate->getTimestamp() - $startDate->getTimestamp();

        $prevEnd = date('Y-m-d H:i:s', $startDate->getTimestamp() - 1);
        $prevStart = date('Y-m-d H:i:s', $startDate->getTimestamp() - $diff);

        return [$prevStart, $prevEnd];
    }

    private function getSalesStats(string $start, string $end): array {
        $sql = "SELECT COUNT(*) as order_count, SUM(grand_total) as total_sales
                FROM orders 
                WHERE created_at BETWEEN :start AND :end AND status != 'cancelled'";
        
        $row = $this->db->query($sql, [':start' => $start, ':end' => $end])[0] ?? null;
        
        $totalSales = (float)($row['total_sales'] ?? 0.0);
        $orderCount = (int)($row['order_count'] ?? 0);
        $aov = $orderCount > 0 ? ($totalSales / $orderCount) : 0.0;

        // Calculate Cost of Goods Sold (COGS) dynamically if possible
        $cogsSql = "SELECT SUM(oi.quantity * COALESCE(p.cost_price, 0)) as cost_total
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    JOIN products p ON oi.product_id = p.id
                    WHERE o.created_at BETWEEN :start AND :end AND o.status != 'cancelled'";
        $cogsRow = $this->db->query($cogsSql, [':start' => $start, ':end' => $end])[0] ?? null;
        $costTotal = (float)($cogsRow['cost_total'] ?? 0.0);

        return [
            'total_sales' => $totalSales,
            'order_count' => $orderCount,
            'aov' => $aov,
            'cost_total' => $costTotal
        ];
    }

    private function getOrderStatusCounts(string $start, string $end): array {
        $sql = "SELECT status, COUNT(*) as count 
                FROM orders 
                WHERE created_at BETWEEN :start AND :end
                GROUP BY status";
        
        $rows = $this->db->query($sql, [':start' => $start, ':end' => $end]);
        
        $statuses = [
            'pending' => 0,
            'processing' => 0,
            'shipped' => 0,
            'delivered' => 0,
            'cancelled' => 0,
            'refunded' => 0
        ];

        foreach ($rows as $row) {
            $status = strtolower($row['status']);
            if (array_key_exists($status, $statuses)) {
                $statuses[$status] = (int)$row['count'];
            }
        }

        return $statuses;
    }

    private function getStockStatus(): array {
        // Out of stock (stock = 0)
        $outOfStockRow = $this->db->query("SELECT COUNT(*) as count FROM inventories WHERE stock = 0")[0] ?? null;
        $outOfStock = (int)($outOfStockRow['count'] ?? 0);

        // Critical stock (stock between 1 and 5)
        $criticalStockRow = $this->db->query("SELECT COUNT(*) as count FROM products WHERE total_stock BETWEEN 1 AND critical_stock AND deleted_at IS NULL")[0] ?? null;
        $criticalStock = (int)($criticalStockRow['count'] ?? 0);

        // Total products count
        $totalProductsRow = $this->db->query("SELECT COUNT(*) as count FROM products WHERE deleted_at IS NULL")[0] ?? null;
        $totalProducts = (int)($totalProductsRow['count'] ?? 0);

        // Active products count
        $activeProductsRow = $this->db->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1 AND status = 'active' AND deleted_at IS NULL")[0] ?? null;
        $activeProducts = (int)($activeProductsRow['count'] ?? 0);

        // Passive products count
        $passiveProductsRow = $this->db->query("SELECT COUNT(*) as count FROM products WHERE (is_active = 0 OR status = 'passive') AND deleted_at IS NULL")[0] ?? null;
        $passiveProducts = (int)($passiveProductsRow['count'] ?? 0);

        // Draft products count
        $draftProductsRow = $this->db->query("SELECT COUNT(*) as count FROM products WHERE status = 'draft' AND deleted_at IS NULL")[0] ?? null;
        $draftProducts = (int)($draftProductsRow['count'] ?? 0);

        // Top Selling Products
        $topSellingSql = "SELECT p.id, pt.name, SUM(oi.quantity) as sales_count, p.price
                          FROM order_items oi
                          JOIN products p ON oi.product_id = p.id
                          JOIN product_translations pt ON p.id = pt.product_id
                          WHERE p.deleted_at IS NULL
                          GROUP BY p.id, pt.name, p.price
                          ORDER BY sales_count DESC LIMIT 5";
        $topSelling = $this->db->query($topSellingSql);

        // Top Viewed (using dummy/view count mock since schema doesn't have views table, fallback to top selling)
        $topViewed = $topSelling;

        // Newly added products
        $newlyAddedSql = "SELECT p.id, pt.name, p.price, p.created_at
                          FROM products p
                          JOIN product_translations pt ON p.id = pt.product_id
                          WHERE p.deleted_at IS NULL
                          ORDER BY p.id DESC LIMIT 5";
        $newlyAdded = $this->db->query($newlyAddedSql);

        return [
            'out_of_stock' => $outOfStock,
            'critical_stock' => $criticalStock,
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'passive_products' => $passiveProducts,
            'draft_products' => $draftProducts,
            'top_selling' => $topSelling,
            'top_viewed' => $topViewed,
            'newly_added' => $newlyAdded
        ];
    }

    private function getRecentOrders(int $limit): array {
        return $this->db->query("SELECT o.*, CONCAT(up.first_name, ' ', up.last_name) as customer_name
                                 FROM orders o
                                 JOIN user_profiles up ON o.user_id = up.user_id
                                 ORDER BY o.id DESC LIMIT {$limit}");
    }

    private function getRecentMembers(int $limit): array {
        return $this->db->query("SELECT u.id, u.email, u.created_at, CONCAT(up.first_name, ' ', up.last_name) as name, up.phone
                                 FROM users u
                                 JOIN user_profiles up ON u.id = up.user_id
                                 ORDER BY u.id DESC LIMIT {$limit}");
    }

    private function getCategorySales(string $start, string $end): array {
        $sql = "SELECT ct.name as category_name, SUM(oi.total) as total_sales
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN product_category_relations pcr ON oi.product_id = pcr.product_id
                JOIN categories c ON pcr.category_id = c.id
                JOIN category_translations ct ON c.id = ct.category_id
                WHERE o.created_at BETWEEN :start AND :end AND o.status != 'cancelled'
                GROUP BY ct.name
                ORDER BY total_sales DESC";
        return $this->db->query($sql, [':start' => $start, ':end' => $end]);
    }

    private function getChartData(string $start, string $end): array {
        $sql = "SELECT DATE(created_at) as date, SUM(grand_total) as sales, COUNT(*) as orders
                FROM orders
                WHERE created_at BETWEEN :start AND :end AND status != 'cancelled'
                GROUP BY DATE(created_at)
                ORDER BY DATE(created_at) ASC";
        
        $rows = $this->db->query($sql, [':start' => $start, ':end' => $end]);
        
        $labels = [];
        $sales = [];
        $orders = [];

        foreach ($rows as $row) {
            $labels[] = date('d M', strtotime($row['date']));
            $sales[] = (float)$row['sales'];
            $orders[] = (int)$row['orders'];
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'orders' => $orders
        ];
    }

    private function calculatePercentageChange(float $current, float $previous): float {
        if ($previous == 0.0) {
            return $current > 0.0 ? 100.0 : 0.0;
        }
        return (($current - $previous) / $previous) * 100.0;
    }
}
