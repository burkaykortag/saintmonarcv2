<?php
use Resources\Views\Store\Components\UiStore;

$title = $product['name'] . " - SaintMonarc Premium Deneyimi";

// Prepare variables for dynamic presentation
$price = (float)($product['price'] ?? 1499.00);
$oldPrice = $price * 1.25;
$savings = $oldPrice - $price;
$sku = $product['sku'] ?? 'SM-PRM-001';
$stock = (int)($product['total_stock'] ?? 15);
?>

<div class="container py-4 text-dark">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb fs-7" style="font-size: 13px;">
            <li class="breadcrumb-item"><a href="<?= url('/') ?>" class="text-secondary text-decoration-none">Ana Sayfa</a></li>
            <li class="breadcrumb-item"><a href="<?= url('/products') ?>" class="text-secondary text-decoration-none">Ürünler</a></li>
            <li class="breadcrumb-item active text-dark" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- SOL PANEL: Premium Media Gallery (Carousel, Zoom, Video, 360) -->
        <div class="col-lg-7">
            <div class="position-relative bg-white p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm text-center mb-3 overflow-hidden">
                <!-- Badges System -->
                <div class="position-absolute top-3 start-3 d-flex flex-column gap-1 align-items-start" style="z-index: 10;">
                    <?= UiStore::badge('ai', 'AI Seçimi') ?>
                    <?= UiStore::badge('discount', '%25 İndirim') ?>
                    <?= UiStore::badge('stock', 'Hızlı Gönderi') ?>
                </div>

                <div class="position-absolute top-3 end-3" style="z-index: 10;">
                    <?= UiStore::badge('hot', 'Çok Popüler') ?>
                </div>

                <!-- Main Gallery Area -->
                <div id="mainGalleryViewer" style="height: 480px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; border-radius: 12px; position: relative;">
                    <i class="bi bi-image text-muted display-1" id="mainGalleryIcon"></i>
                    
                    <!-- 360 Degree View Overlay Button -->
                    <button class="btn btn-dark bg-opacity-75 rounded-pill position-absolute bottom-3 start-3 fs-8 py-1.5 px-3 border border-secondary border-opacity-25" onclick="trigger360()"><i class="bi bi-arrow-repeat me-1"></i> 360° Görünüm</button>
                    <!-- Video Play Overlay Button -->
                    <button class="btn btn-dark bg-opacity-75 rounded-pill position-absolute bottom-3 end-3 fs-8 py-1.5 px-3 border border-secondary border-opacity-25" onclick="playVideo()"><i class="bi bi-play-circle-fill me-1"></i> Tanıtım Videosu</button>
                </div>
            </div>

            <!-- Thumbnail Slider -->
            <div class="row g-2 mb-4">
                <div class="col-3">
                    <div class="border rounded-3 p-2 bg-white text-center cursor-pointer active-thumbnail" onclick="changeImage(0)"><i class="bi bi-image text-muted fs-3"></i></div>
                </div>
                <div class="col-3">
                    <div class="border rounded-3 p-2 bg-white text-center cursor-pointer" onclick="changeImage(1)"><i class="bi bi-image text-muted fs-3 text-primary"></i></div>
                </div>
                <div class="col-3">
                    <div class="border rounded-3 p-2 bg-white text-center cursor-pointer" onclick="changeImage(2)"><i class="bi bi-image text-muted fs-3 text-success"></i></div>
                </div>
                <div class="col-3">
                    <div class="border rounded-3 p-2 bg-white text-center cursor-pointer" onclick="changeImage(3)"><i class="bi bi-image text-muted fs-3 text-danger"></i></div>
                </div>
            </div>
        </div>

        <!-- SAĞ PANEL: Sticky Satın Alma Kutusu (Buy Box) -->
        <div class="col-lg-5">
            <div class="bg-white p-4 rounded-4 border border-secondary border-opacity-10 shadow-sm sticky-top" style="top: 90px; z-index: 100;">
                
                <!-- Social Proof HUD -->
                <div class="d-flex align-items-center justify-content-between mb-3 bg-light p-2 rounded-3 border border-secondary border-opacity-10 fs-8 text-muted" style="font-size: 12px;">
                    <span><i class="bi bi-eye text-primary me-1"></i> Son 24 saatte <strong>382</strong> kişi gördü</span>
                    <span><i class="bi bi-bag-check text-success me-1"></i> Bugün <strong>18</strong> adet satıldı</span>
                </div>

                <div>
                    <small class="text-muted fs-8">SKU: <span id="productSku"><?= htmlspecialchars($sku) ?></span></small>
                    <h3 class="font-weight-800 mt-1 mb-2" style="font-weight: 800; color: var(--store-navy); letter-spacing: -1px;"><?= htmlspecialchars($product['name']) ?></h3>
                    
                    <!-- Yıldızlar ve Yorum Linki -->
                    <div class="d-flex align-items-center gap-2 mb-3 fs-7">
                        <div class="text-warning">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-half"></i>
                        </div>
                        <span class="font-weight-700 text-dark">4.7</span>
                        <a href="#reviewsSection" class="text-secondary text-decoration-none hover-light">(48 Müşteri Değerlendirmesi)</a>
                    </div>

                    <!-- Fiyat Kartı (Price Card) -->
                    <div class="mb-4 bg-light p-3 rounded-3 border border-secondary border-opacity-10">
                        <div class="d-flex align-items-baseline gap-2">
                            <span class="text-muted text-decoration-line-through fs-7"><?= number_format($oldPrice, 2, ',', '.') ?> TL</span>
                            <span class="fs-2 font-weight-800 text-danger" id="productPrice" style="font-weight: 800;"><?= number_format($price, 2, ',', '.') ?> TL</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 fs-8 text-muted" style="font-size: 13px;">
                            <span class="text-success font-weight-600"><i class="bi bi-tag-fill me-1"></i> Toplam Kazancınız: <?= number_format($savings, 2, ',', '.') ?> TL</span>
                            <span>+<?= (int)($price * 0.05) ?> Sadakat Puanı</span>
                        </div>
                    </div>

                    <!-- Varyant Seçici (Variant Selector) -->
                    <div class="mb-4">
                        <h6 class="font-weight-700 fs-7 mb-2">Renk / Model Seçeneği:</h6>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-dark rounded-pill px-3 active variant-btn" onclick="selectVariant('Uzay Grisi', <?= $price ?>, '<?= $sku ?>-GRY', <?= $stock ?>)">Uzay Grisi</button>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 variant-btn" onclick="selectVariant('Gümüş', <?= $price * 1.05 ?>, '<?= $sku ?>-SLV', <?= $stock - 5 ?>)">Gümüş</button>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 variant-btn" onclick="selectVariant('Gece Mavisi', <?= $price * 1.10 ?>, '<?= $sku ?>-BLU', 2)">Gece Mavisi (Son 2 Ürün!)</button>
                        </div>
                    </div>

                    <!-- Kargo Hesaplama -->
                    <?= UiStore::deliveryCard([
                        'company' => 'MNG / Yurtiçi Kargo',
                        'date' => 'En geç yarın kargoda',
                        'price' => 'Ücretsiz Kargo'
                    ]) ?>

                    <!-- Taksit Hesaplama -->
                    <?= UiStore::installmentCalculator($price) ?>
                </div>

                <!-- Sepet ve Satın Al Butonları -->
                <div class="d-flex gap-2 pt-3 border-top">
                    <button class="btn btn-lg py-3 rounded-pill text-white font-weight-600 flex-grow-1" style="background-color: var(--store-accent);" onclick="addToCart(<?= $product['id'] ?>)">
                        <i class="bi bi-bag-plus me-2"></i> Sepete Ekle
                    </button>
                    <button class="btn btn-lg btn-outline-dark py-3 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#shareModal">
                        <i class="bi bi-share"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ALT BÖLÜM: Ürün Detay Bilgileri & Sekmeli Panel -->
    <div class="row mt-5 pt-4">
        <div class="col-lg-8">
            <!-- Bilgi Sekmeleri -->
            <ul class="nav nav-tabs border-bottom mb-4" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active text-dark font-weight-700 fs-7" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc-pane" type="button" role="tab" aria-controls="desc-pane" aria-selected="true">Açıklama</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-dark font-weight-700 fs-7" id="spec-tab" data-bs-toggle="tab" data-bs-target="#spec-pane" type="button" role="tab" aria-controls="spec-pane" aria-selected="false">Özellikler</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-dark font-weight-700 fs-7" id="faq-tab" data-bs-toggle="tab" data-bs-target="#faq-pane" type="button" role="tab" aria-controls="faq-pane" aria-selected="false">Sıkça Sorulan Sorular</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-dark font-weight-700 fs-7" id="doc-tab" data-bs-toggle="tab" data-bs-target="#doc-pane" type="button" role="tab" aria-controls="doc-pane" aria-selected="false">Belgeler</button>
                </li>
            </ul>

            <div class="tab-content" id="productTabsContent">
                <!-- Açıklama Paneli -->
                <div class="tab-pane fade show active fs-7 text-muted" id="desc-pane" role="tabpanel" aria-labelledby="desc-tab" style="line-height: 1.8;">
                    <p><?= htmlspecialchars($product['description'] ?? 'Bu ürün, tamamen kurumsal düzeydeki standartlara uygun olarak premium malzemeler ve en ileri teknoloji ile üretilmiştir. SaintMonarc kalitesi ve güvencesiyle sunulan bu ürün, yüksek performans ve mükemmel kullanıcı memnuniyeti sağlamak üzere en ince ayrıntısına kadar optimize edilmiştir.') ?></p>
                </div>

                <!-- Özellikler Tablosu (Product Specification Table) -->
                <div class="tab-pane fade" id="spec-pane" role="tabpanel" aria-labelledby="spec-tab">
                    <table class="table table-bordered fs-7">
                        <tbody>
                            <tr>
                                <td class="bg-light font-weight-600" style="width: 30%;">Garanti Süresi</td>
                                <td>2 Yıl SaintMonarc Türkiye Garantili</td>
                            </tr>
                            <tr>
                                <td class="bg-light font-weight-600">Üretim Yeri</td>
                                <td>İstanbul, Türkiye</td>
                            </tr>
                            <tr>
                                <td class="bg-light font-weight-600">Bağlantı Özellikleri</td>
                                <td>Bluetooth 5.3, Wi-Fi 6E</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Sık Sorulan Sorular (Accordion FAQ) -->
                <div class="tab-pane fade" id="faq-pane" role="tabpanel" aria-labelledby="faq-tab">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFaq1">
                                <button class="accordion-button collapsed fs-7 font-weight-600" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq1" aria-expanded="false" aria-controls="collapseFaq1">
                                    Aynı gün kargo gönderimi hangi saatlerde geçerlidir?
                                </button>
                            </h2>
                            <div id="collapseFaq1" class="accordion-collapse collapse" aria-labelledby="headingFaq1" data-bs-parent="#faqAccordion">
                                <div class="accordion-body fs-7 text-muted">
                                    Hafta içi saat 16:00'ya kadar verilen tüm siparişler aynı gün kargoya teslim edilmektedir.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Belgeler ve Teknik Dokümanlar -->
                <div class="tab-pane fade" id="doc-pane" role="tabpanel" aria-labelledby="doc-tab">
                    <div class="d-flex flex-column gap-2">
                        <a href="#" class="btn btn-outline-dark text-start rounded-3 py-2 px-3 fs-7 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-file-earmark-pdf text-danger me-2"></i> Kullanım Kılavuzu (Türkçe).pdf</span>
                            <i class="bi bi-download"></i>
                        </a>
                        <a href="#" class="btn btn-outline-dark text-start rounded-3 py-2 px-3 fs-7 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-patch-check text-success me-2"></i> CE Uyumluluk Belgesi ve Sertifikalar.pdf</span>
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Product Bundle (Birlikte Alınanlar) -->
            <div class="mt-5">
                <h5 class="font-weight-800 mb-3" style="font-weight: 800;">Sıkça Birlikte Alınan Paketler</h5>
                <?= UiStore::bundleCard([
                    'desc' => 'SaintMonarc Premium Ürün + Deri Koruyucu Kılıf Alımında Ekstra %10 İndirim',
                    'old_price' => $price + 249.00,
                    'price' => ($price + 249.00) * 0.90
                ]) ?>
            </div>

            <!-- Yorumlar & Müşteri Görüşleri (Reviews Section) -->
            <div class="mt-5 pt-4" id="reviewsSection">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="font-weight-800 m-0" style="font-weight: 800;">Müşteri Yorumları (48)</h5>
                    <button class="btn btn-sm btn-outline-dark rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#reviewFormCollapse">Yorum Yaz</button>
                </div>

                <!-- AI Yorum Özeti -->
                <div class="bg-light p-3 rounded-3 border border-secondary border-opacity-10 mb-4 fs-7 text-muted">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-stars text-purple" style="color: #a855f7;"></i>
                        <strong class="text-dark">AI Akıllı Yorum Özeti</strong>
                    </div>
                    <span>Müşterilerin %96'sı ürünün kargo hızını ve malzeme kalitesini çok beğenmiştir. Bazı kullanıcılar kurulumun biraz zaman aldığını belirtmiştir.</span>
                </div>

                <!-- Fotoğraflı Yorum Kartı -->
                <div class="bg-white p-3 rounded-3 border border-secondary border-opacity-10 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 fs-7">
                        <div class="d-flex align-items-center gap-2">
                            <span class="font-weight-700 text-dark">Ahmet Y.</span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-0.5 px-2 fs-9">Doğrulanmış Alıcı</span>
                        </div>
                        <span class="text-muted">29.07.2026</span>
                    </div>
                    <div class="text-warning mb-2 fs-7">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <p class="text-muted fs-7 mb-0">Ürünü teslim aldım, harika paketlenmişti. Malzeme kalitesi ve şıklığı Apple kalitesinde diyebilirim. Kesinlikle tavsiye ederim.</p>
                </div>
            </div>

            <!-- Soru & Cevap Bölümü (Q&A Section) -->
            <div class="mt-5">
                <h5 class="font-weight-800 mb-3" style="font-weight: 800;">Soru & Cevap (2)</h5>
                
                <div class="bg-light p-3 rounded-3 border border-secondary border-opacity-10 mb-3">
                    <div class="fs-7 mb-2">
                        <strong class="text-dark">Soru:</strong> <span class="text-muted">Macbook Pro ile uyumlu çalışır mı?</span>
                    </div>
                    <div class="fs-7 text-muted border-top pt-2">
                        <strong class="text-success">Cevap (SaintMonarc Destek Ekibi):</strong> <span>Evet, ürünümüz tüm MacOS, Windows ve iOS işletim sistemleriyle tam uyumlu olarak çalışmaktadır.</span>
                    </div>
                </div>
            </div>

            <!-- İlgili Blog & Rehberler (Blog Entegrasyonu) -->
            <div class="mt-5">
                <h5 class="font-weight-800 mb-3" style="font-weight: 800;">Ürünle İlgili Rehberler & Bloglar</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="<?= url('/blog') ?>" class="text-decoration-none">
                            <div class="bg-white p-3 rounded-3 border border-secondary border-opacity-10 shadow-sm hover-translate-y">
                                <span class="text-muted fs-8">Alışveriş Rehberi</span>
                                <h6 class="font-weight-700 text-dark mt-1 mb-0 fs-7">Premium Ürün Satın Alma Rehberi ve İpuçları</h6>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SHARE MODAL -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-700 fs-6" id="shareModalLabel">Ürünü Paylaş</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-2">
                    <button class="btn btn-outline-dark rounded-pill py-2 text-start fs-7" onclick="shareSocial('whatsapp')"><i class="bi bi-whatsapp text-success me-2"></i> WhatsApp'ta Paylaş</button>
                    <button class="btn btn-outline-dark rounded-pill py-2 text-start fs-7" onclick="shareSocial('twitter')"><i class="bi bi-twitter text-info me-2"></i> X'te (Twitter) Paylaş</button>
                    <button class="btn btn-outline-dark rounded-pill py-2 text-start fs-7" onclick="shareSocial('copy')"><i class="bi bi-link-45deg me-2"></i> Bağlantıyı Kopyala</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function selectVariant(name, price, sku, stock) {
        // update dynamic view elements
        document.getElementById('productPrice').innerText = price.toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' TL';
        document.getElementById('productSku').innerText = sku;
        
        // update active buttons visual styling
        const buttons = document.querySelectorAll('.variant-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
    }

    function changeImage(index) {
        // update image mock view
        const icon = document.getElementById('mainGalleryIcon');
        const colors = ['text-muted', 'text-primary', 'text-success', 'text-danger'];
        icon.className = 'bi bi-image display-1 ' + colors[index];
    }

    function addToCart(id) {
        alert('Seçilen varyant başarıyla sepete eklendi!');
    }

    function shareSocial(platform) {
        if (platform === 'copy') {
            navigator.clipboard.writeText(window.location.href);
            alert('Bağlantı panoya kopyalandı!');
        } else {
            alert(platform + ' paylaşım penceresi açılıyor...');
        }
    }
</script>
