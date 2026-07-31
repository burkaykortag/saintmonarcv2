<?php
use App\Helpers\ComponentHelper;

$title = 'OMS – Sipariş Analitiği | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';

// ─── Mock Data ────────────────────────────────────────────────────────────────
$kpis = [
    ['label'=>'Toplam Sipariş',   'value'=>'4.827',  'sub'=>'bu ay',      'change'=>12.4,  'icon'=>'bi-cart-check-fill',   'color'=>'#c5a880', 'bg'=>'rgba(197,168,128,0.12)',
     'spark'=>[42,55,48,70,65,80,74,90,85,95,88,102]],
    ['label'=>'Toplam Gelir',     'value'=>'₺2.14M', 'sub'=>'bu ay',      'change'=>18.7,  'icon'=>'bi-cash-stack',        'color'=>'#22c55e', 'bg'=>'rgba(34,197,94,0.12)',
     'spark'=>[60,72,68,88,82,95,91,110,105,118,112,130]],
    ['label'=>'Ort. Sipariş',     'value'=>'₺443',   'sub'=>'AOV',        'change'=>5.2,   'icon'=>'bi-receipt-cutoff',    'color'=>'#38bdf8', 'bg'=>'rgba(56,189,248,0.12)',
     'spark'=>[420,435,428,440,445,450,442,448,455,460,458,443]],
    ['label'=>'Dönüşüm Oranı',   'value'=>'3.84%',  'sub'=>'ziyaretçi',  'change'=>0.6,   'icon'=>'bi-funnel-fill',       'color'=>'#a78bfa', 'bg'=>'rgba(167,139,250,0.12)',
     'spark'=>[3.1,3.3,3.0,3.5,3.4,3.6,3.7,3.8,3.7,3.9,3.8,3.84]],
    ['label'=>'İptal Oranı',      'value'=>'6.2%',   'sub'=>'siparişler', 'change'=>-1.3,  'icon'=>'bi-x-circle-fill',     'color'=>'#f87171', 'bg'=>'rgba(248,113,113,0.12)',
     'spark'=>[8.5,8.1,7.9,7.6,7.2,7.0,6.8,6.7,6.5,6.4,6.3,6.2]],
    ['label'=>'İade Oranı',       'value'=>'4.1%',   'sub'=>'siparişler', 'change'=>-0.8,  'icon'=>'bi-arrow-return-left', 'color'=>'#fb923c', 'bg'=>'rgba(251,146,60,0.12)',
     'spark'=>[5.2,5.0,4.9,4.7,4.5,4.4,4.3,4.2,4.2,4.1,4.1,4.1]],
    ['label'=>'Ort. Hazırlama',   'value'=>'1.8 sa', 'sub'=>'hedef: 2sa', 'change'=>11.1,  'icon'=>'bi-box-seam-fill',     'color'=>'#34d399', 'bg'=>'rgba(52,211,153,0.12)',
     'spark'=>[2.8,2.6,2.5,2.4,2.3,2.2,2.1,2.0,1.9,1.9,1.8,1.8]],
    ['label'=>'Ort. Teslimat',    'value'=>'2.4 gün','sub'=>'hedef: 3gün','change'=>20.0,  'icon'=>'bi-truck',             'color'=>'#22d3ee', 'bg'=>'rgba(34,211,238,0.12)',
     'spark'=>[3.8,3.5,3.3,3.1,2.9,2.8,2.7,2.6,2.5,2.5,2.4,2.4]],
];

$hourlyToday    = [4,2,3,2,1,2,5,14,28,42,55,68,74,80,72,65,58,70,76,64,48,35,22,12];
$hourlyYest     = [5,3,2,1,2,3,6,16,32,45,52,60,70,75,68,60,55,65,70,58,44,30,18,10];
$hourlyLastWeek = [3,2,2,1,1,2,5,12,25,38,48,58,65,70,64,58,50,62,68,55,40,28,16,8];

$weekDays   = ['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'];
$weekOrders = [312,285,340,298,420,385,210];
$weekRev    = [138240,126450,150720,132240,186300,170925,93150];

$months      = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
$monthOrders = [2840,2560,3120,2980,3450,3680,4100,4320,3890,4200,4580,4827];
$monthRev    = [1258800,1134720,1381920,1320360,1528350,1630560,1816300,1913760,1723410,1860600,2029060,2139546];

$carriers = [
    ['name'=>'Yurtiçi Kargo', 'icon'=>'bi-truck-front-fill','sent'=>1842,'delivered'=>1798,'avg_days'=>2.1,'delay_pct'=>2.4,'score'=>4.7,'color'=>'#22c55e'],
    ['name'=>'Aras Kargo',    'icon'=>'bi-truck-front-fill','sent'=>1290,'delivered'=>1241,'avg_days'=>2.6,'delay_pct'=>3.8,'score'=>4.4,'color'=>'#38bdf8'],
    ['name'=>'MNG Kargo',     'icon'=>'bi-truck-front-fill','sent'=>890, 'delivered'=>852, 'avg_days'=>2.9,'delay_pct'=>4.3,'score'=>4.2,'color'=>'#a78bfa'],
    ['name'=>'PTT Kargo',     'icon'=>'bi-truck-front-fill','sent'=>520, 'delivered'=>488, 'avg_days'=>3.4,'delay_pct'=>6.2,'score'=>3.9,'color'=>'#fb923c'],
    ['name'=>'Sürat Kargo',   'icon'=>'bi-truck-front-fill','sent'=>285, 'delivered'=>271, 'avg_days'=>2.4,'delay_pct'=>4.9,'score'=>4.1,'color'=>'#f472b6'],
];

$cancelReasons = [
    ['label'=>'Fikir Değişikliği',     'pct'=>34,'color'=>'#c5a880'],
    ['label'=>'Fiyat Uygunsuzluğu',   'pct'=>22,'color'=>'#38bdf8'],
    ['label'=>'Geç Teslimat Beklenti','pct'=>18,'color'=>'#a78bfa'],
    ['label'=>'Ürün Bulunamadı',       'pct'=>14,'color'=>'#f87171'],
    ['label'=>'Diğer',                 'pct'=>12,'color'=>'rgba(255,255,255,0.25)'],
];

$returnReasons = [
    ['label'=>'Beden/Model Uyumsuzluğu','pct'=>41],
    ['label'=>'Ürün Hasarlı Geldi',     'pct'=>28],
    ['label'=>'Renk Farkı',             'pct'=>17],
    ['label'=>'Yanlış Ürün',            'pct'=>9],
    ['label'=>'Diğer',                  'pct'=>5],
];

$prepBuckets = ['≤0.5','≤1','≤1.5','≤2','≤2.5','≤3','≤3.5','≤4','≤4.5','>4.5'];
$prepCounts  = [45,182,490,812,620,340,180,95,42,21];

$cities = [
    ['name'=>'İstanbul','orders'=>1842,'rev'=>816648,'x'=>220,'y'=>138],
    ['name'=>'Ankara',  'orders'=>684, 'rev'=>303012,'x'=>340,'y'=>185],
    ['name'=>'İzmir',   'orders'=>512, 'rev'=>226976,'x'=>148,'y'=>220],
    ['name'=>'Bursa',   'orders'=>298, 'rev'=>132114,'x'=>240,'y'=>158],
    ['name'=>'Antalya', 'orders'=>264, 'rev'=>117040,'x'=>295,'y'=>295],
    ['name'=>'Adana',   'orders'=>198, 'rev'=>87768, 'x'=>390,'y'=>278],
    ['name'=>'Gaziantep','orders'=>174,'rev'=>77124, 'x'=>420,'y'=>272],
    ['name'=>'Konya',   'orders'=>156, 'rev'=>69156, 'x'=>340,'y'=>248],
    ['name'=>'Kayseri', 'orders'=>128, 'rev'=>56704, 'x'=>390,'y'=>215],
    ['name'=>'Mersin',  'orders'=>108, 'rev'=>47844, 'x'=>360,'y'=>285],
];

$predOrders = [388,402,415,395,445,520,310];
$predLow    = [355,372,385,362,412,490,280];
$predHigh   = [420,432,448,428,478,552,340];
$predDays   = ['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'];
?>
<style>
.analytics-header-bar{background:linear-gradient(135deg,rgba(197,168,128,.06),rgba(56,189,248,.04));border:1px solid rgba(197,168,128,.15);border-radius:16px;padding:20px 24px;margin-bottom:24px}
.kpi-card{background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:20px;transition:border-color .25s,transform .25s,box-shadow .25s;position:relative;overflow:hidden}
.kpi-card::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,var(--kpi-color,#c5a880),transparent 60%);opacity:0;transition:opacity .3s;pointer-events:none}
.kpi-card:hover{border-color:rgba(255,255,255,.15);transform:translateY(-3px);box-shadow:0 12px 40px rgba(0,0,0,.35)}
.kpi-card:hover::before{opacity:.04}
.kpi-icon-wrap{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.kpi-value{font-size:26px;font-weight:700;line-height:1.1;letter-spacing:-.5px}
.kpi-label{font-size:11px;font-weight:500;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.6px}
.kpi-sub{font-size:11px;color:rgba(255,255,255,.35)}
.badge-up{background:rgba(34,197,94,.15);color:#4ade80;font-size:10px;padding:2px 7px;border-radius:20px;font-weight:600}
.badge-down{background:rgba(248,113,113,.15);color:#f87171;font-size:10px;padding:2px 7px;border-radius:20px;font-weight:600}
.section-card{background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:24px;margin-bottom:24px}
.section-title{font-size:15px;font-weight:600;color:#fff;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.section-subtitle{font-size:12px;color:rgba(255,255,255,.35);margin-bottom:20px}
.date-pill{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:6px 14px;font-size:12px;font-weight:500;color:rgba(255,255,255,.55);cursor:pointer;transition:all .2s}
.date-pill:hover,.date-pill.active{background:rgba(197,168,128,.15);border-color:rgba(197,168,128,.4);color:#c5a880}
.chart-tab{background:transparent;border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:5px 14px;font-size:12px;color:rgba(255,255,255,.45);cursor:pointer;transition:all .2s}
.chart-tab.active{background:rgba(197,168,128,.15);border-color:rgba(197,168,128,.4);color:#c5a880}
.cargo-table th{font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:rgba(255,255,255,.35);border-bottom:1px solid rgba(255,255,255,.07);padding:10px 12px;font-weight:500}
.cargo-table td{font-size:13px;color:rgba(255,255,255,.8);border-bottom:1px solid rgba(255,255,255,.04);padding:12px;vertical-align:middle}
.cargo-table tr:last-child td{border-bottom:none}
.perf-bar-bg{background:rgba(255,255,255,.06);border-radius:4px;height:6px;overflow:hidden}
.perf-bar-fill{height:100%;border-radius:4px;transition:width .6s ease}
.star-rating{color:#c5a880;font-size:13px;letter-spacing:1px}
.hist-bar{transition:opacity .2s}.hist-bar:hover{opacity:.8}
.ai-badge{background:linear-gradient(135deg,rgba(167,139,250,.2),rgba(56,189,248,.1));border:1px solid rgba(167,139,250,.3);border-radius:8px;padding:3px 10px;font-size:11px;color:#a78bfa;font-weight:600;display:inline-flex;align-items:center;gap:5px}
.confidence-band{fill:rgba(197,168,128,.08)}
.donut-legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.export-btn{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:7px 14px;font-size:12px;color:rgba(255,255,255,.65);cursor:pointer;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.export-btn:hover{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.15);color:#fff}
.export-btn.gold{border-color:rgba(197,168,128,.3);color:#c5a880}
.export-btn.gold:hover{background:rgba(197,168,128,.1)}
.city-marker{transition:r .3s;cursor:pointer}.city-marker:hover{filter:brightness(1.3)}
.pulse-dot{width:8px;height:8px;background:#4ade80;border-radius:50%;animation:pulse-anim 2s infinite;display:inline-block}
@keyframes pulse-anim{0%,100%{box-shadow:0 0 0 0 rgba(74,222,128,.4)}50%{box-shadow:0 0 0 6px rgba(74,222,128,0)}}
.dot-visible{transition:opacity .2s}
</style>

<?php
/* ── SVG helper ──────────────────────────────────────────────────────── */
function sparklinePts(array $d,int $w=80,int $h=32):string{
    $mn=min($d);$mx=max($d);$r=$mx-$mn?:1;$n=count($d);$pts=[];
    for($i=0;$i<$n;$i++){$pts[]=round(($i/($n-1))*$w,2).','.round($h-(($d[$i]-$mn)/$r)*$h,2);}
    return implode(' ',$pts);
}
function sparkSVG(array $d,string $col):string{
    $pts=sparklinePts($d);$n=count($d);
    [$x0]=explode(',',$pts);$parts=explode(' ',$pts);[$xN]=explode(',',$parts[$n-1]);
    return "<polyline points='$pts {$xN},32 {$x0},32' fill='$col' fill-opacity='.1' stroke='none'/>".
           "<polyline points='$pts' fill='none' stroke='$col' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/>";
}

function hXY(array $a,int $i,int $n,int $w,int $h,float $mn,float $rng,int $pad):array{
    return['x'=>$pad+round(($i/($n-1))*($w-2*$pad),2),'y'=>round($h-(($a[$i]-$mn)/$rng)*$h,2)];
}
function buildSvgPath(array $d,int $w,int $h,float $mn,float $rng,int $pad):string{
    $n=count($d);$s='';
    for($i=0;$i<$n;$i++){$p=hXY($d,$i,$n,$w,$h,$mn,$rng,$pad);$s.=($i?' L':'M')."{$p['x']},{$p['y']}";}
    return $s;
}
function barChart(array $labels,array $orders,array $revs,string $id,int $w=660,int $h=150):string{
    $n=count($labels);$mxO=max($orders);$mxR=max($revs);
    $bw=floor(($w-40)/$n);$gap=4;$bw1=floor(($bw-$gap*3)/2);
    $o="<svg id='$id' viewBox='0 0 ".($w+50)." ".($h+50)."' width='100%' style='min-width:380px;' role='img' aria-label='$id'>";
    for($g=0;$g<=4;$g++){$gy=round($h-($g/4)*$h);$v=number_format(round(($g/4)*$mxO));
        $o.="<line x1='30' y1='$gy' x2='".($w+10)."' y2='$gy' stroke='rgba(255,255,255,.05)' stroke-width='1'/>".
            "<text x='26' y='".($gy+4)."' fill='rgba(255,255,255,.25)' font-size='9' font-family='Outfit,sans-serif' text-anchor='end'>$v</text>";}
    for($i=0;$i<$n;$i++){
        $cx=30+$i*$bw+$bw/2;
        $bh1=round(($orders[$i]/$mxO)*$h);$bh2=round(($revs[$i]/$mxR)*$h*.85);
        $x1=$cx-$bw1-$gap/2;$x2=$cx+$gap/2;$y1=$h-$bh1;$y2=$h-$bh2;
        $o.="<rect x='$x1' y='$y1' width='$bw1' height='$bh1' rx='3' fill='#c5a880' fill-opacity='.75' class='hist-bar'><title>{$labels[$i]}: {$orders[$i]} sipariş</title></rect>";
        $o.="<rect x='$x2' y='$y2' width='$bw1' height='$bh2' rx='3' fill='#38bdf8' fill-opacity='.55' class='hist-bar'><title>{$labels[$i]}: ₺".number_format($revs[$i])." gelir</title></rect>";
        $o.="<text x='$cx' y='".($h+16)."' fill='rgba(255,255,255,.3)' font-size='9' font-family='Outfit,sans-serif' text-anchor='middle'>{$labels[$i]}</text>";
    }
    return $o."</svg>";
}

function predXY(int $i,int $n,int $w,int $h,float $v,float $mn,float $rng):array{
    return['x'=>round(40+($i/($n-1))*($w-60),2),'y'=>round($h-(($v-$mn)/$rng)*$h,2)];
}
?>

<!-- ── PAGE HEADER ──────────────────────────────────────────────────────────── -->
<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Siparişler'=>url('/admin/orders'),'Analitik'=>'#']) ?>
    <div class="analytics-header-bar mt-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-3 mb-1 flex-wrap">
                    <h2 class="text-white fw-bold m-0" style="font-size:24px;letter-spacing:-.5px;">
                        <i class="bi bi-graph-up-arrow me-2" style="color:#c5a880;"></i>Sipariş Analitiği &amp; Zeka Merkezi
                    </h2>
                    <span class="ai-badge"><i class="bi bi-stars"></i>AI Destekli</span>
                </div>
                <p class="m-0" style="font-size:13px;color:rgba(255,255,255,.4);">
                    Son güncelleme: <?= date('d.m.Y H:i') ?> &nbsp;·&nbsp; Gerçek zamanlı veri akışı aktif
                    <span class="pulse-dot ms-2" aria-hidden="true"></span>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <div class="d-flex gap-1 flex-wrap" role="group" aria-label="Tarih aralığı seçici">
                    <?php foreach(['Bugün'=>'today','Hafta'=>'week','Ay'=>'month','3 Ay'=>'3m','Yıl'=>'year'] as $lbl=>$val): ?>
                    <button class="date-pill <?= $val==='month'?'active':'' ?>"
                            onclick="setRange(this,'<?= $val ?>')"
                            aria-pressed="<?= $val==='month'?'true':'false' ?>">
                        <?= $lbl ?>
                    </button>
                    <?php endforeach; ?>
                    <button class="date-pill" onclick="setRange(this,'custom')" aria-pressed="false">
                        <i class="bi bi-calendar3 me-1"></i>Özel
                    </button>
                </div>
                <div class="vr opacity-25 d-none d-md-block"></div>
                <a href="#" class="export-btn" aria-label="CSV olarak dışa aktar"><i class="bi bi-filetype-csv"></i>CSV</a>
                <a href="#" class="export-btn" aria-label="Excel olarak dışa aktar"><i class="bi bi-file-earmark-excel"></i>Excel</a>
                <a href="#" class="export-btn gold" aria-label="PDF olarak dışa aktar"><i class="bi bi-file-earmark-pdf"></i>PDF</a>
            </div>
        </div>
    </div>
</div>

<!-- Custom date range (hidden by default) -->
<div id="custom-range-row" class="d-none flex-wrap gap-2 align-items-center mb-4">
    <input type="date" id="range-start" class="form-control form-control-sm" style="background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.1);color:#fff;width:auto;" aria-label="Başlangıç tarihi">
    <span style="color:rgba(255,255,255,.3);">—</span>
    <input type="date" id="range-end" class="form-control form-control-sm" style="background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.1);color:#fff;width:auto;" aria-label="Bitiş tarihi">
    <button class="btn btn-sm" style="background:rgba(197,168,128,.15);color:#c5a880;border:1px solid rgba(197,168,128,.3);"><i class="bi bi-check2 me-1"></i>Uygula</button>
</div>

<!-- ── KPI CARDS ─────────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4" role="list" aria-label="Temel performans göstergeleri">
<?php foreach($kpis as $k):
    $isPos=$k['change']>=0;
    $chStr=($isPos?'+':'').number_format($k['change'],1).'%';
?>
    <div class="col-xl-3 col-lg-4 col-md-6" role="listitem">
        <div class="kpi-card h-100" style="--kpi-color:<?= $k['color'] ?>;">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div>
                    <div class="kpi-label mb-1"><?= htmlspecialchars($k['label']) ?></div>
                    <div class="kpi-value" style="color:<?= $k['color'] ?>;"><?= htmlspecialchars($k['value']) ?></div>
                    <div class="kpi-sub mt-1"><?= htmlspecialchars($k['sub']) ?></div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <div class="kpi-icon-wrap" style="background:<?= $k['bg'] ?>;color:<?= $k['color'] ?>;"><i class="<?= $k['icon'] ?>"></i></div>
                    <span class="<?= $isPos?'badge-up':'badge-down' ?>" aria-label="Değişim <?= $chStr ?>">
                        <i class="bi bi-arrow-<?= $isPos?'up':'down' ?>-short"></i><?= $chStr ?>
                    </span>
                </div>
            </div>
            <svg class="w-100" height="32" viewBox="0 0 80 32" preserveAspectRatio="none" aria-hidden="true">
                <?= sparkSVG($k['spark'], $k['color']) ?>
            </svg>
        </div>
    </div>
<?php endforeach; ?>
</div>

<!-- ── HOURLY CHART ──────────────────────────────────────────────────────────── -->
<div class="section-card" aria-labelledby="hourly-title">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <div class="section-title" id="hourly-title"><i class="bi bi-clock-history" style="color:#c5a880;"></i>Saatlik Sipariş Dağılımı</div>
            <div class="section-subtitle">24 saatlik sipariş yoğunluğu karşılaştırması</div>
        </div>
        <div class="d-flex gap-3 align-items-center flex-wrap" role="img" aria-label="Grafik açıklaması">
            <span style="font-size:12px;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:6px;">
                <svg width="24" height="8" aria-hidden="true"><line x1="0" y1="4" x2="24" y2="4" stroke="#c5a880" stroke-width="2.5" stroke-linecap="round"/></svg>Bugün
            </span>
            <span style="font-size:12px;color:rgba(255,255,255,.4);display:flex;align-items:center;gap:6px;">
                <svg width="24" height="8" aria-hidden="true"><line x1="0" y1="4" x2="24" y2="4" stroke="rgba(255,255,255,.3)" stroke-width="2" stroke-linecap="round"/></svg>Dün
            </span>
            <span style="font-size:12px;color:rgba(255,255,255,.35);display:flex;align-items:center;gap:6px;">
                <svg width="24" height="8" aria-hidden="true"><line x1="0" y1="4" x2="24" y2="4" stroke="rgba(167,139,250,.5)" stroke-width="2" stroke-dasharray="4 3" stroke-linecap="round"/></svg>Geçen Hafta
            </span>
        </div>
    </div>
    <?php
    $hw=680;$hh=140;$hpad=10;
    $hMax=max(array_merge($hourlyToday,$hourlyYest,$hourlyLastWeek))+5;$hMin=0;$hRng=$hMax-$hMin;
    $pT =buildSvgPath($hourlyToday,   $hw,$hh,$hMin,$hRng,$hpad);
    $pY =buildSvgPath($hourlyYest,    $hw,$hh,$hMin,$hRng,$hpad);
    $pLW=buildSvgPath($hourlyLastWeek,$hw,$hh,$hMin,$hRng,$hpad);
    $pFirst=hXY($hourlyToday,0,24,$hw,$hh,$hMin,$hRng,$hpad);
    $pLast =hXY($hourlyToday,23,24,$hw,$hh,$hMin,$hRng,$hpad);
    $areaPath="$pT L{$pLast['x']},{$hh} L{$pFirst['x']},{$hh} Z";
    ?>
    <div style="overflow-x:auto;">
        <svg viewBox="0 0 <?= $hw+50 ?> <?= $hh+50 ?>" width="100%" style="min-width:480px;" role="img" aria-label="Saatlik sipariş dağılımı">
            <defs>
                <linearGradient id="hAreaGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#c5a880" stop-opacity=".18"/><stop offset="100%" stop-color="#c5a880" stop-opacity="0"/>
                </linearGradient>
                <filter id="lineGlow"><feGaussianBlur stdDeviation="2" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
            </defs>
            <?php for($g=0;$g<=4;$g++):$gy=round($hh-($g/4)*$hh);$gv=round(($g/4)*$hMax); ?>
            <line x1="<?= $hpad+20 ?>" y1="<?= $gy ?>" x2="<?= $hw-$hpad+20 ?>" y2="<?= $gy ?>" stroke="rgba(255,255,255,.05)" stroke-width="1"/>
            <text x="<?= $hw+24 ?>" y="<?= $gy+4 ?>" fill="rgba(255,255,255,.25)" font-size="9" font-family="Outfit,sans-serif"><?= $gv ?></text>
            <?php endfor; ?>
            <g transform="translate(20,0)">
                <path d="<?= $areaPath ?>" fill="url(#hAreaGrad)"/>
                <path d="<?= $pLW ?>" fill="none" stroke="rgba(167,139,250,.45)" stroke-width="1.8" stroke-dasharray="5 4" stroke-linecap="round"/>
                <path d="<?= $pY  ?>" fill="none" stroke="rgba(255,255,255,.25)" stroke-width="1.8" stroke-linecap="round"/>
                <path d="<?= $pT  ?>" fill="none" stroke="#c5a880" stroke-width="2.5" stroke-linecap="round" filter="url(#lineGlow)"/>
                <?php for($hi=0;$hi<24;$hi++):
                    $dp=hXY($hourlyToday,$hi,24,$hw,$hh,$hMin,$hRng,$hpad); ?>
                <circle cx="<?= $dp['x'] ?>" cy="<?= $dp['y'] ?>" r="4" fill="#c5a880" fill-opacity="0"
                        class="dot-visible" tabindex="0" role="img"
                        aria-label="Saat <?= $hi ?>:00 — <?= $hourlyToday[$hi] ?> sipariş"
                        onmouseenter="this.style.fillOpacity=1" onmouseleave="this.style.fillOpacity=0"
                        onfocus="this.style.fillOpacity=1" onblur="this.style.fillOpacity=0">
                    <title><?= $hi ?>:00 — <?= $hourlyToday[$hi] ?> sipariş</title>
                </circle>
                <?php endfor; ?>
                <?php for($hi=0;$hi<24;$hi+=2):
                    $dp=hXY($hourlyToday,$hi,24,$hw,$hh,$hMin,$hRng,$hpad); ?>
                <text x="<?= $dp['x'] ?>" y="<?= $hh+18 ?>" fill="rgba(255,255,255,.3)" font-size="9" font-family="Outfit,sans-serif" text-anchor="middle"><?= sprintf('%02d',$hi) ?></text>
                <?php endfor; ?>
            </g>
            <text x="<?= ($hw+50)/2 ?>" y="<?= $hh+40 ?>" fill="rgba(255,255,255,.2)" font-size="9" font-family="Outfit,sans-serif" text-anchor="middle">Saat (00 – 23)</text>
        </svg>
    </div>
</div>

<!-- ── WEEKLY/MONTHLY BAR CHART ──────────────────────────────────────────────── -->
<div class="section-card" aria-labelledby="trend-title">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <div class="section-title" id="trend-title"><i class="bi bi-bar-chart-fill" style="color:#38bdf8;"></i>Sipariş &amp; Gelir Trendi</div>
            <div class="section-subtitle">Zaman bazlı sipariş sayısı ve gelir karşılaştırması</div>
        </div>
        <div class="d-flex gap-1" role="group" aria-label="Trend periyodu seçici">
            <button class="chart-tab active" id="tab-weekly"  onclick="showTrend('weekly')">Haftalık</button>
            <button class="chart-tab"        id="tab-monthly" onclick="showTrend('monthly')">Aylık</button>
            <button class="chart-tab"        id="tab-yearly"  onclick="showTrend('yearly')">Yıllık</button>
        </div>
    </div>
    <div id="trend-weekly"  style="overflow-x:auto;"><?= barChart($weekDays,$weekOrders,$weekRev,'bar-weekly') ?></div>
    <div id="trend-monthly" style="display:none;overflow-x:auto;"><?= barChart($months,$monthOrders,$monthRev,'bar-monthly') ?></div>
    <div id="trend-yearly"  style="display:none;overflow-x:auto;"><?= barChart($months,$monthOrders,$monthRev,'bar-yearly') ?></div>
    <div class="d-flex gap-4 mt-3" aria-label="Renk açıklaması">
        <span style="font-size:12px;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:7px;">
            <span style="width:12px;height:12px;border-radius:3px;background:#c5a880;display:inline-block;"></span>Sipariş Sayısı
        </span>
        <span style="font-size:12px;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:7px;">
            <span style="width:12px;height:12px;border-radius:3px;background:#38bdf8;opacity:.7;display:inline-block;"></span>Gelir (₺)
        </span>
    </div>
</div>

<!-- ── CARGO PERFORMANCE ─────────────────────────────────────────────────────── -->
<div class="section-card" aria-labelledby="cargo-title">
    <div class="section-title" id="cargo-title"><i class="bi bi-truck-front-fill" style="color:#34d399;"></i>Kargo Firması Performansı</div>
    <div class="section-subtitle">Son 30 günün kargo karşılaştırmalı analizi</div>
    <div style="overflow-x:auto;">
        <table class="cargo-table w-100" role="table" aria-label="Kargo performans tablosu">
            <thead>
                <tr>
                    <th scope="col">Kargo Firması</th>
                    <th scope="col">Gönderim</th>
                    <th scope="col">Teslim</th>
                    <th scope="col">Teslim %</th>
                    <th scope="col">Ort. Süre</th>
                    <th scope="col">Gecikme %</th>
                    <th scope="col">Müşteri Puanı</th>
                    <th scope="col">Performans</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($carriers as $c):
                $dp=round($c['delivered']/$c['sent']*100,1);
                $pp=min(100,max(0,round((5-($c['delay_pct']/10))*20+($c['score']/5)*40)));
                $stars=str_repeat('★',round($c['score'])).str_repeat('☆',5-round($c['score']));
            ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,.06);display:flex;align-items:center;justify-content:center;color:<?= $c['color'] ?>;"><i class="<?= $c['icon'] ?>" style="font-size:14px;"></i></div>
                            <span style="font-weight:600;"><?= htmlspecialchars($c['name']) ?></span>
                        </div>
                    </td>
                    <td><strong><?= number_format($c['sent']) ?></strong></td>
                    <td><?= number_format($c['delivered']) ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="perf-bar-bg" style="width:60px;"><div class="perf-bar-fill" style="width:<?= $dp ?>%;background:<?= $c['color'] ?>;"></div></div>
                            <span style="font-size:12px;"><?= $dp ?>%</span>
                        </div>
                    </td>
                    <td><span style="color:<?= $c['avg_days']<=2.5?'#4ade80':($c['avg_days']<=3?'#fbbf24':'#f87171') ?>;"><?= $c['avg_days'] ?> gün</span></td>
                    <td><span class="<?= $c['delay_pct']<=3?'badge-up':'badge-down' ?>"><?= $c['delay_pct'] ?>%</span></td>
                    <td>
                        <div class="star-rating" aria-label="<?= $c['score'] ?> yıldız"><?= $stars ?></div>
                        <div style="font-size:11px;color:rgba(255,255,255,.4);"><?= $c['score'] ?> / 5.0</div>
                    </td>
                    <td>
                        <div class="perf-bar-bg" style="width:80px;"><div class="perf-bar-fill" style="width:<?= $pp ?>%;background:linear-gradient(90deg,<?= $c['color'] ?>,<?= $c['color'] ?>88);"></div></div>
                        <div style="font-size:10px;color:rgba(255,255,255,.3);margin-top:3px;"><?= $pp ?>% skor</div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── CANCEL & RETURN ANALYSIS ──────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <!-- İptal -->
    <div class="col-lg-6">
        <div class="section-card h-100" aria-labelledby="cancel-title">
            <div class="section-title" id="cancel-title"><i class="bi bi-x-circle-fill" style="color:#f87171;"></i>İptal Analizi</div>
            <div class="section-subtitle">İptal nedenlerinin dağılımı</div>
            <div class="row align-items-center g-3">
                <div class="col-5">
                    <?php
                    $dR=55;$dCX=65;$dCY=65;$dSW=18;$dC=2*M_PI*$dR;$dOff=0;
                    $dTotal=array_sum(array_column($cancelReasons,'pct'));
                    echo "<svg viewBox='0 0 130 130' width='100%' style='max-width:160px;' role='img' aria-label='İptal nedenleri donut grafiği'>";
                    echo "<circle cx='$dCX' cy='$dCY' r='$dR' fill='none' stroke='rgba(255,255,255,.04)' stroke-width='$dSW'/>";
                    foreach($cancelReasons as $cr){
                        $dash=round(($cr['pct']/$dTotal)*$dC,2);
                        $gap=$dC-$dash;
                        $rot=round(-90+($dOff/$dTotal)*360,2);
                        echo "<circle cx='$dCX' cy='$dCY' r='$dR' fill='none' stroke='{$cr['color']}' stroke-width='$dSW' stroke-dasharray='$dash $gap' transform='rotate($rot $dCX $dCY)' style='transition:stroke-dasharray .6s;'/>";
                        $dOff+=$cr['pct'];
                    }
                    echo "<text x='$dCX' y='".($dCY-5)."' fill='#fff' font-size='16' font-weight='700' font-family='Outfit,sans-serif' text-anchor='middle'>6.2%</text>";
                    echo "<text x='$dCX' y='".($dCY+10)."' fill='rgba(255,255,255,.35)' font-size='8' font-family='Outfit,sans-serif' text-anchor='middle'>iptal oranı</text>";
                    echo "</svg>";
                    ?>
                </div>
                <div class="col-7">
                    <?php foreach($cancelReasons as $cr): ?>
                    <div class="d-flex align-items-center justify-content-between mb-1" style="font-size:12px;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="donut-legend-dot" style="background:<?= $cr['color'] ?>;"></span>
                            <span style="color:rgba(255,255,255,.7);"><?= htmlspecialchars($cr['label']) ?></span>
                        </div>
                        <strong style="color:#fff;"><?= $cr['pct'] ?>%</strong>
                    </div>
                    <div class="perf-bar-bg mb-2" style="height:4px;"><div class="perf-bar-fill" style="width:<?= $cr['pct'] ?>%;background:<?= $cr['color'] ?>;height:4px;"></div></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- İade -->
    <div class="col-lg-6">
        <div class="section-card h-100" aria-labelledby="return-title">
            <div class="section-title" id="return-title"><i class="bi bi-arrow-return-left" style="color:#fb923c;"></i>İade Analizi</div>
            <div class="section-subtitle">İade nedenlerinin dağılımı ve maliyet etkisi</div>
            <?php
            $retMax=max(array_column($returnReasons,'pct'));
            $retCols=['#fb923c','#fbbf24','#a78bfa','#38bdf8','rgba(255,255,255,.25)'];
            ?>
            <div style="overflow-x:auto;">
                <svg viewBox="0 0 290 <?= count($returnReasons)*36+10 ?>" width="100%" role="img" aria-label="İade nedenleri çubuk grafiği">
                <?php foreach($returnReasons as $ri=>$rr):
                    $bw_=round(($rr['pct']/$retMax)*225);
                    $y_=$ri*36+4;$rc=$retCols[$ri]??'#c5a880';
                ?>
                    <text x="0" y="<?= $y_+11 ?>" fill="rgba(255,255,255,.65)" font-size="10" font-family="Outfit,sans-serif"><?= htmlspecialchars($rr['label']) ?></text>
                    <rect x="0" y="<?= $y_+16 ?>" width="<?= $bw_ ?>" height="12" rx="3" fill="<?= $rc ?>" fill-opacity=".7" class="hist-bar"><title><?= $rr['label'] ?>: <?= $rr['pct'] ?>%</title></rect>
                    <text x="<?= $bw_+6 ?>" y="<?= $y_+27 ?>" fill="rgba(255,255,255,.5)" font-size="10" font-family="Outfit,sans-serif"><?= $rr['pct'] ?>%</text>
                <?php endforeach; ?>
                </svg>
            </div>
            <div class="d-flex gap-3 flex-wrap pt-2 mt-2" style="border-top:1px solid rgba(255,255,255,.06);">
                <div style="font-size:12px;color:rgba(255,255,255,.4);"><i class="bi bi-arrow-clockwise me-1" style="color:#fb923c;"></i>Ort. iade süresi: <strong style="color:#fff;">3.2 gün</strong></div>
                <div style="font-size:12px;color:rgba(255,255,255,.4);"><i class="bi bi-currency-lira me-1" style="color:#fb923c;"></i>İade maliyeti: <strong style="color:#fff;">₺87.420</strong></div>
            </div>
        </div>
    </div>
</div>

<!-- ── PREP TIME HISTOGRAM ────────────────────────────────────────────────────── -->
<div class="section-card" aria-labelledby="hist-title">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <div class="section-title" id="hist-title"><i class="bi bi-stopwatch-fill" style="color:#a78bfa;"></i>Hazırlama Süresi Dağılımı</div>
            <div class="section-subtitle">Sipariş hazırlama sürelerinin histogramı — SLA hedefi: 2 saat</div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:22px;font-weight:700;color:#4ade80;"><?= round(array_sum(array_slice($prepCounts,0,4))/array_sum($prepCounts)*100) ?>%</div>
            <div style="font-size:11px;color:rgba(255,255,255,.35);">SLA Uyum Oranı</div>
        </div>
    </div>
    <?php
    $histW=620;$histH=130;$histMax=max($prepCounts);$histN=count($prepCounts);
    $histBW=floor(($histW-20)/$histN)-4;
    $slaX=20+3*(floor(($histW-20)/$histN));
    ?>
    <div style="overflow-x:auto;">
        <svg viewBox="0 0 <?= $histW+50 ?> <?= $histH+55 ?>" width="100%" style="min-width:380px;" role="img" aria-label="Hazırlama süresi histogram">
            <?php for($g=0;$g<=4;$g++):$gy=round($histH-($g/4)*$histH);$gv=round(($g/4)*$histMax); ?>
            <line x1="20" y1="<?= $gy ?>" x2="<?= $histW ?>" y2="<?= $gy ?>" stroke="rgba(255,255,255,.05)" stroke-width="1"/>
            <text x="16" y="<?= $gy+4 ?>" fill="rgba(255,255,255,.25)" font-size="9" font-family="Outfit,sans-serif" text-anchor="end"><?= $gv ?></text>
            <?php endfor; ?>
            <?php foreach($prepCounts as $pi=>$pv):
                $bh_=round(($pv/$histMax)*$histH);
                $bx_=20+$pi*(floor(($histW-20)/$histN));
                $by_=$histH-$bh_;
                $col_=($pi<=3)?'rgba(167,139,250,.7)':'rgba(248,113,113,.7)';
            ?>
                <rect x="<?= $bx_+2 ?>" y="<?= $by_ ?>" width="<?= $histBW ?>" height="<?= $bh_ ?>" rx="3" fill="<?= $col_ ?>" class="hist-bar"><title><?= $prepBuckets[$pi] ?> saat: <?= number_format($pv) ?> sipariş</title></rect>
                <text x="<?= $bx_+$histBW/2+2 ?>" y="<?= $histH+16 ?>" fill="rgba(255,255,255,.3)" font-size="8.5" font-family="Outfit,sans-serif" text-anchor="middle"><?= $prepBuckets[$pi] ?></text>
            <?php endforeach; ?>
            <line x1="<?= $slaX ?>" y1="0" x2="<?= $slaX ?>" y2="<?= $histH ?>" stroke="#4ade80" stroke-width="1.5" stroke-dasharray="4 3" opacity=".7"/>
            <text x="<?= $slaX+4 ?>" y="14" fill="#4ade80" font-size="9" font-family="Outfit,sans-serif">SLA Hedefi</text>
            <text x="<?= $slaX+4 ?>" y="26" fill="#4ade80" font-size="8" font-family="Outfit,sans-serif" opacity=".7">2 saat</text>
            <text x="<?= ($histW+50)/2 ?>" y="<?= $histH+42 ?>" fill="rgba(255,255,255,.2)" font-size="9" font-family="Outfit,sans-serif" text-anchor="middle">Hazırlama Süresi</text>
            <rect x="<?= $histW-140 ?>" y="4" width="10" height="10" rx="2" fill="rgba(167,139,250,.7)"/>
            <text x="<?= $histW-126 ?>" y="13" fill="rgba(255,255,255,.45)" font-size="9" font-family="Outfit,sans-serif">SLA Uyumlu</text>
            <rect x="<?= $histW-58 ?>" y="4" width="10" height="10" rx="2" fill="rgba(248,113,113,.7)"/>
            <text x="<?= $histW-44 ?>" y="13" fill="rgba(255,255,255,.45)" font-size="9" font-family="Outfit,sans-serif">Aşım</text>
        </svg>
    </div>
</div>

<!-- ── TURKEY CITY MAP ───────────────────────────────────────────────────────── -->
<div class="section-card" aria-labelledby="map-title">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <div class="section-title" id="map-title"><i class="bi bi-geo-alt-fill" style="color:#22d3ee;"></i>Şehir Bazlı Satış Haritası</div>
            <div class="section-subtitle">Türkiye genelinde sipariş coğrafi dağılımı — daire büyüklüğü sipariş hacmine göre</div>
        </div>
    </div>
    <div class="row g-4 align-items-start">
        <div class="col-lg-8">
            <div style="background:rgba(0,0,0,.2);border-radius:12px;overflow:hidden;">
                <svg viewBox="0 0 600 340" width="100%" role="img" aria-label="Türkiye şehir satış haritası">
                    <defs>
                        <radialGradient id="mapBg2" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="rgba(56,189,248,.04)"/><stop offset="100%" stop-color="rgba(0,0,0,0)"/>
                        </radialGradient>
                    </defs>
                    <rect width="600" height="340" fill="rgba(255,255,255,.015)" rx="12"/>
                    <rect width="600" height="340" fill="url(#mapBg2)" rx="12"/>
                    <!-- Simplified Turkey silhouette (decorative schematic) -->
                    <path d="M50,140 Q70,120 100,115 Q130,110 160,112 Q190,110 220,108
                             Q250,108 280,105 Q310,105 340,108 Q380,110 420,105
                             Q455,100 490,112 Q520,120 545,135 Q558,148 550,165
                             Q545,175 538,188 Q525,198 510,205 Q490,212 475,225
                             Q460,238 450,255 Q440,268 425,272 Q408,275 390,265
                             Q375,258 360,252 Q345,248 330,250 Q310,252 295,248
                             Q280,243 262,248 Q245,252 228,258 Q210,265 192,268
                             Q172,270 155,262 Q138,252 125,240 Q110,228 95,218
                             Q76,205 65,190 Q52,172 50,155 Z"
                          fill="rgba(34,211,238,.06)" stroke="rgba(34,211,238,.18)" stroke-width="1.5"/>
                    <?php
                    $maxOrd=max(array_column($cities,'orders'));
                    foreach($cities as $ci2=>$city2){
                        $r2=max(8,round(sqrt($city2['orders']/$maxOrd)*32));
                        $col2=($ci2===0)?'#c5a880':'#22d3ee';
                        $op2=round(0.6+($city2['orders']/$maxOrd)*0.4,2);
                        echo "<g class='city-marker' role='button' tabindex='0' aria-label='".htmlspecialchars($city2['name']).": ".number_format($city2['orders'])." sipariş'>";
                        echo "<circle cx='{$city2['x']}' cy='{$city2['y']}' r='$r2' fill='$col2' fill-opacity='.12' stroke='$col2' stroke-width='1.5' stroke-opacity='$op2'/>";
                        echo "<circle cx='{$city2['x']}' cy='{$city2['y']}' r='".round($r2*.35)."' fill='$col2' fill-opacity='.9'/>";
                        echo "<text x='{$city2['x']}' y='".($city2['y']+$r2+13)."' fill='rgba(255,255,255,.75)' font-size='9' font-family='Outfit,sans-serif' text-anchor='middle'>".htmlspecialchars($city2['name'])."</text>";
                        echo "<title>".htmlspecialchars($city2['name'])." — ".number_format($city2['orders'])." sipariş — ₺".number_format($city2['rev'])."</title></g>";
                    }
                    ?>
                    <g transform="translate(10,290)">
                        <circle cx="8" cy="8" r="8" fill="#c5a880" fill-opacity=".12" stroke="#c5a880" stroke-width="1.2"/>
                        <circle cx="8" cy="8" r="3" fill="#c5a880" fill-opacity=".9"/>
                        <text x="20" y="13" fill="rgba(255,255,255,.4)" font-size="9" font-family="Outfit,sans-serif">İstanbul (1. şehir)</text>
                        <circle cx="128" cy="8" r="6" fill="#22d3ee" fill-opacity=".12" stroke="#22d3ee" stroke-width="1.2"/>
                        <circle cx="128" cy="8" r="2.5" fill="#22d3ee" fill-opacity=".9"/>
                        <text x="138" y="13" fill="rgba(255,255,255,.4)" font-size="9" font-family="Outfit,sans-serif">Diğer şehirler</text>
                    </g>
                </svg>
            </div>
        </div>
        <div class="col-lg-4">
            <div style="font-size:12px;font-weight:600;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px;">Top 10 Şehir</div>
            <?php
            $cityTotal=array_sum(array_column($cities,'orders'));
            foreach($cities as $ci3=>$city3):
                $pct3=round($city3['orders']/$cityTotal*100,1);
            ?>
            <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid rgba(255,255,255,.04);font-size:13px;">
                <div class="d-flex align-items-center gap-2">
                    <span style="width:20px;height:20px;border-radius:6px;background:rgba(255,255,255,.06);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:rgba(255,255,255,.4);">
                        <?= $ci3+1 ?>
                    </span>
                    <span style="color:rgba(255,255,255,.8);"><?= htmlspecialchars($city3['name']) ?></span>
                </div>
                <div class="text-end">
                    <div style="font-weight:600;color:#fff;"><?= number_format($city3['orders']) ?></div>
                    <div style="font-size:10px;color:rgba(255,255,255,.3);"><?= $pct3 ?>%</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ── AI PREDICTIVE ANALYSIS ────────────────────────────────────────────────── -->
<div class="section-card" aria-labelledby="ai-title">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <div class="section-title" id="ai-title">
                <i class="bi bi-stars" style="color:#a78bfa;"></i>Tahminsel Analiz
                <span class="ai-badge ms-2"><i class="bi bi-cpu"></i>AI Modeli v2.4</span>
            </div>
            <div class="section-subtitle">Makine öğrenmesi destekli sipariş tahmini — Güven aralığı: %95</div>
        </div>
        <div class="d-flex gap-3 align-items-center">
            <div style="text-align:center;">
                <div style="font-size:28px;font-weight:700;color:#a78bfa;"><?= $predOrders[0] ?></div>
                <div style="font-size:11px;color:rgba(255,255,255,.35);">Yarınki Tahmin</div>
            </div>
            <div style="border-left:1px solid rgba(255,255,255,.07);padding-left:16px;text-align:center;">
                <div style="font-size:15px;font-weight:600;color:rgba(255,255,255,.6);"><?= $predLow[0] ?>–<?= $predHigh[0] ?></div>
                <div style="font-size:11px;color:rgba(255,255,255,.35);">Güven Aralığı</div>
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <?php
            $pw=560;$ph=140;
            $allP=array_merge($predOrders,$predLow,$predHigh);
            $pMin=min($allP)-20;$pMax=max($allP)+20;$pRng=$pMax-$pMin;
            $pN=count($predOrders);
            $pathP='';$pathL='';$pathH_='';
            for($pi=0;$pi<$pN;$pi++){
                $pp_=predXY($pi,$pN,$pw,$ph,$predOrders[$pi],$pMin,$pRng);
                $pl_=predXY($pi,$pN,$pw,$ph,$predLow[$pi],   $pMin,$pRng);
                $ph__=predXY($pi,$pN,$pw,$ph,$predHigh[$pi], $pMin,$pRng);
                $pathP .=($pi?'  L':'M')."{$pp_['x']},{$pp_['y']}";
                $pathL .=($pi?'  L':'M')."{$pl_['x']},{$pl_['y']}";
                $pathH_.=($pi?'  L':'M')."{$ph__['x']},{$ph__['y']}";
            }
            $confPoly=$pathH_;
            for($pi=$pN-1;$pi>=0;$pi--){
                $pl_=predXY($pi,$pN,$pw,$ph,$predLow[$pi],$pMin,$pRng);
                $confPoly.=" L{$pl_['x']},{$pl_['y']}";
            }
            $confPoly.=' Z';
            $pFirst=predXY(0,$pN,$pw,$ph,$predOrders[0],$pMin,$pRng);
            $pLst  =predXY($pN-1,$pN,$pw,$ph,$predOrders[$pN-1],$pMin,$pRng);
            ?>
            <div style="overflow-x:auto;">
                <svg viewBox="0 0 <?= $pw+20 ?> <?= $ph+55 ?>" width="100%" style="min-width:320px;" role="img" aria-label="7 günlük sipariş tahmini">
                    <defs>
                        <linearGradient id="predGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#a78bfa" stop-opacity=".15"/><stop offset="100%" stop-color="#a78bfa" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <?php for($g=0;$g<=4;$g++):$gy=round($ph-($g/4)*$ph);$gv=round($pMin+($g/4)*$pRng); ?>
                    <line x1="40" y1="<?= $gy ?>" x2="<?= $pw-20 ?>" y2="<?= $gy ?>" stroke="rgba(255,255,255,.05)" stroke-width="1"/>
                    <text x="36" y="<?= $gy+4 ?>" fill="rgba(255,255,255,.25)" font-size="9" font-family="Outfit,sans-serif" text-anchor="end"><?= $gv ?></text>
                    <?php endfor; ?>
                    <path d="<?= $confPoly ?>" fill="rgba(197,168,128,.08)"/>
                    <path d="<?= $pathL ?>" fill="none" stroke="rgba(167,139,250,.3)" stroke-width="1" stroke-dasharray="4 3"/>
                    <path d="<?= $pathH_ ?>" fill="none" stroke="rgba(167,139,250,.3)" stroke-width="1" stroke-dasharray="4 3"/>
                    <path d="<?= $pathP ?> L<?= $pLst['x'] ?>,<?= $ph ?> L<?= $pFirst['x'] ?>,<?= $ph ?> Z" fill="url(#predGrad)"/>
                    <path d="<?= $pathP ?>" fill="none" stroke="#a78bfa" stroke-width="2.5" stroke-linecap="round"/>
                    <?php for($pi=0;$pi<$pN;$pi++):
                        $pp_=predXY($pi,$pN,$pw,$ph,$predOrders[$pi],$pMin,$pRng);
                    ?>
                    <circle cx="<?= $pp_['x'] ?>" cy="<?= $pp_['y'] ?>" r="4" fill="#a78bfa" stroke="#07051a" stroke-width="2"
                            tabindex="0" role="img" aria-label="<?= $predDays[$pi] ?>: <?= $predOrders[$pi] ?> sipariş tahmini">
                        <title><?= $predDays[$pi] ?>: <?= $predOrders[$pi] ?> (<?= $predLow[$pi] ?>–<?= $predHigh[$pi] ?>)</title>
                    </circle>
                    <text x="<?= $pp_['x'] ?>" y="<?= $ph+18 ?>" fill="rgba(255,255,255,.3)" font-size="10" font-family="Outfit,sans-serif" text-anchor="middle"><?= $predDays[$pi] ?></text>
                    <text x="<?= $pp_['x'] ?>" y="<?= $pp_['y']-10 ?>" fill="rgba(167,139,250,.85)" font-size="9" font-family="Outfit,sans-serif" text-anchor="middle"><?= $predOrders[$pi] ?></text>
                    <?php endfor; ?>
                    <text x="<?= ($pw+20)/2 ?>" y="<?= $ph+40 ?>" fill="rgba(255,255,255,.2)" font-size="9" font-family="Outfit,sans-serif" text-anchor="middle">
                        Önümüzdeki 7 Gün (<?= date('d.m') ?>–<?= date('d.m',strtotime('+6 days')) ?>)
                    </text>
                </svg>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="d-flex flex-column gap-3 h-100">
                <!-- Seasonal -->
                <div style="background:rgba(167,139,250,.06);border:1px solid rgba(167,139,250,.15);border-radius:12px;padding:16px;">
                    <div style="font-size:11px;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;">Mevsimsel Trend</div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-sun-fill" style="color:#fbbf24;font-size:20px;"></i>
                        <div>
                            <div style="font-size:14px;font-weight:600;color:#fff;">Yaz Sezonu</div>
                            <div style="font-size:11px;color:rgba(255,255,255,.35);">Temmuz – Eylül</div>
                        </div>
                    </div>
                    <div style="font-size:12px;color:rgba(255,255,255,.5);">
                        Geçen yıla kıyasla <strong style="color:#4ade80;">+%23.4</strong> büyüme bekleniyor.
                    </div>
                </div>
                <!-- AI model metrics -->
                <div style="background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:16px;">
                    <div style="font-size:11px;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px;">Model Metrikleri</div>
                    <?php foreach([['Doğruluk (MAPE)','4.2%','#4ade80'],['R² Skoru','0.943','#a78bfa'],['Eğitim Verisi','18 ay','#38bdf8'],['Son Güncelleme','2sa önce','#c5a880']] as [$ml,$mv,$mc]): ?>
                    <div class="d-flex justify-content-between align-items-center py-1" style="border-bottom:1px solid rgba(255,255,255,.04);font-size:12px;">
                        <span style="color:rgba(255,255,255,.4);"><?= $ml ?></span>
                        <strong style="color:<?= $mc ?>;"><?= $mv ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Action buttons -->
                <div class="d-flex gap-2 flex-wrap">
                    <button class="export-btn flex-fill justify-content-center" aria-label="Tahmin raporunu indir"><i class="bi bi-download"></i>Tahmin Raporu</button>
                    <button class="export-btn gold flex-fill justify-content-center" aria-label="Model ayarları"><i class="bi bi-sliders"></i>Model Ayarları</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setRange(btn, range) {
    document.querySelectorAll('.date-pill').forEach(b => { b.classList.remove('active'); b.setAttribute('aria-pressed','false'); });
    btn.classList.add('active'); btn.setAttribute('aria-pressed','true');
    const cr = document.getElementById('custom-range-row');
    if (range === 'custom') { cr.classList.remove('d-none'); cr.classList.add('d-flex'); }
    else { cr.classList.add('d-none'); cr.classList.remove('d-flex'); }
}

function showTrend(p) {
    ['weekly','monthly','yearly'].forEach(t => {
        document.getElementById('trend-'+t).style.display = (t===p?'block':'none');
        const btn = document.getElementById('tab-'+t);
        btn.classList.toggle('active', t===p);
    });
}

// Animate perf bars on load
(function animateBars() {
    document.querySelectorAll('.perf-bar-fill').forEach(bar => {
        const w = bar.style.width; bar.style.width = '0';
        requestAnimationFrame(() => { setTimeout(() => { bar.style.width = w; }, 80); });
    });
})();
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
