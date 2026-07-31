<?php
$title = 'Satıcı Satış Merkezi | VEYRA Marketplace';
include dirname(__DIR__) . '/admin/layouts/header.php';
?>

<div class="container-fluid py-4">
    <!-- Vendor Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="badge bg-primary me-2">VEYRA Satıcı Portalı</span>
            <h1 class="h3 mb-0 text-white font-weight-bold d-inline align-middle"><?= htmlspecialchars($vendor['name'] ?? 'Mağazam') ?></h1>
            <p class="text-muted mb-0">Mağaza Yönetimi, Ürünler, Siparişler ve Finansal Cüzdan</p>
        </div>
        <div>
            <a href="<?= url('/admin/vendors/wallet?vendor_id=' . ($vendor['id'] ?? 1)) ?>" class="btn btn-success btn-sm">
                <i class="bi bi-wallet2 me-1"></i> Bakiye: ₺<?= number_format((float)($wallet['balance'] ?? 0), 2) ?>
            </a>
        </div>
    </div>

    <!-- Vendor KPI Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="text-muted small">Kullanılabilir Bakiye</div>
                    <div class="fs-3 fw-bold text-success">₺<?= number_format((float)($wallet['balance'] ?? 0), 2) ?></div>
                    <div class="text-muted small mt-1">Bekleyen Hakediş: ₺<?= number_format((float)($wallet['pending_payout'] ?? 0), 2) ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="text-muted small">Toplam Satış Hacmi</div>
                    <div class="fs-3 fw-bold text-white">₺<?= number_format((float)($stats['total_sales'] ?? 0), 2) ?></div>
                    <div class="text-info small mt-1">Toplam Sipariş: <?= (int)($stats['total_orders'] ?? 0) ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="text-muted small">Yayındaki Ürün Sayısı</div>
                    <div class="fs-3 fw-bold text-info"><?= $productCount ?></div>
                    <div class="text-muted small mt-1">Komisyon Oranınız: %<?= number_format((float)($vendor['commission_rate'] ?? 10), 2) ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="text-muted small">Mağaza Değerlendirmesi</div>
                    <div class="fs-3 fw-bold text-warning"><i class="bi bi-star-fill"></i> <?= number_format((float)($vendor['rating'] ?? 5.0), 2) ?> / 5.0</div>
                    <div class="text-success small mt-1"><i class="bi bi-shield-check"></i> Onaylı Satıcı</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Onboarding -->
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header border-secondary bg-transparent">
                    <h5 class="card-title text-white mb-0">Hızlı İşlemler</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="<?= url('/admin/products') ?>" class="btn btn-outline-light text-start p-3">
                        <i class="bi bi-plus-circle me-2 text-primary"></i> <strong>Yeni Ürün Ekle / Yönet</strong>
                        <div class="small text-muted ms-4">Mağazanıza yeni ürün ekleyin veya var olan ürünleri düzenleyin.</div>
                    </a>
                    <a href="<?= url('/admin/orders') ?>" class="btn btn-outline-light text-start p-3">
                        <i class="bi bi-bag-check me-2 text-success"></i> <strong>Siparişler ve Kargolar</strong>
                        <div class="small text-muted ms-4">Gelen siparişlerinizi görüntüleyin ve kargo paketlerini hazırlayın.</div>
                    </a>
                    <a href="<?= url('/admin/vendors/wallet?vendor_id=' . ($vendor['id'] ?? 1)) ?>" class="btn btn-outline-light text-start p-3">
                        <i class="bi bi-cash-stack me-2 text-warning"></i> <strong>Hakediş Talebi Oluştur</strong>
                        <div class="small text-muted ms-4">Kullanılabilir bakiyenizden banka hesabınıza ödeme talep edin.</div>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header border-secondary bg-transparent">
                    <h5 class="card-title text-white mb-0">Mağaza Bilgileri & Sözleşme</h5>
                </div>
                <div class="card-body">
                    <table class="table table-dark table-borderless small mb-0">
                        <tr>
                            <th class="text-muted w-35">Mağaza Adı:</th>
                            <td class="text-white fw-bold"><?= htmlspecialchars($vendor['name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">E-Posta:</th>
                            <td class="text-white"><?= htmlspecialchars($vendor['email'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Telefon:</th>
                            <td class="text-white"><?= htmlspecialchars($vendor['phone'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Vergi No:</th>
                            <td class="text-white"><?= htmlspecialchars($vendor['tax_number'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">İl / İlçe:</th>
                            <td class="text-white"><?= htmlspecialchars($vendor['city'] ?? '-') ?> / <?= htmlspecialchars($vendor['district'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">IBAN:</th>
                            <td class="font-monospace text-success"><?= htmlspecialchars($vendor['iban'] ?? '-') ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/admin/layouts/footer.php'; ?>
