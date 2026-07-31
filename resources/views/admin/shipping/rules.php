<?php
use App\Helpers\ComponentHelper;

$title = "Kargo Kuralları - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Lojistik & Kargo' => url('/admin/shipping'), 'Kargo Kuralları' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Kargo Fiyatlandırma & Desi Kuralları</h2>
        <a href="<?= url('/admin/shipping') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Lojistik Paneli</a>
    </div>
</div>

<div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <div class="table-responsive">
        <table class="table align-middle text-white table-borderless fs-7">
            <thead>
                <tr class="text-muted border-bottom border-secondary border-opacity-25">
                    <th>Kural Adı</th>
                    <th>Min / Maks Sipariş Tutarı</th>
                    <th>Min / Maks Ağırlık</th>
                    <th>Min / Maks Desi</th>
                    <th>Ücretsiz Kargo Limiti</th>
                    <th class="text-end">Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rules)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Tanımlı kargo kuralı bulunmuyor.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rules as $rule): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($rule['name']) ?></strong></td>
                            <td>
                                ₺<?= number_format((float)($rule['min_order_amount'] ?? 0.0), 2) ?> - 
                                ₺<?= number_format((float)($rule['max_order_amount'] ?? 99999.0), 2) ?>
                            </td>
                            <td>
                                <?= number_format((float)($rule['min_weight'] ?? 0.0), 1) ?> kg - 
                                <?= number_format((float)($rule['max_weight'] ?? 999.0), 1) ?> kg
                            </td>
                            <td>
                                <?= number_format((float)($rule['min_desi'] ?? 0.0), 1) ?> ds - 
                                <?= number_format((float)($rule['max_desi'] ?? 999.0), 1) ?> ds
                            </td>
                            <td>
                                <?php if ($rule['free_shipping_limit'] !== null): ?>
                                    <strong class="text-success">₺<?= number_format((float)$rule['free_shipping_limit'], 2) ?> Üzeri</strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-opacity-10 bg-<?= $rule['is_active'] ? 'success text-success' : 'danger text-danger' ?>">
                                    <?= $rule['is_active'] ? 'Aktif' : 'Pasif' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
