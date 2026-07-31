<?php
use App\Helpers\ComponentHelper;
$title = 'WMS – Stok Sayım Modülü | SaintMonarc';
include dirname(dirname(__DIR__)) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>
<style>
.cnt-table th {
    font-size: 11px;
    font-weight: 600;
    color: var(--pim-text-xs);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-bottom: 1px solid var(--pim-border)!important;
    padding: 10px 12px;
}
.cnt-table td {
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
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Depo Yönetimi'=>'#','Sayım İşlemleri'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-calculator me-2" style="color:#10b981"></i>Stok Sayım Modülü (Count)</h2>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-warning text-dark fw-bold btn-sm" data-bs-toggle="modal" data-bs-target="#newCountModal"><i class="bi bi-plus-circle me-1"></i>Yeni Sayım Başlat</button>
        </div>
    </div>

    <!-- Active/Completed Counts Queue -->
    <div class="card border-0 p-4 text-white mb-4" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important;border-radius:20px">
        <h4 class="text-white fs-6 mb-3"><i class="bi bi-clock-history me-2 text-warning"></i>Son Sayım Kayıtları</h4>
        <div class="table-responsive">
            <table class="table cnt-table mb-0">
                <thead>
                    <tr>
                        <th>Sayım No</th>
                        <th>Depo</th>
                        <th>Tür</th>
                        <th>Durum</th>
                        <th>Kalem Sayısı</th>
                        <th>Toplam Sapma (Fark)</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($counts)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Sistemde kayıtlı sayım geçmişi bulunamadı.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($counts as $c): 
                            $statusColor = $c['status'] === 'completed' ? '#10b981' : '#fbbf24';
                        ?>
                        <tr>
                            <td class="fw-bold" style="color:#c5a880">#CNT-<?= $c['id'] ?></td>
                            <td><?= htmlspecialchars($c['warehouse_name']) ?></td>
                            <td><span class="badge" style="background:rgba(255,255,255,0.05);color:var(--pim-text-sm);border:1px solid rgba(255,255,255,0.1)"><?= strtoupper(htmlspecialchars($c['type'])) ?></span></td>
                            <td>
                                <span class="badge" style="background:<?= $statusColor ?>22;color:<?= $statusColor ?>;border:1px solid <?= $statusColor ?>44;font-size:10px">
                                    <?= strtoupper(htmlspecialchars($c['status'])) ?>
                                </span>
                            </td>
                            <td><?= $c['item_count'] ?> adet</td>
                            <td class="fw-bold" style="color:<?= $c['total_difference'] == 0 ? '#10b981' : '#ef4444' ?>">
                                <?= $c['total_difference'] > 0 ? '+' : '' ?><?= $c['total_difference'] ?> adet
                            </td>
                            <td><?= date('d.m.Y H:i', strtotime($c['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Count Modal -->
<div class="modal fade" id="newCountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="newCountForm" class="modal-content text-white" style="background:#0f0c20;border:1px solid var(--pim-border);border-radius:20px">
            <div class="modal-header border-bottom border-secondary border-opacity-25 p-4">
                <h5 class="modal-title fw-bold text-white fs-5"><i class="bi bi-calculator-fill text-warning me-2"></i>Yeni Envanter Sayımı Hazırla</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7">Sayım Deposu</label>
                        <select id="count_warehouse_id" class="form-select border-0 text-white" style="background:rgba(255,255,255,0.04);font-size:12px;padding:10px">
                            <?php foreach ($warehouses as $w): ?>
                                <option value="<?= $w['id'] ?>" style="background:#0f0c20"><?= htmlspecialchars($w['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7">Sayım Tipi</label>
                        <select id="count_type" class="form-select border-0 text-white" style="background:rgba(255,255,255,0.04);font-size:12px;padding:10px">
                            <option value="cycle" style="background:#0f0c20">Döngüsel Sayım (Cycle Count)</option>
                            <option value="partial" style="background:#0f0c20">Kısmi Sayım (Partial)</option>
                            <option value="full" style="background:#0f0c20">Tam Sayım (Full Count)</option>
                        </select>
                    </div>
                </div>

                <h5 class="text-white fs-7 mb-3"><i class="bi bi-list-task text-warning me-2"></i>Ürün Envanter Listesi ve Sayılan Girişleri</h5>
                
                <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                    <table class="table cnt-table mb-0">
                        <thead>
                            <tr>
                                <th>Ürün Adı</th>
                                <th>SKU</th>
                                <th class="text-center">Sistemdeki Stok</th>
                                <th class="text-end" style="width:140px">Fiziki Sayılan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventories as $inv): ?>
                            <tr>
                                <td style="font-weight:600"><?= htmlspecialchars($inv['product_name']) ?></td>
                                <td><code><?= htmlspecialchars($inv['product_sku']) ?></code></td>
                                <td class="text-center text-info" id="sys_qty_<?= $inv['id'] ?>"><?= $inv['stock'] ?></td>
                                <td class="text-end">
                                    <input type="number" name="quantities[<?= $inv['id'] ?>]" class="form-control border-0 text-white text-center count-input" 
                                           style="background:rgba(255,255,255,0.04);font-size:12px;padding:6px;border-radius:8px" 
                                           value="<?= $inv['stock'] ?>" oninput="calcDiff(<?= $inv['id'] ?>)">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-top border-secondary border-opacity-25 p-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-warning text-dark fw-bold" onclick="submitReconciliation()">Sayımı Tamamla & Farkı Reconcile Et</button>
            </div>
        </form>
    </div>
</div>

<script>
function calcDiff(id) {
    const sys = parseInt(document.getElementById('sys_qty_' + id).textContent);
    const countVal = document.querySelector(`input[name="quantities[${id}]"]`).value;
    const count = parseInt(countVal) || 0;
    const diff = count - sys;
}

function submitReconciliation() {
    const wid = document.getElementById('count_warehouse_id').value;
    const type = document.getElementById('count_type').value;
    
    const fd = new FormData();
    fd.append('warehouse_id', wid);
    fd.append('type', type);
    fd.append('csrf_token', '<?= $csrfToken ?>');

    const inputs = document.querySelectorAll('.count-input');
    inputs.forEach(input => {
        fd.append(input.name, input.value);
    });

    fetch('<?= url("/api/wms/counts/reconcile") ?>', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Sayım başarıyla tamamlandı ve stok farkları envantere uygulandı.');
            location.reload();
        } else {
            alert('Hata: ' + res.message);
        }
    });
}
</script>
<?php include dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
