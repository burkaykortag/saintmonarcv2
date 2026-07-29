<?php
use App\Helpers\ComponentHelper;

$title = "Özellik Yönetimi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Ürün Özellikleri' => url('/admin/attributes')]) ?>
        <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Varyant Özellik Yönetimi</h2>
        <p class="text-muted mb-0 fs-6">Ürün varyantları için kullanılabilecek nitelikleri (Renk, Beden vb.) ve seçeneklerini yönetin.</p>
    </div>
    
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/attributes/sets') ?>" class="btn btn-secondary border-0">
            <i class="bi bi-folder2-open me-2"></i> Özellik Grupları
        </a>
        <a href="<?= url('/admin/attributes/create') ?>" class="btn">
            <i class="bi bi-plus-circle me-2"></i> Yeni Özellik Ekle
        </a>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="p-3 rounded-4 mb-4" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
    <form method="GET" action="" class="row g-2">
        <div class="col-12 col-md-5">
            <div class="position-relative">
                <input type="text" name="q" class="search-input w-100" placeholder="Özellik adı veya kodu ara..." value="<?= htmlspecialchars($q ?? '') ?>">
                <i class="bi bi-search position-absolute text-muted" style="right: 16px; top: 12px;"></i>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <select name="type" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" onchange="this.form.submit()">
                <option value="">Tüm Özellik Tipleri</option>
                <option value="select" <?= ($type ?? '') === 'select' ? 'selected' : '' ?>>Select (Seçim Kutusu)</option>
                <option value="color_picker" <?= ($type ?? '') === 'color_picker' ? 'selected' : '' ?>>Color Picker (Renk Seçici)</option>
                <option value="text" <?= ($type ?? '') === 'text' ? 'selected' : '' ?>>Text Field (Metin Alti)</option>
                <option value="textarea" <?= ($type ?? '') === 'textarea' ? 'selected' : '' ?>>Text Area (Çoklu Satır)</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <button type="submit" class="btn btn-secondary border-0 w-100 fs-7" style="padding: 10px 0;"><i class="bi bi-funnel me-2"></i>Filtrele</button>
        </div>
    </form>
</div>

<!-- Table -->
<div class="table-responsive rounded-4" style="border: 1px solid var(--sm-border); background: rgba(255,255,255,0.01);">
    <table class="table table-hover align-middle mb-0 text-white">
        <thead class="text-muted" style="background: rgba(255,255,255,0.02);">
            <tr>
                <th style="padding: 16px;">Özellik Adı</th>
                <th style="padding: 16px;">Sistem Kodu</th>
                <th style="padding: 16px;">Türü</th>
                <th style="padding: 16px;">Seçenek Adedi</th>
                <th style="padding: 16px; text-align: right;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($attributes)): ?>
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        Kayıtlı özellik bulunamadı.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($attributes as $attr): ?>
                    <tr>
                        <td style="padding: 16px;" class="font-weight-600"><?= htmlspecialchars($attr['name'] ?? '') ?></td>
                        <td style="padding: 16px;" class="text-muted"><code><?= htmlspecialchars($attr['code'] ?? '') ?></code></td>
                        <td style="padding: 16px;">
                            <span class="badge bg-secondary text-capitalize bg-opacity-10 text-white border-0">
                                <?= str_replace('_', ' ', htmlspecialchars($attr['type'] ?? '')) ?>
                            </span>
                        </td>
                        <td style="padding: 16px;"><?= (int)$attr['value_count'] ?> seçenek</td>
                        <td style="padding: 16px; text-align: right;">
                            <div class="d-inline-flex gap-2">
                                <a href="<?= url('/admin/attributes/edit?id=' . $attr['id']) ?>" class="btn btn-sm btn-secondary border-0 p-2" title="Düzenle">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="<?= url('/admin/attributes/delete') ?>" method="POST" onsubmit="return confirm('Bu özelliği silmek istediğinize emin misiniz?');" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $attr['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger bg-opacity-10 border-0 p-2" title="Sil">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
