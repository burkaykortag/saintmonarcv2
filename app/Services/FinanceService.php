<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\FinanceRepository;
use Core\Contracts\CacheInterface;
use Exception;

class FinanceService {
    private FinanceRepository $repository;
    private CacheInterface $cache;

    public function __construct(FinanceRepository $repository, CacheInterface $cache) {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    /**
     * Otomatik fatura numarası üretir (örn: FAT-2026-0000001).
     */
    public function generateInvoiceNumber(string $type = 'sales'): string {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $year = date('Y');
        $prefix = $type === 'sales' ? 'SAT' : 'AL';
        
        $rows = $db->query(
            "SELECT invoice_number FROM invoices 
             WHERE invoice_number LIKE :prefix 
             ORDER BY id DESC LIMIT 1",
            [':prefix' => $prefix . '-' . $year . '-%']
        );

        if (empty($rows)) {
            $num = 1;
        } else {
            $parts = explode('-', $rows[0]['invoice_number']);
            $num = (int)end($parts) + 1;
        }

        return $prefix . '-' . $year . '-' . sprintf('%07d', $num);
    }

    /**
     * KDV ve Vergileri Hesaplar.
     */
    public function calculateTaxes(float $subTotal, float $taxRatePercent = 20.00): array {
        $taxAmount = $subTotal * ($taxRatePercent / 100);
        $grandTotal = $subTotal + $taxAmount;

        return [
            'sub_total' => $subTotal,
            'tax_rate' => $taxRatePercent,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal
        ];
    }

    /**
     * Kur Farkı Hesaplar.
     */
    public function calculateExchangeDiff(float $amountForeign, float $oldRate, float $newRate): float {
        $oldVal = $amountForeign * $oldRate;
        $newVal = $amountForeign * $newRate;
        return $newVal - $oldVal; // Pozitif ise kur geliri, negatif ise kur gideri
    }

    /**
     * Sipariş tamamlandığında otomatik fatura ve muhasebe fiş kaydı oluşturur.
     */
    public function processOrderCompletion(int $orderId, int $customerId, float $grandTotal, array $items): int {
        $subTotal = 0.0;
        $taxTotal = 0.0;
        $invoiceItems = [];

        foreach ($items as $item) {
            $taxRate = 20.00; // Varsayılan KDV oranı
            $price = (float)$item['price'];
            $qty = (int)$item['quantity'];
            
            $itemSub = $price * $qty;
            $itemTax = $itemSub * ($taxRate / 100);
            $itemTotal = $itemSub + $itemTax;

            $subTotal += $itemSub;
            $taxTotal += $itemTax;

            $invoiceItems[] = [
                'item_name' => $item['name'] ?? 'Ürün Açıklaması',
                'quantity' => $qty,
                'unit_price' => $price,
                'tax_rate' => $taxRate,
                'tax_amount' => $itemTax,
                'total_amount' => $itemTotal
            ];
        }

        $invNum = $this->generateInvoiceNumber('sales');
        
        // 1. Fatura Oluştur
        $invoiceId = $this->repository->createInvoice([
            'invoice_number' => $invNum,
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'invoice_type' => 'sales',
            'sub_total' => $subTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $subTotal + $taxTotal,
            'status' => 'completed',
            'invoice_date' => date('Y-m-d'),
            'uuid' => bin2hex(random_bytes(16)),
            'qr_code' => 'QR-INV-' . $invNum
        ], $invoiceItems);

        // 2. Müşteri Cari Bakiyesini Borçlandır (Müşteri Alacağı Artar / Bize Borçlanır)
        $this->repository->updateCustomerAccountBalance($customerId, $subTotal + $taxTotal, 'debit');

        // 3. Otomatik Muhasebe Yevmiye Fişi Oluştur (Double-entry)
        // 120 Alıcılar Hesabı BORÇLU
        // 600 Yurtiçi Satışlar ALACAKLI
        // 391 Hesaplanan KDV ALACAKLI
        $this->repository->createAccountingEntry(
            'YEV-SATIS',
            'FIS-' . $invNum,
            'Sipariş #' . $orderId . ' Tamamlanma Fişi',
            date('Y-m-d'),
            [
                [
                    'account_code' => '120.0001',
                    'account_name' => 'Yurt İçi Alıcılar - Müşteri #' . $customerId,
                    'direction' => 'debit',
                    'amount' => $subTotal + $taxTotal
                ],
                [
                    'account_code' => '600.0001',
                    'account_name' => 'Yurt İçi Satışlar %20',
                    'direction' => 'credit',
                    'amount' => $subTotal
                ],
                [
                    'account_code' => '391.0001',
                    'account_name' => 'Hesaplanan KDV %20',
                    'direction' => 'credit',
                    'amount' => $taxTotal
                ]
            ]
        );

        // Cache Temizle
        $this->clearFinanceCache();

        return $invoiceId;
    }

    /**
     * Sipariş iade edildiğinde otomatik ters kayıt fişi ve iade faturası oluşturur.
     */
    public function processOrderRefund(int $orderId, int $customerId, float $refundTotal, array $items): int {
        $subTotal = 0.0;
        $taxTotal = 0.0;
        $invoiceItems = [];

        foreach ($items as $item) {
            $taxRate = 20.00;
            $price = (float)$item['price'];
            $qty = (int)$item['quantity'];

            $itemSub = $price * $qty;
            $itemTax = $itemSub * ($taxRate / 100);
            $itemTotal = $itemSub + $itemTax;

            $subTotal += $itemSub;
            $taxTotal += $itemTax;

            $invoiceItems[] = [
                'item_name' => 'İADE: ' . ($item['name'] ?? 'Ürün Açıklaması'),
                'quantity' => $qty,
                'unit_price' => $price,
                'tax_rate' => $taxRate,
                'tax_amount' => $itemTax,
                'total_amount' => $itemTotal
            ];
        }

        $invNum = $this->generateInvoiceNumber('return');

        // 1. İade Faturası Oluştur
        $invoiceId = $this->repository->createInvoice([
            'invoice_number' => $invNum,
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'invoice_type' => 'return',
            'sub_total' => $subTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $subTotal + $taxTotal,
            'status' => 'completed',
            'invoice_date' => date('Y-m-d'),
            'uuid' => bin2hex(random_bytes(16)),
            'qr_code' => 'QR-RET-' . $invNum
        ], $invoiceItems);

        // 2. Müşteri Cari Bakiyesini Alacaklandır (Ters işlem)
        $this->repository->updateCustomerAccountBalance($customerId, $subTotal + $taxTotal, 'credit');

        // 3. Ters Muhasebe Fişi Oluştur
        // 610 Satıştan İadeler BORÇLU
        // 191 İndirilecek KDV BORÇLU
        // 120 Alıcılar ALACAKLI
        $this->repository->createAccountingEntry(
            'YEV-IADE',
            'FIS-' . $invNum,
            'Sipariş #' . $orderId . ' İade Fişi',
            date('Y-m-d'),
            [
                [
                    'account_code' => '610.0001',
                    'account_name' => 'Satıştan İadeler',
                    'direction' => 'debit',
                    'amount' => $subTotal
                ],
                [
                    'account_code' => '191.0001',
                    'account_name' => 'İndirilecek KDV %20',
                    'direction' => 'debit',
                    'amount' => $taxTotal
                ],
                [
                    'account_code' => '120.0001',
                    'account_name' => 'Yurt İçi Alıcılar - Müşteri #' . $customerId,
                    'direction' => 'credit',
                    'amount' => $subTotal + $taxTotal
                ]
            ]
        );

        $this->clearFinanceCache();

        return $invoiceId;
    }

    /**
     * Cache Temizleme.
     */
    public function clearFinanceCache(): void {
        $this->cache->delete('finance_summary');
        $this->cache->delete('invoice_list');
        $this->cache->delete('tax_cache');
        $this->cache->delete('account_cache');
    }
}
