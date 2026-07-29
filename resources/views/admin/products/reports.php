<?php
use App\Helpers\ComponentHelper;

$title = "Ürün Analiz Raporları - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Ürünler' => url('/admin/products'), 'Raporlar' => '#']) ?>
    <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Ürün Raporlama ve Analiz Raporları</h2>
    <p class="text-muted mb-0 fs-6">Katalog ürünlerinin kârlılık, popülerlik ve stok hareketlerini detaylı tablolar üzerinden inceleyin.</p>
</div>

<div class="row g-4 mb-4">
    <!-- Most Viewed -->
    <div class="col-12 col-xl-6">
        <div class="card p-4 border-0 h-100" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;"><i class="bi bi-eye me-2 text-info"></i>En Çok Görüntülenen Ürünler</h4>
            <div class="table-responsive">
                <table class="table table-dark table-hover border-0 m-0" style="background:transparent;">
                    <thead>
                        <tr class="fs-8 text-muted border-bottom border-secondary">
                            <th>Ürün Adı</th>
                            <th>SKU</th>
                            <th class="text-end">Görüntülenme</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mostViewed as $mv): ?>
                            <tr class="align-middle fs-7">
                                <td><a href="<?= url('/admin/products/edit?id=' . $mv['id']) ?>" class="text-white font-weight-600"><?= htmlspecialchars($mv['name']) ?></a></td>
                                <td><code class="text-warning"><?= htmlspecialchars($mv['sku']) ?></code></td>
                                class="text-end font-weight-600 text-info"><?= $mv['view_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Best Sellers -->
    <div class="col-12 col-xl-6">
        <div class="card p-4 border-0 h-100" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;"><i class="bi bi-cart-check me-2 text-success"></i>En Çok Satan Ürünler</h4>
            <div class="table-responsive">
                <table class="table table-dark table-hover border-0 m-0" style="background:transparent;">
                    <thead>
                        <tr class="fs-8 text-muted border-bottom border-secondary">
                            <th>Ürün Adı</th>
                            <th class="text-end">Satış Adedi</th>
                            <th class="text-end">Toplam Ciro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bestSellers as $bs): ?>
                            <tr class="align-middle fs-7">
                                <td><a href="<?= url('/admin/products/edit?id=' . $bs['id']) ?>" class="text-white font-weight-600"><?= htmlspecialchars($bs['name']) ?></a></td>
                                <td class="text-end font-weight-600 text-success"><?= $bs['total_sold'] ?></td>
                                <td class="text-end font-weight-600">₺<?= number_format((float)$bs['total_revenue'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Most Favorited -->
    <div class="col-12 col-md-6">
        <div class="card p-4 border-0 h-100" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;"><i class="bi bi-heart me-2 text-danger"></i>En Çok Favorilenen Ürünler</h4>
            <div class="table-responsive">
                <table class="table table-dark table-hover border-0 m-0" style="background:transparent;">
                    <thead>
                        <tr class="fs-8 text-muted border-bottom border-secondary">
                            <th>Ürün Adı</th>
                            <th class="text-end">Favori Sayısı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mostFavorited as $mf): ?>
                            <tr class="align-middle fs-7">
                                <td><?= htmlspecialchars($mf['name']) ?></td>
                                <td class="text-end text-danger font-weight-600"><?= $mf['favorite_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Most Added to Cart -->
    <div class="col-12 col-md-6">
        <div class="card p-4 border-0 h-100" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;"><i class="bi bi-bag-plus me-2 text-warning"></i>Sepete En Çok Eklene Ürünler</h4>
            <div class="table-responsive">
                <table class="table table-dark table-hover border-0 m-0" style="background:transparent;">
                    <thead>
                        <tr class="fs-8 text-muted border-bottom border-secondary">
                            <th>Ürün Adı</th>
                            <th class="text-end">Sepet Adedi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mostAddedToCart as $mac): ?>
                            <tr class="align-middle fs-7">
                                <td><?= htmlspecialchars($mac['name']) ?></td>
                                <td class="text-end text-warning font-weight-600"><?= $mac['cart_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Highest Profit -->
    <div class="col-12 col-xl-6">
        <div class="card p-4 border-0 h-100" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;"><i class="bi bi-arrow-up-right-circle me-2 text-success"></i>En Yüksek Kârlı Ürünler</h4>
            <div class="table-responsive">
                <table class="table table-dark table-hover border-0 m-0" style="background:transparent;">
                    <thead>
                        <tr class="fs-8 text-muted border-bottom border-secondary">
                            <th>Ürün Adı</th>
                            <th>Maliyet</th>
                            <th>Fiyat</th>
                            <th class="text-end">Kar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($highestProfit as $hp): ?>
                            <tr class="align-middle fs-7">
                                <td><?= htmlspecialchars($hp['name']) ?></td>
                                <td>₺<?= number_format((float)$hp['cost_price'], 2) ?></td>
                                <td>₺<?= number_format((float)$hp['price'], 2) ?></td>
                                <td class="text-end font-weight-600 text-success">₺<?= number_format((float)$hp['profit'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Lowest Profit -->
    <div class="col-12 col-xl-6">
        <div class="card p-4 border-0 h-100" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;"><i class="bi bi-arrow-down-left-circle me-2 text-danger"></i>En Düşük Kârlı Ürünler</h4>
            <div class="table-responsive">
                <table class="table table-dark table-hover border-0 m-0" style="background:transparent;">
                    <thead>
                        <tr class="fs-8 text-muted border-bottom border-secondary">
                            <th>Ürün Adı</th>
                            <th>Maliyet</th>
                            <th>Fiyat</th>
                            <th class="text-end">Kar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowestProfit as $lp): ?>
                            <tr class="align-middle fs-7">
                                <td><?= htmlspecialchars($lp['name']) ?></td>
                                <td>₺<?= number_format((float)$lp['cost_price'], 2) ?></td>
                                <td>₺<?= number_format((float)$lp['price'], 2) ?></td>
                                <td class="text-end font-weight-600 text-danger">₺<?= number_format((float)$lp['profit'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;"><i class="bi bi-bag-x me-2 text-secondary"></i>Hiç Satmayan Ürünler</h4>
    <div class="table-responsive">
        <table class="table table-dark table-hover border-0 m-0" style="background:transparent;">
            <thead>
                <tr class="fs-8 text-muted border-bottom border-secondary">
                    <th>Ürün Adı</th>
                    <th>SKU</th>
                    <th>Stok</th>
                    <th>Satış Fiyatı</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($nonSelling)): ?>
                    <?php foreach ($nonSelling as $ns): ?>
                        <tr class="align-middle fs-7">
                            <td class="font-weight-600"><?= htmlspecialchars($ns['name']) ?></td>
                            <td><code class="text-warning"><?= htmlspecialchars($ns['sku']) ?></code></td>
                            <td><?= $ns['total_stock'] ?></td>
                            <td>₺<?= number_format((float)$ns['price'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted fs-7">Hiç satmayan ürün bulunmamaktadır.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
