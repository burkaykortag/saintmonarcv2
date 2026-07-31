<?php
use App\Helpers\Ui;

$title = "İş Akışı Otomasyonu - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="font-weight-700 m-0">İş Akışı & Süreç Otomasyon Motoru</h2>
            <p class="text-muted mb-0 fs-7">Sistem olaylarına bağlı tetikleyicilerle çalışan otomatik eylemler ve akış senaryoları tanımlayın.</p>
        </div>
        <div class="d-flex gap-2">
            <?= Ui::button([
                'text' => 'Şablon Merkezi',
                'type' => 'outline',
                'icon' => 'grid',
                'onclick' => "window.location.href='" . url('/admin/workflows/templates') . "'"
            ]) ?>
            <?= Ui::button([
                'text' => 'Yeni İş Akışı',
                'type' => 'gold',
                'icon' => 'plus-circle',
                'onclick' => "window.location.href='" . url('/admin/workflows/create') . "'"
            ]) ?>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <?= Ui::card([
                'title' => 'Kayıtlı Akışlar',
                'value' => count($workflows),
                'icon' => 'cpu',
                'iconColor' => 'var(--sm-gold)'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= Ui::card([
                'title' => 'Aktif Çalışanlar',
                'value' => count(array_filter($workflows, fn($w) => $w['status'] === 'active')),
                'icon' => 'play-circle',
                'iconColor' => 'var(--sm-success)'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= Ui::card([
                'title' => 'Başarılı Tetiklenmeler',
                'value' => '1,480',
                'icon' => 'check-all',
                'iconColor' => 'var(--sm-success)'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= Ui::card([
                'title' => 'Toplam Hata Oranı',
                'value' => '%0.45',
                'icon' => 'exclamation-circle',
                'iconColor' => 'var(--sm-error)'
            ]) ?>
        </div>
    </div>

    <!-- DataGrid of Workflows -->
    <div class="card p-4 border-0">
        <?php
        $headers = ['ID', 'Akış Adı', 'Tetikleyici (Trigger)', 'Çalışma Sayısı', 'Başarı Oranı', 'Durum', 'İşlemler'];
        $rows = [];
        foreach ($workflows as $w) {
            $statusBadge = '';
            switch ($w['status']) {
                case 'active':
                    $statusBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2">Aktif</span>';
                    break;
                case 'draft':
                    $statusBadge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2">Taslak</span>';
                    break;
                case 'paused':
                    $statusBadge = '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 py-1 px-2">Durdurulmuş</span>';
                    break;
            }

            $totalRuns = (int)($w['total_runs'] ?? 0);
            $totalSuccess = (int)($w['total_success'] ?? 0);
            $successRate = ($totalRuns > 0) ? round(($totalSuccess / $totalRuns) * 100, 1) . '%' : '-%';

            $actions = '
                <div class="d-flex gap-2">
                    <a href="' . url('/admin/workflows/edit?id=' . $w['id']) . '" class="btn btn-xs btn-outline-light"><i class="bi bi-pencil-square"></i> Editör</a>
                    <a href="' . url('/admin/workflows/logs?workflow_id=' . $w['id']) . '" class="btn btn-xs btn-outline-info"><i class="bi bi-list-columns"></i> Loglar</a>
                </div>
            ';

            $rows[] = [
                $w['id'],
                '<strong>' . htmlspecialchars($w['name']) . '</strong><br><small class="text-muted">' . htmlspecialchars($w['description'] ?? '') . '</small>',
                '<code>' . htmlspecialchars($w['trigger_type']) . '</code>',
                $totalRuns,
                $successRate,
                $statusBadge,
                $actions
            ];
        }

        echo Ui::datagrid([
            'headers' => $headers,
            'rows' => $rows,
            'emptyMessage' => 'Sistemde kayıtlı iş akışı bulunmamaktadır.'
        ]);
        ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
