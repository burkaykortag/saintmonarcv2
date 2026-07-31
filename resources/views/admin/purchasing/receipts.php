<?php
use App\Helpers\ComponentHelper;
$title = "Mal Kabul (Goods Receipt) | SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="container-fluid py-4 text-white">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim' => url('/admin'), 'Satın Alma' => url('/admin/purchasing/dashboard'), 'Mal Kabul' => '#']) ?>
            <h2 class="mt-2 text-white font-weight-800 fs-3">Mal Kabul İstasyonu (Goods Receipt)</h2>
            <p class="text-muted mb-0 fs-7">WMS entegreli mal kabul terminali. Gelen sevk siparişlerinin barkod, Lot/Batch, son tüketim tarihi doğrulaması.</p>
        </div>
    </div>

    <!-- Alert Messaging -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 text-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- PO Selector list for incoming goods -->
        <div class="col-12 col-lg-4">
            <div class="card bg-dark border-secondary border-opacity-10 p-3 h-100">
                <h5 class="font-weight-800 text-white mb-3 fs-6">Gelen Sevk Siparişleri (Transit)</h5>
                <div class="d-flex flex-column gap-3">
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5 text-muted">Transit sevk siparişi bulunmamaktadır.</div>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <a href="<?= url('/admin/purchasing/receipts?po_id=' . $o['id']) ?>" class="p-3 bg-dark bg-opacity-50 border <?= $selectedPoId === (int)$o['id'] ? 'border-warning' : 'border-secondary border-opacity-25' ?> rounded-3 text-decoration-none text-white d-block hover-lift">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong><?= htmlspecialchars($o['po_number']) ?></strong>
                                    <span class="badge bg-info bg-opacity-10 text-info text-uppercase fs-9">SENT</span>
                                </div>
                                <div class="fs-8 text-muted">Tedarikçi: <?= htmlspecialchars($o['supplier_name']) ?></div>
                                <div class="fs-8 text-muted">Hedef Depo: <?= htmlspecialchars($o['warehouse_name']) ?></div>
                                <div class="text-end text-warning fs-9 mt-2 font-weight-700">Kabulü Başlat &rarr;</div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <hr class="border-secondary border-opacity-10 my-4">

                <h5 class="font-weight-800 text-white mb-3 fs-6">Son Yapılan Mal Kabuller</h5>
                <div class="d-flex flex-column gap-2 fs-8 text-muted">
                    <?php if (empty($receipts)): ?>
                        <div class="text-center py-3">Kabul kaydı bulunmuyor.</div>
                    <?php else: ?>
                        <?php foreach ($receipts as $gr): ?>
                            <div class="p-2 border border-secondary border-opacity-10 bg-dark bg-opacity-20 rounded">
                                <div class="d-flex justify-content-between mb-1">
                                    <strong class="text-white">GR-<?= $gr['id'] ?></strong>
                                    <span><?= date('d.m.Y H:i', strtotime($gr['created_at'])) ?></span>
                                </div>
                                <div>PO: <?= htmlspecialchars($gr['po_number']) ?> (<?= htmlspecialchars($gr['supplier_name']) ?>)</div>
                                <div class="text-success mt-1"><i class="bi bi-check-circle"></i> Toplam <?= $gr['total_qty'] ?> Adet Ürün Alındı</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Goods Receipt Form Entry area -->
        <div class="col-12 col-lg-8">
            <div class="card bg-dark border-secondary border-opacity-10 p-4 h-100">
                <?php if (!$poDetails): ?>
                    <div class="text-center py-5 text-muted my-auto">
                        <i class="bi bi-box-arrow-in-down fs-1 d-block mb-2"></i>
                        Mal kabul formunu doldurmak için sol listeden transit bir sipariş seçin.
                    </div>
                <?php else: ?>
                    <h5 class="font-weight-800 text-white mb-2 fs-6">Mal Kabul Girişi: <?= htmlspecialchars($poDetails['po_number']) ?></h5>
                    <p class="text-muted fs-8 mb-4">Depo: <strong><?= htmlspecialchars($poDetails['warehouse_name']) ?></strong> | Tedarikçi: <strong><?= htmlspecialchars($poDetails['supplier_name']) ?></strong></p>
                    
                    <form action="<?= url('/admin/purchasing/receipts/receive') ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="purchase_order_id" value="<?= $poDetails['id'] ?>">

                        <div class="table-responsive mb-4">
                            <table class="table table-dark table-hover border-secondary border-opacity-10 align-middle fs-8">
                                <thead>
                                    <tr>
                                        <th>Ürün Adı</th>
                                        <th>Sipariş</th>
                                        <th>Önceki Kabul</th>
                                        <th>Gelen Miktar</th>
                                        <th>Lot / Seri No</th>
                                        <th>Hasarlı / Eksik</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($poItems as $i => $item): ?>
                                        <tr>
                                            <td>
                                                <input type="hidden" name="items[<?= $i ?>][product_id]" value="<?= $item['product_id'] ?>">
                                                <input type="hidden" name="items[<?= $i ?>][variant_id]" value="<?= $item['variant_id'] ?>">
                                                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                                <div class="text-muted fs-9"><?= htmlspecialchars($item['product_sku']) ?></div>
                                            </td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td><?= $item['received_quantity'] ?></td>
                                            <td>
                                                <input type="number" name="items[<?= $i ?>][quantity]" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-white" value="<?= $item['quantity'] - $item['received_quantity'] ?>" max="<?= $item['quantity'] - $item['received_quantity'] ?>" min="0" style="width: 80px;">
                                            </td>
                                            <td>
                                                <input type="text" name="items[<?= $i ?>][lot_number]" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-white mb-1" placeholder="Lot No" style="width: 110px;">
                                                <input type="text" name="items[<?= $i ?>][serial_number]" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-white" placeholder="Seri No" style="width: 110px;">
                                            </td>
                                            <td>
                                                <input type="number" name="items[<?= $i ?>][damaged_quantity]" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-white mb-1" value="0" placeholder="Hasarlı" style="width: 80px;">
                                                <input type="number" name="items[<?= $i ?>][missing_quantity]" class="form-control form-control-sm bg-dark border-secondary border-opacity-25 text-white" value="0" placeholder="Eksik" style="width: 80px;">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Mal Kabul Notları</label>
                            <textarea name="notes" rows="2" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="Eksik teslimat, ezilmiş koliler vb. notlarınızı yazın..."></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-warning rounded-pill px-4 font-weight-600"><i class="bi bi-check-circle me-1"></i> Kabulü Onayla & Stokları Güncelle</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
