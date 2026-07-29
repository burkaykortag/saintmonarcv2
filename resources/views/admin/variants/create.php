<?php
use App\Helpers\ComponentHelper;

$title = "Varyant Ekle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Ürünler' => url('/admin/products'),
        'Ürün Düzenle' => url('/admin/products/edit?id=' . $product['id']),
        'Yeni Varyant Ekle' => '#'
    ]) ?>
    <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Varyant Ekle: <?= htmlspecialchars($product['name'] ?? '') ?></h2>
</div>

<div class="p-4 rounded-4 mb-4" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
    <form action="<?= url('/admin/variants/create') ?>" method="POST" id="variantForm">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

        <h5 class="text-white mb-3 font-weight-600">Varyant Özellik Seçimleri</h5>
        <div class="row g-3 mb-4">
            <?php foreach ($attributes as $attr): 
                if (empty($attr['values'])) continue;
            ?>
                <div class="col-md-4">
                    <label class="form-label text-muted"><?= htmlspecialchars($attr['name'] ?? '') ?></label>
                    <select name="options[<?= $attr['id'] ?>]" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;">
                        <option value="">Seçilmedi</option>
                        <?php foreach ($attr['values'] as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        </div>

        <h5 class="text-white mb-3 font-weight-600">Stok ve Kodlama Bilgileri</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label text-muted">SKU (Stok Kodu)</label>
                <div class="input-group">
                    <input type="text" name="sku" id="skuInput" class="search-input w-100" required>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted">Barkod (EAN/Code128)</label>
                <div class="input-group">
                    <input type="text" name="barcode" id="barcodeInput" class="search-input w-100">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted">Stok Miktarı</label>
                <input type="number" name="stock" class="search-input w-100" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted">Varyant Durumu</label>
                <select name="is_active" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;">
                    <option value="1">Aktif</option>
                    <option value="0">Pasif</option>
                </select>
            </div>
        </div>

        <h5 class="text-white mb-3 font-weight-600">Fiyatlandırma</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label text-muted">Fiyat (TRY)</label>
                <input type="number" step="0.01" name="price" class="search-input w-100" required>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted">Piyasa Fiyatı (Üstü Çizili)</label>
                <input type="number" step="0.01" name="compare_at_price" class="search-input w-100">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted">İndirimli Özel Fiyat</label>
                <input type="number" step="0.01" name="special_price" class="search-input w-100">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted">Maliyet</label>
                <input type="number" step="0.01" name="cost_price" class="search-input w-100">
            </div>
        </div>

        <h5 class="text-white mb-3 font-weight-600">Kargo ve Kutu Boyutları</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <label class="form-label text-muted">Ağırlık (kg)</label>
                <input type="number" step="0.01" name="weight" class="search-input w-100" value="0">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted">Desi</label>
                <input type="number" step="0.01" name="desi" class="search-input w-100" value="0">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted">En (cm)</label>
                <input type="number" step="0.01" name="width" class="search-input w-100">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted">Boy (cm)</label>
                <input type="number" step="0.01" name="length" class="search-input w-100">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted">Yükseklik (cm)</label>
                <input type="number" step="0.01" name="height" class="search-input w-100">
            </div>
        </div>

        <!-- Medya Seçimi -->
        <h5 class="text-white mb-3 font-weight-600">Görsel Seçimleri</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label text-muted">Kapak Görseli</label>
                <div class="d-flex gap-2">
                    <input type="hidden" name="image_id" id="coverImageId">
                    <button type="button" class="btn btn-secondary border-0 btn-choose-media" data-target="#coverImageId" data-preview="#coverPreview">Medya Kütüphanesinden Seç</button>
                </div>
                <div id="coverPreview" class="mt-2"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">Galeri Görselleri</label>
                <div class="d-flex gap-2">
                    <input type="hidden" name="image_ids[]" id="galleryImageIds">
                    <button type="button" class="btn btn-secondary border-0 btn-choose-media" data-target="#galleryImageIds" data-preview="#galleryPreview" data-multiple="true">Medya Kütüphanesinden Seç</button>
                </div>
                <div id="galleryPreview" class="d-flex gap-2 flex-wrap mt-2"></div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end mt-4">
            <a href="<?= url('/admin/products/edit?id=' . $product['id']) ?>" class="btn btn-secondary border-0">İptal</a>
            <button type="submit" class="btn">Varyantı Kaydet</button>
        </div>
    </form>
</div>

<!-- Modal integration -->
<?php include dirname(dirname(__DIR__)) . '/media/media_picker_modal.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic media choosing trigger
    document.querySelectorAll('.btn-choose-media').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const targetInput = document.querySelector(btn.dataset.target);
            const previewEl = document.querySelector(btn.dataset.preview);
            const isMultiple = btn.dataset.multiple === 'true';

            // Show Media picker modal using the framework logic
            if (window.showMediaPicker) {
                window.showMediaPicker({
                    multiple: isMultiple,
                    onSelect: function(items) {
                        if (isMultiple) {
                            previewEl.innerHTML = '';
                            const ids = [];
                            items.forEach(function(item) {
                                ids.push(item.id);
                                previewEl.innerHTML += `<img src="${item.url}" class="rounded-3" style="width:60px;height:60px;object-fit:cover;border:1px solid var(--sm-border);">`;
                            });
                            // Set input values E.g. arrays
                            targetInput.value = ids.join(',');
                        } else {
                            if (items.length > 0) {
                                targetInput.value = items[0].id;
                                previewEl.innerHTML = `<img src="${items[0].url}" class="rounded-3" style="width:80px;height:80px;object-fit:cover;border:1px solid var(--sm-border);">`;
                            }
                        }
                    }
                });
            } else {
                alert('Medya kütüphanesi modülü yüklenemedi.');
            }
        });
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
