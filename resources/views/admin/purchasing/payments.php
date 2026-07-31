<?php
use App\Helpers\ComponentHelper;
$title = "Ödeme Takibi (Cari Hesaplar) | SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim' => url('/admin'), 'Satın Alma' => url('/admin/purchasing/dashboard'), 'Ödemeler' => '#']) ?>
            <h2 class="mt-2 text-white font-weight-800 fs-3">Ödeme Takibi & Cari Bakiyeler</h2>
            <p class="text-muted mb-0 fs-7">Tedarikçi borç vadeleri, cari risk analizleri ve kapatılmış faturalar.</p>
        </div>
    </div>

    <!-- Payments Table grid -->
    <div class="card bg-dark border-secondary border-opacity-10 p-3">
        <div class="table-responsive">
            <table class="table table-dark table-hover border-secondary border-opacity-10 align-middle fs-8">
                <thead>
                    <tr>
                        <th>Tedarikçi</th>
                        <th>Satın Alma Siparişi (PO)</th>
                        <th>Ödeme Vade Tarihi</th>
                        <th>Ödeme Durumu</th>
                        <th>Cari Bakiye Tutarı</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-cash-coin fs-1 d-block mb-2"></i>Kayıtlı cari ödeme vadesi bulunmamaktadır.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['supplier_name']) ?></strong></td>
                                <td><?= htmlspecialchars($p['po_number'] ?? 'Manuel Giriş') ?></td>
                                <td><?= date('d.m.Y', strtotime($p['payment_date'])) ?></td>
                                <td>
                                    <?php
                                    $badge = 'secondary';
                                    if ($p['status'] === 'paid') $badge = 'success';
                                    elseif ($p['status'] === 'pending') $badge = 'warning';
                                    elseif ($p['status'] === 'overdue') $badge = 'danger';
                                    ?>
                                    <span class="badge bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?> text-uppercase">
                                        <?= htmlspecialchars($p['status']) ?>
                                    </span>
                                </td>
                                <td>₺<?= number_format((float)$p['amount'], 2) ?></td>
                                <td class="text-end">
                                    <?php if ($p['status'] !== 'paid'): ?>
                                        <button class="btn btn-sm btn-outline-warning rounded-pill px-3" onclick="alert('Entegrasyon tamamlandığında ödeme yapılacaktır.')"><i class="bi bi-wallet2"></i> Öde</button>
                                    <?php else: ?>
                                        <span class="text-success fs-9"><i class="bi bi-check-circle-fill"></i> Ödendi</span>
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

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
