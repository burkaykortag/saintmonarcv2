<?php
use App\Helpers\ComponentHelper;
$title = 'OMS – Kısmi Gönderim | SaintMonarc';
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
.ship-table th {
    font-size: 11px;
    font-weight: 600;
    color: var(--pim-text-xs);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-bottom: 1px solid var(--pim-border)!important;
    padding: 10px 12px;
}
.ship-table td {
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
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Siparişler'=>url('/admin/orders'),'Sipariş Detayı'=>url('/admin/orders/show?id='.$order['id']),'Kısmi Gönderim'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-truck-flatbed me-2" style="color:#06b6d4"></i>Kısmi Gönderim Hazırla</h2>
        </div>
        <a href="<?= url('/admin/orders/show?id='.$order['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bg-arrow-left me-1"></i>Geri</a>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0" style="background:rgba(239, 68, 68, 0.1);color:#ef4444;border-radius:12px;margin-bottom:20px">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <form action="<?= url('/admin/orders/partial-shipment') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="oms-section">
                    <div class="oms-title"><i class="bi bi-list-check me-2"></i>Gönderilecek Ürünleri Seçin</div>
                    
                    <div class="table-responsive">
                        <table class="table ship-table mb-0">
                            <thead>
                                <tr>
                                    <th>Görsel</th>
                                    <th>Ürün Adı</th>
                                    <th>SKU</th>
                                    <th class="text-center">Sipariş</th>
                                    <th class="text-center">Gönderilen</th>
                                    <th class="text-center">Kalan</th>
                                    <th class="text-end" style="width:120px">Şimdi Gönder</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($item['product_image'])): ?>
                                            <img src="<?= $item['product_image'] ?>" alt="" style="width:36px;height:36px;border-radius:6px;object-fit:cover">
                                        <?php else: ?>
                                            <div style="width:36px;height:36px;border-radius:6px;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center"><i class="bi bi-image" style="color:var(--pim-text-xs)"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight:600"><?= htmlspecialchars($item['product_name']) ?></div>
                                    </td>
                                    <td><code style="font-size:11px;color:#c5a880"><?= htmlspecialchars($item['product_sku']) ?></code></td>
                                    <td class="text-center"><?= $item['quantity'] ?></td>
                                    <td class="text-center" style="color:#10b981"><?= $item['quantity_shipped'] ?></td>
                                    <td class="text-center" style="color:#f59e0b"><?= $item['quantity_pending'] ?></td>
                                    <td class="text-end">
                                        <input type="number" name="quantities[<?= $item['id'] ?>]" class="form-control border-0 text-white text-center" 
                                               style="background:rgba(255,255,255,0.04);font-size:12px;padding:6px;border-radius:8px" 
                                               min="0" max="<?= $item['quantity_pending'] ?>" value="<?= $item['quantity_pending'] ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="oms-section">
                    <div class="oms-title"><i class="bi bi-truck me-2"></i>Sevkiyat Detayları</div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Kargo Firması</label>
                        <input type="text" name="carrier_name" class="form-control border-0 text-white" 
                               style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px" 
                               value="Yurtiçi Kargo" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Takip Numarası</label>
                        <input type="text" name="tracking_number" class="form-control border-0 text-white" 
                               style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px" 
                               placeholder="Takip numarasını girin..." required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-size:11px;color:var(--pim-text-xs);text-transform:uppercase;letter-spacing:0.6px">Gönderim Metodu</label>
                        <select name="shipping_method_id" class="form-select border-0 text-white" 
                                style="background:rgba(255,255,255,0.04);font-size:12px;border-radius:8px;padding:10px">
                            <?php foreach ($shippingMethods as $sm): ?>
                                <option value="<?= $sm['id'] ?>" style="background:#0f0c20"><?= htmlspecialchars($sm['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-warning text-dark w-100 fw-bold"><i class="bi bi-check-circle-fill me-2"></i>Kısmi Sevkiyatı Başlat</button>
                </div>
            </div>
        </div>
    </form>
</div>
<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
