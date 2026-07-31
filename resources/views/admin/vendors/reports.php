<?php
use App\Helpers\Ui;

$title = "Satıcı Raporları - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="mb-4">
        <h2 class="font-weight-700 m-0">Satıcı Satış & Komisyon Raporları</h2>
        <p class="text-muted mb-0 fs-7">Satıcı bazında toplam ciroları, komisyon gelirlerini ve net hak ediş tutarlarını inceleyin.</p>
    </div>

    <!-- Summary Statistics Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <?= Ui::card([
                'title' => 'Toplam Brüt Satış',
                'value' => '₺450,250.00',
                'icon' => 'currency-dollar',
                'iconColor' => 'var(--sm-gold)'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= Ui::card([
                'title' => 'Kazanılan Toplam Komisyon',
                'value' => '₺45,025.00',
                'icon' => 'percent',
                'iconColor' => 'var(--sm-warning)'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= Ui::card([
                'title' => 'Satıcılara Ödenen',
                'value' => '₺325,000.00',
                'icon' => 'cash-stack',
                'iconColor' => 'var(--sm-success)'
            ]) ?>
        </div>
        <div class="col-md-3">
            <?= Ui::card([
                'title' => 'Bekleyen Hak Ediş',
                'value' => '₺80,225.00',
                'icon' => 'hourglass-split',
                'iconColor' => 'var(--sm-info)'
            ]) ?>
        </div>
    </div>

    <!-- DataGrid List -->
    <div class="card p-4 border-0">
        <?php
        $headers = ['Satıcı ID', 'Satıcı Adı', 'E-posta', 'Toplam Sipariş', 'Toplam Ciro', 'Komisyon Tutarı', 'Net Hakediş'];
        $rows = [];
        foreach ($vendors as $v) {
            // Mock summary report stats per vendor
            $totalOrders = rand(10, 80);
            $totalGross = $totalOrders * rand(150, 450);
            $commission = round($totalGross * ($v['commission_rate'] / 100), 2);
            $netEarnings = $totalGross - $commission;

            $rows[] = [
                $v['id'],
                '<strong>' . htmlspecialchars($v['name']) . '</strong>',
                htmlspecialchars($v['email']),
                $totalOrders,
                '₺' . number_format($totalGross, 2, ',', '.'),
                '₺' . number_format($commission, 2, ',', '.'),
                '₺' . number_format($netEarnings, 2, ',', '.')
            ];
        }

        echo Ui::datagrid([
            'headers' => $headers,
            'rows' => $rows,
            'emptyMessage' => 'Rapor oluşturulacak satıcı verisi bulunamadı.'
        ]);
        ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
