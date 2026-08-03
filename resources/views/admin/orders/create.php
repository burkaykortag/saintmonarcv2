<?php
use App\Helpers\ComponentHelper;

$title = "Manuel Sipariş Oluştur - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Siparişler' => url('/admin/orders'),
        'Yeni Sipariş' => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Yeni Sipariş Oluştur</h2>
        <a href="<?= url('/admin/orders') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Listeye Dön</a>
    </div>
</div>

<form action="<?= url('/admin/orders/create') ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <div class="row g-4">
        <!-- SOL KOLON: KALEMLER, MÜŞTERİ SEÇİMİ, ADRESLER -->
        <div class="col-lg-8">
            <!-- 1. Müşteri Seçimi -->
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-person-plus-fill me-2 text-warning"></i>Müşteri Seçimi</h4>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Müşteri Seçin (Kayıtlı)</label>
                    <select name="user_id" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- 2. Sipariş Kalemleri Girişi -->
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-basket2-fill me-2 text-warning"></i>Ürün Ekle</h4>
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
                            <tr>
                                <td>
                                    <select name="product_ids[]" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name'] . ' - ' . $p['price'] . ' TRY') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="quantities[]" value="1" min="1" class="search-input w-100 text-white text-center">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-warning mt-2" onclick="addRow()"><i class="bi bi-plus-circle me-1"></i>Yeni Satır Ekle</button>
            </div>

            <!-- 3. Adres Bilgileri -->
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-geo-alt-fill me-2 text-warning"></i>Adres Bilgileri</h4>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Fatura Alıcı Adı</label>
                        <input type="text" name="billing_first_name" required class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Fatura Alıcı Soyadı</label>
                        <input type="text" name="billing_last_name" required class="search-input w-100 text-white">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Fatura Adresi</label>
                    <textarea name="billing_address" required class="search-input w-100 text-white" rows="2"></textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Şehir</label>
                        <input type="text" name="billing_city" required class="search-input w-100 text-white" value="İstanbul">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Ülke</label>
                        <input type="text" name="billing_country" required class="search-input w-100 text-white" value="Türkiye">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 mb-1">Posta Kodu</label>
                        <input type="text" name="billing_zip" required class="search-input w-100 text-white">
                    </div>
                </div>

                <div class="form-check form-switch fs-7 mb-3 mt-4 text-muted">
                    <input class="form-check-input" type="checkbox" id="sameAddressCheck" checked onclick="toggleShippingAddress()">
                    <label class="form-check-label" for="sameAddressCheck">Teslimat adresi fatura adresi ile aynı olsun</label>
                </div>

                <div id="shippingAddressBox" style="display:none;">
                    <hr class="border-secondary opacity-25">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted fs-7 mb-1">Teslimat Alıcı Adı</label>
                            <input type="text" name="shipping_first_name" class="search-input w-100 text-white">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fs-7 mb-1">Teslimat Alıcı Soyadı</label>
                            <input type="text" name="shipping_last_name" class="search-input w-100 text-white">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fs-7 mb-1">Teslimat Adresi</label>
                        <textarea name="shipping_address" class="search-input w-100 text-white" rows="2"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted fs-7 mb-1">Şehir</label>
                            <input type="text" name="shipping_city" class="search-input w-100 text-white" value="İstanbul">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted fs-7 mb-1">Ülke</label>
                            <input type="text" name="shipping_country" class="search-input w-100 text-white" value="Türkiye">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted fs-7 mb-1">Posta Kodu</label>
                            <input type="text" name="shipping_zip" class="search-input w-100 text-white">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SAĞ KOLON: METOTLAR, EK TUTARLAR VE GÖNDER -->
        <div class="col-lg-4">
            <!-- 1. Yöntemler -->
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6">Kargo & Ödeme Yöntemi</h4>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Kargo Firması</label>
                    <select name="shipping_method_id" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <?php foreach ($shippingMethods as $sm): ?>
                            <option value="<?= $sm['id'] ?>">Kargo #<?= $sm['id'] ?> (<?= number_format($sm['price'],2,',','.') ?> ₺)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Ödeme Yöntemi</label>
                    <select name="payment_method_id" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <?php foreach ($paymentMethods as $pm): ?>
                            <option value="<?= $pm['id'] ?>"><?= htmlspecialchars($pm['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- 2. Ekstra Maliyetler -->
            <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6">Maliyet & İndirimler</h4>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">İndirim Tutarı (TRY)</label>
                    <input type="number" step="0.01" name="discount_total" value="0.00" class="search-input w-100 text-white">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Kargo Ücreti (TRY)</label>
                    <input type="number" step="0.01" name="shipping_total" value="0.00" class="search-input w-100 text-white">
                </div>
            </div>

            <!-- 3. Gönder Butonu -->
            <div class="card p-4 border-0 mb-4 text-center" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <button type="submit" class="btn btn-warning text-dark border-0 fs-6 w-100 py-3 font-weight-700">Siparişi Tamamla</button>
                <a href="<?= url('/admin/orders') ?>" class="btn btn-outline-secondary w-100 mt-2">Vazgeç</a>
            </div>
        </div>
    </div>
</form>

<script>
function addRow() {
    var table = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
    var firstRow = table.rows[0];
    var newRow = firstRow.cloneNode(true);
    
    // reset inputs
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

function toggleShippingAddress() {
    var check = document.getElementById('sameAddressCheck');
    var box = document.getElementById('shippingAddressBox');
    
    // Alıcı inputlarını bul
    var sfn = document.getElementsByName('shipping_first_name')[0];
    var sln = document.getElementsByName('shipping_last_name')[0];
    var saddr = document.getElementsByName('shipping_address')[0];
    var scity = document.getElementsByName('shipping_city')[0];
    var scountry = document.getElementsByName('shipping_country')[0];
    var szip = document.getElementsByName('shipping_zip')[0];

    if (check.checked) {
        box.style.display = 'none';
        sfn.required = false;
        sln.required = false;
        saddr.required = false;
    } else {
        box.style.display = 'block';
        sfn.required = true;
        sln.required = true;
        saddr.required = true;
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
