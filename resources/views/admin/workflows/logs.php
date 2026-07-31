<?php
use App\Helpers\Ui;

$title = "Çalışma Logları - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="mb-4">
        <a href="<?= url('/admin/workflows') ?>" class="text-warning text-decoration-none fs-7"><i class="bi bi-arrow-left me-1"></i> İş Akışlarına Geri Dön</a>
        <h2 class="font-weight-700 mt-2 m-0">Otomasyon Çalışma Günlükleri (Logs)</h2>
        <p class="text-muted mb-0 fs-7">Çalışan tüm otomasyonların adım adım işlem detaylarını ve sistem log çıktılarını inceleyin.</p>
    </div>

    <div class="card p-4 border-0">
        <?php
        $headers = ['Log ID', 'İş Akışı Adı', 'Seviye', 'Log Mesajı', 'Oluşturulma Tarihi'];
        $rows = [];
        foreach ($logs as $l) {
            $levelBadge = '';
            switch ($l['level']) {
                case 'info':
                    $levelBadge = '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 py-1 px-2">INFO</span>';
                    break;
                case 'error':
                    $levelBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2">ERROR</span>';
                    break;
                default:
                    $levelBadge = '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 py-1 px-2">' . strtoupper($l['level']) . '</span>';
                    break;
            }

            $rows[] = [
                $l['id'],
                '<strong>' . htmlspecialchars($l['workflow_name']) . '</strong>',
                $levelBadge,
                htmlspecialchars($l['message']),
                $l['created_at']
            ];
        }

        echo Ui::datagrid([
            'headers' => $headers,
            'rows' => $rows,
            'emptyMessage' => 'İncelenecek sistem günlüğü bulunmamaktadır.'
        ]);
        ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
