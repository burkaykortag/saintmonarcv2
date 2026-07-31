<?php
use App\Helpers\ComponentHelper;

$title = "Giderler - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Finans & Muhasebe' => url('/admin/finance'), 'Gider Yönetimi' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Masraf & Gider Yönetimi</h2>
        <a href="<?= url('/admin/finance') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Finans Paneli</a>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>

<div class="row g-4 text-white">
    <!-- GİDER LİSTESİ -->
    <div class="col-lg-8">
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-list-stars text-warning me-2"></i>Masraf Kayıtları</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Kategori</th>
                            <th>Açıklama</th>
                            <th>Tarih</th>
                            <th>KDV Tutarı</th>
                            <th class="text-end">Toplam Tutar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Kayıtlı gider bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $exp): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($exp['category_name']) ?></span></td>
                                    <td><?= htmlspecialchars($exp['description'] ?? '-') ?></td>
                                    <td><?= date('d.m.Y', strtotime($exp['expense_date'])) ?></td>
                                    <td>₺<?= number_format((float)$exp['tax_amount'], 2) ?></td>
                                    <td class="text-end font-weight-600 text-danger">₺<?= number_format((float)$exp['amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- YENİ GİDER FORMU -->
    <div class="col-lg-4">
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6">Yeni Masraf Girişi</h4>
            <form action="<?= url('/admin/expenses/create') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Gider Kategorisi</label>
                    <select name="category_id" required class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Tutar (KDV Dahil)</label>
                    <input type="number" step="0.01" name="amount" required class="search-input w-100 text-white" placeholder="0.00">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">KDV Tutarı</label>
                    <input type="number" step="0.01" name="tax_amount" class="search-input w-100 text-white" placeholder="0.00">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Tarih</label>
                    <input type="date" name="expense_date" required class="search-input w-100 text-white" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Açıklama</label>
                    <textarea name="description" class="search-input w-100 text-white" rows="2" placeholder="Masraf açıklaması..."></textarea>
                </div>

                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 w-100 py-3 font-weight-700 mt-2">Masrafı Kaydet</button>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
