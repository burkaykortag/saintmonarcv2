<?php
use App\Helpers\ComponentHelper;

$title = "Arama İstatistikleri - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Arama Motoru' => url('/admin/search'), 'Arama İstatistikleri' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Arama İstatistikleri & Analiz Raporları</h2>
        <a href="<?= url('/admin/search') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Kontrol Paneli</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card p-3 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 16px;">
            <div class="text-muted fs-8">Toplam Yapılan Arama Sayısı</div>
            <h3 class="text-warning font-weight-700 mt-2 m-0"><?= number_format($totalQueries) ?> Arama</h3>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 16px;">
            <div class="text-muted fs-8">Sonuç Bulunamayan Aramalar</div>
            <h3 class="text-danger font-weight-700 mt-2 m-0"><?= number_format($failedQueries) ?> Arama</h3>
        </div>
    </div>
</div>

<div class="row g-4 text-white">
    <!-- SOL TARAF: POPÜLER ARAMALAR -->
    <div class="col-lg-6">
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-graph-up-arrow text-warning me-2"></i>En Çok Aranan Kelimeler (Popüler Aramalar)</h4>
            
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Arama Terimi</th>
                            <th>Arama Sayısı</th>
                            <th>Tıklanma Sayısı</th>
                            <th>Dönüşüm Oranı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($popular)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Henüz arama verisi bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($popular as $p): ?>
                                <tr>
                                    <td><strong class="text-white"><?= htmlspecialchars($p['keyword']) ?></strong></td>
                                    <td><?= number_format($p['search_count']) ?></td>
                                    <td><?= number_format($p['click_count']) ?></td>
                                    <td>
                                        <span class="text-warning">
                                            %<?= $p['search_count'] > 0 ? number_format(($p['click_count'] / $p['search_count']) * 100, 1) : '0.0' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SAĞ TARAF: SON ARAMA LOGLARI -->
    <div class="col-lg-6">
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-list-task text-warning me-2"></i>Son Arama Kayıtları</h4>
            
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Terim</th>
                            <th>Sonuç Sayısı</th>
                            <th>IP Adresi</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Arama kaydı bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><code class="text-warning"><?= htmlspecialchars($log['query']) ?></code></td>
                                    <td>
                                        <span class="badge bg-<?= $log['results_count'] > 0 ? 'success bg-opacity-10 text-success' : 'danger bg-opacity-10 text-danger' ?>">
                                            <?= $log['results_count'] ?> Sonuç
                                        </span>
                                    </td>
                                    <td><small class="text-muted"><?= htmlspecialchars($log['ip_address'] ?? '') ?></small></td>
                                    <td><small class="text-muted"><?= date('d.m.Y H:i', strtotime($log['created_at'])) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
