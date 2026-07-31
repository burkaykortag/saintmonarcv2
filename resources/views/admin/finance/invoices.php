<?php
use App\Helpers\ComponentHelper;

$title = "Faturalar - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Finans & Muhasebe' => url('/admin/finance'), 'Faturalar' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Satış, Alış ve İade Faturaları</h2>
        <a href="<?= url('/admin/finance') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Finans Paneli</a>
    </div>
</div>

<div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <div class="table-responsive">
        <table class="table align-middle text-white table-borderless fs-7">
            <thead>
                <tr class="text-muted border-bottom border-secondary border-opacity-25">
                    <th>Fatura No</th>
                    <th>UUID</th>
                    <th>Fatura Türü</th>
                    <th>Fatura Tarihi</th>
                    <th>KDV Hariç</th>
                    <th>KDV Toplamı</th>
                    <th>Genel Toplam</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Kayıtlı fatura bulunmuyor.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td><strong class="text-white"><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                            <td><small class="text-muted"><?= htmlspecialchars($inv['uuid'] ?? '-') ?></small></td>
                            <td>
                                <span class="badge bg-opacity-10 text-capitalize bg-<?= $inv['invoice_type'] === 'sales' ? 'success text-success' : 'danger text-danger' ?>">
                                    <?= htmlspecialchars($inv['invoice_type']) ?>
                                </span>
                            </td>
                            <td><?= date('d.m.Y', strtotime($inv['invoice_date'])) ?></td>
                            <td>₺<?= number_format((float)$inv['sub_total'], 2) ?></td>
                            <td>₺<?= number_format((float)$inv['tax_total'], 2) ?></td>
                            <td><strong class="text-warning">₺<?= number_format((float)$inv['grand_total'], 2) ?></strong></td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success">Gönderildi</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
