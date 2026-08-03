<?php
use App\Helpers\ComponentHelper;
$title = 'WMS – Depo Analitik Raporları | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
?>
<style>
.an-table th {
    font-size: 11px;
    font-weight: 600;
    color: var(--pim-text-xs);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-bottom: 1px solid var(--pim-border)!important;
    padding: 10px 12px;
}
.an-table td {
    font-size: 12px;
    color: var(--pim-text-sm);
    border-bottom: 1px solid rgba(255, 255, 255, 0.04)!important;
    padding: 10px 12px;
    vertical-align: middle;
}
</style>

<div class="pim-module">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Depo Yönetimi'=>'#','Analitik Raporlar'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-bar-chart-line me-2" style="color:#c5a880"></i>WMS Depo Analitiği</h2>
        </div>
    </div>

    <!-- ABC & XYZ Matrix analysis -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 p-4 text-white" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important;border-radius:20px">
                <h4 class="text-white fs-6 mb-3"><i class="bi bi-pie-chart-fill text-warning me-2"></i>ABC Analizi (Ciro Hacim Dağılımı)</h4>
                
                <div class="table-responsive">
                    <table class="table an-table mb-0">
                        <thead>
                            <tr>
                                <th>Ürün Adı</th>
                                <th>SKU</th>
                                <th>Ciro (Toplam Satış)</th>
                                <th class="text-end">ABC Sınıfı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($abcAnalysis as $item): 
                                $badgeColor = '#ef4444';
                                if (str_contains($item['abc_class'], 'A')) $badgeColor = '#10b981';
                                elseif (str_contains($item['abc_class'], 'B')) $badgeColor = '#fbbf24';
                            ?>
                            <tr>
                                <td style="font-weight:600"><?= htmlspecialchars($item['product_name']) ?></td>
                                <td><code><?= htmlspecialchars($item['sku']) ?></code></td>
                                <td class="text-white">₺<?= number_format((float)$item['ciro'], 2, ',', '.') ?></td>
                                <td class="text-end">
                                    <span class="badge" style="background:<?= $badgeColor ?>22;color:<?= $badgeColor ?>;border:1px solid <?= $badgeColor ?>44;font-size:10px">
                                        <?= htmlspecialchars($item['abc_class']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 p-4 text-white" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important;border-radius:20px">
                <h4 class="text-white fs-6 mb-3"><i class="bi bi-activity text-warning me-2"></i>XYZ Analizi (Talep Kararlılığı)</h4>
                
                <div class="table-responsive">
                    <table class="table an-table mb-0">
                        <thead>
                            <tr>
                                <th>Ürün Adı</th>
                                <th>SKU</th>
                                <th>Talep Kararlılığı</th>
                                <th class="text-end">XYZ Sınıfı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($xyzAnalysis as $xyz): 
                                $badgeColor = '#ef4444';
                                if (str_contains($xyz['xyz_class'], 'X')) $badgeColor = '#10b981';
                                elseif (str_contains($xyz['xyz_class'], 'Y')) $badgeColor = '#fbbf24';
                            ?>
                            <tr>
                                <td style="font-weight:600"><?= htmlspecialchars($xyz['name']) ?></td>
                                <td><code><?= htmlspecialchars($xyz['sku']) ?></code></td>
                                <td><?= htmlspecialchars($xyz['frequency']) ?></td>
                                <td class="text-end">
                                    <span class="badge" style="background:<?= $badgeColor ?>22;color:<?= $badgeColor ?>;border:1px solid <?= $badgeColor ?>44;font-size:10px">
                                        <?= htmlspecialchars($xyz['xyz_class']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Extra Performance KPI logs -->
    <div class="card border-0 p-4 text-white" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important;border-radius:20px">
        <h4 class="text-white fs-6 mb-3"><i class="bi bi-stopwatch text-warning me-2"></i>Depo & Personel Operasyonel Performansı</h4>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="p-3 rounded-3" style="background:rgba(255,255,255,0.01);border:1px solid rgba(255,255,255,0.03)">
                    <span class="text-muted fs-7">Ortalama Stokta Kalma Süresi</span>
                    <div class="fw-bold fs-6 mt-1 text-white">18 gün</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3" style="background:rgba(255,255,255,0.01);border:1px solid rgba(255,255,255,0.03)">
                    <span class="text-muted fs-7">Sipariş Hazırlama (Picking) Süresi</span>
                    <div class="fw-bold fs-6 mt-1 text-white">4.2 dakika</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3" style="background:rgba(255,255,255,0.01);border:1px solid rgba(255,255,255,0.03)">
                    <span class="text-muted fs-7">Hatalı Toplama Oranı</span>
                    <div class="fw-bold fs-6 mt-1 text-success">%0,05 <span class="text-muted fs-7">(Hedef %0.10)</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
