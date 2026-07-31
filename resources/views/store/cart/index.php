<?php
$title = "Alışveriş Sepetim - SaintMonarc";

?>

<div class="container py-4 text-dark">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="font-weight-800 m-0" style="font-weight: 800; color: var(--store-navy);">Alışveriş Sepetim</h2>
            <p class="text-muted mb-0 fs-7">Sepetinizdeki ürünleri yönetin ve ödemeye geçin.</p>
        </div>
        <a href="<?= url('/products') ?>" class="text-secondary text-decoration-none fs-7"><i class="bi bi-arrow-left me-1"></i> Alışverişe Devam Et</a>
    </div>

    <div class="row g-4">
        <!-- Sol Taraf: Sepet Kalemleri -->
        <div class="col-lg-8">
            <div class="bg-white p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm mb-3">
                
                <!-- Sepet Kalemi 1 -->
                <div class="row g-3 align-items-center py-3 border-bottom">
                    <div class="col-md-2 col-4">
                        <div class="bg-light rounded-3 py-4 text-center"><i class="bi bi-image text-muted fs-4"></i></div>
                    </div>
                    <div class="col-md-5 col-8">
                        <h6 class="font-weight-600 mb-1 text-dark">SaintMonarc Premium Ürün</h6>
                        <small class="text-muted d-block mb-2">Seçenek: 128 GB, Uzay Grisi</small>
                        <span class="text-dark font-weight-700 fs-6">1.499,00 TL</span>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="input-group input-group-sm rounded-pill border p-1" style="max-width: 120px;">
                            <button class="btn btn-link text-dark p-0 border-0 fs-5 px-2" onclick="changeQty(-1)"><i class="bi bi-dash"></i></button>
                            <input type="text" value="1" class="form-control border-0 text-center bg-transparent p-0 fs-7" readonly>
                            <button class="btn btn-link text-dark p-0 border-0 fs-5 px-2" onclick="changeQty(1)"><i class="bi bi-plus"></i></button>
                        </div>
                    </div>
                    <div class="col-md-2 col-6 text-end">
                        <button class="btn btn-link text-danger p-0 border-0"><i class="bi bi-trash fs-5"></i></button>
                    </div>
                </div>

                <!-- Sepet Kalemi 2 -->
                <div class="row g-3 align-items-center py-3">
                    <div class="col-md-2 col-4">
                        <div class="bg-light rounded-3 py-4 text-center"><i class="bi bi-image text-muted fs-4"></i></div>
                    </div>
                    <div class="col-md-5 col-8">
                        <h6 class="font-weight-600 mb-1 text-dark">SM Koruyucu Kılıf</h6>
                        <small class="text-muted d-block mb-2">Seçenek: Deri, Siyah</small>
                        <span class="text-dark font-weight-700 fs-6">249,00 TL</span>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="input-group input-group-sm rounded-pill border p-1" style="max-width: 120px;">
                            <button class="btn btn-link text-dark p-0 border-0 fs-5 px-2" onclick="changeQty(-1)"><i class="bi bi-dash"></i></button>
                            <input type="text" value="1" class="form-control border-0 text-center bg-transparent p-0 fs-7" readonly>
                            <button class="btn btn-link text-dark p-0 border-0 fs-5 px-2" onclick="changeQty(1)"><i class="bi bi-plus"></i></button>
                        </div>
                    </div>
                    <div class="col-md-2 col-6 text-end">
                        <button class="btn btn-link text-danger p-0 border-0"><i class="bi bi-trash fs-5"></i></button>
                    </div>
                </div>

            </div>
        </div>

        <!-- Sağ Taraf: Fiyat Özeti & Sipariş Tamamlama -->
        <div class="col-lg-4">
            <div class="bg-white p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm mb-3">
                <h5 class="font-weight-800 mb-4" style="font-weight: 800;">Sipariş Özeti</h5>

                <div class="d-flex justify-content-between mb-2 fs-7">
                    <span class="text-muted">Ara Toplam</span>
                    <span class="text-dark font-weight-600">1.748,00 TL</span>
                </div>
                <div class="d-flex justify-content-between mb-2 fs-7">
                    <span class="text-muted">Kargo Ücreti</span>
                    <span class="text-success font-weight-600">Ücretsiz</span>
                </div>
                <hr class="my-3">
                <div class="d-flex justify-content-between mb-4">
                    <span class="font-weight-700 text-dark">Genel Toplam</span>
                    <span class="font-weight-800 text-dark fs-5" style="font-weight: 800;">1.748,00 TL</span>
                </div>

                <a href="<?= url('/checkout') ?>" class="btn w-100 py-3 rounded-pill text-white font-weight-600" style="background-color: var(--store-accent);">Ödemeye Geç</a>
            </div>
        </div>
    </div>
</div>

<script>
    function changeQty(diff) {
        // mock change qty
    }
</script>


