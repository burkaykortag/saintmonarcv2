<?php
declare(strict_types=1);

/**
 * Enterprise Design System & Component Library - CLI Unit Tests
 */

define('ROOT_DIR', __DIR__);

// Autoload setup
spl_autoload_register(function (string $class) {
    $prefixMap = ['Core\\' => 'core/', 'App\\' => 'app/'];
    foreach ($prefixMap as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) continue;
        $file = ROOT_DIR . '/' . $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (file_exists($file)) { require $file; return; }
    }
});

$passed = 0;
$failed = 0;

function testCase(string $name, callable $fn) {
    global $passed, $failed;
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
echo "  SPRINT 22 — ENTERPRISE UI COMPONENT SYSTEM TESTS\n";
echo str_repeat('═', 62) . "\n\n";

use App\Helpers\Ui;

// --- TEST SET 1: BUTTON COMPONENT ---
testCase('1. Button renders default text', function() {
    $html = Ui::button();
    return str_contains($html, 'Button') ? true : 'Default text missing';
});

testCase('2. Button renders custom primary class', function() {
    $html = Ui::button(['type' => 'primary']);
    return str_contains($html, 'btn-primary') ? true : 'btn-primary class missing';
});

testCase('3. Button renders custom warning class', function() {
    $html = Ui::button(['type' => 'warning']);
    return str_contains($html, 'text-warning') ? true : 'Warning style missing';
});

testCase('4. Button renders small size', function() {
    $html = Ui::button(['size' => 'small']);
    return str_contains($html, 'btn-sm') ? true : 'btn-sm missing';
});

testCase('5. Button renders large size', function() {
    $html = Ui::button(['size' => 'large']);
    return str_contains($html, 'btn-lg') ? true : 'btn-lg missing';
});

testCase('6. Button renders loader inside when loading', function() {
    $html = Ui::button(['loading' => true]);
    return str_contains($html, 'spinner-border') ? true : 'Spinner spinner missing';
});

testCase('7. Button renders disabled attribute when loading', function() {
    $html = Ui::button(['loading' => true]);
    return str_contains($html, 'disabled') ? true : 'Disabled attribute missing';
});

testCase('8. Button renders icon markup', function() {
    $html = Ui::button(['icon' => 'plus']);
    return str_contains($html, 'data-lucide="plus"') ? true : 'Icon tag missing';
});

testCase('9. Button preserves custom attributes', function() {
    $html = Ui::button(['attributes' => 'data-test="demo"']);
    return str_contains($html, 'data-test="demo"') ? true : 'Custom attributes missing';
});

// --- TEST SET 2: CARD COMPONENT ---
testCase('10. Card renders title', function() {
    $html = Ui::card(['title' => 'Test Ciro']);
    return str_contains($html, 'Test Ciro') ? true : 'Title missing';
});

testCase('11. Card renders value', function() {
    $html = Ui::card(['value' => '₺10,000']);
    return str_contains($html, '₺10,000') ? true : 'Value missing';
});

testCase('12. Card renders custom icon color', function() {
    $html = Ui::card(['icon' => 'banknote', 'color' => '#ff0000']);
    return str_contains($html, 'color: #ff0000') ? true : 'Icon color missing';
});

testCase('13. Card renders custom body', function() {
    $html = Ui::card(['body' => '<p>lorem ipsum</p>']);
    return str_contains($html, 'lorem ipsum') ? true : 'Body content missing';
});

testCase('14. Card renders custom footer', function() {
    $html = Ui::card(['footer' => '<span>footer</span>']);
    return str_contains($html, 'card-footer') ? true : 'Footer section missing';
});

// --- TEST SET 3: DATAGRID COMPONENT ---
testCase('15. DataGrid renders empty rows fallback', function() {
    $html = Ui::datagrid(['headers' => ['İsim']]);
    return str_contains($html, 'veri bulunamadı') ? true : 'Fallback text missing';
});

testCase('16. DataGrid renders all table headers', function() {
    $html = Ui::datagrid(['headers' => ['Ad', 'Soyad', 'E-Posta']]);
    return (str_contains($html, 'Ad') && str_contains($html, 'Soyad') && str_contains($html, 'E-Posta')) ? true : 'Headers missing';
});

testCase('17. DataGrid renders table row values', function() {
    $html = Ui::datagrid([
        'headers' => ['Ad'],
        'rows' => [['Caner']]
    ]);
    return str_contains($html, 'Caner') ? true : 'Row values missing';
});

testCase('18. DataGrid renders bulk actions panel', function() {
    $html = Ui::datagrid([
        'headers' => ['Ad'],
        'bulk_actions' => ['Toplu Sil' => '#']
    ]);
    return str_contains($html, 'Toplu İşlemler') ? true : 'Bulk actions area missing';
});

// --- TEST SET 4: INPUT & SELECT COMPONENTS ---
testCase('19. Input renders label', function() {
    $html = Ui::input(['label' => 'Kullanıcı Adı']);
    return str_contains($html, 'Kullanıcı Adı') ? true : 'Label missing';
});

testCase('20. Input renders type password', function() {
    $html = Ui::input(['type' => 'password']);
    return str_contains($html, 'type=\'password\'') ? true : 'Type password missing';
});

testCase('21. Input renders placeholder text', function() {
    $html = Ui::input(['placeholder' => 'Arama yap...']);
    return str_contains($html, 'placeholder=\'Arama yap...\'') ? true : 'Placeholder missing';
});

testCase('22. Select renders options', function() {
    $html = Ui::select(['options' => ['active' => 'Aktif', 'passive' => 'Pasif']]);
    return (str_contains($html, 'value=\'active\'') && str_contains($html, 'Aktif')) ? true : 'Options missing';
});

testCase('23. Select pre-selects correct option', function() {
    $html = Ui::select([
        'options' => ['active' => 'Aktif', 'passive' => 'Pasif'],
        'selected' => 'passive'
    ]);
    return str_contains($html, 'value=\'passive\' selected') ? true : 'Selected option incorrect';
});

// --- TEST SET 5: MODALS & DRAWERS ---
testCase('24. Modal renders header title', function() {
    $html = Ui::modal(['title' => 'Ödeme Detayı']);
    return str_contains($html, 'Ödeme Detayı') ? true : 'Modal title missing';
});

testCase('25. Modal renders body slots', function() {
    $html = Ui::modal(['body' => '<p>demo modal content</p>']);
    return str_contains($html, 'demo modal content') ? true : 'Modal body content missing';
});

testCase('26. Modal renders footer actions', function() {
    $html = Ui::modal(['footer' => '<button>Onayla</button>']);
    return str_contains($html, 'modal-footer') ? true : 'Modal footer missing';
});

testCase('27. Drawer renders offcanvas direction right', function() {
    $html = Ui::drawer(['direction' => 'right']);
    return str_contains($html, 'offcanvas-end') ? true : 'Direction right missing';
});

testCase('28. Drawer renders offcanvas direction left', function() {
    $html = Ui::drawer(['direction' => 'left']);
    return str_contains($html, 'offcanvas-start') ? true : 'Direction left missing';
});

// --- TEST SET 6: EMPTY STATE & LOADERS ---
testCase('29. EmptyState renders custom message', function() {
    $html = Ui::emptyState(['message' => 'Sipariş Bulunmuyor']);
    return str_contains($html, 'Sipariş Bulunmuyor') ? true : 'Message missing';
});

testCase('30. Loader renders card loader by default', function() {
    $html = Ui::loader();
    return str_contains($html, 'placeholder-glow') ? true : 'Skeleton classes missing';
});

echo "\n" . str_repeat('═', 62) . "\n";
$total = $passed + $failed;
echo "  SONUÇ: {$passed}/{$total} test BAŞARILI" . ($failed > 0 ? ", {$failed} BAŞARISIZ" : '') . "\n";
echo str_repeat('═', 62) . "\n\n";

if ($failed === 0) {
    echo "  ✅  TÜM DESIGN SYSTEM BİLEŞEN TESTLERİ BAŞARIYLA TAMAMLANDI!\n\n";
    exit(0);
} else {
    echo "  ⚠️   Bazı testler başarısız. Lütfen hataları giderin.\n\n";
    exit(1);
}
