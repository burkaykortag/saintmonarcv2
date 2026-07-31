<?php
use App\Helpers\ComponentHelper;

$title = "Kampanya Simülatörü - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Kampanyalar' => url('/admin/promotions'), 'Simülatör' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Kampanya Önizleme & Sepet Simülasyonu</h2>
        <a href="<?= url('/admin/promotions') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Kampanya Listesi</a>
    </div>
</div>

<div class="row g-4 text-white">
    <!-- SOL KOLON: SEPET VE MÜŞTERİ SEÇİMİ -->
    <div class="col-lg-6">
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-cart3 text-warning me-2"></i>Sepet Simülatörü</h4>
            
            <div class="row g-2 mb-3">
                <div class="col-md-6 col-sm-12">
                    <label class="form-label text-muted fs-8 mb-1">Ürün Seçin</label>
                    <select id="prodSelect" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.05); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>" data-sku="<?= htmlspecialchars($p['sku']) ?>" data-price="<?= $p['price'] ?>"><?= htmlspecialchars($p['sku']) ?> (<?= number_format((float)$p['price'], 2) ?> TRY)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label text-muted fs-8 mb-1">Adet</label>
                    <input type="number" id="prodQty" class="search-input w-100 text-white" value="1" min="1">
                </div>
                <div class="col-md-3 col-6 d-flex align-items-end">
                    <button type="button" onclick="addToSimCart()" class="btn btn-warning text-dark border-0 w-100 py-2 fs-7 font-weight-700">Sepete Ekle</button>
                </div>
            </div>

            <!-- Simüle Sepet Tablosu -->
            <div class="table-responsive mb-4">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Ürün (SKU)</th>
                            <th>Fiyat</th>
                            <th width="80">Adet</th>
                            <th width="50" class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="cartTableBody">
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Simüle sepet boş. Ürün ekleyin.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Müşteri & Kupon Simulasyonu -->
            <hr class="border-secondary opacity-25 mb-4">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-person-fill text-warning me-2"></i>Müşteri & Kupon Simülasyonu</h4>
            
            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Test Müşterisi Seçin</label>
                <select id="custSelect" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.05); padding: 10px; border: 1px solid var(--sm-border) !important;">
                    <option value="">Ziyaretçi (Giriş Yapmamış)</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?> (Grubu: <?= htmlspecialchars($c['group_name'] ?? 'Perakende') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted fs-7 mb-1">Kupon Kodu Girin</label>
                <input type="text" id="couponCode" class="search-input w-100 text-white" placeholder="örn: SAVE10">
            </div>

            <button type="button" onclick="runSimulate()" class="btn btn-warning text-dark border-0 w-100 py-3 font-weight-700 mt-2">Simülasyonu Hesapla</button>
        </div>
    </div>

    <!-- SAĞ KOLON: KAMPANYA SONUÇLARI -->
    <div class="col-lg-6">
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;" id="resultBox">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-calculator text-warning me-2"></i>Hesaplama Sonuçları</h4>
            
            <div class="p-4 rounded-3 text-center mb-3" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
                <div class="text-muted fs-7">Ödenecek Toplam Tutar</div>
                <h2 class="font-weight-700 text-warning mt-2 mb-0" id="resFinalTotal">0.00 TRY</h2>
                <div class="text-muted fs-8 mt-2" id="resShippingText">Kargo Bedava: Hayır</div>
            </div>

            <div class="fs-7">
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-10">
                    <span class="text-muted">Ara Toplam:</span>
                    <span class="text-white" id="resSubTotal">0.00 TRY</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-10">
                    <span class="text-muted">Toplam İndirim:</span>
                    <span class="text-danger font-weight-600" id="resDiscount">0.00 TRY</span>
                </div>
                
                <h5 class="text-white font-weight-600 mt-4 mb-2 fs-7">Uygulanan Kampanyalar</h5>
                <ul class="list-group list-group-flush" id="resAppliedList">
                    <li class="list-group-item bg-transparent text-muted px-0 border-0">Henüz uygulanan kampanya bulunmuyor.</li>
                </ul>

                <h5 class="text-white font-weight-600 mt-4 mb-2 fs-7">Kazanılan Hediyeler</h5>
                <ul class="list-group list-group-flush" id="resGiftsList">
                    <li class="list-group-item bg-transparent text-muted px-0 border-0">Kazanılan hediye bulunmuyor.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
var simCart = [];

function addToSimCart() {
    var sel = document.getElementById('prodSelect');
    var opt = sel.options[sel.selectedIndex];
    var id = parseInt(opt.value);
    var sku = opt.getAttribute('data-sku');
    var price = parseFloat(opt.getAttribute('data-price'));
    var qty = parseInt(document.getElementById('prodQty').value);

    // Eğer zaten varsa adedini artır
    var found = false;
    for (var i = 0; i < simCart.length; i++) {
        if (simCart[i].product_id === id) {
            simCart[i].quantity += qty;
            found = true;
            break;
        }
    }
    if (!found) {
        simCart.push({
            product_id: id,
            sku: sku,
            price: price,
            quantity: qty
        });
    }

    renderSimCart();
}

function removeFromSimCart(idx) {
    simCart.splice(idx, 1);
    renderSimCart();
}

function renderSimCart() {
    var tbody = document.getElementById('cartTableBody');
    if (simCart.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Simüle sepet boş. Ürün ekleyin.</td></tr>';
        return;
    }

    var html = '';
    for (var i = 0; i < simCart.length; i++) {
        var item = simCart[i];
        html += '<tr class="border-bottom border-secondary border-opacity-10">' +
                '<td><strong>' + item.sku + '</strong></td>' +
                '<td>' + (item.price * item.quantity).toFixed(2) + ' TRY</td>' +
                '<td>' + item.quantity + ' adet</td>' +
                '<td class="text-end"><button type="button" onclick="removeFromSimCart(' + i + ')" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash"></i></button></td>' +
                '</tr>';
    }
    tbody.innerHTML = html;
}

function runSimulate() {
    if (simCart.length === 0) {
        alert('Lütfen sepet simülasyonuna ürün ekleyin.');
        return;
    }

    var custId = document.getElementById('custSelect').value;
    var coupon = document.getElementById('couponCode').value;

    var payload = {
        cart: simCart,
        customer_id: custId,
        coupon_code: coupon
    };

    fetch('<?= url('/api/promotions/preview') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            var res = data.data;
            document.getElementById('resFinalTotal').innerText = res.final_total.toFixed(2) + ' TRY';
            document.getElementById('resSubTotal').innerText = res.original_total.toFixed(2) + ' TRY';
            document.getElementById('resDiscount').innerText = '-' + res.discount_amount.toFixed(2) + ' TRY';
            document.getElementById('resShippingText').innerText = 'Kargo Bedava: ' + (res.free_shipping ? 'Evet (Kampanyalı)' : 'Hayır');

            // Kampanyaları listele
            var promoList = document.getElementById('resAppliedList');
            if (res.applied_promotions.length === 0) {
                promoList.innerHTML = '<li class="list-group-item bg-transparent text-muted px-0 border-0">Uygulanan kampanya bulunmuyor.</li>';
            } else {
                var html = '';
                res.applied_promotions.forEach(p => {
                    html += '<li class="list-group-item bg-transparent text-white px-0 border-bottom border-secondary border-opacity-10 d-flex justify-content-between">' +
                            '<span><i class="bi bi-check-circle text-success me-2"></i>' + p.name + '</span>' +
                            '<span class="text-danger">-' + p.discount.toFixed(2) + ' TRY</span>' +
                            '</li>';
                });
                promoList.innerHTML = html;
            }

            // Hediyeleri listele
            var giftList = document.getElementById('resGiftsList');
            if (res.gifts.length === 0) {
                giftList.innerHTML = '<li class="list-group-item bg-transparent text-muted px-0 border-0">Kazanılan hediye bulunmuyor.</li>';
            } else {
                var html = '';
                res.gifts.forEach(g => {
                    var detail = g.gift_type === 'points' ? g.points + ' Sadakat Puanı' : 'Ürün Hediye';
                    html += '<li class="list-group-item bg-transparent text-white px-0 border-0">' +
                            '<span><i class="bi bi-gift text-warning me-2"></i>' + detail + '</span>' +
                            '</li>';
                });
                giftList.innerHTML = html;
            }
        } else {
            alert('Simülasyon hesaplanamadı: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Simülasyon sırasında hata oluştu.');
    });
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
