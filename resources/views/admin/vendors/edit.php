<?php
use App\Helpers\Ui;

$title = "Satıcı Düzenle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="mb-4">
        <a href="<?= url('/admin/vendors') ?>" class="text-warning text-decoration-none fs-7"><i class="bi bi-arrow-left me-1"></i> Satıcılara Geri Dön</a>
        <h2 class="font-weight-700 mt-2 m-0">Satıcıyı Düzenle: <?= htmlspecialchars($vendor['name']) ?></h2>
        <p class="text-muted mb-0 fs-7">Firma durumunu, komisyon tiplerini ve detaylı fatura/kayıt notlarını yönetin.</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger border-0 text-danger bg-danger bg-opacity-10 py-3 mb-4 rounded-3 fs-7" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card p-4 border-0" style="max-width: 800px;">
        <form action="<?= url('/admin/vendors/update?id=' . $vendor['id']) ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <?= Ui::input([
                        'label' => 'Satıcı Adı / Firma Ünvanı',
                        'name' => 'name',
                        'value' => $vendor['name'],
                        'required' => true
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= Ui::input([
                        'label' => 'E-posta Adresi',
                        'name' => 'email',
                        'type' => 'email',
                        'value' => $vendor['email'],
                        'required' => true
                    ]) ?>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <?= Ui::input([
                        'label' => 'Telefon Numarası',
                        'name' => 'phone',
                        'value' => $vendor['phone']
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= Ui::select([
                        'label' => 'Durum',
                        'name' => 'status',
                        'value' => $vendor['status'],
                        'options' => [
                            'pending' => 'Onay Bekliyor',
                            'active' => 'Aktif',
                            'suspended' => 'Askıya Alınmış',
                            'rejected' => 'Reddedilmiş'
                        ]
                    ]) ?>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <?= Ui::select([
                        'label' => 'Komisyon Türü',
                        'name' => 'commission_type',
                        'value' => $vendor['commission_type'],
                        'options' => [
                            'percentage' => 'Yüzdelik Oran (%)',
                            'flat' => 'Sabit Bedel (TL)'
                        ]
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= Ui::input([
                        'label' => 'Komisyon Değeri',
                        'name' => 'commission_rate',
                        'type' => 'number',
                        'value' => $vendor['commission_rate'],
                        'required' => true
                    ]) ?>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fs-7 text-muted">Özel Notlar</label>
                <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($vendor['notes'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('/admin/vendors') ?>" class="btn btn-secondary border-0">Vazgeç</a>
                <?= Ui::button(['text' => 'Değişiklikleri Kaydet', 'type' => 'gold']) ?>
            </div>
        </form>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
