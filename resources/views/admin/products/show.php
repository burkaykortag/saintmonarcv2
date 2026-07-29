<?php
use App\Helpers\ComponentHelper;

$title = "Ürün Detayı - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Ürünler' => url('/admin/products'), 'Ürün Detayı' => '#']) ?>
        <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;"><?= htmlspecialchars($product['name']) ?></h2>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/products/edit?id=' . $product['id']) ?>" class="btn">
            <i class="bi bi-pencil-square me-2"></i> Ürünü Düzenle
        </a>
        <a href="<?= url('/admin/products') ?>" class="btn btn-secondary border-0">Geri Dön</a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Details -->
    <div class="col-12 col-lg-8">
        
        <!-- General Info Card -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;">Ürün Bilgileri</h4>
            <table class="table table-dark table-borderless m-0">
                <tbody>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2" width="200">Alt Başlık</td>
                        <td class="text-white py-2"><?= htmlspecialchars($product['subtitle'] ?? '-') ?></td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">Kategori</td>
                        <td class="text-white py-2"><?= htmlspecialchars($product['category_name'] ?? 'Kategorisiz') ?></td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">Marka</td>
                        <td class="text-white py-2"><?= htmlspecialchars($product['brand_name'] ?? 'Markasız') ?></td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">Ürün Tipi</td>
                        <td class="text-white py-2 text-capitalize"><?= htmlspecialchars($product['product_type']) ?></td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">Durum</td>
                        <td class="text-white py-2">
                            <span class="badge bg-primary text-capitalize"><?= htmlspecialchars($product['status']) ?></span>
                        </td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">Kısa Açıklama</td>
                        <td class="text-white py-2"><?= htmlspecialchars($product['short_description'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2">Detaylı Açıklama</td>
                        <td class="text-white py-2"><?= nl2br(htmlspecialchars($product['description'] ?? '-')) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Identifiers / Coding Codes Card -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;">Kimlik & Kodlama Bilgileri</h4>
            <table class="table table-dark table-borderless m-0">
                <tbody>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2" width="200">SKU (Stok Kodu)</td>
                        <td class="text-warning font-weight-600 py-2"><code><?= htmlspecialchars($product['sku']) ?></code></td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">Barkod (EAN/UPC)</td>
                        <td class="text-white py-2"><?= htmlspecialchars($product['barcode'] ?? '-') ?></td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">GTIN</td>
                        <td class="text-white py-2"><?= htmlspecialchars($product['gtin'] ?? '-') ?></td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">EAN</td>
                        <td class="text-white py-2"><?= htmlspecialchars($product['ean'] ?? '-') ?></td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">UPC</td>
                        <td class="text-white py-2"><?= htmlspecialchars($product['upc'] ?? '-') ?></td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">MPN</td>
                        <td class="text-white py-2"><?= htmlspecialchars($product['mpn'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2">Model No</td>
                        <td class="text-white py-2"><?= htmlspecialchars($product['model_no'] ?? '-') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pricing Card -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;">Fiyatlandırma & Karlılık Analizi</h4>
            <div class="row g-3 text-center mb-3">
                <div class="col-4">
                    <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.01); border:1px solid var(--sm-border);">
                        <span class="text-muted fs-8 d-block mb-1">Maliyet Fiyatı</span>
                        <h4 class="text-white font-weight-700 m-0">₺<?= number_format((float)$product['cost_price'], 2) ?></h4>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.01); border:1px solid var(--sm-border);">
                        <span class="text-muted fs-8 d-block mb-1">Satış Fiyatı</span>
                        <h4 class="text-success font-weight-700 m-0">₺<?= number_format((float)$product['price'], 2) ?></h4>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.01); border:1px solid var(--sm-border);">
                        <span class="text-muted fs-8 d-block mb-1">İndirimli Fiyat</span>
                        <h4 class="text-warning font-weight-700 m-0">₺<?= $product['special_price'] ? number_format((float)$product['special_price'], 2) : '-' ?></h4>
                    </div>
                </div>
            </div>
            
            <table class="table table-dark table-borderless m-0">
                <tbody>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2" width="200">Kar Tutarı</td>
                        <td class="text-success font-weight-600 py-2">₺<?= number_format((float)$product['profit'], 2) ?></td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">Kar Marjı (Margin)</td>
                        <td class="text-info font-weight-600 py-2"><?= number_format((float)$product['profit_margin'], 2) ?>%</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2">Kar Oranı (Rate)</td>
                        <td class="text-warning font-weight-600 py-2"><?= number_format((float)$product['profit_rate'], 2) ?>%</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Right Column: Visuals & Switches status -->
    <div class="col-12 col-lg-4">
        
        <!-- cover preview -->
        <div class="card p-4 border-0 mb-4 text-center" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 text-start" style="font-size: 16px;">Ürün Görseli</h4>
            <div class="p-3 rounded-3 border d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.2); border-color: var(--sm-border) !important; height:200px;">
                <?php if (!empty($product['cover_path'])): ?>
                    <img src="<?= url('/' . $product['cover_path']) ?>" class="img-fluid rounded-3" style="max-height: 180px; object-fit: contain;">
                <?php else: ?>
                    <span class="text-muted fs-8">Görsel Seçilmedi</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Inventory and logistics -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;">Stok & Kargo Durumu</h4>
            <table class="table table-dark table-borderless m-0">
                <tbody>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">Toplam Stok</td>
                        <td class="text-white font-weight-600 py-2"><?= $product['total_stock'] ?> Adet</td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">Kritik Stok</td>
                        <td class="text-white py-2"><?= $product['critical_stock'] ?> Adet</td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">Kargo Desi</td>
                        <td class="text-white py-2"><?= $product['desi'] ?? '-' ?> Desi</td>
                    </tr>
                    <tr class="border-bottom border-secondary-subtle">
                        <td class="text-muted py-2">Ağırlık</td>
                        <td class="text-white py-2"><?= $product['weight'] ?? '-' ?> kg</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2">Teslim Süresi</td>
                        <td class="text-white py-2"><?= htmlspecialchars($product['delivery_time'] ?? '-') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Toggle Parameters status -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;">Parametre Durumları</h4>
            <div class="d-flex flex-column gap-2">
                <div class="d-flex justify-content-between align-items-center py-1">
                    <span class="text-muted fs-7">Aktif Ürün</span>
                    <span class="badge <?= $product['is_active'] ? 'bg-success' : 'bg-danger' ?>"><?= $product['is_active'] ? 'Aktif' : 'Pasif' ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1">
                    <span class="text-muted fs-7">Yeni Ürün</span>
                    <span class="badge <?= $product['is_new'] ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= $product['is_new'] ? 'Evet' : 'Hayır' ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1">
                    <span class="text-muted fs-7">Çok Satan</span>
                    <span class="badge <?= $product['is_bestseller'] ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= $product['is_bestseller'] ? 'Evet' : 'Hayır' ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1">
                    <span class="text-muted fs-7">Öne Çıkan</span>
                    <span class="badge <?= $product['is_featured'] ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= $product['is_featured'] ? 'Evet' : 'Hayır' ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-1">
                    <span class="text-muted fs-7">Ana Sayfada Göster</span>
                    <span class="badge <?= $product['show_in_home'] ? 'bg-info' : 'bg-secondary' ?>"><?= $product['show_in_home'] ? 'Evet' : 'Hayır' ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
