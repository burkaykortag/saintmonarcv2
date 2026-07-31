<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\View\View;
use App\Services\VendorService;
use Exception;

class MarketplaceAdminController extends Controller {
    private VendorService $vendorService;

    public function __construct(View $view, VendorService $vendorService) {
        parent::__construct($view);
        $this->vendorService = $vendorService;
    }

    /**
     * Marketplace Overview Dashboard (Platform Admin: Burkay)
     */
    public function dashboard(Request $request, Response $response): string {
        $vendors = $this->vendorService->listVendors();
        $applications = $this->vendorService->getApplications(['status' => 'pending']);
        $pendingProducts = $this->vendorService->getPendingProducts();
        $payouts = $this->vendorService->getPayouts();

        $platformStats = [
            'total_vendors' => count($vendors),
            'active_vendors' => count(array_filter($vendors, fn($v) => $v['status'] === 'active')),
            'pending_applications' => count($applications),
            'pending_products' => count($pendingProducts),
            'pending_payouts' => count(array_filter($payouts, fn($p) => $p['status'] === 'pending'))
        ];

        return $this->render('admin/marketplace/dashboard', [
            'platformStats' => $platformStats,
            'vendors' => $vendors,
            'applications' => $applications,
            'pendingProducts' => $pendingProducts
        ]);
    }

    /**
     * Vendor Applications / Onboarding List
     */
    public function applications(Request $request, Response $response): string {
        $applications = $this->vendorService->getApplications();
        return $this->render('admin/marketplace/applications', [
            'applications' => $applications
        ]);
    }

    /**
     * Approve Vendor Application
     */
    public function approveApplication(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $vendorId = $this->vendorService->approveApplication($id);
            $response->redirect(url('/admin/marketplace/applications?success=' . urlencode('Satıcı başvurusu onaylandı ve mağaza hesabı oluşturuldu (Vendor ID: ' . $vendorId . ')')));
        } catch (Exception $e) {
            $response->redirect(url('/admin/marketplace/applications?error=' . urlencode($e->getMessage())));
        }
    }

    /**
     * Reject Vendor Application
     */
    public function rejectApplication(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        $reason = $request->post('reason') ?? 'Başvuru koşulları karşılanmadı.';
        try {
            $this->vendorService->rejectApplication($id, $reason);
            $response->redirect(url('/admin/marketplace/applications?success=' . urlencode('Satıcı başvurusu reddedildi.')));
        } catch (Exception $e) {
            $response->redirect(url('/admin/marketplace/applications?error=' . urlencode($e->getMessage())));
        }
    }

    /**
     * Product Moderation Center
     */
    public function moderation(Request $request, Response $response): string {
        $pendingProducts = $this->vendorService->getPendingProducts();
        return $this->render('admin/marketplace/moderation', [
            'products' => $pendingProducts
        ]);
    }

    /**
     * Moderate Product Action (Approve / Reject)
     */
    public function moderateProductAction(Request $request, Response $response): void {
        $productId = (int)$request->post('product_id');
        $status = $request->post('status') ?? 'approved';
        try {
            $this->vendorService->moderateProduct($productId, $status);
            $response->redirect(url('/admin/marketplace/moderation?success=' . urlencode('Ürün onay durumu güncellendi: ' . $status)));
        } catch (Exception $e) {
            $response->redirect(url('/admin/marketplace/moderation?error=' . urlencode($e->getMessage())));
        }
    }

    /**
     * Payout Requests Management
     */
    public function payouts(Request $request, Response $response): string {
        $payouts = $this->vendorService->getPayouts();
        return $this->render('admin/marketplace/payouts', [
            'payouts' => $payouts
        ]);
    }

    /**
     * Process Payout Action
     */
    public function processPayoutAction(Request $request, Response $response): void {
        $payoutId = (int)$request->post('payout_id');
        $status = $request->post('status') ?? 'paid';
        $receipt = $request->post('receipt_file') ?? null;
        try {
            $this->vendorService->processPayoutStatus($payoutId, $status, $receipt);
            $response->redirect(url('/admin/marketplace/payouts?success=' . urlencode('Hakediş ödeme durumu güncellendi.')));
        } catch (Exception $e) {
            $response->redirect(url('/admin/marketplace/payouts?error=' . urlencode($e->getMessage())));
        }
    }
}
