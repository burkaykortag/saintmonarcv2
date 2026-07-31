<?php
use App\Helpers\ComponentHelper;
$title = 'WMS – Packing Station (Paketleme İstasyonu) | SaintMonarc';
include dirname(dirname(__DIR__)) . '/layouts/header.php';
?>
<style>
.station-card {
    background: var(--pim-card);
    border: 1px solid var(--pim-border);
    border-radius: var(--pim-radius-lg);
    padding: 22px;
    margin-bottom: 22px;
}
.station-title {
    font-size: 15px;
    font-weight: 700;
    color: #c5a880;
    margin-bottom: 16px;
}
</style>

<div class="pim-module">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Depo Yönetimi'=>'#','Packing (Paketleme Istasyonu)'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-box me-2" style="color:#8b5cf6"></i>Packing Station (Paketleme İstasyonu)</h2>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="station-card text-white">
                <div class="station-title"><i class="bi bi-person-workspace me-2"></i>Aktif Paketleme İstasyon Detayları</div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3" style="background:rgba(255,255,255,0.01);border:1px solid rgba(255,255,255,0.03)">
                            <span class="text-muted fs-7">Operatör Adı:</span>
                            <div class="fw-bold fs-6 mt-1 text-white"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Hakan Yılmaz') ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded-3" style="background:rgba(255,255,255,0.01);border:1px solid rgba(255,255,255,0.03)">
                            <span class="text-muted fs-7">Bekleyen Sipariş:</span>
                            <div class="fw-bold fs-6 mt-1 text-warning"><?= count($packingOrders) ?> adet</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 rounded-3" style="background:rgba(255,255,255,0.01);border:1px solid rgba(255,255,255,0.03)">
                            <span class="text-muted fs-7">Tamamlanan:</span>
                            <div class="fw-bold fs-6 mt-1 text-success">14 sipariş</div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="text-white fs-7 mb-3"><i class="bi bi-box-seam me-2 text-warning"></i>Koli Kutu Seçimi (Carton Selector)</h5>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="form-check p-3 rounded-3 border-0 flex-fill text-center" style="background:rgba(255,255,255,0.02)">
                            <input class="form-check-input float-none mb-2" type="radio" name="box_size" id="box_s" value="S" checked>
                            <label class="form-check-label d-block text-white" for="box_s">
                                <strong>Kutu S (Küçük)</strong>
                                <span style="font-size:10px;display:block;color:var(--pim-text-xs)">20x20x15 cm</span>
                            </label>
                        </div>

                        <div class="form-check p-3 rounded-3 border-0 flex-fill text-center" style="background:rgba(255,255,255,0.02)">
                            <input class="form-check-input float-none mb-2" type="radio" name="box_size" id="box_m" value="M">
                            <label class="form-check-label d-block text-white" for="box_m">
                                <strong>Kutu M (Orta)</strong>
                                <span style="font-size:10px;display:block;color:var(--pim-text-xs)">40x30x20 cm</span>
                            </label>
                        </div>

                        <div class="form-check p-3 rounded-3 border-0 flex-fill text-center" style="background:rgba(255,255,255,0.02)">
                            <input class="form-check-input float-none mb-2" type="radio" name="box_size" id="box_l" value="L">
                            <label class="form-check-label d-block text-white" for="box_l">
                                <strong>Kutu L (Büyük)</strong>
                                <span style="font-size:10px;display:block;color:var(--pim-text-xs)">60x45x30 cm</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div>
                    <h5 class="text-white fs-7 mb-3"><i class="bi bi-speedometer me-2 text-warning"></i>Paket Ağırlığı & Kargo Entegrasyonu</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text border-0 text-white" style="background:rgba(255,255,255,0.05);font-size:12px">Paket Ağırlığı:</span>
                                <input type="number" id="pkgWeight" class="form-control border-0 text-white" 
                                       style="background:rgba(255,255,255,0.04);font-size:12px" placeholder="1.2" step="0.1">
                                <span class="input-group-text border-0 text-white" style="background:rgba(255,255,255,0.05);font-size:12px">KG</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-warning text-dark w-100 fw-bold" onclick="completePacking()"><i class="bi bi-printer me-2"></i>Kargo Etiketi Çıkar & Bitir</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="station-card text-white">
                <div class="station-title"><i class="bi bi-list-task me-2"></i>İstasyon İş Sırası (Queue)</div>
                
                <div class="d-flex flex-column gap-3">
                    <?php if (empty($packingOrders)): ?>
                        <div class="text-center py-4 text-muted fs-7">Paketleme sırasına alınmış sipariş bulunmamaktadır.</div>
                    <?php else: ?>
                        <?php foreach ($packingOrders as $o): ?>
                        <div class="p-3 rounded-3" style="background:rgba(255,255,255,0.02)">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong style="color:#c5a880"><?= htmlspecialchars($o['order_number']) ?></strong>
                                <span style="font-size:11px" class="text-muted"><?= date('H:i', strtotime($o['created_at'])) ?></span>
                            </div>
                            <div style="font-size:12px;color:var(--pim-text-xs)"><?= htmlspecialchars($o['billing_first_name'] . ' ' . $o['billing_last_name']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function completePacking() {
    const w = document.getElementById('pkgWeight').value || '1.5';
    alert(`Paketleme tamamlandı. Paket Ağırlığı: ${w} KG. Kargo sevk barkod etiketi yazdırılıyor...`);
    location.href = '<?= url("/admin/wms/dashboard") ?>';
}
</script>
<?php include dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
