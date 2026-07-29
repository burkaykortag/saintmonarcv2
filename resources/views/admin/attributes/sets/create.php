<?php
use App\Helpers\ComponentHelper;

$title = "Özellik Grubu Ekle - SaintMonarc";
include dirname(dirname(__DIR__)) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Özellik Yönetimi' => url('/admin/attributes'),
        'Özellik Grupları' => url('/admin/attributes/sets'),
        'Yeni Grup Ekle' => '#'
    ]) ?>
    <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Yeni Özellik Grubu Ekle</h2>
</div>

<div class="row">
    <div class="col-12 col-xl-8">
        <div class="p-4 rounded-4 mb-4" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
            <form action="<?= url('/admin/attributes/sets/create') ?>" method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted">Grup Adı (TR)</label>
                    <input type="text" name="name" class="search-input w-100" required placeholder="Örn: Ayakkabı Özellikleri, Tekstil Özellikleri">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Sistem Kodu</label>
                    <input type="text" name="code" class="search-input w-100" required placeholder="Örn: shoe-attributes, clothing-attributes">
                </div>

                <!-- Multilingual translations -->
                <div class="mb-4">
                    <h5 class="text-white mt-4 font-weight-600">Dil Çevirileri</h5>
                    <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
                        <div class="mb-3">
                            <label class="form-label text-muted">İngilizce İsim (EN)</label>
                            <input type="text" name="translations[2][name]" class="search-input w-100" placeholder="Örn: Shoe Attributes">
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
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="attribute_ids[]" value="<?= $attr['id'] ?>" id="attrCheck_<?= $attr['id'] ?>">
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
                    <button type="submit" class="btn">Grubu Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
