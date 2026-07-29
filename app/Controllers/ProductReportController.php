<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Contracts\DatabaseInterface;
use Core\View\View;

class ProductReportController extends Controller {
    private DatabaseInterface $db;

    public function __construct(View $view, DatabaseInterface $db) {
        parent::__construct($view);
        $this->db = $db;
    }

    public function index(Request $request, Response $response): string {
        // Fetch Most Viewed
        $mostViewed = $this->db->query(
            "SELECT p.id, p.sku, pt.name, p.view_count 
             FROM products p
             JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.deleted_at IS NULL AND p.view_count > 0
             ORDER BY p.view_count DESC LIMIT 10"
        );

        // Fetch Best Sellers
        $bestSellers = $this->db->query(
            "SELECT p.id, p.sku, pt.name, SUM(oi.quantity) as total_sold, SUM(oi.total) as total_revenue
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.deleted_at IS NULL
             GROUP BY p.id
             ORDER BY total_sold DESC LIMIT 10"
        );

        // Fetch Most Favorited
        $mostFavorited = $this->db->query(
            "SELECT p.id, p.sku, pt.name, COUNT(f.id) as favorite_count
             FROM favorites f
             JOIN products p ON f.product_id = p.id
             JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.deleted_at IS NULL
             GROUP BY p.id
             ORDER BY favorite_count DESC LIMIT 10"
        );

        // Fetch Most Added to Cart
        $mostAddedToCart = $this->db->query(
            "SELECT p.id, p.sku, pt.name, SUM(ci.quantity) as cart_count
             FROM cart_items ci
             JOIN products p ON ci.product_id = p.id
             JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.deleted_at IS NULL
             GROUP BY p.id
             ORDER BY cart_count DESC LIMIT 10"
        );

        // Fetch Highest Profit
        $highestProfit = $this->db->query(
            "SELECT p.id, p.sku, pt.name, p.price, p.cost_price, p.profit
             FROM products p
             JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.deleted_at IS NULL AND p.profit IS NOT NULL
             ORDER BY p.profit DESC LIMIT 10"
        );

        // Fetch Lowest Profit
        $lowestProfit = $this->db->query(
            "SELECT p.id, p.sku, pt.name, p.price, p.cost_price, p.profit
             FROM products p
             JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.deleted_at IS NULL AND p.profit IS NOT NULL
             ORDER BY p.profit ASC LIMIT 10"
        );

        // Fetch Non Selling
        $nonSelling = $this->db->query(
            "SELECT p.id, p.sku, pt.name, p.price, p.total_stock
             FROM products p
             JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
             WHERE p.deleted_at IS NULL AND p.status = 'published' AND p.id NOT IN (
                 SELECT DISTINCT product_id FROM order_items WHERE product_id IS NOT NULL
             )
             ORDER BY p.id DESC LIMIT 10"
        );

        return $this->render('admin/products/reports', [
            'mostViewed' => $mostViewed,
            'bestSellers' => $bestSellers,
            'mostFavorited' => $mostFavorited,
            'mostAddedToCart' => $mostAddedToCart,
            'highestProfit' => $highestProfit,
            'lowestProfit' => $lowestProfit,
            'nonSelling' => $nonSelling
        ]);
    }
}
