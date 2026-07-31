<?php
declare(strict_types=1);

/**
 * Sprint 29 – Enterprise PIM V2
 * Test Suite: UI/View Layer Tests
 *
 * Bu test dosyası yalnızca View/UI katmanını test eder.
 * Business Logic, Controller, API ve Database katmanlarına dokunulmaz.
 */

define('ROOT_DIR', __DIR__);

// ── Autoload ─────────────────────────────────────────────
if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    require_once ROOT_DIR . '/vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class): void {
        $map = ['Core\\' => 'core/', 'App\\' => 'app/'];
        foreach ($map as $prefix => $base) {
            if (str_starts_with($class, $prefix)) {
                $file = ROOT_DIR . '/' . $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                if (file_exists($file)) { require $file; return; }
            }
        }
    });
}

// ── Test Runner ───────────────────────────────────────────
$passed = 0;
$failed = 0;
$errors = [];

function test(string $name, callable $fn, int &$passed, int &$failed, array &$errors): void {
    try {
        $result = $fn();
        if ($result !== false) {
            echo "  \033[32m[PASS]\033[0m {$name}\n";
            $passed++;
        } else {
            echo "  \033[31m[FAIL]\033[0m {$name}\n";
            $failed++;
            $errors[] = $name;
        }
    } catch (\Throwable $e) {
        echo "  \033[31m[ERROR]\033[0m {$name}: " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = $name . ': ' . $e->getMessage();
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "  Sprint 29 – Enterprise PIM V2 – Test Suite\n";
echo "═══════════════════════════════════════════════════════\n\n";

// ─── 1. VIEW FILE EXISTENCE TESTS ────────────────────────
echo "1. View Dosyaları Varlık Testleri\n";
$viewFiles = [
    'products/index.php'  => 'PIM V2 Index',
    'products/create.php' => 'PIM Create',
    'products/edit.php'   => 'PIM Edit',
    'products/reports.php'=> 'PIM Reports',
    'products/show.php'   => 'PIM Show',
    'layouts/header.php'  => 'Header Layout',
    'layouts/footer.php'  => 'Footer Layout',
    'layouts/sidebar.php' => 'Sidebar Layout',
];
$viewBase = ROOT_DIR . '/resources/views/admin/';
foreach ($viewFiles as $file => $label) {
    test("View mevcut: {$label}", function () use ($viewBase, $file) {
        return file_exists($viewBase . $file);
    }, $passed, $failed, $errors);
}

// ─── 2. SIDEBAR TESTS ────────────────────────────────────
echo "\n2. Sidebar UI Testleri\n";
$sidebarContent = file_get_contents($viewBase . 'layouts/sidebar.php');

test('Sidebar: id="sidebar-wrapper" mevcut', function () use ($sidebarContent) {
    return str_contains($sidebarContent, 'id="sidebar-wrapper"');
}, $passed, $failed, $errors);

test('Sidebar: Dashboard linki mevcut', function () use ($sidebarContent) {
    return str_contains($sidebarContent, 'speedometer2');
}, $passed, $failed, $errors);

test('Sidebar: Alt menüler collapse başlıyor (aria-expanded=false)', function () use ($sidebarContent) {
    return str_contains($sidebarContent, 'aria-expanded="false"');
}, $passed, $failed, $errors);

test('Sidebar: data-bs-toggle="collapse" mevcut', function () use ($sidebarContent) {
    return str_contains($sidebarContent, 'data-bs-toggle="collapse"');
}, $passed, $failed, $errors);

test('Sidebar: Katalog bölümü mevcut', function () use ($sidebarContent) {
    return str_contains($sidebarContent, 'menu-katalog');
}, $passed, $failed, $errors);

test('Sidebar: Sistem bölümü mevcut', function () use ($sidebarContent) {
    return str_contains($sidebarContent, 'menu-sistem');
}, $passed, $failed, $errors);

test('Sidebar: Duplicate olmadan tek sidebar-heading', function () use ($sidebarContent) {
    return substr_count($sidebarContent, 'sidebar-heading') === 1;
}, $passed, $failed, $errors);

test('Sidebar: RBAC kullanıyor', function () use ($sidebarContent) {
    return str_contains($sidebarContent, 'adminHasPermission');
}, $passed, $failed, $errors);

// ─── 3. PIM INDEX PAGE TESTS ─────────────────────────────
echo "\n3. PIM Index (Enterprise Data Grid) Testleri\n";
$indexContent = file_get_contents($viewBase . 'products/index.php');

test('PIM Index: Enterprise PIM başlığı', function () use ($indexContent) {
    return str_contains($indexContent, 'Enterprise PIM');
}, $passed, $failed, $errors);

test('PIM Index: KPI Grid mevcut', function () use ($indexContent) {
    return str_contains($indexContent, 'pim-kpi-grid');
}, $passed, $failed, $errors);

test('PIM Index: Data Grid tablosu mevcut', function () use ($indexContent) {
    return str_contains($indexContent, 'pim-table');
}, $passed, $failed, $errors);

test('PIM Index: Sticky header kolon', function () use ($indexContent) {
    return str_contains($indexContent, 'col-check') && str_contains($indexContent, 'col-thumb');
}, $passed, $failed, $errors);

test('PIM Index: Bulk işlemler mevcut', function () use ($indexContent) {
    return str_contains($indexContent, 'pimBulkPrepare');
}, $passed, $failed, $errors);

test('PIM Index: Toplu Yayınla seçeneği', function () use ($indexContent) {
    return str_contains($indexContent, "'publish'");
}, $passed, $failed, $errors);

test('PIM Index: Toplu Silme seçeneği', function () use ($indexContent) {
    return str_contains($indexContent, "'delete'");
}, $passed, $failed, $errors);

test('PIM Index: Toplu Fiyat Güncelle', function () use ($indexContent) {
    return str_contains($indexContent, "'price'");
}, $passed, $failed, $errors);

test('PIM Index: Toplu Stok Güncelle', function () use ($indexContent) {
    return str_contains($indexContent, "'stock'");
}, $passed, $failed, $errors);

test('PIM Index: Toplu Kategori Değiştir', function () use ($indexContent) {
    return str_contains($indexContent, "'category'");
}, $passed, $failed, $errors);

test('PIM Index: Toplu Marka Değiştir', function () use ($indexContent) {
    return str_contains($indexContent, "'brand'");
}, $passed, $failed, $errors);

test('PIM Index: Column Visibility Chooser', function () use ($indexContent) {
    return str_contains($indexContent, 'pim-col-toggle');
}, $passed, $failed, $errors);

test('PIM Index: Liste Görünümü toggle', function () use ($indexContent) {
    return str_contains($indexContent, 'setView');
}, $passed, $failed, $errors);

test('PIM Index: Kart Görünümü', function () use ($indexContent) {
    return str_contains($indexContent, 'pim-card-grid');
}, $passed, $failed, $errors);

test('PIM Index: Anlık arama', function () use ($indexContent) {
    return str_contains($indexContent, 'pimInstantSearch');
}, $passed, $failed, $errors);

test('PIM Index: Filtre Paneli (Kategori)', function () use ($indexContent) {
    return str_contains($indexContent, 'category_id');
}, $passed, $failed, $errors);

test('PIM Index: Filtre Paneli (Marka)', function () use ($indexContent) {
    return str_contains($indexContent, 'brand_id');
}, $passed, $failed, $errors);

test('PIM Index: Filtre Paneli (Durum)', function () use ($indexContent) {
    return str_contains($indexContent, 'name="status"');
}, $passed, $failed, $errors);

test('PIM Index: Stok göstergesi (progress bar)', function () use ($indexContent) {
    return str_contains($indexContent, 'stock-bar');
}, $passed, $failed, $errors);

test('PIM Index: AI Skoru sütunu', function () use ($indexContent) {
    return str_contains($indexContent, 'ai-score');
}, $passed, $failed, $errors);

test('PIM Index: Status badge sistemi', function () use ($indexContent) {
    return str_contains($indexContent, 'status-pill') && str_contains($indexContent, 'sp-published');
}, $passed, $failed, $errors);

test('PIM Index: Fiyat hücresi (marj göstergesi)', function () use ($indexContent) {
    return str_contains($indexContent, 'price-cell');
}, $passed, $failed, $errors);

test('PIM Index: Sayfalama (pagination)', function () use ($indexContent) {
    return str_contains($indexContent, 'pim-pagination');
}, $passed, $failed, $errors);

test('PIM Index: Geri Dönüşüm Kutusu tab', function () use ($indexContent) {
    return str_contains($indexContent, 'panel-trash');
}, $passed, $failed, $errors);

test('PIM Index: Import Modal', function () use ($indexContent) {
    return str_contains($indexContent, 'importModal');
}, $passed, $failed, $errors);

test('PIM Index: Export dropdown (CSV, Excel, XML, PDF)', function () use ($indexContent) {
    return str_contains($indexContent, 'format=csv') && str_contains($indexContent, 'format=excel') && str_contains($indexContent, 'format=pdf');
}, $passed, $failed, $errors);

test('PIM Index: CSRF token koruması', function () use ($indexContent) {
    return str_contains($indexContent, 'csrfToken');
}, $passed, $failed, $errors);

test('PIM Index: ComponentHelper::breadcrumb kullanımı', function () use ($indexContent) {
    return str_contains($indexContent, 'ComponentHelper::breadcrumb');
}, $passed, $failed, $errors);

test('PIM Index: Responsive CSS (media query)', function () use ($indexContent) {
    return str_contains($indexContent, '@media');
}, $passed, $failed, $errors);

test('PIM Index: Sort fonksiyonu', function () use ($indexContent) {
    return str_contains($indexContent, 'pimSort');
}, $passed, $failed, $errors);

test('PIM Index: Keyboard navigation (ESC)', function () use ($indexContent) {
    return str_contains($indexContent, "key === 'Escape'");
}, $passed, $failed, $errors);

test('PIM Index: Skeleton loading CSS', function () use ($indexContent) {
    return str_contains($indexContent, 'skeleton') && str_contains($indexContent, 'shimmer');
}, $passed, $failed, $errors);

test('PIM Index: CSS Custom Properties (Design Tokens)', function () use ($indexContent) {
    return str_contains($indexContent, '--pim-gold') && str_contains($indexContent, '--pim-bg');
}, $passed, $failed, $errors);

test('PIM Index: Quick action dropdown (SEO, Medya, Fiyat)', function () use ($indexContent) {
    return str_contains($indexContent, 'tab=seo') && str_contains($indexContent, 'tab=media');
}, $passed, $failed, $errors);

test('PIM Index: header.php include', function () use ($indexContent) {
    return str_contains($indexContent, "include dirname(__DIR__) . '/layouts/header.php'");
}, $passed, $failed, $errors);

test('PIM Index: footer.php include', function () use ($indexContent) {
    return str_contains($indexContent, "include dirname(__DIR__) . '/layouts/footer.php'");
}, $passed, $failed, $errors);

// ─── 4. HEADER LAYOUT TESTS ──────────────────────────────
echo "\n4. Header Layout Testleri\n";
$headerContent = file_get_contents($viewBase . 'layouts/header.php');

test('Header: sidebar include mevcut', function () use ($headerContent) {
    return str_contains($headerContent, 'sidebar.php');
}, $passed, $failed, $errors);

test('Header: Sidebar scroll CSS (overflow-y:auto)', function () use ($headerContent) {
    return str_contains($headerContent, 'overflow-y: auto');
}, $passed, $failed, $errors);

test('Header: Google Fonts (Outfit)', function () use ($headerContent) {
    return str_contains($headerContent, 'Outfit');
}, $passed, $failed, $errors);

test('Header: Bootstrap 5 CDN', function () use ($headerContent) {
    return str_contains($headerContent, 'bootstrap@5');
}, $passed, $failed, $errors);

test('Header: Bootstrap Icons CDN', function () use ($headerContent) {
    return str_contains($headerContent, 'bootstrap-icons');
}, $passed, $failed, $errors);

test('Header: Responsive meta viewport', function () use ($headerContent) {
    return str_contains($headerContent, 'viewport');
}, $passed, $failed, $errors);

test('Header: Navbar search input', function () use ($headerContent) {
    return str_contains($headerContent, 'search-input');
}, $passed, $failed, $errors);

// ─── 5. EDIT PAGE TESTS ──────────────────────────────────
echo "\n5. PIM Edit Sayfası Testleri\n";
$editContent = file_get_contents($viewBase . 'products/edit.php');

test('Edit: SEO panel mevcut', function () use ($editContent) {
    return str_contains($editContent, 'seo') || str_contains($editContent, 'SEO');
}, $passed, $failed, $errors);

test('Edit: Varyant yönetimi mevcut', function () use ($editContent) {
    return str_contains($editContent, 'variant') || str_contains($editContent, 'Varyant');
}, $passed, $failed, $errors);

test('Edit: CSRF koruması', function () use ($editContent) {
    return str_contains($editContent, 'csrf_token');
}, $passed, $failed, $errors);

test('Edit: Breadcrumb mevcut', function () use ($editContent) {
    return str_contains($editContent, 'breadcrumb');
}, $passed, $failed, $errors);

test('Edit: Tab paneli mevcut', function () use ($editContent) {
    return str_contains($editContent, 'nav-tabs') || str_contains($editContent, 'tab-pane');
}, $passed, $failed, $errors);

// ─── 6. CREATE PAGE TESTS ────────────────────────────────
echo "\n6. PIM Create Sayfası Testleri\n";
$createContent = file_get_contents($viewBase . 'products/create.php');

test('Create: Form action mevcut', function () use ($createContent) {
    return str_contains($createContent, '<form');
}, $passed, $failed, $errors);

test('Create: CSRF koruması', function () use ($createContent) {
    return str_contains($createContent, 'csrf_token');
}, $passed, $failed, $errors);

test('Create: SKU alanı mevcut', function () use ($createContent) {
    return str_contains($createContent, 'name="sku"') || str_contains($createContent, 'SKU');
}, $passed, $failed, $errors);

// ─── 7. REPORTS PAGE TESTS ───────────────────────────────
echo "\n7. PIM Reports Sayfası Testleri\n";
$reportsContent = file_exists($viewBase . 'products/reports.php') ? file_get_contents($viewBase . 'products/reports.php') : '';

test('Reports: Dosya mevcut', function () use ($reportsContent) {
    return !empty($reportsContent);
}, $passed, $failed, $errors);

// ─── 8. ACCESSIBILITY TESTS ──────────────────────────────
echo "\n8. Erişilebilirlik (Accessibility) Testleri\n";

test('Index: ARIA label/controls mevcut', function () use ($indexContent) {
    return str_contains($indexContent, 'aria-') || str_contains($indexContent, 'role=');
}, $passed, $failed, $errors);

test('Index: Alt text için title attribute', function () use ($indexContent) {
    return str_contains($indexContent, 'title=');
}, $passed, $failed, $errors);

test('Sidebar: role="button" mevcut', function () use ($sidebarContent) {
    return str_contains($sidebarContent, 'role="button"');
}, $passed, $failed, $errors);

test('Sidebar: aria-controls mevcut', function () use ($sidebarContent) {
    return str_contains($sidebarContent, 'aria-controls=');
}, $passed, $failed, $errors);

// ─── 9. PERFORMANCE TESTS ────────────────────────────────
echo "\n9. Performans Testleri\n";

test('Index: Lazy loading (img loading=lazy)', function () use ($indexContent) {
    return str_contains($indexContent, 'loading="lazy"');
}, $passed, $failed, $errors);

test('Index: CSS Custom Properties (performanslı)', function () use ($indexContent) {
    return str_contains($indexContent, 'var(--pim-');
}, $passed, $failed, $errors);

test('Index: transition/animation CSS', function () use ($indexContent) {
    return str_contains($indexContent, 'transition:') || str_contains($indexContent, 'animation:');
}, $passed, $failed, $errors);

// ─── SUMMARY ─────────────────────────────────────────────
echo "\n═══════════════════════════════════════════════════════\n";
$total = $passed + $failed;
echo "  Toplam Test   : {$total}\n";
echo "  \033[32mGeçen\033[0m         : {$passed}\n";
echo "  \033[31mBaşarısız\033[0m    : {$failed}\n";
echo "  Başarı Oranı  : " . ($total > 0 ? round($passed / $total * 100, 1) : 0) . "%\n";
echo "═══════════════════════════════════════════════════════\n";

if (!empty($errors)) {
    echo "\n\033[31mBaşarısız Testler:\033[0m\n";
    foreach ($errors as $err) {
        echo "  • {$err}\n";
    }
}

echo "\n  Sprint 29 – Enterprise PIM V2 Test Suite Tamamlandı.\n";
echo "  Test URL: http://localhost/SaintMonarc/admin/products\n\n";

exit($failed > 0 ? 1 : 0);
