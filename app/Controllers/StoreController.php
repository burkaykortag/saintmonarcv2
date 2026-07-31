<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\View\View;
use Exception;

class StoreController extends Controller
{
    private $db;

    public function __construct(View $view)
    {
        parent::__construct($view);
        $this->db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
    }

    public function home(Request $request, Response $response)
    {
        // Fetch categories for mega menu and home layout
        $categories = $this->db->query(
            "SELECT c.*, ct.name 
             FROM categories c 
             LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
             WHERE c.deleted_at IS NULL LIMIT 12"
        );

        // Fetch brands
        $brands = $this->db->query(
            "SELECT b.*, bt.name 
             FROM brands b 
             LEFT JOIN brand_translations bt ON b.id = bt.brand_id AND bt.language_id = 1
             WHERE b.deleted_at IS NULL LIMIT 12"
        );

        // Fetch products (new arrivals, best sellers)
        $products = $this->db->query(
            "SELECT p.*, pt.name, pt.short_description 
             FROM products p 
             LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.deleted_at IS NULL AND p.is_active = 1 LIMIT 12"
        );

        return $this->render('store/home/index', [
            'categories' => $categories,
            'brands' => $brands,
            'products' => $products
        ]);
    }

    public function products(Request $request, Response $response)
    {
        $products = $this->db->query(
            "SELECT p.*, pt.name, pt.short_description 
             FROM products p 
             LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.deleted_at IS NULL AND p.is_active = 1 LIMIT 24"
        );
        return $this->render('store/category/list', [
            'products' => $products
        ]);
    }

    public function category(Request $request, Response $response)
    {
        $slug = $request->getRouteParam('slug') ?? '';
        $products = $this->db->query(
            "SELECT p.*, pt.name, pt.short_description 
             FROM products p 
             LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.deleted_at IS NULL AND p.is_active = 1 LIMIT 24"
        );
        return $this->render('store/category/list', [
            'slug' => $slug,
            'products' => $products
        ]);
    }

    public function brand(Request $request, Response $response)
    {
        $slug = $request->getRouteParam('slug') ?? '';
        $products = $this->db->query(
            "SELECT p.*, pt.name, pt.short_description 
             FROM products p 
             LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.deleted_at IS NULL AND p.is_active = 1 LIMIT 24"
        );
        return $this->render('store/brand/list', [
            'slug' => $slug,
            'products' => $products
        ]);
    }

    public function productDetail(Request $request, Response $response)
    {
        $slug = $request->getRouteParam('slug') ?? '';
        $product = $this->db->query(
            "SELECT p.*, pt.name, pt.description, pt.short_description 
             FROM products p 
             LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.slug = :slug OR p.id = :id LIMIT 1",
            [':slug' => $slug, ':id' => (int)$slug]
        );

        $prod = $product[0] ?? null;
        if (!$prod) {
            // fallback product
            $prod = [
                'id' => 1,
                'name' => 'Premium Tasarım Ürün',
                'description' => 'Açıklama detayı.',
                'short_description' => 'Özet.',
                'price' => 1499.00,
                'sku' => 'PRM-001',
                'total_stock' => 50,
                'slug' => 'premium-tasarim-urun'
            ];
        }

        return $this->render('store/product/detail', [
            'product' => $prod
        ]);
    }

    public function cart(Request $request, Response $response)
    {
        return $this->render('store/cart/index');
    }

    public function checkout(Request $request, Response $response)
    {
        return $this->render('store/checkout/index');
    }

    public function account(Request $request, Response $response)
    {
        return $this->render('store/customer/dashboard');
    }

    public function blog(Request $request, Response $response)
    {
        return $this->render('store/blog/index');
    }

    public function search(Request $request, Response $response)
    {
        $query = $request->get('q') ?? '';
        $products = $this->db->query(
            "SELECT p.*, pt.name, pt.short_description 
             FROM products p 
             LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.deleted_at IS NULL AND p.is_active = 1 LIMIT 12"
        );
        return $this->render('store/search/results', [
            'query' => $query,
            'products' => $products
        ]);
    }
}
