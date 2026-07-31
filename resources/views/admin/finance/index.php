<?php
use App\Helpers\ComponentHelper;

$title = "Finans & Muhasebe Paneli - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<style>
.section-card {
    background: rgba(255,255,255,0.01);
    border: 1px solid var(--sm-border) !important;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
}
.stat-mini-box {
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--sm-border);
    border-radius: 12px;
    padding: 16px;
    text-align: center;
}
</style>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Finans & Muhasebe' => '#', 'Dashboard' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Finans & Muhasebe Kontrol Paneli</h2>
        <div class="d-flex gap-2">
            <a href="<?= url('/admin/accounts') ?>" class="btn btn-outline-warning border-0"><i class="bi bi-wallet2 me-2"></i>Cari & Hesaplar</a>
            <a href="<?= url('/admin/invoices') ?>" class="btn btn-outline-info border-0"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Faturalar</a>
            <a href="<?= url('/admin/expenses') ?>" class="btn btn-outline-danger border-0"><i class="bi bi-graph-down me-2"></i>Gider Yönetimi</a>
            <a href="<?= url('/admin/revenues') ?>" class="btn btn-outline-success border-0"><i class="bi bi-graph-up me-2"></i>Gelir Yönetimi</a>
            <a href="<?= url('/admin/reports/finance') ?>" class="btn btn-outline-warning border-0"><i class="bi bi-file-earmark-bar-graph me-2"></i>Finansal Raporlar</a>
        </div>
    </div>
</div>

<div class="row g-4 mb-4 text-white">
    <div class="col-md-3">
        <div class="stat-mini-box h-100 d-flex flex-column justify-content-center">
            <div class="text-muted fs-8">Toplam Gelir (Revenues)</div>
            <h3 class="text-success font-weight-700 mt-2 mb-0">₺<?= number_format($totalRevenue, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini-box h-100 d-flex flex-column justify-content-center">
            <div class="text-muted fs-8">Toplam Gider (Expenses)</div>
            <h3 class="text-danger font-weight-700 mt-2 mb-0">₺<?= number_format($totalExpense, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini-box h-100 d-flex flex-column justify-content-center">
            <div class="text-muted fs-8">Net Kâr / Zarar</div>
            <?php $net = $totalRevenue - $totalExpense; ?>
            <h3 class="<?= $net >= 0 ? 'text-success' : 'text-danger' ?> font-weight-700 mt-2 mb-0">₺<?= number_format($net, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini-box h-100 d-flex flex-column justify-content-center">
            <div class="text-muted fs-8">Toplam Faturalandırma</div>
            <h3 class="text-warning font-weight-700 mt-2 mb-0"><?= number_format($invoiceCount) ?> Adet</h3>
        </div>
    </div>
</div>

<div class="row g-4 text-white">
    <!-- HESAP PLANLARI VE BAKİYELER -->
    <div class="col-lg-6">
        <div class="section-card">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-calculator-fill text-warning me-2"></i>Aktif Hesap Planı ve Bakiye Durumları</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Hesap Kodu</th>
                            <th>Hesap Adı</th>
                            <th>Türü</th>
                            <th class="text-end">Bakiye</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($accounts)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Hesap planı henüz tanımlanmamış.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($accounts as $acc): ?>
                                <tr>
                                    <td><code class="text-warning"><?= htmlspecialchars($acc['code']) ?></code></td>
                                    <td><?= htmlspecialchars($acc['name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($acc['type']) ?></span></td>
                                    <td class="text-end font-weight-600 <?= $acc['balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        ₺<?= number_format((float)$acc['balance'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SON FATURALAR -->
    <div class="col-lg-6">
        <div class="section-card">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-file-earmark-text text-warning me-2"></i>Son Oluşturulan Faturalar</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Fatura No</th>
                            <th>Tür</th>
                            <th>Tarih</th>
                            <th class="text-end">Tutar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Kayıtlı fatura bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td><strong class="text-white"><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-opacity-10 text-capitalize bg-<?= $inv['invoice_type'] === 'sales' ? 'success text-success' : 'danger text-danger' ?>">
                                            <?= htmlspecialchars($inv['invoice_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d.m.Y', strtotime($inv['invoice_date'])) ?></td>
                                    <td class="text-end font-weight-600 text-warning">₺<?= number_format((float)$inv['grand_total'], 2) ?></td>
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
