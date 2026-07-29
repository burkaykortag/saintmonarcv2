<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\CategoryService;
use App\Repositories\CategoryRepository;
use Core\Contracts\DatabaseInterface;
use Core\View\View;
use Exception;

class CategoryController extends Controller {
    private CategoryService $service;
    private CategoryRepository $repository;
    private DatabaseInterface $db;

    public function __construct(View $view, CategoryService $service, CategoryRepository $repository, DatabaseInterface $db) {
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
        
        $type = $request->get('type');
        if ($type === 'parent') {
            $filters['parent_only'] = true;
        } elseif ($type === 'sub') {
            $filters['sub_only'] = true;
        }

        // Get flat list for statistics and options
        $flatCategories = $this->repository->getAll($filters);

        // Filter by product status if requested
        $hasProducts = $request->get('products');
        if ($hasProducts === 'yes') {
            $flatCategories = array_filter($flatCategories, fn($c) => (int)$c['product_count'] > 0);
        } elseif ($hasProducts === 'no') {
            $flatCategories = array_filter($flatCategories, fn($c) => (int)$c['product_count'] === 0);
        }

        // Re-build tree structure on the filtered flat list
        $categoriesTree = $this->repository->buildTree($flatCategories);

        return $this->render('admin/categories/index', [
            'categories' => $categoriesTree,
            'flat_categories' => $flatCategories,
            'q' => $filters['q'] ?? '',
            'is_active' => $request->get('is_active') ?? '',
            'type' => $type ?? '',
            'products' => $hasProducts ?? ''
        ]);
    }

    public function showCreate(Request $request, Response $response): string {
        $parents = $this->repository->getAll(['is_active' => 1]);
        return $this->render('admin/categories/create', [
            'parents' => $parents
        ]);
    }

    public function store(Request $request, Response $response): void {
        try {
            $data = $request->post();
            $data['seo'] = $request->post('seo') ?? [];
            
            $this->service->create($data);
            $response->redirect('/admin/categories?success=' . urlencode('Kategori başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/categories/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function showEdit(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $category = $this->repository->getById($id);
        if (!$category) {
            $response->redirect('/admin/categories?error=' . urlencode('Kategori bulunamadı.'));
            exit;
        }

        $parents = $this->repository->getAll(['is_active' => 1]);
        $seo = $this->service->getSeoMeta($id);

        return $this->render('admin/categories/edit', [
            'category' => $category,
            'parents' => $parents,
            'seo' => $seo
        ]);
    }

    public function update(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $data = $request->post();
            $data['seo'] = $request->post('seo') ?? [];
            
            $this->service->update($id, $data);
            $response->redirect('/admin/categories?success=' . urlencode('Kategori başarıyla güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/categories/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->service->delete($id);
            $response->redirect('/admin/categories?success=' . urlencode('Kategori ve alt dalları silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/categories?error=' . urlencode($e->getMessage()));
        }
    }

    public function sort(Request $request, Response $response): void {
        $orders = $request->post('orders'); // Array of {id: int, sort_order: int, parent_id: int|null}
        if (empty($orders) || !is_array($orders)) {
            $response->json(['success' => false, 'message' => 'Geçersiz sıralama verisi.'], 400);
            return;
        }

        try {
            $this->db->beginTransaction();
            foreach ($orders as $o) {
                $parentId = isset($o['parent_id']) && $o['parent_id'] !== '' ? (int)$o['parent_id'] : null;
                $this->db->execute(
                    "UPDATE categories SET sort_order = :sort, parent_id = :parent WHERE id = :id",
                    [
                        ':sort' => (int)$o['sort_order'],
                        ':parent' => $parentId,
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
        $ids = $request->post('category_ids') ?? [];
        $targetParentId = $request->post('target_parent_id') ? (int)$request->post('target_parent_id') : null;

        if (empty($ids)) {
            $response->redirect('/admin/categories?error=' . urlencode('Seçili kategori bulunamadı.'));
            return;
        }

        try {
            $this->db->beginTransaction();

            if ($action === 'delete') {
                foreach ($ids as $id) {
                    $this->db->execute("UPDATE categories SET deleted_at = NOW() WHERE id = :id", [':id' => (int)$id]);
                }
            } elseif ($action === 'active') {
                foreach ($ids as $id) {
                    $this->db->execute("UPDATE categories SET is_active = 1 WHERE id = :id", [':id' => (int)$id]);
                }
            } elseif ($action === 'passive') {
                foreach ($ids as $id) {
                    $this->db->execute("UPDATE categories SET is_active = 0 WHERE id = :id", [':id' => (int)$id]);
                }
            } elseif ($action === 'move') {
                foreach ($ids as $id) {
                    // Prevent circular hierarchy
                    if ((int)$id === $targetParentId) continue;
                    $this->db->execute(
                        "UPDATE categories SET parent_id = :target WHERE id = :id",
                        [':target' => $targetParentId, ':id' => (int)$id]
                    );
                }
            }

            $this->db->commit();
            $this->service->clearCache();
            $response->redirect('/admin/categories?success=' . urlencode('Toplu işlem başarıyla tamamlandı.'));
        } catch (Exception $e) {
            $this->db->rollBack();
            $response->redirect('/admin/categories?error=' . urlencode($e->getMessage()));
        }
    }

    public function export(Request $request, Response $response): void {
        $categories = $this->repository->getAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=categories_export_' . date('Ymd_His') . '.csv');
        
        $output = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['ID', 'Kategori Adı', 'Slug', 'Üst Kategori', 'Ürün Sayısı', 'Durum', 'Sıra', 'Oluşturulma Tarihi']);
        
        foreach ($categories as $c) {
            fputcsv($output, [
                $c['id'],
                $c['name'],
                $c['slug'],
                $c['parent_name'] ?? 'Ana Kategori',
                $c['product_count'],
                $c['is_active'] ? 'Aktif' : 'Pasif',
                $c['sort_order'],
                $c['created_at']
            ]);
        }
        fclose($output);
        exit;
    }

    public function apiTree(Request $request, Response $response): void {
        try {
            $tree = $this->service->getTreeCached(['is_active' => 1]);
            $response->json([
                'success' => true,
                'data' => $tree
            ]);
        } catch (Exception $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
