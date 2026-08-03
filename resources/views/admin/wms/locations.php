<?php
use App\Helpers\ComponentHelper;
$title = 'WMS – Lokasyonlar & Heat Map | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
?>
<style>
.heat-map-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 12px;
}
.rack-cell {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid var(--pim-border);
    border-radius: 12px;
    padding: 12px;
    text-align: center;
    transition: all 0.2s;
}
.rack-cell:hover {
    border-color: #c5a880;
    transform: scale(1.02);
}
.rack-code {
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 6px;
    display: block;
}
.rack-status {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 4px;
}
.occ-pct {
    font-size: 11px;
    font-weight: 600;
}
</style>

<div class="pim-module">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Depo Yönetimi'=>'#','Lokasyonlar'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-grid-3x3-gap me-2" style="color:#10b981"></i>Raf Lokasyonları & Isı Haritası</h2>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="text-muted fs-7">Depo Seçin:</span>
            <select class="form-select border-0 text-white" style="background:rgba(255,255,255,0.05);font-size:12px;width:200px" 
                    onchange="location.href='?warehouse_id='+this.value">
                <?php foreach ($warehouses as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $selected_warehouse_id === (int)$w['id'] ? 'selected' : '' ?> style="background:#0f0c20">
                        <?= htmlspecialchars($w['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Heat Map Grid -->
    <div class="card border-0 p-4 mb-4" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important;border-radius:20px">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-white fs-6 m-0"><i class="bi bi-fire text-danger me-2"></i>Depo Doluluk Isı Haritası (Warehouse Heat Map)</h4>
            
            <!-- Legend -->
            <div class="d-flex gap-3 align-items-center" style="font-size:11px">
                <div class="d-flex align-items-center"><span class="rack-status" style="background:#10b981"></span> <span class="text-muted">&lt;%50 Boş</span></div>
                <div class="d-flex align-items-center"><span class="rack-status" style="background:#fbbf24"></span> <span class="text-muted">%50-80 Orta</span></div>
                <div class="d-flex align-items-center"><span class="rack-status" style="background:#f59e0b"></span> <span class="text-muted">%80-95 Dolu</span></div>
                <div class="d-flex align-items-center"><span class="rack-status" style="background:#ef4444"></span> <span class="text-muted">&gt;%95 Kritik</span></div>
            </div>
        </div>

        <div class="heat-map-grid">
            <?php foreach ($locations as $loc): 
                $maxC = (int)$loc['max_capacity'];
                $currC = (int)$loc['current_qty'];
                $pct = $maxC > 0 ? round(($currC / $maxC) * 100) : 0;
                
                $color = '#10b981'; // Green
                if ($pct > 95) $color = '#ef4444'; // Red
                elseif ($pct > 80) $color = '#f59e0b'; // Orange
                elseif ($pct > 50) $color = '#fbbf24'; // Yellow
            ?>
            <div class="rack-cell">
                <span class="rack-code text-white"><?= htmlspecialchars($loc['location_code']) ?></span>
                <div class="d-flex align-items-center justify-content-center gap-1">
                    <span class="rack-status" style="background:<?= $color ?>"></span>
                    <span class="occ-pct" style="color:<?= $color ?>"><?= $pct ?>%</span>
                </div>
                <span style="font-size:9px;color:var(--pim-text-xs);display:block;margin-top:4px" class="text-muted"><?= $currC ?> / <?= $maxC ?> adet</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Location Code Generator Wizard -->
    <div class="card border-0 p-4" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important;border-radius:20px">
        <h4 class="text-white fs-6 mb-3"><i class="bi bi-cpu text-warning me-2"></i>Yeni Lokasyon Koordinat Sihirbazı</h4>
        <p style="font-size:11px;color:var(--pim-text-xs)" class="mb-4 text-muted">Aşağıdaki alanları doldurarak Enterprise lokasyon koordinat kodu (örn: A-03-04-B) otomatik olarak üretebilirsiniz.</p>

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Koridor (Aisle)</label>
                <input type="text" id="aisle" class="form-control border-0 text-white" style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px" placeholder="A" oninput="genLoc()">
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Raf (Rack)</label>
                <input type="text" id="rack" class="form-control border-0 text-white" style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px" placeholder="03" oninput="genLoc()">
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Kat (Shelf)</label>
                <input type="text" id="shelf" class="form-control border-0 text-white" style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px" placeholder="04" oninput="genLoc()">
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Göz (Bin)</label>
                <input type="text" id="bin" class="form-control border-0 text-white" style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px" placeholder="B" oninput="genLoc()">
            </div>
        </div>

        <div class="mt-4 p-3 rounded-3 d-flex justify-content-between align-items-center" style="background:rgba(255,255,255,0.01)">
            <span style="font-size:12px;color:var(--pim-text-sm)">Üretilen Lokasyon Kodu:</span>
            <span id="loc_result" class="fw-bold" style="font-size:18px;color:#c5a880;letter-spacing:1px">A-03-04-B</span>
        </div>
    </div>
</div>

<script>
function genLoc() {
    const aisle = document.getElementById('aisle').value || 'A';
    const rack = document.getElementById('rack').value || '03';
    const shelf = document.getElementById('shelf').value || '04';
    const bin = document.getElementById('bin').value || 'B';
    document.getElementById('loc_result').textContent = `${aisle}-${rack}-${shelf}-${bin}`;
}
</script>
<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
