<?php
use App\Helpers\ComponentHelper;

$title = "Kategori Ekle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Kategoriler' => url('/admin/categories'), 'Yeni Kategori' => '#']) ?>
    <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Yeni Kategori Ekle</h2>
</div>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<form action="<?= url('/admin/categories/create') ?>" method="POST" class="row g-4">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <!-- Left Column: Details -->
    <div class="col-12 col-xl-8">
        
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Genel Bilgiler</h4>
            
            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Kategori Adı</label>
                <input type="text" name="name" required class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;" placeholder="Örn: Ayakkabı & Çanta">
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Üst Kategori</label>
                <select name="parent_id" class="form-select border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;">
                    <option value="">Yok (Ana Kategori Yap)</option>
                    <?php foreach ($parents as $parent): ?>
                        <option value="<?= $parent['id'] ?>"><?= htmlspecialchars($parent['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Kategori Açıklaması</label>
                <textarea name="description" class="form-control border-0 text-white" rows="4" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;" placeholder="Kategoriye ait kısa açıklama giriniz..."></textarea>
            </div>
        </div>

        <!-- Media Selection -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Görseller & İkon</h4>
            
            <div class="row g-3">
                <!-- Cover Image -->
                <div class="col-12 col-md-4 text-center">
                    <label class="form-label text-muted fs-7 mb-2 d-block">Kapak Görseli</label>
                    <input type="hidden" name="cover_image_id" id="cover_image_id" value="">
                    <div id="cover_preview" class="p-3 mb-2 rounded-3 border d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.2); border-color: var(--sm-border) !important; height:120px;">
                        <span class="text-muted fs-8">Görsel Seçilmedi</span>
                    </div>
                    <button type="button" class="btn btn-secondary py-2 w-100 fs-7" onclick="openMediaPicker('cover')">Görsel Seç</button>
                </div>

                <!-- Banner Image -->
                <div class="col-12 col-md-4 text-center">
                    <label class="form-label text-muted fs-7 mb-2 d-block">Banner Görseli</label>
                    <input type="hidden" name="banner_image_id" id="banner_image_id" value="">
                    <div id="banner_preview" class="p-3 mb-2 rounded-3 border d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.2); border-color: var(--sm-border) !important; height:120px;">
                        <span class="text-muted fs-8">Görsel Seçilmedi</span>
                    </div>
                    <button type="button" class="btn btn-secondary py-2 w-100 fs-7" onclick="openMediaPicker('banner')">Görsel Seç</button>
                </div>

                <!-- Icon Image -->
                <div class="col-12 col-md-4 text-center">
                    <label class="form-label text-muted fs-7 mb-2 d-block">Kategori İkonu</label>
                    <input type="hidden" name="icon_image_id" id="icon_image_id" value="">
                    <div id="icon_preview" class="p-3 mb-2 rounded-3 border d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.2); border-color: var(--sm-border) !important; height:120px;">
                        <span class="text-muted fs-8">İkon Seçilmedi</span>
                    </div>
                    <button type="button" class="btn btn-secondary py-2 w-100 fs-7" onclick="openMediaPicker('icon')">İkon Seç</button>
                </div>
            </div>
        </div>

        <!-- SEO Metadata -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">SEO & Meta Verileri</h4>
            
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Meta Başlığı (Title)</label>
                    <input type="text" name="seo[title]" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Anahtar Kelimeler (Keywords)</label>
                    <input type="text" name="seo[keywords]" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;" placeholder="Kelime, kelime...">
                </div>
                <div class="col-12">
                    <label class="form-label text-muted fs-7 mb-1">Meta Açıklaması (Description)</label>
                    <textarea name="seo[description]" class="form-control border-0 text-white fs-7" rows="2" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Canonical URL</label>
                    <input type="text" name="seo[canonical_url]" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Robots Politikası</label>
                    <select name="seo[robots]" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;">
                        <option value="index, follow">index, follow (Tavsiye Edilen)</option>
                        <option value="noindex, nofollow">noindex, nofollow</option>
                        <option value="noindex, follow">noindex, follow</option>
                    </select>
                </div>

                <!-- Open Graph Social Share Settings -->
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Open Graph Title</label>
                    <input type="text" name="seo[og_title]" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Open Graph Image URL</label>
                    <input type="text" name="seo[og_image]" id="og_image_url" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;">
                </div>
                <div class="col-12">
                    <label class="form-label text-muted fs-7 mb-1">Open Graph Description</label>
                    <textarea name="seo[og_description]" class="form-control border-0 text-white fs-7" rows="2" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"></textarea>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Column: Settings / Action -->
    <div class="col-12 col-xl-4">
        
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Yayınlama Ayarları</h4>
            
            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Sıralama Değeri</label>
                <input type="number" name="sort_order" class="form-control border-0 text-white" value="0" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="isActiveSwitch" style="accent-color: var(--sm-gold);">
                <label class="form-check-label text-white fs-7" for="isActiveSwitch">Kategori Aktif</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="show_in_menu" value="1" id="showInMenuSwitch" style="accent-color: var(--sm-gold);">
                <label class="form-check-label text-white fs-7" for="showInMenuSwitch">Navigasyon Menüsünde Göster</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="show_in_home" value="1" id="showInHomeSwitch" style="accent-color: var(--sm-gold);">
                <label class="form-check-label text-white fs-7" for="showInHomeSwitch">Ana Sayfada Göster</label>
            </div>

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="isFeaturedSwitch" style="accent-color: var(--sm-gold);">
                <label class="form-check-label text-white fs-7" for="isFeaturedSwitch">Öne Çıkan Kategori</label>
            </div>

            <div class="d-flex flex-column gap-2">
                <button type="submit" class="btn w-100 py-3">Kategoriyi Kaydet</button>
                <a href="<?= url('/admin/categories') ?>" class="btn btn-secondary w-100 py-3 border-0 text-center">İptal Et</a>
            </div>
        </div>

    </div>
</form>

<!-- Include Advanced Media Picker Modal -->
<?php include dirname(__DIR__) . '/media/media_picker_modal.php'; ?>

<script>
    let activeMediaTarget = null;

    function openMediaPicker(targetType) {
        activeMediaTarget = targetType;
        
        SM_MediaPicker.init({
            singleSelect: true,
            allowedTypes: ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'],
            callback: function(selectedItems) {
                if (selectedItems.length > 0) {
                    const item = selectedItems[0];
                    if (activeMediaTarget === 'cover') {
                        document.getElementById('cover_image_id').value = item.id;
                        document.getElementById('cover_preview').innerHTML = `<img src="${'<?= url("/") ?>/' + item.filepath}" class="img-fluid rounded-3" style="max-height: 100px; object-fit: contain;">`;
                    } else if (activeMediaTarget === 'banner') {
                        document.getElementById('banner_image_id').value = item.id;
                        document.getElementById('banner_preview').innerHTML = `<img src="${'<?= url("/") ?>/' + item.filepath}" class="img-fluid rounded-3" style="max-height: 100px; object-fit: contain;">`;
                    } else if (activeMediaTarget === 'icon') {
                        document.getElementById('icon_image_id').value = item.id;
                        document.getElementById('icon_preview').innerHTML = `<img src="${'<?= url("/") ?>/' + item.filepath}" class="img-fluid rounded-3" style="max-height: 100px; object-fit: contain;">`;
                    }
                }
            }
        });
    }
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
