<?php
$title = 'Yeni Yönetici Ekle – SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1 text-white"><i class="bi bi-person-plus me-2" style="color: var(--sm-gold);"></i>Yeni Yönetici Ekle</h3>
        <p class="text-muted mb-0">Hiyerarşik yetki seviyesine uygun olarak yeni bir platform yöneticisi tanımlayın.</p>
    </div>
    <a href="<?= url('/admin/users') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kullanıcılara Dön
    </a>
</div>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger bg-dark text-danger border-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-lg" style="background-color: var(--sm-dark-card); border: 1px solid var(--sm-border) !important; border-radius: 14px;">
            <div class="card-body p-4">
                <form action="<?= url('/admin/users/create') ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="mb-3">
                        <label class="form-label text-white fw-medium">Kullanıcı Adı</label>
                        <input type="text" name="username" class="form-control bg-dark text-white border-secondary" required placeholder="ör: ahmet_manager">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white fw-medium">E-posta Adresi</label>
                        <input type="email" name="email" class="form-control bg-dark text-white border-secondary" required placeholder="ör: ahmet@saintmonarc.com">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white fw-medium">Şifre</label>
                        <input type="password" name="password" class="form-control bg-dark text-white border-secondary" required placeholder="••••••••">
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-white fw-medium">Atanacak Rol</label>
                        <select name="role_id" class="form-select bg-dark text-white border-secondary" required>
                            <option value="">-- Rol Seçin --</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>">
                                    <?= htmlspecialchars($r['name']) ?> (Priority: <?= $r['priority'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">Sadece kendi hiyerarşik seviyenizin altındaki rolleri atayabilirsiniz.</small>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 py-2 w-100 fw-semibold" style="background: linear-gradient(135deg, var(--sm-gold), #b89228); border: none; color: #000;">
                        <i class="bi bi-check-circle me-1"></i> Yöneticiyi Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
