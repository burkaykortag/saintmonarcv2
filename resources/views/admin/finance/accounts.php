<?php
use App\Helpers\ComponentHelper;

$title = "Cari & Hesaplar - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Finans & Muhasebe' => url('/admin/finance'), 'Cari & Hesaplar' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Cari Hesaplar & Kasa - Banka Bakiyeleri</h2>
        <a href="<?= url('/admin/finance') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Finans Paneli</a>
    </div>
</div>

<div class="row g-4 text-white">
    <!-- BANKA HESAPLARI -->
    <div class="col-md-6">
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-bank text-warning me-2"></i>Banka Hesapları</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Banka Adı</th>
                            <th>IBAN / Hesap No</th>
                            <th class="text-end">Bakiye</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bankAccounts)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Banka hesabı tanımlanmamış.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bankAccounts as $bank): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($bank['bank_name']) ?></strong></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($bank['iban']) ?></small></td>
                                    <td class="text-end font-weight-600 text-warning">₺<?= number_format((float)$bank['balance'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- KASA HESAPLARI -->
    <div class="col-md-6">
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-cash-coin text-warning me-2"></i>Kasa Hesapları (Nakit)</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Kasa Adı</th>
                            <th>Para Birimi</th>
                            <th class="text-end">Bakiye</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cashAccounts)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Kasa tanımlanmamış.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cashAccounts as $cash): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($cash['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($cash['currency']) ?></td>
                                    <td class="text-end font-weight-600 text-warning">₺<?= number_format((float)$cash['balance'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MÜŞTERİ CARİ HESAPLARI -->
    <div class="col-12">
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-people text-warning me-2"></i>Müşteri Cari Hesap Bakiyeleri (120 Cari Hesabı)</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Müşteri Adı Soyadı</th>
                            <th>Muhasebe Kodu</th>
                            <th>Son Güncelleme</th>
                            <th class="text-end">Cari Bakiye</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customerAccounts)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Müşteri cari bakiye kaydı bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customerAccounts as $cust): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']) ?></strong></td>
                                    <td><code class="text-warning"><?= htmlspecialchars($cust['account_code']) ?></code></td>
                                    <td><?= date('d.m.Y H:i', strtotime($cust['updated_at'])) ?></td>
                                    <td class="text-end font-weight-600 <?= $cust['balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        ₺<?= number_format((float)$cust['balance'], 2) ?>
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
