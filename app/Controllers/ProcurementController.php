<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\View\View;
use App\Repositories\ProcurementRepository;
use App\Services\ProcurementService;
use App\Repositories\WarehouseRepository;
use App\Repositories\ProductRepository;
use Exception;

class ProcurementController extends Controller {
    private ProcurementRepository $repository;
    private ProcurementService $service;
    private WarehouseRepository $warehouseRepository;
    private ProductRepository $productRepository;

    public function __construct(
        View $view,
        ProcurementRepository $repository,
        ProcurementService $service,
        WarehouseRepository $warehouseRepository,
        ProductRepository $productRepository
    ) {
        parent::__construct($view);
        $this->repository = $repository;
        $this->service = $service;
        $this->warehouseRepository = $warehouseRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Satın Alma Analitik Dashboard
     */
    public function dashboard(Request $request, Response $response): string {
        $period = $request->get('period') ?? 'this_month';
        $analytics = $this->repository->getPurchaseAnalytics($period);
        return $this->render('admin/purchasing/dashboard', [
            'analytics' => $analytics,
            'period' => $period
        ]);
    }

    /**
     * Tedarikçiler Grid/List Görünümü
     */
    public function suppliers(Request $request, Response $response): string {
        $filters = $request->all();
        $suppliers = $this->repository->getAllSuppliers($filters);
        
        return $this->render('admin/purchasing/suppliers', [
            'suppliers' => $suppliers,
            'filters' => $filters
        ]);
    }

    /**
     * Tedarikçi 360 Ekranı
     */
    public function supplierShow(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $supplier = $this->repository->getSupplierById($id);
        if (!$supplier) {
            return "Tedarikçi bulunamadı.";
        }

        return $this->render('admin/purchasing/supplier_show', [
            'supplier' => $supplier,
            'contacts' => $this->repository->getSupplierContacts($id),
            'addresses' => $this->repository->getSupplierAddresses($id),
            'pos' => $this->repository->getSupplierPOs($id),
            'invoices' => $this->repository->getSupplierInvoices($id),
            'payments' => $this->repository->getSupplierPayments($id),
            'contracts' => $this->repository->getSupplierContracts($id),
            'documents' => $this->repository->getSupplierDocuments($id),
            'performance' => $this->repository->getSupplierPerformance($id),
            'notes' => $this->repository->getSupplierNotes($id)
        ]);
    }

    /**
     * Satın Alma Siparişleri (PO) Listesi
     */
    public function orders(Request $request, Response $response): string {
        $filters = $request->all();
        $orders = $this->repository->getAllPurchaseOrders($filters);
        return $this->render('admin/purchasing/orders', [
            'orders' => $orders,
            'filters' => $filters
        ]);
    }

    /**
     * SAP Tarzı 5 Adımlı Satın Alma Sihirbazı
     */
    public function wizard(Request $request, Response $response): string {
        $suppliers = $this->repository->getAllSuppliers();
        $products = $this->productRepository->getAll();
        $warehouses = $this->warehouseRepository->getAll();

        return $this->render('admin/purchasing/wizard', [
            'suppliers' => $suppliers,
            'products' => $products,
            'warehouses' => $warehouses
        ]);
    }

    /**
     * RFQ Teklif İstekleri listeleme ve karşılaştırma
     */
    public function rfq(Request $request, Response $response): string {
        $rfqId = $request->get('id') ? (int)$request->get('id') : null;
        $rfqs = $this->repository->getRFQs();
        
        $compareData = null;
        if ($rfqId) {
            $compareData = $this->service->compareRFQ($rfqId);
        }

        $products = $this->productRepository->getAll();
        $suppliers = $this->repository->getAllSuppliers();

        return $this->render('admin/purchasing/rfq', [
            'rfqs' => $rfqs,
            'compareData' => $compareData,
            'selectedRfqId' => $rfqId,
            'products' => $products,
            'suppliers' => $suppliers
        ]);
    }

    /**
     * Goods Receipt (Mal Kabul) Ekranı
     */
    public function receipts(Request $request, Response $response): string {
        $poId = $request->get('po_id') ? (int)$request->get('po_id') : null;
        
        $orders = $this->repository->getAllPurchaseOrders(['status' => 'sent']);
        $receipts = $this->repository->getGoodsReceipts();
        
        $poDetails = null;
        $poItems = [];
        if ($poId) {
            $poDetails = $this->repository->getPurchaseOrderById($poId);
            $poItems = $this->repository->getPurchaseOrderItems($poId);
        }

        return $this->render('admin/purchasing/receipts', [
            'orders' => $orders,
            'receipts' => $receipts,
            'poDetails' => $poDetails,
            'poItems' => $poItems,
            'selectedPoId' => $poId
        ]);
    }

    /**
     * Tedarikçi Ödemeleri Takip Ekranı
     */
    public function payments(Request $request, Response $response): string {
        $payments = $this->repository->getSupplierPaymentsAll();
        return $this->render('admin/purchasing/payments', [
            'payments' => $payments
        ]);
    }

    /**
     * Sözleşmeler Yönetimi
     */
    public function contracts(Request $request, Response $response): string {
        $contracts = $this->repository->getSupplierContractsAll();
        $suppliers = $this->repository->getAllSuppliers();
        return $this->render('admin/purchasing/contracts', [
            'contracts' => $contracts,
            'suppliers' => $suppliers
        ]);
    }

    /**
     * AI Satın Alma Asistanı
     */
    public function aiAssistant(Request $request, Response $response): string {
        $suggestions = $this->service->getAiPurchasingAssistantSuggestions();
        return $this->render('admin/purchasing/ai_assistant', [
            'suggestions' => $suggestions
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST ACTIONS & API ENDPOINTS
    // ─────────────────────────────────────────────────────────────

    public function createSupplier(Request $request, Response $response): void {
        try {
            $data = $request->post();
            $id = $this->service->createSupplier($data);
            $response->redirect(url('/admin/purchasing/suppliers?id=' . $id . '&success=' . urlencode('Tedarikçi başarıyla oluşturuldu.')));
        } catch (Exception $e) {
            $response->redirect(url('/admin/purchasing/suppliers?error=' . urlencode($e->getMessage())));
        }
    }

    public function updateSupplier(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $data = $request->post();
            $this->service->updateSupplier($id, $data);
            $response->redirect(url('/admin/purchasing/suppliers/show?id=' . $id . '&success=' . urlencode('Tedarikçi güncellendi.')));
        } catch (Exception $e) {
            $response->redirect(url('/admin/purchasing/suppliers/show?id=' . $id . '&error=' . urlencode($e->getMessage())));
        }
    }

    public function deleteSupplier(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->service->deleteSupplier($id);
            $response->redirect(url('/admin/purchasing/suppliers?success=' . urlencode('Tedarikçi silindi.')));
        } catch (Exception $e) {
            $response->redirect(url('/admin/purchasing/suppliers?error=' . urlencode($e->getMessage())));
        }
    }

    public function createPurchaseOrder(Request $request, Response $response): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $adminId = $_SESSION['admin_id'] ?? 1;

        try {
            $data = $request->post();
            $poId = $this->service->createPurchaseOrder($data, $adminId);
            $response->redirect(url('/admin/purchasing/orders?success=' . urlencode('Satın alma siparişi oluşturuldu.')));
        } catch (Exception $e) {
            $response->redirect(url('/admin/purchasing/wizard?error=' . urlencode($e->getMessage())));
        }
    }

    public function approvePurchaseOrder(Request $request, Response $response): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $adminId = $_SESSION['admin_id'] ?? 1;

        $id = (int)$request->post('id');
        $status = $request->post('status') ?? 'approved';

        try {
            $this->service->updatePurchaseOrderStatus($id, $status, $adminId);
            $response->redirect(url('/admin/purchasing/orders?success=' . urlencode("Sipariş durumu güncellendi: {$status}")));
        } catch (Exception $e) {
            $response->redirect(url('/admin/purchasing/orders?error=' . urlencode($e->getMessage())));
        }
    }

    public function receiveGoods(Request $request, Response $response): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $adminId = $_SESSION['admin_id'] ?? 1;

        $poId = (int)$request->post('purchase_order_id');
        $items = $request->post('items') ?? [];
        $notes = $request->post('notes') ?? '';

        try {
            $this->service->receiveGoods($poId, $items, $adminId, $notes);
            $response->redirect(url('/admin/purchasing/receipts?success=' . urlencode('Mal kabul başarıyla tamamlandı ve stoklar güncellendi.')));
        } catch (Exception $e) {
            $response->redirect(url('/admin/purchasing/receipts?po_id=' . $poId . '&error=' . urlencode($e->getMessage())));
        }
    }

    public function createRFQ(Request $request, Response $response): void {
        try {
            $data = $request->post();
            $this->service->createRFQ($data);
            $response->redirect(url('/admin/purchasing/rfq?success=' . urlencode('Teklif talebi oluşturuldu.')));
        } catch (Exception $e) {
            $response->redirect(url('/admin/purchasing/rfq?error=' . urlencode($e->getMessage())));
        }
    }

    public function submitRFQResponse(Request $request, Response $response): void {
        $rfqId = (int)$request->post('rfq_id');
        try {
            $data = $request->post();
            $this->service->submitRFQResponse($data);
            $response->redirect(url('/admin/purchasing/rfq?id=' . $rfqId . '&success=' . urlencode('Teklif cevabı kaydedildi.')));
        } catch (Exception $e) {
            $response->redirect(url('/admin/purchasing/rfq?id=' . $rfqId . '&error=' . urlencode($e->getMessage())));
        }
    }

    // ─────────────────────────────────────────────────────────────
    // REST API ENDPOINTS
    // ─────────────────────────────────────────────────────────────

    public function apiSuppliers(Request $request, Response $response): void {
        try {
            $suppliers = $this->repository->getAllSuppliers();
            $response->json(['success' => true, 'data' => $suppliers]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiCreateOrder(Request $request, Response $response): void {
        $data = json_decode($request->getBody(), true) ?? $request->post();
        try {
            $poId = $this->service->createPurchaseOrder($data, 1);
            $response->json(['success' => true, 'purchase_order_id' => $poId]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function apiApproveOrder(Request $request, Response $response): void {
        $data = json_decode($request->getBody(), true) ?? $request->post();
        try {
            $poId = (int)($data['id'] ?? 0);
            $status = $data['status'] ?? 'approved';
            $this->service->updatePurchaseOrderStatus($poId, $status, 1);
            $response->json(['success' => true]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function apiCompareRfq(Request $request, Response $response): void {
        $id = (int)$request->get('id');
        try {
            $compare = $this->service->compareRFQ($id);
            $response->json(['success' => true, 'data' => $compare]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function apiReceiveGoods(Request $request, Response $response): void {
        $data = json_decode($request->getBody(), true) ?? $request->post();
        try {
            $poId = (int)($data['purchase_order_id'] ?? 0);
            $items = $data['items'] ?? [];
            $notes = $data['notes'] ?? '';
            $grId = $this->service->receiveGoods($poId, $items, 1, $notes);
            $response->json(['success' => true, 'goods_receipt_id' => $grId]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
