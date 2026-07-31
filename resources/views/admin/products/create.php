<?php
use App\Helpers\ComponentHelper;
$title = 'PIM – Yeni Ürün Ekle | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
/* ── Wizard specifics ───────────────────────── */
.pim-wizard-wrap{background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius-lg);overflow:hidden}
.pim-wizard-body{padding:36px 40px;min-height:460px}
.wiz-tip{background:linear-gradient(135deg,var(--pim-gold-glow),transparent);border:1px solid var(--pim-gold-solid);border-radius:var(--pim-radius);padding:16px 20px;display:flex;gap:12px;align-items:flex-start;margin-top:20px}
.wiz-tip i{font-size:22px;color:var(--pim-gold);flex-shrink:0;margin-top:2px}
.brand-card-opt{border:1.5px solid var(--pim-border);border-radius:var(--pim-radius-sm);padding:14px 18px;cursor:pointer;transition:var(--pim-transition);display:flex;align-items:center;gap:12px}
.brand-card-opt:hover,.brand-card-opt.selected{border-color:var(--pim-gold);background:var(--pim-gold-glow);color:var(--pim-gold)}
.brand-card-opt input{display:none}
.pub-opt{border:1.5px solid var(--pim-border);border-radius:var(--pim-radius-sm);padding:16px 20px;cursor:pointer;transition:var(--pim-transition)}
.pub-opt:hover,.pub-opt.selected{border-color:var(--pim-gold);background:var(--pim-gold-glow)}
.pub-opt input{display:none}
.checklist-item{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--pim-border);font-size:13px}
.checklist-item:last-child{border:none}
.step-complete-badge{width:22px;height:22px;border-radius:50%;background:var(--pim-success-bg);border:1.5px solid var(--pim-success);color:var(--pim-success);display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0}
.step-pending-badge{width:22px;height:22px;border-radius:50%;background:rgba(255,255,255,.05);border:1.5px solid var(--pim-border);display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;color:var(--pim-text-xs)}
.preview-card{background:var(--pim-surface);border:1px solid var(--pim-border);border-radius:var(--pim-radius);overflow:hidden}
.preview-img{width:100%;aspect-ratio:1;object-fit:cover;background:rgba(255,255,255,.03);display:flex;align-items:center;justify-content:center}
@media(max-width:768px){.pim-wizard-body{padding:20px 16px}.pim-stepper{padding:12px 10px}}
</style>

<div class="pim-module">

<!-- ─── Header ───────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
  <div>
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Ürünler' => url('/admin/products'), 'Yeni Ürün' => '#']) ?>
    <h2 class="text-white fw-bold m-0 mt-1" style="font-size:22px"><i class="bi bi-plus-circle me-2 text-pim-gold"></i>Yeni Ürün Ekle</h2>
  </div>
  <a href="<?= url('/admin/products') ?>" class="pim-btn pim-btn-secondary"><i class="bi bi-arrow-left"></i> Ürün Listesine Dön</a>
</div>

<form id="pimCreateForm" action="<?= url('/admin/products/store') ?>" method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

<div class="pim-wizard-wrap" id="pimCreateWizard">
  <!-- ── Stepper ────────────────────────────────────── -->
  <div class="pim-stepper" role="tablist">
    <?php
    $steps = [
        'Genel Bilgiler','Kategori','Marka','Varyantlar',
        'Fiyat','Stok','Medya','SEO','Önizleme','Yayınla'
    ];
    foreach ($steps as $i => $s):
        $n = $i + 1;
    ?>
    <div class="pim-step <?= $n===1?'active':'' ?>" id="dot<?= $n ?>">
      <div class="pim-step-num"><?= $n ?></div>
      <span class="d-none d-md-inline"><?= $s ?></span>
    </div>
    <?php if ($n < count($steps)): ?><div class="pim-step-connector" id="conn<?= $n ?>"></div><?php endif; endforeach; ?>
  </div>

  <!-- ── Wizard Body ───────────────────────────────── -->
  <div class="pim-wizard-body">

    <!-- STEP 1: GENEL BİLGİLER -->
    <div class="pim-wizard-step active" id="wstep1">
      <h4 class="fw-700 text-white mb-4"><i class="bi bi-info-circle text-pim-gold me-2"></i>Genel Bilgiler</h4>
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="pim-form-group">
            <label class="pim-form-label">Ürün Adı <span class="required">*</span></label>
            <input class="pim-input" style="font-size:16px;padding:14px" type="text" name="name" id="wizProductName" required placeholder="Örn: Premium Deri Cüzdan – Kahverengi">
          </div>
          <div class="pim-form-group">
            <label class="pim-form-label">Alt Başlık</label>
            <input class="pim-input" type="text" name="subtitle" placeholder="Ürünü özetleyen kısa başlık">
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="pim-form-label">Ürün Tipi</label>
              <select class="pim-select" name="product_type">
                <option value="physical">Fiziksel</option><option value="digital">Dijital</option>
                <option value="service">Hizmet</option><option value="subscription">Abonelik</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="pim-form-label">Durum</label>
              <select class="pim-select" name="condition">
                <option value="new">Yeni</option><option value="renewed">Yenilenmiş</option><option value="used">İkinci El</option>
              </select>
            </div>
          </div>
          <div class="pim-form-group">
            <label class="pim-form-label">Kısa Açıklama</label>
            <textarea class="pim-textarea" name="short_description" rows="4" placeholder="Ürün liste kartında görünecek kısa açıklama..."></textarea>
          </div>
          <div class="pim-form-group">
            <label class="pim-form-label">Ana Açıklama</label>
            <textarea id="wizDesc" name="description"></textarea>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="wiz-tip">
            <i class="bi bi-lightbulb-fill"></i>
            <div><strong class="text-white">Pro İpucu!</strong><p class="text-muted fs-7 mb-0 mt-1">İyi bir ürün adı ve açıklama satışları <strong class="text-success">%40'a kadar artırır</strong>. Anahtar kelimeleri doğal kullanın.</p></div>
          </div>
          <div class="pim-section mt-3">
            <div class="pim-section-title mb-3"><i class="bi bi-eye"></i>Canlı Önizleme</div>
            <div style="background:rgba(255,255,255,.02);border-radius:10px;padding:14px">
              <div class="fw-700 text-white" id="wizNamePreview" style="font-size:15px">Ürün adı buraya gelecek...</div>
              <div class="text-muted fs-7 mt-1" id="wizDescPreview">Açıklama buraya gelecek...</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 2: KATEGORİ -->
    <div class="pim-wizard-step" id="wstep2">
      <h4 class="fw-700 text-white mb-4"><i class="bi bi-diagram-2 text-pim-gold me-2"></i>Kategori Seçimi</h4>
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="pim-form-group">
            <label class="pim-form-label">Ana Kategori <span class="required">*</span></label>
            <select class="pim-select" name="category_id" id="wizCategory" style="font-size:15px;padding:12px">
              <option value="">– Kategori Seçin –</option>
              <?php foreach ($categories ?? [] as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= htmlspecialchars(str_repeat('└ ', $cat['depth'] ?? 0) . $cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="pim-form-group">
            <label class="pim-form-label">Etiketler</label>
            <div class="d-flex flex-wrap align-items-center gap-1 pim-input" id="wizTagWrap" style="min-height:44px;cursor:text" onclick="document.getElementById('wizTagInput').focus()">
              <input type="text" id="wizTagInput" placeholder="Etiket ekle, Enter'a bas" style="background:none;border:none;outline:none;color:var(--pim-text);font-size:13px;flex:1;min-width:120px">
            </div>
            <input type="hidden" name="tags" id="wizTagsHidden">
          </div>
        </div>
        <div class="col-lg-5">
          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-tree"></i>Kategori Yolu</div>
            <div id="catPathPreview" class="text-muted fs-7">Kategori seçilmedi.</div>
          </div>
          <div class="wiz-tip mt-3">
            <i class="bi bi-tags-fill"></i>
            <div><strong class="text-white">Kategori Önemi</strong><p class="text-muted fs-7 mb-0 mt-1">Doğru kategori ürününüzün arama sonuçlarında üst sıralarda yer almasını sağlar.</p></div>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 3: MARKA -->
    <div class="pim-wizard-step" id="wstep3">
      <h4 class="fw-700 text-white mb-4"><i class="bi bi-award text-pim-gold me-2"></i>Marka Seçimi</h4>
      <div class="row g-3" id="brandCards">
        <div class="col-12">
          <label class="brand-card-opt selected" id="noBrandCard">
            <input type="radio" name="brand_id" value="" checked>
            <i class="bi bi-slash-circle fs-4 text-muted"></i>
            <div><div class="fw-600">Markasız</div><div class="text-muted fs-7">Belirli bir marka yok</div></div>
          </label>
        </div>
        <?php foreach ($brands ?? [] as $b): ?>
        <div class="col-md-6 col-lg-4">
          <label class="brand-card-opt" onclick="selectBrand(this)">
            <input type="radio" name="brand_id" value="<?= $b['id'] ?>">
            <div style="width:36px;height:36px;background:rgba(255,255,255,.05);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <?php if (!empty($b['logo'])): ?><img src="<?= url($b['logo']) ?>" style="width:28px;height:28px;object-fit:contain"><?php else: ?><i class="bi bi-building text-muted"></i><?php endif; ?>
            </div>
            <div><div class="fw-600"><?= htmlspecialchars($b['name']) ?></div><div class="text-muted fs-7"><?= htmlspecialchars($b['slug'] ?? '') ?></div></div>
          </label>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- STEP 4: VARYANTLAR -->
    <div class="pim-wizard-step" id="wstep4">
      <h4 class="fw-700 text-white mb-4"><i class="bi bi-sliders text-pim-gold me-2"></i>Varyant Ayarları</h4>
      <div class="mb-4">
        <label class="pim-toggle-wrap mb-3">
          <span class="pim-toggle"><input type="checkbox" id="hasVariantsToggle" onchange="toggleVariantBuilder(this.checked)"><span class="pim-toggle-slider"></span></span>
          <span class="pim-toggle-label fw-600">Bu ürünün varyantları var</span>
        </label>
        <div class="text-muted fs-7">Renk, beden, numara gibi seçenekler için varyantları etkinleştirin.</div>
      </div>
      <div id="variantBuilderSection" style="display:none">
        <div class="pim-section mb-4">
          <div class="pim-section-title mb-3">Varyant Tipleri</div>
          <div class="d-flex flex-wrap gap-2" id="varTypePills">
            <?php foreach (['Renk','Beden','Numara','Boyut','Materyal','Paket','Kapasite'] as $vt): ?>
            <label class="pim-btn pim-btn-ghost pim-btn-sm vtype-pill" data-type="<?= $vt ?>">
              <input type="checkbox" name="variant_types[]" value="<?= $vt ?>" style="display:none" onchange="handleVarType(this)"> <?= $vt ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div id="varTypeInputs"></div>
        <div class="pim-section mt-4">
          <div class="pim-section-title mb-3">Varyant Kombinasyonları</div>
          <div id="varCombinations"><div class="pim-empty py-4"><i class="bi bi-sliders"></i><p>Varyant tipi seçip değerleri girin.</p></div></div>
        </div>
      </div>
      <div id="noVariantMsg">
        <div class="wiz-tip"><i class="bi bi-box-seam-fill"></i><div><strong class="text-white">Tekli Ürün</strong><p class="text-muted fs-7 mb-0 mt-1">Varyant olmadan tek tip ürün olarak kaydedilecek.</p></div></div>
      </div>
    </div>

    <!-- STEP 5: FİYAT -->
    <div class="pim-wizard-step" id="wstep5">
      <h4 class="fw-700 text-white mb-4"><i class="bi bi-currency-exchange text-pim-gold me-2"></i>Fiyatlandırma</h4>
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="pim-section mb-4">
            <div class="pim-section-title mb-3">Fiyat Yapısı</div>
            <div class="row g-3">
              <?php foreach ([['list_price','Liste Fiyatı'],['price','Satış Fiyatı *'],['sale_price','Kampanya Fiyatı'],['cost_price','Maliyet'],['dealer_price','Bayi Fiyatı'],['wholesale_price','Toptan Fiyat']] as [$f,$l]): ?>
              <div class="col-md-6">
                <label class="pim-form-label"><?= $l ?></label>
                <div class="pim-price-input-wrap"><span class="pim-price-currency">₺</span><input class="pim-input pim-price-input" type="number" name="<?= $f ?>" id="wiz_<?= $f ?>" value="0" step="0.01" min="0"></div>
              </div>
              <?php endforeach; ?>
              <div class="col-md-6">
                <label class="pim-form-label">KDV Oranı</label>
                <select class="pim-select" name="tax_rate"><option value="0">%0</option><option value="1">%1</option><option value="8" selected>%8</option><option value="18">%18</option><option value="20">%20</option></select>
              </div>
              <div class="col-md-6">
                <label class="pim-form-label">Para Birimi</label>
                <select class="pim-select" name="currency_code"><option value="TRY" selected>TRY</option><option value="USD">USD</option><option value="EUR">EUR</option><option value="GBP">GBP</option></select>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="pim-section mb-3">
            <div class="pim-section-title mb-3"><i class="bi bi-pie-chart"></i>Karlılık Analizi</div>
            <div class="d-flex flex-column gap-3">
              <div class="d-flex justify-content-between align-items-center p-3 rounded-3" style="background:var(--pim-success-bg)"><span class="text-muted fs-7">Kar Marjı</span><span class="fw-700 fs-4 text-success" id="wizMargin">%0</span></div>
              <div class="d-flex justify-content-between align-items-center p-3 rounded-3" style="background:var(--pim-purple-bg)"><span class="text-muted fs-7">Markup</span><span class="fw-700 fs-4" style="color:var(--pim-purple)" id="wizMarkup">%0</span></div>
            </div>
          </div>
          <div class="wiz-tip">
            <i class="bi bi-graph-up-fill"></i>
            <div><strong class="text-white">Fiyat Stratejisi</strong><p class="text-muted fs-7 mb-0 mt-1">Rakip fiyatlarını araştırın. %30+ kar marjı sürdürülebilir büyüme için idealdir.</p></div>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 6: STOK -->
    <div class="pim-wizard-step" id="wstep6">
      <h4 class="fw-700 text-white mb-4"><i class="bi bi-boxes text-pim-gold me-2"></i>Stok & Tanımlayıcılar</h4>
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="pim-section mb-4">
            <div class="pim-section-title mb-3">Stok Bilgileri</div>
            <div class="mb-3">
              <label class="pim-toggle-wrap">
                <span class="pim-toggle"><input type="hidden" name="unlimited_stock" value="0"><input type="checkbox" name="unlimited_stock" value="1" id="wizUnlimitedToggle" onchange="toggleStockField(this.checked)"><span class="pim-toggle-slider"></span></span>
                <span class="pim-toggle-label">Sınırsız Stok</span>
              </label>
            </div>
            <div id="wizStockFields">
              <div class="pim-form-group"><label class="pim-form-label">Başlangıç Stoğu</label><input class="pim-input" type="number" name="stock" id="wizStock" value="0" min="0"></div>
              <div class="row g-3">
                <div class="col-4"><label class="pim-form-label">Kritik</label><input class="pim-input" type="number" name="critical_stock" value="5" min="0"></div>
                <div class="col-4"><label class="pim-form-label">Min</label><input class="pim-input" type="number" name="min_stock" value="0" min="0"></div>
                <div class="col-4"><label class="pim-form-label">Max</label><input class="pim-input" type="number" name="max_stock" value="9999" min="0"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="pim-section">
            <div class="pim-section-title mb-3">Tanımlayıcılar</div>
            <div class="pim-form-group">
              <label class="pim-form-label">SKU</label>
              <div class="d-flex gap-2">
                <input class="pim-input pim-input-mono" type="text" name="sku" id="wizSku" required placeholder="STO-001">
                <button type="button" class="pim-btn pim-btn-ghost pim-btn-sm" onclick="genSKU()"><i class="bi bi-magic"></i></button>
              </div>
            </div>
            <div class="pim-form-group"><label class="pim-form-label">Barkod</label><input class="pim-input pim-input-mono" type="text" name="barcode" placeholder="EAN-13"></div>
            <div class="pim-form-group"><label class="pim-form-label">MPN</label><input class="pim-input pim-input-mono" type="text" name="mpn"></div>
            <div class="pim-form-group"><label class="pim-form-label">GTIN</label><input class="pim-input pim-input-mono" type="text" name="gtin"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 7: MEDYA -->
    <div class="pim-wizard-step" id="wstep7">
      <h4 class="fw-700 text-white mb-4"><i class="bi bi-images text-pim-gold me-2"></i>Medya</h4>
      <div class="row g-4">
        <div class="col-lg-5">
          <div class="pim-section mb-4">
            <div class="pim-section-title mb-3"><i class="bi bi-star"></i>Kapak Görseli</div>
            <div class="pim-dropzone" id="wizCoverDrop" style="min-height:200px">
              <input type="file" name="cover_image" accept="image/*" id="wizCoverInput" onchange="previewCover(this)">
              <i class="bi bi-cloud-upload"></i>
              <div class="fw-600">Kapak görseli yükle</div>
              <div class="text-muted fs-7 mt-1">Önerilen: 800×800px</div>
            </div>
            <div id="wizCoverPreview" style="display:none;margin-top:12px"><img id="wizCoverImg" style="width:100%;border-radius:10px;aspect-ratio:1;object-fit:cover" alt="Kapak"></div>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-images"></i>Galeri Görselleri</div>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <span class="pim-badge pim-badge-muted">WebP</span><span class="pim-badge pim-badge-muted">AVIF</span><span class="pim-badge pim-badge-muted">JPG</span><span class="pim-badge pim-badge-muted">PNG</span><span class="pim-badge pim-badge-muted">GIF</span>
            </div>
            <div class="pim-dropzone" id="wizGalleryDrop">
              <input type="file" name="gallery_images[]" accept="image/*" multiple id="wizGalleryInput" onchange="previewGallery(this.files)">
              <i class="bi bi-cloud-upload"></i>
              <div class="fw-600">Galeri görsellerini yükle</div>
              <div class="text-muted fs-7 mt-1">Çoklu yükleme desteklenir</div>
            </div>
            <div class="pim-media-grid mt-3" id="wizGalleryGrid"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 8: SEO -->
    <div class="pim-wizard-step" id="wstep8">
      <h4 class="fw-700 text-white mb-4"><i class="bi bi-search-heart text-pim-gold me-2"></i>SEO Ayarları</h4>
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="pim-section mb-4">
            <div class="pim-section-title mb-3">Meta Bilgileri</div>
            <div class="pim-form-group">
              <label class="pim-form-label">Meta Başlık</label>
              <input class="pim-input" type="text" name="seo_title" id="wizSeoTitle" maxlength="80" placeholder="Ürün başlığı – SaintMonarc" oninput="updateWizSeoPreview()">
              <div class="pim-char-counter" id="wizTitleCount">0 / 60</div>
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">Meta Açıklama</label>
              <textarea class="pim-textarea" name="seo_description" id="wizSeoDesc" rows="3" maxlength="200" placeholder="Ürün açıklaması..." oninput="updateWizSeoPreview()"></textarea>
              <div class="pim-char-counter" id="wizDescCount">0 / 160</div>
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">Slug (Otomatik)</label>
              <input class="pim-input pim-input-mono" type="text" name="slug" id="wizSlug" placeholder="urun-adi">
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">OG Başlık</label>
              <input class="pim-input" type="text" name="og_title">
            </div>
            <div class="pim-form-group">
              <label class="pim-form-label">Robots</label>
              <select class="pim-select" name="robots"><option value="index, follow">Index, Follow</option><option value="noindex, follow">NoIndex, Follow</option><option value="noindex, nofollow">NoIndex, NoFollow</option></select>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="pim-section mb-4">
            <div class="pim-section-title mb-3"><i class="bi bi-google"></i>Google Önizleme</div>
            <div class="pim-seo-preview">
              <div class="pim-seo-url" id="wizPrevUrl">saintmonarc.com › products › ...</div>
              <div class="pim-seo-title" id="wizPrevTitle">Ürün başlığı buraya gelecek</div>
              <div class="pim-seo-desc" id="wizPrevDesc">Meta açıklama buraya gelecek.</div>
            </div>
          </div>
          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-speedometer2"></i>SEO Skoru</div>
            <div class="d-flex align-items-center gap-3">
              <div class="pim-seo-score-ring" id="wizSeoRing" style="--score-d:0deg;--score-c:var(--pim-danger)"><div class="pim-seo-score-inner"><span class="pim-seo-score-num" id="wizSeoNum">0</span><span class="pim-seo-score-lbl">Skor</span></div></div>
              <div class="text-muted fs-7" id="wizSeoLabel">Alanları doldurun...</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 9: ÖNİZLEME -->
    <div class="pim-wizard-step" id="wstep9">
      <h4 class="fw-700 text-white mb-4"><i class="bi bi-eye text-pim-gold me-2"></i>Ürün Önizlemesi</h4>
      <div class="text-muted fs-7 mb-4">Lütfen tüm bilgileri kontrol edin. Değiştirmek istediğiniz adıma geri dönebilirsiniz.</div>
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="preview-card">
            <div class="preview-img" id="previewImgWrap"><i class="bi bi-image fs-1 text-muted"></i></div>
            <div class="p-3">
              <div class="fw-700 text-white fs-6 mb-1" id="previewName">—</div>
              <div class="text-muted fs-7 mb-2" id="previewDesc">—</div>
              <div class="d-flex justify-content-between">
                <span class="fw-700 text-pim-gold" id="previewPrice">₺0,00</span>
                <span class="pim-badge pim-badge-muted" id="previewStock">Stok: 0</span>
              </div>
              <div class="d-flex gap-2 mt-2 flex-wrap">
                <span class="pim-code" id="previewSku">SKU: —</span>
                <span class="pim-badge pim-badge-info" id="previewCat">Kategori: —</span>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-check-all"></i>Kontrol Listesi</div>
            <div id="previewChecklist">
              <?php foreach ($steps as $i => $s): $n = $i+1; ?>
              <div class="checklist-item"><span class="step-pending-badge" id="chk<?= $n ?>"><?= $n ?></span><span>Adım <?= $n ?>: <?= $s ?></span><a href="#" class="ms-auto pim-btn pim-btn-ghost pim-btn-sm" onclick="goToStep(<?= $n ?>);return false"><i class="bi bi-pencil"></i></a></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 10: YAYINLA -->
    <div class="pim-wizard-step" id="wstep10">
      <h4 class="fw-700 text-white mb-4"><i class="bi bi-rocket-takeoff text-pim-gold me-2"></i>Yayın Ayarları</h4>
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="pim-section mb-4">
            <div class="pim-section-title mb-3">Yayın Seçeneği</div>
            <div class="d-flex flex-column gap-3">
              <?php
              $pubOpts = [
                  ['published','Hemen Yayınla','Ürün hemen mağazada görünsün','bi-rocket'],
                  ['draft','Taslak Kaydet','Hazır olmadan sakla','bi-file-earmark'],
                  ['passive','Pasif Kaydet','Sisteme ekle ama gösterme','bi-pause-circle'],
              ];
              foreach ($pubOpts as [$val, $lbl, $desc, $ico]):
              ?>
              <label class="pub-opt" onclick="selectPubOpt(this)">
                <input type="radio" name="status" value="<?= $val ?>" <?= $val==='published'?'checked':'' ?>>
                <div class="d-flex align-items-center gap-3">
                  <i class="bi <?= $ico ?> fs-4 text-pim-gold"></i>
                  <div><div class="fw-600"><?= $lbl ?></div><div class="text-muted fs-7"><?= $desc ?></div></div>
                </div>
              </label>
              <?php endforeach; ?>
              <label class="pub-opt" onclick="selectPubOpt(this)">
                <input type="radio" name="status" value="coming_soon">
                <div class="d-flex align-items-center gap-3">
                  <i class="bi bi-calendar-event fs-4 text-pim-gold"></i>
                  <div><div class="fw-600">Zamanlanmış Yayın</div><div class="text-muted fs-7">Belirli bir tarihte yayınla</div></div>
                </div>
                <div class="mt-2 ms-5" id="scheduleField" style="display:none">
                  <input class="pim-input" type="datetime-local" name="available_from">
                </div>
              </label>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="pim-section">
            <div class="pim-section-title mb-3"><i class="bi bi-check-circle"></i>Tamamlanan Adımlar</div>
            <div id="finalChecklist" class="d-flex flex-column gap-1">
              <?php foreach ($steps as $i => $s): $n=$i+1; ?>
              <div class="d-flex align-items-center gap-2 py-1"><div class="step-complete-badge"><i class="bi bi-check"></i></div><span class="fs-7"><?= $s ?></span></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="text-center mt-4">
        <button type="submit" class="pim-btn pim-btn-primary pim-btn-xl" id="wizFinishBtn">
          <i class="bi bi-rocket-takeoff"></i> Ürünü Kaydet
        </button>
      </div>
    </div>

  </div><!-- /wizard-body -->

  <!-- ── Wizard Footer ─────────────────────────── -->
  <div class="pim-wizard-footer">
    <button type="button" class="pim-btn pim-btn-secondary" id="wizPrevBtn" disabled><i class="bi bi-arrow-left"></i> Geri</button>
    <span class="text-muted fs-7" id="wizStepCounter">Adım 1 / 10</span>
    <button type="button" class="pim-btn pim-btn-primary" id="wizNextBtn">İleri <i class="bi bi-arrow-right"></i></button>
  </div>
</div><!-- /wizard-wrap -->
</form>
</div><!-- /pim-module -->

<script>
/* ── TinyMCE ───────────────────────────────── */
tinymce.init({selector:'#wizDesc',skin:'oxide-dark',content_css:'dark',height:280,menubar:false,promotion:false,toolbar:'bold italic | bullist numlist | link',plugins:'link lists'});

/* ── Tag Input ─────────────────────────────── */
PIM.tagInput.init('wizTagInput','wizTagsHidden',[',','Enter']);

/* ── Pricing ───────────────────────────────── */
PIM.pricing.bindLive('wiz_price','wiz_cost_price','wizMargin','wizMarkup');

/* ── SEO char counters ─────────────────────── */
PIM.charCounter.bind('wizSeoTitle','wizTitleCount',{warn:50,max:60});
PIM.charCounter.bind('wizSeoDesc','wizDescCount',{warn:140,max:160});

/* ── Wizard State ──────────────────────────── */
const TOTAL_STEPS = 10;
let currentStep = 1;
function goToStep(n){
    document.querySelectorAll('.pim-wizard-step').forEach((s,i)=>s.classList.toggle('active',i+1===n));
    document.querySelectorAll('.pim-step').forEach((d,i)=>{
        d.classList.toggle('active',i+1===n);
        d.classList.toggle('done',i+1<n);
    });
    document.querySelectorAll('.pim-step-connector').forEach((c,i)=>c.classList.toggle('done',i+1<n));
    document.getElementById('wizPrevBtn').disabled = n===1;
    document.getElementById('wizNextBtn').style.display = n===TOTAL_STEPS?'none':'';
    document.getElementById('wizStepCounter').textContent = `Adım ${n} / ${TOTAL_STEPS}`;
    currentStep = n;
    if(n===9) aggregatePreview();
    window.scrollTo({top:0,behavior:'smooth'});
}
document.getElementById('wizPrevBtn').addEventListener('click',()=>goToStep(currentStep-1));
document.getElementById('wizNextBtn').addEventListener('click',()=>{
    if(validateWizStep(currentStep)) goToStep(currentStep+1);
});
function validateWizStep(n){
    const step = document.getElementById('wstep'+n);
    let ok = true;
    step.querySelectorAll('[required]').forEach(el=>{
        if(!el.value.trim()){el.style.borderColor='var(--pim-danger)';el.focus();ok=false;}
        else el.style.borderColor='';
    });
    if(!ok) PIM.toast.error('Lütfen zorunlu alanları doldurun.');
    return ok;
}

/* ── Name → slug, preview ──────────────────── */
document.getElementById('wizProductName').addEventListener('input',function(){
    const v = this.value;
    document.getElementById('wizNamePreview').textContent = v || 'Ürün adı buraya gelecek...';
    const slug = v.toLowerCase()
        .replace(/ğ/g,'g').replace(/ü/g,'u').replace(/ş/g,'s').replace(/ı/g,'i').replace(/ö/g,'o').replace(/ç/g,'c')
        .replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
    const slugEl = document.getElementById('wizSlug');
    if(slugEl && !slugEl._edited) slugEl.value = slug;
    document.getElementById('wizPrevUrl').textContent = 'saintmonarc.com › products › ' + slug;
    document.getElementById('wizPrevTitle').textContent = document.getElementById('wizSeoTitle').value || v || 'Ürün başlığı...';
});
document.getElementById('wizSlug').addEventListener('input',function(){ this._edited=true; });

/* ── SKU Generator ─────────────────────────── */
function genSKU(){
    const pre = (document.getElementById('wizProductName').value||'PRD').substring(0,3).toUpperCase();
    document.getElementById('wizSku').value = pre+'-'+Date.now().toString(36).toUpperCase();
}

/* ── SEO Preview ───────────────────────────── */
function updateWizSeoPreview(){
    document.getElementById('wizPrevTitle').textContent = document.getElementById('wizSeoTitle').value || document.getElementById('wizProductName').value || '—';
    document.getElementById('wizPrevDesc').textContent  = document.getElementById('wizSeoDesc').value  || '—';
    const score = PIM.seoScore.calculate({title:document.getElementById('wizSeoTitle').value, description:document.getElementById('wizSeoDesc').value});
    PIM.seoScore.render(score,'wizSeoRing');
    document.getElementById('wizSeoNum').textContent = score;
    document.getElementById('wizSeoLabel').textContent = score>=70?'🟢 İyi SEO':score>=40?'🟡 Orta':'🔴 Zayıf';
}

/* ── Brand selection ───────────────────────── */
function selectBrand(el){
    document.querySelectorAll('.brand-card-opt').forEach(c=>c.classList.remove('selected'));
    el.classList.add('selected'); el.querySelector('input').checked=true;
}

/* ── Publish option ────────────────────────── */
function selectPubOpt(el){
    document.querySelectorAll('.pub-opt').forEach(p=>p.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input').checked=true;
    const sched = document.getElementById('scheduleField');
    if(sched) sched.style.display = el.querySelector('input').value==='coming_soon'?'':'none';
}
document.querySelectorAll('.pub-opt').forEach(p=>p.addEventListener('click',function(){selectPubOpt(this);}));
document.querySelector('.pub-opt').classList.add('selected');

/* ── Variant Builder ───────────────────────── */
function toggleVariantBuilder(v){
    document.getElementById('variantBuilderSection').style.display = v?'':'none';
    document.getElementById('noVariantMsg').style.display = v?'none':'';
}
function handleVarType(cb){
    const lbl = cb.closest('.vtype-pill');
    lbl.classList.toggle('active',cb.checked);
    lbl.style.cssText = cb.checked?'color:var(--pim-gold);border-color:var(--pim-gold);background:var(--pim-gold-glow)':'';
    renderVarInputs();
}
function renderVarInputs(){
    const container = document.getElementById('varTypeInputs');
    container.innerHTML='';
    document.querySelectorAll('.vtype-pill input:checked').forEach(cb=>{
        const div=document.createElement('div');
        div.className='pim-section mb-3';
        div.innerHTML=`<div class="pim-section-title mb-2">${cb.value} Değerleri</div>
            <input class="pim-input" type="text" placeholder="Örn: Kırmızı, Mavi, Yeşil (virgülle ayırın)" oninput="renderCombinations()">`;
        container.appendChild(div);
    });
}
function renderCombinations(){
    document.getElementById('varCombinations').innerHTML='<div class="text-muted fs-7 p-3">Kombinasyonlar oluşturulacak...</div>';
}

/* ── Media Preview ─────────────────────────── */
function previewCover(input){
    const file=input.files[0]; if(!file) return;
    const r=new FileReader();
    r.onload=e=>{
        document.getElementById('wizCoverImg').src=e.target.result;
        document.getElementById('wizCoverPreview').style.display='';
    };
    r.readAsDataURL(file);
}
function previewGallery(files){
    const grid=document.getElementById('wizGalleryGrid');
    Array.from(files).forEach(f=>{
        if(!f.type.startsWith('image/')) return;
        const r=new FileReader();
        r.onload=e=>{
            const item=document.createElement('div');
            item.className='pim-media-item';
            item.innerHTML=`<img src="${e.target.result}" loading="lazy" alt=""><div class="pim-media-overlay"><button type="button" class="pim-btn pim-btn-danger pim-btn-sm pim-btn-icon" onclick="this.closest('.pim-media-item').remove()"><i class="bi bi-trash3"></i></button></div>`;
            grid.appendChild(item);
        };
        r.readAsDataURL(f);
    });
}
PIM.dropzone.init('wizGalleryDrop', files=>previewGallery(files));

/* ── Step 9 Preview Aggregation ────────────── */
function aggregatePreview(){
    document.getElementById('previewName').textContent  = document.getElementById('wizProductName').value || '—';
    document.getElementById('previewPrice').textContent = '₺' + (parseFloat(document.getElementById('wiz_price').value)||0).toFixed(2);
    document.getElementById('previewStock').textContent = 'Stok: ' + (document.getElementById('wizStock').value||'0');
    document.getElementById('previewSku').textContent   = 'SKU: ' + (document.getElementById('wizSku').value||'—');
    const catSel = document.getElementById('wizCategory');
    document.getElementById('previewCat').textContent   = catSel.options[catSel.selectedIndex]?.text || 'Kategori: —';
    document.querySelectorAll('.checklist-item .step-pending-badge').forEach((b,i)=>{
        b.className='step-complete-badge'; b.innerHTML='<i class="bi bi-check"></i>';
    });
    // cover preview
    const covImg = document.getElementById('wizCoverImg');
    if(covImg && covImg.src) {
        const wrap = document.getElementById('previewImgWrap');
        wrap.innerHTML=`<img src="${covImg.src}" style="width:100%;height:200px;object-fit:cover" alt="">`;
    }
}

/* ── Category Path Preview ─────────────────── */
document.getElementById('wizCategory').addEventListener('change',function(){
    const opt = this.options[this.selectedIndex];
    document.getElementById('catPathPreview').textContent = opt.value ? '🏷 ' + opt.text.trim() : 'Kategori seçilmedi.';
});

/* ── Unlimited Stock Toggle ────────────────── */
function toggleStockField(checked){
    const f=document.getElementById('wizStockFields');
    f.style.opacity=checked?'.3':'1'; f.style.pointerEvents=checked?'none':'auto';
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
