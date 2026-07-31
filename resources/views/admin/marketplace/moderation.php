<?php
$title = 'Ürün Moderasyonu | VEYRA Marketplace';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-white font-weight-bold">Ürün Moderasyon Merkezi</h1>
            <p class="text-muted mb-0">Satıcılar tarafından eklenen ve pazaryerinde yayına girmek için onay bekleyen ürünler</p>
        </div>
        <a href="<?= url('/admin/marketplace/dashboard') ?>" class="btn btn-outline-secondary btn-sm">← Pazaryeri Dashboard</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show bg-dark text-success border-success" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card bg-dark border-secondary">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                        <tr class="text-muted small">
                            <th>ID</th>
                            <th>Ürün Adı & SKU</th>
                            <th>Satıcı</th>
                            <th>Marka</th>
                            <th>Fiyat</th>
                            <th>Onay Durumu</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Onay bekleyen ürün bulunmamaktadır. Tüm ürünler incelendi.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td>#<?= $p['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($p['name'] ?? 'İsimsiz Ürün') ?></div>
                                    <small class="text-muted">SKU: <?= htmlspecialchars($p['sku'] ?? '-') ?></small>
                                </td>
                                <td><span class="badge bg-primary bg-opacity-20 text-primary border border-primary"><?= htmlspecialchars($p['vendor_name'] ?? 'SaintMonarc') ?></span></td>
                                <td><?= htmlspecialchars($p['brand_name'] ?? '-') ?></td>
                                <td class="fw-bold text-white">₺<?= number_format((float)$p['price'], 2) ?></td>
                                <td><span class="badge bg-warning"><?= htmlspecialchars($p['approval_status']) ?></span></td>
                                <td class="text-end">
                                    <form method="POST" action="<?= url('/admin/marketplace/moderation/action') ?>" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn btn-sm btn-success me-1"><i class="bi bi-check-lg"></i> Yayına Al (Onayla)</button>
                                    </form>
                                    <form method="POST" action="<?= url('/admin/marketplace/moderation/action') ?>" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Reddet</button>
                                    </form>
                                </td>
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
