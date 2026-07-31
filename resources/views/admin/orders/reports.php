<?php
use App\Helpers\ComponentHelper;

$title = "Sipariş ve Finansal Raporlar - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Siparişler' => url('/admin/orders'),
        'Finansal Raporlar' => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Sipariş ve Finansal Raporlar</h2>
        <div class="d-flex gap-2">
            <a href="<?= url('/admin/orders') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Sipariş Listesi</a>
        </div>
    </div>
</div>

<!-- 1. Temel Özet Kartları -->
<div class="row g-3 mb-4">
    <!-- Toplam Sipariş -->
    <div class="col-md-3 col-sm-6">
        <div class="card p-3 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 16px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted fs-8 mb-1">Toplam Sipariş</h6>
                    <h3 class="m-0 font-weight-700"><?= number_format((float)($data['summary']['total_orders'] ?? 0)) ?> Adet</h3>
                </div>
                <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-circle">
                    <i class="bi bi-cart-check-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Toplam Ciro -->
    <div class="col-md-3 col-sm-6">
        <div class="card p-3 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 16px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted fs-8 mb-1">Toplam Ciro (Revenue)</h6>
                    <h3 class="m-0 font-weight-700 text-warning"><?= number_format((float)($data['summary']['total_revenue'] ?? 0.0), 2) ?> TRY</h3>
                </div>
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle">
                    <i class="bi bi-currency-dollar fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Ortalama Sepet Tutarı -->
    <div class="col-md-3 col-sm-6">
        <div class="card p-3 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 16px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted fs-8 mb-1">Sepet Ortalaması (AOV)</h6>
                    <h3 class="m-0 font-weight-700"><?= number_format((float)($data['summary']['avg_basket'] ?? 0.0), 2) ?> TRY</h3>
                </div>
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle">
                    <i class="bi bi-basket-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Toplam İndirimler -->
    <div class="col-md-3 col-sm-6">
        <div class="card p-3 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 16px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted fs-8 mb-1">Yapılan Toplam İndirim</h6>
                    <h3 class="m-0 font-weight-700 text-danger"><?= number_format((float)($data['summary']['total_discount'] ?? 0.0), 2) ?> TRY</h3>
                </div>
                <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle">
                    <i class="bi bi-tags-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- EN ÇOK SATAN ÜRÜNLER -->
    <div class="col-md-6">
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-award-fill me-2 text-warning"></i>En Çok Satan Ürünler</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-hover">
                    <thead>
                        <tr class="text-muted fs-7 border-bottom border-secondary">
                            <th>Ürün Adı</th>
                            <th>Miktar</th>
                            <th class="text-end">Ciro</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        <?php if (empty($data['top_products'])): ?>
                            <tr>
                                <td colspan="3" class="text-center py-3 text-muted">Veri bulunamadı.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['top_products'] as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['product_name']) ?></td>
                                    <td><strong><?= $p['qty'] ?> Adet</strong></td>
                                    <td class="text-end text-warning"><?= number_format((float)$p['revenue'], 2) ?> TRY</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- EN DEĞERLİ MÜŞTERİLER -->
    <div class="col-md-6">
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-people-fill me-2 text-warning"></i>En Çok Alışveriş Yapan Müşteriler</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-hover">
                    <thead>
                        <tr class="text-muted fs-7 border-bottom border-secondary">
                            <th>Müşteri</th>
                            <th>Sipariş Adedi</th>
                            <th class="text-end">Toplam Harcama</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        <?php if (empty($data['top_customers'])): ?>
                            <tr>
                                <td colspan="3" class="text-center py-3 text-muted">Veri bulunamadı.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['top_customers'] as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['billing_first_name'] . ' ' . $c['billing_last_name']) ?></td>
                                    <td><strong><?= $c['orders_count'] ?> Sipariş</strong></td>
                                    <td class="text-end text-success"><?= number_format((float)$c['total_spent'], 2) ?> TRY</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
