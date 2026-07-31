<?php
use App\Helpers\ComponentHelper;
$title = 'OMS – Ödeme Merkezi | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();

$transactions  = $transactions  ?? [];
$pendingPayment= $pendingPayment ?? [];
$filter        = $filter        ?? 'all';

// Mock transactions if empty
if (empty($transactions)) {
    $methods = [
        ['name'=>'Kredi Kartı','icon'=>'bi-credit-card-fill','color'=>'#3b82f6'],
        ['name'=>'Havale/EFT','icon'=>'bi-bank','color'=>'#10b981'],
        ['name'=>'Kapıda Ödeme','icon'=>'bi-cash-coin','color'=>'#f59e0b'],
        ['name'=>'Cari','icon'=>'bi-building','color'=>'#8b5cf6'],
    ];
    $tStatuses = [
        ['label'=>'Tamamlandı','color'=>'#10b981'],
        ['label'=>'Bekleyen','color'=>'#f59e0b'],
        ['label'=>'Başarısız','color'=>'#ef4444'],
        ['label'=>'İade Edildi','color'=>'#f97316'],
    ];
    $names = ['Ayşe Kaya','Mehmet Demir','Fatih Yıldız','Zeynep Arslan','Can Öztürk','Selin Tekin','Murat Bakır','Elif Çelik','Hakan Şahin','Merve Polat'];
    for($i=0;$i<20;$i++) {
        $m = $methods[$i % count($methods)];
        $st = $tStatuses[$i % count($tStatuses)];
        $amount = rand(250,5000);
        $commission = round($amount * rand(15,35)/1000, 2);
        $riskScore = rand(0,100);
        $transactions[] = [
            'id'               => 3000+$i,
            'order_id'         => 1000+$i,
            'order_number'     => 'SM-2026-'.str_pad(1000+$i,4,'0',STR_PAD_LEFT),
            'billing_first_name'=> explode(' ',$names[$i%count($names)])[0],
            'billing_last_name' => explode(' ',$names[$i%count($names)])[1]??'K.',
            'method_name'      => $m['name'],
            'method_icon'      => $m['icon'],
            'method_color'     => $m['color'],
            'amount'           => $amount,
            'commission'       => $commission,
            'net'              => $amount - $commission,
            'status_label'     => $st['label'],
            'status_color'     => $st['color'],
            'risk_score'       => $riskScore,
            'currency_code'    => 'TRY',
            'created_at'       => date('Y-m-d H:i:s', strtotime('-'.rand(1,72).' hours')),
        ];
    }
}

$kpis = [
    ['l'=>'Toplam Tahsilat','v'=>'₺'.number_format(rand(180000,350000),0,',','.'),'c'=>'#10b981','i'=>'bi-currency-exchange'],
    ['l'=>'Bekleyen Ödeme','v'=>rand(8,20),'c'=>'#f59e0b','i'=>'bi-hourglass-split'],
    ['l'=>'İade Talepleri','v'=>rand(3,9),'c'=>'#f97316','i'=>'bi-arrow-return-left'],
    ['l'=>'Fraud Risk','v'=>rand(1,4),'c'=>'#ef4444','i'=>'bi-shield-exclamation'],
    ['l'=>'Komisyon Maliyeti','v'=>'₺'.number_format(rand(8000,25000),0,',','.'),'c'=>'#8b5cf6','i'=>'bi-percent'],
    ['l'=>'Başarılı Ödeme %','v'=>rand(92,99).'%','c'=>'#3b82f6','i'=>'bi-check-circle'],
    ['l'=>'Red Oranı','v'=>rand(1,5).'%','c'=>'#ef4444','i'=>'bi-x-octagon'],
    ['l'=>'Ort. İşlem','v'=>'₺'.number_format(rand(800,2500),0,',','.'),'c'=>'#c5a880','i'=>'bi-receipt-cutoff'],
];

// Method distribution mock
$methodDist = [
    ['name'=>'Kredi Kartı','pct'=>58,'color'=>'#3b82f6'],
    ['name'=>'Havale/EFT','pct'=>22,'color'=>'#10b981'],
    ['name'=>'Kapıda Ödeme','pct'=>13,'color'=>'#f59e0b'],
    ['name'=>'Cari Hesap','pct'=>5,'color'=>'#8b5cf6'],
    ['name'=>'Diğer','pct'=>2,'color'=>'#64748b'],
];

// Fraud alerts mock
$fraudAlerts = [
    ['order'=>'SM-2026-1042','score'=>92,'reason'=>'Farklı IP adresi, ilk kez alışveriş'],
    ['order'=>'SM-2026-1087','score'=>78,'reason'=>'Aynı kartla 3. sipariş - 1 saat'],
    ['order'=>'SM-2026-1103','score'=>85,'reason'=>'Yüksek tutarlı, yeni adres'],
];
?>
<style>
.oms-section{background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius-lg);padding:22px;margin-bottom:22px}
.oms-section-title{font-size:13px;font-weight:700;color:var(--pim-text);text-transform:uppercase;letter-spacing:.8px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.pay-table th{font-size:11px;font-weight:600;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:.6px;border-bottom:1px solid var(--pim-border)!important;padding:10px 12px}
.pay-table td{font-size:12px;color:var(--pim-text-sm);border-bottom:1px solid rgba(255,255,255,.04)!important;padding:10px 12px;vertical-align:middle}
.pay-table tr:hover td{background:rgba(255,255,255,.02)}
.risk-badge{padding:3px 8px;border-radius:20px;font-size:10px;font-weight:700}
.oms-kpi-mini{background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius);padding:16px;text-align:center;transition:var(--pim-transition)}
.oms-kpi-mini:hover{background:var(--pim-card-hover);transform:translateY(-1px)}
.filter-tab.active{background:rgba(197,168,128,.12)!important;color:#c5a880!important;border-color:rgba(197,168,128,.3)!important}
.fraud-card{padding:12px;border-radius:10px;border:1px solid rgba(239,68,68,.25);background:rgba(239,68,68,.04);margin-bottom:8px}
</style>

<div class="pim-module">
<!-- Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Siparişler'=>url('/admin/orders'),'Ödeme Merkezi'=>'#']) ?>
        <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-credit-card-fill me-2" style="color:#c5a880"></i>Ödeme & Tahsilat Merkezi</h2>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-danger" onclick="openFraudReport()"><i class="bi bi-shield-exclamation me-1"></i>Fraud Raporu</button>
        <a href="<?= url('/admin/orders/export?format=excel') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-download me-1"></i>Export</a>
    </div>
</div>

<!-- KPI Grid -->
<div class="row g-3 mb-4">
    <?php foreach($kpis as $k): ?>
    <div class="col-sm-6 col-md-3 col-lg">
        <div class="oms-kpi-mini" style="border-top:3px solid <?= $k['c'] ?>">
            <div style="color:<?= $k['c'] ?>;font-size:20px;margin-bottom:6px"><i class="bi <?= $k['i'] ?>"></i></div>
            <div style="font-size:18px;font-weight:700;color:var(--pim-text);margin-bottom:2px"><?= $k['v'] ?></div>
            <div style="font-size:10px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:.6px"><?= $k['l'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Main Transaction Table -->
    <div class="col-lg-8">
        <div class="oms-section">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="oms-section-title m-0"><i class="bi bi-list-ul" style="color:#c5a880"></i>İşlem Listesi</div>
                <div class="d-flex gap-1 flex-wrap">
                    <?php foreach(['all'=>'Tümü','completed'=>'Tamamlandı','pending'=>'Bekleyen','failed'=>'Başarısız','refunded'=>'İade'] as $f=>$fl): ?>
                    <a href="?filter=<?= $f ?>" class="btn btn-xs btn-outline-secondary filter-tab <?= $filter===$f?'active':'' ?>" style="font-size:11px;padding:4px 10px"><?= $fl ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table pay-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Sipariş</th>
                            <th>Müşteri</th>
                            <th>Yöntem</th>
                            <th>Tutar</th>
                            <th>Komisyon</th>
                            <th>Net</th>
                            <th>Risk</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($transactions as $t):
                        $riskColor = $t['risk_score'] >= 80 ? '#ef4444' : ($t['risk_score'] >= 50 ? '#f59e0b' : '#10b981');
                        $riskLabel = $t['risk_score'] >= 80 ? 'Yüksek' : ($t['risk_score'] >= 50 ? 'Orta' : 'Düşük');
                    ?>
                    <tr>
                        <td>
                            <a href="<?= url('/admin/orders/show?id='.$t['order_id']) ?>" class="text-decoration-none" style="color:#c5a880;font-weight:600;font-size:12px">
                                <?= htmlspecialchars($t['order_number']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars(($t['billing_first_name']??'').' '.($t['billing_last_name']??'')) ?></td>
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:4px;color:<?= $t['method_color']??'#64748b' ?>">
                                <i class="bi <?= $t['method_icon']??'bi-credit-card' ?>"></i>
                                <span style="font-size:11px"><?= htmlspecialchars($t['method_name']??'') ?></span>
                            </span>
                        </td>
                        <td style="font-weight:600;color:var(--pim-text)">₺<?= number_format($t['amount'],2,',','.') ?></td>
                        <td style="color:#ef4444">₺<?= number_format($t['commission'],2,',','.') ?></td>
                        <td style="color:#10b981;font-weight:600">₺<?= number_format($t['net'],2,',','.') ?></td>
                        <td>
                            <span class="risk-badge" style="background:<?= $riskColor ?>18;color:<?= $riskColor ?>;border:1px solid <?= $riskColor ?>44">
                                <?= $t['risk_score'] ?>% <?= $riskLabel ?>
                            </span>
                        </td>
                        <td>
                            <span class="risk-badge" style="background:<?= $t['status_color']??'#64748b' ?>18;color:<?= $t['status_color']??'#64748b' ?>;border:1px solid <?= $t['status_color']??'#64748b' ?>44">
                                <?= htmlspecialchars($t['status_label']??'') ?>
                            </span>
                        </td>
                        <td style="font-size:10px;color:var(--pim-text-xs)"><?= !empty($t['created_at']) ? date('d.m.Y H:i', strtotime($t['created_at'])) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Payment Method Distribution -->
        <div class="oms-section mb-3">
            <div class="oms-section-title"><i class="bi bi-pie-chart-fill" style="color:#3b82f6"></i>Ödeme Yöntemi Dağılımı</div>
            <?php foreach($methodDist as $m): ?>
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width:10px;height:10px;border-radius:3px;background:<?= $m['color'] ?>;flex-shrink:0"></div>
                <span style="font-size:12px;color:var(--pim-text-sm);flex:1"><?= $m['name'] ?></span>
                <span style="font-size:12px;font-weight:600;color:var(--pim-text)"><?= $m['pct'] ?>%</span>
            </div>
            <div style="height:4px;background:rgba(255,255,255,.06);border-radius:4px;overflow:hidden;margin-bottom:8px">
                <div style="width:<?= $m['pct'] ?>%;height:100%;background:<?= $m['color'] ?>;border-radius:4px"></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Fraud Alerts -->
        <div class="oms-section">
            <div class="oms-section-title"><i class="bi bi-shield-exclamation" style="color:#ef4444"></i>Fraud Uyarıları</div>
            <?php foreach($fraudAlerts as $fa): ?>
            <div class="fraud-card">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:12px;font-weight:600;color:#c5a880"><?= htmlspecialchars($fa['order']) ?></span>
                    <span class="risk-badge" style="background:#ef444418;color:#ef4444;border:1px solid #ef444444"><?= $fa['score'] ?>% Risk</span>
                </div>
                <div style="font-size:11px;color:var(--pim-text-xs);margin-bottom:8px"><?= htmlspecialchars($fa['reason']) ?></div>
                <div class="d-flex gap-2">
                    <button class="btn btn-xs" style="font-size:10px;padding:2px 8px;border:1px solid rgba(16,185,129,.3);color:#10b981;background:rgba(16,185,129,.06);border-radius:6px" onclick="showToast('İşlem onaylandı ve fraud riski temizlendi. ✓', '#10b981'); this.closest('.fraud-card').remove();">Onayla</button>
                    <button class="btn btn-xs" style="font-size:10px;padding:2px 8px;border:1px solid rgba(239,68,68,.3);color:#ef4444;background:rgba(239,68,68,.06);border-radius:6px" onclick="showToast('İşlem engellendi ve kart bloke listesine alındı. ✗', '#ef4444'); this.closest('.fraud-card').remove();">Engelle</button>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="mt-3 pt-3" style="border-top:1px solid var(--pim-border)">
                <div class="oms-section-title" style="margin-bottom:10px"><i class="bi bi-shield-lock" style="color:#8b5cf6"></i>Fraud Kuralları</div>
                <?php $rules = ['IP başına maks. 3 sipariş/gün','Aynı kart 5+ deneme','Yüksek tutarlı yeni üye','VPN/Proxy tespiti']; foreach($rules as $rule): ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:6px;height:6px;border-radius:50%;background:#8b5cf6;flex-shrink:0"></div>
                    <span style="font-size:11px;color:var(--pim-text-xs)"><?= htmlspecialchars($rule) ?></span>
                    <div class="ms-auto" style="width:28px;height:14px;border-radius:10px;background:#10b981;position:relative;cursor:pointer">
                        <div style="width:10px;height:10px;border-radius:50%;background:#fff;position:absolute;right:2px;top:2px"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bekleyen Ödemeler -->
<?php if (!empty($pendingPayment)): ?>
<div class="oms-section">
    <div class="oms-section-title"><i class="bi bi-hourglass-split" style="color:#f59e0b"></i>Bekleyen Ödemeler</div>
    <div class="table-responsive">
        <table class="table pay-table align-middle mb-0">
            <thead><tr><th>Sipariş</th><th>Müşteri</th><th>Tutar</th><th>Durum</th><th>Tarih</th><th class="text-end">İşlem</th></tr></thead>
            <tbody>
            <?php foreach($pendingPayment as $pp): ?>
            <tr>
                <td><a href="<?= url('/admin/orders/show?id='.$pp['id']) ?>" class="text-decoration-none" style="color:#c5a880;font-weight:600"><?= htmlspecialchars($pp['order_number']) ?></a></td>
                <td><?= htmlspecialchars(($pp['billing_first_name']??'').' '.($pp['billing_last_name']??'')) ?></td>
                <td style="font-weight:600;color:var(--pim-text)">₺<?= number_format((float)($pp['grand_total']??0),2,',','.') ?></td>
                <td><span class="risk-badge" style="background:<?= $pp['status_color']??'#f59e0b' ?>18;color:<?= $pp['status_color']??'#f59e0b' ?>;border:1px solid <?= $pp['status_color']??'#f59e0b' ?>44"><?= htmlspecialchars($pp['status_name']??'Bekleyen') ?></span></td>
                <td style="font-size:10px;color:var(--pim-text-xs)"><?= !empty($pp['created_at']) ? date('d.m.Y H:i', strtotime($pp['created_at'])) : '-' ?></td>
                <td class="text-end"><a href="<?= url('/admin/orders/show?id='.$pp['id']) ?>" class="btn btn-xs" style="font-size:11px;padding:3px 10px;border:1px solid rgba(197,168,128,.3);color:#c5a880;background:rgba(197,168,128,.06);border-radius:6px">Görüntüle</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function openFraudReport() {
    alert('Fraud raporu hazırlanıyor... Bu özellik yakında aktif olacak.');
}
function showToast(msg, color) {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:24px;right:24px;background:#0f0c20;border:1px solid ${color};border-radius:12px;padding:12px 20px;color:${color};font-size:13px;font-weight:600;z-index:9999;animation:feedSlideIn .3s ease;box-shadow:0 8px 24px rgba(0,0,0,.5)`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(()=>t.remove(), 3000);
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
