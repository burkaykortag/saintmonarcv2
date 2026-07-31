<?php
use App\Helpers\ComponentHelper;
$title = 'WMS – Depolar | SaintMonarc';
include dirname(dirname(__DIR__)) . '/layouts/header.php';
?>
<div class="pim-module">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Depo Yönetimi'=>'#','Depolar'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-house-gear me-2" style="color:#3b82f6"></i>Çoklu Depo Yönetimi</h2>
        </div>
    </div>

    <!-- Warehouse Grid -->
    <div class="row g-4">
        <?php foreach ($warehouses as $w): 
            $maxCap = (int)$w['max_capacity'];
            $usedCap = (int)$w['used_capacity'];
            $pct = $maxCap > 0 ? round(($usedCap / $maxCap) * 100) : 0;
            
            // Occupancy color class
            $barColor = '#10b981'; // Green
            if ($pct > 95) $barColor = '#ef4444'; // Red
            elseif ($pct > 80) $barColor = '#f59e0b'; // Orange
            elseif ($pct > 50) $barColor = '#fbbf24'; // Yellow
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 text-white rounded-4 p-4" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge" style="background:rgba(59,130,246,0.1);color:#3b82f6;font-size:11px;font-weight:700"><?= htmlspecialchars($w['code']) ?></span>
                    <?php if ($w['is_default']): ?>
                        <span class="badge bg-warning text-dark fw-bold" style="font-size:10px">VARSAYILAN</span>
                    <?php endif; ?>
                </div>

                <h3 class="text-white fw-bold fs-5 mb-2"><?= htmlspecialchars($w['name']) ?></h3>
                
                <div style="font-size:12px;color:var(--pim-text-sm)" class="mb-4">
                    <div class="d-flex align-items-center gap-2 mb-1"><i class="bi bi-geo-alt"></i> <span><?= htmlspecialchars($w['address'] ?? '-') ?></span></div>
                    <div class="d-flex align-items-center gap-2 mb-1"><i class="bi bi-person"></i> <span>Yetkili: <?= htmlspecialchars($w['manager_name'] ?? '-') ?></span></div>
                    <div class="d-flex align-items-center gap-2 mb-1"><i class="bi bi-telephone"></i> <span>Telefon: <?= htmlspecialchars($w['phone'] ?? '-') ?></span></div>
                </div>

                <!-- Progress/Capacity -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:11px">
                        <span class="text-muted">Kapasite Kullanımı</span>
                        <span class="fw-bold text-white"><?= number_format($usedCap) ?> / <?= number_format($maxCap) ?> (<?= $pct ?>%)</span>
                    </div>
                    <div class="progress" style="height:6px;background:rgba(255,255,255,0.05);border-radius:10px">
                        <div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%; background-color: <?= $barColor ?>; border-radius:10px"></div>
                    </div>
                </div>

                <!-- Stats summary -->
                <div class="d-flex justify-content-between p-3 rounded-3" style="background:rgba(255,255,255,0.01);font-size:12px">
                    <div class="text-center">
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase">Ürün Çeşidi</div>
                        <div class="fw-bold mt-1 fs-6 text-warning"><?= number_format($w['total_products'] ?? 0) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase">Toplam Stok</div>
                        <div class="fw-bold mt-1 fs-6 text-info"><?= number_format($w['total_stock'] ?? 0) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase">Raf Gözü</div>
                        <div class="fw-bold mt-1 fs-6 text-success"><?= number_format($w['location_count'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
