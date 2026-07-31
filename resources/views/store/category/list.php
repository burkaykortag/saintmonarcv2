<?php
$title = "Ürün Listesi - SaintMonarc";

?>

<div class="container py-4 text-dark">
    <div class="row g-4">
        <!-- Sol Taraf: Filtreleme Sidebarı -->
        <div class="col-lg-3">
            <div class="bg-white p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm">
                <h5 class="font-weight-800 mb-4" style="font-weight: 800;">Filtreler</h5>

                <!-- Fiyat Aralığı -->
                <div class="mb-4 pb-3 border-bottom">
                    <h6 class="font-weight-700 fs-7 mb-3">Fiyat Aralığı</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" placeholder="Min" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <input type="number" placeholder="Max" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>

                <!-- Markalar -->
                <div class="mb-4 pb-3 border-bottom">
                    <h6 class="font-weight-700 fs-7 mb-3">Markalar</h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="brandApple">
                        <label class="form-check-label fs-7" for="brandApple">Apple</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="brandNike">
                        <label class="form-check-label fs-7" for="brandNike">Nike</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="brandZara">
                        <label class="form-check-label fs-7" for="brandZara">Zara</label>
                    </div>
                </div>

                <!-- Stok Durumu -->
                <div class="mb-4 pb-3 border-bottom">
                    <h6 class="font-weight-700 fs-7 mb-3">Stok Durumu</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="inStockOnly" checked>
                        <label class="form-check-label fs-7" for="inStockOnly">Yalnızca Stoktakiler</label>
                    </div>
                </div>

                <!-- AI Akıllı Filtre Önerisi -->
                <div class="bg-light p-3 rounded-3 border border-purple border-opacity-25" style="border-color: #a855f7 !important;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-stars text-purple" style="color: #a855f7;"></i>
                        <span class="font-weight-700 fs-7 text-dark">AI Filtre Önerisi</span>
                    </div>
                    <p class="text-muted fs-8 mb-0" style="font-size: 11px;">Son aramalarınıza dayanarak size en uygun ürün özelliklerini listeledik.</p>
                </div>
            </div>
        </div>

        <!-- Sağ Taraf: Ürün Grid Listesi -->
        <div class="col-lg-9">
            <!-- Üst Sıralama HUD -->
            <div class="bg-white p-3 rounded-4 border border-secondary border-opacity-10 shadow-sm mb-4 d-flex justify-content-between align-items-center">
                <span class="text-muted fs-7">Toplam <strong><?= count($products) ?></strong> ürün listeleniyor</span>
                <div class="d-flex gap-2 align-items-center">
                    <span class="fs-7 text-muted">Sıralama:</span>
                    <select class="form-select form-select-sm border-0 bg-light rounded-pill px-3" style="width: 160px;">
                        <option value="featured">Önerilen</option>
                        <option value="price_asc">Fiyat: Düşükten Yükseğe</option>
                        <option value="price_desc">Fiyat: Yüksekten Düşüğe</option>
                    </select>
                </div>
            </div>

            <!-- Ürünler Grid -->
            <div class="row g-3">
                <?php foreach ($products as $p): ?>
                    <div class="col-md-4 col-6">
                        <div class="product-card">
                            <span class="badge-ai"><i class="bi bi-stars"></i> AI</span>
                            <div class="product-image-wrapper">
                                <div class="position-absolute w-100 h-100 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-image text-muted display-6"></i>
                                </div>
                            </div>
                            <div class="p-3">
                                <small class="text-muted fs-8">SKU: <?= htmlspecialchars($p['sku'] ?? 'N/A') ?></small>
                                <h6 class="text-dark font-weight-600 my-1 text-truncate">
                                    <a href="<?= url('/product/' . $p['slug']) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($p['name']) ?></a>
                                </h6>
                                <p class="text-muted fs-7 text-truncate"><?= htmlspecialchars($p['short_description'] ?? '') ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                    <span class="text-dark font-weight-700 fs-6"><?= number_format((float)($p['price'] ?? 0), 2, ',', '.') ?> TL</span>
                                    <button class="btn btn-xs btn-outline-dark rounded-circle" onclick="addToCart(<?= $p['id'] ?>)"><i class="bi bi-bag-plus"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function addToCart(id) {
        alert('Ürün sepete eklendi!');
    }
</script>


