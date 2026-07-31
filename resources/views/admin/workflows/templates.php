<?php
use App\Helpers\Ui;

$title = "Şablon Merkezi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="mb-4">
        <a href="<?= url('/admin/workflows') ?>" class="text-warning text-decoration-none fs-7"><i class="bi bi-arrow-left me-1"></i> İş Akışlarına Geri Dön</a>
        <h2 class="font-weight-700 mt-2 m-0">Hazır İş Akışı Şablonları</h2>
        <p class="text-muted mb-0 fs-7">Sık kullanılan e-ticaret süreçleri için önceden tasarlanmış hazır otomasyon şablonlarını tek tıkla kurun.</p>
    </div>

    <div class="row g-3">
        <!-- VIP Müşteri Şablonu -->
        <div class="col-md-4">
            <div class="card p-4 border-0 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2">E-Ticaret CRM</span>
                        <i class="bi bi-star-fill text-warning fs-5"></i>
                    </div>
                    <h4 class="font-weight-700 fs-5 mb-2">VIP Müşteri Karşılama</h4>
                    <p class="text-muted fs-7">1000 TL üzeri ilk alışverişini tamamlayan müşterilere VIP etiketi ekler ve 50 TL cüzdan puanı tanımlar.</p>
                </div>
                <div class="mt-4 pt-3 border-top border-secondary border-opacity-10">
                    <?= Ui::button(['text' => 'Şablonu Kullan', 'type' => 'gold', 'className' => 'w-100', 'onclick' => 'useTemplate(1)']) ?>
                </div>
            </div>
        </div>

        <!-- Sepet Terk Şablonu -->
        <div class="col-md-4">
            <div class="card p-4 border-0 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2">Satış Artırma</span>
                        <i class="bi bi-cart-x-fill text-danger fs-5"></i>
                    </div>
                    <h4 class="font-weight-700 fs-5 mb-2">Terk Edilmiş Sepet Hatırlatıcısı</h4>
                    <p class="text-muted fs-7">Kullanıcı sepetine ürün ekleyip 2 saat boyunca işlem yapmazsa, otomatik sepet hatırlatma SMS\'i gönderir.</p>
                </div>
                <div class="mt-4 pt-3 border-top border-secondary border-opacity-10">
                    <?= Ui::button(['text' => 'Şablonu Kullan', 'type' => 'gold', 'className' => 'w-100', 'onclick' => 'useTemplate(2)']) ?>
                </div>
            </div>
        </div>

        <!-- Düşük Stok Uyarısı -->
        <div class="col-md-4">
            <div class="card p-4 border-0 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 py-1 px-2">Envanter</span>
                        <i class="bi bi-box-seam-fill text-info fs-5"></i>
                    </div>
                    <h4 class="font-weight-700 fs-5 mb-2">Kritik Stok Uyarı Sistemi</h4>
                    <p class="text-muted fs-7">Herhangi bir ürünün stok miktarı 5 adetin altına indiğinde, satın alma yöneticisine Slack bildirimi atar.</p>
                </div>
                <div class="mt-4 pt-3 border-top border-secondary border-opacity-10">
                    <?= Ui::button(['text' => 'Şablonu Kullan', 'type' => 'gold', 'className' => 'w-100', 'onclick' => 'useTemplate(3)']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function useTemplate(id) {
        alert('Seçilen şablon başarıyla kuruldu!');
    }
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
