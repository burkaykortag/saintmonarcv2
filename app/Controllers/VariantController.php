<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\VariantService;
use App\Repositories\VariantRepository;
use App\Repositories\AttributeRepository;
use App\Repositories\ProductRepository;
use Core\View\View;
use Exception;

class VariantController extends Controller {
    private VariantService $service;
    private VariantRepository $repository;
    private AttributeRepository $attributeRepository;
    private ProductRepository $productRepository;

    public function __construct(
        View $view, 
        VariantService $service, 
        VariantRepository $repository,
        AttributeRepository $attributeRepository,
        ProductRepository $productRepository
    ) {
        parent::__construct($view);
        $this->service = $service;
        $this->repository = $repository;
        $this->attributeRepository = $attributeRepository;
        $this->productRepository = $productRepository;
    }

    public function index(Request $request, Response $response): string {
        $filters = $request->get();
        $variants = $this->repository->getAll($filters);

        $attributes = $this->attributeRepository->getAll();
        $products = $this->productRepository->getAll();

        return $this->render('admin/variants/index', [
            'variants' => $variants,
            'attributes' => $attributes,
            'products' => $products,
            'filters' => $filters
        ]);
    }

    public function showCreate(Request $request, Response $response): string {
        $productId = (int)$request->get('product_id');
        $product = $this->productRepository->getById($productId);
        if (!$product) {
            $response->redirect('/admin/products?error=' . urlencode('Ürün bulunamadı.'));
            exit;
        }

        $attributes = $this->attributeRepository->getAll();

        return $this->render('admin/variants/create', [
            'product' => $product,
            'attributes' => $attributes
        ]);
    }

    public function store(Request $request, Response $response): void {
        $productId = (int)$request->post('product_id');
        try {
            $data = $request->post();
            $this->service->create($data);
            $response->redirect('/admin/products/edit?id=' . $productId . '&success=' . urlencode('Varyant başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/variants/create?product_id=' . $productId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function showEdit(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $variant = $this->repository->getById($id);
        if (!$variant) {
            $response->redirect('/admin/products?error=' . urlencode('Varyant bulunamadı.'));
            exit;
        }

        $product = $this->productRepository->getById((int)$variant['product_id']);
        $attributes = $this->attributeRepository->getAll();

        return $this->render('admin/variants/edit', [
            'variant' => $variant,
            'product' => $product,
            'attributes' => $attributes
        ]);
    }

    public function update(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        $variant = $this->repository->getById($id);
        if (!$variant) {
            $response->redirect('/admin/products?error=' . urlencode('Varyant bulunamadı.'));
            exit;
        }
        $productId = (int)$variant['product_id'];

        try {
            $data = $request->post();
            $this->service->update($id, $data);
            $response->redirect('/admin/products/edit?id=' . $productId . '&success=' . urlencode('Varyant başarıyla güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/variants/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        $variant = $this->repository->getById($id);
        if (!$variant) {
            $response->redirect('/admin/products?error=' . urlencode('Varyant bulunamadı.'));
            exit;
        }
        $productId = (int)$variant['product_id'];

        try {
            $this->service->delete($id);
            $response->redirect('/admin/products/edit?id=' . $productId . '&success=' . urlencode('Varyant başarıyla silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/products/edit?id=' . $productId . '&error=' . urlencode($e->getMessage()));
        }
    }

    // --- Generate Combinations (AJAX) ---
    public function generateCombinations(Request $request, Response $response): void {
        try {
            $data = json_decode($request->getRawBody(), true) ?? $request->post();
            $productId = (int)($data['product_id'] ?? 0);
            $attributesMap = $data['attributes'] ?? []; // [attr_id => [val_id_1, val_id_2]]

            $combinations = $this->service->generateCombinations($productId, $attributesMap);
            $this->json(['success' => true, 'combinations' => $combinations]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    // --- Bulk Operations ---
    public function bulk(Request $request, Response $response): void {
        $action = trim((string)$request->post('action'));
        $ids = $request->post('ids');
        if (empty($ids) || !is_array($ids)) {
            $response->redirect('/admin/variants?error=' . urlencode('Hiçbir varyant seçilmedi.'));
            return;
        }

        $ids = array_map('intval', $ids);

        try {
            if ($action === 'delete') {
                $this->service->bulkDelete($ids);
                $msg = 'Seçilen varyantlar başarıyla silindi.';
            } elseif ($action === 'activate') {
                $this->service->bulkToggleActive($ids, 1);
                $msg = 'Seçilen varyantlar aktif hale getirildi.';
            } elseif ($action === 'deactivate') {
                $this->service->bulkToggleActive($ids, 0);
                $msg = 'Seçilen varyantlar pasif hale getirildi.';
            } elseif ($action === 'generate_sku') {
                $this->service->bulkSkuGenerate($ids);
                $msg = 'Seçilen varyantların SKU\'ları otomatik oluşturuldu.';
            } elseif ($action === 'generate_barcode_ean13') {
                $this->service->bulkBarcodeGenerate($ids, 'EAN13');
                $msg = 'Seçilen varyantların EAN13 barkodları otomatik oluşturuldu.';
            } elseif ($action === 'generate_barcode_ean8') {
                $this->service->bulkBarcodeGenerate($ids, 'EAN8');
                $msg = 'Seçilen varyantların EAN8 barkodları otomatik oluşturuldu.';
            } elseif ($action === 'generate_barcode_c128') {
                $this->service->bulkBarcodeGenerate($ids, 'Code128');
                $msg = 'Seçilen varyantların Code128 barkodları otomatik oluşturuldu.';
            } elseif ($action === 'update_price') {
                $price = (float)$request->post('price');
                $compare = $request->post('compare_at_price') !== '' ? (float)$request->post('compare_at_price') : null;
                $special = $request->post('special_price') !== '' ? (float)$request->post('special_price') : null;
                $this->service->bulkUpdatePrices($ids, $price, $compare, $special);
                $msg = 'Seçilen varyantların fiyatları toplu güncellendi.';
            } elseif ($action === 'update_stock') {
                $stock = (int)$request->post('stock');
                $this->service->bulkUpdateStocks($ids, $stock);
                $msg = 'Seçilen varyantların stokları toplu güncellendi.';
            } else {
                throw new Exception('Geçersiz işlem.');
            }

            $response->redirect('/admin/variants?success=' . urlencode($msg));
        } catch (Exception $e) {
            $response->redirect('/admin/variants?error=' . urlencode($e->getMessage()));
        }
    }

    // --- Export ---
    public function export(Request $request, Response $response): void {
        $format = $request->get('format') === 'excel' ? 'excel' : 'csv';
        $filters = $request->get();

        if ($format === 'excel') {
            $content = $this->service->exportExcel($filters);
            $filename = 'varyant-listesi-' . date('Ymd-His') . '.xls';
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        } else {
            $content = $this->service->exportCsv($filters);
            $filename = 'varyant-listesi-' . date('Ymd-His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
        }

        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $content;
        exit;
    }

    // --- REST API Endpoints ---
    public function apiIndex(Request $request, Response $response): void {
        $filters = $request->get();
        $variants = $this->repository->getAll($filters);
        $this->json($variants);
    }

    public function apiShow(Request $request, Response $response): void {
        $pathParts = explode('/', trim($request->getPath(), '/'));
        $id = (int)end($pathParts);
        $variant = $this->repository->getById($id);
        if (!$variant) {
            $this->json(['error' => 'Varyant bulunamadı.'], 404);
            return;
        }
        $this->json($variant);
    }

    public function apiStore(Request $request, Response $response): void {
        try {
            $data = json_decode($request->getRawBody(), true) ?? $request->post();
            $id = $this->service->create($data);
            $this->json(['success' => true, 'id' => $id], 201);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function apiUpdate(Request $request, Response $response): void {
        $pathParts = explode('/', trim($request->getPath(), '/'));
        $id = (int)end($pathParts);
        try {
            $data = json_decode($request->getRawBody(), true) ?? $request->post();
            $this->service->update($id, $data);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function apiDelete(Request $request, Response $response): void {
        $pathParts = explode('/', trim($request->getPath(), '/'));
        $id = (int)end($pathParts);
        try {
            $this->service->delete($id);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function apiProductVariants(Request $request, Response $response): void {
        $pathParts = explode('/', trim($request->getPath(), '/'));
        // /api/products/{id}/variants => {id} is the second to last part
        $productId = (int)$pathParts[count($pathParts) - 2];
        $variants = $this->repository->getByProductId($productId);
        $this->json($variants);
    }
}
