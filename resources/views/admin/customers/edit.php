<?php
use App\Helpers\ComponentHelper;

$title = "Müşteri Düzenle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Müşteriler' => url('/admin/customers'),
        'Müşteri Detayı' => url('/admin/customers/show?id=' . $customer['id']),
        'Düzenle' => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Müşteri Düzenle: <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></h2>
        <a href="<?= url('/admin/customers/show?id=' . $customer['id']) ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Müşteriye Geri Dön</a>
    </div>
</div>

<form action="<?= url('/admin/customers/edit') ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" value="<?= $customer['id'] ?>">

    <div class="row g-4">
        <!-- SOL KOLON: KİŞİSEL BİLGİLER -->
        <div class="col-lg-8">
            <div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-person-fill me-2 text-warning"></i>Kişisel Bilgiler</h4>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Müşteri Adı</label>
                        <input type="text" name="first_name" required value="<?= htmlspecialchars($customer['first_name']) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Müşteri Soyadı</label>
                        <input type="text" name="last_name" required value="<?= htmlspecialchars($customer['last_name']) ?>" class="search-input w-100 text-white">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">E-Posta Adresi</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($customer['email']) ?>" class="search-input w-100 text-white">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Telefon Numarası</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" class="search-input w-100 text-white">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Hesap Şifresi (Sadece değiştirmek isterseniz girin)</label>
                    <input type="password" name="password" class="search-input w-100 text-white" placeholder="••••••••">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Profil Fotoğrafı (Avatar)</label>
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border); display: flex; align-items: center; justify-content: center;">
                            <?php if (!empty($customer['avatar'])): ?>
                                <img src="<?= url($customer['avatar']) ?>" style="width:100%; height:100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="bi bi-person-fill text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="avatar" class="form-control bg-dark border-secondary text-white fs-7">
                    </div>
                </div>

                <div class="form-check form-switch fs-7 mb-2 text-muted mt-4">
                    <input class="form-check-input" type="checkbox" name="kvkk_consent" id="kvkkCheck" value="1" <?= $customer['kvkk_consent'] ? 'checked' : '' ?>>
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
                            <option value="<?= $g['id'] ?>" <?= $customer['customer_group_id'] == $g['id'] ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Müşteri Durumu</label>
                    <select name="status" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <option value="active" <?= $customer['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="passive" <?= $customer['status'] === 'passive' ? 'selected' : '' ?>>Pasif</option>
                        <option value="VIP" <?= $customer['status'] === 'VIP' ? 'selected' : '' ?>>VIP</option>
                        <option value="risky" <?= $customer['status'] === 'risky' ? 'selected' : '' ?>>Riskli</option>
                        <option value="suspended" <?= $customer['status'] === 'suspended' ? 'selected' : '' ?>>Askıya Alınmış</option>
                    </select>
                </div>
            </div>

            <!-- 2. Etiketler -->
            <div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6">Sistem Etiketleri</h4>
                <div class="fs-7 text-muted">
                    <?php foreach ($tags as $t): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="tag_ids[]" value="<?= $t['id'] ?>" id="tag_<?= $t['id'] ?>" <?= in_array($t['id'], $customerTags) ? 'checked' : '' ?>>
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
                <button type="submit" class="btn btn-warning text-dark border-0 fs-6 w-100 py-3 font-weight-700">Değişiklikleri Kaydet</button>
                <a href="<?= url('/admin/customers/show?id=' . $customer['id']) ?>" class="btn btn-outline-secondary w-100 mt-2">Vazgeç</a>
            </div>
        </div>
    </div>
</form>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
