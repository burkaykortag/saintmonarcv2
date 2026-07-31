<?php
use App\Helpers\ComponentHelper;

$title = "Lojistik Raporları - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Lojistik & Kargo' => url('/admin/shipping'), 'Lojistik Raporları' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Kargo Taşıyıcı Performans & Teslimat Süreleri</h2>
        <a href="<?= url('/admin/shipping') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Lojistik Paneli</a>
    </div>
</div>

<div class="row g-4 text-white">
    <!-- FİRMA BAZLI PERFORMANS TABLOSU -->
    <div class="col-12">
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-bar-chart-fill text-warning me-2"></i>Kargo Taşıyıcı Başarı Performansı</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Kargo Firması</th>
                            <th>Toplam Gönderi Adedi</th>
                            <th>Başarıyla Teslim Edilen</th>
                            <th class="text-end">Teslimat Başarı Oranı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($performance)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Performans datası henüz oluşmamış.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($performance as $perf): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($perf['company_name']) ?></strong></td>
                                    <td><?= number_format((float)$perf['total']) ?> Gönderi</td>
                                    <td><?= number_format((float)$perf['delivered']) ?> Adet</td>
                                    <td class="text-end font-weight-600 text-warning">
                                        <?php 
                                        $ratio = $perf['total'] > 0 ? ($perf['delivered'] / $perf['total']) * 100 : 100;
                                        echo number_format($ratio, 1) . '%';
                                        ?>
                                    </td>
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
