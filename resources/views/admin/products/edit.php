<?php
use App\Helpers\ComponentHelper;

$title = "Ürün Düzenle 2.0 - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();

// Prepare JSON-LD Product Schema for preview
$jsonLd = [
    "@context" => "https://schema.org/",
    "@type" => "Product",
    "name" => $product['name'],
    "image" => !empty($product['cover_path']) ? url($product['cover_path']) : '',
    "description" => $product['short_description'] ?? '',
    "sku" => $product['sku'],
    "mpn" => $product['mpn'] ?? '',
    "brand" => [
        "@type" => "Brand",
        "name" => $product['brand_name'] ?? 'SaintMonarc'
    ],
    "offers" => [
        "@type" => "Offer",
        "url" => url('/products/' . $product['slug']),
        "priceCurrency" => $product['currency_code'] ?? 'TRY',
        "price" => $product['price'],
        "availability" => ($product['total_stock'] > 0) ? "https://schema.org/InStock" : "https://schema.org/OutOfStock"
    ]
];
$jsonLdString = json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
.nav-tabs .nav-link {
    color: rgba(255,255,255,0.6);
    border: 1px solid transparent !important;
    background: rgba(255,255,255,0.02);
    transition: all 0.3s ease;
}
.nav-tabs .nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.05);
}
.nav-tabs .nav-link.active {
    color: var(--sm-gold, #c5a880) !important;
    background: rgba(197, 168, 128, 0.1) !important;
    border: 1px solid rgba(197, 168, 128, 0.3) !important;
}
.tab-pane {
    animation: fadeIn 0.4s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.search-input {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--sm-border) !important;
    padding: 10px 12px;
    color: #white;
    border-radius: 8px;
}
.search-input:focus {
    border-color: var(--sm-gold, #c5a880) !important;
    outline: none;
}
</style>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Ürünler' => url('/admin/products'), 'Ürün Düzenle 2.0' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Ürün Düzenle: <?= htmlspecialchars($product['name']) ?></h2>
        <a href="<?= url('/admin/products') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Ürün Listesine Dön</a>
    </div>
</div>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<form action="<?= url('/admin/products/edit') ?>" method="POST" id="productForm" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" value="<?= $product['id'] ?>">

    <!-- Tabs Nav -->
    <ul class="nav nav-tabs border-0 mb-4 gap-2 flex-wrap" id="productTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active rounded-3 py-2 px-3 fs-7" id="tab-genel" data-bs-toggle="tab" data-bs-target="#panel-genel" type="button" role="tab">Genel Bilgiler</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-aciklamalar" data-bs-toggle="tab" data-bs-target="#panel-aciklamalar" type="button" role="tab">Açıklamalar</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-kategori-marka" data-bs-toggle="tab" data-bs-target="#panel-kategori-marka" type="button" role="tab">Kategori & Marka</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-varyantlar" data-bs-toggle="tab" data-bs-target="#panel-varyantlar" type="button" role="tab">Varyantlar</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-fiyat" data-bs-toggle="tab" data-bs-target="#panel-fiyat" type="button" role="tab">Fiyatlandırma</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-stok" data-bs-toggle="tab" data-bs-target="#panel-stok" type="button" role="tab">Stok</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-seo" data-bs-toggle="tab" data-bs-target="#panel-seo" type="button" role="tab">SEO</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-medya" data-bs-toggle="tab" data-bs-target="#panel-medya" type="button" role="tab">Medya</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-belgeler" data-bs-toggle="tab" data-bs-target="#panel-belgeler" type="button" role="tab">Belgeler</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-kargo" data-bs-toggle="tab" data-bs-target="#panel-kargo" type="button" role="tab">Kargo</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-iliskili" data-bs-toggle="tab" data-bs-target="#panel-iliskili" type="button" role="tab">İlişkili Ürünler</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-gecmis" data-bs-toggle="tab" data-bs-target="#panel-gecmis" type="button" role="tab">Geçmiş</button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content" id="productTabsContent">
        
        <!-- 1. GENEL BİLGİLER -->
        <div class="tab-pane fade show active" id="panel-genel" role="tabpanel">
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-4 fs-6">Genel Ürün Bilgileri</h4>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Ürün Adı</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($product['name']) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Ürün Alt Başlığı</label>
                        <input type="text" name="subtitle" value="<?= htmlspecialchars($product['subtitle'] ?? '') ?>" class="search-input w-100 text-white">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Stok Kodu (SKU)</label>
                        <input type="text" name="sku" required value="<?= htmlspecialchars($product['sku']) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Barkod</label>
                        <input type="text" name="barcode" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Model No</label>
                        <input type="text" name="model_no" value="<?= htmlspecialchars($product['model_no'] ?? '') ?>" class="search-input w-100 text-white">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">GTIN</label>
                        <input type="text" name="gtin" value="<?= htmlspecialchars($product['gtin'] ?? '') ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">EAN</label>
                        <input type="text" name="ean" value="<?= htmlspecialchars($product['ean'] ?? '') ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Durum</label>
                        <select name="status" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                            <option value="draft" <?= $product['status'] === 'draft' ? 'selected' : '' ?>>Taslak</option>
                            <option value="published" <?= $product['status'] === 'published' ? 'selected' : '' ?>>Yayında</option>
                            <option value="passive" <?= $product['status'] === 'passive' ? 'selected' : '' ?>>Pasif</option>
                            <option value="archived" <?= $product['status'] === 'archived' ? 'selected' : '' ?>>Arşiv</option>
                            <option value="coming_soon" <?= $product['status'] === 'coming_soon' ? 'selected' : '' ?>>Yakında</option>
                            <option value="out_of_stock" <?= $product['status'] === 'out_of_stock' ? 'selected' : '' ?>>Stokta Yok</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Satışa Açılış Tarihi</label>
                        <input type="datetime-local" name="available_from" value="<?= $product['available_from'] ? date('Y-m-d\TH:i', strtotime($product['available_from'])) : '' ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Satıştan Kalkış Tarihi</label>
                        <input type="datetime-local" name="available_to" value="<?= $product['available_to'] ? date('Y-m-d\TH:i', strtotime($product['available_to'])) : '' ?>" class="search-input w-100 text-white">
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Minimum Sipariş Miktarı</label>
                        <input type="number" name="min_order" value="<?= (int)($product['min_order'] ?? 1) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Maksimum Sipariş Miktarı</label>
                        <input type="number" name="max_order" value="<?= $product['max_order'] ? (int)$product['max_order'] : '' ?>" class="search-input w-100 text-white">
                    </div>
                </div>
                <h5 class="text-white font-weight-600 mb-3 fs-7">Etiket ve Durum Bayrakları</h5>
                <div class="row g-2">
                    <div class="col-md-4">
                        <div class="form-check p-3 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border);">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="is_featured" value="1" id="checkFeatured" <?= (int)($product['is_featured'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label text-white" for="checkFeatured">Öne Çıkan Ürün</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check p-3 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border);">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="is_new" value="1" id="checkNew" <?= (int)($product['is_new'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label text-white" for="checkNew">Yeni Ürün</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check p-3 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border);">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="is_bestseller" value="1" id="checkBestseller" <?= (int)($product['is_bestseller'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label text-white" for="checkBestseller">Çok Satan Ürün</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check p-3 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border);">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="is_deal" value="1" id="checkDeal" <?= (int)($product['is_deal'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label text-white" for="checkDeal">Fırsat Ürünü</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check p-3 rounded-3" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border);">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="allow_backorder" value="1" id="checkBackorder" <?= (int)($product['allow_backorder'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label text-white" for="checkBackorder">Stokta Yoksa Satılabilsin</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. AÇIKLAMALAR -->
        <div class="tab-pane fade" id="panel-aciklamalar" role="tabpanel">
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-4 fs-6">Açıklamalar ve Döküman Yazıları</h4>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Kısa Açıklama</label>
                    <input type="text" name="short_description" value="<?= htmlspecialchars($product['short_description'] ?? '') ?>" class="search-input w-100 text-white">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Özet Bilgi</label>
                    <textarea name="summary" class="search-input w-100 text-white" rows="2"><?= htmlspecialchars($product['summary'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Detaylı Açıklama (Uzun Açıklama)</label>
                    <textarea id="descriptionEditor" name="description" class="search-input w-100 text-white" rows="8"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Teknik Özellikler</label>
                    <textarea name="technical_specs" class="search-input w-100 text-white" rows="3"><?= htmlspecialchars($product['technical_specs'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Kutu İçeriği</label>
                    <textarea name="box_content" class="search-input w-100 text-white" rows="2"><?= htmlspecialchars($product['box_content'] ?? '') ?></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Garanti Bilgisi</label>
                        <textarea name="warranty" class="search-input w-100 text-white" rows="3"><?= htmlspecialchars($product['warranty'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">İade Koşulları</label>
                        <textarea name="return_policy" class="search-input w-100 text-white" rows="3"><?= htmlspecialchars($product['return_policy'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. KATEGORİ & MARKA -->
        <div class="tab-pane fade" id="panel-kategori-marka" role="tabpanel">
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-4 fs-6">Kategori & Marka İlişkilendirmesi</h4>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Kategori</label>
                        <select name="category_id" required class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                            <option value="">Kategori Seçin</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Marka</label>
                        <select name="brand_id" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                            <option value="">Marka Seçin</option>
                            <?php foreach ($brands as $br): ?>
                                <option value="<?= $br['id'] ?>" <?= $product['brand_id'] == $br['id'] ? 'selected' : '' ?>><?= htmlspecialchars($br['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Etiketler (Virgülle ayırın)</label>
                    <input type="text" name="tags" value="<?= htmlspecialchars($tags ?? '') ?>" class="search-input w-100 text-white" placeholder="Örn: spor, ayakkabı, koşu">
                </div>
            </div>
        </div>

        <!-- 4. VARYANTLAR -->
        <div class="tab-pane fade" id="panel-varyantlar" role="tabpanel">
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="text-white font-weight-600 m-0 fs-6">Ürün Varyantları</h4>
                    <a href="<?= url('/admin/variants?product_id=' . $product['id']) ?>" class="btn btn-sm btn-secondary border-0"><i class="bi bi-gear me-1"></i>Hızlı Varyant Yönetimi</a>
                </div>
                
                <div class="table-responsive rounded-3">
                    <table class="table table-hover align-middle mb-0 text-white">
                        <thead class="text-muted" style="background: rgba(255,255,255,0.01);">
                            <tr>
                                <th>Görsel</th>
                                <th>SKU</th>
                                <th>Barkod</th>
                                <th>Fiyat</th>
                                <th>Stok</th>
                                <th>Seçenekler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($variants)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Bu ürüne ait varyant bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($variants as $v): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($v['image_path'])): ?>
                                                <img src="<?= url($v['image_path']) ?>" class="rounded-3" style="width: 35px; height: 35px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; background: rgba(255,255,255,0.02);"><i class="bi bi-image text-muted"></i></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?= htmlspecialchars($v['sku']) ?></code></td>
                                        <td class="text-muted"><?= htmlspecialchars($v['barcode'] ?? '-') ?></td>
                                        <td class="text-warning"><?= number_format((float)$v['price'], 2) ?> TRY</td>
                                        <td><?= (int)$v['stock'] ?></td>
                                        <td class="text-muted fs-8">
                                            <?php 
                                            $optsList = [];
                                            foreach ($v['options'] as $opt) {
                                                $optsList[] = $opt['attribute_name'] . ': ' . $opt['option_value'];
                                            }
                                            echo htmlspecialchars(implode(', ', $optsList));
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. FİYATLANDIRMA -->
        <div class="tab-pane fade" id="panel-fiyat" role="tabpanel">
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-4 fs-6">Fiyatlandırma & KDV Yönetimi</h4>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">Alış Fiyatı (Maliyet)</label>
                        <input type="number" step="0.01" name="cost_price" id="costPrice" value="<?= (float)($product['cost_price'] ?? 0.0) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">Satış Fiyatı</label>
                        <input type="number" step="0.01" name="price" id="priceInput" required value="<?= (float)$product['price'] ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">Piyasa Fiyatı (Eski Fiyat)</label>
                        <input type="number" step="0.01" name="compare_at_price" value="<?= $product['compare_at_price'] ? (float)$product['compare_at_price'] : '' ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">Para Birimi</label>
                        <select name="currency_code" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                            <option value="TRY" <?= ($product['currency_code'] ?? 'TRY') === 'TRY' ? 'selected' : '' ?>>TL (TRY)</option>
                            <option value="USD" <?= ($product['currency_code'] ?? '') === 'USD' ? 'selected' : '' ?>>Dolar (USD)</option>
                            <option value="EUR" <?= ($product['currency_code'] ?? '') === 'EUR' ? 'selected' : '' ?>>Euro (EUR)</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">İndirimli Özel Fiyat</label>
                        <input type="number" step="0.01" name="special_price" value="<?= $product['special_price'] ? (float)$product['special_price'] : '' ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">KDV Oranı (%)</label>
                        <input type="number" step="0.01" name="tax_rate" value="<?= (float)($product['tax_rate'] ?? 20.0) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">KDV Durumu</label>
                        <select name="tax_included" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                            <option value="0" <?= (int)($product['tax_included'] ?? 0) === 0 ? 'selected' : '' ?>>KDV Hariç</option>
                            <option value="1" <?= (int)($product['tax_included'] ?? 0) === 1 ? 'selected' : '' ?>>KDV Dahil</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">İndirim Başlangıç Tarihi</label>
                        <input type="datetime-local" name="special_price_start" value="<?= $product['special_price_start'] ? date('Y-m-d\TH:i', strtotime($product['special_price_start'])) : '' ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">İndirim Bitiş Tarihi</label>
                        <input type="datetime-local" name="special_price_end" value="<?= $product['special_price_end'] ? date('Y-m-d\TH:i', strtotime($product['special_price_end'])) : '' ?>" class="search-input w-100 text-white">
                    </div>
                </div>
                
                <h5 class="text-white font-weight-600 mb-3 fs-7">Kâr Hesaplama Matrisi</h5>
                <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <span class="text-muted d-block fs-8 mb-1">Kâr Tutarı</span>
                            <span class="fs-5 text-success font-weight-700" id="profitVal">0.00 TRY</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block fs-8 mb-1">Kâr Oranı (Maliyet Üzerinden)</span>
                            <span class="fs-5 text-warning font-weight-700" id="profitRateVal">0.00%</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block fs-8 mb-1">Kâr Marjı (Satış Üzerinden)</span>
                            <span class="fs-5 text-warning font-weight-700" id="profitMarginVal">0.00%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. STOK -->
        <div class="tab-pane fade" id="panel-stok" role="tabpanel">
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-4 fs-6">Envanter & Depo Yönetimi</h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">Toplam Stok</label>
                        <input type="number" name="total_stock" value="<?= (int)($product['total_stock'] ?? 0) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">Kritik Stok Seviyesi</label>
                        <input type="number" name="critical_stock" value="<?= (int)($product['critical_stock'] ?? 5) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">Minimum Stok Seviyesi</label>
                        <input type="number" name="min_stock" value="<?= (int)($product['min_stock'] ?? 0) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">Stok Yönetimi Aktif</label>
                        <select name="track_stock" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                            <option value="1" <?= (int)($product['track_stock'] ?? 1) === 1 ? 'selected' : '' ?>>Evet (Stok Düşsün)</option>
                            <option value="0" <?= (int)($product['track_stock'] ?? 0) === 0 ? 'selected' : '' ?>>Hayır</option>
                        </select>
                    </div>
                </div>

                <h5 class="text-white font-weight-600 mb-3 fs-7">Depolar</h5>
                <div class="table-responsive rounded-3 mb-4">
                    <table class="table table-hover align-middle mb-0 text-white">
                        <thead class="text-muted" style="background: rgba(255,255,255,0.01);">
                            <tr>
                                <th>Depo Adı</th>
                                <th>Konum</th>
                                <th>Mevcut Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Merkez Depo (Main Warehouse)</td>
                                <td>İstanbul, TR</td>
                                <td><?= (int)($product['total_stock'] ?? 0) ?> adet</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 7. SEO -->
        <div class="tab-pane fade" id="panel-seo" role="tabpanel">
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-4 fs-6">Arama Motoru Optimizasyonu (SEO)</h4>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Meta Title</label>
                    <input type="text" name="seo[title]" value="<?= htmlspecialchars($seo['title'] ?? '') ?>" class="search-input w-100 text-white" placeholder="Boş bırakılırsa ürün adı kullanılır.">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Meta Description</label>
                    <textarea name="seo[description]" class="search-input w-100 text-white" rows="2" placeholder="Ürün kısa açıklaması veya özetini girin."><?= htmlspecialchars($seo['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Meta Keywords</label>
                    <input type="text" name="seo[keywords]" value="<?= htmlspecialchars($seo['keywords'] ?? '') ?>" class="search-input w-100 text-white" placeholder="etiket1, etiket2">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Canonical URL</label>
                        <input type="text" name="seo[canonical_url]" value="<?= htmlspecialchars($seo['canonical_url'] ?? '') ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Robots Tag</label>
                        <select name="seo[robots]" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                            <option value="index, follow" <?= ($seo['robots'] ?? '') === 'index, follow' ? 'selected' : '' ?>>Index, Follow</option>
                            <option value="noindex, nofollow" <?= ($seo['robots'] ?? '') === 'noindex, nofollow' ? 'selected' : '' ?>>Noindex, Nofollow</option>
                        </select>
                    </div>
                </div>

                <h5 class="text-white font-weight-600 mb-3 fs-7">Sosyal Medya Paylaşımları (OpenGraph / Twitter Card)</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">OG Title (Sosyal Paylaşım Başlığı)</label>
                        <input type="text" name="seo[og_title]" value="<?= htmlspecialchars($seo['og_title'] ?? '') ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">OG Description (Paylaşım Açıklaması)</label>
                        <input type="text" name="seo[og_description]" value="<?= htmlspecialchars($seo['og_description'] ?? '') ?>" class="search-input w-100 text-white">
                    </div>
                </div>

                <h5 class="text-white font-weight-600 mb-3 fs-7">JSON-LD Product Schema Önizleme</h5>
                <pre class="p-3 rounded-3 text-white fs-8" style="background: rgba(0,0,0,0.2); border: 1px solid var(--sm-border); overflow-x: auto; max-height: 250px;"><code><?= htmlspecialchars($jsonLdString) ?></code></pre>
            </div>
        </div>

        <!-- 8. MEDYA -->
        <div class="tab-pane fade" id="panel-medya" role="tabpanel">
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-4 fs-6">Medya & Görsel Kütüphanesi</h4>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-white font-weight-600 mb-2">Kapak Resmi</label>
                        <div class="d-flex gap-2 mb-2">
                            <input type="hidden" name="cover_image_id" id="coverImageId" value="<?= $product['cover_image_id'] ?>">
                            <button type="button" class="btn btn-secondary border-0 btn-choose-media" data-target="#coverImageId" data-preview="#coverPreview">Medya Seç</button>
                        </div>
                        <div id="coverPreview">
                            <?php if (!empty($product['cover_path'])): ?>
                                <img src="<?= url($product['cover_path']) ?>" class="rounded-3" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid var(--sm-border);">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-white font-weight-600 mb-2">Ürün Galerisi</label>
                        <div class="d-flex gap-2 mb-2">
                            <input type="hidden" name="gallery_image_ids[]" id="galleryImageIds" value="<?= implode(',', array_column($gallery, 'image_id')) ?>">
                            <button type="button" class="btn btn-secondary border-0 btn-choose-media" data-target="#galleryImageIds" data-preview="#galleryPreview" data-multiple="true">Galeri Seç</button>
                        </div>
                        <div id="galleryPreview" class="d-flex gap-2 flex-wrap">
                            <?php foreach ($gallery as $g): ?>
                                <img src="<?= url($g['filepath']) ?>" class="rounded-3" style="width: 60px; height: 60px; object-fit: cover; border: 1px solid var(--sm-border);">
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <hr style="background-color: var(--sm-border); height: 1px; margin: 24px 0;">
                <h5 class="text-white font-weight-600 mb-3 fs-7">Video & 360 Görsel Linkleri</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">YouTube Video Linki</label>
                        <input type="text" name="youtube_url" value="<?= htmlspecialchars($product['youtube_url'] ?? '') ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Vimeo Video Linki</label>
                        <input type="text" name="vimeo_url" value="<?= htmlspecialchars($product['vimeo_url'] ?? '') ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">MP4 Video Linki</label>
                        <input type="text" name="mp4_url" value="<?= htmlspecialchars($product['mp4_url'] ?? '') ?>" class="search-input w-100 text-white">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label text-muted fs-7 mb-1">360 Görsel Frame JSON (Dinamik Varlıklar)</label>
                    <textarea name="images_360" class="search-input w-100 text-white" rows="2" placeholder='["url1", "url2"]'><?= htmlspecialchars($product['images_360'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- 9. BELGELER -->
        <div class="tab-pane fade" id="panel-belgeler" role="tabpanel">
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="text-white font-weight-600 m-0 fs-6">Kullanım Kılavuzu & Dökümanlar</h4>
                    <button type="button" class="btn btn-sm btn-secondary border-0" id="addDocBtn"><i class="bi bi-file-earmark-arrow-up me-1"></i>Belge Ekle</button>
                </div>
                <div id="docsContainer">
                    <?php 
                    $docIndex = 0;
                    foreach ($files as $f): 
                    ?>
                        <div class="row g-2 mb-2 align-items-center doc-row">
                            <div class="col-md-5">
                                <input type="text" name="product_files[<?= $docIndex ?>][name]" class="search-input w-100 text-white" placeholder="Belge Başlığı" value="<?= htmlspecialchars($f['name']) ?>" required>
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="product_files[<?= $docIndex ?>][path]" class="search-input w-100 text-white" placeholder="Medya URL/Path" value="<?= htmlspecialchars($f['path']) ?>" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger bg-opacity-10 border-0 remove-doc-btn p-2"><i class="bi bi-trash text-danger"></i></button>
                            </div>
                        </div>
                    <?php 
                        $docIndex++;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>

        <!-- 10. KARGO -->
        <div class="tab-pane fade" id="panel-kargo" role="tabpanel">
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-4 fs-6">Desi & Kargo Boyutları</h4>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Ağırlık (kg)</label>
                        <input type="number" step="0.01" name="weight" value="<?= (float)($product['weight'] ?? 0.0) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Desi</label>
                        <input type="number" step="0.01" name="desi" value="<?= (float)($product['desi'] ?? 0.0) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">En (cm)</label>
                        <input type="number" step="0.01" name="width" value="<?= $product['width'] ? (float)$product['width'] : '' ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Boy (cm)</label>
                        <input type="number" step="0.01" name="length" value="<?= $product['length'] ? (float)$product['length'] : '' ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted fs-7 mb-1">Yükseklik (cm)</label>
                        <input type="number" step="0.01" name="height" value="<?= $product['height'] ? (float)$product['height'] : '' ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-2">
                        <div class="form-check p-3 rounded-3 mt-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border);">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="free_shipping" value="1" id="checkFreeShipping" <?= (int)($product['free_shipping'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label text-white" for="checkFreeShipping">Ücretsiz Kargo</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 11. İLİŞKİLİ ÜRÜNLER -->
        <div class="tab-pane fade" id="panel-iliskili" role="tabpanel">
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-4 fs-6">İlişkili Ürün & Pazarlama Yönetimi</h4>
                
                <?php 
                $types = [
                    'similar' => 'Benzer Ürünler',
                    'complementary' => 'Birlikte Alınanlar',
                    'cross_sell' => 'Çapraz Satışlar (Cross-Sell)',
                    'upsell' => 'Upsell Ürünleri'
                ];
                foreach ($types as $typeKey => $typeName):
                    $selectedRels = [];
                    foreach ($relations as $rel) {
                        if ($rel['relation_type'] === $typeKey) {
                            $selectedRels[] = (int)$rel['related_product_id'];
                        }
                    }
                ?>
                    <div class="mb-4">
                        <label class="form-label text-white font-weight-600 mb-2"><?= htmlspecialchars($typeName) ?></label>
                        <select name="relations[<?= $typeKey ?>][]" class="form-select border-0 text-white fs-7 relations-select w-100" multiple style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important;">
                            <?php foreach ($allProducts as $ap): 
                                if ((int)$ap['id'] === (int)$product['id']) continue;
                            ?>
                                <option value="<?= $ap['id'] ?>" <?= in_array((int)$ap['id'], $selectedRels) ? 'selected' : '' ?>><?= htmlspecialchars($ap['name']) ?> (<?= htmlspecialchars($ap['sku']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 12. GEÇMİŞ -->
        <div class="tab-pane fade" id="panel-gecmis" role="tabpanel">
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-4 fs-6">Değişiklik ve Güncelleme Geçmişi</h4>
                <div class="table-responsive rounded-3">
                    <table class="table table-hover align-middle mb-0 text-white">
                        <thead class="text-muted" style="background: rgba(255,255,255,0.01);">
                            <tr>
                                <th>Tarih</th>
                                <th>Kullanıcı</th>
                                <th>Eylem</th>
                                <th>IP Adresi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Bu ürüne ait denetim logu bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td><?= date('d.m.Y H:i:s', strtotime($h['created_at'])) ?></td>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-10 text-white border-0 text-capitalize">
                                                <?= htmlspecialchars($h['admin_name'] ?? 'Sistem') ?> (<?= htmlspecialchars($h['user_type']) ?>)
                                            </span>
                                        </td>
                                        <td><code class="text-warning"><?= htmlspecialchars($h['event']) ?></code></td>
                                        <td class="text-muted"><?= htmlspecialchars($h['ip_address']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Form Footer Actions -->
    <div class="d-flex justify-content-end gap-2 mb-5">
        <a href="<?= url('/admin/products') ?>" class="btn btn-secondary border-0">İptal</a>
        <button type="submit" class="btn">Değişiklikleri Kaydet</button>
    </div>
</form>

<!-- Include Media Picker Modal -->
<?php include dirname(__DIR__) . '/media/media_picker_modal.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. TinyMCE Initializer
    tinymce.init({
        selector: '#descriptionEditor',
        height: 400,
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image media table | code fullscreen',
        skin: 'oxide-dark',
        content_css: 'dark'
    });

    // 2. Kâr ve Kar Marjı Hesaplama Matrisi
    const costPriceInput = document.getElementById('costPrice');
    const priceInput = document.getElementById('priceInput');

    const profitVal = document.getElementById('profitVal');
    const profitRateVal = document.getElementById('profitRateVal');
    const profitMarginVal = document.getElementById('profitMarginVal');

    function calculateProfit() {
        const cost = parseFloat(costPriceInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;

        const profit = price - cost;
        const profitRate = cost > 0 ? (profit / cost) * 100 : 0;
        const profitMargin = price > 0 ? (profit / price) * 100 : 0;

        profitVal.textContent = profit.toFixed(2) + ' TRY';
        profitRateVal.textContent = profitRate.toFixed(2) + '%';
        profitMarginVal.textContent = profitMargin.toFixed(2) + '%';

        if (profit < 0) {
            profitVal.className = 'fs-5 text-danger font-weight-700';
        } else {
            profitVal.className = 'fs-5 text-success font-weight-700';
        }
    }

    costPriceInput.addEventListener('input', calculateProfit);
    priceInput.addEventListener('input', calculateProfit);
    calculateProfit();

    // 3. Dynamic Media Picker Integration
    document.querySelectorAll('.btn-choose-media').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const targetInput = document.querySelector(btn.dataset.target);
            const previewEl = document.querySelector(btn.dataset.preview);
            const isMultiple = btn.dataset.multiple === 'true';

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

    // 4. Dynamic documents list
    const addDocBtn = document.getElementById('addDocBtn');
    const docsContainer = document.getElementById('docsContainer');
    let docIndex = <?= $docIndex ?>;

    addDocBtn.addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 align-items-center doc-row';
        row.innerHTML = `
            <div class="col-md-5">
                <input type="text" name="product_files[${docIndex}][name]" class="search-input w-100 text-white" placeholder="Belge Başlığı" required>
            </div>
            <div class="col-md-5">
                <input type="text" name="product_files[${docIndex}][path]" class="search-input w-100 text-white" placeholder="Medya URL/Path" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger bg-opacity-10 border-0 remove-doc-btn p-2"><i class="bi bi-trash text-danger"></i></button>
            </div>
        `;
        docsContainer.appendChild(row);

        row.querySelector('.remove-doc-btn').addEventListener('click', function() {
            row.remove();
        });

        docIndex++;
    });

    document.querySelectorAll('.remove-doc-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            btn.closest('.doc-row').remove();
        });
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
