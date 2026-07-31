<?php
use App\Helpers\Ui;

$title = "Çalışma Geçmişi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="mb-4">
        <a href="<?= url('/admin/workflows') ?>" class="text-warning text-decoration-none fs-7"><i class="bi bi-arrow-left me-1"></i> İş Akışlarına Geri Dön</a>
        <h2 class="font-weight-700 mt-2 m-0">Otomasyon Çalışma Geçmişi</h2>
        <p class="text-muted mb-0 fs-7">Sistem genelinde tetiklenen tüm iş akışlarının çalışma sonuçlarını ve detaylarını listeleyin.</p>
    </div>

    <div class="card p-4 border-0">
        <?php
        $headers = ['Geçmiş ID', 'İş Akışı Adı', 'Çalışma Durumu', 'Başlama Zamanı', 'Tamamlanma Zamanı', 'Hata Detayı'];
        $rows = [];
        foreach ($history as $h) {
            $statusBadge = '';
            switch ($h['status']) {
                case 'success':
                    $statusBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2">Başarılı</span>';
                    break;
                case 'failed':
                    $statusBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2">Başarısız</span>';
                    break;
                case 'retrying':
                    $statusBadge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2">Yeniden Deneniyor</span>';
                    break;
            }

            $rows[] = [
                $h['id'],
                '<strong>' . htmlspecialchars($h['workflow_name']) . '</strong>',
                $statusBadge,
                $h['started_at'],
                $h['completed_at'] ?? '-',
                $h['error_message'] ? '<span class="text-danger fs-8">' . htmlspecialchars($h['error_message']) . '</span>' : '-'
            ];
        }

        echo Ui::datagrid([
            'headers' => $headers,
            'rows' => $rows,
            'emptyMessage' => 'Henüz kaydedilmiş çalışma geçmişi bulunmamaktadır.'
        ]);
        ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
