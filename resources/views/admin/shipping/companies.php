<?php
use App\Helpers\ComponentHelper;

$title = "Kargo Firmaları - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Lojistik & Kargo' => url('/admin/shipping'), 'Kargo Firmaları' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Kargo Taşıyıcı & Entegrasyon Firmaları</h2>
        <a href="<?= url('/admin/shipping') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Lojistik Paneli</a>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>

<div class="row g-4 text-white">
    <!-- FİRMALAR LİSTESİ -->
    <div class="col-lg-8">
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-list text-warning me-2"></i>Taşıyıcılar</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Firma Adı</th>
                            <th>Firma Kodu</th>
                            <th>Vergi Numarası</th>
                            <th>Durum</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($companies)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Kayıtlı kargo firması bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($companies as $comp): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($comp['name']) ?></strong></td>
                                    <td><code class="text-warning"><?= htmlspecialchars($comp['code']) ?></code></td>
                                    <td><?= htmlspecialchars($comp['tax_number'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-opacity-10 bg-<?= $comp['is_active'] ? 'success text-success' : 'danger text-danger' ?>">
                                            <?= $comp['is_active'] ? 'Aktif' : 'Pasif' ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= url('/admin/shipping/companies/edit?id=' . $comp['id']) ?>" class="btn btn-warning btn-sm text-dark px-3 font-weight-600"><i class="bi bi-gear me-1"></i>Bağlantı & Düzenle</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- FİRMA EKLEME FORMU -->
    <div class="col-lg-4">
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6">Yeni Firma Ekle</h4>
            <form action="<?= url('/admin/shipping/companies/create') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Firma Adı</label>
                    <input type="text" name="name" required class="search-input w-100 text-white" placeholder="Örn: Yurtiçi Kargo">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Firma Kodu</label>
                    <input type="text" name="code" required class="search-input w-100 text-white" placeholder="yurtici">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Vergi Numarası</label>
                    <input type="text" name="tax_number" class="search-input w-100 text-white" placeholder="1234567890">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Durum</label>
                    <select name="is_active" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <option value="1">Aktif</option>
                        <option value="0">Pasif</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 w-100 py-3 font-weight-700 mt-2">Firmayı Kaydet</button>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
