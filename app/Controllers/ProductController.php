<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use App\Services\ProductService;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\BrandRepository;
use Core\Contracts\DatabaseInterface;
use Core\View\View;
use Exception;

class ProductController extends Controller {
    private ProductService $service;
    private ProductRepository $repository;
    private CategoryRepository $categoryRepository;
    private BrandRepository $brandRepository;
    private DatabaseInterface $db;

    public function __construct(
        View $view, 
        ProductService $service, 
        ProductRepository $repository, 
        CategoryRepository $categoryRepository,
        BrandRepository $brandRepository,
        DatabaseInterface $db
    ) {
        parent::__construct($view);
        $this->service = $service;
        $this->repository = $repository;
        $this->categoryRepository = $categoryRepository;
        $this->brandRepository = $brandRepository;
        $this->db = $db;
    }

    public function index(Request $request, Response $response): string {
        $filters = [];
        if ($request->get('q')) {
            $filters['q'] = trim((string)$request->get('q'));
        }
        if ($request->get('category_id') !== null && $request->get('category_id') !== '') {
            $filters['category_id'] = (int)$request->get('category_id');
        }
        if ($request->get('brand_id') !== null && $request->get('brand_id') !== '') {
            $filters['brand_id'] = (int)$request->get('brand_id');
        }
        if ($request->get('status') !== null && $request->get('status') !== '') {
            $filters['status'] = $request->get('status');
        }
        if ($request->get('sku') !== null && $request->get('sku') !== '') {
            $filters['sku'] = trim($request->get('sku'));
        }
        if ($request->get('barcode') !== null && $request->get('barcode') !== '') {
            $filters['barcode'] = trim($request->get('barcode'));
        }

        // Active products
        $products = $this->repository->getAll($filters, false);

        // Soft Deleted products (Trash bin)
        $trash = $this->repository->getAll($filters, true);

        $categories = $this->categoryRepository->getAll();
        $brands = $this->brandRepository->getAll();

        return $this->render('admin/products/index', [
            'products' => $products,
            'trash' => $trash,
            'categories' => $categories,
            'brands' => $brands,
            'q' => $filters['q'] ?? '',
            'category_id' => $request->get('category_id') ?? '',
            'brand_id' => $request->get('brand_id') ?? '',
            'status' => $request->get('status') ?? '',
            'sku' => $filters['sku'] ?? '',
            'barcode' => $filters['barcode'] ?? ''
        ]);
    }

    public function showCreate(Request $request, Response $response): string {
        $categories = $this->categoryRepository->getAll();
        $brands = $this->brandRepository->getAll(['is_active' => 1]);
        $allProducts = $this->repository->getAll();
        
        // Fetch all attributes for variants
        $attributes = $this->db->query("
            SELECT a.*, at.name 
            FROM attributes a 
            JOIN attribute_translations at ON a.id = at.attribute_id AND at.language_id = 1
            ORDER BY a.id ASC
        ");
        foreach ($attributes as &$attr) {
            $attr['values'] = $this->db->query("
                SELECT av.*, avt.name as value 
                FROM attribute_values av 
                JOIN attribute_value_translations avt ON av.id = avt.attribute_value_id AND avt.language_id = 1
                WHERE av.attribute_id = :aid
                ORDER BY av.id ASC
            ", [':aid' => $attr['id']]);
        }

        return $this->render('admin/products/create', [
            'categories' => $categories,
            'brands' => $brands,
            'allProducts' => $allProducts,
            'attributes' => $attributes
        ]);
    }

    public function store(Request $request, Response $response): void {
        try {
            $data = $request->post();
            $data['seo'] = $request->post('seo') ?? [];
            $data['variants'] = $request->post('variants') ?? [];
            $data['product_files'] = $request->post('product_files') ?? [];
            $data['relations'] = $request->post('relations') ?? [];

            $this->service->create($data);
            $response->redirect('/admin/products?success=' . urlencode('Ürün başarıyla oluşturuldu.'));
        } catch (Exception $e) {
            $response->redirect('/admin/products/create?error=' . urlencode($e->getMessage()));
        }
    }

    public function showEdit(Request $request, Response $response): string {
        $id = (int)$request->get('id');
        $product = $this->repository->getById($id, true);
        if (!$product) {
            $response->redirect('/admin/products?error=' . urlencode('Ürün bulunamadı.'));
            exit;
        }

        // Increment view count
        $this->db->execute("UPDATE products SET view_count = view_count + 1 WHERE id = :id", [':id' => $id]);

        $categories = $this->categoryRepository->getAll();
        $brands = $this->brandRepository->getAll(['is_active' => 1]);
        $seo = $this->service->getSeoMeta($id);
        $gallery = $this->repository->getGalleryImages($id);
        $tags = array_map(function($t) { return $t['name']; }, $this->repository->getTags($id));
        $files = $this->repository->getFiles($id);
        $relations = $this->repository->getRelations($id);
        $variants = $this->repository->getVariants($id);
        $allProducts = $this->repository->getAll();

        // Fetch all attributes for variants
        $attributes = $this->db->query("
            SELECT a.*, at.name 
            FROM attributes a 
            JOIN attribute_translations at ON a.id = at.attribute_id AND at.language_id = 1
            ORDER BY a.id ASC
        ");
        foreach ($attributes as &$attr) {
            $attr['values'] = $this->db->query("
                SELECT av.*, avt.name as value 
                FROM attribute_values av 
                JOIN attribute_value_translations avt ON av.id = avt.attribute_value_id AND avt.language_id = 1
                WHERE av.attribute_id = :aid
                ORDER BY av.id ASC
            ", [':aid' => $attr['id']]);
        }

        // Audit view activity
        $auditLogger = \Core\Application::getInstance()->getContainer()->get(\App\Services\AuditLogger::class);
        $auditLogger->logActivity('product_view', "Ürün detayları görüntülendi: " . $product['name'] . " (ID: {$id})");

        // Fetch stock movements for the product
        $stockMovements = $this->db->query(
            "SELECT im.*, i.product_id, i.variant_id
             FROM inventory_movements im
             JOIN inventories i ON im.inventory_id = i.id
             WHERE i.product_id = :pid
             ORDER BY im.id DESC LIMIT 50",
            [':pid' => $id]
        );

        // Fetch product documents
        $documents = $this->db->query(
            "SELECT * FROM product_documents WHERE product_id = :pid ORDER BY id ASC",
            [':pid' => $id]
        );

        // Fetch Product history from audit_logs
        $history = $this->db->query(
            "SELECT al.*, a.username as admin_name 
             FROM audit_logs al
             LEFT JOIN admins a ON al.user_id = a.id
             WHERE al.auditable_type = 'Product' AND al.auditable_id = :id
             ORDER BY al.id DESC",
            [':id' => $id]
        );

        return $this->render('admin/products/edit', [
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
            'seo' => $seo,
            'gallery' => $gallery,
            'tags' => implode(', ', $tags),
            'files' => $files,
            'documents' => $documents ?? [],
            'relations' => $relations,
            'variants' => $variants,
            'allProducts' => $allProducts,
            'attributes' => $attributes,
            'history' => $history,
            'stockMovements' => $stockMovements ?? []
        ]);
    }

    public function update(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $data = $request->post();
            $data['seo'] = $request->post('seo') ?? [];
            $data['variants'] = $request->post('variants') ?? [];
            $data['product_files'] = $request->post('product_files') ?? [];
            $data['relations'] = $request->post('relations') ?? [];

            $this->service->update($id, $data);
            $response->redirect('/admin/products?success=' . urlencode('Ürün başarıyla güncellendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/products/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->service->softDelete($id);
            $response->redirect('/admin/products?success=' . urlencode('Ürün geri dönüşüm kutusuna taşındı.'));
        } catch (Exception $e) {
            $response->redirect('/admin/products?error=' . urlencode($e->getMessage()));
        }
    }

    public function restore(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->service->restore($id);
            $response->redirect('/admin/products?success=' . urlencode('Ürün başarıyla geri yüklendi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/products?error=' . urlencode($e->getMessage()));
        }
    }

    public function forceDelete(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $this->service->forceDelete($id);
            $response->redirect('/admin/products?success=' . urlencode('Ürün kalıcı olarak silindi.'));
        } catch (Exception $e) {
            $response->redirect('/admin/products?error=' . urlencode($e->getMessage()));
        }
    }

    public function duplicate(Request $request, Response $response): void {
        $id = (int)$request->post('id');
        try {
            $newId = $this->service->duplicate($id);
            $response->redirect('/admin/products?success=' . urlencode('Ürün başarıyla kopyalandı. Yeni Ürün ID: ' . $newId));
        } catch (Exception $e) {
            $response->redirect('/admin/products?error=' . urlencode($e->getMessage()));
        }
    }

    public function bulk(Request $request, Response $response): void {
        $action = $request->post('action') ?? '';
        $ids = $request->post('product_ids') ?? [];

        if (empty($ids)) {
            $response->redirect('/admin/products?error=' . urlencode('Seçili ürün bulunamadı.'));
            return;
        }

        try {
            $this->db->beginTransaction();

            if ($action === 'delete') {
                foreach ($ids as $id) {
                    $this->service->softDelete((int)$id);
                }
            } elseif ($action === 'active' || $action === 'publish') {
                foreach ($ids as $id) {
                    $this->db->execute("UPDATE products SET is_active = 1, status = 'published' WHERE id = :id", [':id' => (int)$id]);
                }
            } elseif ($action === 'passive') {
                foreach ($ids as $id) {
                    $this->db->execute("UPDATE products SET is_active = 0, status = 'passive' WHERE id = :id", [':id' => (int)$id]);
                }
            } elseif ($action === 'category') {
                $catId = (int)$request->post('target_category_id');
                foreach ($ids as $id) {
                    $this->db->execute("DELETE FROM product_category_relations WHERE product_id = :id", [':id' => (int)$id]);
                    $this->db->execute("INSERT INTO product_category_relations (product_id, category_id) VALUES (:pid, :cid)", [':pid' => (int)$id, ':cid' => $catId]);
                }
            } elseif ($action === 'brand') {
                $brandId = (int)$request->post('target_brand_id');
                foreach ($ids as $id) {
                    $this->db->execute("UPDATE products SET brand_id = :bid WHERE id = :id", [':bid' => $brandId, ':id' => (int)$id]);
                }
            } elseif ($action === 'price') {
                $price = (float)$request->post('bulk_price');
                foreach ($ids as $id) {
                    $prod = $this->repository->getById((int)$id);
                    if ($prod) {
                        $cost = (float)$prod['cost_price'];
                        $profit = $price - $cost;
                        $margin = $price > 0 ? ($profit / $price) * 100 : 0.0;
                        $rate = $cost > 0 ? ($profit / $cost) * 100 : 0.0;
                        $this->db->execute(
                            "UPDATE products SET price = :price, profit = :profit, profit_margin = :margin, profit_rate = :rate WHERE id = :id",
                            [':price' => $price, ':profit' => $profit, ':margin' => $margin, ':rate' => $rate, ':id' => (int)$id]
                        );
                    }
                }
            } elseif ($action === 'stock') {
                $stock = (int)$request->post('bulk_stock');
                foreach ($ids as $id) {
                    $this->db->execute("UPDATE products SET total_stock = :stock WHERE id = :id", [':stock' => $stock, ':id' => (int)$id]);
                    $this->db->execute(
                        "INSERT INTO inventories (product_id, variant_id, stock) VALUES (:pid, NULL, :stock) 
                         ON DUPLICATE KEY UPDATE stock = :stock",
                        [':pid' => (int)$id, ':stock' => $stock]
                    );
                }
            }

            $this->db->commit();
            $this->service->clearCache();
            $response->redirect('/admin/products?success=' . urlencode('Toplu işlem başarıyla tamamlandı.'));
        } catch (Exception $e) {
            $this->db->rollBack();
            $response->redirect('/admin/products?error=' . urlencode($e->getMessage()));
        }
    }

    public function export(Request $request, Response $response): void {
        $format = $request->get('format') ?? 'csv';
        $products = $this->repository->getAll();
        
        if ($format === 'xml') {
            $xml = $this->service->generateXmlSitemap();
            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename=products_export_' . date('Ymd_His') . '.xml');
            echo $xml;
            exit;
        }

        if ($format === 'excel') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename=products_export_' . date('Ymd_His') . '.xls');
            echo "<table border='1'>
                    <tr>
                        <th>ID</th>
                        <th>Ürün Adı</th>
                        <th>SKU</th>
                        <th>Barkod</th>
                        <th>Kategori</th>
                        <th>Marka</th>
                        <th>Stok</th>
                        <th>Alış Fiyatı</th>
                        <th>Satış Fiyatı</th>
                        <th>Kar</th>
                        <th>Kar Marjı (%)</th>
                        <th>Durum</th>
                        <th>Eklenme Tarihi</th>
                    </tr>";
            foreach ($products as $p) {
                echo "<tr>
                        <td>{$p['id']}</td>
                        <td>" . htmlspecialchars($p['name']) . "</td>
                        <td>" . htmlspecialchars($p['sku']) . "</td>
                        <td>" . htmlspecialchars($p['barcode'] ?? '') . "</td>
                        <td>" . htmlspecialchars($p['category_name'] ?? 'Kategorisiz') . "</td>
                        <td>" . htmlspecialchars($p['brand_name'] ?? 'Markasız') . "</td>
                        <td>{$p['total_stock']}</td>
                        <td>{$p['cost_price']}</td>
                        <td>{$p['price']}</td>
                        <td>{$p['profit']}</td>
                        <td>" . number_format((float)$p['profit_margin'], 2) . "</td>
                        <td>{$p['status']}</td>
                        <td>{$p['created_at']}</td>
                      </tr>";
            }
            echo "</table>";
            exit;
        }

        // CSV export fallback
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=products_export_' . date('Ymd_His') . '.csv');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['ID', 'Ürün Adı', 'SKU', 'Barkod', 'Kategori', 'Marka', 'Stok', 'Alış Fiyatı', 'Satış Fiyatı', 'Kar', 'Kar Marjı (%)', 'Durum', 'Eklenme Tarihi']);
        
        foreach ($products as $p) {
            fputcsv($output, [
                $p['id'],
                $p['name'],
                $p['sku'],
                $p['barcode'],
                $p['category_name'] ?? 'Kategorisiz',
                $p['brand_name'] ?? 'Markasız',
                $p['total_stock'],
                $p['cost_price'],
                $p['price'],
                $p['profit'],
                number_format((float)$p['profit_margin'], 2),
                $p['status'],
                $p['created_at']
            ]);
        }
        fclose($output);
        exit;
    }

    public function import(Request $request, Response $response): void {
        try {
            if (empty($_FILES['import_file']['tmp_name'])) {
                throw new Exception("Lütfen geçerli bir dosya yükleyin.");
            }

            $originalName = $_FILES['import_file']['name'];
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            
            if (!in_array(strtolower($ext), ['csv', 'xml', 'json', 'xls', 'xlsx'])) {
                throw new Exception("Desteklenmeyen dosya formatı.");
            }

            $tempDir = dirname(dirname(__DIR__)) . '/public/uploads/temp';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $tempFileName = 'import_' . uniqid() . '.' . $ext;
            $tempPath = $tempDir . '/' . $tempFileName;

            if (!move_uploaded_file($_FILES['import_file']['tmp_name'], $tempPath)) {
                throw new Exception("Dosya yüklenirken bir hata oluştu.");
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['import_file_path'] = $tempPath;
            $_SESSION['import_file_ext'] = $ext;

            $response->redirect('/admin/products/import/mapping');
        } catch (Exception $e) {
            $response->redirect('/admin/products?error=' . urlencode($e->getMessage()));
        }
    }

    public function showImportMapping(Request $request, Response $response): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_GET['mock_import'])) {
            $mockFile = sys_get_temp_dir() . '/mock_import.csv';
            file_put_contents($mockFile, "name,sku,price,stock,status\n");
            $_SESSION['import_file_path'] = $mockFile;
            $_SESSION['import_file_ext'] = 'csv';
        }
        $filePath = $_SESSION['import_file_path'] ?? '';
        $ext = $_SESSION['import_file_ext'] ?? '';

        if (empty($filePath) || !file_exists($filePath)) {
            $response->redirect('/admin/products?error=' . urlencode("İçe aktarılacak dosya bulunamadı veya süresi doldu."));
            exit;
        }

        $headers = $this->service->parseHeaders($filePath, $ext);
        return $this->render('admin/products/import_mapping', [
            'headers' => $headers
        ]);
    }

    public function processImport(Request $request, Response $response): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $filePath = $_SESSION['import_file_path'] ?? '';
        $ext = $_SESSION['import_file_ext'] ?? '';
        $mapping = $request->post('mapping') ?? [];

        try {
            if (empty($filePath) || !file_exists($filePath)) {
                throw new Exception("Dosya bulunamadı.");
            }
            if (empty($mapping['name']) || empty($mapping['sku']) || empty($mapping['price']) || empty($mapping['total_stock'])) {
                throw new Exception("Zorunlu alanları (Ürün Adı, SKU, Fiyat, Stok) eşleştirmelisiniz.");
            }

            $res = $this->service->importMappedData($filePath, $ext, $mapping);
            
            @unlink($filePath);
            unset($_SESSION['import_file_path']);
            unset($_SESSION['import_file_ext']);

            $auditLogger = \Core\Application::getInstance()->getContainer()->get(\App\Services\AuditLogger::class);
            $auditLogger->logActivity('product_import', "Toplu ürün aktarımı yapıldı. Eklenen: {$res['imported']}, Güncellenen: {$res['updated']}");

            $response->redirect('/admin/products?success=' . urlencode("İçe aktarım tamamlandı. Yeni: {$res['imported']}, Güncellenen: {$res['updated']}"));
        } catch (Exception $e) {
            $response->redirect('/admin/products?error=' . urlencode($e->getMessage()));
        }
    }

    public function apiIndex(Request $request, Response $response): void {
        try {
            $products = $this->service->getTreeCached(['status' => 'published']);
            $response->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiShow(Request $request, Response $response): void {
        $path = $request->getUri();
        $parts = explode('/', trim($path, '/'));
        $sku = end($parts);

        try {
            $product = $this->db->query(
                "SELECT p.*, pt.name, pt.description 
                 FROM products p
                 JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
                 WHERE p.sku = :sku AND p.deleted_at IS NULL LIMIT 1",
                [':sku' => $sku]
            )[0] ?? null;

            if ($product) {
                $response->json([
                    'success' => true,
                    'data' => $product
                ]);
            } else {
                $response->json(['success' => false, 'message' => 'Ürün bulunamadı.'], 404);
            }
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiStore(Request $request, Response $response): void {
        try {
            $data = json_decode($request->getBody(), true) ?? $request->post();
            if (empty($data)) {
                throw new Exception("Geçersiz veya boş veri.");
            }
            $productId = $this->service->create($data);
            $response->json([
                'success' => true,
                'message' => 'Ürün başarıyla oluşturuldu.',
                'product_id' => $productId
            ], 201);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function apiUpdate(Request $request, Response $response): void {
        $path = $request->getUri();
        $parts = explode('/', trim($path, '/'));
        $sku = end($parts);

        try {
            $prod = $this->db->query("SELECT id FROM products WHERE sku = :sku AND deleted_at IS NULL LIMIT 1", [':sku' => $sku]);
            if (empty($prod)) {
                throw new Exception("Ürün bulunamadı.");
            }
            $id = (int)$prod[0]['id'];

            $data = json_decode($request->getBody(), true) ?? $request->post();
            if (empty($data)) {
                throw new Exception("Geçersiz veya boş veri.");
            }

            $this->service->update($id, $data);
            $response->json([
                'success' => true,
                'message' => 'Ürün başarıyla güncellendi.'
            ]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function apiDelete(Request $request, Response $response): void {
        $path = $request->getUri();
        $parts = explode('/', trim($path, '/'));
        $sku = end($parts);

        try {
            $prod = $this->db->query("SELECT id FROM products WHERE sku = :sku AND deleted_at IS NULL LIMIT 1", [':sku' => $sku]);
            if (empty($prod)) {
                throw new Exception("Ürün bulunamadı.");
            }
            $id = (int)$prod[0]['id'];

            $this->service->softDelete($id);
            $response->json([
                'success' => true,
                'message' => 'Ürün başarıyla silindi (Soft Delete).'
            ]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
