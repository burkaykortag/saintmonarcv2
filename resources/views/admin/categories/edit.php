<?php
use App\Helpers\ComponentHelper;

$title = "Kategori DÃ¼zenle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['YÃ¶netim Paneli' => url('/admin'), 'Katalog' => '#', 'Kategoriler' => url('/admin/categories'), 'Kategori DÃ¼zenle' => '#']) ?>
    <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Kategoriyi DÃ¼zenle: <?= htmlspecialchars($category['name']) ?></h2>
</div>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<form action="<?= url('/admin/categories/edit') ?>" method="POST" class="row g-4">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" value="<?= $category['id'] ?>">

    <!-- Left Column: Details -->
    <div class="col-12 col-xl-8">
        
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Genel Bilgiler</h4>
            
            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Kategori AdÄ±</label>
                <input type="text" name="name" required class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;" value="<?= htmlspecialchars($category['name']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Ãst Kategori</label>
                <select name="parent_id" class="form-select border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;">
                    <option value="">Yok (Ana Kategori Yap)</option>
                    <?php foreach ($parents as $parent): ?>
                        <!-- Prevent selection of self as parent -->
                        <?php if ((int)$parent['id'] !== (int)$category['id']): ?>
                            <option value="<?= $parent['id'] ?>" <?= (int)$parent['id'] === (int)$category['parent_id'] ? 'selected' : '' ?>><?= htmlspecialchars($parent['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Kategori AÃ§Ä±klamasÄ±</label>
                <textarea name="description" class="form-control border-0 text-white" rows="4" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Media Selection -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">GÃ¶rseller & Ä°kon</h4>
            
            <div class="row g-3">
                <!-- Cover Image -->
                <div class="col-12 col-md-4 text-center">
                    <label class="form-label text-muted fs-7 mb-2 d-block">Kapak GÃ¶rseli</label>
                    <input type="hidden" name="cover_image_id" id="cover_image_id" value="<?= $category['cover_image_id'] ?? '' ?>">
                    <div id="cover_preview" class="p-3 mb-2 rounded-3 border d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.2); border-color: var(--sm-border) !important; height:120px;">
                        <?php if (!empty($category['cover_path'])): ?>
                            <img src="<?= url('/' . $category['cover_path']) ?>" class="img-fluid rounded-3" style="max-height: 100px; object-fit: contain;">
                        <?php else: ?>
                            <span class="text-muted fs-8">GÃ¶rsel SeÃ§ilmedi</span>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary py-2 w-100 fs-7" onclick="openMediaPicker('cover')">GÃ¶rsel SeÃ§</button>
                </div>

                <!-- Banner Image -->
                <div class="col-12 col-md-4 text-center">
                    <label class="form-label text-muted fs-7 mb-2 d-block">Banner GÃ¶rseli</label>
                    <input type="hidden" name="banner_image_id" id="banner_image_id" value="<?= $category['banner_image_id'] ?? '' ?>">
                    <div id="banner_preview" class="p-3 mb-2 rounded-3 border d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.2); border-color: var(--sm-border) !important; height:120px;">
                        <?php if (!empty($category['banner_path'])): ?>
                            <img src="<?= url('/' . $category['banner_path']) ?>" class="img-fluid rounded-3" style="max-height: 100px; object-fit: contain;">
                        <?php else: ?>
                            <span class="text-muted fs-8">GÃ¶rsel SeÃ§ilmedi</span>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary py-2 w-100 fs-7" onclick="openMediaPicker('banner')">GÃ¶rsel SeÃ§</button>
                </div>

                <!-- Icon Image -->
                <div class="col-12 col-md-4 text-center">
                    <label class="form-label text-muted fs-7 mb-2 d-block">Kategori Ä°konu</label>
                    <input type="hidden" name="icon_image_id" id="icon_image_id" value="<?= $category['icon_image_id'] ?? '' ?>">
                    <div id="icon_preview" class="p-3 mb-2 rounded-3 border d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.2); border-color: var(--sm-border) !important; height:120px;">
                        <?php if (!empty($category['icon_path'])): ?>
                            <img src="<?= url('/' . $category['icon_path']) ?>" class="img-fluid rounded-3" style="max-height: 100px; object-fit: contain;">
                        <?php else: ?>
                            <span class="text-muted fs-8">Ä°kon SeÃ§ilmedi</span>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary py-2 w-100 fs-7" onclick="openMediaPicker('icon')">Ä°kon SeÃ§</button>
                </div>
            </div>
        </div>

        <!-- SEO Metadata -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">SEO & Meta Verileri</h4>
            
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Meta BaÅlÄ±ÄÄ± (Title)</label>
                    <input type="text" name="seo[title]" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;" value="<?= htmlspecialchars($seo['title'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Anahtar Kelimeler (Keywords)</label>
                    <input type="text" name="seo[keywords]" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;" value="<?= htmlspecialchars($seo['keywords'] ?? '') ?>" placeholder="Kelime, kelime...">
                </div>
                <div class="col-12">
                    <label class="form-label text-muted fs-7 mb-1">Meta AÃ§Ä±klamasÄ± (Description)</label>
                    <textarea name="seo[description]" class="form-control border-0 text-white fs-7" rows="2" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"><?= htmlspecialchars($seo['description'] ?? '') ?></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Canonical URL</label>
                    <input type="text" name="seo[canonical_url]" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;" value="<?= htmlspecialchars($seo['canonical_url'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Robots PolitikasÄ±</label>
                    <select name="seo[robots]" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;">
                        <option value="index, follow" <?= ($seo['robots'] ?? 'index, follow') === 'index, follow' ? 'selected' : '' ?>>index, follow (Tavsiye Edilen)</option>
                        <option value="noindex, nofollow" <?= ($seo['robots'] ?? '') === 'noindex, nofollow' ? 'selected' : '' ?>>noindex, nofollow</option>
                        <option value="noindex, follow" <?= ($seo['robots'] ?? '') === 'noindex, follow' ? 'selected' : '' ?>>noindex, follow</option>
                    </select>
                </div>

                <!-- Open Graph Social Share Settings -->
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Open Graph Title</label>
                    <input type="text" name="seo[og_title]" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;" value="<?= htmlspecialchars($seo['og_title'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Open Graph Image URL</label>
                    <input type="text" name="seo[og_image]" id="og_image_url" class="form-control border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;" value="<?= htmlspecialchars($seo['og_image'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label text-muted fs-7 mb-1">Open Graph Description</label>
                    <textarea name="seo[og_description]" class="form-control border-0 text-white fs-7" rows="2" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"><?= htmlspecialchars($seo['og_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Column: Settings / Action -->
    <div class="col-12 col-xl-4">
        
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Yayinlama Ayarlari</h4>
            
            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Siralama Degeri</label>
                <input type="number" name="sort_order" class="form-control border-0 text-white" value="<?= $category['sort_order'] ?>" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= $category['is_active'] ? 'checked' : '' ?> id="isActiveSwitch" style="accent-color: var(--sm-gold);">
                <label class="form-check-label text-white fs-7" for="isActiveSwitch">Kategori Aktif</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="show_in_menu" value="1" <?= $category['show_in_menu'] ? 'checked' : '' ?> id="showInMenuSwitch" style="accent-color: var(--sm-gold);">
                <label class="form-check-label text-white fs-7" for="showInMenuSwitch">Navigasyon Menüsünde Göster</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="show_in_home" value="1" <?= $category['show_in_home'] ? 'checked' : '' ?> id="showInHomeSwitch" style="accent-color: var(--sm-gold);">
                <label class="form-check-label text-white fs-7" for="showInHomeSwitch">Ana Sayfada Göster</label>
            </div>

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="is_featured" value="1" <?= $category['is_featured'] ? 'checked' : '' ?> id="isFeaturedSwitch" style="accent-color: var(--sm-gold);">
                <label class="form-check-label text-white fs-7" for="isFeaturedSwitch">Öne Çıkan Kategori</label>
            </div>

            <div class="d-flex flex-column gap-2">
                <button type="submit" class="btn w-100 py-3">Degisiklikleri Kaydet</button>
                <a href="<?= url('/admin/categories') ?>" class="btn btn-secondary w-100 py-3 border-0 text-center">Iptal Et</a>
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
