<?php
$title = 'Pazaryeri Yönetimi | VEYRA Platform';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-white font-weight-bold">VEYRA Marketplace Platform</h1>
            <p class="text-muted mb-0">Platform Yöneticisi: Burkay | Ana Satıcı: SaintMonarc Official Store</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/admin/marketplace/applications') ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i> Satıcı Başvuruları (<?= $platformStats['pending_applications'] ?>)
            </a>
            <a href="<?= url('/admin/marketplace/moderation') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-shield-check me-1"></i> Ürün Moderasyonu (<?= $platformStats['pending_products'] ?>)
            </a>
        </div>
    </div>

    <!-- KPI Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 text-primary p-3 rounded">
                            <i class="bi bi-shop fs-3"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Toplam Satıcı</div>
                            <div class="fs-4 fw-bold text-white"><?= $platformStats['total_vendors'] ?></div>
                            <div class="text-success small"><i class="bi bi-check-circle me-1"></i><?= $platformStats['active_vendors'] ?> Aktif Mağaza</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 text-warning p-3 rounded">
                            <i class="bi bi-file-earmark-person fs-3"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Bekleyen Başvuru</div>
                            <div class="fs-4 fw-bold text-white"><?= $platformStats['pending_applications'] ?></div>
                            <a href="<?= url('/admin/marketplace/applications') ?>" class="text-warning small text-decoration-none">İncele ve Onayla →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-info bg-opacity-10 text-info p-3 rounded">
                            <i class="bi bi-box-seam fs-3"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Moderasyon Bekleyen</div>
                            <div class="fs-4 fw-bold text-white"><?= $platformStats['pending_products'] ?></div>
                            <a href="<?= url('/admin/marketplace/moderation') ?>" class="text-info small text-decoration-none">Ürünleri İncele →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-success bg-opacity-10 text-success p-3 rounded">
                            <i class="bi bi-wallet2 fs-3"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Bekleyen Hakediş</div>
                            <div class="fs-4 fw-bold text-white"><?= $platformStats['pending_payouts'] ?></div>
                            <a href="<?= url('/admin/marketplace/payouts') ?>" class="text-success small text-decoration-none">Ödemeleri Yönet →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Vendors Table -->
    <div class="card bg-dark border-secondary mb-4">
        <div class="card-header border-secondary bg-transparent d-flex justify-content-between align-items-center">
            <h5 class="card-title text-white mb-0">Platform Satıcıları (Vendors)</h5>
            <a href="<?= url('/admin/vendors/create') ?>" class="btn btn-outline-light btn-sm">+ Yeni Satıcı Tanımla</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                        <tr class="text-muted small">
                            <th>ID</th>
                            <th>Mağaza Adı</th>
                            <th>E-Posta</th>
                            <th>Komisyon Oranı</th>
                            <th>Puan</th>
                            <th>Durum</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendors as $v): ?>
                        <tr>
                            <td>#<?= $v['id'] ?></td>
                            <td>
                                <div class="fw-bold text-white"><?= htmlspecialchars($v['name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($v['slug']) ?></small>
                                <?php if ($v['id'] == 1): ?>
                                    <span class="badge bg-primary ms-1">Ana Satıcı</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($v['email']) ?></td>
                            <td>%<?= number_format((float)$v['commission_rate'], 2) ?></td>
                            <td>
                                <span class="text-warning"><i class="bi bi-star-fill"></i> <?= number_format((float)$v['rating'], 2) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $v['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($v['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('/admin/vendors/edit?id=' . $v['id']) ?>" class="btn btn-sm btn-outline-info">Düzenle</a>
                                <a href="<?= url('/admin/vendors/wallet?vendor_id=' . $v['id']) ?>" class="btn btn-sm btn-outline-success">Cüzdan</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
