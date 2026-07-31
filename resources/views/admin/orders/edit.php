<?php
use App\Helpers\ComponentHelper;

$title = "Siparişi Düzenle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Siparişler' => url('/admin/orders'),
        'Sipariş Detayı' => url('/admin/orders/show?id=' . $order['id']),
        'Siparişi Düzenle' => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Siparişi Düzenle: <?= htmlspecialchars($order['order_number']) ?></h2>
        <a href="<?= url('/admin/orders/show?id=' . $order['id']) ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Siparişe Geri Dön</a>
    </div>
</div>

<form action="<?= url('/admin/orders/edit') ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" value="<?= $order['id'] ?>">

    <div class="row g-4">
        <!-- SOL KOLON: KALEMLER, ADRESLER -->
        <div class="col-lg-8">
            <!-- 1. Kalemleri Düzenle -->
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-basket2-fill me-2 text-warning"></i>Sipariş Kalemlerini Düzenle</h4>
                <div class="table-responsive">
                    <table class="table table-borderless align-middle text-white" id="itemsTable">
                        <thead>
                            <tr class="text-muted fs-7 border-bottom border-secondary">
                                <th>Ürün Seçin</th>
                                <th width="120">Adet</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <select name="product_ids[]" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                                            <?php foreach ($products as $p): ?>
                                                <option value="<?= $p['id'] ?>" <?= $p['id'] === $item['product_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name'] . ' - ' . $p['price'] . ' TRY') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="quantities[]" value="<?= $item['quantity'] ?>" min="1" class="search-input w-100 text-white text-center">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-warning mt-2" onclick="addRow()"><i class="bi bi-plus-circle me-1"></i>Yeni Satır Ekle</button>
            </div>

            <!-- 2. Adres Bilgileri -->
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-geo-alt-fill me-2 text-warning"></i>Adres Bilgileri</h4>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Fatura Alıcı Adı</label>
                        <input type="text" name="billing_first_name" required value="<?= htmlspecialchars($order['billing_first_name']) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Fatura Alıcı Soyadı</label>
                        <input type="text" name="billing_last_name" required value="<?= htmlspecialchars($order['billing_last_name']) ?>" class="search-input w-100 text-white">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Fatura Adresi</label>
                    <textarea name="billing_address" required class="search-input w-100 text-white" rows="2"><?= htmlspecialchars($order['billing_address']) ?></textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Şehir</label>
                        <input type="text" name="billing_city" required value="<?= htmlspecialchars($order['billing_city']) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Ülke</label>
                        <input type="text" name="billing_country" required value="<?= htmlspecialchars($order['billing_country']) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Posta Kodu</label>
                        <input type="text" name="billing_zip" required value="<?= htmlspecialchars($order['billing_zip']) ?>" class="search-input w-100 text-white">
                    </div>
                </div>

                <hr class="border-secondary opacity-25">
                
                <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-truck me-2 text-warning"></i>Teslimat Bilgileri</h4>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Teslimat Alıcı Adı</label>
                        <input type="text" name="shipping_first_name" required value="<?= htmlspecialchars($order['shipping_first_name']) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Teslimat Alıcı Soyadı</label>
                        <input type="text" name="shipping_last_name" required value="<?= htmlspecialchars($order['shipping_last_name']) ?>" class="search-input w-100 text-white">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Teslimat Adresi</label>
                    <textarea name="shipping_address" required class="search-input w-100 text-white" rows="2"><?= htmlspecialchars($order['shipping_address']) ?></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Şehir</label>
                        <input type="text" name="shipping_city" required value="<?= htmlspecialchars($order['shipping_city']) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Ülke</label>
                        <input type="text" name="shipping_country" required value="<?= htmlspecialchars($order['shipping_country']) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Posta Kodu</label>
                        <input type="text" name="shipping_zip" required value="<?= htmlspecialchars($order['shipping_zip']) ?>" class="search-input w-100 text-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- SAĞ KOLON: METOTLAR, DURUM, EK TUTARLAR VE GÖNDER -->
        <div class="col-lg-4">
            <!-- 1. Yöntemler -->
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6">Sipariş Durumu</h4>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Durum</label>
                    <select name="status" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Yeni Sipariş</option>
                        <option value="pending_payment" <?= $order['status'] === 'pending_payment' ? 'selected' : '' ?>>Ödeme Bekliyor</option>
                        <option value="payment_approved" <?= $order['status'] === 'payment_approved' ? 'selected' : '' ?>>Ödeme Onaylandı</option>
                        <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>Hazırlanıyor</option>
                        <option value="packing" <?= $order['status'] === 'packing' ? 'selected' : '' ?>>Paketleniyor</option>
                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Kargoya Verildi</option>
                        <option value="out_for_delivery" <?= $order['status'] === 'out_for_delivery' ? 'selected' : '' ?>>Dağıtımda</option>
                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Teslim Edildi</option>
                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>İptal</option>
                        <option value="refund_pending" <?= $order['status'] === 'refund_pending' ? 'selected' : '' ?>>İade Bekliyor</option>
                        <option value="refund_approved" <?= $order['status'] === 'refund_approved' ? 'selected' : '' ?>>İade Onaylandı</option>
                        <option value="refunded" <?= $order['status'] === 'refunded' ? 'selected' : '' ?>>İade Edildi</option>
                        <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                    </select>
                </div>
            </div>

            <!-- 2. Gönder Butonları -->
            <div class="card p-4 border-0 mb-4 text-center" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <button type="submit" class="btn btn-warning text-dark border-0 fs-6 w-100 py-3 font-weight-700">Değişiklikleri Kaydet</button>
                <a href="<?= url('/admin/orders/show?id=' . $order['id']) ?>" class="btn btn-outline-secondary w-100 mt-2">İptal</a>
            </div>
        </div>
    </div>
</form>

<script>
function addRow() {
    var table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
    var firstRow = table.rows[0];
    var newRow = firstRow.cloneNode(true);
    newRow.querySelector('input').value = 1;
    table.appendChild(newRow);
}

function removeRow(btn) {
    var table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
    if (table.rows.length > 1) {
        var row = btn.parentNode.parentNode;
        row.parentNode.removeChild(row);
    } else {
        alert('En az 1 adet ürün eklemelisiniz.');
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
