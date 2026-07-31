<?php
use App\Helpers\ComponentHelper;

$title = "Kampanya Raporları - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Kampanyalar' => url('/admin/promotions'), 'Raporlar' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Kampanya Raporları</h2>
        <a href="<?= url('/admin/promotions') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Kampanya Listesi</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 16px;">
            <div class="text-muted fs-8">Toplam Kampanya Maliyeti (İndirimler)</div>
            <h3 class="text-danger font-weight-700 mt-2 m-0"><?= number_format($totalDiscount, 2) ?> TRY</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 16px;">
            <div class="text-muted fs-8">Sağlanan Toplam Ciro</div>
            <h3 class="text-success font-weight-700 mt-2 m-0"><?= number_format($totalRevenue, 2) ?> TRY</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 16px;">
            <div class="text-muted fs-8">Ortalama ROI (Yatırım Getirisi Oranı)</div>
            <h3 class="text-warning font-weight-700 mt-2 m-0">%<?= number_format($avgRoi, 2) ?></h3>
        </div>
    </div>
</div>

<div class="card p-4 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-graph-up-arrow text-warning me-2"></i>Kampanya Başına Performans Raporu</h4>
    
    <div class="table-responsive">
        <table class="table align-middle text-white table-hover">
            <thead>
                <tr class="text-muted fs-7 border-bottom border-secondary">
                    <th>Kampanya</th>
                    <th>Tür</th>
                    <th>Görüntülenme</th>
                    <th>Tıklanma</th>
                    <th>Dönüşüm</th>
                    <th>Kullanılan İndirim</th>
                    <th>ROI</th>
                </tr>
            </thead>
            <tbody class="fs-7">
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Henüz performans verisi bulunmuyor.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['type']) ?></span></td>
                            <td><?= (int)$r['views'] ?></td>
                            <td><?= (int)$r['clicks'] ?></td>
                            <td><span class="text-success font-weight-600"><?= (int)$r['conversions'] ?> Sipariş</span></td>
                            <td class="text-danger font-weight-600"><?= number_format((float)($r['total_discount_given'] ?? 0.0), 2) ?> TRY</td>
                            <td class="text-warning"><strong>%<?= number_format((float)($r['roi'] ?? 0.0), 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
