<?php
$title = "Güvenli Ödeme - SaintMonarc";

?>

<div class="container py-4 text-dark">
    <div class="mb-4">
        <h2 class="font-weight-800 m-0" style="font-weight: 800; color: var(--store-navy);"><i class="bi bi-shield-lock-fill text-success me-1"></i> Güvenli Ödeme</h2>
        <p class="text-muted mb-0 fs-7">Lütfen bilgilerinizi eksiksiz doldurarak siparişinizi tamamlayın.</p>
    </div>

    <div class="row g-4">
        <!-- Sol Taraf: Step Wizard Formu -->
        <div class="col-lg-8">
            <div class="bg-white p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm mb-3">
                
                <!-- Adım 1: Fatura & Teslimat Adresi -->
                <div class="mb-4 pb-4 border-bottom">
                    <h5 class="font-weight-700 fs-6 mb-3"><span class="badge bg-dark rounded-circle me-2">1</span> Teslimat Bilgileri</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fs-7">Adı *</label>
                            <input type="text" class="form-control form-control-sm rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-7">Soyadı *</label>
                            <input type="text" class="form-control form-control-sm rounded-3" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fs-7">Açık Adres *</label>
                            <textarea class="form-control form-control-sm rounded-3" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-7">Şehir *</label>
                            <input type="text" class="form-control form-control-sm rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-7">Telefon *</label>
                            <input type="tel" class="form-control form-control-sm rounded-3" required>
                        </div>
                    </div>
                </div>

                <!-- Adım 2: Kargo Seçeneği -->
                <div class="mb-4 pb-4 border-bottom">
                    <h5 class="font-weight-700 fs-6 mb-3"><span class="badge bg-dark rounded-circle me-2">2</span> Kargo Yöntemi</h5>
                    <div class="form-check p-3 border rounded-3 mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <input class="form-check-input ms-0 me-2" type="radio" name="shipping" id="shipStandard" checked>
                            <label class="form-check-label fs-7 font-weight-600" for="shipStandard">Standart Teslimat</label>
                            <small class="text-muted d-block ms-4" style="font-size: 11px;">Tahmini Teslim: 2-3 iş günü</small>
                        </div>
                        <span class="text-success font-weight-700 fs-7">Ücretsiz</span>
                    </div>
                </div>

                <!-- Adım 3: Ödeme Yöntemi -->
                <div class="mb-3">
                    <h5 class="font-weight-700 fs-6 mb-3"><span class="badge bg-dark rounded-circle me-2">3</span> Ödeme Yöntemi</h5>
                    <div class="form-check p-3 border rounded-3 mb-2">
                        <input class="form-check-input ms-0 me-2" type="radio" name="payment" id="payCard" checked>
                        <label class="form-check-label fs-7 font-weight-600" for="payCard">Kredi / Banka Kartı</label>
                    </div>
                    
                    <!-- Kart Detayları -->
                    <div class="row g-3 mt-1 bg-light p-3 rounded-3 border">
                        <div class="col-md-8">
                            <label class="form-label fs-7">Kart Numarası</label>
                            <input type="text" class="form-control form-control-sm rounded-3" placeholder="0000 0000 0000 0000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-7">CVC / CVV</label>
                            <input type="text" class="form-control form-control-sm rounded-3" placeholder="123">
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Sağ Taraf: Sepet Özeti -->
        <div class="col-lg-4">
            <div class="bg-white p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm mb-3">
                <h5 class="font-weight-800 mb-4" style="font-weight: 800;">Sipariş Özeti</h5>

                <div class="d-flex justify-content-between mb-2 fs-7">
                    <span class="text-muted">SaintMonarc Premium Ürün</span>
                    <span class="text-dark font-weight-600">1.499,00 TL</span>
                </div>
                <div class="d-flex justify-content-between mb-2 fs-7">
                    <span class="text-muted">SM Koruyucu Kılıf</span>
                    <span class="text-dark font-weight-600">249,00 TL</span>
                </div>
                <hr class="my-3">
                <div class="d-flex justify-content-between mb-4">
                    <span class="font-weight-700 text-dark">Genel Toplam</span>
                    <span class="font-weight-800 text-dark fs-5" style="font-weight: 800;">1.748,00 TL</span>
                </div>

                <button class="btn w-100 py-3 rounded-pill text-white font-weight-600" style="background-color: var(--store-accent);" onclick="completeOrder()">Siparişi Tamamla (1.748,00 TL)</button>
            </div>
        </div>
    </div>
</div>

<script>
    function completeOrder() {
        alert('Siparişiniz başarıyla alındı! Teşekkür ederiz.');
        window.location.href = '<?= url('/') ?>';
    }
</script>


