<?php
use App\Helpers\ComponentHelper;
$title = 'WMS – Depolar Arası Transferler | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>
<style>
.tr-table th {
    font-size: 11px;
    font-weight: 600;
    color: var(--pim-text-xs);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-bottom: 1px solid var(--pim-border)!important;
    padding: 10px 12px;
}
.tr-table td {
    font-size: 12px;
    color: var(--pim-text-sm);
    border-bottom: 1px solid rgba(255, 255, 255, 0.04)!important;
    padding: 10px 12px;
    vertical-align: middle;
}
</style>

<div class="pim-module">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Depo Yönetimi'=>'#','Transferler'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-arrow-left-right me-2" style="color:#c5a880"></i>Depolar Arası Transferler</h2>
        </div>
        <button class="btn btn-warning text-dark fw-bold btn-sm" data-bs-toggle="modal" data-bs-target="#newTransferModal"><i class="bi bi-plus-circle me-1"></i>Yeni Transfer Talebi</button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success p-3 rounded-3 mb-4">
            <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-4">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Active Transfers List -->
    <div class="card border-0 p-4 text-white" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important;border-radius:20px">
        <div class="table-responsive">
            <table class="table tr-table mb-0">
                <thead>
                    <tr>
                        <th>Transfer No</th>
                        <th>Nereden (Kaynak)</th>
                        <th>Nereye (Hedef)</th>
                        <th>Kalem Sayısı</th>
                        <th>Toplam Adet</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transfers)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Aktif depolar arası transfer kaydı bulunmamaktadır.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transfers as $t): 
                            $statusColor = '#6b7280';
                            if ($t['status'] === 'pending') $statusColor = '#fbbf24';
                            elseif ($t['status'] === 'approved') $statusColor = '#3b82f6';
                            elseif ($t['status'] === 'shipped') $statusColor = '#8b5cf6';
                            elseif ($t['status'] === 'completed') $statusColor = '#10b981';
                        ?>
                        <tr>
                            <td class="fw-bold" style="color:#c5a880">#TR-<?= $t['id'] ?></td>
                            <td><?= htmlspecialchars($t['from_warehouse_name']) ?></td>
                            <td><?= htmlspecialchars($t['to_warehouse_name']) ?></td>
                            <td><?= $t['item_count'] ?> çeşit</td>
                            <td><?= $t['total_qty'] ?> adet</td>
                            <td>
                                <span class="badge" style="background:<?= $statusColor ?>22;color:<?= $statusColor ?>;border:1px solid <?= $statusColor ?>44;font-size:10px">
                                    <?= strtoupper(htmlspecialchars($t['status'])) ?>
                                </span>
                            </td>
                            <td><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></td>
                            <td class="text-end">
                                <form action="<?= url('/admin/wms/transfers/update') ?>" method="POST" style="display:inline-block">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    
                                    <?php if ($t['status'] === 'pending'): ?>
                                        <button type="submit" name="status" value="approved" class="btn btn-xs btn-outline-primary border-0" style="font-size:10px;padding:4px 8px"><i class="bi bi-check-circle"></i> Onayla</button>
                                    <?php elseif ($t['status'] === 'approved'): ?>
                                        <button type="submit" name="status" value="shipped" class="btn btn-xs btn-outline-info border-0" style="font-size:10px;padding:4px 8px"><i class="bi bi-truck"></i> Sevk Et</button>
                                    <?php elseif ($t['status'] === 'shipped'): ?>
                                        <button type="submit" name="status" value="completed" class="btn btn-xs btn-outline-success border-0" style="font-size:10px;padding:4px 8px"><i class="bi bi-check2-all"></i> Teslim Al</button>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:11px"><i class="bi bi-lock-fill"></i> Kilitli</span>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Transfer Modal -->
<div class="modal fade" id="newTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="newTransferForm" class="modal-content text-white" style="background:#0f0c20;border:1px solid var(--pim-border);border-radius:20px">
            <div class="modal-header border-bottom border-secondary border-opacity-25 p-4">
                <h5 class="modal-title fw-bold text-white fs-5"><i class="bi bi-plus-circle-fill text-warning me-2"></i>Yeni Transfer Talebi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label text-muted fs-7">Çıkış Deposu (Nereden)</label>
                    <select id="from_warehouse_id" class="form-select border-0 text-white" style="background:rgba(255,255,255,0.04);font-size:12px;padding:10px">
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>" style="background:#0f0c20"><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7">Hedef Depo (Nereye)</label>
                    <select id="to_warehouse_id" class="form-select border-0 text-white" style="background:rgba(255,255,255,0.04);font-size:12px;padding:10px">
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>" style="background:#0f0c20" <?= $w['id'] == 2 ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7">Ürün Seçin</label>
                    <select id="product_select" class="form-select border-0 text-white" style="background:rgba(255,255,255,0.04);font-size:12px;padding:10px">
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>" style="background:#0f0c20"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['sku']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7">Miktar</label>
                    <input type="number" id="transfer_qty" class="form-control border-0 text-white" style="background:rgba(255,255,255,0.04);font-size:12px;padding:10px" min="1" value="10">
                </div>
            </div>

            <div class="modal-footer border-top border-secondary border-opacity-25 p-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-warning text-dark fw-bold" onclick="submitTransfer()">Transferi Başlat</button>
            </div>
        </form>
    </div>
</div>

<script>
function submitTransfer() {
    const fromWid = document.getElementById('from_warehouse_id').value;
    const toWid = document.getElementById('to_warehouse_id').value;
    const productId = document.getElementById('product_select').value;
    const qty = document.getElementById('transfer_qty').value;

    const fd = new FormData();
    fd.append('from_warehouse_id', fromWid);
    fd.append('to_warehouse_id', toWid);
    fd.append('items[0][product_id]', productId);
    fd.append('items[0][quantity]', qty);
    fd.append('csrf_token', '<?= $csrfToken ?>');

    fetch('<?= url("/api/wms/transfers/create") ?>', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Transfer talebi başarıyla oluşturuldu.');
            location.reload();
        } else {
            alert('Hata: ' + res.message);
        }
    });
}
</script>
<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
