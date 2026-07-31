<?php
use App\Helpers\ComponentHelper;

$title = "Sipariş Yönetimi Enterprise - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<style>
.nav-tabs .nav-link {
    color: rgba(255,255,255,0.6);
    border: 1px solid transparent !important;
    background: rgba(255,255,255,0.02);
    transition: all 0.3s ease;
}
.nav-tabs .nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.05);
}
.nav-tabs .nav-link.active {
    color: var(--sm-gold, #c5a880) !important;
    background: rgba(197, 168, 128, 0.1) !important;
    border: 1px solid rgba(197, 168, 128, 0.3) !important;
}
.search-input {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--sm-border) !important;
    padding: 10px 12px;
    color: #white;
    border-radius: 8px;
    font-size: 13px;
}
.search-input:focus {
    border-color: var(--sm-gold, #c5a880) !important;
    outline: none;
}
.badge-status {
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 600;
}
</style>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Satışlar' => '#', 'Sipariş Yönetimi' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Sipariş Yönetimi Enterprise</h2>
        <div class="d-flex gap-2">
            <a href="<?= url('/admin/orders/statuses') ?>" class="btn btn-outline-warning border-0"><i class="bi bi-gear-fill me-2"></i>Sipariş Durumları</a>
            <a href="<?= url('/admin/orders/reports') ?>" class="btn btn-outline-info border-0"><i class="bi bi-graph-up-arrow me-2"></i>Finansal Raporlar</a>
            <a href="<?= url('/admin/orders/create') ?>" class="btn btn-warning text-dark border-0"><i class="bi bi-plus-circle me-2"></i>Yeni Sipariş Oluştur</a>
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

<!-- 1. Gelişmiş Filtreleme Paneli -->
<div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-funnel-fill me-2 text-warning"></i>Gelişmiş Filtreleme ve Arama</h4>
    <form method="GET" action="<?= url('/admin/orders') ?>">
        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <label class="form-label text-muted fs-7 mb-1">Sipariş No</label>
                <input type="text" name="order_number" value="<?= htmlspecialchars($filters['order_number'] ?? '') ?>" class="search-input w-100 text-white" placeholder="SM-2026...">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label text-muted fs-7 mb-1">Müşteri Adı/Soyadı</label>
                <input type="text" name="customer" value="<?= htmlspecialchars($filters['customer'] ?? '') ?>" class="search-input w-100 text-white" placeholder="Müşteri ara...">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label text-muted fs-7 mb-1">Telefon</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($filters['phone'] ?? '') ?>" class="search-input w-100 text-white" placeholder="0532...">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label text-muted fs-7 mb-1">Durum</label>
                <select name="status" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                    <option value="">Tümü</option>
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?= $st['code'] ?>" <?= ($filters['status'] ?? '') === $st['code'] ? 'selected' : '' ?>><?= htmlspecialchars($st['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label text-muted fs-7 mb-1">Tarih Başlangıç</label>
                <input type="date" name="date_start" value="<?= htmlspecialchars($filters['date_start'] ?? '') ?>" class="search-input w-100 text-white">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label text-muted fs-7 mb-1">Tarih Bitiş</label>
                <input type="date" name="date_end" value="<?= htmlspecialchars($filters['date_end'] ?? '') ?>" class="search-input w-100 text-white">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label text-muted fs-7 mb-1">Min Tutar</label>
                <input type="number" step="0.01" name="min_amount" value="<?= htmlspecialchars($filters['min_amount'] ?? '') ?>" class="search-input w-100 text-white" placeholder="0.00">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label text-muted fs-7 mb-1">Max Tutar</label>
                <input type="number" step="0.01" name="max_amount" value="<?= htmlspecialchars($filters['max_amount'] ?? '') ?>" class="search-input w-100 text-white" placeholder="0.00">
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-3 flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" onclick="setQuickDate('today')" class="btn btn-sm btn-outline-secondary">Bugün</button>
                <button type="button" onclick="setQuickDate('yesterday')" class="btn btn-sm btn-outline-secondary">Dün</button>
                <button type="button" onclick="setQuickDate('this_week')" class="btn btn-sm btn-outline-secondary">Bu Hafta</button>
                <button type="button" onclick="setQuickDate('this_month')" class="btn btn-sm btn-outline-secondary">Bu Ay</button>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('/admin/orders') ?>" class="btn btn-secondary border-0 fs-7">Sıfırla</a>
                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 px-4">Filtrele</button>
            </div>
        </div>
    </form>
</div>

<!-- 2. Sipariş Listeleri & Sekmeler -->
<div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <ul class="nav nav-tabs border-0 mb-4 gap-2" id="orderListTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active rounded-3 py-2 px-3 fs-7" id="tab-active" data-bs-toggle="tab" data-bs-target="#panel-active" type="button" role="tab">Aktif Siparişler (<?= count($orders) ?>)</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-trash" data-bs-toggle="tab" data-bs-target="#panel-trash" type="button" role="tab">Çöp Kutusu (<?= count($trash) ?>)</button>
        </li>
    </ul>

    <form action="<?= url('/admin/orders/bulk') ?>" method="POST" id="bulkForm">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        
        <!-- Toplu İşlemler Kontrolü -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <select name="action" class="form-select border-0 text-white fs-7 py-2 px-3" style="background: rgba(255,255,255,0.05); width: 220px; border: 1px solid var(--sm-border) !important;">
                    <option value="">Seçili Siparişleri...</option>
                    <option value="invoice">E-Arşiv Fatura Oluştur</option>
                    <option value="delete">Çöp Kutusuna Taşı</option>
                    <option value="status">Durumunu Değiştir</option>
                </select>
                
                <select name="target_status" class="form-select border-0 text-white fs-7 py-2 px-3" style="background: rgba(255,255,255,0.05); width: 200px; border: 1px solid var(--sm-border) !important; display:none;" id="targetStatusSelect">
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?= $st['code'] ?>"><?= htmlspecialchars($st['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 px-3 py-2">Uygula</button>
            </div>
            
            <div class="d-flex gap-2">
                <a href="<?= url('/admin/orders/export?format=excel') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
                <a href="<?= url('/admin/orders/export?format=csv') ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-file-earmark-spreadsheet me-1"></i>CSV</a>
                <a href="<?= url('/admin/orders/export?format=xml') ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-file-earmark-code me-1"></i>XML</a>
                <a href="<?= url('/admin/orders/export?format=json') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-json me-1"></i>JSON</a>
            </div>
        </div>

        <div class="tab-content" id="orderListTabsContent">
            <!-- AKTİF SİPARİŞLER PANELİ -->
            <div class="tab-pane fade show active" id="panel-active" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-white">
                        <thead>
                            <tr class="text-muted fs-7">
                                <th width="40"><input type="checkbox" id="selectAllActive" onclick="toggleSelectAll('active')"></th>
                                <th>Sipariş No</th>
                                <th>Müşteri</th>
                                <th>Şehir</th>
                                <th>Toplam Tutar</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th width="150" class="text-end">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="fs-7">
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Arama kriterlerine uygun aktif sipariş bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                    <tr>
                                        <td><input type="checkbox" name="order_ids[]" value="<?= $o['id'] ?>" class="check-active"></td>
                                        <td>
                                            <a href="<?= url('/admin/orders/show?id=' . $o['id']) ?>" class="text-warning font-weight-600 text-decoration-none">
                                                <?= htmlspecialchars($o['order_number']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($o['billing_first_name'] . ' ' . $o['billing_last_name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($o['customer_email'] ?? '') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($o['billing_city'] ?? '-') ?></td>
                                        <td><strong><?= number_format((float)$o['grand_total'], 2) ?> <?= $o['currency_code'] ?></strong></td>
                                        <td>
                                            <span class="badge-status" style="background: <?= $o['status_color'] ?>22; color: <?= $o['status_color'] ?>; border: 1px solid <?= $o['status_color'] ?>44;">
                                                <i class="bi <?= $o['status_icon'] ?> me-1"></i><?= htmlspecialchars($o['status_name'] ?? $o['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="<?= url('/admin/orders/show?id=' . $o['id']) ?>" class="btn btn-sm btn-dark" title="Görüntüle"><i class="bi bi-eye"></i></a>
                                                <a href="<?= url('/admin/orders/edit?id=' . $o['id']) ?>" class="btn btn-sm btn-dark" title="Düzenle"><i class="bi bi-pencil"></i></a>
                                                <button type="button" onclick="duplicateOrder(<?= $o['id'] ?>)" class="btn btn-sm btn-dark" title="Kopyala"><i class="bi bi-copy"></i></button>
                                                <button type="button" onclick="deleteOrder(<?= $o['id'] ?>)" class="btn btn-sm btn-outline-danger" title="Çöpe Taşı"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ÇÖP KUTUSU PANELİ -->
            <div class="tab-pane fade" id="panel-trash" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-white">
                        <thead>
                            <tr class="text-muted fs-7">
                                <th width="40"><input type="checkbox" id="selectAllTrash" onclick="toggleSelectAll('trash')"></th>
                                <th>Sipariş No</th>
                                <th>Müşteri</th>
                                <th>Şehir</th>
                                <th>Toplam Tutar</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th width="150" class="text-end">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="fs-7">
                            <?php if (empty($trash)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Çöp kutusu boş.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($trash as $o): ?>
                                    <tr>
                                        <td><input type="checkbox" name="order_ids[]" value="<?= $o['id'] ?>" class="check-trash"></td>
                                        <td><span class="text-muted"><?= htmlspecialchars($o['order_number']) ?></span></td>
                                        <td>
                                            <?= htmlspecialchars($o['billing_first_name'] . ' ' . $o['billing_last_name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($o['customer_email'] ?? '') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($o['billing_city'] ?? '-') ?></td>
                                        <td><strong><?= number_format((float)$o['grand_total'], 2) ?> <?= $o['currency_code'] ?></strong></td>
                                        <td>
                                            <span class="badge-status" style="background: <?= $o['status_color'] ?>22; color: <?= $o['status_color'] ?>; border: 1px solid <?= $o['status_color'] ?>44;">
                                                <?= htmlspecialchars($o['status_name'] ?? $o['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <button type="button" onclick="restoreOrder(<?= $o['id'] ?>)" class="btn btn-sm btn-outline-success" title="Geri Yükle"><i class="bi bi-arrow-counterclockwise"></i> Geri Yükle</button>
                                                <button type="button" onclick="forceDeleteOrder(<?= $o['id'] ?>)" class="btn btn-sm btn-danger" title="Kalıcı Sil"><i class="bi bi-trash-fill"></i> Kalıcı Sil</button>
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
    </form>
</div>

<!-- Yardımcı Formlar (İşlemler için) -->
<form id="actionForm" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" id="actionId">
</form>

<script>
document.getElementsByName('action')[0].addEventListener('change', function() {
    var select = document.getElementById('targetStatusSelect');
    if (this.value === 'status') {
        select.style.display = 'inline-block';
    } else {
        select.style.display = 'none';
    }
});

function toggleSelectAll(type) {
    var checkAll = document.getElementById('selectAll' + type.charAt(0).toUpperCase() + type.slice(1));
    var checkboxes = document.querySelectorAll('.check-' + type);
    checkboxes.forEach(function(cb) {
        cb.checked = checkAll.checked;
    });
}

function setQuickDate(type) {
    var today = new Date().toISOString().split('T')[0];
    var startInput = document.getElementsByName('date_start')[0];
    var endInput = document.getElementsByName('date_end')[0];
    
    if (type === 'today') {
        startInput.value = today;
        endInput.value = today;
    } else if (type === 'yesterday') {
        var yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        var yStr = yesterday.toISOString().split('T')[0];
        startInput.value = yStr;
        endInput.value = yStr;
    } else if (type === 'this_week') {
        var startOfWeek = new Date();
        startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay() + 1);
        startInput.value = startOfWeek.toISOString().split('T')[0];
        endInput.value = today;
    } else if (type === 'this_month') {
        var startOfMonth = new Date();
        startOfMonth.setDate(1);
        startInput.value = startOfMonth.toISOString().split('T')[0];
        endInput.value = today;
    }
}

function duplicateOrder(id) {
    if (confirm('Bu siparişi kopyalayarak yeni bir taslak sipariş oluşturmak istediğinize emin misiniz?')) {
        var form = document.getElementById('actionForm');
        form.action = '<?= url('/admin/orders/duplicate') ?>';
        document.getElementById('actionId').value = id;
        form.submit();
    }
}

function deleteOrder(id) {
    if (confirm('Bu siparişi çöp kutusuna taşımak istediğinize emin misiniz?')) {
        var form = document.getElementById('actionForm');
        form.action = '<?= url('/admin/orders/delete') ?>';
        document.getElementById('actionId').value = id;
        form.submit();
    }
}

function restoreOrder(id) {
    var form = document.getElementById('actionForm');
    form.action = '<?= url('/admin/orders/restore') ?>';
    document.getElementById('actionId').value = id;
    form.submit();
}

function forceDeleteOrder(id) {
    if (confirm('BU SİPARİŞİ VE İLİŞKİLİ TÜM VERİLERİ (ÖDEMELER, KARGO, İADELER) KALICI OLARAK SİLMEK İSTEDİĞİNİZE EMİN MİSİNİZ?\nBu işlem geri alınamaz!')) {
        var form = document.getElementById('actionForm');
        form.action = '<?= url('/admin/orders/force-delete') ?>';
        document.getElementById('actionId').value = id;
        form.submit();
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
