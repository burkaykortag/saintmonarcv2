<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\DatabaseInterface;
use App\Repositories\OrderRepository;
use App\Repositories\VendorRepository;
use App\Repositories\ProductRepository;
use App\Services\WarehouseService;
use App\Services\AuditLogger;
use Exception;

class MarketplaceOrderService {
    private DatabaseInterface $db;
    private OrderRepository $orderRepository;
    private VendorRepository $vendorRepository;
    private ProductRepository $productRepository;
    private WarehouseService $warehouseService;
    private AuditLogger $auditLogger;

    public function __construct(
        DatabaseInterface $db,
        OrderRepository $orderRepository,
        VendorRepository $vendorRepository,
        ProductRepository $productRepository,
        WarehouseService $warehouseService,
        AuditLogger $auditLogger
    ) {
        $this->db = $db;
        $this->orderRepository = $orderRepository;
        $this->vendorRepository = $vendorRepository;
        $this->productRepository = $productRepository;
        $this->warehouseService = $warehouseService;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Creates a Multi-Vendor Marketplace Order (Parent Order) and splits it into child Vendor Orders.
     */
    public function createMarketplaceOrder(array $data, array $cartItems, int $userId): array {
        if (empty($cartItems)) {
            throw new Exception("Sepetinizde ürün bulunmamaktadır.");
        }

        $this->db->beginTransaction();
        try {
            // 1. Group items by vendor_id
            $vendorGroups = [];
            $subtotal = 0;
            $taxTotal = 0;
            $grandTotal = 0;

            foreach ($cartItems as $item) {
                $product = $this->productRepository->getById((int)$item['product_id']);
                if (!$product) {
                    throw new Exception("Ürün bulunamadı (ID: {$item['product_id']})");
                }

                $vendorId = !empty($product['vendor_id']) ? (int)$product['vendor_id'] : 1;
                $qty = (int)($item['quantity'] ?? 1);
                $price = (float)($product['price'] ?? 0.00);
                $taxRate = (float)($product['tax_rate'] ?? 20.00);

                $rowSubtotal = $qty * $price;
                $rowTax = $rowSubtotal * ($taxRate / 100);

                $subtotal += $rowSubtotal;
                $taxTotal += $rowTax;

                if (!isset($vendorGroups[$vendorId])) {
                    $vendorGroups[$vendorId] = [
                        'items' => [],
                        'subtotal' => 0,
                        'tax_total' => 0,
                        'quantity' => 0
                    ];
                }

                $vendorGroups[$vendorId]['items'][] = [
                    'product_id' => $product['id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $qty,
                    'price' => $price,
                    'subtotal' => $rowSubtotal,
                    'tax' => $rowTax
                ];
                $vendorGroups[$vendorId]['subtotal'] += $rowSubtotal;
                $vendorGroups[$vendorId]['tax_total'] += $rowTax;
                $vendorGroups[$vendorId]['quantity'] += $qty;
            }

            $grandTotal = $subtotal + $taxTotal;
            $orderNumber = 'SM-' . date('Y') . '-' . sprintf('%06d', rand(1, 999999));

            // 2. Create Parent Order record
            $sqlOrder = "INSERT INTO orders (order_number, user_id, status, subtotal, tax_total, discount_total, shipping_total, grand_total, currency_code,
                                             billing_first_name, billing_last_name, billing_address, billing_city, billing_country, billing_zip,
                                             shipping_first_name, shipping_last_name, shipping_address, shipping_city, shipping_country, shipping_zip, created_at)
                         VALUES (:onum, :uid, 'pending', :sub, :tax, 0, 0, :grand, 'TRY',
                                 :bfn, :bln, :baddr, :bcity, :bcountry, :bzip,
                                 :sfn, :sln, :saddr, :scity, :scountry, :szip, NOW())";
            
            $this->db->execute($sqlOrder, [
                ':onum' => $orderNumber,
                ':uid' => $userId,
                ':sub' => $subtotal,
                ':tax' => $taxTotal,
                ':grand' => $grandTotal,
                ':bfn' => $data['billing_first_name'] ?? 'Müşteri',
                ':bln' => $data['billing_last_name'] ?? 'Adı',
                ':baddr' => $data['billing_address'] ?? 'Adres',
                ':bcity' => $data['billing_city'] ?? 'İstanbul',
                ':bcountry' => $data['billing_country'] ?? 'Türkiye',
                ':bzip' => $data['billing_zip'] ?? '34000',
                ':sfn' => $data['shipping_first_name'] ?? 'Müşteri',
                ':sln' => $data['shipping_last_name'] ?? 'Adı',
                ':saddr' => $data['shipping_address'] ?? 'Adres',
                ':scity' => $data['shipping_city'] ?? 'İstanbul',
                ':scountry' => $data['shipping_country'] ?? 'Türkiye',
                ':szip' => $data['shipping_zip'] ?? '34000'
            ]);

            $orderId = (int)$this->db->lastInsertId();
            $vendorOrderIds = [];

            // 3. Create Child Vendor Orders & Process Commission/Wallet/Stock
            foreach ($vendorGroups as $vendorId => $group) {
                $vendor = $this->vendorRepository->getVendor($vendorId);
                $commRate = (float)($vendor['commission_rate'] ?? 10.00);

                $vendorSubtotal = $group['subtotal'];
                $vendorTax = $group['tax_total'];
                $commAmount = round($vendorSubtotal * ($commRate / 100), 2);
                $payoutAmount = $vendorSubtotal - $commAmount;

                $vendorOrderId = $this->vendorRepository->createVendorOrder([
                    'vendor_id' => $vendorId,
                    'order_id' => $orderId,
                    'order_number' => $orderNumber . '-V' . $vendorId,
                    'item_price' => count($group['items']) > 0 ? $group['items'][0]['price'] : 0,
                    'quantity' => $group['quantity'],
                    'subtotal' => $vendorSubtotal,
                    'tax_total' => $vendorTax,
                    'commission_rate' => $commRate,
                    'commission_amount' => $commAmount,
                    'payout_amount' => $payoutAmount,
                    'status' => 'pending'
                ]);

                $vendorOrderIds[] = $vendorOrderId;

                // Credit payout to vendor wallet & record transaction
                $this->vendorRepository->addWalletTransaction([
                    'vendor_id' => $vendorId,
                    'type' => 'credit',
                    'amount' => $payoutAmount,
                    'reference_type' => 'order',
                    'reference_id' => $orderId,
                    'description' => "Pazaryeri Sipariş Hakedişi ({$orderNumber})"
                ]);

                // Record commission
                $this->vendorRepository->createCommission([
                    'vendor_id' => $vendorId,
                    'order_id' => $orderId,
                    'rate' => $commRate,
                    'calculated_amount' => $commAmount,
                    'status' => 'pending'
                ]);

                // Adjust WMS stock per item
                foreach ($group['items'] as $item) {
                    // WMS Stock Deduction
                    $this->warehouseService->adjustStock(
                        (int)$item['product_id'],
                        !empty($item['variant_id']) ? (int)$item['variant_id'] : null,
                        1, // Main warehouse
                        -$item['quantity'],
                        'out',
                        "Marketplace Sipariş Satışı ({$orderNumber})"
                    );
                }
            }

            $this->db->commit();
            $this->auditLogger->logActivity('marketplace_order_create', "Multi-vendor sepet siparişi oluşturuldu: {$orderNumber} (ID: {$orderId})");
            $this->auditLogger->logAudit('create', 'Order', $orderId, null, ['grand_total' => $grandTotal, 'vendors' => array_keys($vendorGroups)]);

            return [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'vendor_orders' => $vendorOrderIds
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
