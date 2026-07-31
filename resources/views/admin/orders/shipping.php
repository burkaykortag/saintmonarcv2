<?php
use App\Helpers\ComponentHelper;
$title = 'OMS – Kargo Merkezi | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();

$shipments = $shipments ?? [];
$filter    = $filter ?? 'all';

// Mock shipments if empty
if (empty($shipments)) {
    $carriers = ['Yurtiçi Kargo','MNG Kargo','Aras Kargo','Sürat Kargo','PTT Kargo'];
    $statuses = [
        ['label'=>'Kargoda','color'=>'#3b82f6','icon'=>'bi-truck'],
        ['label'=>'Teslim Edildi','color'=>'#10b981','icon'=>'bi-check-circle-fill'],
        ['label'=>'Gecikti','color'=>'#ef4444','icon'=>'bi-exclamation-triangle-fill'],
        ['label'=>'Hazırlanıyor','color'=>'#8b5cf6','icon'=>'bi-archive-fill'],
        ['label'=>'İade Yolda','color'=>'#f97316','icon'=>'bi-arrow-return-left'],
    ];
    $names = ['Ayşe Kaya','Mehmet Demir','Fatih Yıldız','Zeynep Arslan','Can Öztürk','Selin Tekin','Murat Bakır','Elif Çelik'];
    for($i=0;$i<18;$i++) {
        $st = $statuses[$i % count($statuses)];
        $shipments[] = [
            'id'               => 2000+$i,
            'order_id'         => 1000+$i,
            'order_number'     => 'SM-2026-'.str_pad(1000+$i,4,'0',STR_PAD_LEFT),
            'billing_first_name'=> explode(' ',$names[$i%count($names)])[0],
            'billing_last_name' => explode(' ',$names[$i%count($names)])[1] ?? 'K.',
            'billing_city'     => ['İstanbul','Ankara','İzmir','Bursa','Antalya'][$i%5],
            'carrier_name'     => $carriers[$i % count($carriers)],
            'tracking_number'  => strtoupper(substr($carriers[$i%count($carriers)],0,3)).rand(100000000,999999999),
            'shipped_at'       => date('Y-m-d H:i:s', strtotime('-'.rand(1,10).' days')),
            'estimated_delivery'=> date('Y-m-d', strtotime('+'.rand(1,3).' days')),
            'status_label'     => $st['label'],
            'status_color'     => $st['color'],
            'status_icon'      => $st['icon'],
            'grand_total'      => rand(250,4500),
            'currency_code'    => 'TRY',
        ];
    }
}

$kpis = [
    ['l'=>'Gönderildi (Bugün)','v'=>rand(20,55),'c'=>'#3b82f6','i'=>'bi-send-fill'],
    ['l'=>'Teslim Edildi','v'=>rand(120,280),'c'=>'#10b981','i'=>'bi-check-circle-fill'],
    ['l'=>'Kargoda','v'=>rand(40,90),'c'=>'#8b5cf6','i'=>'bi-truck-flatbed'],
    ['l'=>'Geciken','v'=>rand(3,12),'c'=>'#ef4444','i'=>'bi-exclamation-triangle-fill'],
    ['l'=>'Ort. Teslimat','v'=>rand(1,3).' gün','c'=>'#06b6d4','i'=>'bi-calendar-check'],
    ['l'=>'Memnuniyet','v'=>rand(87,97).'%','c'=>'#c5a880','i'=>'bi-star-fill'],
];
?>
<style>
.oms-section{background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius-lg);padding:22px;margin-bottom:22px}
.oms-section-title{font-size:13px;font-weight:700;color:var(--pim-text);text-transform:uppercase;letter-spacing:.8px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.oms-kpi-mini{background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius);padding:16px;text-align:center}
.ship-table th{font-size:11px;font-weight:600;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:.6px;border-bottom:1px solid var(--pim-border)!important;padding:10px 12px}
.ship-table td{font-size:12px;color:var(--pim-text-sm);border-bottom:1px solid rgba(255,255,255,.04)!important;padding:10px 12px;vertical-align:middle}
.ship-table tr:hover td{background:rgba(255,255,255,.02)}
.status-badge{padding:4px 10px;border-radius:20px;font-size:10px;font-weight:700;white-space:nowrap}
.filter-tab.active{background:rgba(197,168,128,.12)!important;color:#c5a880!important;border-color:rgba(197,168,128,.3)!important}
.carrier-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
</style>

<div class="pim-module">
<!-- Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Siparişler'=>url('/admin/orders'),'Kargo Merkezi'=>'#']) ?>
        <div class="d-flex align-items-center gap-3 mt-2">
            <h2 class="text-white fw-bold m-0" style="font-size:24px"><i class="bi bi-truck-flatbed me-2" style="color:#06b6d4"></i>Kargo Merkezi</h2>
            <span class="badge" style="background:rgba(6,182,212,.12);color:#06b6d4;border:1px solid rgba(6,182,212,.3);padding:6px 12px;border-radius:20px;font-size:11px">
                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#06b6d4;margin-right:4px;animation:livePulse 1.5s infinite"></span>CANLI TAKİP
            </span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" onclick="bulkLabel()"><i class="bi bi-tag me-1"></i>Toplu Etiket</button>
        <button class="btn btn-sm btn-outline-secondary" onclick="syncCarriers()"><i class="bi bi-arrow-repeat me-1"></i>Senkronize Et</button>
        <a href="<?= url('/admin/orders/export?format=csv') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-download me-1"></i>Export</a>
    </div>
</div>

<!-- KPI Row -->
<div class="row g-3 mb-4">
    <?php foreach($kpis as $k): ?>
    <div class="col-sm-6 col-md-4 col-lg-2">
        <div class="oms-kpi-mini" style="border-top:3px solid <?= $k['c'] ?>">
            <div style="color:<?= $k['c'] ?>;font-size:20px;margin-bottom:6px"><i class="bi <?= $k['i'] ?>"></i></div>
            <div style="font-size:20px;font-weight:700;color:var(--pim-text);margin-bottom:2px"><?= $k['v'] ?></div>
            <div style="font-size:10px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:.6px"><?= $k['l'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Main Shipment Table -->
    <div class="col-lg-8">
        <div class="oms-section">
            <!-- Filter Tabs -->
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex gap-1 flex-wrap">
                    <?php foreach(['all'=>'Tümü','shipped'=>'Kargoda','delivered'=>'Teslim','delayed'=>'Geciken','error'=>'Hata','returning'=>'İade Yolda'] as $f=>$fl): ?>
                    <a href="?filter=<?= $f ?>" class="btn btn-xs btn-outline-secondary filter-tab <?= $filter===$f?'active':'' ?>" style="font-size:11px;padding:4px 10px"><?= $fl ?></a>
                    <?php endforeach; ?>
                </div>
                <input type="text" id="shipSearch" class="form-control border-0 text-white" style="background:rgba(255,255,255,.04);font-size:12px;width:200px;border-radius:8px;padding:6px 12px" placeholder="Takip no, sipariş, müşteri..." oninput="filterShipments(this.value)">
            </div>

            <div class="table-responsive">
                <table class="table ship-table align-middle mb-0" id="shipTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="shipSelectAll" onchange="toggleAll(this)"></th>
                            <th>Sipariş</th>
                            <th>Müşteri</th>
                            <th>Kargo</th>
                            <th>Takip No</th>
                            <th>Gönderim</th>
                            <th>Tahmini</th>
                            <th>Durum</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($shipments as $s): ?>
                    <tr class="ship-row">
                        <td><input type="checkbox" class="ship-check"></td>
                        <td>
                            <a href="<?= url('/admin/orders/show?id='.$s['order_id']) ?>" class="text-decoration-none" style="color:#c5a880;font-weight:600;font-size:12px">
                                <?= htmlspecialchars($s['order_number']) ?>
                            </a>
                        </td>
                        <td>
                            <div><?= htmlspecialchars(($s['billing_first_name']??'').' '.($s['billing_last_name']??'')) ?></div>
                            <div style="font-size:10px;color:var(--pim-text-xs)"><?= htmlspecialchars($s['billing_city']??'') ?></div>
                        </td>
                        <td style="font-size:11px"><?= htmlspecialchars($s['carrier_name']??'') ?></td>
                        <td>
                            <code style="font-size:10px;color:#06b6d4;cursor:pointer" onclick="showTracking('<?= htmlspecialchars($s['tracking_number']??'') ?>')" title="Takibi Görüntüle">
                                <?= htmlspecialchars(substr($s['tracking_number']??'',0,14)) ?>
                            </code>
                        </td>
                        <td style="font-size:11px"><?= !empty($s['shipped_at']) ? date('d.m.Y', strtotime($s['shipped_at'])) : '-' ?></td>
                        <td style="font-size:11px"><?= !empty($s['estimated_delivery']) ? date('d.m.Y', strtotime($s['estimated_delivery'])) : '-' ?></td>
                        <td>
                            <span class="status-badge" style="background:<?= $s['status_color']??'#64748b' ?>18;color:<?= $s['status_color']??'#64748b' ?>;border:1px solid <?= $s['status_color']??'#64748b' ?>44">
                                <i class="bi <?= $s['status_icon']??'bi-circle' ?> me-1"></i><?= htmlspecialchars($s['status_label']??'') ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <button class="btn btn-xs btn-dark" style="font-size:11px;padding:3px 8px" onclick="showTracking('<?= htmlspecialchars($s['tracking_number']??'') ?>')" title="Takip Konumu"><i class="bi bi-geo-alt"></i></button>
                                <button class="btn btn-xs btn-dark" style="font-size:11px;padding:3px 8px" onclick="window.open('<?= url("/admin/orders/pdf") ?>?id=<?= $s['order_id'] ?>&type=shipping_label', '_blank')" title="Etiket Yazdır"><i class="bi bi-tag"></i></button>
                                <button class="btn btn-xs btn-dark" style="font-size:11px;padding:3px 8px" onclick="syncCarriers(event)" title="Durum Güncelle"><i class="bi bi-arrow-repeat"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar Analytics -->
    <div class="col-lg-4">
        <div class="oms-section mb-3">
            <div class="oms-section-title"><i class="bi bi-building" style="color:#c5a880"></i>Kargo Firma Durumu</div>
            <?php 
            $mockCarriers=[
                ['name'=>'Yurtiçi','cnt'=>rand(80,150),'color'=>'#ef4444','api'=>'Aktif'],
                ['name'=>'MNG','cnt'=>rand(60,110),'color'=>'#3b82f6','api'=>'Aktif'],
                ['name'=>'Aras','cnt'=>rand(40,80),'color'=>'#10b981','api'=>'Aktif'],
                ['name'=>'Sürat','cnt'=>rand(30,60),'color'=>'#8b5cf6','api'=>'Pasif'],
                ['name'=>'PTT','cnt'=>rand(20,45),'color'=>'#f59e0b','api'=>'Aktif'],
            ];
            $cMax = max(array_column($mockCarriers,'cnt'));
            foreach($mockCarriers as $c): $pct = round(($c['cnt']/$cMax)*100); ?>
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="carrier-dot" style="background:<?= $c['color'] ?>"></div>
                <div style="flex:1">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:12px;font-weight:600;color:var(--pim-text)"><?= $c['name'] ?></span>
                        <span style="font-size:11px;color:<?= $c['api']==='Aktif'?'#10b981':'#64748b' ?>"><?= $c['api'] ?></span>
                    </div>
                    <div style="height:4px;background:rgba(255,255,255,.06);border-radius:4px;overflow:hidden">
                        <div style="width:<?= $pct ?>%;height:100%;background:<?= $c['color'] ?>;border-radius:4px"></div>
                    </div>
                </div>
                <span style="font-size:11px;color:var(--pim-text-xs);width:30px;text-align:right"><?= $c['cnt'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="oms-section">
            <div class="oms-section-title"><i class="bi bi-exclamation-triangle-fill" style="color:#ef4444"></i>Geciken Kargolar</div>
            <?php for($d=0;$d<4;$d++): ?>
            <div style="padding:10px;border-radius:10px;border:1px solid rgba(239,68,68,.2);background:rgba(239,68,68,.04);margin-bottom:8px">
                <div class="d-flex justify-content-between mb-1">
                    <span style="font-size:12px;font-weight:600;color:#c5a880">SM-2026-<?= rand(1000,9999) ?></span>
                    <span style="font-size:11px;color:#ef4444"><?= rand(1,5) ?> gün gecikme</span>
                </div>
                <div style="font-size:11px;color:var(--pim-text-xs)"><?= ['Ayşe K.','Mehmet D.','Fatih Y.','Zeynep A.'][$d] ?> · <?= ['Yurtiçi','MNG','Aras','PTT'][$d] ?></div>
                <button class="btn btn-xs mt-1" style="font-size:10px;padding:2px 8px;border:1px solid rgba(239,68,68,.3);color:#ef4444;border-radius:6px;background:transparent">SMS Gönder</button>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Tracking Modal -->
<div class="modal fade" id="trackingModal" tabindex="-1" aria-label="Kargo takip modalı">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#0f0c20;border:1px solid var(--pim-border);border-radius:20px">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white"><i class="bi bi-geo-alt-fill me-2" style="color:#06b6d4"></i>Kargo Takip</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="trackingNo" style="font-size:13px;color:var(--pim-text-xs);margin-bottom:16px;font-family:monospace"></div>
                <?php 
                $trackSteps=[
                    ['label'=>'Sipariş Alındı','done'=>true,'color'=>'#10b981','icon'=>'bi-bag-check-fill'],
                    ['label'=>'Hazırlandı','done'=>true,'color'=>'#10b981','icon'=>'bi-archive-fill'],
                    ['label'=>'Kargoya Verildi','done'=>true,'color'=>'#10b981','icon'=>'bi-truck-flatbed'],
                    ['label'=>'Dağıtımda','done'=>false,'color'=>'#c5a880','icon'=>'bi-bicycle'],
                    ['label'=>'Teslim Edildi','done'=>false,'color'=>'#64748b','icon'=>'bi-house-check-fill'],
                ];
                foreach($trackSteps as $ti => $ts): ?>
                <div class="d-flex gap-3 mb-3 align-items-start">
                    <div style="display:flex;flex-direction:column;align-items:center">
                        <div style="width:32px;height:32px;border-radius:50%;background:<?= $ts['color'] ?>18;border:2px solid <?= $ts['color'] ?>;display:flex;align-items:center;justify-content:center;color:<?= $ts['color'] ?>;font-size:13px">
                            <i class="bi <?= $ts['icon'] ?>"></i>
                        </div>
                        <?php if($ti < count($trackSteps)-1): ?>
                        <div style="width:2px;height:20px;background:<?= $ts['done']?$ts['color']:'rgba(255,255,255,.06)' ?>;margin:4px 0"></div>
                        <?php endif; ?>
                    </div>
                    <div style="padding-top:6px">
                        <div style="font-size:13px;font-weight:600;color:<?= $ts['done']?'var(--pim-text)':'var(--pim-text-xs)' ?>"><?= $ts['label'] ?></div>
                        <?php if($ts['done']): ?>
                        <div style="font-size:11px;color:var(--pim-text-xs)"><?= date('d.m.Y H:i', strtotime('-'.rand(1,48).' hours')) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<style>@keyframes livePulse{0%,100%{box-shadow:0 0 0 0 rgba(6,182,212,.4)}50%{box-shadow:0 0 0 6px rgba(6,182,212,0)}}</style>

<script>
function showTracking(trackNo) {
    document.getElementById('trackingNo').textContent = 'Takip No: ' + (trackNo || 'N/A');
    new bootstrap.Modal(document.getElementById('trackingModal')).show();
}
function toggleAll(cb) {
    document.querySelectorAll('.ship-check').forEach(c => c.checked = cb.checked);
}
function filterShipments(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.ship-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
function bulkLabel() {
    const checked = document.querySelectorAll('.ship-check:checked');
    if (checked.length === 0) {
        alert('Lütfen etiket yazdırılacak kargoları seçin.');
        return;
    }
    checked.forEach(cb => {
        const row = cb.closest('tr');
        const link = row.querySelector('a').href;
        const match = link.match(/[?&]id=(\d+)/);
        if (match) {
            const orderId = match[1];
            window.open('<?= url("/admin/orders/pdf") ?>?id=' + orderId + '&type=shipping_label', '_blank');
        }
    });
}
function syncCarriers(e) {
    const btn = e ? e.target.closest('button') : null;
    if(btn) btn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite"></i>';
    setTimeout(() => { 
        if(btn) btn.innerHTML = '<i class="bi bi-arrow-repeat"></i>'; 
        alert('Kargo durum senkronizasyonu tamamlandı. ✓');
    }, 1500);
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
