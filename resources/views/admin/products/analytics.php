<?php
use App\Helpers\ComponentHelper;
$title = 'PIM – Ürün Analitiği | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';

// ── Mock Data ──────────────────────────────────────────────
$kpis = [
    ['Toplam Satış',    rand(1200,4500),  'bi-bag-check',          'success', '+'.rand(8,35).'%'],
    ['Toplam Gelir',    '₺'.number_format(rand(80000,350000),0,',','.'), 'bi-currency-exchange', 'gold', '+'.rand(5,22).'%'],
    ['Ort. Sipariş',    '₺'.number_format(rand(250,950),0,',','.'), 'bi-receipt',          'info',    '+'.rand(2,12).'%'],
    ['Dönüşüm Oranı',  '%'.rand(2,9).'.'.rand(1,9), 'bi-graph-up-arrow',   'purple',  '+'.rand(1,5).'%'],
    ['Görüntülenme',   number_format(rand(15000,95000),0,',','.'), 'bi-eye',              'cyan',    '+'.rand(10,40).'%'],
    ['Favorileme',     rand(500,3200),   'bi-heart',              'danger',  '+'.rand(3,18).'%'],
    ['İade Oranı',     '%'.rand(1,8),    'bi-arrow-return-left',  'warning', '-'.rand(1,4).'%'],
    ['Yeni Ürünler',   rand(8,45),       'bi-plus-circle',        'gold',    '+'.rand(2,10).'%'],
];

$topSellers = [];
$leastSellers = [];
$mostProfitable = [];
$criticalStock = [];
for ($i = 0; $i < 10; $i++) {
    $p = number_format(rand(150,2500),2); $c = number_format(rand(50,1200),2);
    $topSellers[]    = ['name'=>'Ürün '.($i+1).' – '.['Deri Cüzdan','Güneş Gözlüğü','Kadın Çanta','Erkek Kemer','Parfüm','Bileklik','Broş','Küpe','Kolye','Yüzük'][$i], 'sku'=>'SKU-'.str_pad($i+1,4,'0',STR_PAD_LEFT), 'sales'=>rand(120,900), 'revenue'=>'₺'.number_format(rand(15000,180000),0,',','.'), 'stock'=>rand(10,200), 'trend'=>rand(5,35)];
    $leastSellers[]  = ['name'=>'Ürün '.($i+11).' – '.['Kalem','Defter','Askı','Şapka','Çorap','Eldiven','Kemer','Toka','İğne','Rozet'][$i], 'sku'=>'SKU-'.str_pad($i+11,4,'0',STR_PAD_LEFT), 'sales'=>rand(1,18), 'revenue'=>'₺'.number_format(rand(500,8000),0,',','.'), 'stock'=>rand(0,30), 'trend'=>-rand(1,15)];
    $mostProfitable[]= ['name'=>'Ürün '.($i+21).' – '.['Altın Kolye','Elmas Yüzük','Deri Çanta','Lüks Parfüm','İpek Fular','Gümüş Bilezik','Taşlı Broş','Vintage Saat','El Yapımı Küpe','Özel Tasarım'][$i], 'cost'=>rand(100,800), 'price'=>rand(500,3000), 'margin'=>rand(25,70), 'profit'=>'₺'.number_format(rand(5000,80000),0,',','.')];
    if ($i < 5) $criticalStock[] = ['name'=>'Kritik Ürün '.($i+1), 'sku'=>'CRT-'.($i+1), 'stock'=>rand(0,4), 'min'=>5];
}

// SVG sparkline helper
function sparkline(array $data, string $color = '#c5a880', int $w = 120, int $h = 32): string {
    $max = max($data) ?: 1; $min = min($data);
    $n   = count($data); $step = $n > 1 ? $w / ($n - 1) : $w;
    $pts = [];
    foreach ($data as $i => $v) {
        $x = round($i * $step);
        $y = round($h - (($v - $min) / max(1, $max - $min)) * $h);
        $pts[] = "$x,$y";
    }
    $path = 'M ' . implode(' L ', $pts);
    $area = 'M ' . $pts[0] . ' L ' . implode(' L ', array_slice($pts, 1)) . " L {$w},{$h} L 0,{$h} Z";
    return "<svg width='$w' height='$h' viewBox='0 0 $w $h' fill='none' xmlns='http://www.w3.org/2000/svg'>
        <defs><linearGradient id='sg' x1='0' y1='0' x2='0' y2='1'>
            <stop offset='0%' stop-color='$color' stop-opacity='0.3'/>
            <stop offset='100%' stop-color='$color' stop-opacity='0'/>
        </linearGradient></defs>
        <path d='$area' fill='url(#sg)'/>
        <path d='$path' stroke='$color' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
    </svg>";
}

// Sales trend data (12 months)
$months = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
$salesTrend = [42,58,51,74,68,89,95,83,112,98,127,145];
$maxSales   = max($salesTrend);

// Category distribution
$catDist = [
    ['Takı & Aksesuar', 38, '#c5a880'],
    ['Çanta & Cüzdan',  22, '#8b5cf6'],
    ['Giyim',           17, '#06b6d4'],
    ['Kozmetik',        13, '#10b981'],
    ['Diğer',           10, '#f59e0b'],
];
$donutTotal = array_sum(array_column($catDist, 1));
$offset = 0;
$radius = 54; $circ = 2 * M_PI * $radius;
?>

<style>
/* ── Analytics-specific ──────────────────────────── */
.anal-section{background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius-lg);padding:24px;margin-bottom:20px}
.anal-section-title{font-size:15px;font-weight:700;color:var(--pim-text);margin-bottom:18px;display:flex;align-items:center;gap:8px}
.date-range-btns{display:flex;gap:4px}
.date-range-btn{padding:6px 14px;border-radius:8px;border:1px solid var(--pim-border);background:transparent;color:var(--pim-text-xs);font-size:12px;cursor:pointer;transition:var(--pim-transition)}
.date-range-btn:hover,.date-range-btn.active{background:var(--pim-gold-glow);border-color:var(--pim-gold);color:var(--pim-gold)}
.trend-bar{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:2px 7px;border-radius:20px}
.trend-up{background:var(--pim-success-bg);color:var(--pim-success)}
.trend-down{background:var(--pim-danger-bg);color:var(--pim-danger)}
.funnel-step{margin-bottom:10px}
.funnel-label{display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px}
.funnel-bar-bg{height:28px;background:rgba(255,255,255,.04);border-radius:6px;overflow:hidden}
.funnel-bar-fill{height:100%;border-radius:6px;display:flex;align-items:center;padding-left:12px;font-size:12px;font-weight:600;color:#fff;transition:width .8s var(--pim-ease)}
.rank-badge{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}
.rank-1{background:var(--pim-gold);color:#07051a}
.rank-2{background:#9ca3af;color:#07051a}
.rank-3{background:#b45309;color:#fff}
.rank-n{background:rgba(255,255,255,.08);color:var(--pim-text-xs)}
.crit-badge{display:inline-flex;align-items:center;gap:5px;background:var(--pim-danger-bg);color:var(--pim-danger);border:1px solid rgba(239,68,68,.3);border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600}
.crit-badge i{animation:pulse-icon 1.5s ease infinite}
@keyframes pulse-icon{0%,100%{opacity:1}50%{opacity:.4}}
.compare-col{flex:1;background:rgba(255,255,255,.02);border-radius:var(--pim-radius);padding:16px;border:1px solid var(--pim-border)}
.chart-grid-line{stroke:rgba(255,255,255,.06);stroke-width:1}
.chart-label{fill:var(--pim-text-xs);font-size:10px;font-family:var(--pim-font)}
</style>

<div class="pim-module">

<!-- ─── Header ─────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Ürünler' => url('/admin/products'), 'Analitiği' => '#']) ?>
        <h2 class="text-white fw-bold m-0 mt-1" style="font-size:22px"><i class="bi bi-bar-chart-line me-2 text-pim-gold"></i>Ürün Analitiği</h2>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="date-range-btns">
            <button class="date-range-btn active" onclick="setRange(this,'7d')">7G</button>
            <button class="date-range-btn" onclick="setRange(this,'30d')">30G</button>
            <button class="date-range-btn" onclick="setRange(this,'90d')">90G</button>
            <button class="date-range-btn" onclick="setRange(this,'1y')">1Y</button>
        </div>
        <a href="<?= url('/admin/products') ?>" class="pim-btn pim-btn-secondary pim-btn-sm"><i class="bi bi-arrow-left"></i> Ürünler</a>
        <button class="pim-btn pim-btn-ghost pim-btn-sm" onclick="window.print()"><i class="bi bi-download"></i> Dışa Aktar</button>
    </div>
</div>

<!-- ─── KPI Row ─────────────────────────────────────────── -->
<div class="pim-kpi-row mb-4" style="grid-template-columns:repeat(auto-fill,minmax(190px,1fr))">
    <?php foreach ($kpis as [$lbl, $val, $ico, $col, $trend]): ?>
    <div class="pim-kpi" style="--kpi-icon-bg:var(--pim-<?= $col ?>-bg);--kpi-icon-color:var(--pim-<?= $col ?>)">
        <div class="pim-kpi-icon"><i class="bi <?= $ico ?>"></i></div>
        <div style="flex:1;min-width:0">
            <div class="pim-kpi-val"><?= $val ?></div>
            <div class="pim-kpi-lbl"><?= $lbl ?></div>
            <div class="mt-1">
                <span class="trend-badge <?= str_starts_with($trend,'+') ? 'trend-up' : 'trend-down' ?>">
                    <i class="bi <?= str_starts_with($trend,'+') ? 'bi-arrow-up' : 'bi-arrow-down' ?>"></i><?= $trend ?>
                </span>
            </div>
        </div>
        <div class="ms-auto"><?= sparkline([rand(20,100),rand(30,120),rand(25,110),rand(40,130),rand(35,140),rand(50,150),rand(60,160)], 'var(--pim-'.$col.')') ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ─── Charts Row ──────────────────────────────────────── -->
<div class="row g-4 mb-4">

    <!-- Sales Trend SVG Line Chart -->
    <div class="col-lg-7">
        <div class="anal-section h-100">
            <div class="anal-section-title"><i class="bi bi-graph-up-arrow text-pim-gold"></i>Satış Trendi (12 Ay)</div>
            <?php
            $cw = 560; $ch = 200; $pad = 40;
            $dw = $cw - $pad * 2; $dh = $ch - $pad;
            $n  = count($salesTrend); $step = $dw / ($n - 1);
            $pts = [];
            foreach ($salesTrend as $i => $v) {
                $x = $pad + $i * $step;
                $y = $ch - $pad/2 - ($v / $maxSales) * $dh;
                $pts[] = "$x,$y";
            }
            $path  = 'M ' . implode(' L ', $pts);
            $area  = 'M ' . $pts[0] . ' L ' . implode(' L ', array_slice($pts,1))
                   . ' L ' . ($pad + ($n-1)*$step) . ',' . ($ch-$pad/2)
                   . ' L ' . $pad . ',' . ($ch-$pad/2) . ' Z';
            ?>
            <div class="pim-grid-scroll">
            <svg width="100%" viewBox="0 0 <?= $cw ?> <?= $ch ?>" xmlns="http://www.w3.org/2000/svg" style="min-width:400px">
                <defs>
                    <linearGradient id="trendGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#c5a880" stop-opacity="0.3"/>
                        <stop offset="100%" stop-color="#c5a880" stop-opacity="0.01"/>
                    </linearGradient>
                </defs>
                <!-- Grid lines -->
                <?php for ($g = 0; $g <= 4; $g++): $gy = ($ch-$pad/2) - $g*($dh/4); ?>
                <line x1="<?= $pad ?>" y1="<?= $gy ?>" x2="<?= $cw-$pad ?>" y2="<?= $gy ?>" class="chart-grid-line"/>
                <text x="<?= $pad-5 ?>" y="<?= $gy+4 ?>" class="chart-label" text-anchor="end"><?= round($maxSales/4*$g) ?></text>
                <?php endfor; ?>
                <!-- Area fill -->
                <path d="<?= $area ?>" fill="url(#trendGrad)"/>
                <!-- Line -->
                <path d="<?= $path ?>" stroke="#c5a880" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                <!-- Dots + labels -->
                <?php foreach ($salesTrend as $i => $v):
                    $x = $pad + $i * $step;
                    $y = $ch - $pad/2 - ($v / $maxSales) * $dh;
                ?>
                <circle cx="<?= $x ?>" cy="<?= $y ?>" r="4" fill="#c5a880" stroke="#07051a" stroke-width="2">
                    <title><?= $months[$i] ?>: <?= $v ?></title>
                </circle>
                <text x="<?= $x ?>" y="<?= $ch-4 ?>" class="chart-label" text-anchor="middle"><?= $months[$i] ?></text>
                <?php endforeach; ?>
            </svg>
            </div>
        </div>
    </div>

    <!-- Category Donut Chart -->
    <div class="col-lg-5">
        <div class="anal-section h-100">
            <div class="anal-section-title"><i class="bi bi-pie-chart text-pim-gold"></i>Kategori Dağılımı</div>
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <svg width="140" height="140" viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0">
                    <circle cx="70" cy="70" r="<?= $radius ?>" fill="none" stroke="rgba(255,255,255,.05)" stroke-width="18"/>
                    <?php foreach ($catDist as $cat):
                        $pct     = $cat[1] / $donutTotal;
                        $dash    = $pct * $circ;
                        $gap     = $circ - $dash;
                        $rot     = -90 + ($offset / $donutTotal) * 360;
                    ?>
                    <circle cx="70" cy="70" r="<?= $radius ?>"
                        fill="none" stroke="<?= $cat[2] ?>" stroke-width="18"
                        stroke-dasharray="<?= round($dash,2) ?> <?= round($gap,2) ?>"
                        stroke-dashoffset="0"
                        transform="rotate(<?= round($rot,1) ?> 70 70)"
                        stroke-linecap="butt">
                        <title><?= $cat[0] ?>: %<?= $cat[1] ?></title>
                    </circle>
                    <?php $offset += $cat[1]; endforeach; ?>
                    <text x="70" y="66" text-anchor="middle" font-size="14" font-weight="700" fill="#e2e8f0">%100</text>
                    <text x="70" y="80" text-anchor="middle" font-size="9" fill="#6b7a99">toplam</text>
                </svg>
                <div class="flex-1 d-flex flex-column gap-2">
                    <?php foreach ($catDist as $cat): ?>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:10px;height:10px;border-radius:3px;background:<?= $cat[2] ?>;flex-shrink:0"></div>
                        <span class="fs-7 flex-1"><?= $cat[0] ?></span>
                        <span class="pim-code fs-7">%<?= $cat[1] ?></span>
                        <div style="width:50px;height:4px;border-radius:2px;background:rgba(255,255,255,.06);overflow:hidden"><div style="width:<?= $cat[1] ?>%;height:100%;background:<?= $cat[2] ?>"></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Top Sellers ─────────────────────────────────────── -->
<div class="anal-section">
    <div class="anal-section-title"><i class="bi bi-trophy text-pim-gold"></i>En Çok Satanlar – Top 10</div>
    <div class="pim-grid-wrap">
        <div class="pim-grid-scroll">
            <table class="pim-table">
                <thead><tr><th>#</th><th>Ürün</th><th>SKU</th><th>Satış</th><th>Gelir</th><th>Stok</th><th>Trend</th></tr></thead>
                <tbody>
                    <?php foreach ($topSellers as $i => $p): $rn = $i+1; ?>
                    <tr>
                        <td><div class="rank-badge <?= $rn<=3?"rank-$rn":'rank-n' ?>"><?= $rn ?></div></td>
                        <td><span class="fw-600"><?= htmlspecialchars($p['name']) ?></span></td>
                        <td><span class="pim-code"><?= $p['sku'] ?></span></td>
                        <td><strong><?= number_format($p['sales']) ?></strong></td>
                        <td class="text-success fw-600"><?= $p['revenue'] ?></td>
                        <td><?= $p['stock'] ?></td>
                        <td><span class="trend-badge trend-up"><i class="bi bi-arrow-up"></i>%<?= $p['trend'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─── Least Sellers ───────────────────────────────────── -->
<div class="anal-section">
    <div class="anal-section-title"><i class="bi bi-exclamation-triangle text-pim-warning"></i>En Az Satanlar – Son 10</div>
    <div class="pim-grid-wrap">
        <div class="pim-grid-scroll">
            <table class="pim-table">
                <thead><tr><th>#</th><th>Ürün</th><th>SKU</th><th>Satış</th><th>Gelir</th><th>Stok</th><th>Trend</th></tr></thead>
                <tbody>
                    <?php foreach ($leastSellers as $i => $p): ?>
                    <tr>
                        <td><div class="rank-badge rank-n"><?= $i+1 ?></div></td>
                        <td><span class="fw-600"><?= htmlspecialchars($p['name']) ?></span></td>
                        <td><span class="pim-code"><?= $p['sku'] ?></span></td>
                        <td><?= $p['sales'] ?></td>
                        <td><?= $p['revenue'] ?></td>
                        <td><?= $p['stock'] ?></td>
                        <td><span class="trend-badge trend-down"><i class="bi bi-arrow-down"></i>%<?= abs($p['trend']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─── Most Profitable + Critical Stock ────────────────── -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="anal-section h-100">
            <div class="anal-section-title"><i class="bi bi-star text-pim-gold"></i>En Karlı Ürünler</div>
            <div class="pim-grid-wrap">
                <div class="pim-grid-scroll">
                    <table class="pim-table">
                        <thead><tr><th>Ürün</th><th>Maliyet</th><th>Fiyat</th><th>Marj</th><th>Kar</th></tr></thead>
                        <tbody>
                            <?php foreach ($mostProfitable as $p): ?>
                            <tr>
                                <td class="fw-600"><?= htmlspecialchars($p['name']) ?></td>
                                <td class="text-muted">₺<?= number_format($p['cost'],2) ?></td>
                                <td>₺<?= number_format($p['price'],2) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:50px;height:5px;border-radius:3px;background:rgba(255,255,255,.06);overflow:hidden">
                                            <div style="width:<?= $p['margin'] ?>%;height:100%;background:var(--pim-success)"></div>
                                        </div>
                                        <span class="text-success fw-600">%<?= $p['margin'] ?></span>
                                    </div>
                                </td>
                                <td class="fw-700 text-success"><?= $p['profit'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="anal-section h-100">
            <div class="anal-section-title"><i class="bi bi-exclamation-octagon text-pim-danger"></i>Kritik Stok Uyarıları</div>
            <?php foreach ($criticalStock as $p): ?>
            <div class="d-flex align-items-center gap-3 py-3 border-bottom border-opacity-10 border-white">
                <div style="width:36px;height:36px;background:var(--pim-danger-bg);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-box-seam text-danger"></i>
                </div>
                <div class="flex-1">
                    <div class="fw-600 fs-7"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><span class="pim-code"><?= $p['sku'] ?></span> – Min: <?= $p['min'] ?></div>
                </div>
                <span class="crit-badge"><i class="bi bi-circle-fill" style="font-size:8px"></i><?= $p['stock'] ?> adet</span>
            </div>
            <?php endforeach; ?>
            <div class="mt-3">
                <a href="<?= url('/admin/products?filter=critical_stock') ?>" class="pim-btn pim-btn-danger pim-btn-sm w-100"><i class="bi bi-exclamation-octagon"></i> Tüm Kritik Stokları Görüntüle</a>
            </div>
        </div>
    </div>
</div>

<!-- ─── Conversion Funnel + Product Comparison ──────────── -->
<div class="row g-4 mb-0">
    <div class="col-lg-5">
        <div class="anal-section h-100">
            <div class="anal-section-title"><i class="bi bi-funnel text-pim-gold"></i>Dönüşüm Hunisi</div>
            <?php
            $funnel = [
                ['Sayfa Görüntülenme', 100, 'var(--pim-cyan)'],
                ['Ürün İnceleme',       62, 'var(--pim-gold)'],
                ['Sepete Ekleme',       38, 'var(--pim-purple)'],
                ['Ödeme Başlattı',      22, 'var(--pim-warning)'],
                ['Sipariş Tamamlandı',  14, 'var(--pim-success)'],
            ];
            ?>
            <?php foreach ($funnel as [$label, $pct, $color]): ?>
            <div class="funnel-step">
                <div class="funnel-label">
                    <span class="text-muted fs-7"><?= $label ?></span>
                    <span class="fw-600 fs-7">%<?= $pct ?></span>
                </div>
                <div class="funnel-bar-bg">
                    <div class="funnel-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>">
                        <?= number_format(rand(5000,50000)*$pct/100,0,',','.') ?> kullanıcı
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="anal-section h-100">
            <div class="anal-section-title"><i class="bi bi-columns-gap text-pim-gold"></i>Ürün Karşılaştırma</div>
            <div class="d-flex gap-3 flex-wrap">
                <?php
                $cmpProducts = [
                    ['Deri Cüzdan', '₺599', rand(120,400), '%'.rand(30,60), rand(50,200)],
                    ['Güneş Gözlüğü', '₺349', rand(80,300), '%'.rand(20,50), rand(20,150)],
                    ['Altın Kolye', '₺1299', rand(40,150), '%'.rand(40,70), rand(10,80)],
                ];
                $metrics = ['Fiyat','Satış','Marj','Stok'];
                ?>
                <?php foreach ($cmpProducts as $i => $cp): ?>
                <div class="compare-col">
                    <div class="text-center mb-3">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(197,168,128,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 8px">
                            <i class="bi bi-box-seam text-pim-gold fs-5"></i>
                        </div>
                        <div class="fw-700 text-white fs-7"><?= $cp[0] ?></div>
                    </div>
                    <?php foreach ($metrics as $mi => $metric): ?>
                    <div class="d-flex justify-content-between py-2 border-bottom border-opacity-10 border-white">
                        <span class="text-muted" style="font-size:11px"><?= $metric ?></span>
                        <span class="fw-600 fs-7"><?= $cp[$mi+1] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

</div><!-- /pim-module -->

<script>
function setRange(btn, range) {
    document.querySelectorAll('.date-range-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    // In a real system, this would trigger an AJAX refresh
    PIM.toast.info('Tarih aralığı değiştirildi: ' + range.toUpperCase());
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
