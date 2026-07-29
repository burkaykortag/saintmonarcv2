<?php
use App\Helpers\ComponentHelper;

$title = "Ürün Ekle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Ürünler' => url('/admin/products'), 'Yeni Ürün' => '#']) ?>
    <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Yeni Ürün Ekle</h2>
</div>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<form action="<?= url('/admin/products/create') ?>" method="POST" class="row g-4" id="productForm">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <!-- Left Column: Fields -->
    <div class="col-12 col-xl-8">
        
        <!-- General Info Card -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Genel Bilgiler</h4>
            
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Ürün Adı</label>
                    <input type="text" name="name" required class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Ürün Alt Başlığı</label>
                    <input type="text" name="subtitle" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Kategori</label>
                    <select name="category_id" required class="form-select border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;">
                        <option value="">Kategori Seçin</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Marka</label>
                    <select name="brand_id" class="form-select border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;">
                        <option value="">Marka Seçin</option>
                        <?php foreach ($brands as $br): ?>
                            <option value="<?= $br['id'] ?>"><?= htmlspecialchars($br['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Ürün Tipi</label>
                    <select name="product_type" class="form-select border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;">
                        <option value="physical">Fiziksel Ürün</option>
                        <option value="digital">Dijital Varlık</option>
                        <option value="service">Hizmet</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Durum</label>
                    <select name="status" class="form-select border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;">
                        <option value="draft">Taslak</option>
                        <option value="published">Yayında</option>
                        <option value="passive">Pasif</option>
                        <option value="archived">Arşiv</option>
                        <option value="coming_soon">Yakında</option>
                        <option value="out_of_stock">Stokta Yok</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Kısa Açıklama</label>
                <input type="text" name="short_description" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 12px;">
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Özet Bilgi</label>
                <textarea name="summary" class="form-control border-0 text-white" rows="2" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Detaylı Açıklama (Long Description)</label>
                <textarea id="descriptionEditor" name="description" class="form-control border-0 text-white" rows="8" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;"></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Teknik Özellikler</label>
                <textarea name="technical_specs" class="form-control border-0 text-white" rows="4" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"></textarea>
            </div>
            
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <label class="form-label text-muted fs-7 mb-1">Kullanım Talimatı</label>
                    <textarea name="instructions" class="form-control border-0 text-white" rows="3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"></textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label text-muted fs-7 mb-1">Garanti Bilgisi</label>
                    <textarea name="warranty" class="form-control border-0 text-white" rows="3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"></textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label text-muted fs-7 mb-1">Teslimat Bilgisi</label>
                    <textarea name="delivery_info" class="form-control border-0 text-white" rows="3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"></textarea>
                </div>
            </div>
        </div>

        <!-- Identifiers Card -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Kodlama & Barkod Bilgileri</h4>
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <label class="form-label text-muted fs-7 mb-1">Stok Kodu (SKU)</label>
                    <input type="text" name="sku" required class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label text-muted fs-7 mb-1">Barkod (EAN/UPC)</label>
                    <input type="text" name="barcode" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label text-muted fs-7 mb-1">Model No</label>
                    <input type="text" name="model_no" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
            </div>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">GTIN</label>
                    <input type="text" name="gtin" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">EAN</label>
                    <input type="text" name="ean" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">UPC</label>
                    <input type="text" name="upc" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">MPN</label>
                    <input type="text" name="mpn" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
            </div>
        </div>

        <!-- Pricing Card -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Fiyatlandırma & Çoklu Para Birimi</h4>
            <div class="row g-3 mb-3 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">Para Birimi</label>
                    <select name="currency_code" id="currencySelect" class="form-select border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                        <option value="TRY">TRY (₺)</option>
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">Maliyet Fiyatı</label>
                    <input type="number" step="0.0001" name="cost_price" id="costPriceInput" value="0.00" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">Satış Fiyatı (Vergi Hariç)</label>
                    <input type="number" step="0.0001" name="price" id="priceInput" value="0.00" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">Karşılaştırma Fiyatı</label>
                    <input type="number" step="0.0001" name="compare_at_price" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
            </div>

            <div class="row g-3 mb-3 align-items-center">
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">KDV Oranı (%)</label>
                    <input type="number" step="0.01" name="tax_rate" id="taxRateInput" value="18.00" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">Vergi Dahil Fiyat</label>
                    <input type="text" readonly id="taxIncludedPriceInput" class="form-control border-0 text-white-50 bg-transparent" style="border: 1px dashed var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-12 col-md-6">
                    <div class="p-3 rounded-3" style="background: rgba(197, 168, 128, 0.05); border: 1px solid rgba(197, 168, 128, 0.15);">
                        <div class="d-flex justify-content-between mb-1 fs-7">
                            <span class="text-muted">Brüt Kar:</span>
                            <span class="font-weight-600 text-white" id="profitSpan">0.00 ₺</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 fs-7">
                            <span class="text-muted">Kar Marjı:</span>
                            <span class="font-weight-600 text-success" id="marginSpan">0.00%</span>
                        </div>
                        <div class="d-flex justify-content-between fs-7">
                            <span class="text-muted">Kar Oranı (Markup):</span>
                            <span class="font-weight-600 text-info" id="rateSpan">0.00%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label text-muted fs-7 mb-1">İndirimli Özel Fiyat</label>
                    <input type="number" step="0.0001" name="special_price" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label text-muted fs-7 mb-1">Başlangıç Tarihi</label>
                    <input type="datetime-local" name="special_price_start" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label text-muted fs-7 mb-1">Bitiş Tarihi</label>
                    <input type="datetime-local" name="special_price_end" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
            </div>
        </div>

        <!-- Stock and Shipping Card -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Stok Parametreleri & Kargo Desi</h4>
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="unlimited_stock" value="1" id="unlimitedStockSwitch">
                        <label class="form-check-label text-white fs-7" for="unlimitedStockSwitch">Sınırsız Stok</label>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">Toplam Stok</label>
                    <input type="number" name="total_stock" id="totalStockInput" value="0" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">Kritik Stok Sınırı</label>
                    <input type="number" name="critical_stock" value="5" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">Minimum Stok</label>
                    <input type="number" name="min_stock" value="0" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
            </div>
            
            <div class="row g-3 mb-3 align-items-center">
                <div class="col-6 col-md-3">
                    <label class="form-label text-muted fs-7 mb-1">Maksimum Sipariş</label>
                    <input type="number" name="max_order" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-6 col-md-3">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="allow_backorder" value="1" id="allowBackorderSwitch">
                        <label class="form-check-label text-white fs-7" for="allowBackorderSwitch">Backorder (Aşıma İzin Ver)</label>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_preorder" value="1" id="isPreorderSwitch">
                        <label class="form-check-label text-white fs-7" for="isPreorderSwitch">Ön Siparişli Ürün</label>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" checked name="track_stock" value="1" id="trackStockSwitch">
                        <label class="form-check-label text-white fs-7" for="trackStockSwitch">Stok Takibi Yap</label>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-6 col-md-2">
                    <label class="form-label text-muted fs-7 mb-1">Ağırlık (Kg)</label>
                    <input type="number" step="0.01" name="weight" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label text-muted fs-7 mb-1">Desi Hacmi</label>
                    <input type="number" step="0.01" name="desi" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-4 col-md-2">
                    <label class="form-label text-muted fs-7 mb-1">Genişlik (cm)</label>
                    <input type="number" step="0.1" name="width" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-4 col-md-2">
                    <label class="form-label text-muted fs-7 mb-1">Yükseklik (cm)</label>
                    <input type="number" step="0.1" name="height" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-4 col-md-2">
                    <label class="form-label text-muted fs-7 mb-1">Uzunluk (cm)</label>
                    <input type="number" step="0.1" name="length" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
            </div>
        </div>

        <!-- Variants Card -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-2" style="font-size: 16px;">Ürün Varyant Seçenekleri</h4>
            <p class="text-muted fs-8 mb-4">Renk, beden, materyal vb. parametreleri işaretleyip "Varyantları Oluştur" tuşuyla stok ve fiyat tablolarını dinamik üretin.</p>
            
            <div class="row g-3 mb-4">
                <?php foreach ($attributes as $attr): ?>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label text-white-50 font-weight-600 fs-7 mb-2"><?= htmlspecialchars($attr['name']) ?></label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($attr['values'] as $val): ?>
                                <div class="form-check form-check-inline p-0 m-0">
                                    <input type="checkbox" class="btn-check attr-checkbox" id="val-<?= $val['id'] ?>" data-attr-id="<?= $attr['id'] ?>" data-attr-name="<?= htmlspecialchars($attr['name']) ?>" data-val-id="<?= $val['id'] ?>" data-val-name="<?= htmlspecialchars($val['value']) ?>" autocomplete="off">
                                    <label class="btn btn-outline-secondary fs-8 py-1 px-2 border-secondary-subtle" for="val-<?= $val['id'] ?>"><?= htmlspecialchars($val['value']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="btn btn-secondary border-0 mb-4 fs-7 py-2" onclick="generateVariantsTable()"><i class="bi bi-gear-wide-connected me-2"></i>Varyant Kombinasyonlarını Üret</button>
            
            <div class="table-responsive">
                <table class="table table-dark border-0 m-0 d-none" id="variantsTable" style="background:transparent;">
                    <thead>
                        <tr class="fs-8 text-muted border-bottom border-secondary">
                            <th>Varyant Tipi</th>
                            <th>SKU</th>
                            <th>Barkod</th>
                            <th>Fiyat (₺)</th>
                            <th>Maliyet</th>
                            <th>Stok</th>
                            <th>Görsel</th>
                        </tr>
                    </thead>
                    <tbody id="variantsTableBody"></tbody>
                </table>
            </div>
        </div>

        <!-- Related Products Card -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">İlişkili Ürünler & Kampanyalar</h4>
            <div class="row g-3">
                <div class="col-12 col-md-6 mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Benzer Ürünler (Similar)</label>
                    <select name="relations[similar][]" multiple class="form-select border-0 text-white bg-dark fs-7" style="height: 120px;">
                        <?php foreach ($allProducts as $ap): ?>
                            <option value="<?= $ap['id'] ?>"><?= htmlspecialchars($ap['name']) ?> (SKU: <?= $ap['sku'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Tamamlayıcı Ürünler (Complementary)</label>
                    <select name="relations[complementary][]" multiple class="form-select border-0 text-white bg-dark fs-7" style="height: 120px;">
                        <?php foreach ($allProducts as $ap): ?>
                            <option value="<?= $ap['id'] ?>"><?= htmlspecialchars($ap['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Çapraz Satış Ürünleri (Cross-Sell)</label>
                    <select name="relations[cross_sell][]" multiple class="form-select border-0 text-white bg-dark fs-7" style="height: 120px;">
                        <?php foreach ($allProducts as $ap): ?>
                            <option value="<?= $ap['id'] ?>"><?= htmlspecialchars($ap['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Dikey Satış Ürünleri (Upsell)</label>
                    <select name="relations[upsell][]" multiple class="form-select border-0 text-white bg-dark fs-7" style="height: 120px;">
                        <?php foreach ($allProducts as $ap): ?>
                            <option value="<?= $ap['id'] ?>"><?= htmlspecialchars($ap['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Additional Files Card -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-2" style="font-size: 16px;">Ek Dosyalar & PDF Dokümanları</h4>
            <p class="text-muted fs-8 mb-4">Kullanım kılavuzları, garanti belgeleri gibi ek PDF dosyalarını listeye ekleyin.</p>
            <div id="additionalFilesList" class="mb-3"></div>
            <button type="button" class="btn btn-secondary border-0 fs-8 py-2" onclick="addFileRow()"><i class="bi bi-file-earmark-plus me-2"></i>Yeni Dosya Ekle</button>
        </div>

        <!-- SEO Card -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Arama Motoru Optimizasyonu (SEO)</h4>
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Meta Title</label>
                    <input type="text" name="seo[title]" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Canonical URL</label>
                    <input type="text" name="seo[canonical_url]" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Meta Açıklaması (Meta Description)</label>
                <textarea name="seo[description]" class="form-control border-0 text-white" rows="3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Anahtar Kelimeler (Meta Keywords)</label>
                <input type="text" name="seo[keywords]" class="form-control border-0 text-white" placeholder="virgülle ayırarak giriniz..." style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
            </div>
            
            <h5 class="text-white-50 font-weight-600 fs-7 mb-3 mt-4">Sosyal Medya Kartları (Open Graph & Twitter Card)</h5>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Sosyal Medya Başlığı (OG Title)</label>
                    <input type="text" name="seo[og_title]" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-7 mb-1">Sosyal Medya Görseli URL (OG Image)</label>
                    <input type="text" name="seo[og_image]" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
                </div>
                <div class="col-12">
                    <label class="form-label text-muted fs-7 mb-1">Sosyal Medya Açıklaması (OG Description)</label>
                    <textarea name="seo[og_description]" class="form-control border-0 text-white" rows="2" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; resize:none;"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Meta Info / Sidebars -->
    <div class="col-12 col-xl-4">
        
        <!-- Media Settings -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Medya Dosyaları & Görseller</h4>
            
            <div class="mb-4">
                <label class="form-label text-muted fs-7 mb-2">Kapak Fotoğrafı</label>
                <input type="hidden" name="cover_image_id" id="cover_image_id" value="">
                <div id="cover_preview" class="border border-secondary rounded-4 p-3 d-flex align-items-center justify-content-center cursor-pointer" style="min-height: 120px; background: rgba(0,0,0,0.15);" onclick="openMediaPicker('cover')">
                    <span class="text-muted"><i class="bi bi-image me-2"></i>Resim Seç</span>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-muted fs-7 mb-2">Ürün Galeri Resimleri</label>
                <div id="gallery_previews" class="row g-2 mb-2"></div>
                <button type="button" class="btn btn-secondary w-100 py-2 border-0 fs-8" onclick="openMediaPicker('gallery')">
                    <i class="bi bi-images me-2"></i>Galeriye Görsel Ekle
                </button>
                <div id="gallery_inputs_container"></div>
            </div>

            <div class="mb-4">
                <label class="form-label text-muted fs-7 mb-2">Tanıtım Videosu (Kütüphane)</label>
                <input type="hidden" name="promo_video_id" id="promo_video_id" value="">
                <div id="video_preview" class="border border-secondary rounded-4 p-3 d-flex align-items-center justify-content-center cursor-pointer" style="min-height: 80px; background: rgba(0,0,0,0.15);" onclick="openMediaPicker('video')">
                    <span class="text-muted"><i class="bi bi-play-circle me-2"></i>Kütüphaneden Video Seç</span>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label text-muted fs-7 mb-1">Youtube Video Linki</label>
                <input type="text" name="youtube_url" class="form-control border-0 text-white" placeholder="https://youtube.com/watch?v=..." style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
            </div>
            <div class="mb-4">
                <label class="form-label text-muted fs-7 mb-1">Vimeo Video Linki</label>
                <input type="text" name="vimeo_url" class="form-control border-0 text-white" placeholder="https://vimeo.com/..." style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
            </div>
            <div class="mb-4">
                <label class="form-label text-muted fs-7 mb-1">Mp4 Video Linki (Alternatif)</label>
                <input type="text" name="mp4_url" class="form-control border-0 text-white" placeholder="https://site.com/video.mp4" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
            </div>
            <div class="mb-2">
                <label class="form-label text-muted fs-7 mb-1">360 Derece Görsel (ID'ler)</label>
                <input type="text" name="images_360" class="form-control border-0 text-white" placeholder="virgülle ayrılmış medya ID'leri..." style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
            </div>
        </div>

        <!-- Badges & Flags -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Ürün Rozetleri & Etiketler</h4>
            
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_new" value="1" id="isNewSwitch">
                <label class="form-check-label text-white fs-7" for="isNewSwitch">Yeni Ürün</label>
            </div>
            
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_discount" value="1" id="isDiscountSwitch">
                <label class="form-check-label text-white fs-7" for="isDiscountSwitch">İndirim Rozeti</label>
            </div>
            
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_bestseller" value="1" id="isBestSellerSwitch">
                <label class="form-check-label text-white fs-7" for="isBestSellerSwitch">Çok Satan (Bestseller)</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="isFeaturedSwitch">
                <label class="form-check-label text-white fs-7" for="isFeaturedSwitch">Öne Çıkan Ürün</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_editors_choice" value="1" id="isEditorsSwitch">
                <label class="form-check-label text-white fs-7" for="isEditorsSwitch">Editörün Seçimi</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_campaign" value="1" id="isCampaignSwitch">
                <label class="form-check-label text-white fs-7" for="isCampaignSwitch">Kampanya Ürünü</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_new_arrival" value="1" id="isArrivalSwitch">
                <label class="form-check-label text-white fs-7" for="isArrivalSwitch">Yeni Gelen</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_premium" value="1" id="isPremiumSwitch">
                <label class="form-check-label text-white fs-7" for="isPremiumSwitch">Premium Sürüm</label>
            </div>

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="free_shipping" value="1" id="freeShippingSwitch">
                <label class="form-check-label text-white fs-7" for="freeShippingSwitch">Ücretsiz Kargo</label>
            </div>

            <div class="mb-2">
                <label class="form-label text-muted fs-7 mb-1">Ürün Etiketleri</label>
                <input type="text" name="tags" class="form-control border-0 text-white" placeholder="Örn: ayakkabı, spor, deri..." style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; padding: 10px;">
            </div>
        </div>

        <!-- Publishing Actions -->
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-4" style="font-size: 16px;">Yayınlama Ayarları</h4>
            
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" checked name="is_active" value="1" id="isActiveSwitch">
                <label class="form-check-label text-white fs-7" for="isActiveSwitch">Ürün Aktif</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="show_in_home" value="1" id="showHomeSwitch">
                <label class="form-check-label text-white fs-7" for="showHomeSwitch">Ana Sayfada Göster</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="show_in_slider" value="1" id="showSliderSwitch">
                <label class="form-check-label text-white fs-7" for="showSliderSwitch">Slider Üzerinde Göster</label>
            </div>

            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" name="show_in_banner" value="1" id="showBannerSwitch">
                <label class="form-check-label text-white fs-7" for="showBannerSwitch">Banner Üzerinde Göster</label>
            </div>

            <div class="d-flex flex-column gap-2">
                <button type="submit" class="btn w-100 py-3">Ürünü Kaydet ve Yayınla</button>
                <a href="<?= url('/admin/products') ?>" class="btn btn-secondary w-100 py-3 border-0 text-center">İptal Et</a>
            </div>
        </div>

    </div>
</form>

<!-- Include Media Picker Modal -->
<?php include dirname(__DIR__) . '/media/media_picker_modal.php'; ?>

<script>
    tinymce.init({
        selector: '#descriptionEditor',
        theme: 'silver',
        skin: 'oxide-dark',
        content_css: 'dark',
        height: 350,
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        background_color: 'rgba(255,255,255,0.03)'
    });

    const costInput = document.getElementById('costPriceInput');
    const priceInput = document.getElementById('priceInput');
    const taxInput = document.getElementById('taxRateInput');
    
    const profitSpan = document.getElementById('profitSpan');
    const marginSpan = document.getElementById('marginSpan');
    const rateSpan = document.getElementById('rateSpan');
    const taxIncInput = document.getElementById('taxIncludedPriceInput');
    const currencySelect = document.getElementById('currencySelect');

    function calculateProfit() {
        const cost = parseFloat(costInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const taxRate = parseFloat(taxInput.value) || 0;
        const currency = currencySelect.value;
        const sym = currency === 'TRY' ? '₺' : (currency === 'USD' ? '$' : '€');

        const profit = price - cost;
        const margin = price > 0 ? (profit / price) * 100 : 0;
        const rate = cost > 0 ? (profit / cost) * 100 : 0;
        const taxInc = price * (1 + (taxRate / 100));

        profitSpan.textContent = profit.toFixed(2) + ' ' + sym;
        marginSpan.textContent = margin.toFixed(2) + '%';
        rateSpan.textContent = rate.toFixed(2) + '%';
        taxIncInput.value = taxInc.toFixed(2) + ' ' + sym;
    }

    [costInput, priceInput, taxInput, currencySelect].forEach(input => {
        input.addEventListener('input', calculateProfit);
        input.addEventListener('change', calculateProfit);
    });
    
    function generateVariantsTable() {
        const selectedCheks = document.querySelectorAll('.attr-checkbox:checked');
        if (selectedCheks.length === 0) {
            alert('Lütfen en az bir varyant değeri seçin.');
            return;
        }

        const groups = {};
        selectedCheks.forEach(cb => {
            const attrId = cb.getAttribute('data-attr-id');
            const attrName = cb.getAttribute('data-attr-name');
            const valId = cb.getAttribute('data-val-id');
            const valName = cb.getAttribute('data-val-name');
            
            if (!groups[attrId]) {
                groups[attrId] = [];
            }
            groups[attrId].push({ attrId, attrName, valId, valName });
        });

        const cartesian = (arrays) => {
            return arrays.reduce((acc, curr) => {
                const res = [];
                acc.forEach(a => {
                    curr.forEach(b => {
                        res.push([...a, b]);
                    });
                });
                return res;
            }, [[]]);
        };

        const combinations = cartesian(Object.values(groups));
        const tbody = document.getElementById('variantsTableBody');
        tbody.innerHTML = '';
        document.getElementById('variantsTable').classList.remove('d-none');

        combinations.forEach((combo, index) => {
            const labels = combo.map(c => c.valName).join(' / ');
            const skuVal = document.getElementsByName('sku')[0].value + '-' + combo.map(c => c.valName.substring(0,2).toUpperCase()).join('-');
            
            let attrHiddenInputs = '';
            combo.forEach(c => {
                attrHiddenInputs += `<input type="hidden" name="variants[${index}][attributes][${c.attrId}]" value="${c.valId}">`;
            });

            const tr = document.createElement('tr');
            tr.className = 'align-middle border-bottom border-secondary-subtle';
            tr.innerHTML = `
                <td>
                    <span class="font-weight-600 text-white">${labels}</span>
                    ${attrHiddenInputs}
                </td>
                <td><input type="text" name="variants[${index}][sku]" value="${skuVal}" class="form-control bg-dark border-secondary text-white py-1 px-2 fs-8"></td>
                <td><input type="text" name="variants[${index}][barcode]" class="form-control bg-dark border-secondary text-white py-1 px-2 fs-8"></td>
                <td><input type="number" step="0.01" name="variants[${index}][price]" class="form-control bg-dark border-secondary text-white py-1 px-2 fs-8" value="${priceInput.value}"></td>
                <td><input type="number" step="0.01" name="variants[${index}][cost_price]" class="form-control bg-dark border-secondary text-white py-1 px-2 fs-8" value="${costInput.value}"></td>
                <td><input type="number" name="variants[${index}][stock]" class="form-control bg-dark border-secondary text-white py-1 px-2 fs-8" value="10"></td>
                <td>
                    <input type="hidden" name="variants[${index}][image_id]" id="var_img_${index}" value="">
                    <button type="button" class="btn btn-secondary border-0 p-1 fs-8" onclick="openVarMediaPicker(${index})"><i class="bi bi-image"></i></button>
                    <span id="var_preview_${index}" style="margin-left: 5px;"></span>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    let activeVarIndex = null;
    function openVarMediaPicker(index) {
        activeVarIndex = index;
        openMediaPicker('variant_picker');
    }

    let fileRowIdx = 0;
    function addFileRow() {
        const container = document.getElementById('additionalFilesList');
        const div = document.createElement('div');
        div.className = 'row g-2 mb-2 align-items-center';
        div.id = 'file-row-' + fileRowIdx;
        div.innerHTML = `
            <div class="col-5">
                <input type="text" name="product_files[${fileRowIdx}][name]" class="form-control bg-dark border-secondary text-white fs-8" placeholder="Dosya Açıklaması">
            </div>
            <div class="col-5">
                <input type="text" name="product_files[${fileRowIdx}][path]" class="form-control bg-dark border-secondary text-white fs-8" placeholder="Dosya Yolu">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger border-0 p-1 fs-8 w-100" onclick="removeFileRow(${fileRowIdx})"><i class="bi bi-trash"></i></button>
            </div>
        `;
        container.appendChild(div);
        fileRowIdx++;
    }

    function removeFileRow(idx) {
        document.getElementById('file-row-' + idx).remove();
    }

    let activeMediaTarget = null;
    let selectedGalleryImages = [];

    function openMediaPicker(targetType) {
        activeMediaTarget = targetType;
        
        SM_MediaPicker.init({
            singleSelect: (targetType !== 'gallery'),
            allowedTypes: targetType === 'video' ? ['mp4', 'mov', 'avi', 'mkv'] : ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'pdf'],
            callback: function(selectedItems) {
                if (selectedItems.length > 0) {
                    if (activeMediaTarget === 'cover') {
                        const item = selectedItems[0];
                        document.getElementById('cover_image_id').value = item.id;
                        document.getElementById('cover_preview').innerHTML = `<img src="<?= url("/") ?>/${item.filepath}" class="img-fluid rounded-4" style="max-height:120px; object-fit:contain;">`;
                    } else if (activeMediaTarget === 'video') {
                        const item = selectedItems[0];
                        document.getElementById('promo_video_id').value = item.id;
                        document.getElementById('video_preview').innerHTML = `<div class="d-flex align-items-center text-success"><i class="bi bi-check-circle me-2"></i> ${item.filename} seçildi</div>`;
                    } else if (activeMediaTarget === 'variant_picker') {
                        const item = selectedItems[0];
                        document.getElementById('var_img_' + activeVarIndex).value = item.id;
                        document.getElementById('var_preview_' + activeVarIndex).innerHTML = `<img src="<?= url("/") ?>/${item.filepath}" style="max-height: 25px; max-width: 40px; object-fit: contain;">`;
                    } else if (activeMediaTarget === 'gallery') {
                        selectedGalleryImages = [...selectedGalleryImages, ...selectedItems];
                        renderGalleryPreviews();
                    }
                }
            }
        });
    }

    function renderGalleryPreviews() {
        const previewDiv = document.getElementById('gallery_previews');
        const inputsDiv = document.getElementById('gallery_inputs_container');
        previewDiv.innerHTML = '';
        inputsDiv.innerHTML = '';

        selectedGalleryImages.forEach((item, idx) => {
            const col = document.createElement('div');
            col.className = 'col-3 position-relative';
            col.innerHTML = `
                <img src="<?= url("/") ?>/${item.filepath}" class="img-fluid rounded-3 border border-secondary" style="max-height: 70px; object-fit: cover;">
                <button type="button" class="position-absolute top-0 end-0 bg-danger text-white border-0 rounded-circle fs-9" style="width:18px; height:18px; line-height:12px; margin-top:-5px; margin-right:5px;" onclick="removeGalleryImg(${idx})">×</button>
            `;
            previewDiv.appendChild(col);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'gallery_image_ids[]';
            input.value = item.id;
            inputsDiv.appendChild(input);
        });
    }

    function removeGalleryImg(idx) {
        selectedGalleryImages.splice(idx, 1);
        renderGalleryPreviews();
    }
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
