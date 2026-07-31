$title = "SaintMonarc Premium E-Ticaret Deneyimi";

<!-- Hero Slider Section -->
<section class="position-relative py-5 overflow-hidden bg-dark text-white" style="min-height: 480px; display: flex; align-items: center; background: radial-gradient(circle at 80% 20%, #1e1b4b 0%, #060210 100%);">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <span class="badge bg-gold text-white mb-3" style="background-color: var(--store-accent); font-size: 11px; letter-spacing: 1px;">HAFTANIN ÖNE ÇIKANLARI</span>
                <h1 class="display-4 font-weight-800 mb-3" style="font-weight: 800; letter-spacing: -2px; line-height: 1.1;">Geleceğin Teknolojisi, Bugün Vitrinde.</h1>
                <p class="text-secondary fs-6 mb-4" style="line-height: 1.6;">Yapay zekâ destekli en yeni elektronik cihazlar, premium saatler ve şık moda koleksiyonlarında lansmana özel avantajlar.</p>
                <div class="d-flex gap-3">
                    <a href="<?= url('/products') ?>" class="btn px-4 py-3 rounded-pill font-weight-600 text-white" style="background-color: var(--store-accent);">Şimdi Keşfet</a>
                    <a href="#" class="btn btn-outline-light px-4 py-3 rounded-pill font-weight-600">Kampanyalar</a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <!-- Premium visualization placeholder -->
                <div class="d-inline-block bg-white bg-opacity-5 p-4 rounded-4 border border-secondary border-opacity-10 shadow-lg">
                    <div style="width: 320px; height: 320px; background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 20px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                        <i class="bi bi-cpu text-white display-3 mb-2" style="color: var(--store-accent) !important;"></i>
                        <span class="text-white fs-6 font-weight-600">SM AI recommendation engine</span>
                        <span class="text-secondary fs-8">Sprint 17 Integrated</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Category Cards Section -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="font-weight-800 m-0" style="font-weight: 800;">Kategorilere Göre Göz Atın</h3>
                <p class="text-muted mb-0 fs-7">Sizin için özenle seçilmiş en popüler kategorilerimiz.</p>
            </div>
            <a href="<?= url('/products') ?>" class="text-dark text-decoration-none font-weight-600 fs-7">Tümünü Gör <i class="bi bi-arrow-right ms-1"></i></a>
        </div>

        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
                <div class="col-md-3 col-6">
                    <a href="<?= url('/category/' . $cat['slug']) ?>" class="text-decoration-none">
                        <div class="bg-white p-4 rounded-3 border border-secondary border-opacity-10 text-center shadow-sm hover-translate-y">
                            <i class="bi bi-tag-fill text-muted fs-4 mb-2 d-block" style="color: var(--store-accent) !important;"></i>
                            <h6 class="text-dark font-weight-600 m-0"><?= htmlspecialchars($cat['name']) ?></h6>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Product Grid Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="font-weight-800 m-0" style="font-weight: 800;">En Çok Satan Ürünler</h3>
                <p class="text-muted mb-0 fs-7">En popüler ve en çok tercih edilen premium ürünler.</p>
            </div>
            <span class="badge bg-purple text-white py-2 px-3 rounded-pill" style="background: linear-gradient(135deg, #6366f1, #a855f7); font-size: 11px;"><i class="bi bi-stars me-1"></i> AI Önerileri Aktif</span>
        </div>

        <div class="row g-4">
            <?php foreach ($products as $p): ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="product-card">
                        <!-- AI & Discount Badges -->
                        <span class="badge-ai"><i class="bi bi-stars"></i> AI</span>
                        <?php if (isset($p['discount_rate']) && $p['discount_rate'] > 0): ?>
                            <span class="badge-discount">%<?= (int)$p['discount_rate'] ?> İNDİRİM</span>
                        <?php endif; ?>

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
                            
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-secondary border-opacity-10">
                                <span class="text-dark font-weight-700 fs-5" style="font-weight: 700;"><?= number_format((float)($p['price'] ?? 0), 2, ',', '.') ?> TL</span>
                                <button class="btn btn-sm btn-outline-dark rounded-circle" onclick="addToCart(<?= $p['id'] ?>)"><i class="bi bi-bag-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Brand Showcase -->
<section class="py-5">
    <div class="container">
        <h4 class="font-weight-800 text-center mb-4" style="font-weight: 800; letter-spacing: -0.5px;">Premium Markalar</h4>
        <div class="row g-3 justify-content-center">
            <?php foreach ($brands as $b): ?>
                <div class="col-md-2 col-4">
                    <a href="<?= url('/brand/' . $b['slug']) ?>" class="text-decoration-none">
                        <div class="bg-white p-3 rounded-3 border border-secondary border-opacity-10 text-center shadow-sm">
                            <span class="text-dark font-weight-600 fs-7"><?= htmlspecialchars($b['name']) ?></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
    function addToCart(productId) {
        alert('Ürün sepete eklendi! (ID: ' + productId + ')');
    }
</script>
