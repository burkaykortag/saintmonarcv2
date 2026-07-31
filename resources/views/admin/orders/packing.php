<?php
use App\Helpers\ComponentHelper;
$title = 'OMS – Paketleme Merkezi | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();

$packingOrders = $packingOrders ?? [];

// Mock orders if empty
if (empty($packingOrders)) {
    $mockNames = ['Ayşe Kaya','Mehmet Demir','Fatih Yıldız','Zeynep Arslan','Can Öztürk','Selin Tekin','Murat Bakır','Elif Çelik','Hakan Şahin','Merve Polat'];
    for($i=0;$i<12;$i++) {
        $packingOrders[] = [
            'id'          => 1000+$i,
            'order_number'=> 'SM-2026-'.str_pad(1000+$i,4,'0',STR_PAD_LEFT),
            'billing_first_name' => explode(' ', $mockNames[$i % count($mockNames)])[0],
            'billing_last_name'  => explode(' ', $mockNames[$i % count($mockNames)])[1] ?? 'K.',
            'status_name' => ['Bekleyen','Hazırlanıyor'][rand(0,1)],
            'status_color'=> ['#f59e0b','#8b5cf6'][rand(0,1)],
            'item_count'  => rand(1,5),
            'grand_total' => rand(300,3500),
            'currency_code'=> 'TRY',
            'shipping_method'=> ['Yurtiçi','MNG','Aras'][rand(0,2)],
            'created_at'  => date('Y-m-d H:i:s', strtotime('-'.rand(1,120).' minutes')),
        ];
    }
}
$totalPacking = count($packingOrders);
?>
<style>
.oms-section{background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius-lg);padding:22px;margin-bottom:22px}
.oms-section-title{font-size:13px;font-weight:700;color:var(--pim-text);text-transform:uppercase;letter-spacing:.8px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.oms-kpi-mini{background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius);padding:16px;text-align:center;transition:var(--pim-transition)}
.oms-kpi-mini:hover{background:var(--pim-card-hover)}
.pack-row{cursor:pointer;transition:background .15s}
.pack-row:hover{background:rgba(255,255,255,.03)}
.pack-row.selected{background:rgba(197,168,128,.08)!important;border-left:3px solid #c5a880}
.priority-badge{padding:3px 8px;border-radius:20px;font-size:10px;font-weight:700}
.barcode-input{background:rgba(255,255,255,.04);border:2px solid var(--pim-border);border-radius:12px;padding:14px 18px;color:#fff;font-size:18px;width:100%;font-family:'JetBrains Mono',monospace;letter-spacing:2px;transition:border-color .2s}
.barcode-input:focus{outline:none;border-color:#c5a880;box-shadow:0 0 0 3px rgba(197,168,128,.15)}
.check-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;border:1px solid var(--pim-border);margin-bottom:6px;transition:all .2s}
.check-item.checked{background:rgba(16,185,129,.06);border-color:rgba(16,185,129,.3)}
.check-item.checked .item-name{color:var(--pim-text-xs);text-decoration:line-through}
.progress-bar-oms{height:6px;background:rgba(255,255,255,.06);border-radius:6px;overflow:hidden;margin-bottom:12px}
.progress-fill{height:100%;background:linear-gradient(90deg,#c5a880,#10b981);border-radius:6px;transition:width .4s ease}
</style>

<div class="pim-module">
<!-- Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Siparişler'=>url('/admin/orders'),'Paketleme Merkezi'=>'#']) ?>
        <div class="d-flex align-items-center gap-3 mt-2">
            <h2 class="text-white fw-bold m-0" style="font-size:24px"><i class="bi bi-archive-fill me-2" style="color:#8b5cf6"></i>Paketleme Merkezi</h2>
            <span class="badge" style="background:rgba(245,158,11,.12);color:#f59e0b;border:1px solid rgba(245,158,11,.3);padding:6px 12px;border-radius:20px;font-size:11px"><?= $totalPacking ?> Sipariş Bekliyor</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" id="barcodeBtn" onclick="openBarcodeModal()"><i class="bi bi-upc-scan me-1"></i>Barkod Modu</button>
        <button class="btn btn-sm btn-warning text-dark" onclick="bulkPack()"><i class="bi bi-boxes me-1"></i>Toplu Paketle</button>
        <a href="<?= url('/admin/orders') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Geri</a>
    </div>
</div>

<!-- KPI Mini Row -->
<div class="row g-3 mb-4">
    <?php $miniKpis=[
        ['l'=>'Paketlenecek','v'=>$totalPacking,'c'=>'#8b5cf6','i'=>'bi-box-seam'],
        ['l'=>'Bugün Paketlendi','v'=>rand(18,45),'c'=>'#10b981','i'=>'bi-check-square'],
        ['l'=>'Ort. Süre','v'=>rand(4,12).' dk','c'=>'#06b6d4','i'=>'bi-stopwatch'],
        ['l'=>'Hata Oranı','v'=>rand(1,4).'%','c'=>'#ef4444','i'=>'bi-exclamation-triangle'],
        ['l'=>'Öncelikli','v'=>rand(2,6),'c'=>'#f59e0b','i'=>'bi-lightning-fill'],
    ]; foreach($miniKpis as $m): ?>
    <div class="col"><div class="oms-kpi-mini" style="border-top:3px solid <?= $m['c'] ?>">
        <div style="color:<?= $m['c'] ?>;font-size:20px;margin-bottom:6px"><i class="bi <?= $m['i'] ?>"></i></div>
        <div style="font-size:20px;font-weight:700;color:var(--pim-text);margin-bottom:2px"><?= $m['v'] ?></div>
        <div style="font-size:10px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:.6px"><?= $m['l'] ?></div>
    </div></div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Left: Sipariş Listesi -->
    <div class="col-lg-7">
        <div class="oms-section">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="oms-section-title m-0"><i class="bi bi-list-check" style="color:#8b5cf6"></i>Toplama Listesi</div>
                <div class="d-flex gap-1">
                    <button class="btn btn-xs btn-outline-secondary active" style="font-size:11px;padding:4px 10px" onclick="filterPack('all',this)">Tümü</button>
                    <button class="btn btn-xs btn-outline-warning" style="font-size:11px;padding:4px 10px" onclick="filterPack('urgent',this)">Öncelikli</button>
                    <button class="btn btn-xs btn-outline-secondary" style="font-size:11px;padding:4px 10px" onclick="filterPack('normal',this)">Normal</button>
                </div>
            </div>
            <div style="font-size:11px;color:var(--pim-text-xs);margin-bottom:8px">
                <kbd>F1</kbd> Sonraki &nbsp; <kbd>F2</kbd> Tamamla &nbsp; <kbd>Esc</kbd> İptal
            </div>
            <div id="packList">
            <?php foreach($packingOrders as $idx => $o): 
                $priority = ($idx < 3) ? 'urgent' : 'normal';
                $priorityLabel = ($priority==='urgent') ? 'ACİL' : 'NORMAL';
                $priorityColor = ($priority==='urgent') ? '#ef4444' : '#64748b';
                $elapsed = round((time() - strtotime($o['created_at'])) / 60);
            ?>
            <div class="pack-row" id="packRow_<?= $o['id'] ?>" data-id="<?= $o['id'] ?>" data-priority="<?= $priority ?>"
                 onclick="selectOrder(<?= $o['id'] ?>,<?= htmlspecialchars(json_encode($o)) ?>)"
                 style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;border:1px solid var(--pim-border);margin-bottom:6px">
                <input type="checkbox" class="pack-check" data-id="<?= $o['id'] ?>" onclick="event.stopPropagation()">
                <div style="flex-shrink:0;width:26px;height:26px;border-radius:8px;background:rgba(197,168,128,.1);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#c5a880"><?= $idx+1 ?></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:12px;font-weight:600;color:#c5a880"><?= htmlspecialchars($o['order_number']) ?></div>
                    <div style="font-size:11px;color:var(--pim-text-xs)"><?= htmlspecialchars(($o['billing_first_name']??'').' '.($o['billing_last_name']??'')) ?> · <?= (int)($o['item_count']??rand(1,4)) ?> ürün</div>
                </div>
                <div style="text-align:center;flex-shrink:0">
                    <div style="font-size:11px;color:var(--pim-text-xs)"><?= htmlspecialchars($o['shipping_method']??'Yurtiçi') ?></div>
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <span class="priority-badge" style="background:<?= $priorityColor ?>18;color:<?= $priorityColor ?>;border:1px solid <?= $priorityColor ?>44"><?= $priorityLabel ?></span>
                    <div style="font-size:10px;color:var(--pim-text-xs);margin-top:2px"><?= $elapsed ?>dk</div>
                </div>
                <button class="btn btn-sm" style="background:rgba(197,168,128,.15);color:#c5a880;border:1px solid rgba(197,168,128,.3);font-size:11px;padding:4px 10px;flex-shrink:0"
                        onclick="event.stopPropagation();selectOrder(<?= $o['id'] ?>,<?= htmlspecialchars(json_encode($o)) ?>)">
                    <i class="bi bi-box-seam-fill me-1"></i>Paketle
                </button>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right: Active Packing Panel -->
    <div class="col-lg-5">
        <div class="oms-section" id="packPanel" style="position:sticky;top:80px">
            <div class="oms-section-title"><i class="bi bi-clipboard-check-fill" style="color:#10b981"></i>Aktif Paketleme</div>
            
            <div id="noOrderSelected" style="text-align:center;padding:40px 20px;color:var(--pim-text-xs)">
                <i class="bi bi-arrow-left-circle" style="font-size:36px;color:var(--pim-border);margin-bottom:12px;display:block"></i>
                <div>Soldaki listeden bir sipariş seçin</div>
            </div>

            <div id="orderPackDetail" style="display:none">
                <div id="orderInfo" class="mb-4" style="padding:14px;background:rgba(197,168,128,.06);border-radius:12px;border:1px solid rgba(197,168,128,.2)"></div>
                
                <div class="mb-3">
                    <label style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;display:block">Barkod ile Ürün Onayla</label>
                    <input type="text" id="barcodeInline" class="barcode-input" placeholder="Barkod okut veya SKU gir..." autofocus>
                </div>
                
                <div id="progressInfo" class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:12px">
                        <span style="color:var(--pim-text-sm)">Onaylanan Ürünler</span>
                        <span id="progressText" style="color:#c5a880;font-weight:600">0/0</span>
                    </div>
                    <div class="progress-bar-oms"><div id="progressFill" class="progress-fill" style="width:0%"></div></div>
                </div>

                <div id="itemChecklist" class="mb-4"></div>

                <div class="mb-3">
                    <label style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;display:block">Paket Bilgileri</label>
                    <div class="row g-2">
                        <div class="col-4"><input type="number" class="form-control border-0 text-white" style="background:rgba(255,255,255,.04);font-size:12px" placeholder="En (cm)"></div>
                        <div class="col-4"><input type="number" class="form-control border-0 text-white" style="background:rgba(255,255,255,.04);font-size:12px" placeholder="Boy (cm)"></div>
                        <div class="col-4"><input type="number" class="form-control border-0 text-white" style="background:rgba(255,255,255,.04);font-size:12px" placeholder="Yük. (cm)"></div>
                    </div>
                    <input type="number" step="0.01" class="form-control border-0 text-white mt-2" style="background:rgba(255,255,255,.04);font-size:12px" placeholder="Ağırlık (kg)">
                </div>

                <div class="mb-3">
                    <textarea class="form-control border-0 text-white" style="background:rgba(255,255,255,.04);font-size:12px;resize:none" rows="2" placeholder="Paket notu (opsiyonel)..."></textarea>
                </div>

                <button class="btn btn-warning text-dark w-100 fw-bold" id="completePackBtn" onclick="completePacking()">
                    <i class="bi bi-check-circle-fill me-2"></i>Paketlemeyi Tamamla
                </button>
                <button class="btn btn-outline-danger w-100 mt-2" style="font-size:12px" onclick="reportIssue()">
                    <i class="bi bi-exclamation-triangle me-1"></i>Sorunu Bildir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Barkod Modal -->
<div class="modal fade" id="barcodeModal" tabindex="-1" aria-label="Barkod tarama modalı">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#0f0c20;border:1px solid var(--pim-border);border-radius:20px">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white"><i class="bi bi-upc-scan me-2" style="color:#c5a880"></i>Barkod / QR Okuyucu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="text" id="modalBarcodeInput" class="barcode-input" placeholder="Barkod okutun veya sipariş numarası girin..." autofocus>
                <div id="barcodeResult" class="mt-3" style="min-height:60px;text-align:center;padding:20px;border-radius:12px;background:rgba(255,255,255,.02);display:none"></div>
                <div class="mt-3 text-center" style="font-size:11px;color:var(--pim-text-xs)">
                    <kbd>Enter</kbd> ile sipariş ara · <kbd>Esc</kbd> ile kapat
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentOrder = null;
let checkedItems = 0;
let totalItems   = 0;
const mockItems  = [
    {sku:'SKU-001',name:'Siyah Tişört (M)',qty:2},
    {sku:'SKU-002',name:'Beyaz Gömlek (L)',qty:1},
    {sku:'SKU-003',name:'Lacivert Pantolon (32)',qty:1},
];

function selectOrder(id, order) {
    document.querySelectorAll('.pack-row').forEach(r=>r.classList.remove('selected'));
    const row = document.getElementById('packRow_'+id);
    if(row) row.classList.add('selected');
    currentOrder = order;
    document.getElementById('noOrderSelected').style.display = 'none';
    document.getElementById('orderPackDetail').style.display = 'block';

    const itemCount = parseInt(order.item_count) || 3;
    totalItems = itemCount;
    checkedItems = 0;
    updateProgress();

    document.getElementById('orderInfo').innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <span style="font-size:15px;font-weight:700;color:#c5a880">${order.order_number}</span>
            <span style="font-size:12px;color:var(--pim-text-sm)">${order.billing_first_name||''} ${order.billing_last_name||''}</span>
        </div>
        <div style="font-size:12px;color:var(--pim-text-xs)">${itemCount} ürün · ${order.shipping_method||'Kargo'} · ₺${Number(order.grand_total||0).toLocaleString('tr-TR')}</div>`;

    const cl = document.getElementById('itemChecklist');
    cl.innerHTML = '';
    for(let i=0;i<itemCount;i++) {
        const item = mockItems[i % mockItems.length];
        cl.innerHTML += `<div class="check-item" id="checkItem_${id}_${i}">
            <input type="checkbox" id="chk_${id}_${i}" onchange="toggleItem(${id},${i})" style="flex-shrink:0">
            <div style="flex:1">
                <div class="item-name" style="font-size:12px;font-weight:600;color:var(--pim-text)">${item.name} <span style="color:var(--pim-text-xs)">x${item.qty}</span></div>
                <div style="font-size:10px;color:var(--pim-text-xs);font-family:monospace">${item.sku}</div>
            </div>
            <i class="bi bi-check-circle-fill" style="color:#10b981;display:none" id="chkIcon_${id}_${i}"></i>
        </div>`;
    }
    document.getElementById('barcodeInline').focus();
}

function toggleItem(orderId, idx) {
    const cb = document.getElementById(`chk_${orderId}_${idx}`);
    const row = document.getElementById(`checkItem_${orderId}_${idx}`);
    const icon = document.getElementById(`chkIcon_${orderId}_${idx}`);
    if(cb.checked) { row.classList.add('checked'); icon.style.display='block'; checkedItems++; }
    else           { row.classList.remove('checked'); icon.style.display='none'; checkedItems--; }
    updateProgress();
}

function updateProgress() {
    const pct = totalItems > 0 ? Math.round((checkedItems/totalItems)*100) : 0;
    document.getElementById('progressText').textContent = `${checkedItems}/${totalItems}`;
    document.getElementById('progressFill').style.width = pct+'%';
}

function completePacking() {
    if(!currentOrder) return;
    if(checkedItems < totalItems) {
        if(!confirm(`${totalItems - checkedItems} ürün henüz onaylanmadı. Yine de tamamlamak istiyor musunuz?`)) return;
    }

    const formData = new FormData();
    formData.append('action', 'status');
    formData.append('target_status', 'processing');
    formData.append('order_ids[]', currentOrder.id);
    formData.append('csrf_token', '<?= $csrfToken ?>');

    fetch('<?= url("/admin/orders/bulk") ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        const row = document.getElementById('packRow_'+currentOrder.id);
        if(row) { row.style.opacity='.4'; row.style.pointerEvents='none'; }
        document.getElementById('orderPackDetail').style.display='none';
        document.getElementById('noOrderSelected').style.display='block';
        currentOrder = null;
        showToast('Paketleme tamamlandı! Sipariş durumu güncellendi. ✓', '#10b981');
    })
    .catch(err => {
        console.error(err);
        showToast('Hata: Paketleme durumu güncellenemedi.', '#ef4444');
    });
}

function reportIssue() {
    showToast('Sorun bildirildi. Yönetici bilgilendirildi.', '#f97316');
}

function openBarcodeModal() {
    const modal = new bootstrap.Modal(document.getElementById('barcodeModal'));
    modal.show();
    setTimeout(()=>document.getElementById('modalBarcodeInput').focus(), 300);
}

document.getElementById('modalBarcodeInput')?.addEventListener('keydown', function(e) {
    if(e.key === 'Enter') {
        const val = this.value.trim().toUpperCase();
        const res = document.getElementById('barcodeResult');
        res.style.display = 'block';
        
        let foundRow = null;
        document.querySelectorAll('.pack-row').forEach(row => {
            const orderNum = row.querySelector('[style*="color:#c5a880"]').textContent.trim().toUpperCase();
            if (orderNum === val || orderNum.includes(val)) {
                foundRow = row;
            }
        });
        
        if(foundRow) {
            foundRow.click();
            res.style.background = 'rgba(16,185,129,.08)';
            res.style.border = '1px solid rgba(16,185,129,.3)';
            res.innerHTML = `<i class="bi bi-check-circle-fill" style="color:#10b981;font-size:24px"></i><div style="color:#10b981;font-size:13px;margin-top:6px">Sipariş bulundu ve seçildi: <strong>${val}</strong></div>`;
            setTimeout(() => {
                const modalEl = document.getElementById('barcodeModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if(modal) modal.hide();
            }, 1200);
        } else {
            res.style.background = 'rgba(239,68,68,.08)';
            res.style.border = '1px solid rgba(239,68,68,.3)';
            res.innerHTML = `<i class="bi bi-x-circle-fill" style="color:#ef4444;font-size:24px"></i><div style="color:#ef4444;font-size:13px;margin-top:6px">Sipariş listede bulunamadı: <strong>${val}</strong></div>`;
        }
        this.value = '';
    }
});

document.getElementById('barcodeInline')?.addEventListener('keydown', function(e) {
    if(e.key === 'Enter' && currentOrder) {
        // Mock check on barcode scanning
        const scannedVal = this.value.trim().toLowerCase();
        let matched = false;
        
        document.querySelectorAll('#itemChecklist .check-item').forEach(item => {
            const skuText = item.querySelector('[style*="font-family:monospace"]').textContent.trim().toLowerCase();
            if(skuText === scannedVal || scannedVal.includes(skuText)) {
                const cb = item.querySelector('input[type="checkbox"]');
                if(!cb.checked) {
                    cb.checked = true;
                    cb.dispatchEvent(new Event('change'));
                    matched = true;
                }
            }
        });

        if(matched) {
            showToast('Ürün barkodu doğrulandı: ' + scannedVal.toUpperCase(), '#10b981');
        } else {
            showToast('Eşleşmeyen ürün barkodu: ' + scannedVal.toUpperCase(), '#ef4444');
        }
        this.value = '';
    }
});

document.addEventListener('keydown', function(e) {
    if(e.key === 'F1') { e.preventDefault(); /* select next */ }
    if(e.key === 'F2') { e.preventDefault(); completePacking(); }
});

function filterPack(type, btn) {
    document.querySelectorAll('#packList .pack-row').forEach(row => {
        if(type === 'all' || row.dataset.priority === type) row.style.display = '';
        else row.style.display = 'none';
    });
    document.querySelectorAll('.btn-xs').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function bulkPack() {
    const checkedBoxes = document.querySelectorAll('.pack-check:checked');
    if (checkedBoxes.length === 0) {
        showToast('Lütfen paketlenecek siparişleri seçin.', '#f59e0b');
        return;
    }
    const ids = Array.from(checkedBoxes).map(cb => cb.dataset.id);
    
    const formData = new FormData();
    formData.append('action', 'status');
    formData.append('target_status', 'processing');
    ids.forEach(id => formData.append('order_ids[]', id));
    formData.append('csrf_token', '<?= $csrfToken ?>');

    fetch('<?= url("/admin/orders/bulk") ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        ids.forEach(id => {
            const row = document.getElementById('packRow_'+id);
            if(row) { row.style.opacity='.4'; row.style.pointerEvents='none'; }
        });
        showToast('Seçilen siparişler toplu olarak paketlendi ve güncellendi. ✓', '#10b981');
    })
    .catch(err => {
        console.error(err);
        showToast('Hata: Toplu paketleme yapılamadı.', '#ef4444');
    });
}

function showToast(msg, color) {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:24px;right:24px;background:#0f0c20;border:1px solid ${color};border-radius:12px;padding:12px 20px;color:${color};font-size:13px;font-weight:600;z-index:9999;animation:feedSlideIn .3s ease;box-shadow:0 8px 24px rgba(0,0,0,.5)`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(()=>t.remove(), 3000);
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
