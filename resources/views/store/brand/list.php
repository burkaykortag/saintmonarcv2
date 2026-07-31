<?php
$title = "Marka Sayfası - SaintMonarc";

?>

<div class="container py-4 text-dark">
    <!-- Brand Header -->
    <div class="bg-white p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm mb-4 d-flex align-items-center gap-4">
        <div style="width: 80px; height: 80px; background-color: var(--store-bg); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-award-fill text-muted fs-2"></i>
        </div>
        <div>
            <h3 class="font-weight-800 m-0" style="font-weight: 800;"><?= htmlspecialchars(ucfirst($slug ?? 'Premium Marka')) ?></h3>
            <p class="text-muted mb-0 fs-7">Markaya ait en özel koleksiyonları ve ürünleri listeleyin.</p>
        </div>
    </div>

    <!-- Product Grid -->
    <div class="row g-3">
        <?php foreach ($products as $p): ?>
            <div class="col-md-3 col-6">
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

<script>
    function addToCart(id) {
        alert('Ürün sepete eklendi!');
    }
</script>


