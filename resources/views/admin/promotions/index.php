<?php
use App\Helpers\ComponentHelper;

$title = "Kampanya & İndirim Motoru - SaintMonarc";
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
    color: #white;
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
.promo-card {
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--sm-border);
    border-radius: 16px;
    transition: all 0.3s ease;
}
.promo-card:hover {
    transform: translateY(-5px);
    border-color: var(--sm-gold, #c5a880);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
</style>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Kampanyalar' => '#', 'Tüm Kampanyalar' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Kampanya & İndirim Motoru (Promotion Engine)</h2>
        <div class="d-flex gap-2">
            <a href="<?= url('/admin/promotions/preview') ?>" class="btn btn-outline-info border-0"><i class="bi bi-play-circle me-2"></i>Kampanya Simülatörü</a>
            <a href="<?= url('/admin/promotions/calendar') ?>" class="btn btn-outline-warning border-0"><i class="bi bi-calendar-range me-2"></i>Kampanya Takvimi</a>
            <a href="<?= url('/admin/promotions/reports') ?>" class="btn btn-outline-success border-0"><i class="bi bi-bar-chart-line me-2"></i>Kampanya Raporları</a>
            <a href="<?= url('/admin/promotions/create') ?>" class="btn btn-warning text-dark border-0"><i class="bi bi-plus-circle me-2"></i>Yeni Kampanya Ekle</a>
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

<!-- Filtreler -->
<div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-funnel-fill me-2 text-warning"></i>Gelişmiş Filtreleme</h4>
    <form method="GET" action="<?= url('/admin/promotions') ?>">
        <div class="row g-3">
            <div class="col-md-4 col-sm-12">
                <label class="form-label text-muted fs-7 mb-1">Kampanya Adı, Açıklaması veya Kodu</label>
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" class="search-input w-100 text-white" placeholder="Arama yapın...">
            </div>
            <div class="col-md-4 col-sm-6">
                <label class="form-label text-muted fs-7 mb-1">Durum</label>
                <select name="status" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                    <option value="">Tümü</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Taslak</option>
                    <option value="passive" <?= ($filters['status'] ?? '') === 'passive' ? 'selected' : '' ?>>Pasif</option>
                    <option value="expired" <?= ($filters['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Süresi Dolmuş</option>
                    <option value="scheduled" <?= ($filters['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Planlanmış</option>
                </select>
            </div>
            <div class="col-md-4 col-sm-6">
                <label class="form-label text-muted fs-7 mb-1">Kampanya Türü</label>
                <select name="type" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                    <option value="">Tümü</option>
                    <option value="percentage" <?= ($filters['type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Yüzdelik İndirim</option>
                    <option value="fixed_cart" <?= ($filters['type'] ?? '') === 'fixed_cart' ? 'selected' : '' ?>>Sepette Sabit Tutar</option>
                    <option value="fixed_product" <?= ($filters['type'] ?? '') === 'fixed_product' ? 'selected' : '' ?>>Üründe Sabit İndirim</option>
                    <option value="free_shipping" <?= ($filters['type'] ?? '') === 'free_shipping' ? 'selected' : '' ?>>Ücretsiz Kargo</option>
                    <option value="gift_product" <?= ($filters['type'] ?? '') === 'gift_product' ? 'selected' : '' ?>>Hediye Ürün Kampanyası</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="<?= url('/admin/promotions') ?>" class="btn btn-secondary border-0 fs-7">Sıfırla</a>
            <button type="submit" class="btn btn-warning text-dark border-0 fs-7 px-4">Filtrele</button>
        </div>
    </form>
</div>

<!-- Kampanya Sekmeleri ve Tablo -->
<div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <ul class="nav nav-tabs border-0 gap-2" id="promoTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active rounded-3 py-2 px-3 fs-7" id="tab-active" data-bs-toggle="tab" data-bs-target="#panel-active" type="button" role="tab">Tüm Kampanyalar (<?= count($promotions) ?>)</button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-trash" data-bs-toggle="tab" data-bs-target="#panel-trash" type="button" role="tab">Geri Dönüşüm (<?= count($trash) ?>)</button>
            </li>
        </ul>
        <div class="btn-group">
            <a href="<?= url('/admin/promotions?view=list' . (http_build_query($filters) ? '&' . http_build_query($filters) : '')) ?>" class="btn btn-sm btn-<?= $viewMode === 'list' ? 'warning text-dark' : 'dark' ?>"><i class="bi bi-list-task"></i> Liste</a>
            <a href="<?= url('/admin/promotions?view=card' . (http_build_query($filters) ? '&' . http_build_query($filters) : '')) ?>" class="btn btn-sm btn-<?= $viewMode === 'card' ? 'warning text-dark' : 'dark' ?>"><i class="bi bi-grid-fill"></i> Kart</a>
        </div>
    </div>

    <form action="<?= url('/admin/promotions/bulk') ?>" method="POST" id="bulkForm">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <select name="action" class="form-select border-0 text-white fs-7 py-2 px-3" style="background: rgba(255,255,255,0.05); width: 220px; border: 1px solid var(--sm-border) !important;" id="actionSelect">
                    <option value="">Seçili Kampanyaları...</option>
                    <option value="status">Durumunu Değiştir</option>
                    <option value="delete">Sil (Çöp Kutusuna Taşı)</option>
                </select>
                <select name="target_status" class="form-select border-0 text-white fs-7 py-2 px-3" style="background: rgba(255,255,255,0.05); width: 180px; border: 1px solid var(--sm-border) !important; display:none;" id="targetStatusSelect">
                    <option value="active">Aktif</option>
                    <option value="draft">Taslak</option>
                    <option value="passive">Pasif</option>
                </select>
                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 px-3 py-2">Uygula</button>
            </div>
            
            <div class="d-flex gap-2">
                <a href="<?= url('/admin/promotions/export?format=excel') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
                <a href="<?= url('/admin/promotions/export?format=csv') ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-file-earmark-spreadsheet me-1"></i>CSV</a>
            </div>
        </div>

        <div class="tab-content" id="promoTabsContent">
            <!-- AKTİF KAMPANYALAR -->
            <div class="tab-pane fade show active" id="panel-active" role="tabpanel">
                <?php if ($viewMode === 'card'): ?>
                    <div class="row g-3">
                        <?php if (empty($promotions)): ?>
                            <div class="col-12 text-center py-4 text-muted fs-7">Filtrelere uygun kampanya bulunamadı.</div>
                        <?php else: ?>
                            <?php foreach ($promotions as $p): ?>
                                <div class="col-xl-3 col-md-4 col-sm-6">
                                    <div class="promo-card p-3 text-white h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <input type="checkbox" name="promotion_ids[]" value="<?= $p['id'] ?>" class="check-active">
                                                <span class="badge bg-dark border border-secondary" style="font-size:10px;"><?= htmlspecialchars(strtoupper($p['type'])) ?></span>
                                            </div>
                                            <h5 class="m-0 fs-6 font-weight-600">
                                                <a href="<?= url('/admin/promotions/edit?id=' . $p['id']) ?>" class="text-white text-decoration-none hover-gold">
                                                    <?= htmlspecialchars($p['name']) ?>
                                                </a>
                                            </h5>
                                            <p class="text-muted fs-8 mb-2 mt-1"><?= htmlspecialchars($p['description'] ?? '') ?></p>
                                            
                                            <?php if ($p['code']): ?>
                                                <span class="badge bg-warning text-dark font-weight-600 mb-2">Kod: <?= htmlspecialchars($p['code']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="border-top border-secondary border-opacity-25 pt-2 mt-2 fs-8">
                                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Öncelik:</span> <span class="text-white"><?= $p['priority'] ?></span></div>
                                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Kuponlar:</span> <span class="text-white"><?= $p['coupon_count'] ?> Kupon</span></div>
                                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Durum:</span> 
                                                <span class="badge text-capitalize bg-opacity-10 
                                                    bg-<?= $p['status'] === 'active' ? 'success text-success' : ($p['status'] === 'draft' ? 'secondary text-secondary' : 'danger text-danger') ?>">
                                                    <?= htmlspecialchars($p['status']) ?>
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-center gap-1 mt-3">
                                                <a href="<?= url('/admin/promotions/edit?id=' . $p['id']) ?>" class="btn btn-sm btn-dark"><i class="bi bi-pencil"></i> Düzenle</a>
                                                <button type="button" onclick="duplicatePromo(<?= $p['id'] ?>)" class="btn btn-sm btn-outline-warning"><i class="bi bi-files"></i></button>
                                                <button type="button" onclick="deletePromo(<?= $p['id'] ?>)" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-white">
                            <thead>
                                <tr class="text-muted fs-7">
                                    <th width="40"><input type="checkbox" id="selectAllActive" onclick="toggleSelectAll('active')"></th>
                                    <th>Kampanya Adı</th>
                                    <th>Tür</th>
                                    <th>Kod</th>
                                    <th>Öncelik</th>
                                    <th>Başlangıç / Bitiş</th>
                                    <th>Kupon Sayısı</th>
                                    <th>Durum</th>
                                    <th width="150" class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                <?php if (empty($promotions)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">Kayıtlı kampanya bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($promotions as $p): ?>
                                        <tr>
                                            <td><input type="checkbox" name="promotion_ids[]" value="<?= $p['id'] ?>" class="check-active"></td>
                                            <td>
                                                <a href="<?= url('/admin/promotions/edit?id=' . $p['id']) ?>" class="text-white text-decoration-none font-weight-600 hover-gold">
                                                    <?= htmlspecialchars($p['name']) ?>
                                                </a>
                                                <div class="text-muted fs-8"><?= htmlspecialchars($p['description'] ?? '') ?></div>
                                            </td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($p['type']) ?></span></td>
                                            <td><code class="text-warning"><?= $p['code'] ?? '-' ?></code></td>
                                            <td><strong><?= $p['priority'] ?></strong></td>
                                            <td>
                                                <small><?= $p['start_date'] ? date('d.m.Y H:i', strtotime($p['start_date'])) : 'Sınırsız' ?></small><br>
                                                <small class="text-muted"><?= $p['end_date'] ? date('d.m.Y H:i', strtotime($p['end_date'])) : 'Sınırsız' ?></small>
                                            </td>
                                            <td><span class="badge bg-info text-dark"><?= $p['coupon_count'] ?></span></td>
                                            <td>
                                                <span class="badge bg-opacity-10 text-capitalize bg-<?= $p['status'] === 'active' ? 'success text-success' : ($p['status'] === 'draft' ? 'secondary text-secondary' : 'danger text-danger') ?>">
                                                    <?= htmlspecialchars($p['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <a href="<?= url('/admin/promotions/edit?id=' . $p['id']) ?>" class="btn btn-sm btn-dark" title="Düzenle"><i class="bi bi-pencil"></i></a>
                                                    <button type="button" onclick="duplicatePromo(<?= $p['id'] ?>)" class="btn btn-sm btn-dark" title="Kopyala"><i class="bi bi-files"></i></button>
                                                    <button type="button" onclick="deletePromo(<?= $p['id'] ?>)" class="btn btn-sm btn-outline-danger" title="Sil"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- GERİ DÖNÜŞÜM -->
            <div class="tab-pane fade" id="panel-trash" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-white">
                        <thead>
                            <tr class="text-muted fs-7">
                                <th width="40"><input type="checkbox" id="selectAllTrash" onclick="toggleSelectAll('trash')"></th>
                                <th>Kampanya</th>
                                <th>Tür</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th width="200" class="text-end">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="fs-7">
                            <?php if (empty($trash)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Çöp kutusu boş.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($trash as $p): ?>
                                    <tr>
                                        <td><input type="checkbox" name="promotion_ids[]" value="<?= $p['id'] ?>" class="check-trash"></td>
                                        <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($p['type']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($p['status']) ?></span></td>
                                        <td><?= date('d.m.Y', strtotime($p['created_at'])) ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <button type="button" onclick="restorePromo(<?= $p['id'] ?>)" class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> Geri Yükle</button>
                                                <button type="button" onclick="forceDeletePromo(<?= $p['id'] ?>)" class="btn btn-sm btn-danger"><i class="bi bi-trash-fill"></i> Kalıcı Sil</button>
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

<!-- Gizli Formlar -->
<form id="actionForm" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" id="actionId">
</form>

<script>
document.getElementById('actionSelect').addEventListener('change', function() {
    var statusSel = document.getElementById('targetStatusSelect');
    statusSel.style.display = (this.value === 'status') ? 'inline-block' : 'none';
});

function toggleSelectAll(type) {
    var checkAll = document.getElementById('selectAll' + type.charAt(0).toUpperCase() + type.slice(1));
    var checkboxes = document.querySelectorAll('.check-' + type);
    checkboxes.forEach(function(cb) {
        cb.checked = checkAll.checked;
    });
}

function deletePromo(id) {
    if (confirm('Bu kampanyayı silmek istediğinize emin misiniz?')) {
        var form = document.getElementById('actionForm');
        form.action = '<?= url('/admin/promotions/delete') ?>';
        document.getElementById('actionId').value = id;
        form.submit();
    }
}

function restorePromo(id) {
    var form = document.getElementById('actionForm');
    form.action = '<?= url('/admin/promotions/restore') ?>';
    document.getElementById('actionId').value = id;
    form.submit();
}

function forceDeletePromo(id) {
    if (confirm('DİKKAT! Bu kampanyayı veritabanından kalıcı olarak sileceksiniz.\nBu işlem geri alınamaz! Onaylıyor musunuz?')) {
        var form = document.getElementById('actionForm');
        form.action = '<?= url('/admin/promotions/force-delete') ?>';
        document.getElementById('actionId').value = id;
        form.submit();
    }
}

function duplicatePromo(id) {
    if (confirm('Bu kampanyayı kopyalamak istiyor musunuz?')) {
        var form = document.getElementById('actionForm');
        form.action = '<?= url('/admin/promotions/duplicate') ?>';
        document.getElementById('actionId').value = id;
        form.submit();
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
