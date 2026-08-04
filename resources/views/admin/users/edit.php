<?php
$title = 'Yönetici Düzenle – SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1 text-white"><i class="bi bi-pencil-square me-2" style="color: var(--sm-gold);"></i>Yönetici Düzenle</h3>
        <p class="text-muted mb-0">Platform yöneticisinin bilgilerini ve atanmış rolünü güncelleyin.</p>
    </div>
    <a href="<?= url('/admin/users') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kullanıcılara Dön
    </a>
</div>

<div class="row">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-lg" style="background-color: var(--sm-dark-card); border: 1px solid var(--sm-border) !important; border-radius: 14px;">
            <div class="card-body p-4">
                <form action="<?= url('/admin/users/edit') ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" value="<?= $user['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label text-white fw-medium">Kullanıcı Adı</label>
                        <input type="text" name="username" class="form-control bg-dark text-white border-secondary" required value="<?= htmlspecialchars($user['username']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white fw-medium">E-posta Adresi</label>
                        <input type="email" name="email" class="form-control bg-dark text-white border-secondary" required value="<?= htmlspecialchars($user['email']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white fw-medium">Atanan Rol</label>
                        <select name="role_id" class="form-select bg-dark text-white border-secondary">
                            <option value="">-- Rol Değiştirme --</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= ($assignedRoleId == $r['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['name']) ?> (Priority: <?= $r['priority'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-white fw-medium">Hesap Durumu</label>
                        <select name="is_active" class="form-select bg-dark text-white border-secondary">
                            <option value="1" <?= !empty($user['is_active']) ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= empty($user['is_active']) ? 'selected' : '' ?>>Pasif</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 py-2 w-100 fw-semibold" style="background: linear-gradient(135deg, var(--sm-gold), #b89228); border: none; color: #000;">
                        <i class="bi bi-check-circle me-1"></i> Değişiklikleri Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
