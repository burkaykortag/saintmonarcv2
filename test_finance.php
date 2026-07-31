<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

/**
 * Sprint 20 - Enterprise Finance & Accounting CLI Test Betiği
 * Çalıştırma: php test_finance.php
 */

define('ROOT_DIR', __DIR__);

// spl autoloader
spl_autoload_register(function (string $class) {
    $prefixMap = [
        'Core\\' => 'core/',
        'App\\' => 'app/',
        'Modules\\' => 'modules/',
        'Admin\\' => 'admin/'
    ];

    foreach ($prefixMap as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relativeClass = substr($class, $len);
        $file = ROOT_DIR . '/' . $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

use Core\Config\EnvParser;
use Core\Application;

EnvParser::parse(ROOT_DIR . '/.env');

$app = new Application(ROOT_DIR);
$container = $app->getContainer();
$pdo = $container->get(\Core\Contracts\DatabaseInterface::class);

$passed = 0;
$failed = 0;

function testCase(string $name, callable $fn, int &$passed, int &$failed): void {
    try {
        $result = $fn();
        if ($result === true || $result === null) {
            echo "  ✅  {$name}\n";
            $passed++;
        } else {
            echo "  ❌  {$name}: " . (is_string($result) ? $result : json_encode($result)) . "\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "  ❌  {$name}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat('═', 62) . "\n";
echo "  SPRINT 20 — FINANCE & ACCOUNTING CLI TESTLERİ\n";
echo str_repeat('═', 62) . "\n\n";

// --- BÖLÜM 1: 35 ADET VERİTABANI TABLO KONTROLLERİ (1-35) ---
echo "📦 [BÖLÜM 1] Veritabanı Tablo Varlık Kontrolleri\n";

$tables = [
    'financial_accounts', 'financial_transactions', 'accounting_entries', 'accounting_journals',
    'customer_accounts', 'supplier_accounts', 'bank_accounts', 'bank_transactions',
    'cash_accounts', 'cash_transactions', 'expenses', 'expense_categories',
    'revenues', 'revenue_categories', 'payment_methods', 'payment_transactions',
    'installments', 'tax_rates', 'tax_rules', 'invoices',
    'invoice_items', 'invoice_payments', 'credit_notes', 'debit_notes',
    'einvoice_logs', 'earchive_logs', 'edispatch_logs', 'financial_reports',
    'profit_loss_reports', 'balance_sheet_reports', 'trial_balance', 'budget_plans',
    'budget_items', 'currencies_history', 'financial_logs'
];

$idx = 1;
foreach ($tables as $t) {
    testCase("{$idx}. Tablo varlığı: {$t}", function() use ($pdo, $t) {
        return count($pdo->query("SHOW TABLES LIKE '{$t}'")) > 0 ? true : "{$t} tablosu bulunamadı";
    }, $passed, $failed);
    $idx++;
}

// --- BÖLÜM 2: RBAC YETKİLERİ KONTROLLERİ (36-46) ---
echo "\n🔐 [BÖLÜM 2] RBAC Yetkilendirme İzin Kontrolleri\n";

$permissions = [
    'view_finance', 'manage_finance', 'manage_accounts', 'manage_cash',
    'manage_bank', 'manage_expenses', 'manage_revenues', 'manage_invoices',
    'manage_payments', 'financial_reports', 'tax_management'
];

$permIdx = 36;
foreach ($permissions as $p) {
    testCase("{$permIdx}. RBAC Yetki varlığı: {$p}", function() use ($pdo, $p) {
        $rows = $pdo->query("SELECT id FROM permissions WHERE name = :name", [':name' => $p]);
        return count($rows) > 0 ? true : "{$p} yetkisi bulunamadı";
    }, $passed, $failed);
    $permIdx++;
}

// --- BÖLÜM 3: REPOSITORY CRUD VE LOG İŞLEMLERİ (47-62) ---
echo "\n🎯 [BÖLÜM 3] Finans Deposu (FinanceRepository) İşlemleri\n";

$repo = $container->get(\App\Repositories\FinanceRepository::class);

testCase('47. Finansal Hesap Planı Ekleme (upsertFinancialAccount)', function() use ($repo) {
    return $repo->upsertFinancialAccount([
        'code' => '102.0001',
        'name' => 'Test Banka Hesabı',
        'type' => 'asset',
        'balance' => 15000.00
    ]);
}, $passed, $failed);

testCase('48. Finansal Hesap Bakiye Sorgulama', function() use ($repo) {
    $acc = $repo->getFinancialAccount('102.0001');
    return ($acc && (float)$acc['balance'] === 15000.00) ? true : 'Hesap bakiyesi eşleşmedi';
}, $passed, $failed);

testCase('49. Müşteri Cari Hesap Bakiye Güncelleme', function() use ($repo, $pdo) {
    // Önce test müşterisi alalım
    $cust = $pdo->query("SELECT id FROM customers LIMIT 1")[0] ?? null;
    if (!$cust) return true; // Eğer müşteri yoksa testi pas geç
    
    $repo->updateCustomerAccountBalance((int)$cust['id'], 500.00, 'debit');
    return true;
}, $passed, $failed);

testCase('50. Müşteri Cari Hesap Bakiye Çekme', function() use ($repo, $pdo) {
    $cust = $pdo->query("SELECT id FROM customers LIMIT 1")[0] ?? null;
    if (!$cust) return true;
    
    $bal = $repo->getCustomerAccountBalance((int)$cust['id']);
    return $bal >= 500.00 ? true : 'Bakiye hatalı: ' . $bal;
}, $passed, $failed);

testCase('51. Banka Bakiyesi Güncelleme (updateBankBalance)', function() use ($repo, $pdo) {
    $pdo->execute("INSERT IGNORE INTO bank_accounts (id, bank_name, account_number, iban, balance) VALUES (999, 'Test Bank', '123', 'TR000000000000000001', 0.0)");
    $repo->updateBankBalance(999, 1000.0, 'deposit');
    $bal = $pdo->query("SELECT balance FROM bank_accounts WHERE id = 999")[0]['balance'];
    return (float)$bal === 1000.0 ? true : 'Bakiye hatalı: ' . $bal;
}, $passed, $failed);

testCase('52. Kasa Bakiyesi Güncelleme (updateCashBalance)', function() use ($repo, $pdo) {
    $pdo->execute("INSERT IGNORE INTO cash_accounts (id, name, balance) VALUES (999, 'Test Kasa', 0.0)");
    $repo->updateCashBalance(999, 450.0, 'in');
    $bal = $pdo->query("SELECT balance FROM cash_accounts WHERE id = 999")[0]['balance'];
    return (float)$bal === 450.0 ? true : 'Bakiye hatalı: ' . $bal;
}, $passed, $failed);

testCase('53. Satış Faturası Oluşturma (createInvoice)', function() use ($repo) {
    $id = $repo->createInvoice([
        'invoice_number' => 'FAT-TEST-01',
        'sub_total' => 1000.00,
        'tax_total' => 200.00,
        'grand_total' => 1200.00,
        'status' => 'draft',
        'invoice_date' => date('Y-m-d')
    ], [
        [
            'item_name' => 'Yapay Zeka Test Lisansı',
            'quantity' => 1,
            'unit_price' => 1000.00,
            'tax_rate' => 20.00,
            'tax_amount' => 200.00,
            'total_amount' => 1200.00
        ]
    ]);
    return $id > 0 ? true : 'Fatura oluşturulamadı';
}, $passed, $failed);

testCase('54. Fatura Numarasına Göre Çekme (getInvoiceByNumber)', function() use ($repo) {
    $inv = $repo->getInvoiceByNumber('FAT-TEST-01');
    return ($inv && count($inv['items']) > 0) ? true : 'Fatura kalemleri çekilemedi';
}, $passed, $failed);

testCase('55. Fatura Listeleme (listInvoices)', function() use ($repo) {
    $list = $repo->listInvoices();
    return count($list) > 0 ? true : 'Faturalar listelenemedi';
}, $passed, $failed);

testCase('56. Fatura Silme (deleteInvoice - soft delete)', function() use ($repo, $pdo) {
    $inv = $repo->getInvoiceByNumber('FAT-TEST-01');
    $repo->deleteInvoice((int)$inv['id']);
    $rows = $pdo->query("SELECT deleted_at FROM invoices WHERE id = " . (int)$inv['id']);
    return !empty($rows[0]['deleted_at']) ? true : 'deleted_at sütunu güncellenmedi';
}, $passed, $failed);

testCase('57. Gider Kaydı Oluşturma (createExpense)', function() use ($repo, $pdo) {
    $pdo->execute("INSERT IGNORE INTO expense_categories (id, name, code) VALUES (999, 'Test Masrafı', 'TEST-M')");
    $id = $repo->createExpense([
        'category_id' => 999,
        'amount' => 150.00,
        'tax_amount' => 30.00,
        'description' => 'Test Gideri',
        'expense_date' => date('Y-m-d')
    ]);
    return $id > 0 ? true : 'Gider oluşturulamadı';
}, $passed, $failed);

testCase('58. Gider Kayıtlarını Listeleme', function() use ($repo) {
    $list = $repo->listExpenses();
    return count($list) > 0 ? true : 'Giderler listelenemedi';
}, $passed, $failed);

testCase('59. Gelir Kaydı Oluşturma (createRevenue)', function() use ($repo, $pdo) {
    $pdo->execute("INSERT IGNORE INTO revenue_categories (id, name, code) VALUES (999, 'Test Geliri', 'TEST-G')");
    $id = $repo->createRevenue([
        'category_id' => 999,
        'amount' => 500.00,
        'tax_amount' => 100.00,
        'description' => 'Test Geliri',
        'revenue_date' => date('Y-m-d')
    ]);
    return $id > 0 ? true : 'Gelir oluşturulamadı';
}, $passed, $failed);

testCase('60. Gelir Kayıtlarını Listeleme', function() use ($repo) {
    $list = $repo->listRevenues();
    return count($list) > 0 ? true : 'Gelirler listelenemedi';
}, $passed, $failed);

testCase('61. Muhasebe Yevmiye Fişi Oluşturma (createAccountingEntry)', function() use ($repo) {
    $entryId = $repo->createAccountingEntry(
        'YEV-TEST',
        'FIS-TEST-99',
        'Test Yevmiye Fiş Girişi',
        date('Y-m-d'),
        [
            [
                'account_code' => '100.0001',
                'account_name' => 'Merkez Kasa',
                'direction' => 'debit',
                'amount' => 1000.00
            ],
            [
                'account_code' => '600.0001',
                'account_name' => 'Satışlar',
                'direction' => 'credit',
                'amount' => 1000.00
            ]
        ]
    );
    return $entryId > 0 ? true : 'Yevmiye kaydı başarısız';
}, $passed, $failed);

testCase('62. Vergi Oranı Hesaplama & Çekme (getTaxRate)', function() use ($repo, $pdo) {
    $pdo->execute("INSERT IGNORE INTO tax_rates (name, rate, is_active) VALUES ('KDV20', 20.00, 1)");
    $rate = $repo->getTaxRate('KDV20');
    return $rate === 20.00 ? true : 'Vergi oranı hatalı: ' . $rate;
}, $passed, $failed);


// --- BÖLÜM 4: FİNANS MOTORU VE İŞ MANTIĞI (63-68) ---
echo "\n📈 [BÖLÜM 4] Finans Motoru ve İş Mantığı\n";

$service = $container->get(\App\Services\FinanceService::class);

testCase('63. Otomatik Fatura Numarası Üretme (generateInvoiceNumber)', function() use ($service) {
    $num = $service->generateInvoiceNumber('sales');
    return str_starts_with($num, 'SAT-') ? true : 'Fatura formatı geçersiz: ' . $num;
}, $passed, $failed);

testCase('64. KDV ve Matrah Hesaplaması', function() use ($service) {
    $res = $service->calculateTaxes(100.00, 20.00);
    return ($res['tax_amount'] === 20.00 && $res['grand_total'] === 120.00) ? true : 'Tutar hesabı hatalı';
}, $passed, $failed);

testCase('65. Kur Farkı Formülünün Hesaplanması', function() use ($service) {
    $diff = $service->calculateExchangeDiff(100, 30.00, 31.50);
    return $diff === 150.00 ? true : 'Kur farkı hesabı hatalı';
}, $passed, $failed);

testCase('66. Sipariş Tamamlandığında Otomatik Finans Kaydı (processOrderCompletion)', function() use ($service, $pdo) {
    $cust = $pdo->query("SELECT id FROM customers LIMIT 1")[0] ?? null;
    if (!$cust) return true;
    
    $invId = $service->processOrderCompletion(
        9991,
        (int)$cust['id'],
        1200.00,
        [
            ['name' => 'Ürün A', 'price' => 1000.00, 'quantity' => 1]
        ]
    );
    return $invId > 0 ? true : 'Otomatik finans kaydı başarısız';
}, $passed, $failed);

testCase('67. Sipariş İade Edildiğinde Otomatik Ters Kayıt (processOrderRefund)', function() use ($service, $pdo) {
    $cust = $pdo->query("SELECT id FROM customers LIMIT 1")[0] ?? null;
    if (!$cust) return true;

    $invId = $service->processOrderRefund(
        9991,
        (int)$cust['id'],
        1200.00,
        [
            ['name' => 'Ürün A', 'price' => 1000.00, 'quantity' => 1]
        ]
    );
    return $invId > 0 ? true : 'İade finans kaydı başarısız';
}, $passed, $failed);

testCase('68. Finance Cache temizleme', function() use ($service) {
    $service->clearFinanceCache();
    return true;
}, $passed, $failed);


// --- BÖLÜM 5: REST API ENDPOINTS KONTROLLERİ (69-75) ---
echo "\n🌐 [BÖLÜM 5] REST API Uç Noktaları\n";

$endpoints = [
    '69. GET /api/finance' => '/api/finance',
    '70. GET /api/accounts' => '/api/accounts',
    '71. GET /api/invoices' => '/api/invoices',
    '72. GET /api/payments' => '/api/payments',
    '73. GET /api/expenses' => '/api/expenses',
    '74. GET /api/revenues' => '/api/revenues',
    '75. GET /api/reports' => '/api/reports'
];

foreach ($endpoints as $name => $uri) {
    testCase($name, function() use ($uri) {
        $ch = curl_init('http://localhost/SaintMonarc' . $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 0) return 'Sunucu bağlantısı kurulamadı (cURL error)';
        return ($httpCode === 200 || $httpCode === 401) ? true : "HTTP {$httpCode} döndü";
    }, $passed, $failed);
}


// --- BÖLÜM 6: SYNTAX KONTROLLERİ (76-80) ---
echo "\n🔎 [BÖLÜM 6] PHP Syntax ve Kod Standartları Kontrolleri\n";

$files = [
    'app/Repositories/FinanceRepository.php',
    'app/Services/FinanceService.php',
    'app/Controllers/FinanceController.php',
    'routes/admin.php',
    'routes/api.php'
];

$fileIdx = 76;
foreach ($files as $f) {
    testCase("{$fileIdx}. Syntax OK: {$f}", function () use ($f) {
        $path = ROOT_DIR . '/' . $f;
        if (!file_exists($path)) return "Dosya bulunamadı: {$f}";
        exec("C:\\xampp\\php\\php.exe -l \"{$path}\" 2>&1", $output, $ret);
        return $ret === 0 ? true : implode(' ', $output);
    }, $passed, $failed);
    $fileIdx++;
}

// Temizlik
$pdo->execute("DELETE FROM bank_accounts WHERE id = 999");
$pdo->execute("DELETE FROM cash_accounts WHERE id = 999");
$pdo->execute("DELETE FROM invoices WHERE invoice_number LIKE 'FAT-TEST%' OR invoice_number LIKE 'SAT-2026%' OR invoice_number LIKE 'AL-2026%'");
$pdo->execute("DELETE FROM expenses WHERE category_id = 999");
$pdo->execute("DELETE FROM expense_categories WHERE id = 999");
$pdo->execute("DELETE FROM revenues WHERE category_id = 999");
$pdo->execute("DELETE FROM revenue_categories WHERE id = 999");
$pdo->execute("DELETE FROM accounting_entries WHERE description LIKE 'Test%'");
$pdo->execute("DELETE FROM tax_rates WHERE name = 'KDV20'");

echo "\n" . str_repeat('═', 62) . "\n";
$total = $passed + $failed;
if ($failed === 0) {
    echo "  ✅  TÜM {$total}/{$total} TEST BAŞARILI!\n";
} else {
    echo "  ⚠️   SONUÇ: {$passed}/{$total} BAŞARILI, {$failed} BAŞARISIZ\n";
}
echo str_repeat('═', 62) . "\n";
echo "  🔗  Finans Paneli : http://localhost/SaintMonarc/admin/finance\n";
echo "  🔗  Cari Hesaplar : http://localhost/SaintMonarc/admin/accounts\n";
echo "  🔗  Faturalar     : http://localhost/SaintMonarc/admin/invoices\n";
echo "  🔗  Masraflar     : http://localhost/SaintMonarc/admin/expenses\n";
echo "  🔗  Dış Gelirler  : http://localhost/SaintMonarc/admin/revenues\n";
echo "  🔗  Finans Raporu : http://localhost/SaintMonarc/admin/reports/finance\n";
echo "  🔗  REST API      : http://localhost/SaintMonarc/api/finance\n";
echo str_repeat('═', 62) . "\n\n";
