<?php
$title = 'Hakediş & Ödemeler | VEYRA Marketplace';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-white font-weight-bold">Satıcı Hakediş Ödemeleri (Payouts)</h1>
            <p class="text-muted mb-0">Satıcıların pazaryeri cüzdan bakiyelerinden talep ettiği hakediş ödemeleri</p>
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
                            <th>Satıcı</th>
                            <th>Talep Tutarı</th>
                            <th>IBAN</th>
                            <th>Talep Tarihi</th>
                            <th>Durum</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payouts)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Henüz ödeme talebi bulunmamaktadır.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payouts as $pay): ?>
                            <tr>
                                <td>#<?= $pay['id'] ?></td>
                                <td><div class="fw-bold text-white"><?= htmlspecialchars($pay['vendor_name'] ?? 'Satıcı #' . $pay['vendor_id']) ?></div></td>
                                <td class="fw-bold text-success">₺<?= number_format((float)$pay['amount'], 2) ?></td>
                                <td><span class="font-monospace text-muted"><?= htmlspecialchars($pay['iban']) ?></span></td>
                                <td><?= date('d.m.Y H:i', strtotime($pay['created_at'])) ?></td>
                                <td>
                                    <?php
                                    $b = 'warning';
                                    if ($pay['status'] === 'paid') $b = 'success';
                                    if ($pay['status'] === 'rejected') $b = 'danger';
                                    ?>
                                    <span class="badge bg-<?= $b ?>"><?= ucfirst($pay['status']) ?></span>
                                </td>
                                <td class="text-end">
                                    <?php if ($pay['status'] === 'pending'): ?>
                                        <form method="POST" action="<?= url('/admin/marketplace/payouts/process') ?>" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
                                            <input type="hidden" name="payout_id" value="<?= $pay['id'] ?>">
                                            <input type="hidden" name="status" value="paid">
                                            <button type="submit" class="btn btn-sm btn-success me-1"><i class="bi bi-check-circle"></i> Ödendi İşaretle</button>
                                        </form>
                                        <form method="POST" action="<?= url('/admin/marketplace/payouts/process') ?>" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
                                            <input type="hidden" name="payout_id" value="<?= $pay['id'] ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Reddet</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">İşlendi</span>
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
