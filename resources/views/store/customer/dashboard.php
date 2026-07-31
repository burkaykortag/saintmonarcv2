<?php
$title = "Müşteri Paneli - SaintMonarc";

?>

<div class="container py-4 text-dark">
    <div class="mb-4">
        <h2 class="font-weight-800 m-0" style="font-weight: 800; color: var(--store-navy);">Hesabım</h2>
        <p class="text-muted mb-0 fs-7">Profil bilgilerinizi, geçmiş siparişlerinizi ve cüzdan bakiyenizi buradan takip edebilirsiniz.</p>
    </div>

    <div class="row g-4">
        <!-- Sol Taraf: Hesap Menüsü -->
        <div class="col-lg-3">
            <div class="bg-white p-3 rounded-4 border border-secondary border-opacity-10 shadow-sm d-flex flex-column gap-2">
                <a href="#" class="btn btn-sm btn-dark text-start rounded-3 px-3"><i class="bi bi-person-fill me-2"></i> Profil Bilgilerim</a>
                <a href="#" class="btn btn-sm btn-outline-secondary text-start rounded-3 px-3 border-0"><i class="bi bi-cart-check me-2"></i> Siparişlerim</a>
                <a href="#" class="btn btn-sm btn-outline-secondary text-start rounded-3 px-3 border-0"><i class="bi bi-heart me-2"></i> Favorilerim</a>
                <a href="#" class="btn btn-sm btn-outline-secondary text-start rounded-3 px-3 border-0"><i class="bi bi-wallet2 me-2"></i> Cüzdanım & Puanlar</a>
                <a href="#" class="btn btn-sm btn-outline-secondary text-start rounded-3 px-3 border-0 text-danger"><i class="bi bi-box-arrow-right me-2"></i> Güvenli Çıkış</a>
            </div>
        </div>

        <!-- Sağ Taraf: Detay Arayüzü -->
        <div class="col-lg-9">
            <!-- Profil Özet Kartları -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="bg-white p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fs-8">Cüzdan Bakiyesi</span>
                            <h4 class="font-weight-800 m-0 mt-1">450,00 TL</h4>
                        </div>
                        <i class="bi bi-wallet2 text-success fs-3"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-white p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fs-8">Sadakat Puanı</span>
                            <h4 class="font-weight-800 m-0 mt-1">1,250 Puan</h4>
                        </div>
                        <i class="bi bi-gift text-primary fs-3"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-white p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fs-8">Aktif Siparişler</span>
                            <h4 class="font-weight-800 m-0 mt-1">1 Sipariş</h4>
                        </div>
                        <i class="bi bi-truck text-warning fs-3"></i>
                    </div>
                </div>
            </div>

            <!-- Geçmiş Siparişler -->
            <div class="bg-white p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm">
                <h5 class="font-weight-800 mb-3" style="font-weight: 800;">Son Siparişlerim</h5>
                
                <div class="table-responsive">
                    <table class="table align-middle fs-7">
                        <thead>
                            <tr>
                                <th>Sipariş No</th>
                                <th>Tarih</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                                <th>Detay</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#SM-9812739</strong></td>
                                <td>29.07.2026</td>
                                <td>1.748,00 TL</td>
                                <td><span class="badge bg-warning text-dark bg-opacity-10 border border-warning border-opacity-25 py-1 px-2">Kargoya Verildi</span></td>
                                <td><button class="btn btn-xs btn-outline-dark">Detay</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


