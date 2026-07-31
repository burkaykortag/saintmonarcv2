<?php
use App\Helpers\ComponentHelper;
$title = 'WMS – Picking (Ürün Toplama) | SaintMonarc';
include dirname(dirname(__DIR__)) . '/layouts/header.php';
?>
<style>
.pick-table th {
    font-size: 11px;
    font-weight: 600;
    color: var(--pim-text-xs);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-bottom: 1px solid var(--pim-border)!important;
    padding: 10px 12px;
}
.pick-table td {
    font-size: 12px;
    color: var(--pim-text-sm);
    border-bottom: 1px solid rgba(255, 255, 255, 0.04)!important;
    padding: 10px 12px;
    vertical-align: middle;
}
.pick-row.completed-pick {
    background: rgba(16, 185, 129, 0.03)!important;
    opacity: 0.7;
}
.pick-row.completed-pick td {
    text-decoration: line-through;
}
</style>

<div class="pim-module">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Depo Yönetimi'=>'#','Picking (Ürün Toplama)'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-hand-index-thumb me-2" style="color:#8b5cf6"></i>Picking (Ürün Toplama) Ekranı</h2>
        </div>
    </div>

    <div class="row g-4">
        <!-- Pick Queue List -->
        <div class="col-lg-8">
            <div class="card border-0 p-4 text-white mb-4" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important;border-radius:20px">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-white fs-6 m-0"><i class="bi bi-compass me-2 text-warning"></i>AI Rota Optimize Toplama Listesi</h4>
                    
                    <!-- Progress bar -->
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-size:11px;color:var(--pim-text-xs)">Toplama İlerlemesi:</span>
                        <div class="progress" style="width:100px;height:8px;background:rgba(255,255,255,0.05);border-radius:10px">
                            <div id="pickingProgress" class="progress-bar bg-success" role="progressbar" style="width: 0%; border-radius:10px"></div>
                        </div>
                        <span id="pickingPercent" class="fw-bold" style="font-size:11px;color:#10b981">0%</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table pick-table mb-0">
                        <thead>
                            <tr>
                                <th>Raf Lokasyon</th>
                                <th>Ürün Adı</th>
                                <th>SKU / Barkod</th>
                                <th class="text-center">Hedef Adet</th>
                                <th class="text-center">Toplanan</th>
                                <th class="text-end">Durum</th>
                            </tr>
                        </thead>
                        <tbody id="pickItemsBody">
                            <?php foreach ($pickingItems as $idx => $item): ?>
                            <tr class="pick-row" id="pick_row_<?= $idx ?>" data-sku="<?= htmlspecialchars($item['sku']) ?>" data-needed="<?= $item['qty_needed'] ?>" data-picked="0">
                                <td><span class="badge" style="background:rgba(197,168,128,0.1);color:#c5a880;font-size:12px;font-weight:700"><?= htmlspecialchars($item['location_code']) ?></span></td>
                                <td class="product-name" style="font-weight:600"><?= htmlspecialchars($item['product_name']) ?></td>
                                <td><code><?= htmlspecialchars($item['sku']) ?></code></td>
                                <td class="text-center fw-bold"><?= $item['qty_needed'] ?></td>
                                <td class="text-center picked-count text-info" style="font-weight:700">0</td>
                                <td class="text-end status-pill"><span class="badge bg-secondary" style="font-size:10px">BEKLENİYOR</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Barcode Reader Terminal -->
        <div class="col-lg-4">
            <div class="card border-0 p-4 text-white" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important;border-radius:20px">
                <h4 class="text-white fs-6 mb-3"><i class="bi bi-qr-code-scan me-2 text-warning"></i>Barkod Okuyucu Terminal</h4>
                
                <div class="mb-4">
                    <label class="form-label" style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Ürün SKU / Barkod Tara</label>
                    <input type="text" id="barcodeInput" class="form-control border-0 text-white text-center" 
                           style="background:rgba(255,255,255,0.04);font-size:16px;border-radius:8px;padding:12px;font-family:monospace;letter-spacing:1px" 
                           placeholder="SKU girin veya okutun..." autofocus onkeypress="handleBarcodeKey(event)">
                </div>

                <!-- Toast alert logs -->
                <div id="terminalAlert" class="alert d-none border-0 p-3 rounded-3 mb-0" style="font-size:12px">
                    <i class="bi bi-info-circle-fill me-2"></i><span id="alertText"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function handleBarcodeKey(event) {
    if (event.key === 'Enter') {
        const inputVal = document.getElementById('barcodeInput').value.trim();
        document.getElementById('barcodeInput').value = '';
        if (!inputVal) return;

        // Try to find the item in our picking rows
        const rows = document.querySelectorAll('.pick-row');
        let matched = false;

        for (let row of rows) {
            const sku = row.getAttribute('data-sku');
            const needed = parseInt(row.getAttribute('data-needed'));
            let picked = parseInt(row.getAttribute('data-picked'));

            if (sku === inputVal) {
                matched = true;
                if (picked < needed) {
                    picked++;
                    row.setAttribute('data-picked', picked);
                    row.querySelector('.picked-count').textContent = picked;

                    if (picked === needed) {
                        row.classList.add('completed-pick');
                        row.querySelector('.status-pill').innerHTML = '<span class="badge bg-success" style="font-size:10px">TAMAMLANDI</span>';
                        showAlert('Ürün toplama tamamlandı: ' + sku, 'success');
                    } else {
                        showAlert('Adet eklendi (' + picked + '/' + needed + '): ' + sku, 'info');
                    }
                    updateProgress();
                    break;
                } else {
                    showAlert('Zaten bu üründen hedeflenen adet toplandı!', 'warning');
                    break;
                }
            }
        }

        if (!matched) {
            showAlert('HATA: Toplama listesinde olmayan yanlış ürün barkodu!', 'danger');
        }
    }
}

function showAlert(msg, type) {
    const box = document.getElementById('terminalAlert');
    box.classList.remove('d-none', 'alert-success', 'alert-info', 'alert-warning', 'alert-danger');
    box.classList.add('alert-' + type);
    
    let icon = 'bi-info-circle-fill';
    if (type === 'success') icon = 'bi-check-circle-fill';
    if (type === 'warning' || type === 'danger') icon = 'bi-exclamation-triangle-fill';

    box.querySelector('i').className = 'bi ' + icon + ' me-2';
    document.getElementById('alertText').textContent = msg;
}

function updateProgress() {
    const rows = document.querySelectorAll('.pick-row');
    let totalNeeded = 0;
    let totalPicked = 0;

    rows.forEach(row => {
        totalNeeded += parseInt(row.getAttribute('data-needed'));
        totalPicked += parseInt(row.getAttribute('data-picked'));
    });

    const pct = totalNeeded > 0 ? Math.round((totalPicked / totalNeeded) * 100) : 0;
    document.getElementById('pickingProgress').style.width = pct + '%';
    document.getElementById('pickingPercent').textContent = pct + '%';
}
</script>
<?php include dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
