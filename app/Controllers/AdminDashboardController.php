<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Contracts\DatabaseInterface;
use Core\View\View;
use App\Services\DashboardService;

class AdminDashboardController extends Controller {
    private DatabaseInterface $db;
    private DashboardService $dashboardService;

    public function __construct(View $view, DatabaseInterface $db, DashboardService $dashboardService) {
        parent::__construct($view);
        $this->db = $db;
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request, Response $response): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $adminId = $_SESSION['admin_id'] ?? null;
        $admin = null;
        if ($adminId) {
            $result = $this->db->query("SELECT * FROM admins WHERE id = :id LIMIT 1", [':id' => $adminId]);
            $admin = $result[0] ?? null;
        }

        // Get filter inputs
        $filter = $request->get('filter') ?? 'last_30_days';
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Fetch metrics
        $analytics = $this->dashboardService->getAnalytics($filter, $startDate, $endDate);

        if ($request->get('ajax') === 'realtime_feed') {
            header('Content-Type: application/json');
            echo json_encode([
                'recent_orders' => $analytics['recent_orders'] ?? [],
                'audit_logs' => $analytics['audit_logs'] ?? [],
            ]);
            exit;
        }

        return $this->render('admin/dashboard', [
            'admin' => $admin,
            'analytics' => $analytics
        ]);
    }

    public function components(Request $request, Response $response): string {
        return $this->render('admin/components/showcase', []);
    }
}
