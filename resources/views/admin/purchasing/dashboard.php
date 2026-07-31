<?php
use App\Helpers\ComponentHelper;
$title = "Satın Alma Analitiği & Dashboard | SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
$stats = $analytics['stats'] ?? [];
$monthlyChart = $analytics['monthly_chart'] ?? [];
?>

<div class="container-fluid py-4 text-white">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim' => url('/admin'), 'Satın Alma' => url('/admin/purchasing/dashboard'), 'Dashboard' => '#']) ?>
            <h2 class="mt-2 text-white font-weight-800 fs-3">Satın Alma Analitik Merkezi</h2>
            <p class="text-muted mb-0 fs-7">ERP seviyesinde satın alma akışları, tedarikçi performansı ve bütçe analitiği.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('/admin/purchasing/wizard') ?>" class="btn btn-warning rounded-pill px-4 font-weight-600"><i class="bi bi-magic me-1"></i> Satın Alma Sihirbazı</a>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card bg-dark border-secondary border-opacity-10 p-3 h-100">
                <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Toplam Alış Hacmi</small>
                <h3 class="font-weight-800 text-white mt-2 mb-1">₺<?= number_format($stats['total_purchasing'] ?? 0, 2) ?></h3>
                <span class="text-success fs-9"><i class="bi bi-arrow-up-short"></i> Bu Ay Kararlı</span>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card bg-dark border-secondary border-opacity-10 p-3 h-100">
                <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Bekleyen Siparişler (PO)</small>
                <h3 class="font-weight-800 text-warning mt-2 mb-1"><?= $stats['pending_pos'] ?? 0 ?> Adet</h3>
                <span class="text-muted fs-9">Onay ve teslimat bekleyen</span>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card bg-dark border-secondary border-opacity-10 p-3 h-100">
                <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Bekleyen Sevkiyatlar</small>
                <h3 class="font-weight-800 text-info mt-2 mb-1"><?= $stats['pending_deliveries'] ?? 0 ?> Sipariş</h3>
                <span class="text-info fs-9"><i class="bi bi-truck"></i> Transit durumda</span>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card bg-dark border-secondary border-opacity-10 p-3 h-100">
                <small class="text-muted text-uppercase font-weight-700 fs-9 d-block">Geciken Siparişler</small>
                <h3 class="font-weight-800 text-danger mt-2 mb-1"><?= $stats['delayed_orders'] ?? 0 ?> Sipariş</h3>
                <span class="text-danger fs-9"><i class="bi bi-clock-history"></i> Termin aşıldı</span>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics Grid -->
    <div class="row g-4 mb-4">
        <!-- Purchase Volume Chart -->
        <div class="col-12 col-lg-8">
            <div class="card bg-dark border-secondary border-opacity-10 p-4 h-100">
                <h5 class="text-white font-weight-700 mb-3 fs-6">Aylara Göre Alış Hacmi</h5>
                <div style="height: 300px; position: relative;">
                    <canvas id="purchaseVolumeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Category Distribution & AI Predictions -->
        <div class="col-12 col-lg-4">
            <div class="card bg-dark border-secondary border-opacity-10 p-4 h-100">
                <h5 class="text-white font-weight-700 mb-3 fs-6">Kategori Dağılımı</h5>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($analytics['category_distribution'] ?? [] as $cat): ?>
                        <div>
                            <div class="d-flex justify-content-between fs-8 mb-1">
                                <span><?= $cat['category_name'] ?></span>
                                <span class="font-weight-700"><?= $cat['percentage'] ?>%</span>
                            </div>
                            <div class="progress bg-secondary bg-opacity-25" style="height: 6px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $cat['percentage'] ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <hr class="border-secondary border-opacity-10 my-4">

                <!-- AI Purchasing Prediction -->
                <div class="bg-warning bg-opacity-10 p-3 rounded-3 border border-warning border-opacity-25">
                    <div class="d-flex align-items-center gap-2 mb-2 text-warning">
                        <i class="bi bi-stars"></i>
                        <h6 class="mb-0 font-weight-700 fs-8">AI Satın Alma Tahminleri</h6>
                    </div>
                    <p class="fs-9 text-muted mb-0">
                        Gelecek 30 gün içinde tekstil grubunda <strong>%15 talep artışı</strong> beklenmektedir. Stok seviyelerini korumak için 5 gün içinde sipariş tazelenmesi önerilir.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('purchaseVolumeChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($monthlyChart['labels'] ?? []) ?>,
            datasets: [{
                label: 'Satın Alma Tutarı (₺)',
                data: <?= json_encode($monthlyChart['data'] ?? []) ?>,
                borderColor: '#c5a880',
                backgroundColor: 'rgba(197, 168, 128, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)'
                    },
                    ticks: {
                        color: '#a3a3a3'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)'
                    },
                    ticks: {
                        color: '#a3a3a3'
                    }
                }
            }
        }
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
