<?php
use App\Helpers\ComponentHelper;

$title = "Özellik Grubu Düzenle - SaintMonarc";
include dirname(dirname(__DIR__)) . '/layouts/header.php';

// Prepare English translation
$enTranslation = '';
foreach ($set['translations'] ?? [] as $t) {
    if ((int)$t['language_id'] === 2) {
        $enTranslation = $t['name'];
        break;
    }
}

// Map selected attribute IDs
$selectedIds = array_column($set['attributes'] ?? [], 'id');
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Özellik Yönetimi' => url('/admin/attributes'),
        'Özellik Grupları' => url('/admin/attributes/sets'),
        'Grubu Düzenle' => '#'
    ]) ?>
    <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Grubu Düzenle: <?= htmlspecialchars($set['name'] ?? '') ?></h2>
</div>

<div class="row">
    <div class="col-12 col-xl-8">
        <div class="p-4 rounded-4 mb-4" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
            <form action="<?= url('/admin/attributes/sets/edit') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="id" value="<?= $set['id'] ?>">

                <div class="mb-3">
                    <label class="form-label text-muted">Grup Adı (TR)</label>
                    <input type="text" name="name" class="search-input w-100" required placeholder="Örn: Ayakkabı Özellikleri" value="<?= htmlspecialchars($set['name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Sistem Kodu</label>
                    <input type="text" name="code" class="search-input w-100" required placeholder="Örn: shoe-attributes" value="<?= htmlspecialchars($set['code'] ?? '') ?>">
                </div>

                <!-- Multilingual translations -->
                <div class="mb-4">
                    <h5 class="text-white mt-4 font-weight-600">Dil Çevirileri</h5>
                    <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
                        <div class="mb-3">
                            <label class="form-label text-muted">İngilizce İsim (EN)</label>
                            <input type="text" name="translations[2][name]" class="search-input w-100" placeholder="Örn: Shoe Attributes" value="<?= htmlspecialchars($enTranslation) ?>">
                        </div>
                    </div>
                </div>

                <!-- Mapping Attributes List -->
                <div class="mb-4">
                    <h5 class="text-white mt-4 font-weight-600">Gruba Dahil Edilecek Özellikler</h5>
                    <p class="text-muted fs-7">Bu özellik grubuna atanacak nitelikleri seçin.</p>
                    <div class="row g-2">
                        <?php if (empty($attributes)): ?>
                            <div class="col-12 text-muted">Öncelikle özellik eklemelisiniz.</div>
                        <?php else: ?>
                            <?php foreach ($attributes as $attr): ?>
                                <div class="col-md-6">
                                    <div class="form-check p-3 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border);">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="attribute_ids[]" value="<?= $attr['id'] ?>" id="attrCheck_<?= $attr['id'] ?>" <?= in_array($attr['id'], $selectedIds) ? 'checked' : '' ?>>
                                        <label class="form-check-label text-white" for="attrCheck_<?= $attr['id'] ?>">
                                            <?= htmlspecialchars($attr['name'] ?? '') ?> <code>(<?= htmlspecialchars($attr['code'] ?? '') ?>)</code>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end mt-4">
                    <a href="<?= url('/admin/attributes/sets') ?>" class="btn btn-secondary border-0">İptal</a>
                    <button type="submit" class="btn">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
