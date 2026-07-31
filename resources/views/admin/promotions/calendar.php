<?php
use App\Helpers\ComponentHelper;

$title = "Kampanya Takvimi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Kampanyalar' => url('/admin/promotions'), 'Kampanya Takvimi' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Kampanya Zaman Takvimi</h2>
        <a href="<?= url('/admin/promotions') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Kampanya Listesi</a>
    </div>
</div>

<div class="card p-4 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-calendar3 text-warning me-2"></i>Aktif ve Planlanmış Kampanya Süreçleri</h4>
    
    <div class="row g-3">
        <?php if (empty($promotions)): ?>
            <div class="col-12 text-center text-muted py-5">Takvime eklenecek kampanya bulunmuyor.</div>
        <?php else: ?>
            <?php foreach ($promotions as $p): ?>
                <div class="col-md-6">
                    <div class="p-3 rounded mb-3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-white"><?= htmlspecialchars($p['name']) ?></strong>
                            <span class="badge text-capitalize bg-opacity-10 bg-<?= $p['status'] === 'active' ? 'success text-success' : 'secondary text-secondary' ?>"><?= $p['status'] ?></span>
                        </div>
                        <p class="fs-8 text-muted mb-2"><?= htmlspecialchars($p['description'] ?? '') ?></p>
                        
                        <div class="row g-1 fs-8 pt-2 border-top border-secondary border-opacity-25 mt-2">
                            <div class="col-6"><i class="bi bi-play-fill text-success"></i> Başlangıç:</div>
                            <div class="col-6 text-end text-white font-weight-600"><?= $p['start_date'] ? date('d.m.Y H:i', strtotime($p['start_date'])) : 'Süresiz / Anında' ?></div>
                            <div class="col-6"><i class="bi bi-stop-fill text-danger"></i> Bitiş:</div>
                            <div class="col-6 text-end text-white font-weight-600"><?= $p['end_date'] ? date('d.m.Y H:i', strtotime($p['end_date'])) : 'Süresiz' ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
