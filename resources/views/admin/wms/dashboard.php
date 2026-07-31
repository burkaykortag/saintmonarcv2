<?php
use App\Helpers\ComponentHelper;
$title = 'WMS – Depo Yönetimi Dashboard | SaintMonarc';
include dirname(dirname(__DIR__)) . '/layouts/header.php';
?>
<div class="pim-module">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Depo Yönetimi'=>'#','Dashboard'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-houses-fill me-2" style="color:#c5a880"></i>Depo Yönetim Paneli (WMS)</h2>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="text-muted fs-7">Aktif Depo:</span>
            <select class="form-select border-0 text-white" style="background:rgba(255,255,255,0.05);font-size:12px;width:200px" 
                    onchange="location.href='?warehouse_id='+this.value">
                <?php foreach ($warehouses as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $selected_warehouse_id === (int)$w['id'] ? 'selected' : '' ?> style="background:#0f0c20">
                        <?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars($w['code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: Toplam Ürün -->
        <div class="col-md-3 col-sm-6">
            <div class="p-3 rounded-4 border-0 text-white" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Toplam Ürün Çeşidi</span>
                        <h3 class="fw-bold mt-1 mb-0" style="font-size:22px"><?= number_format($stats['total_products']) ?></h3>
                    </div>
                    <div style="background:rgba(197,168,128,0.1);padding:6px;border-radius:10px;color:#c5a880"><i class="bi bi-box-seam fs-5"></i></div>
                </div>
            </div>
        </div>

        <!-- KPI 2: Toplam Stok -->
        <div class="col-md-3 col-sm-6">
            <div class="p-3 rounded-4 border-0 text-white" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Mevcut Toplam Stok</span>
                        <h3 class="fw-bold mt-1 mb-0" style="font-size:22px"><?= number_format($stats['total_stock']) ?></h3>
                    </div>
                    <div style="background:rgba(59,130,246,0.1);padding:6px;border-radius:10px;color:#3b82f6"><i class="bi bi-boxes fs-5"></i></div>
                </div>
            </div>
        </div>

        <!-- KPI 3: Kritik Stok -->
        <div class="col-md-3 col-sm-6">
            <div class="p-3 rounded-4 border-0 text-white" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Kritik Stok SKU</span>
                        <h3 class="fw-bold mt-1 mb-0 text-danger" style="font-size:22px"><?= number_format($stats['critical_stock']) ?></h3>
                    </div>
                    <div style="background:rgba(239,68,68,0.1);padding:6px;border-radius:10px;color:#ef4444"><i class="bi bi-exclamation-triangle fs-5"></i></div>
                </div>
            </div>
        </div>

        <!-- KPI 4: Lokasyon Doluluğu -->
        <div class="col-md-3 col-sm-6">
            <div class="p-3 rounded-4 border-0 text-white" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Dolu / Boş Raf Gözü</span>
                        <h3 class="fw-bold mt-1 mb-0" style="font-size:22px"><?= $stats['occupied_locations'] ?> <span class="text-muted fs-7">/ <?= $stats['empty_locations'] ?></span></h3>
                    </div>
                    <div style="background:rgba(16,185,129,0.1);padding:6px;border-radius:10px;color:#10b981"><i class="bi bi-grid-3x3-gap fs-5"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Stats & Backlog Charts -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="p-4 rounded-4" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important">
                <h4 class="text-white fs-6 mb-4"><i class="bi bi-bar-chart-line me-2 text-warning"></i>Depo Günlük Giriş / Çıkış Hacmi</h4>
                <div style="height:250px">
                    <canvas id="dailyInOutChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="p-4 rounded-4" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important">
                <h4 class="text-white fs-6 mb-4"><i class="bi bi-hourglass-split me-2 text-warning"></i>Depo İş Kuyruğu (Backlog)</h4>
                <div style="height:250px" class="d-flex align-items-center justify-content-center">
                    <canvas id="backlogChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Extra Info Box -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="p-4 rounded-4" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important">
                <h4 class="text-white fs-6 mb-3"><i class="bi bi-arrow-left-right me-2 text-warning"></i>Depo Aktiviteleri Summary</h4>
                <table class="table table-borderless text-white mb-0" style="font-size:13px">
                    <tbody>
                        <tr>
                            <td class="text-muted ps-0">Bugünkü Transfer Talepleri</td>
                            <td class="text-end fw-bold"><?= $stats['today_transfers'] ?> adet</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Bugünkü Mal Girişi (Inbound)</td>
                            <td class="text-end fw-bold" style="color:#10b981">+<?= $stats['daily_in'] ?> adet</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0">Bugünkü Mal Çıkışı (Outbound)</td>
                            <td class="text-end fw-bold" style="color:#ef4444">-<?= $stats['daily_out'] ?> adet</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-4 rounded-4 d-flex flex-column justify-content-between" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important;height:100%">
                <div>
                    <h4 class="text-white fs-6 mb-2"><i class="bi bi-robot me-2 text-warning"></i>AI WMS Asistan Tavsiyesi</h4>
                    <p style="color:var(--pim-text-xs);margin:0">Depo giriş/çıkış verilerine göre C-3 koridorundaki A sınıfı ürünlerin toplama süreleri uzuyor. Bu raflardaki ürünlerin mal kabul kapısına taşınması önerilir.</p>
                </div>
                <a href="<?= url('/admin/wms/ai-assistant') ?>" class="btn btn-sm btn-outline-warning mt-3 align-self-start"><i class="bi bi-stars me-1"></i>Tüm Tavsiyeleri Gör</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart 1: Daily In/Out
const ctxInOut = document.getElementById('dailyInOutChart').getContext('2d');
new Chart(ctxInOut, {
    type: 'bar',
    data: {
        labels: ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Bugün'],
        datasets: [
            {
                label: 'Mal Girişi (Inbound)',
                data: [120, 190, 300, 50, 200, 30, <?= $stats['daily_in'] ?>],
                backgroundColor: 'rgba(16, 185, 129, 0.6)',
                borderColor: '#10b981',
                borderWidth: 1
            },
            {
                label: 'Mal Çıkışı (Outbound)',
                data: [80, 150, 220, 180, 130, 45, <?= $stats['daily_out'] ?>],
                backgroundColor: 'rgba(239, 68, 68, 0.6)',
                borderColor: '#ef4444',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: '#a0aec0' } }
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: '#a0aec0' } },
            y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#a0aec0' } }
        }
    }
});

// Chart 2: Backlog
const ctxBacklog = document.getElementById('backlogChart').getContext('2d');
new Chart(ctxBacklog, {
    type: 'doughnut',
    data: {
        labels: ['Bekleyen Toplama', 'Bekleyen Paketleme'],
        datasets: [{
            data: [<?= $stats['pending_picking'] ?>, <?= $stats['pending_shipping'] ?>],
            backgroundColor: ['#fbbf24', '#8b5cf6'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { color: '#a0aec0' } }
        }
    }
});
</script>
<?php include dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
