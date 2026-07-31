<?php
use App\Helpers\ComponentHelper;
$title = "Satın Alma Siparişleri (PO) | SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="container-fluid py-4 text-white">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim' => url('/admin'), 'Satın Alma' => url('/admin/purchasing/dashboard'), 'Siparişler (PO)' => '#']) ?>
            <h2 class="mt-2 text-white font-weight-800 fs-3">Satın Alma Siparişleri (Purchase Orders)</h2>
            <p class="text-muted mb-0 fs-7">Tedarikçi siparişleri, onay bekleyen sipariş formları ve sevk/kabul durumları.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('/admin/purchasing/wizard') ?>" class="btn btn-warning rounded-pill px-4 font-weight-600"><i class="bi bi-magic me-1"></i> Yeni Sipariş Sihirbazı</a>
        </div>
    </div>

    <!-- Alert Messaging -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 text-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- PO Grid / Table -->
    <div class="card bg-dark border-secondary border-opacity-10 p-3">
        <div class="table-responsive">
            <table class="table table-dark table-hover border-secondary border-opacity-10 align-middle fs-8">
                <thead>
                    <tr>
                        <th>Sipariş Numarası</th>
                        <th>Tedarikçi</th>
                        <th>Hedef Depo</th>
                        <th>Tarih</th>
                        <th>Beklenen Teslimat</th>
                        <th>Miktar (Sipariş / Kabul)</th>
                        <th>Durum</th>
                        <th>Genel Toplam</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted"><i class="bi bi-cart-check fs-1 d-block mb-2"></i>Kayıtlı satın alma siparişi bulunmamaktadır.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($o['po_number']) ?></strong></td>
                                <td><?= htmlspecialchars($o['supplier_name']) ?></td>
                                <td><?= htmlspecialchars($o['warehouse_name']) ?></td>
                                <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
                                <td><?= $o['expected_delivery'] ? date('d.m.Y', strtotime($o['expected_delivery'])) : '-' ?></td>
                                <td><?= $o['total_qty'] ?> / <span class="text-success"><?= $o['received_qty'] ?></span></td>
                                <td>
                                    <?php
                                    $badge = 'secondary';
                                    if ($o['status'] === 'approved') $badge = 'success';
                                    elseif ($o['status'] === 'pending_approval') $badge = 'warning';
                                    elseif ($o['status'] === 'sent') $badge = 'info';
                                    elseif ($o['status'] === 'completed') $badge = 'success';
                                    ?>
                                    <span class="badge bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?> text-uppercase">
                                        <?= htmlspecialchars($o['status']) ?>
                                    </span>
                                </td>
                                <td>₺<?= number_format((float)$o['grand_total'], 2) ?></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <?php if ($o['status'] === 'pending_approval'): ?>
                                            <button class="btn btn-sm btn-success rounded-pill px-3" onclick="updateStatus(<?= $o['id'] ?>, 'approved')"><i class="bi bi-check2"></i> Onayla</button>
                                        <?php endif; ?>
                                        <?php if ($o['status'] === 'approved'): ?>
                                            <button class="btn btn-sm btn-info rounded-pill px-3" onclick="updateStatus(<?= $o['id'] ?>, 'sent')"><i class="bi bi-send"></i> Sevk Et</button>
                                        <?php endif; ?>
                                        <?php if ($o['status'] === 'sent'): ?>
                                            <a href="<?= url('/admin/purchasing/receipts?po_id=' . $o['id']) ?>" class="btn btn-sm btn-warning rounded-pill px-3"><i class="bi bi-box-arrow-in-down"></i> Mal Kabul</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function updateStatus(id, status) {
    if (confirm('Bu siparişi ' + status.toUpperCase() + ' olarak güncellemek istiyor musunuz?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= url('/admin/purchasing/orders/approve') ?>';
        
        const inputCsrf = document.createElement('input');
        inputCsrf.type = 'hidden';
        inputCsrf.name = 'csrf_token';
        inputCsrf.value = '<?= $csrfToken ?>';
        form.appendChild(inputCsrf);

        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id';
        inputId.value = id;
        form.appendChild(inputId);

        const inputStatus = document.createElement('input');
        inputStatus.type = 'hidden';
        inputStatus.name = 'status';
        inputStatus.value = status;
        form.appendChild(inputStatus);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
