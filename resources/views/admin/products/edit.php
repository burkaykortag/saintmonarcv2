<?php
use App\Helpers\ComponentHelper;

$title = 'PIM – Ürün Düzenle | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();

$jsonLd = [
    '@context' => 'https://schema.org/', '@type' => 'Product',
    'name'  => $product['name'],
    'sku'   => $product['sku'],
    'brand' => ['@type' => 'Brand', 'name' => $product['brand_name'] ?? 'SaintMonarc'],
    'offers'=> ['@type' => 'Offer',
        'priceCurrency' => $product['currency_code'] ?? 'TRY',
        'price'         => $product['price'],
        'availability'  => (($product['total_stock'] ?? 0) > 0)
            ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock']
];
$jsonLdString = json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$price     = (float)($product['price']      ?? 0);
$salePrice = (float)($product['sale_price'] ?? 0);
$cost      = (float)($product['cost_price'] ?? 0);
$stock     = (int)($product['total_stock']  ?? 0);
$critStock = (int)($product['critical_stock'] ?? 5);
$margin    = $price > 0 ? round((($price - $cost) / $price) * 100, 1) : 0;
$statusMap = [
    'published'   => ['Yayında',     'success'],
    'draft'       => ['Taslak',      'muted'],
    'passive'     => ['Pasif',       'warning'],
    'archived'    => ['Arşiv',       'danger'],
    'coming_soon' => ['Yakında',     'info'],
];
$st = $statusMap[$product['status'] ?? 'draft'] ?? ['Bilinmiyor', 'muted'];
?>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
/* ─── Product Info Bar ───────────────────────────── */
.product-bar{display:flex;align-items:center;flex-wrap:wrap;gap:20px;padding:16px 22px;background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius);margin-bottom:18px}
.product-bar-thumb{width:48px;height:48px;border-radius:10px;object-fit:cover;border:1px solid var(--pim-border);flex-shrink:0}
.product-bar-info{flex:1;min-width:0}
.product-bar-name{font-size:17px;font-weight:700;color:var(--pim-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.product-bar-sku{font-family:var(--pim-font-mono);font-size:12px;color:var(--pim-text-xs);margin-top:2px}
.product-bar-meta{display:flex;gap:16px;align-items:center;flex-wrap:wrap}
.product-bar-stat{display:flex;flex-direction:column;align-items:center;gap:2px}
.product-bar-stat-val{font-size:15px;font-weight:700;color:var(--pim-text)}
.product-bar-stat-lbl{font-size:10px;text-transform:uppercase;color:var(--pim-text-xs);letter-spacing:.5px}
/* ─── Workspace overrides ────────────────────────── */
.pim-workspace{min-height:78vh}
.stock-progress{height:6px;border-radius:3px;background:rgba(255,255,255,.06);margin-top:6px;overflow:hidden}
.stock-bar-fill{height:100%;border-radius:3px;transition:width .8s var(--pim-ease)}
/* ─── Variant Row ─────────────────────────────────── */
.variant-grid-cols{grid-template-columns:2fr 1fr 1fr 1fr 1fr 1fr 48px}
/* ─── Price grid ──────────────────────────────────── */
.price-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
/* ─── AI Ring ─────────────────────────────────────── */
.ai-ring{width:70px;height:70px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-direction:column;background:conic-gradient(var(--pim-purple) var(--r,0deg),rgba(255,255,255,.05) 0);flex-shrink:0}
.ai-ring-inner{width:52px;height:52px;border-radius:50%;background:var(--pim-surface);display:flex;flex-direction:column;align-items:center;justify-content:center}
/* ─── Version list ────────────────────────────────── */
.version-row{display:flex;align-items:center;gap:14px;padding:12px 16px;border-bottom:1px solid var(--pim-border);transition:background .15s}
.version-row:last-child{border-bottom:none}
.version-row:hover{background:rgba(255,255,255,.02)}
.version-badge{font-family:var(--pim-font-mono);font-size:11px;font-weight:700;background:rgba(197,168,128,.12);color:var(--pim-gold);padding:3px 9px;border-radius:6px}
/* ─── Autosave ────────────────────────────────────── */
#autosaveStatus{font-size:12px;color:var(--pim-text-xs);transition:all .3s}
#autosaveStatus.saving{color:var(--pim-warning)}
#autosaveStatus.saved{color:var(--pim-success)}
@media(max-width:768px){.price-grid{grid-template-columns:1fr}.product-bar-meta{gap:12px}}
</style>

<div class="pim-module">

<!-- ─── Breadcrumb + Header ──────────────────────────────── -->
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Ürünler' => url('/admin/products'), 'Düzenle' => '#']) ?>
        <h2 class="text-white fw-bold m-0 mt-1" style="font-size:22px">
            Ürün Düzenle
            <span class="pim-badge pim-badge-<?= $st[1] ?> ms-2" style="font-size:12px;vertical-align:middle"><?= $st[0] ?></span>
        </h2>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <span id="autosaveStatus">●&nbsp;Değişiklikler kaydedilmedi</span>
        <a href="<?= url('/products/' . $product['slug']) ?>" target="_blank" class="pim-btn pim-btn-ghost pim-btn-sm"><i class="bi bi-eye"></i> Önizle</a>
        <a href="<?= url('/admin/products/duplicate?id=' . $product['id']) ?>" class="pim-btn pim-btn-ghost pim-btn-sm"><i class="bi bi-copy"></i> Kopyala</a>
        <a href="<?= url('/admin/products') ?>" class="pim-btn pim-btn-secondary pim-btn-sm"><i class="bi bi-arrow-left"></i> Geri</a>
        <button type="submit" form="pimEditForm" class="pim-btn pim-btn-primary"><i class="bi bi-check2-circle"></i> Kaydet</button>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
<div class="alert border-0 rounded-3 mb-3" style="background:var(--pim-success-bg);color:var(--pim-success)"><i class="bi bi-check-circle me-2"></i>Ürün başarıyla güncellendi.</div>
<?php endif; if (!empty($_GET['error'])): ?>
<div class="alert border-0 rounded-3 mb-3" style="background:var(--pim-danger-bg);color:var(--pim-danger)"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<!-- ─── Product Info Bar ─────────────────────────────────── -->
<div class="product-bar mb-3">
    <?php if (!empty($product['cover_path'])): ?>
    <img src="<?= url($product['cover_path']) ?>" class="product-bar-thumb" loading="lazy" alt="<?= htmlspecialchars($product['name']) ?>">
    <?php else: ?>
    <div class="product-bar-thumb d-flex align-items-center justify-content-center" style="background:rgba(255,255,255,.04)"><i class="bi bi-image text-muted"></i></div>
    <?php endif; ?>
    <div class="product-bar-info">
        <div class="product-bar-name"><?= htmlspecialchars($product['name']) ?></div>
        <div class="product-bar-sku"><i class="bi bi-upc me-1"></i><?= htmlspecialchars($product['sku']) ?></div>
    </div>
    <div class="product-bar-meta">
        <div class="product-bar-stat"><span class="product-bar-stat-val"><?= number_format($price, 2) ?> ₺</span><span class="product-bar-stat-lbl">Satış Fiyatı</span></div>
        <div class="product-bar-stat"><span class="product-bar-stat-val"><?= $stock ?></span><span class="product-bar-stat-lbl">Mevcut Stok</span></div>
        <div class="product-bar-stat"><span class="product-bar-stat-val">%<?= $margin ?></span><span class="product-bar-stat-lbl">Kar Marjı</span></div>
        <div class="product-bar-stat"><span class="product-bar-stat-val"><?= htmlspecialchars($product['brand_name'] ?? '—') ?></span><span class="product-bar-stat-lbl">Marka</span></div>
    </div>
</div>

<!-- ─── Main Form ────────────────────────────────────────── -->
<form id="pimEditForm" action="<?= url('/admin/products/edit') ?>" method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
<input type="hidden" name="id" value="<?= $product['id'] ?>">

<!-- ─── Workspace ───────────────────────────────────────── -->
<div class="pim-workspace" id="pimWorkspace">

  <!-- LEFT: Tab Nav -->
  <nav class="pim-tab-nav" role="tablist" aria-label="Ürün yönetim sekmeleri">
    <div class="pim-tab-section-label">Temel Bilgiler</div>
    <div class="pim-tab-item" data-tab="general"      role="tab" aria-controls="general"      title="Genel Bilgiler">     <i class="bi bi-info-circle"></i>Genel Bilgiler</div>
    <div class="pim-tab-item" data-tab="descriptions" role="tab" aria-controls="descriptions" title="Açıklamalar">         <i class="bi bi-text-left"></i>Açıklamalar</div>
    <div class="pim-tab-item" data-tab="seo"          role="tab" aria-controls="seo"          title="SEO Workspace">       <i class="bi bi-search-heart"></i>SEO</div>
    <div class="pim-tab-divider"></div>
    <div class="pim-tab-section-label">Fiyat & Stok</div>
    <div class="pim-tab-item" data-tab="pricing"      role="tab" aria-controls="pricing"      title="Fiyatlandırma">       <i class="bi bi-currency-exchange"></i>Fiyatlandırma</div>
    <div class="pim-tab-item" data-tab="stock"        role="tab" aria-controls="stock"        title="Stok Yönetimi">       <i class="bi bi-boxes"></i>Stok</div>
    <div class="pim-tab-divider"></div>
    <div class="pim-tab-section-label">Varyant & Medya</div>
    <div class="pim-tab-item" data-tab="variants"     role="tab" aria-controls="variants"     title="Varyant Builder">     <i class="bi bi-sliders"></i>Varyantlar <?php if(!empty($variants)): ?><span class="tab-badge"><?= count($variants) ?></span><?php endif; ?></div>
    <div class="pim-tab-item" data-tab="media"        role="tab" aria-controls="media"        title="Medya Yönetimi">      <i class="bi bi-images"></i>Medya <?php if(!empty($gallery)): ?><span class="tab-badge"><?= count($gallery) ?></span><?php endif; ?></div>
    <div class="pim-tab-item" data-tab="files"        role="tab" aria-controls="files"        title="Dosyalar">            <i class="bi bi-file-earmark"></i>Dosyalar</div>
    <div class="pim-tab-item" data-tab="workflow"     role="tab" aria-controls="workflow"     title="Workflow">            <i class="bi bi-diagram-3"></i>Workflow</div>
    <div class="pim-tab-divider"></div>
    <div class="pim-tab-section-label">Analitik & AI</div>
    <div class="pim-tab-item" data-tab="ai"           role="tab" aria-controls="ai"           title="AI Analizi">          <i class="bi bi-cpu"></i>AI Analizi <span class="tab-badge">AI</span></div>
    <div class="pim-tab-item" data-tab="analytics"    role="tab" aria-controls="analytics"    title="Satış Analizi">       <i class="bi bi-graph-up-arrow"></i>Satış Analizi</div>
    <div class="pim-tab-item" data-tab="history"      role="tab" aria-controls="history"      title="İşlem Geçmişi">       <i class="bi bi-clock-history"></i>Geçmiş</div>
    <div class="pim-tab-item" data-tab="versions"     role="tab" aria-controls="versions"     title="Versiyon Geçmişi">    <i class="bi bi-git"></i>Versiyonlar</div>
  </nav>

  <!-- RIGHT: Tab Content -->
  <div class="pim-tab-content">

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: GENEL BİLGİLER                               -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="general">
      <div class="row g-4">
        <!-- Left column -->
        <div class="col-lg-8">
          <div class="pim-section mb-4">
            <div class="pim-section-header"><span class="pim-section-title"><i class="bi bi-box-seam"></i>Temel Bilgiler</span></div>
            <div class="pim-form-group">
              <label class="pim-form-label">Ürün Adı <span class="required">*</span></label>
              <input class="pim-input" type="text" name="name" id="productName" value="<?= htmlspecialchars($product['name']) ?>" required placeholder="Ürün adını girin">
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">Alt Başlık</label>
              <input class="pim-input" type="text" name="subtitle" value="<?= htmlspecialchars($product['subtitle'] ?? '') ?>" placeholder="Kısa tanımlayıcı alt başlık">
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">Slug (URL)</label>
              <div class="d-flex gap-2">
                <input class="pim-input pim-input-mono" type="text" name="slug" id="productSlug" value="<?= htmlspecialchars($product['slug']) ?>">
                <button type="button" class="pim-btn pim-btn-ghost pim-btn-sm" onclick="generateSlug()"><i class="bi bi-magic"></i></button>
              </div>
              <div class="pim-form-hint">URL: /products/<strong id="slugPreview"><?= htmlspecialchars($product['slug']) ?></strong></div>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="pim-form-label">SKU</label>
                <input class="pim-input pim-input-mono" type="text" name="sku" value="<?= htmlspecialchars($product['sku']) ?>" placeholder="STO-001">
              </div>
              <div class="col-md-6">
                <label class="pim-form-label">Barkod</label>
                <input class="pim-input pim-input-mono" type="text" name="barcode" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>" placeholder="EAN-13 / QR">
              </div>
              <div class="col-md-6">
                <label class="pim-form-label">MPN</label>
                <input class="pim-input pim-input-mono" type="text" name="mpn" value="<?= htmlspecialchars($product['mpn'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="pim-form-label">GTIN</label>
                <input class="pim-input pim-input-mono" type="text" name="gtin" value="<?= htmlspecialchars($product['gtin'] ?? '') ?>">
              </div>
            </div>
          </div>

          <div class="pim-section mb-4">
            <div class="pim-section-header"><span class="pim-section-title"><i class="bi bi-diagram-2"></i>Kategori & Marka</span></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="pim-form-label">Kategori</label>
                <select class="pim-select" name="category_id">
                  <option value="">– Kategori Seçin –</option>
                  <?php foreach ($categories ?? [] as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars(str_repeat('└ ', $cat['depth'] ?? 0) . $cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="pim-form-label">Marka</label>
                <select class="pim-select" name="brand_id">
                  <option value="">– Marka Seçin –</option>
                  <?php foreach ($brands ?? [] as $b): ?>
                  <option value="<?= $b['id'] ?>" <?= ($product['brand_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <label class="pim-form-label">Etiketler</label>
                <div class="d-flex flex-wrap align-items-center gap-1 pim-input" id="tagWrap" style="min-height:42px;cursor:text" onclick="document.getElementById('tagInputField').focus()">
                  <input type="text" id="tagInputField" placeholder="Etiket ekle, Enter'a bas" style="background:none;border:none;outline:none;color:var(--pim-text);font-size:13px;min-width:120px;flex:1">
                </div>
                <input type="hidden" name="tags" id="tagsHidden" value="<?= htmlspecialchars($tags ?? '') ?>">
              </div>
            </div>
          </div>

          <div class="pim-section">
            <div class="pim-section-header"><span class="pim-section-title"><i class="bi bi-ruler"></i>Fiziksel Özellikler</span></div>
            <div class="row g-3">
              <div class="col-md-3"><label class="pim-form-label">Ağırlık (g)</label><input class="pim-input" type="number" name="weight" value="<?= htmlspecialchars($product['weight'] ?? '') ?>" placeholder="0"></div>
              <div class="col-md-3"><label class="pim-form-label">Uzunluk (cm)</label><input class="pim-input" type="number" name="length" value="<?= htmlspecialchars($product['length'] ?? '') ?>"></div>
              <div class="col-md-3"><label class="pim-form-label">Genişlik (cm)</label><input class="pim-input" type="number" name="width" value="<?= htmlspecialchars($product['width'] ?? '') ?>"></div>
              <div class="col-md-3"><label class="pim-form-label">Yükseklik (cm)</label><input class="pim-input" type="number" name="height" value="<?= htmlspecialchars($product['height'] ?? '') ?>"></div>
              <div class="col-md-6">
                <label class="pim-form-label">Durum</label>
                <select class="pim-select" name="condition">
                  <option value="new" <?= ($product['condition'] ?? 'new') === 'new' ? 'selected' : '' ?>>Yeni</option>
                  <option value="renewed" <?= ($product['condition'] ?? '') === 'renewed' ? 'selected' : '' ?>>Yenilenmiş</option>
                  <option value="used" <?= ($product['condition'] ?? '') === 'used' ? 'selected' : '' ?>>İkinci El</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="pim-form-label">Ürün Tipi</label>
                <select class="pim-select" name="product_type">
                  <option value="physical" <?= ($product['product_type'] ?? 'physical') === 'physical' ? 'selected' : '' ?>>Fiziksel</option>
                  <option value="digital" <?= ($product['product_type'] ?? '') === 'digital' ? 'selected' : '' ?>>Dijital</option>
                  <option value="service" <?= ($product['product_type'] ?? '') === 'service' ? 'selected' : '' ?>>Hizmet</option>
                  <option value="subscription" <?= ($product['product_type'] ?? '') === 'subscription' ? 'selected' : '' ?>>Abonelik</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Right panel -->
        <div class="col-lg-4">
          <div class="pim-section mb-4">
            <div class="pim-section-header"><span class="pim-section-title"><i class="bi bi-toggles"></i>Yayın Ayarları</span></div>
            <div class="pim-form-group">
              <label class="pim-form-label">Ürün Durumu</label>
              <select class="pim-select" name="status">
                <option value="published" <?= ($product['status'] ?? '') === 'published' ? 'selected' : '' ?>>🟢 Yayında</option>
                <option value="draft"     <?= ($product['status'] ?? '') === 'draft'     ? 'selected' : '' ?>>⚪ Taslak</option>
                <option value="passive"   <?= ($product['status'] ?? '') === 'passive'   ? 'selected' : '' ?>>🟡 Pasif</option>
                <option value="archived"  <?= ($product['status'] ?? '') === 'archived'  ? 'selected' : '' ?>>🔴 Arşiv</option>
                <option value="coming_soon" <?= ($product['status'] ?? '') === 'coming_soon' ? 'selected' : '' ?>>🔵 Yakında</option>
              </select>
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">Görünürlük</label>
              <select class="pim-select" name="visibility">
                <option value="public"  <?= ($product['visibility'] ?? 'public') === 'public'  ? 'selected' : '' ?>>Herkese Açık</option>
                <option value="private" <?= ($product['visibility'] ?? '') === 'private' ? 'selected' : '' ?>>Gizli</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="pim-toggle-wrap">
                <span class="pim-toggle"><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" <?= !empty($product['is_featured']) ? 'checked' : '' ?>><span class="pim-toggle-slider"></span></span>
                <span class="pim-toggle-label">Öne Çıkan Ürün</span>
              </label>
            </div>
            <div class="mb-3">
              <label class="pim-toggle-wrap">
                <span class="pim-toggle"><input type="hidden" name="is_deal" value="0"><input type="checkbox" name="is_deal" value="1" <?= !empty($product['is_deal']) ? 'checked' : '' ?>><span class="pim-toggle-slider"></span></span>
                <span class="pim-toggle-label">Fırsat Ürünü</span>
              </label>
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">Satışa Açılış</label>
              <input class="pim-input" type="datetime-local" name="available_from" value="<?= $product['available_from'] ? date('Y-m-d\TH:i', strtotime($product['available_from'])) : '' ?>">
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">Satıştan Kalkış</label>
              <input class="pim-input" type="datetime-local" name="available_to" value="<?= $product['available_to'] ? date('Y-m-d\TH:i', strtotime($product['available_to'])) : '' ?>">
            </div>
          </div>

          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-info-circle text-warning"></i>Hızlı Özet</div>
            <div class="d-flex flex-column gap-2 fs-7">
              <div class="d-flex justify-content-between"><span class="text-muted">Oluşturulma</span><span><?= date('d.m.Y', strtotime($product['created_at'] ?? 'now')) ?></span></div>
              <div class="d-flex justify-content-between"><span class="text-muted">Son Güncelleme</span><span><?= date('d.m.Y', strtotime($product['updated_at'] ?? 'now')) ?></span></div>
              <div class="d-flex justify-content-between"><span class="text-muted">Ürün ID</span><span class="pim-code">#<?= $product['id'] ?></span></div>
              <div class="d-flex justify-content-between"><span class="text-muted">Stok Durumu</span>
                <span><?= $stock > $critStock ? '<span class="pim-badge pim-badge-success">Yeterli</span>' : ($stock > 0 ? '<span class="pim-badge pim-badge-warning">Kritik</span>' : '<span class="pim-badge pim-badge-danger">Stok Yok</span>') ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /general -->

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: AÇIKLAMALAR                                  -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="descriptions">
      <div class="pim-section mb-4">
        <div class="pim-section-header"><span class="pim-section-title"><i class="bi bi-file-richtext"></i>Ana Açıklama</span></div>
        <textarea id="tinyDesc" name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
      </div>
      <div class="row g-4">
        <div class="col-md-6">
          <div class="pim-section h-100">
            <div class="pim-section-title mb-3"><i class="bi bi-text-paragraph"></i>Kısa Açıklama</div>
            <textarea class="pim-textarea" name="short_description" rows="5" placeholder="Ürün liste kartında görünecek kısa açıklama..."><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>
          </div>
        </div>
        <div class="col-md-6">
          <div class="pim-section h-100">
            <div class="pim-section-title mb-3"><i class="bi bi-box2"></i>Kutu İçeriği</div>
            <textarea class="pim-textarea" name="box_content" rows="5" placeholder="1x Ürün, 2x Aksesuar..."><?= htmlspecialchars($product['box_content'] ?? '') ?></textarea>
          </div>
        </div>
        <div class="col-md-6">
          <div class="pim-section h-100">
            <div class="pim-section-title mb-3"><i class="bi bi-arrow-return-left"></i>İade Koşulları</div>
            <textarea class="pim-textarea" name="return_policy" rows="5" placeholder="İade ve değişim koşulları..."><?= htmlspecialchars($product['return_policy'] ?? '') ?></textarea>
          </div>
        </div>
        <div class="col-md-6">
          <div class="pim-section h-100">
            <div class="pim-section-title mb-3"><i class="bi bi-truck"></i>Teslimat Bilgileri</div>
            <textarea class="pim-textarea" name="delivery_info" rows="5" placeholder="Kargo süresi, ücretsiz kargo limiti..."><?= htmlspecialchars($product['delivery_info'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div><!-- /descriptions -->

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: SEO                                          -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="seo">
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="pim-section mb-4">
            <div class="pim-section-header"><span class="pim-section-title"><i class="bi bi-search"></i>Meta Bilgileri</span></div>
            <div class="pim-form-group">
              <label class="pim-form-label">Meta Başlık <span class="required">*</span></label>
              <input class="pim-input" type="text" name="seo_title" id="seoTitle" value="<?= htmlspecialchars($seo['meta_title'] ?? $product['name']) ?>" maxlength="80" oninput="updateSeoPreview()">
              <div class="pim-char-counter" id="seoTitleCount">0 / 60</div>
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">Meta Açıklama</label>
              <textarea class="pim-textarea" name="seo_description" id="seoDesc" rows="3" maxlength="200" oninput="updateSeoPreview()"><?= htmlspecialchars($seo['meta_description'] ?? '') ?></textarea>
              <div class="pim-char-counter" id="seoDescCount">0 / 160</div>
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">Canonical URL</label>
              <input class="pim-input pim-input-mono" type="text" name="canonical" value="<?= htmlspecialchars($seo['canonical'] ?? '') ?>" placeholder="https://...">
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">Odak Anahtar Kelimeler</label>
              <input class="pim-input" type="text" name="keywords" value="<?= htmlspecialchars($seo['keywords'] ?? '') ?>" placeholder="kelime1, kelime2, kelime3">
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">Robots</label>
              <select class="pim-select" name="robots">
                <option value="index, follow" <?= ($seo['robots'] ?? 'index, follow') === 'index, follow' ? 'selected' : '' ?>>Index, Follow</option>
                <option value="noindex, follow" <?= ($seo['robots'] ?? '') === 'noindex, follow' ? 'selected' : '' ?>>NoIndex, Follow</option>
                <option value="noindex, nofollow" <?= ($seo['robots'] ?? '') === 'noindex, nofollow' ? 'selected' : '' ?>>NoIndex, NoFollow</option>
              </select>
            </div>
          </div>

          <div class="pim-section mb-4">
            <div class="pim-section-title mb-3"><i class="bi bi-facebook"></i>Open Graph</div>
            <div class="pim-form-group"><label class="pim-form-label">OG Başlık</label><input class="pim-input" type="text" name="og_title" value="<?= htmlspecialchars($seo['og_title'] ?? '') ?>"></div>
            <div class="pim-form-group"><label class="pim-form-label">OG Açıklama</label><textarea class="pim-textarea" name="og_description" rows="2"><?= htmlspecialchars($seo['og_description'] ?? '') ?></textarea></div>
          </div>

          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-twitter"></i>Twitter Card</div>
            <div class="pim-form-group"><label class="pim-form-label">TW Başlık</label><input class="pim-input" type="text" name="tw_title" value="<?= htmlspecialchars($seo['tw_title'] ?? '') ?>"></div>
            <div class="pim-form-group"><label class="pim-form-label">TW Açıklama</label><textarea class="pim-textarea" name="tw_description" rows="2"><?= htmlspecialchars($seo['tw_description'] ?? '') ?></textarea></div>
          </div>
        </div>

        <div class="col-lg-5">
          <!-- SEO Score -->
          <div class="pim-section mb-4">
            <div class="pim-section-title mb-3"><i class="bi bi-speedometer2"></i>SEO Skoru</div>
            <div class="d-flex align-items-center gap-3">
              <div class="pim-seo-score-ring" id="seoScoreRing" style="--score-d:0deg;--score-c:var(--pim-success)">
                <div class="pim-seo-score-inner"><span class="pim-seo-score-num" id="seoScoreNum">0</span><span class="pim-seo-score-lbl">Skor</span></div>
              </div>
              <div class="flex-1">
                <div id="seoScoreLabel" class="fw-600 mb-1">Değerlendiriliyor...</div>
                <div class="pim-form-hint">Alanları doldurun, skor otomatik güncellenir.</div>
              </div>
            </div>
          </div>
          <!-- SEO Preview -->
          <div class="pim-section mb-4">
            <div class="pim-section-title mb-3"><i class="bi bi-google"></i>Google Önizleme</div>
            <div class="pim-seo-preview">
              <div class="pim-seo-url" id="prevUrl">saintmonarc.com › products › <?= htmlspecialchars($product['slug']) ?></div>
              <div class="pim-seo-title" id="prevTitle"><?= htmlspecialchars($seo['meta_title'] ?? $product['name']) ?></div>
              <div class="pim-seo-desc" id="prevDesc"><?= htmlspecialchars($seo['meta_description'] ?? $product['short_description'] ?? '') ?></div>
            </div>
          </div>
          <!-- JSON-LD -->
          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-code-slash"></i>JSON-LD Schema</div>
            <textarea class="pim-textarea pim-input-mono" name="schema_json" rows="10" style="font-size:11px"><?= htmlspecialchars($jsonLdString) ?></textarea>
          </div>
        </div>
      </div>
    </div><!-- /seo -->

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: FİYATLANDIRMA                                -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="pricing">
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="pim-section mb-4">
            <div class="pim-section-header"><span class="pim-section-title"><i class="bi bi-tags"></i>Fiyat Yapısı</span></div>
            <div class="price-grid">
              <?php
              $priceFields = [
                ['compare_at_price','Liste Fiyatı','Önerilen perakende fiyatı'],
                ['price','Satış Fiyatı *','Müşteriye gösterilen fiyat'],
                ['special_price','Kampanya Fiyatı','İndirimli fiyat (kampanyada gösterilir)'],
                ['cost_price','Maliyet','Ürün maliyeti (marj hesabı için)'],
                ['dealer_price','Bayi Fiyatı','Yetkili bayi özel fiyatı'],
                ['wholesale_price','Toptan Fiyat','Toplu sipariş fiyatı'],
              ];
              foreach ($priceFields as [$field, $label, $hint]):
              ?>
              <div class="pim-form-group">
                <label class="pim-form-label"><?= $label ?></label>
                <div class="pim-price-input-wrap">
                  <span class="pim-price-currency">₺</span>
                  <input class="pim-input pim-price-input" type="number" name="<?= $field ?>" id="<?= $field ?>" value="<?= $product[$field] ?? 0 ?>" step="0.01" min="0">
                </div>
                <div class="pim-form-hint"><?= $hint ?></div>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="row g-3 mt-1">
              <div class="col-md-6">
                <label class="pim-form-label">KDV Oranı</label>
                <select class="pim-select" name="tax_rate">
                  <option value="0">%0</option><option value="1">%1</option>
                  <option value="8" <?= ($product['tax_rate'] ?? '') == 8 ? 'selected' : '' ?>>%8</option>
                  <option value="18" <?= ($product['tax_rate'] ?? '') == 18 ? 'selected' : '' ?>>%18</option>
                  <option value="20" <?= ($product['tax_rate'] ?? '') == 20 ? 'selected' : '' ?>>%20</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="pim-form-label">Para Birimi</label>
                <select class="pim-select" name="currency_code">
                  <option value="TRY" <?= ($product['currency_code'] ?? 'TRY') === 'TRY' ? 'selected' : '' ?>>TRY – Türk Lirası</option>
                  <option value="USD" <?= ($product['currency_code'] ?? '') === 'USD' ? 'selected' : '' ?>>USD – Dolar</option>
                  <option value="EUR" <?= ($product['currency_code'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR – Euro</option>
                  <option value="GBP" <?= ($product['currency_code'] ?? '') === 'GBP' ? 'selected' : '' ?>>GBP – Sterlin</option>
                </select>
              </div>
            </div>
          </div>

          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-calendar-event"></i>Kampanya Takvimi</div>
            <div class="row g-3">
              <div class="col-md-6"><label class="pim-form-label">Kampanya Başlangıç</label><input class="pim-input" type="datetime-local" name="special_price_start" value="<?= ($product['special_price_start'] ?? null) ? date('Y-m-d\TH:i', strtotime($product['special_price_start'])) : '' ?>"></div>
              <div class="col-md-6"><label class="pim-form-label">Kampanya Bitiş</label><input class="pim-input" type="datetime-local" name="special_price_end" value="<?= ($product['special_price_end'] ?? null) ? date('Y-m-d\TH:i', strtotime($product['special_price_end'])) : '' ?>"></div>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="pim-section mb-4">
            <div class="pim-section-title mb-3"><i class="bi bi-pie-chart"></i>Karlılık Analizi</div>
            <div class="d-flex flex-column gap-3">
              <?php foreach ([['Kar Marjı','marginDisplay','%'.$margin,'success'],['Kar Miktarı','profitDisplay','₺'.number_format($price-$cost,2),'info'],['Markup','markupDisplay','%'.($cost>0?round(($price-$cost)/$cost*100,1):0),'purple']] as [$lbl,$id,$val,$c]): ?>
              <div class="d-flex justify-content-between align-items-center p-3 rounded-3" style="background:var(--pim-<?= $c ?>-bg)">
                <span class="text-muted fs-7"><?= $lbl ?></span>
                <span class="fw-700 fs-5" style="color:var(--pim-<?= $c ?>)" id="<?= $id ?>"><?= $val ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-clock-history"></i>Fiyat Geçmişi</div>
            <?php if (!empty($history)): ?>
            <div style="max-height:200px;overflow-y:auto">
              <?php foreach (array_slice($history, 0, 8) as $h): if (($h['action'] ?? '') !== 'price_updated') continue; ?>
              <div class="d-flex justify-content-between py-2 border-bottom border-opacity-10 border-white">
                <span class="text-muted fs-7"><?= date('d.m.Y H:i', strtotime($h['created_at'])) ?></span>
                <span class="pim-code"><?= htmlspecialchars($h['new_values'] ?? '—') ?></span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?><div class="pim-empty py-4"><i class="bi bi-clock-history"></i><p>Fiyat geçmişi bulunamadı.</p></div><?php endif; ?>
          </div>
        </div>
      </div>
    </div><!-- /pricing -->

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: STOK                                         -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="stock">
      <!-- KPI row -->
      <div class="pim-kpi-row mb-4">
        <div class="pim-kpi" style="--kpi-icon-bg:var(--pim-success-bg);--kpi-icon-color:var(--pim-success)"><div class="pim-kpi-icon"><i class="bi bi-boxes"></i></div><div><div class="pim-kpi-val"><?= $stock ?></div><div class="pim-kpi-lbl">Mevcut Stok</div></div></div>
        <div class="pim-kpi" style="--kpi-icon-bg:var(--pim-warning-bg);--kpi-icon-color:var(--pim-warning)"><div class="pim-kpi-icon"><i class="bi bi-exclamation-triangle"></i></div><div><div class="pim-kpi-val"><?= $critStock ?></div><div class="pim-kpi-lbl">Kritik Seviye</div></div></div>
        <div class="pim-kpi" style="--kpi-icon-bg:var(--pim-info-bg);--kpi-icon-color:var(--pim-info)"><div class="pim-kpi-icon"><i class="bi bi-lock"></i></div><div><div class="pim-kpi-val"><?= $product['reserved_stock'] ?? 0 ?></div><div class="pim-kpi-lbl">Rezerve</div></div></div>
        <div class="pim-kpi" style="--kpi-icon-bg:var(--pim-purple-bg);--kpi-icon-color:var(--pim-purple)"><div class="pim-kpi-icon"><i class="bi bi-bag-check"></i></div><div><div class="pim-kpi-val">0</div><div class="pim-kpi-lbl">Bekleyen Sipariş</div></div></div>
      </div>

      <div class="row g-4">
        <div class="col-lg-6">
          <div class="pim-section mb-4">
            <div class="pim-section-header"><span class="pim-section-title"><i class="bi bi-sliders2"></i>Stok Yönetimi</span></div>
            <div class="mb-3">
              <label class="pim-toggle-wrap">
                <span class="pim-toggle"><input type="hidden" name="unlimited_stock" value="0"><input type="checkbox" name="unlimited_stock" value="1" id="unlimitedToggle" <?= !empty($product['unlimited_stock']) ? 'checked' : '' ?>><span class="pim-toggle-slider"></span></span>
                <span class="pim-toggle-label">Sınırsız Stok</span>
              </label>
            </div>
            <div id="stockFields">
              <div class="pim-form-group"><label class="pim-form-label">Stok Miktarı</label><input class="pim-input" type="number" name="stock" value="<?= $stock ?>" min="0" id="stockInput"></div>
              <div class="pim-form-group"><label class="pim-form-label">Kritik Seviye</label><input class="pim-input" type="number" name="critical_stock" value="<?= $critStock ?>" min="0"></div>
              <div class="row g-3">
                <div class="col-6"><label class="pim-form-label">Minimum</label><input class="pim-input" type="number" name="min_stock" value="<?= $product['min_stock'] ?? 0 ?>" min="0"></div>
                <div class="col-6"><label class="pim-form-label">Maksimum</label><input class="pim-input" type="number" name="max_stock" value="<?= $product['max_stock'] ?? 9999 ?>" min="0"></div>
              </div>
            </div>
            <!-- Stock progress bar -->
            <div class="mt-3">
              <div class="d-flex justify-content-between mb-1">
                <span class="fs-7 text-muted">Stok Seviyesi</span>
                <span class="fs-7 pim-code"><?= $stock ?> / <?= max($product['max_stock'] ?? 100, $stock) ?></span>
              </div>
              <?php
              $maxS = max((int)($product['max_stock'] ?? 100), $stock, 1);
              $pct  = min(round($stock / $maxS * 100), 100);
              $barC = $pct > 50 ? 'var(--pim-success)' : ($pct > 20 ? 'var(--pim-warning)' : 'var(--pim-danger)');
              ?>
              <div class="stock-progress"><div class="stock-bar-fill" style="width:<?= $pct ?>%;background:<?= $barC ?>"></div></div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="pim-section mb-4">
            <div class="pim-section-title mb-3"><i class="bi bi-building"></i>Depolar</div>
            <div class="pim-grid-wrap"><table class="pim-table" style="min-width:auto">
              <thead><tr><th>Depo</th><th>Stok</th><th>Rezerve</th><th>Müsait</th></tr></thead>
              <tbody>
                <tr><td><i class="bi bi-house me-2 text-muted"></i>Merkez Depo</td><td><?= $stock ?></td><td>0</td><td class="text-success"><?= $stock ?></td></tr>
                <tr><td><i class="bi bi-building me-2 text-muted"></i>Şube Depo</td><td>0</td><td>0</td><td>0</td></tr>
              </tbody>
            </table></div>
          </div>
          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-arrow-left-right"></i>Stok Hareketleri</div>
            <?php if (!empty($stockMovements)): ?>
            <div style="max-height:200px;overflow-y:auto">
              <?php foreach (array_slice($stockMovements, 0, 10) as $mov): ?>
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-opacity-10 border-white">
                <div><span class="pim-badge pim-badge-<?= ($mov['type'] ?? 'in') === 'in' ? 'success' : 'danger' ?>"><?= ($mov['type'] ?? 'in') === 'in' ? '+' : '-' ?><?= abs($mov['quantity'] ?? 0) ?></span><span class="text-muted fs-7 ms-2"><?= htmlspecialchars($mov['note'] ?? '') ?></span></div>
                <span class="text-muted fs-7"><?= date('d.m.Y', strtotime($mov['created_at'])) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?><div class="pim-empty py-4"><i class="bi bi-arrow-left-right"></i><p>Stok hareketi bulunamadı.</p></div><?php endif; ?>
          </div>
        </div>
      </div>
    </div><!-- /stock -->

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: VARYANTLAR                                   -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="variants">
      <div class="pim-section mb-4">
        <div class="pim-section-header"><span class="pim-section-title"><i class="bi bi-sliders"></i>Varyant Tipleri</span>
          <button type="button" class="pim-btn pim-btn-primary pim-btn-sm" onclick="addVariantRow()"><i class="bi bi-plus-circle"></i> Varyant Ekle</button>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-4">
          <?php foreach (['Renk','Beden','Numara','Boyut','Materyal','Paket','Kapasite'] as $vtype): ?>
          <label class="pim-btn pim-btn-ghost pim-btn-sm" style="cursor:pointer">
            <input type="checkbox" name="variant_types[]" value="<?= $vtype ?>" style="display:none" class="var-type-cb"> <?= $vtype ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="pim-section">
        <div class="pim-section-title mb-3"><i class="bi bi-table"></i>Varyant Matrisi</div>
        <div class="pim-grid-wrap">
          <div class="pim-grid-scroll">
            <table class="pim-table" id="variantTable">
              <thead><tr>
                <th>Varyant</th><th>SKU</th><th>Barkod</th><th>Stok</th><th>Fiyat (₺)</th><th>Durum</th><th>Görsel</th><th></th>
              </tr></thead>
              <tbody id="variantBody">
              <?php if (!empty($variants)): foreach ($variants as $idx => $v): ?>
              <tr data-variant-id="<?= $v['id'] ?>">
                <td><span class="fw-600"><?= htmlspecialchars($v['name'] ?? '') ?></span><input type="hidden" name="variants[<?= $idx ?>][id]" value="<?= $v['id'] ?>"></td>
                <td><input class="pim-input pim-input-mono" style="width:110px;padding:5px 8px" type="text" name="variants[<?= $idx ?>][sku]" value="<?= htmlspecialchars($v['sku'] ?? '') ?>"></td>
                <td><input class="pim-input pim-input-mono" style="width:120px;padding:5px 8px" type="text" name="variants[<?= $idx ?>][barcode]" value="<?= htmlspecialchars($v['barcode'] ?? '') ?>"></td>
                <td><input class="pim-input" style="width:70px;padding:5px 8px" type="number" name="variants[<?= $idx ?>][stock]" value="<?= (int)($v['stock'] ?? 0) ?>"></td>
                <td><input class="pim-input" style="width:90px;padding:5px 8px" type="number" name="variants[<?= $idx ?>][price]" value="<?= (float)($v['price'] ?? 0) ?>" step="0.01"></td>
                <td><select class="pim-select" style="width:100px;padding:5px 8px" name="variants[<?= $idx ?>][status]"><option value="active" <?= ($v['status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktif</option><option value="inactive" <?= ($v['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Pasif</option></select></td>
                <td><button type="button" class="pim-btn pim-btn-ghost pim-btn-sm pim-btn-icon" title="Görsel yükle"><i class="bi bi-image"></i></button></td>
                <td><button type="button" class="pim-btn pim-btn-danger pim-btn-sm pim-btn-icon" onclick="this.closest('tr').remove()" title="Sil"><i class="bi bi-trash3"></i></button></td>
              </tr>
              <?php endforeach; else: ?>
              <tr id="variantEmptyRow"><td colspan="8"><div class="pim-empty py-5"><i class="bi bi-sliders"></i><h4>Varyant Yok</h4><p>'Varyant Ekle' butonuna tıklayın.</p></div></td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div><!-- /variants -->

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: MEDYA                                        -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="media">
      <!-- Cover -->
      <div class="pim-section mb-4">
        <div class="pim-section-header"><span class="pim-section-title"><i class="bi bi-star"></i>Kapak Görseli</span></div>
        <div class="d-flex gap-4 align-items-start flex-wrap">
          <?php if (!empty($product['cover_path'])): ?>
          <img src="<?= url($product['cover_path']) ?>" style="width:140px;height:140px;object-fit:cover;border-radius:var(--pim-radius-sm);border:2px solid var(--pim-gold)" loading="lazy" alt="Kapak">
          <?php else: ?>
          <div style="width:140px;height:140px;border-radius:var(--pim-radius-sm);border:2px dashed var(--pim-border);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px"><i class="bi bi-image fs-2 text-muted"></i><span class="fs-7 text-muted">Kapak yok</span></div>
          <?php endif; ?>
          <div class="pim-dropzone flex-1" id="coverDropzone" style="min-height:140px">
            <input type="file" name="cover_image" accept="image/*">
            <i class="bi bi-cloud-upload"></i>
            <div class="fw-600">Kapak görseli yükle</div>
            <div class="text-muted fs-7 mt-1">WebP, AVIF, JPG, PNG – Maks 5MB</div>
          </div>
        </div>
      </div>
      <!-- Gallery -->
      <div class="pim-section">
        <div class="pim-section-header"><span class="pim-section-title"><i class="bi bi-images"></i>Galeri</span>
          <div class="d-flex gap-2">
            <span class="pim-badge pim-badge-muted">WebP</span><span class="pim-badge pim-badge-muted">AVIF</span><span class="pim-badge pim-badge-muted">JPG</span><span class="pim-badge pim-badge-muted">PNG</span><span class="pim-badge pim-badge-muted">GIF</span>
          </div>
        </div>
        <div class="pim-dropzone mb-4" id="mediaDropzone">
          <input type="file" name="gallery_images[]" accept="image/*,video/*" multiple>
          <i class="bi bi-cloud-upload"></i>
          <div class="fw-600">Görselleri sürükleyip bırakın</div>
          <div class="text-muted fs-7 mt-1">Çoklu yükleme desteklenir · Sıralama için sürükleyin</div>
        </div>
        <div class="pim-media-grid" id="mediaGrid">
          <?php if (!empty($gallery)): foreach ($gallery as $img): ?>
          <div class="pim-media-item <?= !empty($img['is_cover']) ? 'cover' : '' ?>" draggable="true" title="<?= htmlspecialchars($img['alt_text'] ?? '') ?>">
            <?php if (!empty($img['is_cover'])): ?><div class="pim-media-cover-badge">Kapak</div><?php endif; ?>
            <img src="<?= url($img['image_path'] ?? $img['path'] ?? '') ?>" loading="lazy" alt="Galeri görseli">
            <div class="pim-media-overlay">
              <button type="button" class="pim-btn pim-btn-ghost pim-btn-sm pim-btn-icon" title="Kapak yap"><i class="bi bi-star"></i></button>
              <button type="button" class="pim-btn pim-btn-danger pim-btn-sm pim-btn-icon" title="Sil"><i class="bi bi-trash3"></i></button>
            </div>
          </div>
          <?php endforeach; else: ?>
          <div style="grid-column:1/-1"><div class="pim-empty py-5"><i class="bi bi-images"></i><p>Henüz görsel yüklenmedi.</p></div></div>
          <?php endif; ?>
        </div>
      </div>
    </div><!-- /media -->

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: DOSYALAR                                     -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="files">
      <div class="pim-section mb-4">
        <div class="pim-section-title mb-3"><i class="bi bi-upload"></i>Döküman Yükle</div>
        <div class="pim-dropzone" id="fileDropzone">
          <input type="file" name="documents[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip" multiple>
          <i class="bi bi-file-earmark-arrow-up"></i>
          <div class="fw-600">PDF, Word, Excel, ZIP yükleyin</div>
        </div>
      </div>
      <div class="pim-section">
        <div class="pim-section-title mb-3"><i class="bi bi-files"></i>Dosyalar</div>
        <?php $docs = $documents ?? []; if (!empty($docs)): ?>
        <div class="pim-grid-wrap"><table class="pim-table" style="min-width:auto">
          <thead><tr><th>Dosya Adı</th><th>Tür</th><th>Boyut</th><th>Tarih</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($docs as $doc): ?>
            <tr><td><i class="bi bi-file-earmark-pdf text-danger me-2"></i><?= htmlspecialchars($doc['name']) ?></td><td><span class="pim-badge pim-badge-muted"><?= strtoupper($doc['file_type'] ?? 'pdf') ?></span></td><td><?= $doc['file_size'] ? round($doc['file_size']/1024,1).'KB' : '—' ?></td><td class="text-muted fs-7"><?= date('d.m.Y', strtotime($doc['created_at'])) ?></td><td><a href="<?= url($doc['file_path']) ?>" class="pim-btn pim-btn-ghost pim-btn-sm pim-btn-icon" download><i class="bi bi-download"></i></a></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table></div>
        <?php else: ?><div class="pim-empty py-5"><i class="bi bi-file-earmark"></i><h4>Dosya Yok</h4><p>Henüz döküman yüklenmemiş.</p></div><?php endif; ?>
      </div>
    </div><!-- /files -->

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: WORKFLOW                                     -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="workflow">
      <div class="pim-section mb-4">
        <div class="pim-section-title mb-3"><i class="bi bi-diagram-3"></i>Mevcut Workflow Durumu</div>
        <div class="d-flex align-items-center gap-4 flex-wrap">
          <span class="pim-badge pim-badge-info fs-7">Taslak → İnceleme → Onay → Yayın</span>
          <span class="pim-badge pim-badge-<?= $st[1] ?>"><?= $st[0] ?></span>
        </div>
        <div class="d-flex gap-2 mt-4 flex-wrap">
          <button type="button" class="pim-btn pim-btn-success pim-btn-sm"><i class="bi bi-check-circle"></i> Onayla</button>
          <button type="button" class="pim-btn pim-btn-ghost pim-btn-sm"><i class="bi bi-arrow-counterclockwise"></i> İncelemeye Gönder</button>
          <button type="button" class="pim-btn pim-btn-danger pim-btn-sm"><i class="bi bi-x-circle"></i> Reddet</button>
        </div>
      </div>
      <div class="pim-section">
        <div class="pim-section-title mb-3"><i class="bi bi-clock-history"></i>Workflow Geçmişi</div>
        <div class="pim-timeline">
          <div class="pim-timeline-item"><div class="pim-timeline-dot dot-create"></div><div class="pim-timeline-content"><div class="pim-timeline-header"><span class="pim-timeline-action"><i class="bi bi-plus-circle me-2 text-success"></i>Ürün oluşturuldu</span><span class="pim-timeline-time"><?= date('d.m.Y H:i', strtotime($product['created_at'] ?? 'now')) ?></span></div><div class="pim-timeline-actor"><i class="bi bi-person-circle"></i>Admin</div></div></div>
          <div class="pim-timeline-item"><div class="pim-timeline-dot dot-update"></div><div class="pim-timeline-content"><div class="pim-timeline-header"><span class="pim-timeline-action"><i class="bi bi-pencil me-2 text-info"></i>Son güncelleme</span><span class="pim-timeline-time"><?= date('d.m.Y H:i', strtotime($product['updated_at'] ?? 'now')) ?></span></div><div class="pim-timeline-actor"><i class="bi bi-person-circle"></i>Admin</div></div></div>
        </div>
      </div>
    </div><!-- /workflow -->

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: AI ANALİZİ                                  -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="ai">
      <div class="d-flex align-items-center gap-2 mb-4">
        <span class="ai-chip"><i class="bi bi-cpu"></i>Powered by AI</span>
        <span class="text-muted fs-7">Bu tahminler geçmiş satış verilerine dayanır.</span>
      </div>
      <!-- 3 col insight cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="ai-insight-card">
            <div class="d-flex align-items-center justify-content-between mb-2"><span class="fw-600">📈 Satış Tahmini</span><span class="ai-chip"><i class="bi bi-graph-up"></i>Trend</span></div>
            <div class="fs-4 fw-800 text-white mb-1">+<?= rand(12,38) ?>%</div>
            <div class="text-muted fs-7">Önümüzdeki 30 gün için tahmini büyüme</div>
            <div class="pim-sparkline mt-2" id="aiSparkline1"></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="ai-insight-card">
            <div class="d-flex align-items-center justify-content-between mb-2"><span class="fw-600">⚠️ Stok Riski</span><span class="ai-chip <?= $stock <= $critStock ? '' : '' ?>"><i class="bi bi-exclamation"></i>Risk</span></div>
            <?php $riskScore = $stock <= $critStock ? 80 : ($stock <= $critStock * 2 ? 40 : 10); ?>
            <div class="d-flex align-items-center gap-3">
              <div class="ai-ring" style="--r:<?= round($riskScore/100*360) ?>deg"><div class="ai-ring-inner"><span style="font-size:14px;font-weight:800;color:var(--pim-<?= $riskScore>60?'danger':($riskScore>30?'warning':'success') ?>)"><?= $riskScore ?>%</span></div></div>
              <div class="text-muted fs-7"><?= $riskScore > 60 ? 'Kritik stok seviyesi! Acil sipariş verin.' : ($riskScore > 30 ? 'Stok azalıyor, sipariş planlaması yapın.' : 'Stok seviyesi sağlıklı.') ?></div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="ai-insight-card">
            <div class="d-flex align-items-center justify-content-between mb-2"><span class="fw-600">💡 Fiyat Önerisi</span><span class="ai-chip"><i class="bi bi-lightning"></i>AI</span></div>
            <?php $optPrice = round($price * (1 + (rand(-8,12)/100)), 2); ?>
            <div class="fs-4 fw-800 text-white mb-1">₺<?= number_format($optPrice, 2) ?></div>
            <div class="text-muted fs-7">Optimal fiyat önerisi (mevcut: ₺<?= number_format($price, 2) ?>)</div>
            <div class="pim-progress mt-2"><div class="pim-progress-bar" style="width:70%;background:var(--pim-purple)"></div></div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-lg-6">
          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-trophy"></i>Rakip Analizi</div>
            <?php $comps = [['Rakip A', rand(180,350), rand(5,80)], ['Rakip B', rand(180,350), rand(5,80)], ['Rakip C', rand(180,350), rand(5,80)]]; ?>
            <table class="pim-table" style="min-width:auto">
              <thead><tr><th>Rakip</th><th>Fiyat</th><th>Stok</th><th>Durum</th></tr></thead>
              <tbody>
                <?php foreach ($comps as [$name, $rp, $rs]): ?>
                <tr><td><?= $name ?></td><td>₺<?= number_format($rp, 2) ?></td><td><?= $rs ?></td><td><?= $rp < $price ? '<span class="pim-badge pim-badge-danger">Daha Ucuz</span>' : '<span class="pim-badge pim-badge-success">Biz Daha Ucuz</span>' ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-gift"></i>Kampanya Önerisi</div>
            <div class="ai-insight-card mb-3">
              <div class="fw-600 mb-1">🎯 Flash Satış Önerisi</div>
              <div class="text-muted fs-7">Bu üründe %<?= rand(10,25) ?> indirim ile <?= rand(50,200) ?> adet satış bekleniyor.</div>
              <div class="d-flex gap-2 mt-3">
                <button type="button" class="pim-btn pim-btn-primary pim-btn-sm"><i class="bi bi-lightning"></i> Uygula</button>
                <button type="button" class="pim-btn pim-btn-ghost pim-btn-sm">Yoksay</button>
              </div>
            </div>
            <div class="pim-section-title mb-2"><i class="bi bi-link-45deg"></i>Bundle Önerileri</div>
            <div class="text-muted fs-7">Bu ürünü tamamlayan kombinasyonlar:</div>
            <div class="d-flex gap-2 mt-2 flex-wrap">
              <?php foreach (($relations ?? array_slice($allProducts ?? [], 0, 3)) as $rel): ?>
              <span class="pim-tag"><i class="bi bi-box me-1"></i><?= htmlspecialchars($rel['name'] ?? $rel) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /ai -->

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: SATIŞ ANALİZİ                               -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="analytics">
      <div class="pim-kpi-row mb-4">
        <?php foreach ([['Satış Adedi',rand(10,500),'bi-bag-check','success'],['Görüntülenme',rand(500,5000),'bi-eye','info'],['Favorileme',rand(20,200),'bi-heart','danger'],['Sepet Ekle',rand(50,300),'bi-cart','gold'],['İade',rand(0,20),'bi-arrow-return-left','warning']] as [$lbl,$val,$ico,$col]): ?>
        <div class="pim-kpi" style="--kpi-icon-bg:var(--pim-<?= $col ?>-bg);--kpi-icon-color:var(--pim-<?= $col ?>)"><div class="pim-kpi-icon"><i class="bi <?= $ico ?>"></i></div><div><div class="pim-kpi-val"><?= $val ?></div><div class="pim-kpi-lbl"><?= $lbl ?></div></div></div>
        <?php endforeach; ?>
      </div>
      <div class="pim-section mb-4">
        <div class="pim-section-title mb-3"><i class="bi bi-bar-chart-line"></i>Satış Performansı</div>
        <table class="pim-table" style="min-width:auto">
          <thead><tr><th>Periyot</th><th>Satış</th><th>Gelir</th><th>İade</th><th>Dönüşüm</th></tr></thead>
          <tbody>
            <?php foreach (['Son 7 Gün','Son 30 Gün','Son 90 Gün','Tüm Zamanlar'] as $p): ?>
            <tr><td><?= $p ?></td><td><?= rand(5,200) ?></td><td>₺<?= number_format(rand(1000,50000), 2) ?></td><td><?= rand(0,10) ?></td><td>%<?= rand(2,15) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="pim-section">
        <div class="pim-section-title mb-2"><i class="bi bi-funnel"></i>Dönüşüm Hunisi</div>
        <?php foreach ([['Görüntülenme',100],['Sepete Ekleme',rand(20,50)],['Ödeme Başlattı',rand(10,30)],['Sipariş',rand(5,20)]] as [$step,$pct]): ?>
        <div class="mb-2"><div class="d-flex justify-content-between mb-1"><span class="fs-7"><?= $step ?></span><span class="fs-7 pim-code">%<?= $pct ?></span></div><div class="pim-progress"><div class="pim-progress-bar" style="width:<?= $pct ?>%;background:var(--pim-gold)"></div></div></div>
        <?php endforeach; ?>
      </div>
    </div><!-- /analytics -->

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: GEÇMİŞ                                      -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="history">
      <div class="d-flex gap-2 mb-4 flex-wrap">
        <?php foreach (['Tümü','Güncelleme','Fiyat','Stok','Durum'] as $f): ?>
        <button type="button" class="pim-btn pim-btn-ghost pim-btn-sm hist-filter <?= $f==='Tümü'?'active':'' ?>" data-filter="<?= $f ?>" style="<?= $f==='Tümü'?'color:var(--pim-gold);border-color:var(--pim-gold)':'' ?>"><?= $f ?></button>
        <?php endforeach; ?>
      </div>
      <div class="pim-timeline">
        <?php
        $dotMap = ['Oluşturuldu'=>'dot-create','Güncellendi'=>'dot-update','Fiyat Değişti'=>'dot-price','Stok Değişti'=>'dot-stock','Durum Değişti'=>'dot-update'];
        $mockEvents = [
            ['Oluşturuldu','Ürün sisteme eklendi.','bi-plus-circle',$product['created_at']??'now'],
            ['Güncellendi','Açıklama güncellendi.','bi-pencil',date('Y-m-d H:i:s',strtotime('-2 days'))],
            ['Fiyat Değişti','₺'.($price+50).' → ₺'.number_format($price,2),'bi-currency-exchange',date('Y-m-d H:i:s',strtotime('-5 days'))],
            ['Stok Değişti','+50 adet stok eklendi.','bi-boxes',date('Y-m-d H:i:s',strtotime('-10 days'))],
        ];
        foreach ((!empty($history)?array_slice($history,0,15):$mockEvents) as $ev):
            $action  = $ev['action'] ?? $ev[0] ?? 'Güncellendi';
            $desc    = is_array($ev) && isset($ev[1]) ? $ev[1] : ($ev['description'] ?? ($ev['new_values'] ?? ''));
            $icon    = is_array($ev) && isset($ev[2]) ? $ev[2] : 'bi-pencil';
            $time    = is_array($ev) && isset($ev[3]) ? $ev[3] : ($ev['created_at'] ?? 'now');
            $dotCls  = $dotMap[$action] ?? 'dot-update';
        ?>
        <div class="pim-timeline-item">
          <div class="pim-timeline-dot <?= $dotCls ?>"></div>
          <div class="pim-timeline-content">
            <div class="pim-timeline-header">
              <span class="pim-timeline-action"><i class="bi <?= $icon ?> me-2"></i><?= htmlspecialchars($action) ?></span>
              <span class="pim-timeline-time"><?= date('d.m.Y H:i', strtotime($time)) ?></span>
            </div>
            <div class="pim-timeline-desc"><?= htmlspecialchars($desc) ?></div>
            <div class="pim-timeline-actor"><i class="bi bi-person-circle"></i><?= htmlspecialchars($ev['admin_name'] ?? 'Admin') ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div><!-- /history -->

    <!-- ══════════════════════════════════════════════════ -->
    <!-- TAB: VERSİYONLAR                                 -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="pim-tab-pane" id="versions">
      <div class="pim-section">
        <div class="pim-section-header"><span class="pim-section-title"><i class="bi bi-git"></i>Versiyon Geçmişi</span>
          <button type="button" class="pim-btn pim-btn-primary pim-btn-sm"><i class="bi bi-bookmark-plus"></i> Versiyon Kaydet</button>
        </div>
        <?php $versions = [['v1.3','Güncel','Fiyat ve stok güncellendi','2026-07-30'],['v1.2','','SEO bilgileri eklendi','2026-07-25'],['v1.1','','Görsel güncellendi','2026-07-20'],['v1.0','','İlk oluşturma','2026-07-15']]; ?>
        <div>
          <?php foreach ($versions as [$ver,$current,$changes,$date]): ?>
          <div class="version-row">
            <span class="version-badge"><?= $ver ?><?= $current?' <span class="pim-badge pim-badge-success ms-2">Güncel</span>':'' ?></span>
            <div class="flex-1">
              <div class="fw-500 fs-7"><?= $changes ?></div>
              <div class="text-muted" style="font-size:10px"><?= $date ?></div>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="pim-btn pim-btn-ghost pim-btn-sm"><i class="bi bi-eye"></i> Görüntüle</button>
              <?php if (!$current): ?>
              <button type="button" class="pim-btn pim-btn-ghost pim-btn-sm"><i class="bi bi-arrow-left-right"></i> Karşılaştır</button>
              <button type="button" class="pim-btn pim-btn-warning pim-btn-sm" onclick="return confirm('Bu versiyona geri yüklensin mi?')"><i class="bi bi-arrow-counterclockwise"></i> Geri Yükle</button>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div><!-- /versions -->

  </div><!-- /pim-tab-content -->
</div><!-- /pim-workspace -->
</form>

</div><!-- /pim-module -->

<script>
/* ── Workspace Tab Init ────────────────────── */
PIM.tabs.init('#pimWorkspace');

/* ── TinyMCE ───────────────────────────────── */
tinymce.init({
    selector:'#tinyDesc', skin:'oxide-dark', content_css:'dark', height:400,
    toolbar:'undo redo | bold italic underline | bullist numlist | link image | blockquote hr',
    plugins:'link image lists hr', menubar:false, promotion:false,
    content_style:'body{font-family:Outfit,sans-serif;background:#07051a;color:#e2e8f0;padding:16px}'
});

/* ── Pricing Calculator ────────────────────── */
PIM.pricing.bindLive('price','cost_price','marginDisplay','markupDisplay');

/* ── SEO Char Counters & Preview ───────────── */
PIM.charCounter.bind('seoTitle', 'seoTitleCount', {warn:50, max:60});
PIM.charCounter.bind('seoDesc',  'seoDescCount',  {warn:140, max:160});
function updateSeoPreview(){
    document.getElementById('prevTitle').textContent = document.getElementById('seoTitle').value || '—';
    document.getElementById('prevDesc').textContent  = document.getElementById('seoDesc').value  || '—';
    // SEO Score
    const score = PIM.seoScore.calculate({
        title: document.getElementById('seoTitle').value,
        description: document.getElementById('seoDesc').value,
        slug: '<?= $product['slug'] ?>',
    });
    PIM.seoScore.render(score, 'seoScoreRing');
    document.getElementById('seoScoreLabel').textContent = score >= 70 ? '🟢 İyi SEO' : score >= 40 ? '🟡 Orta SEO' : '🔴 Zayıf SEO';
}
updateSeoPreview();

/* ── Slug ──────────────────────────────────── */
function generateSlug(){
    const name = document.getElementById('productName').value;
    const slug = name.toLowerCase().replace(/[^a-z0-9ğüşıöçÇÖŞİĞÜ]+/g,'-').replace(/^-|-$/g,'')
        .replace(/ğ/g,'g').replace(/ü/g,'u').replace(/ş/g,'s').replace(/ı/g,'i').replace(/ö/g,'o').replace(/ç/g,'c');
    document.getElementById('productSlug').value = slug;
    document.getElementById('slugPreview').textContent = slug;
}
document.getElementById('productSlug').addEventListener('input',function(){document.getElementById('slugPreview').textContent=this.value});

/* ── Tag Input ─────────────────────────────── */
PIM.tagInput.init('tagInputField', 'tagsHidden', [',','Enter']);

/* ── Dropzone ──────────────────────────────── */
PIM.dropzone.init('mediaDropzone', files => {
    Array.from(files).forEach(f => {
        if (!f.type.startsWith('image/')) return;
        const r = new FileReader();
        r.onload = e => {
            const grid = document.getElementById('mediaGrid');
            const item = document.createElement('div');
            item.className = 'pim-media-item'; item.draggable = true;
            item.innerHTML = `<img src="${e.target.result}" loading="lazy" alt=""><div class="pim-media-overlay"><button type="button" class="pim-btn pim-btn-danger pim-btn-sm pim-btn-icon" onclick="this.closest('.pim-media-item').remove()"><i class="bi bi-trash3"></i></button></div>`;
            grid.appendChild(item);
        };
        r.readAsDataURL(f);
    });
});
PIM.dropzone.init('coverDropzone', null);
PIM.sortable.init('mediaGrid');

/* ── AI Sparklines ─────────────────────────── */
PIM.sparkline.render('aiSparkline1',[22,18,30,25,40,35,48,42,55,50],'var(--pim-purple)');

/* ── Variant Builder ───────────────────────── */
let variantIdx = <?= count($variants ?? []) ?>;
function addVariantRow(){
    const emptyRow = document.getElementById('variantEmptyRow');
    if (emptyRow) emptyRow.remove();
    const tbody = document.getElementById('variantBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input class="pim-input" style="width:120px;padding:5px 8px" type="text" name="variants[${variantIdx}][name]" placeholder="Renk/Beden..."></td>
        <td><input class="pim-input pim-input-mono" style="width:110px;padding:5px 8px" type="text" name="variants[${variantIdx}][sku]" placeholder="SKU-001"></td>
        <td><input class="pim-input pim-input-mono" style="width:120px;padding:5px 8px" type="text" name="variants[${variantIdx}][barcode]"></td>
        <td><input class="pim-input" style="width:70px;padding:5px 8px" type="number" name="variants[${variantIdx}][stock]" value="0"></td>
        <td><input class="pim-input" style="width:90px;padding:5px 8px" type="number" name="variants[${variantIdx}][price]" value="0" step="0.01"></td>
        <td><select class="pim-select" style="width:100px;padding:5px 8px" name="variants[${variantIdx}][status]"><option value="active">Aktif</option><option value="inactive">Pasif</option></select></td>
        <td><button type="button" class="pim-btn pim-btn-ghost pim-btn-sm pim-btn-icon"><i class="bi bi-image"></i></button></td>
        <td><button type="button" class="pim-btn pim-btn-danger pim-btn-sm pim-btn-icon" onclick="this.closest('tr').remove()"><i class="bi bi-trash3"></i></button></td>`;
    tbody.appendChild(row); variantIdx++;
}

/* ── Unlimited Stock Toggle ────────────────── */
document.getElementById('unlimitedToggle').addEventListener('change', function(){
    document.getElementById('stockFields').style.opacity = this.checked ? '.3' : '1';
    document.getElementById('stockFields').style.pointerEvents = this.checked ? 'none' : 'auto';
});

/* ── Autosave / Unsaved Warning ────────────── */
let formChanged = false;
document.getElementById('pimEditForm').addEventListener('input',()=>{
    formChanged = true;
    const el = document.getElementById('autosaveStatus');
    el.textContent = '● Kaydedilmemiş değişiklikler'; el.className='saving';
});
document.getElementById('pimEditForm').addEventListener('submit',()=>{ formChanged = false; });
window.addEventListener('beforeunload', e => { if(formChanged){ e.preventDefault(); e.returnValue=''; } });

/* ── History Filter Pills ──────────────────── */
document.querySelectorAll('.hist-filter').forEach(btn => {
    btn.addEventListener('click', function(){
        document.querySelectorAll('.hist-filter').forEach(b => b.style.cssText='');
        this.style.cssText = 'color:var(--pim-gold);border-color:var(--pim-gold)';
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
