<?php
$title = 'Satıcı Başvuruları | VEYRA Marketplace';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-white font-weight-bold">Satıcı Başvuruları & Onboarding</h1>
            <p class="text-muted mb-0">Platforma katılım talebinde bulunan yeni mağaza başvuruları</p>
        </div>
        <a href="<?= url('/admin/marketplace/dashboard') ?>" class="btn btn-outline-secondary btn-sm">← Pazaryeri Dashboard</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show bg-dark text-success border-success" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show bg-dark text-danger border-danger" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_GET['error']) ?>
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
                            <th>Firma Adı</th>
                            <th>Yetkili</th>
                            <th>E-Posta & Telefon</th>
                            <th>İl / İlçe</th>
                            <th>Kategori</th>
                            <th>Durum</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Henüz başvuru bulunmamaktadır.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                            <tr>
                                <td>#<?= $app['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($app['company_name']) ?></div>
                                    <small class="text-muted">Vergi No: <?= htmlspecialchars($app['tax_number'] ?? '-') ?></small>
                                </td>
                                <td><?= htmlspecialchars($app['contact_name']) ?></td>
                                <td>
                                    <div><?= htmlspecialchars($app['email']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($app['phone']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($app['city'] ?? '-') ?> / <?= htmlspecialchars($app['district'] ?? '-') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($app['category'] ?? 'Genel') ?></span></td>
                                <td>
                                    <?php
                                    $badge = 'warning';
                                    if ($app['status'] === 'approved') $badge = 'success';
                                    if ($app['status'] === 'rejected') $badge = 'danger';
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= ucfirst($app['status']) ?></span>
                                </td>
                                <td class="text-end">
                                    <?php if ($app['status'] === 'pending'): ?>
                                        <form method="POST" action="<?= url('/admin/marketplace/applications/approve') ?>" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
                                            <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success me-1"><i class="bi bi-check-lg"></i> Onayla</button>
                                        </form>
                                        <form method="POST" action="<?= url('/admin/marketplace/applications/reject') ?>" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
                                            <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Reddet</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">İşlem Tamamlandı</span>
                                    <?php endif; ?>
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
