<?php
use App\Helpers\ComponentHelper;

$title = "Müşteri Ekle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Müşteriler' => url('/admin/customers'),
        'Müşteri Ekle' => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Yeni Müşteri Ekle</h2>
        <a href="<?= url('/admin/customers') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Listeye Dön</a>
    </div>
</div>

<form action="<?= url('/admin/customers/create') ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <div class="row g-4">
        <!-- SOL KOLON: KİŞİSEL VE HESAP BİLGİLERİ -->
        <div class="col-lg-8">
            <div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-person-fill me-2 text-warning"></i>Kişisel Bilgiler</h4>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Müşteri Adı</label>
                        <input type="text" name="first_name" required class="search-input w-100 text-white" placeholder="örn: Ahmet">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Müşteri Soyadı</label>
                        <input type="text" name="last_name" required class="search-input w-100 text-white" placeholder="örn: Yılmaz">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">E-Posta Adresi</label>
                        <input type="email" name="email" required class="search-input w-100 text-white" placeholder="ahmet@example.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Telefon Numarası</label>
                        <input type="text" name="phone" class="search-input w-100 text-white" placeholder="0532...">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Hesap Şifresi</label>
                    <input type="password" name="password" class="search-input w-100 text-white" placeholder="••••••••">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Profil Fotoğrafı (Avatar)</label>
                    <input type="file" name="avatar" class="form-control bg-dark border-secondary text-white fs-7">
                </div>

                <div class="form-check form-switch fs-7 mb-2 text-muted mt-4">
                    <input class="form-check-input" type="checkbox" name="kvkk_consent" id="kvkkCheck" value="1">
                    <label class="form-check-label" for="kvkkCheck">KVKK Aydınlatma ve Rıza Metni onaylandı</label>
                </div>
            </div>
        </div>

        <!-- SAĞ KOLON: GRUP VE ETİKETLER -->
        <div class="col-lg-4">
            <!-- 1. Grup Seçimi -->
            <div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6">Müşteri Grubu</h4>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Grup Seçin</label>
                    <select name="customer_group_id" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <?php foreach ($groups as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Müşteri Başlangıç Durumu</label>
                    <select name="status" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <option value="active">Aktif</option>
                        <option value="passive">Pasif</option>
                        <option value="VIP">VIP</option>
                        <option value="suspended">Askıda</option>
                    </select>
                </div>
            </div>

            <!-- 2. Etiketler -->
            <div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6">Sistem Etiketleri</h4>
                <div class="fs-7 text-muted">
                    <?php foreach ($tags as $t): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="tag_ids[]" value="<?= $t['id'] ?>" id="tag_<?= $t['id'] ?>">
                            <label class="form-check-label text-white" for="tag_<?= $t['id'] ?>">
                                <span class="badge" style="background: <?= $t['color'] ?>22; color: <?= $t['color'] ?>; border: 1px solid <?= $t['color'] ?>44;">
                                    <?= htmlspecialchars($t['name']) ?>
                                </span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Gönder -->
            <div class="card p-4 border-0 mb-4 text-center" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <button type="submit" class="btn btn-warning text-dark border-0 fs-6 w-100 py-3 font-weight-700">Müşteriyi Kaydet</button>
                <a href="<?= url('/admin/customers') ?>" class="btn btn-outline-secondary w-100 mt-2">Vazgeç</a>
            </div>
        </div>
    </div>
</form>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
