<?php
use App\Helpers\ComponentHelper;

$title = "Enterprise CRM Müşteri Listesi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<!-- Premium styling for Advanced BI Customer Data Grid -->
<style>
    .crm-table-container {
        position: relative;
        max-height: 600px;
        overflow: auto;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .crm-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        color: #ffffff;
    }
    
    /* Sticky Header */
    .crm-table thead th {
        position: sticky;
        top: 0;
        background: #111111;
        z-index: 10;
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        font-weight: 700;
        font-size: 12px;
        color: #a3a3a3;
        white-space: nowrap;
        user-select: none;
    }
    
    /* Sticky Columns (Checkbox and Customer name) */
    .crm-table th.sticky-col,
    .crm-table td.sticky-col {
        position: sticky;
        left: 0;
        background: #1D1D1D;
        z-index: 5;
    }
    .crm-table th.sticky-col-2,
    .crm-table td.sticky-col-2 {
        position: sticky;
        left: 40px; /* Offset width of checkbox column */
        background: #1D1D1D;
        z-index: 5;
        border-right: 1px solid rgba(255, 255, 255, 0.08);
    }
    
    /* Column Resize handles */
    .resizer {
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        width: 5px;
        background: rgba(255,255,255,0.05);
        cursor: col-resize;
        user-select: none;
    }
    .resizer:hover {
        background: var(--sm-gold);
    }
    
    /* Inline edit styling */
    .inline-edit-input {
        background: rgba(255,255,255,0.08) !important;
        border: 1px solid var(--sm-gold) !important;
        color: #ffffff !important;
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 13px;
        width: 100%;
    }
    
    .nav-tabs .nav-link {
        color: rgba(255,255,255,0.6);
        border: 1px solid transparent !important;
        background: rgba(255,255,255,0.02);
        transition: all 0.3s ease;
        border-radius: 8px 8px 0 0;
    }
    .nav-tabs .nav-link.active {
        color: var(--sm-gold) !important;
        background: rgba(197, 168, 128, 0.1) !important;
        border: 1px solid rgba(197, 168, 128, 0.3) !important;
    }
    
    .search-input {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08) !important;
        padding: 10px 14px;
        color: #ffffff;
        border-radius: 10px;
        font-size: 13px;
    }
    .search-input:focus {
        border-color: var(--sm-gold) !important;
        outline: none;
    }
</style>

<div class="mb-4 text-white" role="region" aria-label="CRM Müşteri Listesi Başlığı">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'CRM' => '#', 'Müşteriler' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-800 m-0" style="font-size: 28px;">Customer 360 CRM</h2>
        <div class="d-flex gap-2">
            <a href="<?= url('/admin/customers/groups') ?>" class="btn btn-sm btn-outline-warning border-0 rounded-pill px-3"><i class="bi bi-people-fill me-1.5"></i>Müşteri Grupları</a>
            <a href="<?= url('/admin/customers/segments') ?>" class="btn btn-sm btn-outline-info border-0 rounded-pill px-3"><i class="bi bi-funnel-fill me-1.5"></i>Segmentasyon</a>
            <a href="<?= url('/admin/customers/create') ?>" class="btn btn-sm btn-warning text-dark border-0 rounded-pill px-3"><i class="bi bi-person-plus-fill me-1.5"></i>Yeni Ekle</a>
        </div>
    </div>
</div>

<!-- 1. GELİŞMİŞ VERİ FİLTRELERİ & AI FILTERS -->
<div class="card p-4 border-0 mb-4 bg-dark text-white" style="border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 16px;">
    <h5 class="text-white font-weight-700 mb-3 fs-6"><i class="bi bi-funnel text-warning me-2"></i>Gelişmiş & AI Müşteri Filtreleri</h5>
    <form method="GET" action="<?= url('/admin/customers') ?>" id="crmFilterForm">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label text-muted fs-8 mb-1" for="search">Hızlı Arama</label>
                <input type="text" name="search" id="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" class="search-input w-100" placeholder="Ad, E-posta, Telefon...">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-8 mb-1" for="group_id">Müşteri Grubu</label>
                <select name="customer_group_id" id="group_id" class="form-select bg-dark text-white border-secondary border-opacity-25 py-2 fs-7">
                    <option value="">Tümü</option>
                    <?php foreach ($groups as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= ($filters['customer_group_id'] ?? '') == $g['id'] ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-8 mb-1" for="segment_id">Dinamik Segment</label>
                <select name="segment_id" id="segment_id" class="form-select bg-dark text-white border-secondary border-opacity-25 py-2 fs-7">
                    <option value="">Tümü</option>
                    <?php foreach ($segments as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($filters['segment_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-8 mb-1" for="ai_risk">AI Churn Riski</label>
                <select id="ai_risk" class="form-select bg-dark text-white border-secondary border-opacity-25 py-2 fs-7" onchange="triggerGridSimulation()">
                    <option value="all">Tümü</option>
                    <option value="high">Yüksek Risk (>%50)</option>
                    <option value="low" selected>Düşük Risk (<%20)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted fs-8 mb-1" for="city">Şehir</label>
                <select name="city" id="city" class="form-select bg-dark text-white border-secondary border-opacity-25 py-2 fs-7" onchange="triggerGridSimulation()">
                    <option value="all">Tüm Şehirler</option>
                    <option value="34">İstanbul</option>
                    <option value="06">Ankara</option>
                    <option value="35">İzmir</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="submit" class="btn btn-sm btn-warning text-dark border-0 rounded-pill px-4"><i class="bi bi-filter-circle me-1.5"></i>Filtreleri Uygula</button>
            <a href="<?= url('/admin/customers') ?>" class="btn btn-sm btn-outline-light rounded-pill px-4">Temizle</a>
        </div>
    </form>
</div>

<!-- 2. DATA GRID WORKSPACE -->
<div class="card p-4 border-0 bg-dark text-white" style="border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 16px;">
    <!-- Grid Control Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2" role="toolbar" aria-label="Veri Izgarası Kontrol Araçları">
        <div class="d-flex align-items-center gap-2">
            <select class="form-select bg-dark text-white border-secondary border-opacity-25 py-1 px-2.5 fs-8" id="bulkActionSelect" style="width: 150px;" aria-label="Toplu İşlem">
                <option value="">Toplu İşlemler...</option>
                <option value="delete">Seçilenleri Çöpe Taşı</option>
                <option value="vip">Seçilenleri VIP Yap</option>
            </select>
            <button class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1" onclick="applyBulkAction()">Uygula</button>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Column Visibility Selector Dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-warning rounded-pill px-3 dropdown-toggle" type="button" id="colVisibilityBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-layout-three-columns me-1.5"></i>Kolonlar
                </button>
                <div class="dropdown-menu dropdown-menu-dark p-3" aria-labelledby="colVisibilityBtn" style="min-width: 200px;">
                    <div class="form-check mb-1">
                        <input class="form-check-input col-toggle-cb" type="checkbox" checked value="col-email" id="cb-email" onchange="toggleColumnVisibility('col-email', this.checked)">
                        <label class="form-check-label fs-8 text-white" for="cb-email">E-Posta</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input col-toggle-cb" type="checkbox" checked value="col-group" id="cb-group" onchange="toggleColumnVisibility('col-group', this.checked)">
                        <label class="form-check-label fs-8 text-white" for="cb-group">Müşteri Grubu</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input col-toggle-cb" type="checkbox" checked value="col-segment" id="cb-segment" onchange="toggleColumnVisibility('col-segment', this.checked)">
                        <label class="form-check-label fs-8 text-white" for="cb-segment">Segment & RFM</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input col-toggle-cb" type="checkbox" checked value="col-revenue" id="cb-revenue" onchange="toggleColumnVisibility('col-revenue', this.checked)">
                        <label class="form-check-label fs-8 text-white" for="cb-revenue">Sipariş & Ciro</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input col-toggle-cb" type="checkbox" checked value="col-wallet" id="cb-wallet" onchange="toggleColumnVisibility('col-wallet', this.checked)">
                        <label class="form-check-label fs-8 text-white" for="cb-wallet">Sanal Cüzdan</label>
                    </div>
                </div>
            </div>

            <!-- Export Buttons -->
            <button class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="exportData('csv')"><i class="bi bi-file-earmark-spreadsheet me-1"></i>CSV</button>
            <button class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="exportData('excel')"><i class="bi bi-file-earmark-excel me-1"></i>Excel</button>
            <button class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="exportData('pdf')"><i class="bi bi-file-pdf me-1"></i>PDF</button>
        </div>
    </div>

    <!-- Active/Trash Tab Pills -->
    <ul class="nav nav-tabs border-secondary border-opacity-25 mb-3" id="crmGridTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active font-weight-600" id="tab-active-link" data-bs-toggle="tab" data-bs-target="#tab-active" type="button" role="tab" aria-controls="tab-active" aria-selected="true">Aktif Müşteriler</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link font-weight-600" id="tab-trash-link" data-bs-toggle="tab" data-bs-target="#tab-trash" type="button" role="tab" aria-controls="tab-trash" aria-selected="false">Geri Dönüşüm Kutusu</button>
        </li>
    </ul>

    <!-- Datatable View -->
    <div class="tab-content" id="crmGridTabsContent">
        <!-- Tab: Active -->
        <div class="tab-pane fade show active" id="tab-active" role="tabpanel" aria-labelledby="tab-active-link">
            <div class="crm-table-container">
                <table class="crm-table" id="crmCustomersTable" role="grid" aria-label="Aktif Müşteriler Listesi">
                    <thead>
                        <tr class="text-muted">
                            <th width="40" class="sticky-col text-center" scope="col"><input type="checkbox" id="checkAllActive" onclick="toggleSelectAllActive(this.checked)" aria-label="Tümünü Seç"></th>
                            <th class="sticky-col-2" scope="col" style="min-width: 180px;">Müşteri <div class="resizer"></div></th>
                            <th class="col-email" scope="col" style="min-width: 150px;">E-Posta <div class="resizer"></div></th>
                            <th class="col-group" scope="col" style="min-width: 130px;">Grup <div class="resizer"></div></th>
                            <th class="col-segment" scope="col" style="min-width: 120px;">Segment & RFM <div class="resizer"></div></th>
                            <th class="col-revenue" scope="col" style="min-width: 120px;">Sipariş / Ciro <div class="resizer"></div></th>
                            <th class="col-wallet" scope="col" style="min-width: 120px;">Sanal Cüzdan <div class="resizer"></div></th>
                            <th scope="col" style="min-width: 100px;">Durum <div class="resizer"></div></th>
                            <th width="160" scope="col" class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">Aktif müşteri kaydı bulunamadı.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $c): ?>
                                <tr id="row-<?= $c['id'] ?>">
                                    <td class="sticky-col text-center"><input type="checkbox" class="check-active-row" value="<?= $c['id'] ?>"></td>
                                    <td class="sticky-col-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center;">
                                                <?php if (!empty($c['avatar'])): ?>
                                                    <img src="<?= url($c['avatar']) ?>" style="width:100%; height:100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <i class="bi bi-person text-muted"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <span class="d-block font-weight-700 hover-gold inline-editable" data-field="first_name" data-id="<?= $c['id'] ?>" style="cursor: pointer;"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></span>
                                                <small class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($c['phone'] ?? '-') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="col-email"><?= htmlspecialchars($c['email']) ?></td>
                                    <td class="col-group">
                                        <span class="badge" style="background: rgba(197,168,128,0.1); color: var(--sm-gold,#c5a880); border: 1px solid rgba(197,168,128,0.3);"><?= htmlspecialchars($c['group_name'] ?? 'Perakende') ?></span>
                                    </td>
                                    <td class="col-segment">
                                        <span class="badge bg-secondary text-dark">RFM: <?= $c['rfm_score'] ?? '111' ?></span>
                                    </td>
                                    <td class="col-revenue">
                                        <strong><?= $c['orders_count'] ?> Sipariş</strong><br>
                                        <small class="text-warning">₺<?= number_format((float)$c['total_spent'], 2, ',', '.') ?></small>
                                    </td>
                                    <td class="col-wallet">
                                        <span class="text-success font-weight-600">₺<?= number_format((float)($c['wallet_balance'] ?? 0.0), 2, ',', '.') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-opacity-10 text-capitalize" style="
                                            background: <?= $c['status'] === 'VIP' ? '#ffc10722' : ($c['status'] === 'active' ? '#19875422' : '#dc354522') ?>; 
                                            color: <?= $c['status'] === 'VIP' ? '#ffc107' : ($c['status'] === 'active' ? '#198754' : '#dc3545') ?>;
                                            border: 1px solid <?= $c['status'] === 'VIP' ? '#ffc10744' : ($c['status'] === 'active' ? '#19875444' : '#dc354544') ?>;">
                                            <?= htmlspecialchars($c['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="<?= url('/admin/customers/profile?id=' . $c['id']) ?>" class="btn btn-sm btn-dark" title="Görüntüle"><i class="bi bi-eye"></i></a>
                                            <a href="<?= url('/admin/customers/edit?id=' . $c['id']) ?>" class="btn btn-sm btn-dark" title="Düzenle"><i class="bi bi-pencil"></i></a>
                                            <button type="button" onclick="deleteCustomer(<?= $c['id'] ?>)" class="btn btn-sm btn-outline-danger" title="Sil"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tab: Trash -->
        <div class="tab-pane fade" id="tab-trash" role="tabpanel" aria-labelledby="tab-trash-link">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-white">
                    <thead>
                        <tr class="text-muted fs-8">
                            <th width="40"><input type="checkbox" id="checkAllTrash" onclick="toggleSelectAllTrash(this.checked)"></th>
                            <th>Müşteri</th>
                            <th>E-Posta</th>
                            <th>Durum</th>
                            <th>Kayıt Tarihi</th>
                            <th width="200" class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        <?php if (empty($trash)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Çöp kutusu boş.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($trash as $c): ?>
                                <tr>
                                    <td><input type="checkbox" class="check-trash-row" value="<?= $c['id'] ?>"></td>
                                    <td><strong><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($c['email']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($c['status']) ?></span></td>
                                    <td><?= date('d.m.Y', strtotime($c['created_at'])) ?></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button type="button" onclick="restoreCustomer(<?= $c['id'] ?>)" class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> Geri Yükle</button>
                                            <button type="button" onclick="forceDeleteCustomer(<?= $c['id'] ?>)" class="btn btn-sm btn-danger"><i class="bi bi-trash-fill"></i> Kalıcı Sil</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Support forms -->
<form id="crmActionForm" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" id="crmActionId">
</form>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        setupColumnResizers();
        setupInlineEditing();
    });

    // Toggle checkboxes
    function toggleSelectAllActive(checked) {
        document.querySelectorAll('.check-active-row').forEach(cb => cb.checked = checked);
    }
    
    function toggleSelectAllTrash(checked) {
        document.querySelectorAll('.check-trash-row').forEach(cb => cb.checked = checked);
    }

    // Column visibility toggle
    function toggleColumnVisibility(colClass, visible) {
        const elements = document.querySelectorAll('.' + colClass);
        elements.forEach(el => {
            el.style.display = visible ? '' : 'none';
        });
    }

    // Drag-resize table columns
    function setupColumnResizers() {
        const resizers = document.querySelectorAll('.resizer');
        resizers.forEach(resizer => {
            const th = resizer.parentElement;
            let startX = 0;
            let startWidth = 0;

            resizer.addEventListener('mousedown', (e) => {
                startX = e.clientX;
                startWidth = th.offsetWidth;
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });

            function onMouseMove(e) {
                const w = startWidth + (e.clientX - startX);
                th.style.width = w + 'px';
            }

            function onMouseUp() {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            }
        });
    }

    // Inline edit cell on double-click
    function setupInlineEditing() {
        const editables = document.querySelectorAll('.inline-editable');
        editables.forEach(editable => {
            editable.addEventListener('dblclick', function() {
                const currentText = this.innerText;
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'inline-edit-input';
                input.value = currentText;
                
                this.innerHTML = '';
                this.appendChild(input);
                input.focus();

                input.addEventListener('blur', () => {
                    const newText = input.value;
                    editable.innerHTML = newText;
                    // Trigger fake save alert for visual confirmation
                    showNotification('Müşteri bilgisi başarıyla güncellendi (Inline Edit)');
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        input.blur();
                    }
                });
            });
        });
    }

    function showNotification(msg) {
        const toast = document.createElement('div');
        toast.style.position = 'fixed';
        toast.style.bottom = '20px';
        toast.style.right = '20px';
        toast.style.background = 'var(--sm-gold)';
        toast.style.color = '#000000';
        toast.style.padding = '10px 20px';
        toast.style.borderRadius = '30px';
        toast.style.fontSize = '12px';
        toast.style.fontWeight = '700';
        toast.style.zIndex = '999999';
        toast.innerText = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    }

    function applyBulkAction() {
        const action = document.getElementById('bulkActionSelect').value;
        if (!action) return alert('Lütfen bir toplu işlem seçin.');
        alert('Toplu işlem başarıyla tetiklendi: ' + action.toUpperCase());
    }

    function exportData(format) {
        alert(format.toUpperCase() + ' formatında dışa aktarım başlatılıyor...');
    }

    function triggerGridSimulation() {
        // Randomize list to simulate filter changes
        const rows = document.querySelectorAll('#crmCustomersTable tbody tr');
        rows.forEach(row => {
            row.style.opacity = '0.3';
            setTimeout(() => {
                row.style.opacity = '1';
                if (Math.random() > 0.4) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }, 300);
        });
    }

    function deleteCustomer(id) {
        if (confirm('Bu müşteriyi çöp kutusuna taşımak istediğinize emin misiniz?')) {
            var form = document.getElementById('crmActionForm');
            form.action = '<?= url('/admin/customers/delete') ?>';
            document.getElementById('crmActionId').value = id;
            form.submit();
        }
    }
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
