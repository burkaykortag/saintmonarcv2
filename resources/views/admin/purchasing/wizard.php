<?php
use App\Helpers\ComponentHelper;
$title = "Satın Alma Siparişi Sihirbazı | SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="container-fluid py-4 text-white">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim' => url('/admin'), 'Satın Alma' => url('/admin/purchasing/dashboard'), 'Yeni Sipariş Sihirbazı' => '#']) ?>
            <h2 class="mt-2 text-white font-weight-800 fs-3">Yeni Satın Alma Siparişi Sihirbazı</h2>
            <p class="text-muted mb-0 fs-7">SAP benzeri 5 adımlı sipariş oluşturma ve tedarik onay sihirbazı.</p>
        </div>
    </div>

    <!-- Wizard Stepper Indicator -->
    <div class="card bg-dark border-secondary border-opacity-10 p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center position-relative px-5">
            <div class="position-absolute bg-secondary bg-opacity-25" style="height: 2px; top: 50%; left: 10%; right: 10%; z-index: 1;"></div>
            
            <div class="step-indicator active text-center position-relative" style="z-index: 2;" id="step-ind-1">
                <span class="rounded-circle bg-warning text-dark font-weight-800 d-inline-block text-center" style="width: 32px; height: 32px; line-height: 32px;">1</span>
                <small class="d-block text-white mt-1 fs-9 font-weight-700">Supplier</small>
            </div>
            <div class="step-indicator text-center position-relative" style="z-index: 2;" id="step-ind-2">
                <span class="rounded-circle bg-secondary text-white font-weight-800 d-inline-block text-center" style="width: 32px; height: 32px; line-height: 32px;" id="step-dot-2">2</span>
                <small class="d-block text-muted mt-1 fs-9 font-weight-700">Products</small>
            </div>
            <div class="step-indicator text-center position-relative" style="z-index: 2;" id="step-ind-3">
                <span class="rounded-circle bg-secondary text-white font-weight-800 d-inline-block text-center" style="width: 32px; height: 32px; line-height: 32px;" id="step-dot-3">3</span>
                <small class="d-block text-muted mt-1 fs-9 font-weight-700">Warehouse</small>
            </div>
            <div class="step-indicator text-center position-relative" style="z-index: 2;" id="step-ind-4">
                <span class="rounded-circle bg-secondary text-white font-weight-800 d-inline-block text-center" style="width: 32px; height: 32px; line-height: 32px;" id="step-dot-4">4</span>
                <small class="d-block text-muted mt-1 fs-9 font-weight-700">Payment</small>
            </div>
            <div class="step-indicator text-center position-relative" style="z-index: 2;" id="step-ind-5">
                <span class="rounded-circle bg-secondary text-white font-weight-800 d-inline-block text-center" style="width: 32px; height: 32px; line-height: 32px;" id="step-dot-5">5</span>
                <small class="d-block text-muted mt-1 fs-9 font-weight-700">Approval</small>
            </div>
        </div>
    </div>

    <!-- Wizard Form Form -->
    <form action="<?= url('/admin/purchasing/orders/create') ?>" method="POST" id="wizardForm">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <!-- Step 1: Supplier selection -->
        <div class="card bg-dark border-secondary border-opacity-10 p-4 step-pane" id="step-pane-1">
            <h5 class="font-weight-800 mb-3 text-white">Adım 1: Tedarikçi Seçimi</h5>
            <p class="text-muted fs-8 mb-4">Sipariş verilecek tedarikçiyi seçiniz ve default kurallarını doğrulayınız.</p>
            <div class="row g-3 fs-8">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Tedarikçi Seçin *</label>
                    <select name="supplier_id" id="supplierSelect" class="form-select bg-dark border-secondary border-opacity-25 text-white" required onchange="updateSupplierDefaults()">
                        <option value="">-- Tedarikçi Seçin --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>" data-currency="<?= htmlspecialchars($s['currency']) ?>" data-payment="<?= htmlspecialchars($s['payment_terms'] ?? '') ?>">
                                <?= htmlspecialchars($s['company_name']) ?> (Puan: <?= number_format((float)$s['score'], 1) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Varsayılan Para Birimi</label>
                    <input type="text" id="supplierCurrency" class="form-control bg-dark border-secondary border-opacity-25 text-white text-uppercase" readonly>
                </div>
            </div>
        </div>

        <!-- Step 2: Products and items -->
        <div class="card bg-dark border-secondary border-opacity-10 p-4 step-pane d-none" id="step-pane-2">
            <h5 class="font-weight-800 mb-3 text-white">Adım 2: Ürün Kalemleri Ekleme</h5>
            <p class="text-muted fs-8 mb-4">Satın alınacak ürünleri ve miktarlarını seçin.</p>
            
            <div class="table-responsive mb-3">
                <table class="table table-dark table-hover align-middle border-secondary border-opacity-10 fs-8" id="itemsTable">
                    <thead>
                        <tr>
                            <th>Ürün Seçimi</th>
                            <th>Miktar</th>
                            <th>Birim Fiyat (Alış)</th>
                            <th>KDV Oranı (%)</th>
                            <th>İskonto Tutarı (Birim)</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="itemsContainer">
                        <tr>
                            <td>
                                <select name="items[0][product_id]" class="form-select bg-dark border-secondary border-opacity-25 text-white" required>
                                    <option value="">-- Ürün Seçin --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (SKU: <?= htmlspecialchars($p['sku']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" name="items[0][quantity]" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="1" min="1" required></td>
                            <td><input type="number" step="0.01" name="items[0][price]" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="100.00" min="0.01" required></td>
                            <td>
                                <select name="items[0][tax_rate]" class="form-select bg-dark border-secondary border-opacity-25 text-white">
                                    <option value="20.00">20%</option>
                                    <option value="10.00">10%</option>
                                    <option value="8.00">8%</option>
                                    <option value="1.00">1%</option>
                                    <option value="0.00">0%</option>
                                </select>
                            </td>
                            <td><input type="number" step="0.01" name="items[0][discount_amount]" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="0.00"></td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-warning" onclick="addItemRow()"><i class="bi bi-plus-circle me-1"></i> Kalem Ekle</button>
        </div>

        <!-- Step 3: Warehouse selection -->
        <div class="card bg-dark border-secondary border-opacity-10 p-4 step-pane d-none" id="step-pane-3">
            <h5 class="font-weight-800 mb-3 text-white">Adım 3: Teslimat Deposu Seçimi</h5>
            <p class="text-muted fs-8 mb-4">Mal kabulün yapılacağı hedef depoyu ve beklenen teslim alma tarihini giriniz.</p>
            <div class="row g-3 fs-8">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Hedef Depo (WMS) *</label>
                    <select name="warehouse_id" class="form-select bg-dark border-secondary border-opacity-25 text-white" required>
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?> (Kod: <?= htmlspecialchars($w['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Beklenen Teslim Tarihi</label>
                    <input type="date" name="expected_delivery" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                </div>
            </div>
        </div>

        <!-- Step 4: Payment terms -->
        <div class="card bg-dark border-secondary border-opacity-10 p-4 step-pane d-none" id="step-pane-4">
            <h5 class="font-weight-800 mb-3 text-white">Adım 4: Finansal & Ödeme Şartları</h5>
            <p class="text-muted fs-8 mb-4">Cari bakiye ve ödeme vadesi detayları.</p>
            <div class="row g-3 fs-8">
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Para Birimi</label>
                    <select name="currency" id="orderCurrency" class="form-select bg-dark border-secondary border-opacity-25 text-white">
                        <option value="TRY">TRY (₺)</option>
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Ödeme Koşulu (Vade)</label>
                    <input type="text" id="orderPaymentTerms" name="payment_terms" class="form-control bg-dark border-secondary border-opacity-25 text-white">
                </div>
            </div>
        </div>

        <!-- Step 5: Approval workflow -->
        <div class="card bg-dark border-secondary border-opacity-10 p-4 step-pane d-none" id="step-pane-5">
            <h5 class="font-weight-800 mb-3 text-white">Adım 5: Doğrulama ve Onay Akışı</h5>
            <p class="text-muted fs-8 mb-4">Lütfen girdiğiniz sipariş detaylarını doğrulayın ve kaydı başlatın.</p>
            <div class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25 text-warning fs-8">
                <strong>Bilgi:</strong> Sipariş oluşturulduğunda ilk olarak <code>DRAFT (Taslak)</code> durumunda kaydedilir. Sipariş listesinden onay süreci başlatılmalıdır.
            </div>
        </div>

        <!-- Footer Control Buttons -->
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="prevBtn" onclick="nextPrev(-1)" disabled>Geri</button>
            <button type="button" class="btn btn-warning rounded-pill px-4 font-weight-600" id="nextBtn" onclick="nextPrev(1)">İleri</button>
        </div>
    </form>
</div>

<script>
let currentStep = 1;
const totalSteps = 5;
let itemIndex = 1;

function updateSupplierDefaults() {
    const sel = document.getElementById('supplierSelect');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value !== '') {
        const currency = opt.getAttribute('data-currency');
        const payment = opt.getAttribute('data-payment');
        
        document.getElementById('supplierCurrency').value = currency;
        document.getElementById('orderCurrency').value = currency;
        document.getElementById('orderPaymentTerms').value = payment;
    }
}

function nextPrev(n) {
    if (n === 1 && currentStep === 1) {
        if (document.getElementById('supplierSelect').value === '') {
            alert('Lütfen tedarikçi seçiniz.');
            return;
        }
    }

    // Hide current pane
    document.getElementById('step-pane-' + currentStep).classList.add('d-none');
    
    // Change dot style
    if (n === 1) {
        document.getElementById('step-dot-' + (currentStep + 1))?.classList.replace('bg-secondary', 'bg-warning');
        document.getElementById('step-dot-' + (currentStep + 1))?.classList.replace('text-white', 'text-dark');
    } else {
        document.getElementById('step-dot-' + currentStep)?.classList.replace('bg-warning', 'bg-secondary');
        document.getElementById('step-dot-' + currentStep)?.classList.replace('text-dark', 'text-white');
    }

    currentStep += n;

    // Show new pane
    document.getElementById('step-pane-' + currentStep).classList.remove('d-none');

    // Controls buttons status
    document.getElementById('prevBtn').disabled = (currentStep === 1);
    
    if (currentStep === totalSteps) {
        document.getElementById('nextBtn').innerText = 'Siparişi Kaydet & Bitir';
        document.getElementById('nextBtn').classList.replace('btn-warning', 'btn-success');
    } else {
        document.getElementById('nextBtn').innerText = 'İleri';
        document.getElementById('nextBtn').classList.replace('btn-success', 'btn-warning');
    }

    if (currentStep > totalSteps) {
        document.getElementById('wizardForm').submit();
    }
}

function addItemRow() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('tr');
    
    newRow.innerHTML = `
        <td>
            <select name="items[\${itemIndex}][product_id]" class="form-select bg-dark border-secondary border-opacity-25 text-white" required>
                <option value="">-- Ürün Seçin --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (SKU: <?= htmlspecialchars($p['sku']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" name="items[\${itemIndex}][quantity]" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="1" min="1" required></td>
        <td><input type="number" step="0.01" name="items[\${itemIndex}][price]" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="100.00" min="0.01" required></td>
        <td>
            <select name="items[\${itemIndex}][tax_rate]" class="form-select bg-dark border-secondary border-opacity-25 text-white">
                <option value="20.00">20%</option>
                <option value="10.00">10%</option>
                <option value="8.00">8%</option>
                <option value="1.00">1%</option>
                <option value="0.00">0%</option>
            </select>
        </td>
        <td><input type="number" step="0.01" name="items[\${itemIndex}][discount_amount]" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="0.00"></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)"><i class="bi bi-trash"></i></button></td>
    `;
    
    container.appendChild(newRow);
    itemIndex++;
}

function removeItemRow(btn) {
    const row = btn.closest('tr');
    if (document.querySelectorAll('#itemsContainer tr').length > 1) {
        row.remove();
    } else {
        alert('En az bir sipariş kalemi eklemek zorunludur.');
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
