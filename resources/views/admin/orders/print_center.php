<?php
use App\Helpers\ComponentHelper;
$title = 'OMS – Yazdırma Merkezi | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>
<style>
.oms-section {
    background: var(--pim-card);
    border: 1px solid var(--pim-border);
    border-radius: var(--pim-radius-lg);
    padding: 22px;
    margin-bottom: 22px;
}
.oms-title {
    font-size: 14px;
    font-weight: 700;
    color: #c5a880;
    margin-bottom: 16px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}
.print-table th {
    font-size: 11px;
    font-weight: 600;
    color: var(--pim-text-xs);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-bottom: 1px solid var(--pim-border)!important;
    padding: 10px 12px;
}
.print-table td {
    font-size: 12px;
    color: var(--pim-text-sm);
    border-bottom: 1px solid rgba(255, 255, 255, 0.04)!important;
    padding: 10px 12px;
    vertical-align: middle;
}
.print-table tr:hover td {
    background: rgba(255, 255, 255, 0.01);
}
</style>

<div class="pim-module">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Siparişler'=>url('/admin/orders'),'Yazdırma Merkezi'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-printer-fill me-2" style="color:#10b981"></i>Yazdırma Merkezi</h2>
        </div>
        <a href="<?= url('/admin/orders') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Geri</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-9">
            <div class="oms-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="oms-title m-0"><i class="bi bi-file-earmark-pdf me-2"></i>Sipariş Evrak Yazdırma Kuyruğu</div>
                    <input type="text" id="printSearch" class="form-control border-0 text-white" 
                           style="background:rgba(255,255,255,0.04);font-size:12px;width:220px;border-radius:8px" 
                           placeholder="Sipariş no veya müşteri ara..." oninput="filterPrintTable(this.value)">
                </div>

                <div class="table-responsive">
                    <table class="table print-table mb-0" id="printTable">
                        <thead>
                            <tr>
                                <th style="width:40px"><input type="checkbox" onchange="toggleAllCheckboxes(this)"></th>
                                <th>Sipariş No</th>
                                <th>Müşteri</th>
                                <th>Tarih</th>
                                <th>Durum</th>
                                <th class="text-end">Ciro</th>
                                <th class="text-end" style="width:180px">Hızlı İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                            <tr class="print-row">
                                <td><input type="checkbox" value="<?= $o['id'] ?>" class="order-print-check"></td>
                                <td>
                                    <a href="<?= url('/admin/orders/show?id=' . $o['id']) ?>" target="_blank" style="color:#c5a880;font-weight:700;text-decoration:none">
                                        <?= htmlspecialchars($o['order_number']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($o['billing_first_name'] . ' ' . $o['billing_last_name']) ?></td>
                                <td style="font-size:11px"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                                <td>
                                    <span class="badge" style="background:rgba(255,255,255,0.05);color:var(--pim-text-sm);border:1px solid rgba(255,255,255,0.1)">
                                        <?= htmlspecialchars($o['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end" style="font-weight:700">₺<?= number_format($o['grand_total'], 2, ',', '.') ?></td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button class="btn btn-xs btn-dark" style="font-size:10px;padding:3px 6px" onclick="printDoc(<?= $o['id'] ?>, 'invoice')" title="Fatura"><i class="bi bi-receipt"></i> Fatura</button>
                                        <button class="btn btn-xs btn-dark" style="font-size:10px;padding:3px 6px" onclick="printDoc(<?= $o['id'] ?>, 'packing_slip')" title="İrsaliye"><i class="bi bi-file-earmark-text"></i> İrsaliye</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="oms-section">
                <div class="oms-title"><i class="bi bi-sliders me-2"></i>Toplu İşlemler</div>
                
                <div class="mb-4">
                    <label class="form-label" style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Yazdırılacak Belge</label>
                    <select id="bulk_document_type" class="form-select border-0 text-white" 
                            style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px">
                        <option value="invoice" style="background:#0f0c20">E-Arşiv Fatura (Invoice)</option>
                        <option value="packing_slip" style="background:#0f0c20">Sevk İrsaliyesi (Packing Slip)</option>
                        <option value="shipping_label" style="background:#0f0c20">Kargo Barkod Etiketi (Shipping Label)</option>
                    </select>
                </div>

                <button class="btn btn-warning text-dark w-100 fw-bold" onclick="printSelected()"><i class="bi bi-printer me-2"></i>Seçilenleri Yazdır</button>
                <div style="font-size:11px;color:var(--pim-text-xs);margin-top:10px;text-align:center">
                    Tarayıcınızın yazdırma diyalogu otomatik olarak açılacaktır.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printDoc(orderId, type) {
    window.open('<?= url("/admin/orders/pdf") ?>?id=' + orderId + '&type=' + type, '_blank');
}

function toggleAllCheckboxes(masterCb) {
    document.querySelectorAll('.order-print-check').forEach(cb => {
        cb.checked = masterCb.checked;
    });
}

function filterPrintTable(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.print-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

function printSelected() {
    const checked = document.querySelectorAll('.order-print-check:checked');
    if (checked.length === 0) {
        alert('Lütfen yazdırılacak siparişleri seçin.');
        return;
    }
    const docType = document.getElementById('bulk_document_type').value;
    checked.forEach(cb => {
        printDoc(cb.value, docType);
    });
}
</script>
<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
