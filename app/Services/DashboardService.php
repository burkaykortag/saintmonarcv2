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
            'chart_data' => $chartData
        ];
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
        $sql = "SELECT o.*, CONCAT(up.first_name, ' ', up.last_name) as customer_name
                FROM orders o
                JOIN user_profiles up ON o.user_id = up.user_id
                ORDER BY o.id DESC LIMIT :limit";
        // Convert limit to numeric bypass for raw execution if bindParam behaves strictly
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
