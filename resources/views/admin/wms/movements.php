<?php
use App\Helpers\ComponentHelper;
$title = 'WMS – Stok Hareket Merkezi | SaintMonarc';
include dirname(dirname(__DIR__)) . '/layouts/header.php';
?>
<style>
.mv-table th {
    font-size: 11px;
    font-weight: 600;
    color: var(--pim-text-xs);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-bottom: 1px solid var(--pim-border)!important;
    padding: 10px 12px;
}
.mv-table td {
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
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Depo Yönetimi'=>'#','Stok Hareketleri'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-arrow-down-up me-2" style="color:#fbbf24"></i>Stok Hareket Merkezi (Movements)</h2>
        </div>
    </div>

    <!-- Filters -->
    <form action="" method="GET" class="mb-4">
        <div class="row g-3">
            <div class="col-md-2">
                <input type="text" name="q" class="form-control border-0 text-white" 
                       style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px" 
                       placeholder="Ürün adı, SKU veya açıklama..." value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
            </div>

            <div class="col-md-2">
                <select name="warehouse_id" class="form-select border-0 text-white" 
                        style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px">
                    <option value="" style="background:#0f0c20">Tüm Depolar</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= ($filters['warehouse_id'] ?? '') == $w['id'] ? 'selected' : '' ?> style="background:#0f0c20">
                            <?= htmlspecialchars($w['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="type" class="form-select border-0 text-white" 
                        style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px">
                    <option value="" style="background:#0f0c20">Tüm Hareketler</option>
                    <option value="giriş" <?= ($filters['type'] ?? '') === 'giriş' ? 'selected' : '' ?> style="background:#0f0c20">Giriş (Inbound)</option>
                    <option value="çıkış" <?= ($filters['type'] ?? '') === 'çıkış' ? 'selected' : '' ?> style="background:#0f0c20">Çıkış (Outbound)</option>
                    <option value="transfer" <?= ($filters['type'] ?? '') === 'transfer' ? 'selected' : '' ?> style="background:#0f0c20">Transfer</option>
                    <option value="sayım" <?= ($filters['type'] ?? '') === 'sayım' ? 'selected' : '' ?> style="background:#0f0c20">Sayım Farkı</option>
                </select>
            </div>

            <div class="col-md-2">
                <input type="date" name="start_date" class="form-control border-0 text-white" 
                       style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px" 
                       value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
            </div>

            <div class="col-md-2">
                <input type="date" name="end_date" class="form-control border-0 text-white" 
                       style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px" 
                       value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-warning text-dark w-100 fw-bold" style="font-size:12px;padding:10px"><i class="bi bi-funnel-fill me-1"></i>Filtrele</button>
            </div>
        </div>
    </form>

    <!-- Movements Table -->
    <div class="card border-0 p-4 text-white" style="background:rgba(255,255,255,0.02);border:1px solid var(--pim-border)!important;border-radius:20px">
        <div class="table-responsive">
            <table class="table mv-table mb-0">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Depo</th>
                        <th>Ürün / SKU</th>
                        <th>Hareket Tipi</th>
                        <th class="text-center">Miktar</th>
                        <th>Açıklama</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movements)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belirtilen kriterlerde stok hareketi bulunamadı.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movements as $m): 
                            $qty = (int)$m['quantity'];
                            $badgeColor = 'rgba(255, 255, 255, 0.05)';
                            $badgeTxtColor = '#fff';
                            
                            if ($m['type'] === 'giriş') {
                                $badgeColor = 'rgba(16, 185, 129, 0.1)';
                                $badgeTxtColor = '#10b981';
                            } elseif ($m['type'] === 'çıkış') {
                                $badgeColor = 'rgba(239, 68, 68, 0.1)';
                                $badgeTxtColor = '#ef4444';
                            } elseif ($m['type'] === 'transfer') {
                                $badgeColor = 'rgba(59, 130, 246, 0.1)';
                                $badgeTxtColor = '#3b82f6';
                            } elseif ($m['type'] === 'sayım') {
                                $badgeColor = 'rgba(245, 158, 11, 0.1)';
                                $badgeTxtColor = '#f59e0b';
                            }
                        ?>
                        <tr>
                            <td><?= date('d.m.Y H:i', strtotime($m['created_at'])) ?></td>
                            <td><?= htmlspecialchars($m['warehouse_name'] ?? 'Merkez Depo') ?></td>
                            <td>
                                <div style="font-weight:600"><?= htmlspecialchars($m['product_name']) ?></div>
                                <code style="font-size:10px;color:#c5a880">SKU: <?= htmlspecialchars($m['product_sku']) ?></code>
                            </td>
                            <td>
                                <span class="badge" style="background:<?= $badgeColor ?>;color:<?= $badgeTxtColor ?>;font-size:10px;text-transform:uppercase">
                                    <?= htmlspecialchars($m['type']) ?>
                                </span>
                            </td>
                            <td class="text-center fw-bold" style="color:<?= $qty > 0 ? '#10b981' : '#ef4444' ?>">
                                <?= $qty > 0 ? '+' : '' ?><?= $qty ?>
                            </td>
                            <td><?= htmlspecialchars($m['description'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
