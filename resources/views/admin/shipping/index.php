<?php
use App\Helpers\ComponentHelper;

$title = "Lojistik & Kargo Yönetimi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<style>
.section-card {
    background: rgba(255,255,255,0.01);
    border: 1px solid var(--sm-border) !important;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
}
.stat-mini-box {
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--sm-border);
    border-radius: 12px;
    padding: 16px;
    text-align: center;
}
</style>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Lojistik & Kargo' => '#', 'Dashboard' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Kurumsal Lojistik & Sevkiyat Kontrol Paneli</h2>
        <div class="d-flex gap-2">
            <a href="<?= url('/admin/shipping/companies') ?>" class="btn btn-outline-warning border-0"><i class="bi bi-building me-2"></i>Firmalar</a>
            <a href="<?= url('/admin/shipping/shipments') ?>" class="btn btn-outline-info border-0"><i class="bi bi-box me-2"></i>Gönderiler</a>
            <a href="<?= url('/admin/shipping/returns') ?>" class="btn btn-outline-danger border-0"><i class="bi bi-arrow-counterclockwise me-2"></i>İade Yönetimi</a>
            <a href="<?= url('/admin/shipping/rules') ?>" class="btn btn-outline-success border-0"><i class="bi bi-sliders me-2"></i>Kargo Kuralları</a>
            <a href="<?= url('/admin/shipping/reports') ?>" class="btn btn-outline-warning border-0"><i class="bi bi-bar-chart me-2"></i>Lojistik Raporları</a>
        </div>
    </div>
</div>

<div class="row g-4 mb-4 text-white">
    <div class="col-md-3">
        <div class="stat-mini-box h-100 d-flex flex-column justify-content-center">
            <div class="text-muted fs-8">Toplam Sevkiyat</div>
            <h3 class="text-warning font-weight-700 mt-2 mb-0"><?= number_format($totalShipments) ?> Gönderi</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini-box h-100 d-flex flex-column justify-content-center">
            <div class="text-muted fs-8">Teslim Edilenler</div>
            <h3 class="text-success font-weight-700 mt-2 mb-0"><?= number_format($delivered) ?> Teslim</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini-box h-100 d-flex flex-column justify-content-center">
            <div class="text-muted fs-8">Yolda / Şubede Bekleyen</div>
            <h3 class="text-info font-weight-700 mt-2 mb-0"><?= number_format($pending) ?> Kargo</h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini-box h-100 d-flex flex-column justify-content-center">
            <div class="text-muted fs-8">İade Edilen Gönderiler</div>
            <h3 class="text-danger font-weight-700 mt-2 mb-0"><?= number_format($returned) ?> İade</h3>
        </div>
    </div>
</div>

<div class="row g-4 text-white">
    <!-- AKTİF GÖNDERİLER VE TAKİP DURUMLARI -->
    <div class="col-lg-7">
        <div class="section-card">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-box-seam text-warning me-2"></i>Son Sevkiyat Hareketleri</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Takip No</th>
                            <th>Firma</th>
                            <th>Desi</th>
                            <th>Maliyet</th>
                            <th class="text-end">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($packages)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Aktif sevkiyat kaydı bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($packages as $pkg): ?>
                                <tr>
                                    <td><code class="text-warning"><?= htmlspecialchars($pkg['tracking_number'] ?? '-') ?></code></td>
                                    <td><?= htmlspecialchars($pkg['company_name']) ?></td>
                                    <td><?= number_format((float)$pkg['desi'], 2) ?> Desi</td>
                                    <td>₺<?= number_format((float)$pkg['shipping_cost'], 2) ?></td>
                                    <td class="text-end">
                                        <span class="badge text-capitalize bg-opacity-10 bg-<?= $pkg['status'] === 'delivered' ? 'success text-success' : 'info text-info' ?>">
                                            <?= htmlspecialchars($pkg['status']) ?>
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

    <!-- HESAP VERİLEN KARGO FİRMALARI -->
    <div class="col-lg-5">
        <div class="section-card">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-building text-warning me-2"></i>Sözleşmeli Kargo Firmaları</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Firma Adı</th>
                            <th>Kod</th>
                            <th class="text-end">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($companies)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Kargo firması tanımlanmamış.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($companies as $comp): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($comp['name']) ?></strong></td>
                                    <td><code class="text-muted"><?= htmlspecialchars($comp['code']) ?></code></td>
                                    <td class="text-end">
                                        <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
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
