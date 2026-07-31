<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\View\View;
use App\Services\VendorService;
use Exception;

class VendorController extends Controller
{
    private VendorService $service;

    public function __construct(View $view, VendorService $service)
    {
        parent::__construct($view);
        $this->service = $service;
    }

    // --- ADMIN BACKOFFICE ACTIONS ---

    public function index(Request $request, Response $response)
    {
        $vendors = $this->service->listVendors();
        return $this->render('admin/vendors/index', [
            'vendors' => $vendors
        ]);
    }

    public function create(Request $request, Response $response)
    {
        return $this->render('admin/vendors/create');
    }

    public function store(Request $request, Response $response)
    {
        try {
            $data = $request->getBody();
            $vendorId = $this->service->createVendor($data);
            return $response->redirect(url("/admin/vendors?success=1"));
        } catch (Exception $e) {
            return $this->render('admin/vendors/create', ['error' => $e->getMessage()]);
        }
    }

    public function edit(Request $request, Response $response)
    {
        $id = (int)$request->getRouteParam('id');
        $vendor = $this->service->getVendor($id);
        if (!$vendor) {
            return $response->redirect(url('/admin/vendors?error=not_found'));
        }
        return $this->render('admin/vendors/edit', ['vendor' => $vendor]);
    }

    public function update(Request $request, Response $response)
    {
        $id = (int)$request->getRouteParam('id');
        try {
            $data = $request->getBody();
            $this->service->updateVendor($id, $data);
            return $response->redirect(url("/admin/vendors?success=updated"));
        } catch (Exception $e) {
            $vendor = $this->service->getVendor($id);
            return $this->render('admin/vendors/edit', ['vendor' => $vendor, 'error' => $e->getMessage()]);
        }
    }

    public function reports(Request $request, Response $response)
    {
        $vendors = $this->service->listVendors();
        return $this->render('admin/vendors/reports', [
            'vendors' => $vendors
        ]);
    }

    public function payments(Request $request, Response $response)
    {
        $vendorId = $request->get('vendor_id') ? (int)$request->get('vendor_id') : null;
        $payments = $this->service->listPayments($vendorId);
        return $this->render('admin/vendors/payments', [
            'payments' => $payments
        ]);
    }

    public function wallet(Request $request, Response $response)
    {
        $vendorId = $request->get('vendor_id') ? (int)$request->get('vendor_id') : 1;
        $wallet = $this->service->getWallet($vendorId);
        return $this->render('admin/vendors/wallet', [
            'wallet' => $wallet,
            'vendorId' => $vendorId
        ]);
    }

    // --- REST API ENDPOINTS ---

    public function apiList(Request $request, Response $response)
    {
        $vendors = $this->service->listVendors();
        return $response->json([
            'status' => 'success',
            'data' => $vendors
        ]);
    }

    public function apiShow(Request $request, Response $response)
    {
        $id = (int)$request->getRouteParam('id');
        $vendor = $this->service->getVendor($id);
        if (!$vendor) {
            return $response->json(['status' => 'error', 'message' => 'Vendor not found'], 404);
        }
        return $response->json([
            'status' => 'success',
            'data' => $vendor
        ]);
    }

    public function apiProducts(Request $request, Response $response)
    {
        $vendorId = (int)$request->get('vendor_id', 1);
        $products = $this->service->getVendorProducts($vendorId);
        return $response->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    public function apiOrders(Request $request, Response $response)
    {
        $vendorId = (int)$request->get('vendor_id', 1);
        // Mock list of orders for the API endpoint response
        $orders = [
            [
                'id' => 101,
                'vendor_id' => $vendorId,
                'order_number' => 'ORD-2026-9081',
                'customer_name' => 'Caner Arslan',
                'total_amount' => 450.00,
                'status' => 'delivered',
                'created_at' => '2026-07-28 14:00:00'
            ]
        ];
        return $response->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    public function apiStatistics(Request $request, Response $response)
    {
        $vendorId = (int)$request->get('vendor_id', 1);
        $stats = $this->service->getStatistics($vendorId);
        return $response->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    public function apiWallet(Request $request, Response $response)
    {
        $vendorId = (int)$request->get('vendor_id', 1);
        $wallet = $this->service->getWallet($vendorId);
        return $response->json([
            'status' => 'success',
            'data' => $wallet
        ]);
    }

    public function apiPayments(Request $request, Response $response)
    {
        $vendorId = (int)$request->get('vendor_id', 1);
        $payments = $this->service->listPayments($vendorId);
        return $response->json([
            'status' => 'success',
            'data' => $payments
        ]);
    }
}
