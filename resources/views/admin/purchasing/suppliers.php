<?php
use App\Helpers\ComponentHelper;
$title = "Tedarikçi Yönetimi | SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="container-fluid py-4 text-white">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim' => url('/admin'), 'Satın Alma' => url('/admin/purchasing/dashboard'), 'Tedarikçiler' => '#']) ?>
            <h2 class="mt-2 text-white font-weight-800 fs-3">Tedarikçi Yönetimi (Suppliers)</h2>
            <p class="text-muted mb-0 fs-7">Sözleşmeli tedarikçi listesi, performans puanlaması ve risk dereceleri.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-light rounded-pill px-3" onclick="exportData('csv')"><i class="bi bi-filetype-csv me-1"></i> CSV</button>
            <button class="btn btn-outline-light rounded-pill px-3" onclick="exportData('excel')"><i class="bi bi-file-earmark-excel me-1"></i> Excel</button>
            <button class="btn btn-warning rounded-pill px-4 font-weight-600" data-bs-toggle="modal" data-bs-target="#addSupplierModal"><i class="bi bi-plus-circle me-1"></i> Yeni Tedarikçi Ekle</button>
        </div>
    </div>

    <!-- Alert Messaging -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 text-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter HUD -->
    <div class="card bg-dark border-secondary border-opacity-10 p-3 mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <input type="text" name="q" class="form-control bg-dark border-secondary border-opacity-25 text-white text-uppercase" placeholder="Şirket adı, Yetkili kişi, Vergi No ile arayın..." value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-3">
                <select name="is_active" class="form-select bg-dark border-secondary border-opacity-25 text-white">
                    <option value="">Tüm Çalışma Durumları</option>
                    <option value="1" <?= isset($filters['is_active']) && $filters['is_active'] === '1' ? 'selected' : '' ?>>Aktif</option>
                    <option value="0" <?= isset($filters['is_active']) && $filters['is_active'] === '0' ? 'selected' : '' ?>>Pasif</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-grid">
                <button type="submit" class="btn btn-warning font-weight-600"><i class="bi bi-funnel me-1"></i> Filtrele</button>
            </div>
            <div class="col-6 col-md-2 d-grid">
                <a href="<?= url('/admin/purchasing/suppliers') ?>" class="btn btn-outline-secondary">Sıfırla</a>
            </div>
        </form>
    </div>

    <!-- Grid / List Grid View -->
    <div class="row g-3">
        <?php if (empty($suppliers)): ?>
            <div class="col-12 text-center py-5 card bg-dark border-secondary border-opacity-10">
                <i class="bi bi-people text-muted fs-1 mb-3"></i>
                <h5 class="text-white font-weight-700">Tedarikçi Bulunamadı</h5>
                <p class="text-muted fs-8">Kriterlerinize uygun tedarikçi kaydı bulunamadı veya henüz eklenmedi.</p>
            </div>
        <?php else: ?>
            <?php foreach ($suppliers as $s): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card bg-dark border-secondary border-opacity-10 p-3 h-100 position-relative hover-lift">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-<?= $s['is_active'] ? 'success' : 'secondary' ?> bg-opacity-10 text-<?= $s['is_active'] ? 'success' : 'secondary' ?> mb-2">
                                    <?= $s['is_active'] ? 'Aktif' : 'Pasif' ?>
                                </span>
                                <h5 class="font-weight-800 text-white mb-1"><a href="<?= url('/admin/purchasing/suppliers/show?id=' . $s['id']) ?>" class="text-white text-decoration-none"><?= htmlspecialchars($s['company_name']) ?></a></h5>
                                <p class="text-muted fs-8 mb-0"><i class="bi bi-tag-fill text-warning me-1"></i> Vergi No: <?= htmlspecialchars($s['tax_number'] ?? 'Belirtilmemiş') ?> (<?= htmlspecialchars($s['tax_office'] ?? '-') ?>)</p>
                            </div>
                            <div class="bg-warning bg-opacity-10 text-warning px-2 py-1 rounded fs-8 font-weight-800 d-flex align-items-center gap-1">
                                <i class="bi bi-star-fill text-warning"></i> <?= number_format((float)$s['score'], 1) ?>
                            </div>
                        </div>

                        <div class="border-top border-secondary border-opacity-10 pt-3 mt-2 fs-8">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Yetkili / Temsilci:</span>
                                <span class="text-white font-weight-600"><?= htmlspecialchars($s['contact_name'] ?? 'Yok') ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Telefon / Email:</span>
                                <span class="text-white font-weight-600"><?= htmlspecialchars($s['phone'] ?? '-') ?> / <?= htmlspecialchars($s['email'] ?? '-') ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Döviz Birimi / Ödeme:</span>
                                <span class="text-white font-weight-600 text-uppercase"><?= htmlspecialchars($s['currency']) ?> - <?= htmlspecialchars($s['payment_terms'] ?? 'Peşin') ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Ortalama Termin Süresi:</span>
                                <span class="text-white font-weight-600"><?= $s['lead_time'] ?> Gün</span>
                            </div>
                        </div>

                        <div class="border-top border-secondary border-opacity-10 pt-3 mt-3 d-flex gap-2">
                            <a href="<?= url('/admin/purchasing/suppliers/show?id=' . $s['id']) ?>" class="btn btn-sm btn-outline-warning w-100 rounded-pill"><i class="bi bi-eye me-1"></i> Detay & 360 Görünüm</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-secondary border-opacity-25 text-white">
            <div class="modal-header border-secondary border-opacity-10">
                <h5 class="modal-title font-weight-800 text-white" id="addSupplierModalLabel">Yeni Tedarikçi Kartı Oluştur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('/admin/purchasing/suppliers/create') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="modal-body row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Şirket / Tedarikçi Adı *</label>
                        <input type="text" name="company_name" class="form-control bg-dark border-secondary border-opacity-25 text-white" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Yetkili / Temsilci Adı</label>
                        <input type="text" name="contact_name" class="form-control bg-dark border-secondary border-opacity-25 text-white">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Vergi Numarası</label>
                        <input type="text" name="tax_number" class="form-control bg-dark border-secondary border-opacity-25 text-white">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Vergi Dairesi</label>
                        <input type="text" name="tax_office" class="form-control bg-dark border-secondary border-opacity-25 text-white">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Telefon Numarası</label>
                        <input type="text" name="phone" class="form-control bg-dark border-secondary border-opacity-25 text-white">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">E-Posta Adresi</label>
                        <input type="email" name="email" class="form-control bg-dark border-secondary border-opacity-25 text-white">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Ülke</label>
                        <input type="text" name="country" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="Türkiye">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Şehir</label>
                        <input type="text" name="city" class="form-control bg-dark border-secondary border-opacity-25 text-white">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Döviz Birimi</label>
                        <select name="currency" class="form-select bg-dark border-secondary border-opacity-25 text-white">
                            <option value="TRY">TRY (₺)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Ödeme Vadesi</label>
                        <input type="text" name="payment_terms" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="Örn: 30 Gün, Peşin">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Termin Süresi (Gün)</label>
                        <input type="number" name="lead_time" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="7">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Çalışma Durumu</label>
                        <select name="is_active" class="form-select bg-dark border-secondary border-opacity-25 text-white">
                            <option value="1">Aktif</option>
                            <option value="0">Pasif</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Tedarikçi Puanı (5 üzerinden)</label>
                        <input type="number" step="0.1" max="5.0" min="1.0" name="score" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="5.0">
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-10">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning font-weight-600">Tedarikçiyi Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function exportData(format) {
    alert(format.toUpperCase() + ' formatında dışa aktarma işlemi başlatıldı.');
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
