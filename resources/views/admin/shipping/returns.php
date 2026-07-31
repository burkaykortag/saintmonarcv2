<?php
use App\Helpers\ComponentHelper;

$title = "İade Yönetimi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Lojistik & Kargo' => url('/admin/shipping'), 'İadeler' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Kargo İadeleri & Depo Giriş İşlemleri</h2>
        <a href="<?= url('/admin/shipping') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Lojistik Paneli</a>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>

<div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <div class="table-responsive">
        <table class="table align-middle text-white table-borderless fs-7">
            <thead>
                <tr class="text-muted border-bottom border-secondary border-opacity-25">
                    <th>İade No</th>
                    <th>Sipariş ID</th>
                    <th>İade Nedeni</th>
                    <th>İade Tarihi</th>
                    <th>Durum</th>
                    <th class="text-end">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Kayıtlı iade talebi bulunmuyor.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($returns as $ret): ?>
                        <tr>
                            <td><strong class="text-danger"><?= htmlspecialchars($ret['return_number']) ?></strong></td>
                            <td>#<?= htmlspecialchars((string)$ret['order_id']) ?></td>
                            <td><?= htmlspecialchars($ret['reason'] ?? '-') ?></td>
                            <td><?= date('d.m.Y', strtotime($ret['created_at'])) ?></td>
                            <td>
                                <span class="badge text-capitalize bg-opacity-10 bg-<?= $ret['status'] === 'completed' ? 'success text-success' : 'warning text-warning' ?>">
                                    <?= htmlspecialchars($ret['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <form action="<?= url('/admin/shipping/returns/update') ?>" method="POST" class="d-inline-flex gap-2 align-items-center">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="id" value="<?= $ret['id'] ?>">
                                    <select name="status" class="form-select border-0 text-white py-1 px-2 fs-8" style="background: rgba(255,255,255,0.05); border: 1px solid var(--sm-border) !important; width: 130px;">
                                        <option value="requested" <?= $ret['status'] === 'requested' ? 'selected' : '' ?>>Talep Alındı</option>
                                        <option value="approved" <?= $ret['status'] === 'approved' ? 'selected' : '' ?>>Onaylandı</option>
                                        <option value="warehouse_in" <?= $ret['status'] === 'warehouse_in' ? 'selected' : '' ?>>Depoya Girdi</option>
                                        <option value="completed" <?= $ret['status'] === 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                                    </select>
                                    <button type="submit" class="btn btn-warning btn-sm text-dark px-3 font-weight-600">Güncelle</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
