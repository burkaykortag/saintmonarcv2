<?php
use App\Helpers\Ui;

$title = "Satıcı Cüzdan Yönetimi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="mb-4">
        <a href="<?= url('/admin/vendors') ?>" class="text-warning text-decoration-none fs-7"><i class="bi bi-arrow-left me-1"></i> Satıcılara Geri Dön</a>
        <h2 class="font-weight-700 mt-2 m-0">Satıcı Cüzdan Kontrolü</h2>
        <p class="text-muted mb-0 fs-7">Cüzdan bakiyesi, bekleyen ödemeler ve finansal hareket detaylarını takip edin.</p>
    </div>

    <!-- Wallet Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <?= Ui::card([
                'title' => 'Mevcut Bakiye',
                'value' => '₺' . number_format((float)($wallet['balance'] ?? 0.00), 2, ',', '.'),
                'icon' => 'wallet2',
                'iconColor' => 'var(--sm-success)'
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= Ui::card([
                'title' => 'Bekleyen Ödeme (Blokeli Bakiye)',
                'value' => '₺' . number_format((float)($wallet['pending_payout'] ?? 0.00), 2, ',', '.'),
                'icon' => 'clock-history',
                'iconColor' => 'var(--sm-warning)'
            ]) ?>
        </div>
    </div>

    <!-- Actions Panel -->
    <div class="card p-4 border-0 mb-4">
        <h4 class="font-weight-700 fs-6 mb-3">Hızlı İşlemler</h4>
        <div class="d-flex gap-2">
            <?= Ui::button(['text' => 'Bakiye Aktar (IBAN)', 'type' => 'gold', 'icon' => 'send']) ?>
            <?= Ui::button(['text' => 'Cüzdan Hareket Raporu Al', 'type' => 'outline', 'icon' => 'file-earmark-text']) ?>
        </div>
    </div>

    <!-- Empty State for Transactions (Mock) -->
    <?= Ui::emptyState([
        'message' => 'Cüzdanda henüz işlem geçmişi bulunmamaktadır.'
    ]) ?>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
