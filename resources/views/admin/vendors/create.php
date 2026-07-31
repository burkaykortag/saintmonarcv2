<?php
use App\Helpers\Ui;

$title = "Yeni Satıcı Ekle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="mb-4">
        <a href="<?= url('/admin/vendors') ?>" class="text-warning text-decoration-none fs-7"><i class="bi bi-arrow-left me-1"></i> Satıcılara Geri Dön</a>
        <h2 class="font-weight-700 mt-2 m-0">Yeni Satıcı (Vendor) Ekle</h2>
        <p class="text-muted mb-0 fs-7">Sisteme yeni satıcı firma kaydı açın ve komisyon detaylarını yapılandırın.</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger border-0 text-danger bg-danger bg-opacity-10 py-3 mb-4 rounded-3 fs-7" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card p-4 border-0" style="max-width: 800px;">
        <form action="<?= url('/admin/vendors/create') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <?= Ui::input([
                        'label' => 'Satıcı Adı / Firma Ünvanı',
                        'name' => 'name',
                        'placeholder' => 'Örn: Monarc Teknoloji A.Ş.',
                        'required' => true
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= Ui::input([
                        'label' => 'E-posta Adresi',
                        'name' => 'email',
                        'type' => 'email',
                        'placeholder' => 'Örn: info@monarc.com',
                        'required' => true
                    ]) ?>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <?= Ui::input([
                        'label' => 'Telefon Numarası',
                        'name' => 'phone',
                        'placeholder' => 'Örn: 0212 999 88 77'
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= Ui::select([
                        'label' => 'Başlangıç Durumu',
                        'name' => 'status',
                        'options' => [
                            'pending' => 'Onay Bekliyor',
                            'active' => 'Aktif',
                            'suspended' => 'Askıya Alınmış'
                        ]
                    ]) ?>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <?= Ui::select([
                        'label' => 'Komisyon Türü',
                        'name' => 'commission_type',
                        'options' => [
                            'percentage' => 'Yüzdelik Oran (%)',
                            'flat' => 'Sabit Bedel (TL)'
                        ]
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= Ui::input([
                        'label' => 'Komisyon Değeri (Oran veya Tutar)',
                        'name' => 'commission_rate',
                        'type' => 'number',
                        'placeholder' => 'Örn: 12.50',
                        'required' => true
                    ]) ?>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fs-7 text-muted">Özel Notlar</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Firma hakkında ek notlar, sözleşme detayları..."></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('/admin/vendors') ?>" class="btn btn-secondary border-0">Vazgeç</a>
                <?= Ui::button(['text' => 'Kaydet', 'type' => 'gold']) ?>
            </div>
        </form>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
