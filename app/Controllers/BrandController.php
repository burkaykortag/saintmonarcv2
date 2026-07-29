<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\BrandService;
use App\Repositories\BrandRepository;
use Core\Contracts\DatabaseInterface;
use Core\View\View;
use Exception;

class BrandController extends Controller {
    private BrandService $service;
    private BrandRepository $repository;
    private DatabaseInterface $db;

    public function __construct(View $view, BrandService $service, BrandRepository $repository, DatabaseInterface $db) {
        parent::__construct($view);
        $this->service = $service;
        $this->repository = $repository;
        $this->db = $db;
    }

    public function index(Request $request, Response $response): string {
        $filters = [];
        if ($request->get('q')) {
            $filters['q'] = trim((string)$request->get('q'));
        }
        if ($request->get('is_active') !== null && $request->get('is_active') !== '') {
            $filters['is_active'] = (int)$request->get('is_active');
        }
        if ($request->get('is_featured') !== null && $request->get('is_featured') !== '') {
            $filters['is_featured'] = (int)$request->get('is_featured');
        }

        $brands = $this->repository->getAll($filters);

        $hasProducts = $request->get('products');
        if ($hasProducts === 'yes') {
            $brands = array_filter($brands, fn($b) => (int)$b['product_count'] > 0);
        } elseif ($hasProducts === 'no') {
            $brands = array_filter($brands, fn($b) => (int)$b['product_count'] === 0);
        }

        return $this->render('admin/brands/index', [
            'brands' => $brands,
            'q' => $filters['q'] ?? '',
            'is_active' => $request->get('is_active') ?? '',
            'is_featured' => $request->get('is_featured') ?? '',
            'products' => $hasProducts ?? ''
        ]);
    }

    public function showCreate(Request $request, Response $response): string {
        return $this->render('admin/brands/create');
    }

    public function store(Request $request, Response $response): void {
        try {
            $data = $request->post();
            $data['seo'] = $request->post('seo') ?? [];
            
            $this->service->create($data);
            $response->redirect('/admin/brands?success=' . urlencode('Marka başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/brands/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function showEdit(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $brand = $this->repository->getById($id);
        if (!$brand) {
            $response->redirect('/admin/brands?error=' . urlencode('Marka bulunamadı.'));
            exit;
        }

        $seo = $this->service->getSeoMeta($id);

        return $this->render('admin/brands/edit', [
            'brand' => $brand,
            'seo' => $seo
        ]);
    }

    public function update(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $data = $request->post();
            $data['seo'] = $request->post('seo') ?? [];
            
            $this->service->update($id, $data);
            $response->redirect('/admin/brands?success=' . urlencode('Marka başarıyla güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/brands/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->service->delete($id);
            $response->redirect('/admin/brands?success=' . urlencode('Marka silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/brands?error=' . urlencode($e->getMessage()));
        }
    }

    public function sort(Request $request, Response $response): void {
        $orders = $request->post('orders'); // Array of {id: int, sort_order: int}
        if (empty($orders) || !is_array($orders)) {
            $response->json(['success' => false, 'message' => 'Geçersiz sıralama verisi.'], 400);
            return;
        }

        try {
            $this->db->beginTransaction();
            foreach ($orders as $o) {
                $this->db->execute(
                    "UPDATE brands SET sort_order = :sort WHERE id = :id",
                    [
                        ':sort' => (int)$o['sort_order'],
                        ':id' => (int)$o['id']
                    ]
                );
            }
            $this->db->commit();
            $this->service->clearCache();
            $response->json(['success' => true, 'message' => 'Sıralama güncellendi.']);
        } catch (Exception $e) {
            $this->db->rollBack();
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulk(Request $request, Response $response): void {
        $action = $request->post('action') ?? '';
        $ids = $request->post('brand_ids') ?? [];

        if (empty($ids)) {
            $response->redirect('/admin/brands?error=' . urlencode('Seçili marka bulunamadı.'));
            return;
        }

        try {
            $this->db->beginTransaction();

            if ($action === 'delete') {
                foreach ($ids as $id) {
                    $this->db->execute("UPDATE brands SET deleted_at = NOW() WHERE id = :id", [':id' => (int)$id]);
                }
            } elseif ($action === 'active') {
                foreach ($ids as $id) {
                    $this->db->execute("UPDATE brands SET is_active = 1 WHERE id = :id", [':id' => (int)$id]);
                }
            } elseif ($action === 'passive') {
                foreach ($ids as $id) {
                    $this->db->execute("UPDATE brands SET is_active = 0 WHERE id = :id", [':id' => (int)$id]);
                }
            }

            $this->db->commit();
            $this->service->clearCache();
            $response->redirect('/admin/brands?success=' . urlencode('Toplu işlem başarıyla tamamlandı.'));
        } catch (Exception $e) {
            $this->db->rollBack();
            $response->redirect('/admin/brands?error=' . urlencode($e->getMessage()));
        }
    }

    public function export(Request $request, Response $response): void {
        $brands = $this->repository->getAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=brands_export_' . date('Ymd_His') . '.csv');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['ID', 'Marka Adı', 'Slug', 'Ürün Sayısı', 'Toplam Satış Adeti', 'Toplam Gelir', 'Durum', 'Sıra', 'Oluşturulma Tarihi']);
        
        foreach ($brands as $b) {
            fputcsv($output, [
                $b['id'],
                $b['name'],
                $b['slug'],
                $b['product_count'],
                $b['total_sales'],
                $b['total_revenue'],
                $b['is_active'] ? 'Aktif' : 'Pasif',
                $b['sort_order'],
                $b['created_at']
            ]);
        }
        fclose($output);
        exit;
    }

    public function apiIndex(Request $request, Response $response): void {
        try {
            $brands = $this->service->getActiveCached();
            $response->json([
                'success' => true,
                'data' => $brands
            ]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiShow(Request $request, Response $response): void {
        // Resolve slug from request path
        $path = $request->getUri();
        // Extract slug from e.g. /api/brands/nike
        $parts = explode('/', trim($path, '/'));
        $slug = end($parts);

        try {
            $brand = $this->repository->getBySlug($slug);
            if ($brand && $brand['is_active']) {
                $response->json([
                    'success' => true,
                    'data' => $brand
                ]);
            } else {
                $response->json(['success' => false, 'message' => 'Marka bulunamadı.'], 404);
            }
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function showPublicBrandPage(Request $request, Response $response): string {
        $path = $request->getUri();
        $parts = explode('/', trim($path, '/'));
        $slug = end($parts);

        $brand = $this->repository->getBySlug($slug);
        if (!$brand || !$brand['is_active']) {
            $response->json(['error' => 'Marka bulunamadı veya pasif durumda.'], 404);
            exit;
        }

        // Fetch products belonging to this brand
        $products = $this->db->query(
            "SELECT p.*, pt.name, pt.short_description 
             FROM products p
             JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.brand_id = :bid AND p.is_active = 1 AND p.deleted_at IS NULL",
            [':bid' => $brand['id']]
        );

        return $this->render('brands/show', [
            'brand' => $brand,
            'products' => $products
        ]);
    }
}
