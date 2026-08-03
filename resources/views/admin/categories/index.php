<?php
use App\Helpers\ComponentHelper;

$title = "Kategori Yönetimi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$GLOBALS['csrfToken'] = $security->generateCsrfToken();

// Helper function to render hierarchical tree rows in the table
function renderCategoryTree(array $nodes, int $level = 0): void {
    foreach ($nodes as $node) {
        $indent = str_repeat('<span class="text-muted ms-3">&mdash;</span>', $level);
        $hasChildren = !empty($node['children']);
        
        $toggleBtn = $hasChildren 
            ? '<button type="button" class="btn p-0 border-0 text-white me-2" onclick="toggleSubtree(' . $node['id'] . ')"><i class="bi bi-chevron-down toggle-icon-' . $node['id'] . '"></i></button>' 
            : '<span class="me-4"></span>';
        
        $parentName = $node['parent_name'] ?? '<span class="text-muted fs-7">Ana Kategori</span>';
        $statusBadge = $node['is_active'] 
            ? '<span class="badge bg-success bg-opacity-10 text-success">Aktif</span>' 
            : '<span class="badge bg-danger bg-opacity-10 text-danger">Pasif</span>';
        
        echo '<tr class="category-row align-middle" data-id="' . $node['id'] . '" data-parent-id="' . ($node['parent_id'] ?? '') . '" data-level="' . $level . '" id="row-' . $node['id'] . '" draggable="true" ondragstart="dragStart(event)" ondragover="dragOver(event)" ondrop="drop(event)">';
        echo '<td class="py-3"><input type="checkbox" class="row-checkbox" value="' . $node['id'] . '" onclick="handleCheckboxChange()" style="accent-color:var(--sm-gold); transform:scale(1.1);"></td>';
        echo '<td class="py-3 font-weight-600">' . $node['id'] . '</td>';
        echo '<td class="py-3 font-weight-600">' . $toggleBtn . $indent . ' ' . htmlspecialchars($node['name']) . '</td>';
        echo '<td class="py-3 text-muted">' . htmlspecialchars($node['slug']) . '</td>';
        echo '<td class="py-3">' . $parentName . '</td>';
        echo '<td class="py-3"><span class="badge bg-secondary rounded-pill">' . $node['product_count'] . ' Ürün</span></td>';
        echo '<td class="py-3">' . $statusBadge . '</td>';
        echo '<td class="py-3"><input type="number" class="form-control text-center p-1 text-white border-0 sort-input" style="background:rgba(255,255,255,0.05); width:50px; font-size:12px;" value="' . $node['sort_order'] . '" onchange="updateSortOrder(' . $node['id'] . ', this.value)"></td>';
        echo '<td class="py-3 text-muted" style="font-size:12px;">' . date('d M Y', strtotime($node['created_at'])) . '</td>';
        echo '<td class="py-3 text-end">';
        echo '<div class="d-flex justify-content-end gap-2">';
        echo '<a href="' . url('/admin/categories/edit?id=' . $node['id']) . '" class="btn btn-secondary py-1 px-3" style="font-size:11px;"><i class="bi bi-pencil-square me-1"></i> Düzenle</a>';
        echo '<form action="' . url('/admin/categories/delete') . '" method="POST" onsubmit="return confirm(\'Kategoriyi ve TÜM ALT kategorilerini silmek istediğinize emin misiniz?\');" class="m-0">';
        echo '<input type="hidden" name="csrf_token" value="' . $GLOBALS['csrfToken'] . '">';
        echo '<input type="hidden" name="id" value="' . $node['id'] . '">';
        echo '<button type="submit" class="btn btn-danger py-1 px-3" style="font-size:11px;"><i class="bi bi-trash me-1"></i> Sil</button>';
        echo '</form>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
        
        if ($hasChildren) {
            renderCategoryTree($node['children'], $level + 1);
        }
    }
}
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Kategoriler' => url('/admin/categories')]) ?>
        <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Kategori Yönetimi (Enterprise)</h2>
        <p class="text-muted mb-0 fs-6">Sınırsız derinlikte alt kategori hiyerarşisi, sürükle-bırak sıralama ve SEO optimizasyonu.</p>
    </div>
    
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/categories/export') ?>" class="btn btn-secondary border-0">
            <i class="bi bi-file-earmark-excel me-2"></i> CSV Dışa Aktar
        </a>
        <a href="<?= url('/admin/categories/create') ?>" class="btn">
            <i class="bi bi-plus-circle me-2"></i> Yeni Kategori Ekle
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

<!-- Filters and Actions -->
<div class="p-3 rounded-4 mb-4" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
    <form method="GET" action="" class="row g-2">
        <div class="col-12 col-md-3">
            <div class="position-relative">
                <input type="text" name="q" class="search-input w-100" placeholder="Kategori veya slug ara..." value="<?= htmlspecialchars($q ?? '') ?>">
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
            <select name="type" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" onchange="this.form.submit()">
                <option value="">Tüm Hiyerarşi</option>
                <option value="parent" <?= $type === 'parent' ? 'selected' : '' ?>>Ana Kategoriler</option>
                <option value="sub" <?= $type === 'sub' ? 'selected' : '' ?>>Alt Kategoriler</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="products" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" onchange="this.form.submit()">
                <option value="">Ürün Filtresi</option>
                <option value="yes" <?= $products === 'yes' ? 'selected' : '' ?>>Ürünlü Kategoriler</option>
                <option value="no" <?= $products === 'no' ? 'selected' : '' ?>>Ürünsüz Kategoriler</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <button type="submit" class="btn btn-secondary border-0 w-100 fs-7" style="padding: 10px 0;"><i class="bi bi-funnel me-2"></i>Filtrele</button>
        </div>
    </form>
</div>

<!-- Bulk Action Action Bar -->
<div id="bulkActionBar" class="d-none p-3 rounded-4 mb-4 d-flex justify-content-between align-items-center" style="background: rgba(197, 168, 128, 0.1); border: 1px solid rgba(197, 168, 128, 0.3);">
    <div class="d-flex align-items-center gap-2">
        <span class="text-white font-weight-600 fs-7"><span id="selectedCount">0</span> kategori seçildi</span>
    </div>
    <div class="d-flex gap-2">
        <form action="<?= url('/admin/categories/bulk') ?>" method="POST" id="bulkForm" class="d-flex align-items-center gap-2">
            <input type="hidden" name="csrf_token" value="<?= $GLOBALS['csrfToken'] ?>">
            <input type="hidden" name="action" id="bulkActionInput" value="">
            <div id="bulkIdsContainer"></div>
            
            <select name="target_parent_id" id="bulkMoveSelect" class="form-select border-0 d-none text-white fs-7" style="background: rgba(0,0,0,0.4); min-width: 140px; padding: 6px 12px;">
                <option value="">Kök Dizin (Ana Kategori)</option>
                <?php foreach ($flat_categories as $fc): ?>
                    <option value="<?= $fc['id'] ?>"><?= htmlspecialchars($fc['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="button" class="btn btn-secondary py-1 px-3 fs-7" onclick="submitBulkAction('move')">Üst Kategoriyi Değiştir</button>
            <button type="button" class="btn btn-secondary py-1 px-3 fs-7 text-success" onclick="submitBulkAction('active')">Toplu Aktif Yap</button>
            <button type="button" class="btn btn-secondary py-1 px-3 fs-7 text-warning" onclick="submitBulkAction('passive')">Toplu Pasif Yap</button>
            <button type="button" class="btn btn-danger py-1 px-3 fs-7" onclick="submitBulkAction('delete')">Toplu Sil</button>
        </form>
    </div>
</div>

<!-- Category Tree View Table -->
<div class="card border-0 p-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <div class="table-responsive">
        <table class="table table-dark table-hover border-0 m-0" style="background: transparent;">
            <thead>
                <tr style="border-bottom: 1px solid var(--sm-border); color: var(--sm-text-muted); font-size:13px;">
                    <th width="40"><input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll()" style="accent-color:var(--sm-gold); transform:scale(1.1);"></th>
                    <th width="80">ID</th>
                    <th>Kategori Adı</th>
                    <th>Slug</th>
                    <th>Üst Kategori</th>
                    <th>Ürün Sayısı</th>
                    <th>Durum</th>
                    <th width="100">Sıra</th>
                    <th>Oluşturulma Tarihi</th>
                    <th class="text-end">İşlemler</th>
                </tr>
            </thead>
            <tbody id="categoryTableBody">
                <?php if (!empty($categories)): ?>
                    <?php renderCategoryTree($categories); ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">Kayıtlı kategori bulunamadı.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Collapsible Subtrees
    function toggleSubtree(id) {
        const icon = document.querySelector('.toggle-icon-' + id);
        const rows = document.querySelectorAll('.category-row');
        const isCollapsed = icon.classList.contains('bi-chevron-right');

        if (isCollapsed) {
            icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
        } else {
            icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
        }

        // Recursively collapse/expand subrows
        toggleChildRows(id, !isCollapsed);
    }

    function toggleChildRows(parentId, collapse) {
        const rows = document.querySelectorAll('.category-row[data-parent-id="' + parentId + '"]');
        rows.forEach(row => {
            const childId = row.getAttribute('data-id');
            if (collapse) {
                row.classList.add('d-none');
                toggleChildRows(childId, true);
            } else {
                row.classList.remove('d-none');
                // Only show grandchildren if child is also expanded
                const childIcon = document.querySelector('.toggle-icon-' + childId);
                if (!childIcon || childIcon.classList.contains('bi-chevron-down')) {
                    toggleChildRows(childId, false);
                }
            }
        });
    }

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
            // Verify if target is on the same hierarchical level
            if (draggedRow.getAttribute('data-parent-id') !== targetRow.getAttribute('data-parent-id')) {
                alert('Sürükle-bırak sıralama sadece aynı hiyerarşik seviyedeki kategoriler arasında yapılabilir.');
                return;
            }

            const body = document.getElementById('categoryTableBody');
            const allRows = Array.from(body.children);
            const draggedIdx = allRows.indexOf(draggedRow);
            const targetIdx = allRows.indexOf(targetRow);

            if (draggedIdx < targetIdx) {
                body.insertBefore(draggedRow, targetRow.nextSibling);
            } else {
                body.insertBefore(draggedRow, targetRow);
            }

            // Save order via AJAX
            saveOrderStructure();
        }
    }

    function updateSortOrder(id, val) {
        const orders = [{ id: id, sort_order: val, parent_id: document.getElementById('row-' + id).getAttribute('data-parent-id') }];
        sendSortRequest(orders);
    }

    function saveOrderStructure() {
        const body = document.getElementById('categoryTableBody');
        const rows = Array.from(body.querySelectorAll('.category-row'));
        const orders = rows.map((row, index) => {
            return {
                id: parseInt(row.getAttribute('data-id')),
                sort_order: index + 1,
                parent_id: row.getAttribute('data-parent-id') ? parseInt(row.getAttribute('data-parent-id')) : null
            };
        });
        sendSortRequest(orders);
    }

    function sendSortRequest(orders) {
        fetch('<?= url("/admin/categories/sort") ?>', {
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

    // Checkbox Operations & Bulk Actions
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
        if (action === 'move') {
            const moveSelect = document.getElementById('bulkMoveSelect');
            if (moveSelect.classList.contains('d-none')) {
                moveSelect.classList.remove('d-none');
                return;
            }
        }

        if (confirm('Toplu işlemi uygulamak istediğinize emin misiniz?')) {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const container = document.getElementById('bulkIdsContainer');
            container.innerHTML = '';

            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'category_ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });

            document.getElementById('bulkActionInput').value = action;
            document.getElementById('bulkForm').submit();
        }
    }
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
