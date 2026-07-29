<?php
use App\Helpers\ComponentHelper;

$title = "Marka Yönetimi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Markalar' => url('/admin/brands')]) ?>
        <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Marka Yönetimi</h2>
        <p class="text-muted mb-0 fs-6">Sistemde yer alan tüm markaları, logo görsellerini, satış adetlerini ve gelir raporlarını yönetin.</p>
    </div>
    
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/brands/export') ?>" class="btn btn-secondary border-0">
            <i class="bi bi-file-earmark-excel me-2"></i> CSV Dışa Aktar
        </a>
        <a href="<?= url('/admin/brands/create') ?>" class="btn">
            <i class="bi bi-plus-circle me-2"></i> Yeni Marka Ekle
        </a>
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
                <input type="text" name="q" class="search-input w-100" placeholder="Marka veya slug ara..." value="<?= htmlspecialchars($q ?? '') ?>">
                <i class="bi bi-search position-absolute text-muted" style="right: 16px; top: 12px;"></i>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <select name="is_active" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" onchange="this.form.submit()">
                <option value="">Tüm Durumlar</option>
                <option value="1" <?= $is_active === '1' ? 'selected' : '' ?>>Aktifler</option>
                <option value="0" <?= $is_active === '0' ? 'selected' : '' ?>>Pasifler</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="is_featured" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" onchange="this.form.submit()">
                <option value="">Tüm Özellikler</option>
                <option value="1" <?= $is_featured === '1' ? 'selected' : '' ?>>Öne Çıkanlar</option>
                <option value="0" <?= $is_featured === '0' ? 'selected' : '' ?>>Normal Markalar</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="products" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" onchange="this.form.submit()">
                <option value="">Ürün Durumu</option>
                <option value="yes" <?= $products === 'yes' ? 'selected' : '' ?>>Ürünlü Markalar</option>
                <option value="no" <?= $products === 'no' ? 'selected' : '' ?>>Ürünsüz Markalar</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <button type="submit" class="btn btn-secondary border-0 w-100 fs-7" style="padding: 10px 0;"><i class="bi bi-funnel me-2"></i>Filtrele</button>
        </div>
    </form>
</div>

<!-- Bulk Actions -->
<div id="bulkActionBar" class="d-none p-3 rounded-4 mb-4 d-flex justify-content-between align-items-center" style="background: rgba(197, 168, 128, 0.1); border: 1px solid rgba(197, 168, 128, 0.3);">
    <div class="d-flex align-items-center gap-2">
        <span class="text-white font-weight-600 fs-7"><span id="selectedCount">0</span> marka seçildi</span>
    </div>
    <div class="d-flex gap-2">
        <form action="<?= url('/admin/brands/bulk') ?>" method="POST" id="bulkForm" class="d-flex align-items-center gap-2">
            <input type="hidden" name="action" id="bulkActionInput" value="">
            <div id="bulkIdsContainer"></div>
            
            <button type="button" class="btn btn-secondary py-1 px-3 fs-7 text-success" onclick="submitBulkAction('active')">Toplu Aktif Yap</button>
            <button type="button" class="btn btn-secondary py-1 px-3 fs-7 text-warning" onclick="submitBulkAction('passive')">Toplu Pasif Yap</button>
            <button type="button" class="btn btn-danger py-1 px-3 fs-7" onclick="submitBulkAction('delete')">Toplu Sil</button>
        </form>
    </div>
</div>

<!-- Brands List Table -->
<div class="card border-0 p-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <div class="table-responsive">
        <table class="table table-dark table-hover border-0 m-0" style="background: transparent;">
            <thead>
                <tr style="border-bottom: 1px solid var(--sm-border); color: var(--sm-text-muted); font-size:13px;">
                    <th width="40"><input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll()" style="accent-color:var(--sm-gold); transform:scale(1.1);"></th>
                    <th width="80">ID</th>
                    <th width="100">Logo</th>
                    <th>Marka Adı</th>
                    <th>Slug</th>
                    <th>Ürün Sayısı</th>
                    <th>Toplam Satış</th>
                    <th>Gelir (Ciro)</th>
                    <th>Durum</th>
                    <th width="100">Sıra</th>
                    <th>Oluşturulma Tarihi</th>
                    <th class="text-end">İşlemler</th>
                </tr>
            </thead>
            <tbody id="brandsTableBody">
                <?php if (!empty($brands)): ?>
                    <?php foreach ($brands as $brand): ?>
                        <tr class="brand-row align-middle" data-id="<?= $brand['id'] ?>" id="row-<?= $brand['id'] ?>" draggable="true" ondragstart="dragStart(event)" ondragover="dragOver(event)" ondrop="drop(event)">
                            <td class="py-3"><input type="checkbox" class="row-checkbox" value="<?= $brand['id'] ?>" onclick="handleCheckboxChange()" style="accent-color:var(--sm-gold); transform:scale(1.1);"></td>
                            <td class="py-3 font-weight-600"><?= $brand['id'] ?></td>
                            <td class="py-3">
                                <?php if (!empty($brand['logo_path'])): ?>
                                    <img src="<?= url('/' . $brand['logo_path']) ?>" class="img-fluid rounded-3" style="max-height: 40px; max-width: 60px; object-fit: contain;">
                                <?php else: ?>
                                    <div class="rounded-3 border border-secondary d-flex align-items-center justify-content-center" style="width: 50px; height: 35px; background: rgba(0,0,0,0.2);">
                                        <i class="bi bi-image text-muted" style="font-size: 14px;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 font-weight-600">
                                <?= htmlspecialchars($brand['name']) ?>
                                <?php if ($brand['is_featured']): ?>
                                    <span class="badge bg-warning text-dark ms-2" style="font-size: 9px;">Öne Çıkan</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-muted"><?= htmlspecialchars($brand['slug']) ?></td>
                            <td class="py-3"><span class="badge bg-secondary rounded-pill"><?= $brand['product_count'] ?> Ürün</span></td>
                            <td class="py-3"><?= $brand['total_sales'] ?> Adet</td>
                            <td class="py-3">₺<?= number_format((float)$brand['total_revenue'], 2) ?></td>
                            <td class="py-3">
                                <?= $brand['is_active'] 
                                    ? '<span class="badge bg-success bg-opacity-10 text-success">Aktif</span>' 
                                    : '<span class="badge bg-danger bg-opacity-10 text-danger">Pasif</span>' ?>
                            </td>
                            <td class="py-3"><input type="number" class="form-control text-center p-1 text-white border-0 sort-input" style="background:rgba(255,255,255,0.05); width:50px; font-size:12px;" value="<?= $brand['sort_order'] ?>" onchange="updateSortOrder(<?= $brand['id'] ?>, this.value)"></td>
                            <td class="py-3 text-muted" style="font-size:12px;"><?= date('d M Y', strtotime($brand['created_at'])) ?></td>
                            <td class="py-3 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?= url('/brands/' . $brand['slug']) ?>" target="_blank" class="btn btn-secondary py-1 px-3" style="font-size:11px;"><i class="bi bi-eye me-1"></i> Önizle</a>
                                    <a href="<?= url('/admin/brands/edit?id=' . $brand['id']) ?>" class="btn btn-secondary py-1 px-3" style="font-size:11px;"><i class="bi bi-pencil-square me-1"></i> Düzenle</a>
                                    <form action="<?= url('/admin/brands/delete') ?>" method="POST" onsubmit="return confirm('Bu markayı silmek istediğinize emin misiniz?');" class="m-0">
                                        <input type="hidden" name="id" value="<?= $brand['id'] ?>">
                                        <button type="submit" class="btn btn-danger py-1 px-3" style="font-size:11px;"><i class="bi bi-trash me-1"></i> Sil</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" class="text-center py-5 text-muted">Kayıtlı marka bulunamadı.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Drag and Drop Ordering
    let draggedRow = null;

    function dragStart(e) {
        draggedRow = e.currentTarget;
        e.dataTransfer.setData('text/plain', draggedRow.id);
        e.dataTransfer.effectAllowed = 'move';
        draggedRow.style.opacity = '0.5';
    }

    function dragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    function drop(e) {
        e.preventDefault();
        const targetRow = e.currentTarget;
        draggedRow.style.opacity = '1';

        if (draggedRow !== targetRow) {
            const body = document.getElementById('brandsTableBody');
            const allRows = Array.from(body.children);
            const draggedIdx = allRows.indexOf(draggedRow);
            const targetIdx = allRows.indexOf(targetRow);

            if (draggedIdx < targetIdx) {
                body.insertBefore(draggedRow, targetRow.nextSibling);
            } else {
                body.insertBefore(draggedRow, targetRow);
            }

            saveOrderStructure();
        }
    }

    function updateSortOrder(id, val) {
        const orders = [{ id: id, sort_order: val }];
        sendSortRequest(orders);
    }

    function saveOrderStructure() {
        const body = document.getElementById('brandsTableBody');
        const rows = Array.from(body.querySelectorAll('.brand-row'));
        const orders = rows.map((row, index) => {
            return {
                id: parseInt(row.getAttribute('data-id')),
                sort_order: index + 1
            };
        });
        sendSortRequest(orders);
    }

    function sendSortRequest(orders) {
        fetch('<?= url("/admin/brands/sort") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ orders: orders })
        })
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                alert('Sıralama kaydedilemedi: ' + res.message);
            }
        })
        .catch(err => {
            console.error(err);
        });
    }

    // Checkboxes and Bulk Actions
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAllCheckbox');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        handleCheckboxChange();
    }

    function handleCheckboxChange() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionBar');
        const countSpan = document.getElementById('selectedCount');

        if (checked.length > 0) {
            bulkBar.classList.remove('d-none');
            countSpan.textContent = checked.length;
        } else {
            bulkBar.classList.add('d-none');
        }
    }

    function submitBulkAction(action) {
        if (confirm('Toplu işlemi uygulamak istediğinize emin misiniz?')) {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const container = document.getElementById('bulkIdsContainer');
            container.innerHTML = '';

            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'brand_ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });

            document.getElementById('bulkActionInput').value = action;
            document.getElementById('bulkForm').submit();
        }
    }
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
