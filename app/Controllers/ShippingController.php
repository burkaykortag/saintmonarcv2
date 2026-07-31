<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\ShippingService;
use App\Repositories\ShippingRepository;
use Core\Security;
use Core\View\View;

class ShippingController extends Controller {
    private ShippingService $service;
    private ShippingRepository $repository;
    private Security $security;

    public function __construct(
        View $view,
        ShippingService $service,
        ShippingRepository $repository,
        Security $security
    ) {
        parent::__construct($view);
        $this->service = $service;
        $this->repository = $repository;
        $this->security = $security;
    }

    /**
     * Lojistik Paneli (Dashboard).
     */
    public function index(Request $request, Response $response): string {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);

        $totalShipments = $db->query("SELECT COUNT(*) as cnt FROM shipping_packages WHERE deleted_at IS NULL")[0]['cnt'] ?? 0;
        $delivered = $db->query("SELECT COUNT(*) as cnt FROM shipping_packages WHERE status = 'delivered' AND deleted_at IS NULL")[0]['cnt'] ?? 0;
        $pending = $db->query("SELECT COUNT(*) as cnt FROM shipping_packages WHERE status = 'pending' AND deleted_at IS NULL")[0]['cnt'] ?? 0;
        $returned = $db->query("SELECT COUNT(*) as cnt FROM shipping_returns WHERE deleted_at IS NULL")[0]['cnt'] ?? 0;

        $packages = $this->repository->listPackages([], 1, 10);
        $companies = $this->repository->listCompanies([], 1, 10);

        return $this->render('admin/shipping/index', [
            'totalShipments' => (int)$totalShipments,
            'delivered' => (int)$delivered,
            'pending' => (int)$pending,
            'returned' => (int)$returned,
            'packages' => $packages,
            'companies' => $companies
        ]);
    }

    /**
     * Kargo Firmaları Sayfası.
     */
    public function companies(Request $request, Response $response): string {
        $companies = $this->repository->listCompanies();
        return $this->render('admin/shipping/companies', [
            'companies' => $companies
        ]);
    }

    /**
     * Gönderiler Listesi.
     */
    public function shipments(Request $request, Response $response): string {
        $packages = $this->repository->listPackages();
        return $this->render('admin/shipping/shipments', [
            'packages' => $packages
        ]);
    }

    /**
     * İadeler Yönetimi.
     */
    public function returns(Request $request, Response $response): string {
        $returns = $this->repository->listReturns();
        return $this->render('admin/shipping/returns', [
            'returns' => $returns
        ]);
    }

    /**
     * Kargo Hesaplama Kuralları.
     */
    public function rules(Request $request, Response $response): string {
        $rules = $this->repository->listRules();
        return $this->render('admin/shipping/rules', [
            'rules' => $rules
        ]);
    }

    /**
     * Raporlar Ekranı.
     */
    public function reports(Request $request, Response $response): string {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);

        $performance = $db->query(
            "SELECT c.name as company_name, COUNT(p.id) as total, SUM(CASE WHEN p.status = 'delivered' THEN 1 ELSE 0 END) as delivered
             FROM shipping_packages p
             JOIN shipping_services s ON p.service_id = s.id
             JOIN shipping_companies c ON s.company_id = c.id
             GROUP BY c.name"
        );

        return $this->render('admin/shipping/reports', [
            'performance' => $performance
        ]);
    }

    /**
     * Kargo Firması Ekleme.
     */
    public function storeCompany(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/shipping/companies?error=CSRF+Hatası.'));
            return;
        }

        $this->repository->createCompany([
            'name' => $request->get('name'),
            'code' => $request->get('code'),
            'tax_number' => $request->get('tax_number'),
            'is_active' => (int)$request->get('is_active', 1)
        ]);

        $response->redirect(url('/admin/shipping/companies?success=Kargo+firması+başarıyla+eklendi.'));
    }

    /**
     * Kargo Firması ve API Entegrasyon Düzenleme Sayfası.
     */
    public function editCompany(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $company = $this->repository->getCompany($id);
        if (!$company) {
            return "Firma bulunamadı.";
        }

        $integration = $this->repository->getIntegration($id) ?? [
            'api_url' => '',
            'username' => '',
            'password' => '',
            'api_key' => '',
            'is_active' => 1
        ];

        return $this->render('admin/shipping/edit_company', [
            'company' => $company,
            'integration' => $integration
        ]);
    }

    /**
     * Kargo Firması Güncelleme.
     */
    public function updateCompany(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/shipping/companies?error=CSRF+Hatası.'));
            return;
        }

        $id = (int)$request->get('id');
        $this->repository->updateCompany($id, [
            'name' => $request->get('name'),
            'code' => $request->get('code'),
            'tax_number' => $request->get('tax_number'),
            'is_active' => (int)$request->get('is_active', 1)
        ]);

        $response->redirect(url('/admin/shipping/companies?success=Kargo+firması+güncellendi.'));
    }

    /**
     * API Entegrasyon Bilgilerini Güncelleme.
     */
    public function updateIntegration(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/shipping/companies?error=CSRF+Hatası.'));
            return;
        }

        $companyId = (int)$request->get('company_id');
        $this->repository->upsertIntegration([
            'company_id' => $companyId,
            'api_url' => $request->get('api_url'),
            'username' => $request->get('username'),
            'password' => $request->get('password'),
            'api_key' => $request->get('api_key'),
            'is_active' => (int)$request->get('is_active', 1)
        ]);

        $response->redirect(url("/admin/shipping/companies/edit?id={$companyId}&success=API+bağlantı+bilgileri+güncellendi."));
    }

    /**
     * Gönderi Sevkiyatı Başlatma.
     */
    public function storeShipment(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/shipping/shipments?error=CSRF+Hatası.'));
            return;
        }

        $this->service->createShipment([
            'order_id' => (int)$request->get('order_id'),
            'service_id' => (int)$request->get('service_id'),
            'desi' => (float)$request->get('desi', 1.0),
            'weight' => (float)$request->get('weight', 1.0),
            'shipping_cost' => (float)$request->get('shipping_cost', 0.00),
            'status' => 'pending'
        ]);

        $response->redirect(url('/admin/shipping/shipments?success=Gönderi+başarıyla+oluşturuldu.'));
    }

    /**
     * İade Talebi Güncelleme.
     */
    public function updateReturnStatus(Request $request, Response $response): void {
        if (!$this->security->validateCsrfToken($request->get('csrf_token') ?? '')) {
            $response->redirect(url('/admin/shipping/returns?error=CSRF+Hatası.'));
            return;
        }

        $id = (int)$request->get('id');
        $status = $request->get('status');

        $this->service->updateReturn($id, $status);

        $response->redirect(url('/admin/shipping/returns?success=İade+durumu+güncellendi.'));
    }

    // --- REST API Uç Noktaları ---

    public function apiIndex(Request $request, Response $response): void {
        $packages = $this->repository->listPackages();
        $response->json(['success' => true, 'data' => $packages]);
    }

    public function apiCalculate(Request $request, Response $response): void {
        $serviceId = (int)$request->get('service_id');
        $countryCode = $request->get('country_code', 'TR');
        $cityName = $request->get('city_name');
        $desi = (float)$request->get('desi', 1.0);
        $orderAmount = (float)$request->get('order_amount', 0.00);

        $cost = $this->service->calculateShippingCost($serviceId, $countryCode, $cityName, $desi, $orderAmount);

        $response->json([
            'success' => true,
            'calculated_cost' => $cost,
            'desi' => $desi,
            'currency' => 'TRY'
        ]);
    }

    public function apiTrack(Request $request, Response $response): void {
        $trackingNumber = $request->get('tracking_number');
        if (empty($trackingNumber)) {
            $response->json(['success' => false, 'message' => 'Takip numarası belirtilmedi.'], 400);
            return;
        }

        $history = $this->repository->getTrackingHistory($trackingNumber);

        $response->json([
            'success' => true,
            'tracking_number' => $trackingNumber,
            'events' => $history
        ]);
    }

    public function apiReturns(Request $request, Response $response): void {
        $returns = $this->repository->listReturns();
        $response->json(['success' => true, 'data' => $returns]);
    }

    public function apiCompanies(Request $request, Response $response): void {
        $companies = $this->repository->listCompanies(['is_active' => 1]);
        $response->json(['success' => true, 'data' => $companies]);
    }

    public function apiLabels(Request $request, Response $response): void {
        $packageId = (int)$request->get('package_id');
        if ($packageId <= 0) {
            $response->json(['success' => false, 'message' => 'Paket ID geçersiz.'], 400);
            return;
        }

        $filePath = $this->service->generateLabel($packageId);

        $response->json([
            'success' => true,
            'label_url' => url('/' . $filePath)
        ]);
    }

    public function apiStatistics(Request $request, Response $response): void {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $stats = $db->query("SELECT * FROM shipping_statistics ORDER BY recorded_date DESC LIMIT 30");
        $response->json(['success' => true, 'data' => $stats]);
    }
}
