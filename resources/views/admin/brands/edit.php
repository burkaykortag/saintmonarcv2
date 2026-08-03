<?php
use App\Helpers\ComponentHelper;

$title = "Marka Düzenle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Markalar' => url('/admin/brands'), 'Marka Düzenle' => '#']) ?>
    <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Markayı Düzenle: <?= htmlspecialchars($brand['name']) ?></h2>
</div>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<form action="<?= url('/admin/brands/edit') ?>" method="POST" class="row g-4">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" value="<?= $brand['id'] ?>">

    <!-- Left Column: Details & Content -->
    <div class="col-12 col-xl-8">
        
        <!-- Brand General details -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Genel Bilgiler</h4>
            
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Marka Adı</label>
                    <input type="text" name="name" required class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;" value="<?= htmlspecialchars($brand['name']) ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Resmi Web Sitesi</label>
                    <input type="url" name="website" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;" value="<?= htmlspecialchars($brand['website'] ?? '') ?>" placeholder="https://nike.com">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Kısa Açıklama</label>
                <input type="text" name="short_description" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;" value="<?= htmlspecialchars($brand['short_description'] ?? '') ?>" placeholder="Sosyal paylaşımlar ve listelemeler için kısa slogan...">
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Detaylı Açıklama (CMS & Marka Sayfası)</label>
                <textarea name="description" class="form-control border-0 text-white" rows="6" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"><?= htmlspecialchars($brand['description'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Media selection (Logo, Cover, Banner) -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Medya Dosyaları</h4>
            
            <div class="row g-3">
                <!-- Brand Logo image -->
                <div class="col-12 col-md-4 text-center">
                    <label class="form-label text-muted fs-7 mb-2 d-block">Marka Logosu</label>
                    <input type="hidden" name="logo_image_id" id="logo_image_id" value="<?= $brand['logo_image_id'] ?? '' ?>">
                    <div id="logo_preview" class="p-3 mb-2 rounded-3 border d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.2); border-color: var(--sm-border) !important; height:120px;">
                        <?php if (!empty($brand['logo_path'])): ?>
                            <img src="<?= url('/' . $brand['logo_path']) ?>" class="img-fluid rounded-3" style="max-height: 100px; object-fit: contain;">
                        <?php else: ?>
                            <span class="text-muted fs-8">Görsel Seçilmedi</span>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary py-2 w-100 fs-7" onclick="openMediaPicker('logo')">Görsel Seç</button>
                </div>

                <!-- Cover Image -->
                <div class="col-12 col-md-4 text-center">
                    <label class="form-label text-muted fs-7 mb-2 d-block">Kapak Görseli</label>
                    <input type="hidden" name="cover_image_id" id="cover_image_id" value="<?= $brand['cover_image_id'] ?? '' ?>">
                    <div id="cover_preview" class="p-3 mb-2 rounded-3 border d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.2); border-color: var(--sm-border) !important; height:120px;">
                        <?php if (!empty($brand['cover_path'])): ?>
                            <img src="<?= url('/' . $brand['cover_path']) ?>" class="img-fluid rounded-3" style="max-height: 100px; object-fit: contain;">
                        <?php else: ?>
                            <span class="text-muted fs-8">Görsel Seçilmedi</span>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary py-2 w-100 fs-7" onclick="openMediaPicker('cover')">Görsel Seç</button>
                </div>

                <!-- Banner Image -->
                <div class="col-12 col-md-4 text-center">
                    <label class="form-label text-muted fs-7 mb-2 d-block">Banner Görseli</label>
                    <input type="hidden" name="banner_image_id" id="banner_image_id" value="<?= $brand['banner_image_id'] ?? '' ?>">
                    <div id="banner_preview" class="p-3 mb-2 rounded-3 border d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.2); border-color: var(--sm-border) !important; height:120px;">
                        <?php if (!empty($brand['banner_path'])): ?>
                            <img src="<?= url('/' . $brand['banner_path']) ?>" class="img-fluid rounded-3" style="max-height: 100px; object-fit: contain;">
                        <?php else: ?>
                            <span class="text-muted fs-8">Görsel Seçilmedi</span>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary py-2 w-100 fs-7" onclick="openMediaPicker('banner')">Görsel Seç</button>
                </div>
            </div>
        </div>

        <!-- SEO Section -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">SEO & Meta Bilgileri</h4>
            
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Meta Başlığı (Title)</label>
                    <input type="text" name="seo[title]" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;" value="<?= htmlspecialchars($seo['title'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Anahtar Kelimeler (Keywords)</label>
                    <input type="text" name="seo[keywords]" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;" value="<?= htmlspecialchars($seo['keywords'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label text-muted fs-7 mb-1">Meta Açıklaması (Description)</label>
                    <textarea name="seo[description]" class="form-control border-0 text-white fs-7" rows="2" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"><?= htmlspecialchars($seo['description'] ?? '') ?></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Canonical URL</label>
                    <input type="text" name="seo[canonical_url]" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;" value="<?= htmlspecialchars($seo['canonical_url'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Robots Politikası</label>
                    <select name="seo[robots]" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;">
                        <option value="index, follow" <?= ($seo['robots'] ?? 'index, follow') === 'index, follow' ? 'selected' : '' ?>>index, follow</option>
                        <option value="noindex, nofollow" <?= ($seo['robots'] ?? '') === 'noindex, nofollow' ? 'selected' : '' ?>>noindex, nofollow</option>
                    </select>
                </div>

                <!-- Open Graph & Social sharing -->
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Open Graph Title</label>
                    <input type="text" name="seo[og_title]" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;" value="<?= htmlspecialchars($seo['og_title'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Social Share Image URL</label>
                    <input type="text" name="seo[og_image]" id="og_image_url" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;" value="<?= htmlspecialchars($seo['og_image'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label text-muted fs-7 mb-1">Open Graph Description</label>
                    <textarea name="seo[og_description]" class="form-control border-0 text-white fs-7" rows="2" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"><?= htmlspecialchars($seo['og_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Column: Actions & switches -->
    <div class="col-12 col-xl-4">
        
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Yayinlama Ayarlari</h4>
            
            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Siralama Degeri</label>
                <input type="number" name="sort_order" class="form-control border-0 text-white" value="<?= $brand['sort_order'] ?>" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= $brand['is_active'] ? 'checked' : '' ?> id="isActiveSwitch" style="accent-color: var(--sm-gold);">
                <label class="form-check-label text-white fs-7" for="isActiveSwitch">Marka Aktif</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_featured" value="1" <?= $brand['is_featured'] ? 'checked' : '' ?> id="isFeaturedSwitch" style="accent-color: var(--sm-gold);">
                <label class="form-check-label text-white fs-7" for="isFeaturedSwitch">Öne Çıkan Marka</label>
            </div>

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="show_in_home" value="1" <?= $brand['show_in_home'] ? 'checked' : '' ?> id="showInHomeSwitch" style="accent-color: var(--sm-gold);">
                <label class="form-check-label text-white fs-7" for="showInHomeSwitch">Ana Sayfada Göster</label>
            </div>

            <div class="d-flex flex-column gap-2">
                <button type="submit" class="btn w-100 py-3">Degisiklikleri Kaydet</button>
                <a href="<?= url('/admin/brands') ?>" class="btn btn-secondary w-100 py-3 border-0 text-center">Iptal Et</a>
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
                    const fullPathUrl = '<?= url("/") ?>/' + item.filepath;
                    if (activeMediaTarget === 'logo') {
                        document.getElementById('logo_image_id').value = item.id;
                        document.getElementById('logo_preview').innerHTML = `<img src="${fullPathUrl}" class="img-fluid rounded-3" style="max-height: 100px; object-fit: contain;">`;
                    } else if (activeMediaTarget === 'cover') {
                        document.getElementById('cover_image_id').value = item.id;
                        document.getElementById('cover_preview').innerHTML = `<img src="${fullPathUrl}" class="img-fluid rounded-3" style="max-height: 100px; object-fit: contain;">`;
                    } else if (activeMediaTarget === 'banner') {
                        document.getElementById('banner_image_id').value = item.id;
                        document.getElementById('banner_preview').innerHTML = `<img src="${fullPathUrl}" class="img-fluid rounded-3" style="max-height: 100px; object-fit: contain;">`;
                    }
                }
            }
        });
    }
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
