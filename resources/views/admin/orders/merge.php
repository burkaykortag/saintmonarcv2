<?php
use App\Helpers\ComponentHelper;
$title = 'OMS – Sipariş Birleştirme Merkezi | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>
<style>
.merge-group-card {
    background: var(--pim-card);
    border: 1px solid var(--pim-border);
    border-radius: var(--pim-radius-lg);
    padding: 22px;
    margin-bottom: 22px;
}
.merge-group-title {
    font-size: 15px;
    font-weight: 700;
    color: #c5a880;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.merge-table th {
    font-size: 11px;
    font-weight: 600;
    color: var(--pim-text-xs);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-bottom: 1px solid var(--pim-border)!important;
    padding: 10px 12px;
}
.merge-table td {
    font-size: 12px;
    color: var(--pim-text-sm);
    border-bottom: 1px solid rgba(255, 255, 255, 0.04)!important;
    padding: 10px 12px;
    vertical-align: middle;
}
.item-pill {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 11px;
    margin-right: 4px;
    display: inline-block;
}
</style>

<div class="pim-module">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Siparişler'=>url('/admin/orders'),'Sipariş Birleştirme'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-union me-2" style="color:#8b5cf6"></i>Sipariş Birleştirme Merkezi</h2>
        </div>
        <a href="<?= url('/admin/orders') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Geri</a>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0" style="background:rgba(239, 68, 68, 0.1);color:#ef4444;border-radius:12px;margin-bottom:20px">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
        <div class="text-center" style="padding:80px 20px;background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius-lg)">
            <i class="bi bi-shield-check" style="font-size:48px;color:#10b981;margin-bottom:16px;display:block"></i>
            <h5 class="text-white fw-bold">Birleştirilebilir Sipariş Bulunmadı</h5>
            <p style="color:var(--pim-text-xs);max-width:400px;margin:8px auto 0 auto">Sistemde aynı müşteriye (aynı isim ve email) ait birden fazla bekleyen (pending) sipariş bulunmamaktadır.</p>
        </div>
    <?php else: ?>
        <div style="font-size:13px;color:var(--pim-text-sm);margin-bottom:16px" class="alert alert-info border-0" style="background:rgba(197,168,128,0.1);color:#c5a880;border-radius:12px">
            <i class="bi bi-info-circle-fill me-2"></i><strong>İpucu:</strong> Birleştirmek istediğiniz siparişleri seçin. İlk seçtiğiniz sipariş ana sipariş olarak kabul edilecek, diğer siparişlerin kalemleri ana siparişe aktarılacak ve o siparişler iptal edilecektir.
        </div>

        <?php foreach ($groups as $gIdx => $g): ?>
            <form action="<?= url('/admin/orders/merge') ?>" method="POST" class="merge-group-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="merge-group-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="merge-group-title m-0">
                            <i class="bi bi-person-fill"></i> Müşteri: <?= htmlspecialchars($g['customer']) ?>
                        </div>
                        <button type="submit" class="btn btn-sm btn-warning text-dark"><i class="bi bi-union me-1"></i>Seçilenleri Birleştir</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table merge-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" onchange="toggleGroupCheckboxes(this, <?= $gIdx ?>)"></th>
                                    <th>Sipariş No</th>
                                    <th>Tarih</th>
                                    <th>Ürünler</th>
                                    <th>Kargo</th>
                                    <th class="text-end">Toplam</th>
                                </tr>
                            </thead>
                            <tbody id="group_tbody_<?= $gIdx ?>">
                                <?php foreach ($g['orders'] as $o): ?>
                                <tr>
                                    <td><input type="checkbox" name="order_ids[]" value="<?= $o['id'] ?>" class="order-select-check"></td>
                                    <td>
                                        <a href="<?= url('/admin/orders/show?id=' . $o['id']) ?>" target="_blank" style="color:#c5a880;font-weight:700;text-decoration:none">
                                            <?= htmlspecialchars($o['order_number']) ?>
                                        </a>
                                    </td>
                                    <td style="font-size:11px"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                                    <td>
                                        <?php foreach ($o['items'] as $item): ?>
                                            <span class="item-pill"><?= htmlspecialchars($item['product_name']) ?> x<?= $item['quantity'] ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td style="font-size:11px"><?= htmlspecialchars($o['shipping_method_name'] ?? 'Kargo') ?></td>
                                    <td class="text-end" style="font-weight:700;color:var(--pim-text)">₺<?= number_format($o['grand_total'], 2, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleGroupCheckboxes(masterCb, groupIdx) {
    const container = document.getElementById("group_tbody_" + groupIdx);
    if (!container) return;
    container.querySelectorAll('.order-select-check').forEach(cb => {
        cb.checked = masterCb.checked;
    });
}
</script>
<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
