<?php
use App\Helpers\ComponentHelper;
$title = "AI Satın Alma Asistanı | SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim' => url('/admin'), 'Satın Alma' => url('/admin/purchasing/dashboard'), 'AI Asistan' => '#']) ?>
            <h2 class="mt-2 text-white font-weight-800 fs-3"><i class="bi bi-stars text-warning me-1"></i> AI Satın Alma & Envanter Asistanı</h2>
            <p class="text-muted mb-0 fs-7">Yapay zeka motorunun anlık stok analizi, gecikme tahminleri ve alternatif tedarikçi önerileri.</p>
        </div>
    </div>

    <!-- Suggestions Grid -->
    <div class="row g-3">
        <?php if (empty($suggestions)): ?>
            <div class="col-12 text-center py-5 card bg-dark border-secondary border-opacity-10">
                <i class="bi bi-shield-check text-success fs-1 mb-3"></i>
                <h5 class="text-white font-weight-700">Tüm Süreçler Kararlı Durumda</h5>
                <p class="text-muted fs-8">AI motoru kritik stok riski veya tedarik gecikmesi tespit etmedi.</p>
            </div>
        <?php else: ?>
            <?php foreach ($suggestions as $sug): ?>
                <div class="col-12 col-md-6">
                    <div class="card bg-dark border-secondary border-opacity-10 p-3 h-100 position-relative hover-lift">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <?php if ($sug['type'] === 'low_stock'): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger text-uppercase fs-9"><i class="bi bi-exclamation-triangle"></i> Kritik Stok</span>
                            <?php else: ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning text-uppercase fs-9"><i class="bi bi-clock-history"></i> Teslimat Riski</span>
                            <?php endif; ?>
                        </div>
                        <h5 class="font-weight-800 text-white mb-2 fs-7"><?= htmlspecialchars($sug['title']) ?></h5>
                        <p class="text-muted fs-8 mb-3"><?= htmlspecialchars($sug['description']) ?></p>
                        
                        <?php if ($sug['type'] === 'low_stock'): ?>
                            <div class="border-top border-secondary border-opacity-10 pt-3 mt-auto d-flex justify-content-between align-items-center">
                                <span class="fs-8 text-muted">Önerilen Sipariş Miktarı: <strong><?= $sug['recommended_qty'] ?> Adet</strong></span>
                                <a href="<?= url('/admin/purchasing/wizard') ?>" class="btn btn-sm btn-warning rounded-pill px-3 font-weight-600">Hızlı Sipariş Ver</a>
                            </div>
                        <?php else: ?>
                            <div class="border-top border-secondary border-opacity-10 pt-3 mt-auto text-end">
                                <a href="<?= url('/admin/purchasing/suppliers/show?id=' . $sug['supplier_id']) ?>" class="btn btn-sm btn-outline-warning rounded-pill px-3">Tedarikçi Karnesini İncele</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
