<?php
use App\Helpers\ComponentHelper;

$title = "Kargo Firması Düzenle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Lojistik & Kargo' => url('/admin/shipping'), 'Kargo Firmaları' => url('/admin/shipping/companies'), 'Firma Düzenle' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Kargo Firması & API Bağlantı Ayarları</h2>
        <a href="<?= url('/admin/shipping/companies') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Firmalar</a>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>

<div class="row g-4 text-white">
    <!-- FİRMA BİLGİLERİ DÜZENLEME -->
    <div class="col-md-6">
        <div class="card p-4 border-0 h-100" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-building text-warning me-2"></i>Firma Bilgilerini Güncelle</h4>
            <form action="<?= url('/admin/shipping/companies/update') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="id" value="<?= $company['id'] ?>">

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Firma Adı</label>
                    <input type="text" name="name" required class="search-input w-100 text-white" value="<?= htmlspecialchars($company['name']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Firma Kodu</label>
                    <input type="text" name="code" required class="search-input w-100 text-white" value="<?= htmlspecialchars($company['code']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Vergi Numarası</label>
                    <input type="text" name="tax_number" class="search-input w-100 text-white" value="<?= htmlspecialchars($company['tax_number'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Durum</label>
                    <select name="is_active" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <option value="1" <?= $company['is_active'] ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= !$company['is_active'] ? 'selected' : '' ?>>Pasif</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 w-100 py-3 font-weight-700 mt-2">Firma Bilgilerini Kaydet</button>
            </form>
        </div>
    </div>

    <!-- API ENTEGRASYON BİLGİLERİ DÜZENLEME -->
    <div class="col-md-6">
        <div class="card p-4 border-0 h-100" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-link-45deg text-warning me-2"></i>API Bağlantı Entegrasyon Parametreleri</h4>
            <form action="<?= url('/admin/shipping/companies/integration') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="company_id" value="<?= $company['id'] ?>">

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">API Endpoint URL</label>
                    <input type="url" name="api_url" required class="search-input w-100 text-white" placeholder="https://api.carrier.com/v1" value="<?= htmlspecialchars($integration['api_url'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">API Kullanıcı Adı (Username)</label>
                    <input type="text" name="username" class="search-input w-100 text-white" value="<?= htmlspecialchars($integration['username'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">API Parolası (Password)</label>
                    <input type="password" name="password" class="search-input w-100 text-white" value="<?= htmlspecialchars($integration['password'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">API Anahtarı (API Key / Auth Token)</label>
                    <input type="text" name="api_key" class="search-input w-100 text-white" value="<?= htmlspecialchars($integration['api_key'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">API Bağlantı Durumu</label>
                    <select name="is_active" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <option value="1" <?= ($integration['is_active'] ?? 1) ? 'selected' : '' ?>>Aktif Entegrasyon</option>
                        <option value="0" <?= !($integration['is_active'] ?? 1) ? 'selected' : '' ?>>Pasif Entegrasyon</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 w-100 py-3 font-weight-700 mt-2">API Bağlantısını Kaydet</button>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
