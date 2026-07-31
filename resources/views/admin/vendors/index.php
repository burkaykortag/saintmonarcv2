<?php
use App\Helpers\Ui;

$title = "Satıcı (Vendor) Yönetimi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="font-weight-700 m-0">Satıcı (Vendor) Yönetimi</h2>
            <p class="text-muted mb-0 fs-7">Marketplace bünyesindeki satıcı firmaları, durumlarını ve komisyon tiplerini denetleyin.</p>
        </div>
        <div>
            <?= Ui::button([
                'text' => 'Yeni Satıcı Ekle',
                'type' => 'gold',
                'icon' => 'plus-circle',
                'onclick' => "window.location.href='" . url('/admin/vendors/create') . "'"
            ]) ?>
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <?= Ui::card([
                'title' => 'Toplam Satıcı',
                'value' => count($vendors),
                'icon' => 'people',
                'iconColor' => 'var(--sm-gold)'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= Ui::card([
                'title' => 'Aktif Satıcılar',
                'value' => count(array_filter($vendors, fn($v) => $v['status'] === 'active')),
                'icon' => 'check-circle',
                'iconColor' => 'var(--sm-success)'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= Ui::card([
                'title' => 'Onay Bekleyenler',
                'value' => count(array_filter($vendors, fn($v) => $v['status'] === 'pending')),
                'icon' => 'clock',
                'iconColor' => 'var(--sm-warning)'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= Ui::card([
                'title' => 'Ortalama Puan',
                'value' => '4.85 / 5.0',
                'icon' => 'star',
                'iconColor' => 'var(--sm-gold)'
            ]) ?>
        </div>
    </div>

    <!-- DataGrid of Vendors -->
    <div class="card p-4 border-0">
        <?php
        $headers = ['ID', 'Satıcı Adı', 'E-posta', 'Durum', 'Komisyon Türü', 'Komisyon Oranı', 'İşlemler'];
        $rows = [];
        foreach ($vendors as $v) {
            $statusBadge = '';
            switch ($v['status']) {
                case 'active':
                    $statusBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2">Aktif</span>';
                    break;
                case 'pending':
                    $statusBadge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2">Beklemede</span>';
                    break;
                case 'suspended':
                    $statusBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2">Askıda</span>';
                    break;
            }

            $actions = '
                <div class="d-flex gap-2">
                    <a href="' . url('/admin/vendors/edit?id=' . $v['id']) . '" class="btn btn-xs btn-outline-light"><i class="bi bi-pencil"></i></a>
                    <a href="' . url('/admin/vendors/wallet?vendor_id=' . $v['id']) . '" class="btn btn-xs btn-outline-warning"><i class="bi bi-wallet2"></i></a>
                </div>
            ';

            $rows[] = [
                $v['id'],
                '<strong>' . htmlspecialchars($v['name']) . '</strong>',
                htmlspecialchars($v['email']),
                $statusBadge,
                strtoupper($v['commission_type']),
                '%' . $v['commission_rate'],
                $actions
            ];
        }

        echo Ui::datagrid([
            'headers' => $headers,
            'rows' => $rows,
            'emptyMessage' => 'Sistemde kayıtlı satıcı bulunmamaktadır.'
        ]);
        ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
