<?php
use App\Helpers\ComponentHelper;

$title = 'OMS – Sipariş Dashboard | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';

// ── Mock Data ───────────────────────────────────────────────────────────────────────────────
$kpi_today        = rand(84, 210);
$kpi_pending      = rand(12, 38);
$kpi_preparing    = rand(8, 25);
$kpi_packed       = rand(5, 20);
$kpi_shipped      = rand(18, 65);
$kpi_delivered    = rand(40, 120);
$kpi_cancelled    = rand(2, 14);
$kpi_returned     = rand(1, 9);
$kpi_pay_pending  = rand(6, 22);
$kpi_prep_avg     = rand(14, 42);
$kpi_revenue      = rand(38000, 185000);
$kpi_hourly_avg   = round($kpi_revenue / 24);
$kpi_conversion   = rand(28, 67);
$kpi_return_rate  = rand(2, 9);
$kpi_satisfaction = rand(82, 99);
$kpi_repeat       = rand(35, 62);

function sparkPoints(int $min = 2, int $max = 30): array {
    $pts = [];
    for ($i = 0; $i < 7; $i++) $pts[] = rand($min, $max);
    return $pts;
}

$hourly = [];
for ($h = 0; $h < 24; $h++) {
    $peak = ($h >= 10 && $h <= 13) || ($h >= 19 && $h <= 22);
    $hourly[$h] = $peak ? rand(12, 34) : rand(1, 14);
}
$maxHourly = max($hourly);

$statusDist = [
    ['label' => 'Yeni',       'val' => rand(8, 25),  'color' => '#22d3ee'],
    ['label' => 'Hazırlanan', 'val' => rand(5, 20),  'color' => '#3b82f6'],
    ['label' => 'Paketlendi', 'val' => rand(4, 15),  'color' => '#a78bfa'],
    ['label' => 'Kargoda',    'val' => rand(10, 30), 'color' => '#c084fc'],
    ['label' => 'Teslim',     'val' => rand(20, 60), 'color' => '#4ade80'],
    ['label' => 'İptal',      'val' => rand(2, 10),  'color' => '#f87171'],
    ['label' => 'İade',       'val' => rand(1, 8),   'color' => '#fb923c'],
    ['label' => 'Bekliyor',   'val' => rand(3, 12),  'color' => '#fbbf24'],
];
$donutTotal = array_sum(array_column($statusDist, 'val'));

$carriers = [
    ['name' => 'Yurtıçi Kargo', 'val' => rand(30, 80), 'color' => '#c5a880'],
    ['name' => 'MNG Kargo',      'val' => rand(15, 55), 'color' => '#3b82f6'],
    ['name' => 'Aras Kargo',     'val' => rand(10, 40), 'color' => '#22d3ee'],
    ['name' => 'PTT Kargo',      'val' => rand(5, 20),  'color' => '#4ade80'],
    ['name' => 'Sürat Kargo',    'val' => rand(3, 15),  'color' => '#a78bfa'],
];
$maxCarrier = max(array_column($carriers, 'val'));

$payments = [
    ['name' => 'Kredi Kartı',   'val' => rand(40, 70), 'color' => '#c5a880'],
    ['name' => 'Havale/EFT',    'val' => rand(10, 25), 'color' => '#3b82f6'],
    ['name' => 'Kapıda Ödeme', 'val' => rand(5, 20),  'color' => '#22d3ee'],
    ['name' => 'PayTR',         'val' => rand(5, 15),  'color' => '#4ade80'],
    ['name' => 'İyzico',         'val' => rand(2, 10),  'color' => '#fb923c'],
];
$payTotal = array_sum(array_column($payments, 'val'));

$topProducts = [
    ['name' => 'Noir Velvet Parfüm 100ml',  'qty' => rand(12, 45), 'rev' => rand(8000, 25000),  'trend' => '+' . rand(5, 38) . '%'],
    ['name' => 'Rose Élite Serum 30ml',      'qty' => rand(8, 30),  'rev' => rand(5000, 18000),  'trend' => '+' . rand(3, 25) . '%'],
    ['name' => 'Amber Night Krem 50ml',      'qty' => rand(6, 22),  'rev' => rand(4000, 12000),  'trend' => '+' . rand(2, 20) . '%'],
    ['name' => 'Gold Ritual Yüz Maskesi',   'qty' => rand(4, 18),  'rev' => rand(2500, 9000),   'trend' => '-' . rand(1, 8)  . '%'],
    ['name' => 'Obsidian Body Scrub 200g',   'qty' => rand(3, 14),  'rev' => rand(1800, 7000),   'trend' => '+' . rand(1, 15) . '%'],
];

$customerList = ['Ayşe Kaya', 'Mehmet Demir', 'Fatma Şahin', 'Ali Yılmaz', 'Zeynep Çelik',
    'Emre Arslan', 'Selin Kurt', 'Burak Öztürk', 'Deniz Aydın', 'Cansu Polat',
    'Kerem Doğan', 'Merve Güneş', 'Tolga Erdoğan', 'Nisan Bozkurt', 'Cem Yıldız'];
$statusesFeed = [
    ['label' => 'Yeni Sipariş',      'color' => '#22d3ee', 'icon' => 'bi-bag-check-fill'],
    ['label' => 'Ödeme Bekleniyor', 'color' => '#fbbf24', 'icon' => 'bi-credit-card'],
    ['label' => 'Hazırlanıyor',      'color' => '#3b82f6', 'icon' => 'bi-box-seam'],
    ['label' => 'Kargoya Verildi',   'color' => '#a78bfa', 'icon' => 'bi-truck'],
    ['label' => 'Teslim Edildi',     'color' => '#4ade80', 'icon' => 'bi-check-circle-fill'],
    ['label' => 'İptal Edildi',      'color' => '#f87171', 'icon' => 'bi-x-circle-fill'],
    ['label' => 'İade Talebi',       'color' => '#fb923c', 'icon' => 'bi-arrow-return-left'],
];
$liveOrders = [];
for ($i = 0; $i < 15; $i++) {
    $st = $statusesFeed[array_rand($statusesFeed)];
    $liveOrders[] = [
        'num'    => 'SM-2026-' . rand(10000, 99999),
        'cust'   => $customerList[array_rand($customerList)],
        'amount' => rand(250, 6500),
        'status' => $st,
        'ago'    => rand(1, 59) . ' dk önce',
    ];
}

$alertStock   = rand(3, 12);
$alertReturn  = rand(2, 8);
$alertPayment = rand(4, 18);

$newCustomers     = rand(8, 35);
$returnCustomers  = rand(25, 90);
$avgOrderVal      = rand(680, 2400);
$cities5          = ['İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Antalya'];
$topCity          = $cities5[array_rand($cities5)];
?>

<style>
:root {
    --oms-gold:   #c5a880;
    --oms-gold-lt:rgba(197,168,128,.12);
    --oms-card:   rgba(255,255,255,.025);
    --oms-border: rgba(255,255,255,.07);
    --oms-cyan:   #22d3ee;
    --oms-blue:   #3b82f6;
    --oms-purple: #a78bfa;
    --oms-green:  #4ade80;
    --oms-red:    #f87171;
    --oms-orange: #fb923c;
    --oms-yellow: #fbbf24;
}
@keyframes livePulse {
    0%,100%{transform:scale(1);opacity:1}
    50%{transform:scale(1.7);opacity:.4}
}
.live-dot {
    width:10px;height:10px;border-radius:50%;
    background:var(--oms-green);display:inline-block;
    animation:livePulse 1.4s infinite;
    box-shadow:0 0 8px var(--oms-green);
}
.oms-kpi-card {
    background:var(--oms-card);
    border:1px solid var(--oms-border);
    border-radius:16px;padding:18px 20px 14px;
    position:relative;overflow:hidden;
    transition:border-color .3s,box-shadow .3s,transform .25s;
    cursor:default;
}
.oms-kpi-card:hover {
    border-color:var(--oms-gold);
    box-shadow:0 0 24px rgba(197,168,128,.18),0 8px 32px rgba(0,0,0,.4);
    transform:translateY(-3px);
}
.oms-kpi-card .kpi-icon {
    width:38px;height:38px;border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    font-size:18px;flex-shrink:0;
}
.oms-kpi-card .kpi-val  {font-size:24px;font-weight:700;line-height:1;letter-spacing:-1px;}
.oms-kpi-card .kpi-label{font-size:10px;color:rgba(255,255,255,.42);text-transform:uppercase;letter-spacing:.6px;margin-top:2px;}
.oms-kpi-card .kpi-trend{font-size:10px;font-weight:600;padding:2px 6px;border-radius:20px;}
.oms-kpi-card .kpi-trend.up  {background:rgba(74,222,128,.12);color:var(--oms-green);}
.oms-kpi-card .kpi-trend.down{background:rgba(248,113,113,.12);color:var(--oms-red);}
.oms-kpi-card .sparkline-svg {opacity:.75;display:block;}
.oms-kpi-card .kpi-glow {
    position:absolute;top:-30px;right:-30px;
    width:80px;height:80px;border-radius:50%;
    opacity:.07;filter:blur(22px);
}
.oms-chart-card {
    background:var(--oms-card);
    border:1px solid var(--oms-border);
    border-radius:20px;padding:24px;height:100%;
}
.oms-section-title {
    font-size:12px;font-weight:600;
    text-transform:uppercase;letter-spacing:.8px;
    color:rgba(255,255,255,.5);margin-bottom:16px;
}
.live-feed-wrap {
    max-height:430px;overflow-y:auto;
    scrollbar-width:thin;scrollbar-color:rgba(197,168,128,.3) transparent;
}
.live-feed-wrap::-webkit-scrollbar{width:4px}
.live-feed-wrap::-webkit-scrollbar-thumb{background:rgba(197,168,128,.3);border-radius:4px}
@keyframes slideInDown {
    from{opacity:0;transform:translateY(-20px)}
    to  {opacity:1;transform:translateY(0)}
}
.feed-item {
    display:flex;align-items:center;gap:12px;
    padding:11px 14px;border-radius:12px;
    background:rgba(255,255,255,.02);
    border:1px solid rgba(255,255,255,.05);
    margin-bottom:8px;
    animation:slideInDown .45s ease forwards;
    transition:background .2s;
}
.feed-item:hover{background:rgba(255,255,255,.05)}
.feed-icon {
    width:36px;height:36px;border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    font-size:15px;flex-shrink:0;
}
.feed-num  {font-size:12px;font-weight:700;color:var(--oms-gold)}
.feed-cust {font-size:12px;color:rgba(255,255,255,.75)}
.feed-amt  {font-size:13px;font-weight:700;margin-left:auto;white-space:nowrap}
.feed-ago  {font-size:10px;color:rgba(255,255,255,.32);white-space:nowrap}
.feed-badge{
    font-size:10px;font-weight:600;
    padding:2px 7px;border-radius:20px;white-space:nowrap;
}
.donut-legend-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;}
.oms-table{background:transparent;color:#fff}
.oms-table th{font-size:11px;color:rgba(255,255,255,.38);text-transform:uppercase;letter-spacing:.5px;border:none;padding:10px 12px;font-weight:600;}
.oms-table td{font-size:13px;border-color:rgba(255,255,255,.05);padding:12px;vertical-align:middle}
.rank-badge{width:26px;height:26px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}
.alert-card {
    background:rgba(255,255,255,.02);
    border:1px solid rgba(255,255,255,.06);
    border-radius:14px;padding:16px 18px;
    display:flex;align-items:center;gap:14px;
    transition:border-color .3s,box-shadow .3s;
    margin-bottom:12px;
}
.alert-card:hover{border-color:rgba(248,113,113,.4);box-shadow:0 0 16px rgba(248,113,113,.1)}
.alert-card.warn:hover{border-color:rgba(251,191,36,.4);box-shadow:0 0 16px rgba(251,191,36,.1)}
.alert-card.info-c:hover{border-color:rgba(34,211,238,.4);box-shadow:0 0 16px rgba(34,211,238,.1)}
.alert-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.daterange-btn {
    font-size:12px;padding:5px 12px;border-radius:8px;
    border:1px solid rgba(255,255,255,.1);
    background:rgba(255,255,255,.03);color:rgba(255,255,255,.55);
    cursor:pointer;transition:all .2s;
}
.daterange-btn.active,.daterange-btn:hover {
    border-color:var(--oms-gold);
    background:rgba(197,168,128,.1);
    color:var(--oms-gold);
}
.cust-mini {
    background:var(--oms-card);border:1px solid var(--oms-border);
    border-radius:14px;padding:16px 18px;
    transition:border-color .3s;
}
.cust-mini:hover{border-color:rgba(197,168,128,.4)}
.hourly-scroll{overflow-x:auto;padding-bottom:8px;}
.hourly-scroll::-webkit-scrollbar{height:4px}
.hourly-scroll::-webkit-scrollbar-thumb{background:rgba(197,168,128,.3);border-radius:4px}
.pct-bar{height:6px;border-radius:4px;margin-top:4px;transition:width .8s ease;}
</style>

<?php
function renderSparkline(array $pts, string $color): string {
    $w = 80; $h = 28; $n = count($pts); $max = max($pts) ?: 1;
    $step = $w / ($n - 1);
    $coords = [];
    foreach ($pts as $i => $v) {
        $x = round($i * $step);
        $y = round($h - ($v / $max) * ($h - 4) + 2);
        $coords[] = "$x,$y";
    }
    $poly     = implode(' ', $coords);
    $fillPoly = '0,' . ($h + 2) . ' ' . $poly . ' ' . $w . ',' . ($h + 2);
    $uid = 'sg' . substr(md5($color . implode('', $pts)), 0, 8);
    return <<<SVG
<svg class="sparkline-svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}" aria-hidden="true">
  <defs>
    <linearGradient id="{$uid}" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="{$color}" stop-opacity="0.35"/>
      <stop offset="100%" stop-color="{$color}" stop-opacity="0"/>
    </linearGradient>
  </defs>
  <polygon points="{$fillPoly}" fill="url(#{$uid})"/>
  <polyline points="{$poly}" fill="none" stroke="{$color}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG;
}

function donutPath(float $start, float $end, float $r = 70, float $cx = 90, float $cy = 90, float $thickness = 22): string {
    $r1 = $r; $r2 = $r - $thickness;
    $a1 = ($start - 90) * M_PI / 180;
    $a2 = ($end   - 90) * M_PI / 180;
    $lg = ($end - $start > 180) ? 1 : 0;
    $x1 = round($cx + $r1 * cos($a1), 2); $y1 = round($cy + $r1 * sin($a1), 2);
    $x2 = round($cx + $r1 * cos($a2), 2); $y2 = round($cy + $r1 * sin($a2), 2);
    $x3 = round($cx + $r2 * cos($a2), 2); $y3 = round($cy + $r2 * sin($a2), 2);
    $x4 = round($cx + $r2 * cos($a1), 2); $y4 = round($cy + $r2 * sin($a1), 2);
    return "M $x1 $y1 A $r1 $r1 0 $lg 1 $x2 $y2 L $x3 $y3 A $r2 $r2 0 $lg 0 $x4 $y4 Z";
}
?>

<!-- ── Breadcrumb + Header ───────────────────────────────────────────────────────────────────── -->
<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Siparişler'     => url('/admin/orders'),
        'Dashboard'      => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3">
        <div class="d-flex align-items-center gap-3">
            <h2 class="m-0 fw-bold" style="font-size:24px;letter-spacing:-.5px;">Executive Sipariş Dashboard</h2>
            <span class="live-dot" title="Canlı veri" aria-label="Canlı"></span>
            <span style="font-size:11px;color:rgba(255,255,255,.35);">Canlı</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button class="daterange-btn active" onclick="setRange('bugun',this)">Bugün</button>
            <button class="daterange-btn"         onclick="setRange('hafta',this)">Hafta</button>
            <button class="daterange-btn"         onclick="setRange('ay',this)">Ay</button>
            <button class="daterange-btn"         onclick="setRange('yil',this)">Yıl</button>
            <div style="width:1px;height:24px;background:rgba(255,255,255,.1);"></div>
            <a href="<?= url('/admin/orders') ?>" class="btn btn-sm" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#fff;font-size:12px;border-radius:9px;">
                <i class="bi bi-list-ul me-1"></i>Sipariş Listesi
            </a>
            <a href="<?= url('/admin/orders/create') ?>" class="btn btn-sm" style="background:linear-gradient(135deg,#c5a880,#e5d1b8);color:#0f0c20;font-weight:700;font-size:12px;border-radius:9px;border:none;">
                <i class="bi bi-plus-circle-fill me-1"></i>Yeni Sipariş
            </a>
        </div>
    </div>
</div>

<?php
$kpiRow1 = [
    ['label'=>'Bugünkü Sipariş','val'=>$kpi_today,    'icon'=>'bi-bag-check',        'color'=>'#c5a880','trend'=>'+'.rand(4,22).'%','up'=>true, 'pts'=>sparkPoints(5,30),'sfx'=>''],
    ['label'=>'Bekleyen',       'val'=>$kpi_pending,  'icon'=>'bi-hourglass-split',  'color'=>'#fbbf24','trend'=>'+'.rand(1,15).'%','up'=>true, 'pts'=>sparkPoints(2,20),'sfx'=>''],
    ['label'=>'Hazırlanan',     'val'=>$kpi_preparing,'icon'=>'bi-box-seam',         'color'=>'#3b82f6','trend'=>'+'.rand(2,18).'%','up'=>true, 'pts'=>sparkPoints(3,25),'sfx'=>''],
    ['label'=>'Paketlenen',     'val'=>$kpi_packed,   'icon'=>'bi-archive',          'color'=>'#a78bfa','trend'=>'+'.rand(1,12).'%','up'=>true, 'pts'=>sparkPoints(2,22),'sfx'=>''],
    ['label'=>'Kargoda',        'val'=>$kpi_shipped,  'icon'=>'bi-truck',            'color'=>'#22d3ee','trend'=>'+'.rand(3,20).'%','up'=>true, 'pts'=>sparkPoints(4,28),'sfx'=>''],
    ['label'=>'Teslim Edilen',  'val'=>$kpi_delivered,'icon'=>'bi-check-circle',     'color'=>'#4ade80','trend'=>'+'.rand(5,28).'%','up'=>true, 'pts'=>sparkPoints(8,30),'sfx'=>''],
    ['label'=>'İptal',           'val'=>$kpi_cancelled,'icon'=>'bi-x-circle',        'color'=>'#f87171','trend'=>'-'.rand(1,10).'%','up'=>false,'pts'=>sparkPoints(1,15),'sfx'=>''],
    ['label'=>'İade',            'val'=>$kpi_returned, 'icon'=>'bi-arrow-return-left','color'=>'#fb923c','trend'=>'-'.rand(1,8).'%', 'up'=>false,'pts'=>sparkPoints(1,12),'sfx'=>''],
];
$kpiRow2 = [
    ['label'=>'Bekleyen Ödeme', 'val'=>$kpi_pay_pending,            'icon'=>'bi-credit-card',       'color'=>'#fbbf24','trend'=>'-'.rand(1,9).'%', 'up'=>false,'pts'=>sparkPoints(1,18),'pfx'=>'', 'sfx'=>''],
    ['label'=>'Ort. Hazırlama', 'val'=>$kpi_prep_avg,               'icon'=>'bi-stopwatch',         'color'=>'#22d3ee','trend'=>'-'.rand(2,12).'%','up'=>false,'pts'=>sparkPoints(5,25),'pfx'=>'', 'sfx'=>' dk'],
    ['label'=>'Toplam Ciro',    'val'=>number_format($kpi_revenue),  'icon'=>'bi-graph-up-arrow',    'color'=>'#c5a880','trend'=>'+'.rand(8,35).'%','up'=>true, 'pts'=>sparkPoints(10,30),'pfx'=>'₺','sfx'=>''],
    ['label'=>'Saatlik Ort.',   'val'=>number_format($kpi_hourly_avg),'icon'=>'bi-clock-history',    'color'=>'#4ade80','trend'=>'+'.rand(3,18).'%','up'=>true, 'pts'=>sparkPoints(6,28),'pfx'=>'₺','sfx'=>''],
    ['label'=>'Dönüşüm %',      'val'=>$kpi_conversion,             'icon'=>'bi-percent',           'color'=>'#a78bfa','trend'=>'+'.rand(1,10).'%','up'=>true, 'pts'=>sparkPoints(15,30),'pfx'=>'', 'sfx'=>'%'],
    ['label'=>'İade Oranı',      'val'=>$kpi_return_rate,            'icon'=>'bi-arrow-repeat',      'color'=>'#fb923c','trend'=>'-'.rand(1,5).'%', 'up'=>false,'pts'=>sparkPoints(1,10),'pfx'=>'', 'sfx'=>'%'],
    ['label'=>'Memnuniyet',     'val'=>$kpi_satisfaction,           'icon'=>'bi-emoji-smile',        'color'=>'#4ade80','trend'=>'+'.rand(1,8).'%', 'up'=>true, 'pts'=>sparkPoints(18,30),'pfx'=>'', 'sfx'=>'%'],
    ['label'=>'Tekrar Alım',    'val'=>$kpi_repeat,                 'icon'=>'bi-arrow-clockwise',   'color'=>'#22d3ee','trend'=>'+'.rand(2,12).'%','up'=>true, 'pts'=>sparkPoints(10,28),'pfx'=>'', 'sfx'=>'%'],
];
?>

<!-- KPI Row 1 -->
<div class="d-flex gap-2 mb-2 flex-nowrap overflow-hidden" role="list" aria-label="Sipariş KPI - Satır 1">
<?php foreach ($kpiRow1 as $k): ?>
    <div class="oms-kpi-card flex-fill" style="min-width:150px;" role="listitem">
        <div class="kpi-glow" style="background:<?= $k['color'] ?>;"></div>
        <div class="d-flex align-items-start justify-content-between mb-2">
            <div class="kpi-icon" style="background:<?= $k['color'] ?>1a;">
                <i class="bi <?= $k['icon'] ?>" style="color:<?= $k['color'] ?>"></i>
            </div>
            <span class="kpi-trend <?= $k['up'] ? 'up' : 'down' ?>">
                <i class="bi <?= $k['up'] ? 'bi-arrow-up-short' : 'bi-arrow-down-short' ?>"></i><?= $k['trend'] ?>
            </span>
        </div>
        <div class="kpi-val" style="color:<?= $k['color'] ?>"><?= $k['val'] ?><?= $k['sfx'] ?></div>
        <div class="kpi-label"><?= $k['label'] ?></div>
        <div class="mt-2"><?= renderSparkline($k['pts'], $k['color']) ?></div>
    </div>
<?php endforeach; ?>
</div>

<!-- KPI Row 2 -->
<div class="d-flex gap-2 mb-4 flex-nowrap overflow-hidden" role="list" aria-label="Sipariş KPI - Satır 2">
<?php foreach ($kpiRow2 as $k): ?>
    <div class="oms-kpi-card flex-fill" style="min-width:150px;" role="listitem">
        <div class="kpi-glow" style="background:<?= $k['color'] ?>;"></div>
        <div class="d-flex align-items-start justify-content-between mb-2">
            <div class="kpi-icon" style="background:<?= $k['color'] ?>1a;">
                <i class="bi <?= $k['icon'] ?>" style="color:<?= $k['color'] ?>"></i>
            </div>
            <span class="kpi-trend <?= $k['up'] ? 'up' : 'down' ?>">
                <i class="bi <?= $k['up'] ? 'bi-arrow-up-short' : 'bi-arrow-down-short' ?>"></i><?= $k['trend'] ?>
            </span>
        </div>
        <div class="kpi-val" style="color:<?= $k['color'] ?>"><?= ($k['pfx'] ?? '') ?><?= $k['val'] ?><?= $k['sfx'] ?></div>
        <div class="kpi-label"><?= $k['label'] ?></div>
        <div class="mt-2"><?= renderSparkline($k['pts'], $k['color']) ?></div>
    </div>
<?php endforeach; ?>
</div>

<!-- ════════════════════════════════════════════════════════════════════════════ -->
<!-- SAATLiK GRAFİK                                                           -->
<!-- ════════════════════════════════════════════════════════════════════════════ -->
<div class="oms-chart-card mb-4" aria-label="Saatlik Sipariş Grafiği">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="oms-section-title mb-0">Saatlik Sipariş Grafiği</div>
            <div style="font-size:13px;color:rgba(255,255,255,.45);">Bugün — 24 saat bazlı sipariş dağılımı</div>
        </div>
        <div class="d-flex gap-3">
            <span style="font-size:11px;color:rgba(255,255,255,.4)">
                <span style="display:inline-block;width:10px;height:10px;background:#c5a880;border-radius:3px;margin-right:4px;"></span>Yoğun Saat
            </span>
            <span style="font-size:11px;color:rgba(255,255,255,.4)">
                <span style="display:inline-block;width:10px;height:10px;background:#3b82f6;border-radius:3px;margin-right:4px;"></span>Normal
            </span>
        </div>
    </div>
    <div class="hourly-scroll">
        <svg width="1060" height="200" viewBox="0 0 1060 200" role="img" aria-label="Saatlik sipariş bar grafik">
            <?php
            for ($g = 0; $g <= 4; $g++):
                $gy   = 10 + ((4 - $g) / 4) * 160;
                $gval = round(($g / 4) * $maxHourly);
            ?>
            <line x1="42" y1="<?= round($gy) ?>" x2="1055" y2="<?= round($gy) ?>"
                  stroke="rgba(255,255,255,.06)" stroke-width="1" stroke-dasharray="4,4"/>
            <text x="38" y="<?= round($gy + 4) ?>" fill="rgba(255,255,255,.25)" font-size="9"
                  text-anchor="end" font-family="Outfit,sans-serif"><?= $gval ?></text>
            <?php endfor; ?>

            <?php
            $barW = 30; $gap = 14; $startX = 45;
            foreach ($hourly as $h => $val):
                $x      = $startX + $h * ($barW + $gap);
                $barH   = $maxHourly > 0 ? round(($val / $maxHourly) * 155) : 0;
                $y      = 170 - $barH;
                $isPeak = ($h >= 10 && $h <= 13) || ($h >= 19 && $h <= 22);
                $color  = $isPeak ? '#c5a880' : '#3b82f6';
                $uid    = 'hbf' . $h;
            ?>
            <defs>
                <linearGradient id="<?= $uid ?>" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%"   stop-color="<?= $color ?>" stop-opacity="0.9"/>
                    <stop offset="100%" stop-color="<?= $color ?>" stop-opacity="0.25"/>
                </linearGradient>
            </defs>
            <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $barW ?>" height="<?= $barH ?>"
                  rx="5" fill="url(#<?= $uid ?>)" class="hourly-bar">
                <title><?= sprintf('%02d:00 – %d sipariş', $h, $val) ?></title>
            </rect>
            <text x="<?= $x + $barW/2 ?>" y="186" fill="rgba(255,255,255,.28)" font-size="9"
                  text-anchor="middle" font-family="Outfit,sans-serif"><?= sprintf('%02d', $h) ?></text>
            <?php if ($barH > 18): ?>
            <text x="<?= $x + $barW/2 ?>" y="<?= $y + 13 ?>" font-size="9" font-weight="700"
                  fill="<?= $isPeak ? '#0f0c20' : 'rgba(255,255,255,.8)' ?>"
                  text-anchor="middle" font-family="Outfit,sans-serif"><?= $val ?></text>
            <?php endif; ?>
            <?php endforeach; ?>
        </svg>
    </div>
</div>

<!-- ══ MIDDLE ROW ═════════════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Donut - Durum Dağılımı -->
    <div class="col-12 col-lg-4">
        <div class="oms-chart-card">
            <div class="oms-section-title">Durum Dağılımı</div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div style="flex-shrink:0">
                <?php
                echo '<svg width="180" height="180" viewBox="0 0 180 180" role="img" aria-label="Durum dağılımı donut">';
                echo '<circle cx="90" cy="90" r="70" fill="none" stroke="rgba(255,255,255,.04)" stroke-width="22"/>';
                $cumDeg = 0;
                foreach ($statusDist as $sd) {
                    $deg = $donutTotal > 0 ? ($sd['val'] / $donutTotal) * 360 : 0;
                    if ($deg < 1) { $cumDeg += $deg; continue; }
                    $path = donutPath($cumDeg, $cumDeg + $deg - 1.5);
                    echo '<path d="' . $path . '" fill="' . $sd['color'] . '" opacity="0.88"><title>' . htmlspecialchars($sd['label']) . ': ' . $sd['val'] . '</title></path>';
                    $cumDeg += $deg;
                }
                echo '<text x="90" y="86" fill="#fff" font-size="22" font-weight="700" text-anchor="middle" font-family="Outfit,sans-serif">' . $donutTotal . '</text>';
                echo '<text x="90" y="104" fill="rgba(255,255,255,.38)" font-size="10" text-anchor="middle" font-family="Outfit,sans-serif">Toplam</text>';
                echo '</svg>';
                ?>
                </div>
                <div class="flex-fill">
                <?php foreach ($statusDist as $sd):
                    $pct = $donutTotal > 0 ? round($sd['val'] / $donutTotal * 100) : 0;
                ?>
                    <div class="d-flex align-items-center gap-2 mb-1" style="font-size:12px">
                        <span class="donut-legend-dot" style="background:<?= $sd['color'] ?>"></span>
                        <span style="color:rgba(255,255,255,.62);flex:1"><?= htmlspecialchars($sd['label']) ?></span>
                        <span style="font-weight:700;color:<?= $sd['color'] ?>"><?= $sd['val'] ?></span>
                        <span style="color:rgba(255,255,255,.28);width:32px;text-align:right"><?= $pct ?>%</span>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Carrier Performance -->
    <div class="col-12 col-lg-4">
        <div class="oms-chart-card">
            <div class="oms-section-title">Kargo Performansı</div>
            <?php foreach ($carriers as $c):
                $pct = $maxCarrier > 0 ? round($c['val'] / $maxCarrier * 100) : 0;
            ?>
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1" style="font-size:12px">
                    <span style="color:rgba(255,255,255,.68)"><?= htmlspecialchars($c['name']) ?></span>
                    <span style="font-weight:700;color:<?= $c['color'] ?>"><?= $c['val'] ?> sipariş</span>
                </div>
                <div style="background:rgba(255,255,255,.06);height:8px;border-radius:6px;overflow:hidden">
                    <div class="pct-bar" style="width:<?= $pct ?>%;background:<?= $c['color'] ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,.07)">
                <div class="d-flex justify-content-between" style="font-size:12px;color:rgba(255,255,255,.38)">
                    <span>Ort. Teslimat Süresi</span>
                    <span style="color:var(--oms-green);font-weight:700"><?= rand(1,3) ?>.<?= rand(1,9) ?> gün</span>
                </div>
                <div class="d-flex justify-content-between mt-1" style="font-size:12px;color:rgba(255,255,255,.38)">
                    <span>Zamanında Teslimat</span>
                    <span style="color:var(--oms-cyan);font-weight:700"><?= rand(88,99) ?>%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="col-12 col-lg-4">
        <div class="oms-chart-card">
            <div class="oms-section-title">Ödeme Yöntemleri</div>
            <?php
            echo '<svg width="100%" height="150" viewBox="0 0 300 150" role="img" aria-label="Ödeme yöntemleri">';
            $cumPie = -90; $rp = 58; $cxP = 75; $cyP = 75;
            foreach ($payments as $pm) {
                $deg = $payTotal > 0 ? ($pm['val'] / $payTotal) * 360 : 0;
                if ($deg < 1) { $cumPie += $deg; continue; }
                $a1  = $cumPie * M_PI / 180;
                $a2  = ($cumPie + $deg) * M_PI / 180;
                $lg  = $deg > 180 ? 1 : 0;
                $x1 = round($cxP + $rp * cos($a1), 2); $y1 = round($cyP + $rp * sin($a1), 2);
                $x2 = round($cxP + $rp * cos($a2), 2); $y2 = round($cyP + $rp * sin($a2), 2);
                echo '<path d="M ' . $cxP . ' ' . $cyP . ' L ' . $x1 . ' ' . $y1 . ' A ' . $rp . ' ' . $rp . ' 0 ' . $lg . ' 1 ' . $x2 . ' ' . $y2 . ' Z" fill="' . $pm['color'] . '" opacity="0.85"><title>' . htmlspecialchars($pm['name']) . ': ' . ($payTotal > 0 ? round($pm['val'] / $payTotal * 100) : 0) . '%</title></path>';
                $cumPie += $deg;
            }
            echo '<circle cx="' . $cxP . '" cy="' . $cyP . '" r="28" fill="#07051a"/>';
            echo '<text x="' . $cxP . '" y="' . ($cyP - 4) . '" fill="#fff" font-size="13" font-weight="700" text-anchor="middle" font-family="Outfit,sans-serif">' . $payTotal . '</text>';
            echo '<text x="' . $cxP . '" y="' . ($cyP + 12) . '" fill="rgba(255,255,255,.32)" font-size="8" text-anchor="middle" font-family="Outfit,sans-serif">ödeme</text>';
            $ly = 22;
            foreach ($payments as $pm) {
                $pct = $payTotal > 0 ? round($pm['val'] / $payTotal * 100) : 0;
                echo '<rect x="152" y="' . ($ly - 8) . '" width="10" height="10" rx="3" fill="' . $pm['color'] . '"/>';
                echo '<text x="166" y="' . ($ly + 1) . '" fill="rgba(255,255,255,.62)" font-size="10" font-family="Outfit,sans-serif">' . htmlspecialchars($pm['name']) . '</text>';
                echo '<text x="296" y="' . ($ly + 1) . '" fill="' . $pm['color'] . '" font-size="10" font-weight="700" text-anchor="end" font-family="Outfit,sans-serif">' . $pct . '%</text>';
                $ly += 24;
            }
            echo '</svg>';
            ?>
        </div>
    </div>
</div>

<!-- ══ LIVE FEED + ALERTS ═════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <div class="col-12 col-lg-8">
        <div class="oms-chart-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="oms-section-title mb-0">Sipariş Akışı</div>
                    <span class="live-dot" style="width:8px;height:8px"></span>
                    <span style="font-size:10px;background:rgba(74,222,128,.12);color:var(--oms-green);padding:2px 8px;border-radius:20px;font-weight:700;letter-spacing:.5px;">CANLI</span>
                </div>
                <span id="live-counter" style="font-size:12px;color:rgba(255,255,255,.32)">Son 15 etkinlik</span>
            </div>
            <div class="live-feed-wrap" id="live-feed" role="log" aria-live="polite" aria-label="Canlı sipariş akışı">
                <?php foreach ($liveOrders as $lo):
                    $bgC  = $lo['status']['color'] . '1a';
                    $txtC = $lo['status']['color'];
                ?>
                <div class="feed-item" role="article" aria-label="Sipariş <?= htmlspecialchars($lo['num']) ?>">
                    <div class="feed-icon" style="background:<?= $bgC ?>">
                        <i class="bi <?= $lo['status']['icon'] ?>" style="color:<?= $txtC ?>"></i>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div class="feed-num"><?= htmlspecialchars($lo['num']) ?></div>
                        <div class="feed-cust"><?= htmlspecialchars($lo['cust']) ?></div>
                    </div>
                    <span class="feed-badge" style="background:<?= $bgC ?>;color:<?= $txtC ?>;border:1px solid <?= $lo['status']['color'] ?>33">
                        <?= htmlspecialchars($lo['status']['label']) ?>
                    </span>
                    <div class="text-end">
                        <div class="feed-amt" style="color:var(--oms-gold)">₺<?= number_format($lo['amount']) ?></div>
                        <div class="feed-ago"><?= htmlspecialchars($lo['ago']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Critical Alerts -->
    <div class="col-12 col-lg-4">
        <div class="oms-chart-card">
            <div class="oms-section-title">Kritik Uyarılar</div>

            <div class="alert-card" role="alert">
                <div class="alert-icon" style="background:rgba(248,113,113,.12)">
                    <i class="bi bi-exclamation-triangle-fill" style="color:#f87171"></i>
                </div>
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:600">Stok Kritik Ürünler</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.38);margin-top:2px">Stok altına düşen ürünler</div>
                </div>
                <div class="text-end">
                    <div style="font-size:22px;font-weight:700;color:#f87171"><?= $alertStock ?></div>
                    <a href="<?= url('/admin/products?filter=low_stock') ?>" class="btn btn-sm" style="font-size:10px;padding:2px 8px;background:rgba(248,113,113,.12);color:#f87171;border:1px solid rgba(248,113,113,.3);border-radius:6px;text-decoration:none">İncele</a>
                </div>
            </div>

            <div class="alert-card warn" role="alert">
                <div class="alert-icon" style="background:rgba(251,191,36,.12)">
                    <i class="bi bi-arrow-return-left" style="color:#fbbf24"></i>
                </div>
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:600">Bekleyen İadeler</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.38);margin-top:2px">Onay bekleyen iade talepleri</div>
                </div>
                <div class="text-end">
                    <div style="font-size:22px;font-weight:700;color:#fbbf24"><?= $alertReturn ?></div>
                    <a href="<?= url('/admin/orders?status=return_requested') ?>" class="btn btn-sm" style="font-size:10px;padding:2px 8px;background:rgba(251,191,36,.12);color:#fbbf24;border:1px solid rgba(251,191,36,.3);border-radius:6px;text-decoration:none">İncele</a>
                </div>
            </div>

            <div class="alert-card info-c" role="alert">
                <div class="alert-icon" style="background:rgba(34,211,238,.12)">
                    <i class="bi bi-credit-card" style="color:#22d3ee"></i>
                </div>
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:600">Ödeme Bekleyen</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.38);margin-top:2px">Havale onayı bekleyenler</div>
                </div>
                <div class="text-end">
                    <div style="font-size:22px;font-weight:700;color:#22d3ee"><?= $alertPayment ?></div>
                    <a href="<?= url('/admin/orders?status=awaiting_payment') ?>" class="btn btn-sm" style="font-size:10px;padding:2px 8px;background:rgba(34,211,238,.12);color:#22d3ee;border:1px solid rgba(34,211,238,.3);border-radius:6px;text-decoration:none">İncele</a>
                </div>
            </div>

            <div style="border-top:1px solid rgba(255,255,255,.06);margin-top:4px;padding-top:14px">
                <div class="d-flex justify-content-between mb-2" style="font-size:12px">
                    <span style="color:rgba(255,255,255,.38)">Son güncelleme</span>
                    <span style="color:var(--oms-gold);font-weight:600" id="last-update-time">--:--</span>
                </div>
                <div class="d-flex justify-content-between" style="font-size:12px">
                    <span style="color:rgba(255,255,255,.38)">Aktif yönetici</span>
                    <span style="color:var(--oms-green);font-weight:600"><?= rand(2,8) ?> yönetici</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ BOTTOM ROW ═══════════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <div class="col-12 col-lg-4">
        <div class="oms-chart-card">
            <div class="oms-section-title">Müşteri Özeti – Bugün</div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="cust-mini text-center">
                        <div style="font-size:28px;font-weight:700;color:var(--oms-cyan)"><?= $newCustomers ?></div>
                        <div style="font-size:11px;color:rgba(255,255,255,.38);margin-top:2px">Yeni Müşteri</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="cust-mini text-center">
                        <div style="font-size:28px;font-weight:700;color:var(--oms-purple)"><?= $returnCustomers ?></div>
                        <div style="font-size:11px;color:rgba(255,255,255,.38);margin-top:2px">Tekrar Alım</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="cust-mini text-center">
                        <div style="font-size:22px;font-weight:700;color:var(--oms-gold)">₺<?= number_format($avgOrderVal) ?></div>
                        <div style="font-size:11px;color:rgba(255,255,255,.38);margin-top:2px">Ort. Sepet</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="cust-mini text-center">
                        <div style="font-size:18px;font-weight:700;color:var(--oms-green)"><?= $topCity ?></div>
                        <div style="font-size:11px;color:rgba(255,255,255,.38);margin-top:2px">En Çok Sipariş</div>
                    </div>
                </div>
            </div>
            <div class="oms-section-title" style="margin-bottom:10px">Şehir Dağılımı</div>
            <?php
            $cityRows = ['İstanbul',rand(35,55),'#c5a880'];
            $cityData = [
                ['İstanbul', rand(35,55), '#c5a880'],
                ['Ankara',    rand(12,22), '#22d3ee'],
                ['İzmir',     rand(8,18),  '#4ade80'],
                ['Bursa',     rand(4,10),  '#a78bfa'],
                ['Antalya',   rand(2,8),   '#fb923c'],
            ];
            foreach ($cityData as [$cityName, $cpct, $ccol]):
            ?>
            <div class="d-flex align-items-center gap-2 mb-2" style="font-size:11px">
                <span style="color:rgba(255,255,255,.58);width:65px"><?= $cityName ?></span>
                <div style="flex:1;background:rgba(255,255,255,.05);height:5px;border-radius:4px;overflow:hidden">
                    <div class="pct-bar" style="width:<?= $cpct ?>%;background:<?= $ccol ?>"></div>
                </div>
                <span style="color:<?= $ccol ?>;width:30px;text-align:right;font-weight:700"><?= $cpct ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top Products -->
    <div class="col-12 col-lg-8">
        <div class="oms-chart-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="oms-section-title mb-0">Bugün En Çok Satan Ürünler</div>
                <a href="<?= url('/admin/products') ?>" style="font-size:12px;color:var(--oms-gold);text-decoration:none">
                    Tüm Ürünler <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table oms-table mb-0" aria-label="Günün en çok satan ürünleri">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Ürün</th>
                            <th class="text-center">Adet</th>
                            <th class="text-end">Ciro</th>
                            <th class="text-center">Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rankColors  = ['#c5a880','#94a3b8','#b45309','rgba(255,255,255,.3)','rgba(255,255,255,.2)'];
                        $rankBgColors= ['rgba(197,168,128,.15)','rgba(148,163,184,.1)','rgba(180,83,9,.1)','rgba(255,255,255,.04)','rgba(255,255,255,.03)'];
                        foreach ($topProducts as $rank => $prod):
                            $isPos = str_starts_with($prod['trend'], '+');
                        ?>
                        <tr>
                            <td>
                                <div class="rank-badge" style="background:<?= $rankBgColors[$rank] ?>;color:<?= $rankColors[$rank] ?>">
                                    <?= $rank + 1 ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($prod['name']) ?></div>
                                <div style="font-size:11px;color:rgba(255,255,255,.32)">SaintMonarc Koleksiyonu</div>
                            </td>
                            <td class="text-center">
                                <span style="font-weight:700;color:var(--oms-cyan)"><?= $prod['qty'] ?></span>
                            </td>
                            <td class="text-end">
                                <span style="font-weight:700;color:var(--oms-gold)">₺<?= number_format($prod['rev']) ?></span>
                            </td>
                            <td class="text-center">
                                <span style="font-size:12px;font-weight:700;color:<?= $isPos ? 'var(--oms-green)' : 'var(--oms-red)' ?>">
                                    <i class="bi <?= $isPos ? 'bi-arrow-up-short' : 'bi-arrow-down-short' ?>"></i><?= htmlspecialchars($prod['trend']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="border-top:1px solid rgba(255,255,255,.08)">
                            <td colspan="2" style="font-size:12px;color:rgba(255,255,255,.3);padding-top:12px">5 ürün gösteriliyor</td>
                            <td class="text-center" style="font-size:12px;font-weight:700;color:var(--oms-cyan);padding-top:12px"><?= array_sum(array_column($topProducts,'qty')) ?></td>
                            <td class="text-end"    style="font-size:13px;font-weight:700;color:var(--oms-gold);padding-top:12px">₺<?= number_format(array_sum(array_column($topProducts,'rev'))) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ══ JAVASCRIPT ═════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    // Live clock
    function updateClock() {
        const el = document.getElementById('last-update-time');
        if (!el) return;
        el.textContent = new Date().toLocaleTimeString('tr-TR', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
    }
    updateClock();
    setInterval(updateClock, 1000);

    // Date range toggle
    window.setRange = function (range, btn) {
        document.querySelectorAll('.daterange-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    };

    // Mock live feed data
    const CUSTOMERS = [
        'Ayşe Kaya','Mehmet Demir','Fatma Şahin','Ali Yılmaz','Zeynep Çelik',
        'Emre Arslan','Selin Kurt','Burak Öztürk','Deniz Aydın','Cansu Polat',
        'Kerem Doğan','Merve Güneş','Tolga Erdoğan','Nisan Bozkurt','Cem Yıldız',
        'Hande Sezer','Barış Yıldız','Melis Kara','Oğuzhan Şen','Büşra Çelik'
    ];
    const STATUSES = [
        {label:'Yeni Sipariş',      color:'#22d3ee', icon:'bi-bag-check-fill'},
        {label:'Ödeme Bekleniyor', color:'#fbbf24', icon:'bi-credit-card'},
        {label:'Hazırlanıyor',      color:'#3b82f6', icon:'bi-box-seam'},
        {label:'Kargoya Verildi',   color:'#a78bfa', icon:'bi-truck'},
        {label:'Teslim Edildi',     color:'#4ade80', icon:'bi-check-circle-fill'},
        {label:'İptal Edildi',      color:'#f87171', icon:'bi-x-circle-fill'},
        {label:'İade Talebi',       color:'#fb923c', icon:'bi-arrow-return-left'},
    ];

    function rnd(a, b) { return Math.floor(Math.random() * (b - a + 1)) + a; }
    function pick(arr)  { return arr[Math.floor(Math.random() * arr.length)]; }

    function newFeedItem() {
        const st   = pick(STATUSES);
        const num  = 'SM-2026-' + (10000 + rnd(0, 89999));
        const cust = pick(CUSTOMERS);
        const amt  = rnd(250, 6500).toLocaleString('tr-TR');
        const ago  = rnd(1, 5) + ' dk önce';
        const bgC  = st.color + '1a';

        const div = document.createElement('div');
        div.className = 'feed-item';
        div.setAttribute('role', 'article');
        div.setAttribute('aria-label', 'Sipariş ' + num);
        div.innerHTML = `
            <div class="feed-icon" style="background:${bgC}">
                <i class="bi ${st.icon}" style="color:${st.color}"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div class="feed-num">${num}</div>
                <div class="feed-cust">${cust}</div>
            </div>
            <span class="feed-badge" style="background:${bgC};color:${st.color};border:1px solid ${st.color}33">${st.label}</span>
            <div class="text-end">
                <div class="feed-amt" style="color:#c5a880">₺${amt}</div>
                <div class="feed-ago">${ago}</div>
            </div>`;
        return div;
    }

    function prependFeedItem() {
        const feed = document.getElementById('live-feed');
        if (!feed) return;
        feed.insertBefore(newFeedItem(), feed.firstChild);
        const items = feed.querySelectorAll('.feed-item');
        if (items.length > 20) items[items.length - 1].remove();
        const counter = document.getElementById('live-counter');
        if (counter) counter.textContent = 'Son ' + feed.querySelectorAll('.feed-item').length + ' etkinlik';
    }
    setInterval(prependFeedItem, 3500);

    // Bar chart hover
    document.querySelectorAll('.hourly-bar').forEach(function (bar) {
        bar.style.cursor = 'pointer';
        bar.style.transition = 'opacity .2s';
        bar.addEventListener('mouseenter', function () { this.style.opacity = '0.65'; });
        bar.addEventListener('mouseleave', function () { this.style.opacity = '1'; });
    });

    // Staggered bar animation
    document.querySelectorAll('.hourly-bar').forEach(function (bar, i) {
        bar.style.opacity = '0';
        bar.style.transition = 'opacity .3s';
        setTimeout(function () { bar.style.opacity = '1'; }, i * 38);
    });

    // Animate pct bars
    document.querySelectorAll('.pct-bar').forEach(function (bar) {
        const w = bar.style.width;
        bar.style.width = '0';
        setTimeout(function () { bar.style.width = w; }, 400);
    });

}());
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
