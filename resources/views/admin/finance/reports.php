<?php
use App\Helpers\ComponentHelper;

$title = "Finansal Raporlar - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Finans & Muhasebe' => url('/admin/finance'), 'Finansal Raporlar' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Yıllık & Dönemsel Finansal Raporlar</h2>
        <a href="<?= url('/admin/finance') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Finans Paneli</a>
    </div>
</div>

<div class="row g-4 text-white">
    <!-- GELİR TABLOSU (PROFIT & LOSS) -->
    <div class="col-md-6">
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-file-earmark-diff text-warning me-2"></i>Gelir Tablosu (Kâr / Zarar)</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Dönem</th>
                            <th>Toplam Gelir</th>
                            <th>Toplam Gider</th>
                            <th class="text-end">Net Kâr</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($profit)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Gelir tablosu kaydı bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($profit as $p): ?>
                                <tr>
                                    <td><?= date('d.m.Y', strtotime($p['period_start'])) ?> - <?= date('d.m.Y', strtotime($p['period_end'])) ?></td>
                                    <td>₺<?= number_format((float)$p['total_revenue'], 2) ?></td>
                                    <td>₺<?= number_format((float)$p['total_expense'], 2) ?></td>
                                    <td class="text-end font-weight-600 text-success">₺<?= number_format((float)$p['net_profit'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- BİLANÇO RAPORLARI (BALANCE SHEETS) -->
    <div class="col-md-6">
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-layout-three-columns text-warning me-2"></i>Bilanço Raporları (Aktif/Pasif)</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Tarih</th>
                            <th>Aktifler Toplamı</th>
                            <th>Borçlar Toplamı</th>
                            <th class="text-end">Özkaynaklar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($balance)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Bilanço kaydı bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($balance as $b): ?>
                                <tr>
                                    <td><?= date('d.m.Y', strtotime($b['report_date'])) ?></td>
                                    <td>₺<?= number_format((float)$b['total_assets'], 2) ?></td>
                                    <td>₺<?= number_format((float)$b['total_liabilities'], 2) ?></td>
                                    <td class="text-end font-weight-600 text-warning">₺<?= number_format((float)$b['total_equity'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- GEÇİCİ MİZAN (TRIAL BALANCE) -->
    <div class="col-12">
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-grid-3x3-gap-fill text-warning me-2"></i>Büyük Defter Mizan Raporu (Trial Balance)</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Hesap Kodu</th>
                            <th>Borç Toplamı</th>
                            <th>Alacak Toplamı</th>
                            <th>Mizan Dönemi</th>
                            <th class="text-end">Bakiye</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mizan)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Mizan kaydı bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mizan as $m): ?>
                                <tr>
                                    <td><code class="text-warning"><?= htmlspecialchars($m['account_code']) ?></code></td>
                                    <td>₺<?= number_format((float)$m['debit_total'], 2) ?></td>
                                    <td>₺<?= number_format((float)$m['credit_total'], 2) ?></td>
                                    <td><?= htmlspecialchars($m['period']) ?></td>
                                    <td class="text-end font-weight-600 <?= $m['balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        ₺<?= number_format((float)$m['balance'], 2) ?>
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
