<?php
use App\Helpers\ComponentHelper;

$title = "Kargo Sevkiyatları - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Lojistik & Kargo' => url('/admin/shipping'), 'Sevkiyatlar' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Kargo Paketleri & Sevkiyat Listesi</h2>
        <a href="<?= url('/admin/shipping') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Lojistik Paneli</a>
    </div>
</div>

<div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <div class="table-responsive">
        <table class="table align-middle text-white table-borderless fs-7">
            <thead>
                <tr class="text-muted border-bottom border-secondary border-opacity-25">
                    <th>Takip No</th>
                    <th>Barkod</th>
                    <th>Sipariş ID</th>
                    <th>Servis Türü</th>
                    <th>Desi / Ağırlık</th>
                    <th>Kargo Maliyeti</th>
                    <th>Tarih</th>
                    <th class="text-end">Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($packages)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Kayıtlı gönderi bulunmuyor.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($packages as $pkg): ?>
                        <tr>
                            <td><strong class="text-warning"><?= htmlspecialchars($pkg['tracking_number'] ?? '-') ?></strong></td>
                            <td><small class="text-muted"><?= htmlspecialchars($pkg['barcode'] ?? '-') ?></small></td>
                            <td>#<?= htmlspecialchars((string)$pkg['order_id']) ?></td>
                            <td><?= htmlspecialchars($pkg['company_name'] . ' ' . $pkg['service_name']) ?></td>
                            <td><?= number_format((float)$pkg['desi'], 1) ?> ds / <?= number_format((float)$pkg['weight'], 1) ?> kg</td>
                            <td><strong>₺<?= number_format((float)$pkg['shipping_cost'], 2) ?></strong></td>
                            <td><?= date('d.m.Y H:i', strtotime($pkg['created_at'])) ?></td>
                            <td class="text-end">
                                <span class="badge text-capitalize bg-opacity-10 bg-<?= $pkg['status'] === 'delivered' ? 'success text-success' : ($pkg['status'] === 'pending' ? 'warning text-warning' : 'info text-info') ?>">
                                    <?= htmlspecialchars($pkg['status']) ?>
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
