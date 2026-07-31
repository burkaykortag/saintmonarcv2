<?php
use App\Helpers\Ui;

$title = "Satıcı Ödemeleri - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="mb-4">
        <h2 class="font-weight-700 m-0">Satıcı Hak Ediş & Ödemeleri</h2>
        <p class="text-muted mb-0 fs-7">Satıcılara yapılan ödemeleri, transfer dekontlarını ve ödeme taleplerini izleyin.</p>
    </div>

    <!-- Payments DataGrid -->
    <div class="card p-4 border-0">
        <?php
        $headers = ['Ödeme ID', 'Satıcı Adı', 'Banka Adı', 'IBAN Numarası', 'Tutar', 'Ödeme Tarihi', 'Durum'];
        $rows = [];
        foreach ($payments as $p) {
            $statusBadge = '';
            switch ($p['status']) {
                case 'paid':
                    $statusBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2">Ödendi</span>';
                    break;
                case 'pending':
                    $statusBadge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2">Onay Bekliyor</span>';
                    break;
                case 'processing':
                    $statusBadge = '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 py-1 px-2">İşlemde</span>';
                    break;
                default:
                    $statusBadge = '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 py-1 px-2">' . strtoupper($p['status']) . '</span>';
                    break;
            }

            $rows[] = [
                $p['id'],
                '<strong>' . htmlspecialchars($p['vendor_name']) . '</strong>',
                htmlspecialchars($p['bank_name']),
                '<code>' . htmlspecialchars($p['iban']) . '</code>',
                '₺' . number_format((float)$p['amount'], 2, ',', '.'),
                $p['payment_date'] ?? 'Bekliyor',
                $statusBadge
            ];
        }

        echo Ui::datagrid([
            'headers' => $headers,
            'rows' => $rows,
            'emptyMessage' => 'Sistemde kayıtlı ödeme veya hak ediş kaydı bulunmamaktadır.'
        ]);
        ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
