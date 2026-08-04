<?php
$title = 'Yönetici Kullanıcıları – SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1 text-white"><i class="bi bi-people me-2" style="color: var(--sm-gold);"></i>Yönetici Kullanıcıları</h3>
        <p class="text-muted mb-0">Platform yöneticilerini, rol atamalarını ve hiyerarşik yetki seviyelerini yönetin.</p>
    </div>
    <div>
        <a href="<?= url('/admin/users/create') ?>" class="btn btn-primary px-4 py-2 fw-semibold" style="background: linear-gradient(135deg, var(--sm-gold), #b89228); border: none; color: #000;">
            <i class="bi bi-person-plus-fill me-2"></i> Yeni Yönetici Ekle
        </a>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success bg-dark text-success border-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger bg-dark text-danger border-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-lg" style="background-color: var(--sm-dark-card); border: 1px solid var(--sm-border) !important; border-radius: 14px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-white" style="border-color: rgba(212,175,55,0.1);">
                <thead style="background: rgba(255,255,255,0.02); color: var(--sm-gold); font-size: 13px; text-transform: uppercase;">
                    <tr>
                        <th class="ps-4">Yönetici Bilgisi</th>
                        <th>Atanan Rol</th>
                        <th>Hiyerarşik Seviye</th>
                        <th>Durum</th>
                        <th>Kayıt Tarihi</th>
                        <th class="text-end pe-4">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-people display-4 d-block mb-3 opacity-25"></i>
                                Kayıtlı yönetici kullanıcısı bulunamadı.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle d-flex justify-content-center align-items-center font-bold text-dark" style="width: 40px; height: 40px; background: var(--sm-gold); font-weight: 600;">
                                            <?= strtoupper(substr($u['username'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-white"><?= htmlspecialchars($u['username']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-warning text-warning px-3 py-2">
                                        <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($u['role_name'] ?? 'Rol Atanmamış') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary px-2 py-1">
                                        Priority: <?= (int)($u['role_priority'] ?? 0) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($u['is_active'])): ?>
                                        <span class="badge bg-success-subtle text-success px-3 py-1"><i class="bi bi-circle-fill me-1 small"></i> Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger px-3 py-1"><i class="bi bi-circle-fill me-1 small"></i> Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= date('d.m.Y H:i', strtotime($u['created_at'])) ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <?php if (!empty($u['can_impersonate'])): ?>
                                            <form action="<?= url('/admin/users/impersonate') ?>" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Kullanıcıya Geç (Impersonate)">
                                                    <i class="bi bi-person-bounding-box me-1"></i> Geçiş Yap
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (!empty($u['can_manage'])): ?>
                                            <a href="<?= url('/admin/users/edit?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-light" title="Düzenle">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if (empty($u['is_super'])): ?>
                                                <form action="<?= url('/admin/users/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
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

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
