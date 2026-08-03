<?php
use App\Helpers\ComponentHelper;

$title = "Varyant Yönetimi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Ürün Varyantları' => url('/admin/variants')]) ?>
        <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Ürün Varyant Yönetimi</h2>
        <p class="text-muted mb-0 fs-6">Katalogda yer alan tüm ürün varyantlarını, barkodlarını, fiyatlarını ve stok düzeylerini toplu olarak yönetin.</p>
    </div>
    
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-secondary border-0 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-2"></i> Dışa Aktar
            </button>
            <ul class="dropdown-menu dropdown-menu-dark">
                <li><a class="dropdown-menu-item text-white p-2 d-block" href="<?= url('/admin/variants/export?format=csv') ?>"><i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV olarak İndir</a></li>
                <li><a class="dropdown-menu-item text-white p-2 d-block" href="<?= url('/admin/variants/export?format=excel') ?>"><i class="bi bi-file-earmark-excel me-2"></i>Excel (XML) olarak İndir</a></li>
            </ul>
        </div>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="p-3 rounded-4 mb-4" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
    <form method="GET" action="" class="row g-2">
        <div class="col-12 col-md-3">
            <div class="position-relative">
                <input type="text" name="q" class="search-input w-100" placeholder="SKU, barkod veya ürün ara..." value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
                <i class="bi bi-search position-absolute text-muted" style="right: 16px; top: 12px;"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select name="product_id" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" onchange="this.form.submit()">
                <option value="">Tüm Ürünler</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (int)($filters['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name'] ?? '') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="is_active" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" onchange="this.form.submit()">
                <option value="">Tüm Durumlar</option>
                <option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Aktifler</option>
                <option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Pasifler</option>
            </select>
        </div>
        <div class="col-12 col-md-4">
            <button type="submit" class="btn btn-secondary border-0 w-100 fs-7" style="padding: 10px 0;"><i class="bi bi-funnel me-2"></i>Filtrele</button>
        </div>
    </form>
</div>

<!-- Bulk Actions Panel (Shows when items checked) -->
<div id="bulkActionBar" class="d-none p-3 rounded-4 mb-4 d-flex justify-content-between align-items-center" style="background: rgba(197, 168, 128, 0.1); border: 1px solid rgba(197, 168, 128, 0.3);">
    <div class="d-flex align-items-center gap-2">
        <span class="text-white font-weight-600 fs-7"><span id="selectedCount">0</span> varyant seçildi</span>
    </div>
    <div class="d-flex gap-2">
        <form action="<?= url('/admin/variants/bulk') ?>" method="POST" id="bulkForm" class="d-flex align-items-center gap-2">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" id="bulkActionInput" value="">
            <div id="bulkIdsContainer"></div>

            <!-- Price update input (hidden by default) -->
            <div id="bulkPriceInputGroup" class="d-none me-2">
                <input type="number" step="0.01" name="price" class="search-input py-1" style="width: 120px;" placeholder="Yeni Fiyat" required disabled>
            </div>
            <!-- Stock update input (hidden by default) -->
            <div id="bulkStockInputGroup" class="d-none me-2">
                <input type="number" name="stock" class="search-input py-1" style="width: 120px;" placeholder="Yeni Stok" required disabled>
            </div>

            <select id="bulkActionSelect" class="form-select border-0 text-white fs-7 py-1" style="background: rgba(255,255,255,0.05); width: 220px;">
                <option value="">Toplu İşlem Seçin</option>
                <option value="activate">Aktif Hale Getir</option>
                <option value="deactivate">Pasif Hale Getir</option>
                <option value="update_price">Fiyat Güncelle</option>
                <option value="update_stock">Stok Güncelle</option>
                <option value="generate_sku">SKU Otomatik Oluştur</option>
                <option value="generate_barcode_ean13">EAN13 Barkod Oluştur</option>
                <option value="generate_barcode_ean8">EAN8 Barkod Oluştur</option>
                <option value="delete">Toplu Sil</option>
            </select>
            <button type="submit" class="btn btn-sm py-2 px-3">Uygula</button>
        </form>
    </div>
</div>

<!-- Table -->
<div class="table-responsive rounded-4" style="border: 1px solid var(--sm-border); background: rgba(255,255,255,0.01);">
    <table class="table table-hover align-middle mb-0 text-white">
        <thead class="text-muted" style="background: rgba(255,255,255,0.02);">
            <tr>
                <th style="padding: 16px; width: 40px;">
                    <input type="checkbox" id="checkAll" class="form-check-input">
                </th>
                <th style="padding: 16px; width: 60px;">Görsel</th>
                <th style="padding: 16px;">Ürün</th>
                <th style="padding: 16px;">SKU</th>
                <th style="padding: 16px;">Barkod</th>
                <th style="padding: 16px;">Fiyat (TRY)</th>
                <th style="padding: 16px;">Stok</th>
                <th style="padding: 16px;">Durum</th>
                <th style="padding: 16px; text-align: right;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($variants)): ?>
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        Kayıtlı varyant bulunamadı.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($variants as $v): ?>
                    <tr>
                        <td style="padding: 16px;">
                            <input type="checkbox" name="variant_ids[]" value="<?= $v['id'] ?>" class="form-check-input item-check">
                        </td>
                        <td style="padding: 16px;">
                            <?php if (!empty($v['cover_path'])): ?>
                                <img src="<?= url($v['cover_path']) ?>" class="rounded-3" style="width: 40px; height: 40px; object-fit: cover; border: 1px solid var(--sm-border);">
                            <?php else: ?>
                                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border);">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 16px;">
                            <span class="font-weight-600 d-block"><?= htmlspecialchars($v['product_name'] ?? '') ?></span>
                        </td>
                        <td style="padding: 16px;"><code><?= htmlspecialchars($v['sku'] ?? '') ?></code></td>
                        <td style="padding: 16px;" class="text-muted"><?= htmlspecialchars($v['barcode'] ?? '-') ?></td>
                        <td style="padding: 16px;" class="font-weight-600 text-warning"><?= number_format((float)$v['price'], 2) ?></td>
                        <td style="padding: 16px;">
                            <?php if ((int)$v['total_stock'] <= 5): ?>
                                <span class="text-danger font-weight-600"><i class="bi bi-exclamation-triangle me-1"></i><?= (int)$v['total_stock'] ?></span>
                            <?php else: ?>
                                <span class="text-success font-weight-600"><?= (int)$v['total_stock'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 16px;">
                            <?php if ((int)$v['is_active'] === 1): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border-0">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border-0">Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 16px; text-align: right;">
                            <div class="d-inline-flex gap-2">
                                <a href="<?= url('/admin/variants/edit?id=' . $v['id']) ?>" class="btn btn-sm btn-secondary border-0 p-2" title="Düzenle">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="<?= url('/admin/variants/delete') ?>" method="POST" onsubmit="return confirm('Bu varyantı silmek istediğinize emin misiniz?');" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger bg-opacity-10 border-0 p-2" title="Sil">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    const itemChecks = document.querySelectorAll('.item-check');
    const bulkActionBar = document.getElementById('bulkActionBar');
    const selectedCount = document.getElementById('selectedCount');
    const bulkActionSelect = document.getElementById('bulkActionSelect');
    const bulkActionInput = document.getElementById('bulkActionInput');
    const bulkIdsContainer = document.getElementById('bulkIdsContainer');
    const bulkForm = document.getElementById('bulkForm');

    const bulkPriceInputGroup = document.getElementById('bulkPriceInputGroup');
    const bulkStockInputGroup = document.getElementById('bulkStockInputGroup');
    const priceInput = bulkPriceInputGroup.querySelector('input');
    const stockInput = bulkStockInputGroup.querySelector('input');

    function toggleBulkBar() {
        const checked = document.querySelectorAll('.item-check:checked');
        selectedCount.textContent = checked.length;
        if (checked.length > 0) {
            bulkActionBar.classList.remove('d-none');
        } else {
            bulkActionBar.classList.add('d-none');
        }
    }

    checkAll.addEventListener('change', function() {
        itemChecks.forEach(function(check) {
            check.checked = checkAll.checked;
        });
        toggleBulkBar();
    });

    itemChecks.forEach(function(check) {
        check.addEventListener('change', toggleBulkBar);
    });

    bulkActionSelect.addEventListener('change', function() {
        const val = this.value;
        bulkActionInput.value = val;

        // Reset extra inputs
        bulkPriceInputGroup.classList.add('d-none');
        priceInput.disabled = true;
        bulkStockInputGroup.classList.add('d-none');
        stockInput.disabled = true;

        if (val === 'update_price') {
            bulkPriceInputGroup.classList.remove('d-none');
            priceInput.disabled = false;
        } else if (val === 'update_stock') {
            bulkStockInputGroup.classList.remove('d-none');
            stockInput.disabled = false;
        }
    });

    bulkForm.addEventListener('submit', function(e) {
        if (!bulkActionSelect.value) {
            alert('Lütfen yapılacak toplu işlemi seçin.');
            e.preventDefault();
            return;
        }

        if (bulkActionSelect.value === 'delete' && !confirm('Seçilen varyantları silmek istediğinize emin misiniz?')) {
            e.preventDefault();
            return;
        }

        // Generate hidden input ids
        bulkIdsContainer.innerHTML = '';
        const checked = document.querySelectorAll('.item-check:checked');
        checked.forEach(function(c) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = c.value;
            bulkIdsContainer.appendChild(input);
        });
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
