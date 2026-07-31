<?php
use App\Helpers\ComponentHelper;

$title = "Customer 360 CRM - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();

// Set dummy CRM values if not provided by database
$spent = (float)($customer['total_spent'] ?? 24890.00);
$ordersCount = (int)($customer['orders_count'] ?? 15);
$aov = $ordersCount > 0 ? $spent / $ordersCount : 0.0;
$riskScore = 15; // Low risk
$aiTrustScore = 98; // Very high trust
$ltv = $spent * 1.5; // Lifetime Value estimate
$profitRate = 42; // 42% profitability
?>

<!-- Custom CSS for Customer 360 CRM experience -->
<style>
    .crm-card {
        background: #1D1D1D !important;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
    }
    .kpi-card-crm {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 16px;
        transition: all 0.3s;
    }
    .kpi-card-crm:hover {
        border-color: var(--sm-gold);
    }
    .timeline-crm {
        border-left: 2px solid rgba(255, 255, 255, 0.05);
        position: relative;
    }
    .timeline-item-crm::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 4px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--sm-gold);
        border: 2px solid #1D1D1D;
    }
    .nav-pills-crm .nav-link {
        color: rgba(255, 255, 255, 0.6);
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        margin-bottom: 6px;
        transition: all 0.3s;
        text-align: left;
        border-radius: 10px;
    }
    .nav-pills-crm .nav-link.active,
    .nav-pills-crm .nav-link:hover {
        color: var(--sm-gold) !important;
        background: rgba(197, 168, 128, 0.1) !important;
        border-color: var(--sm-gold) !important;
    }
    
    .map-density-path {
        transition: fill 0.3s ease;
    }
    .map-density-path:hover {
        fill: rgba(197, 168, 128, 0.4) !important;
    }
</style>

<div class="mb-4 text-white" role="region" aria-label="CRM Başlık ve Navigasyon">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Müşteriler' => url('/admin/customers'),
        'Customer 360: ' . $customer['first_name'] . ' ' . $customer['last_name'] => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <h2 class="text-white font-weight-800 m-0" style="font-size: 28px;"><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></h2>
            <span class="badge bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-1.5 rounded-pill" style="font-size: 11px;">
                <i class="bi bi-star-fill text-warning me-1"></i> <?= htmlspecialchars($customer['group_name'] ?? 'Perakende Müşteri') ?>
            </span>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="triggerCrmAction('block')"><i class="bi bi-slash-circle me-1"></i> Müşteriyi Engelle</button>
            <a href="<?= url('/admin/customers/edit?id=' . $customer['id']) ?>" class="btn btn-sm btn-warning text-dark rounded-pill px-3"><i class="bi-pencil-square me-1"></i> Profili Düzenle</a>
            <a href="<?= url('/admin/customers') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Geri Dön</a>
        </div>
    </div>
</div>

<div class="row g-4 text-white">
    <!-- LEFT SIDEBAR: Executive Customer Profile Summary Card -->
    <div class="col-12 col-lg-3">
        <div class="crm-card p-4 text-center mb-4">
            <div class="position-relative mb-3 mx-auto" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 3px solid var(--sm-gold); background: #171717; display: flex; align-items: center; justify-content: center;">
                <?php if (!empty($customer['avatar'])): ?>
                    <img src="<?= url($customer['avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Profil Fotoğrafı">
                <?php else: ?>
                    <i class="bi bi-person-fill fs-1 text-muted"></i>
                <?php endif; ?>
            </div>
            <h4 class="font-weight-800 fs-5 mb-1"><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></h4>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 mb-3 text-capitalize"><?= htmlspecialchars($customer['status'] ?? 'Aktif') ?></span>
            
            <div class="d-flex flex-column gap-2 text-start fs-8 text-muted pt-3 border-top border-secondary border-opacity-20" style="font-size: 13px;">
                <div class="d-flex justify-content-between"><span>E-posta:</span> <span class="text-white"><?= htmlspecialchars($customer['email']) ?></span></div>
                <div class="d-flex justify-content-between"><span>Telefon:</span> <span class="text-white"><?= htmlspecialchars($customer['phone'] ?? '-') ?></span></div>
                <div class="d-flex justify-content-between"><span>Üyelik Tarihi:</span> <span class="text-white"><?= date('d.m.Y', strtotime($customer['created_at'])) ?></span></div>
                <div class="d-flex justify-content-between"><span>Son Giriş:</span> <span class="text-white"><?= $customer['last_login_at'] ? date('d.m.Y H:i', strtotime($customer['last_login_at'])) : '-' ?></span></div>
                <div class="d-flex justify-content-between"><span>KVKK İzni:</span> <span class="text-white"><?= $customer['kvkk_consent'] ? '✅ Onaylı' : '❌ Onaysız' ?></span></div>
            </div>
        </div>

        <!-- CRM Tab Pills Navigation -->
        <div class="nav flex-column nav-pills nav-pills-crm" id="crmPillsTab" role="tablist" aria-orientation="vertical">
            <button class="nav-link active" id="pill-summary-tab" data-bs-toggle="pill" data-bs-target="#pill-summary" type="button" role="tab"><i class="bi bi-person-lines-fill me-2"></i>Genel Görünüm (360°)</button>
            <button class="nav-link" id="pill-history-tab" data-bs-toggle="pill" data-bs-target="#pill-history" type="button" role="tab"><i class="bi bi-cart-check me-2"></i>Satın Alma Geçmişi</button>
            <button class="nav-link" id="pill-timeline-tab" data-bs-toggle="pill" data-bs-target="#pill-timeline" type="button" role="tab"><i class="bi bi-clock-history me-2"></i>Zaman Çizgisi (Timeline)</button>
            <button class="nav-link" id="pill-ai-tab" data-bs-toggle="pill" data-bs-target="#pill-ai" type="button" role="tab"><i class="bi-cpu me-2"></i>AI Customer Insight</button>
            <button class="nav-link" id="pill-notes-tab" data-bs-toggle="pill" data-bs-target="#pill-notes" type="button" role="tab"><i class="bi bi-sticky me-2"></i>CRM Notları & Görevler</button>
            <button class="nav-link" id="pill-comms-tab" data-bs-toggle="pill" data-bs-target="#pill-comms" type="button" role="tab"><i class="bi bi-chat-left-text me-2"></i>İletişim Geçmişi</button>
            <button class="nav-link" id="pill-loyalty-tab" data-bs-toggle="pill" data-bs-target="#pill-loyalty" type="button" role="tab"><i class="bi bi-trophy me-2"></i>Sadakat & VIP Paneli</button>
            <button class="nav-link" id="pill-map-tab" data-bs-toggle="pill" data-bs-target="#pill-map" type="button" role="tab"><i class="bi bi-geo-alt me-2"></i>Müşteri Yoğunluk Haritası</button>
        </div>
    </div>

    <!-- RIGHT CONTENT AREA: Dynamic Tab Panes -->
    <div class="col-12 col-lg-9">
        <div class="tab-content" id="crmPillsTabContent">
            <!-- TAB 1: Customer 360 Overview -->
            <div class="tab-pane fade show active" id="pill-summary" role="tabpanel" aria-labelledby="pill-summary-tab">
                <div class="crm-card p-4">
                    <h5 class="font-weight-800 mb-4 fs-6"><i class="bi bi-grid-3x3-gap text-warning me-2"></i>Müşteri 360 Derece Analiz Paneli</h5>
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="kpi-card-crm">
                                <small class="text-muted d-block text-uppercase font-weight-700 fs-9">Toplam Sipariş</small>
                                <h4 class="font-weight-800 text-white mt-1 mb-0"><?= $ordersCount ?> Adet</h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card-crm">
                                <small class="text-muted d-block text-uppercase font-weight-700 fs-9">Toplam Harcama</small>
                                <h4 class="font-weight-800 text-warning mt-1 mb-0">₺<?= number_format($spent, 2, ',', '.') ?></h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card-crm">
                                <small class="text-muted d-block text-uppercase font-weight-700 fs-9">Ortalama Sepet (AOV)</small>
                                <h4 class="font-weight-800 text-white mt-1 mb-0">₺<?= number_format($aov, 2, ',', '.') ?></h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card-crm">
                                <small class="text-muted d-block text-uppercase font-weight-700 fs-9">Müşteri Segmenti</small>
                                <h4 class="font-weight-800 text-success mt-1 mb-0">Sadık Müşteri</h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card-crm">
                                <small class="text-muted d-block text-uppercase font-weight-700 fs-9">Sadakat Seviyesi</small>
                                <h4 class="font-weight-800 text-white mt-1 mb-0">Altın Üye (Gold)</h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card-crm">
                                <small class="text-muted d-block text-uppercase font-weight-700 fs-9">Terk Etme Riski</small>
                                <h4 class="font-weight-800 text-success mt-1 mb-0">%<?= $riskScore ?> (Çok Düşük)</h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card-crm">
                                <small class="text-muted d-block text-uppercase font-weight-700 fs-9">AI Güven Skoru</small>
                                <h4 class="font-weight-800 text-info mt-1 mb-0">%<?= $aiTrustScore ?> (Yüksek)</h4>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="kpi-card-crm">
                                <small class="text-muted d-block text-uppercase font-weight-700 fs-9">Ömür Boyu Değer (LTV)</small>
                                <h4 class="font-weight-800 text-warning mt-1 mb-0">₺<?= number_format($ltv, 2, ',', '.') ?></h4>
                            </div>
                        </div>
                    </div>

                    <!-- HUD Quick Actions Grid -->
                    <h6 class="font-weight-700 text-white mt-4 mb-3"><i class="bi bi-lightning-charge-fill text-warning me-1.5"></i>Hızlı CRM Aksiyonları</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-dark" onclick="triggerCrmAction('siparis_gor')"><i class="bi bi-cart me-1.5"></i>Siparişleri Gör</button>
                        <button class="btn btn-sm btn-dark" onclick="triggerCrmAction('mesaj_gonder')"><i class="bi bi-envelope me-1.5"></i>Mesaj Gönder</button>
                        <button class="btn btn-sm btn-dark" onclick="triggerCrmAction('kupon_tanimla')"><i class="bi bi-ticket me-1.5"></i>Kupon Tanımla</button>
                        <button class="btn btn-sm btn-dark" onclick="triggerCrmAction('puan_ekle')"><i class="bi bi-gem me-1.5"></i>Puan Ekle</button>
                        <button class="btn btn-sm btn-dark" onclick="triggerCrmAction('workflow_baslat')"><i class="bi bi-diagram-3 me-1.5"></i>Workflow Başlat</button>
                        <button class="btn btn-sm btn-dark" onclick="triggerCrmAction('ai_analiz')"><i class="bi bi-cpu me-1.5"></i>AI Analiz Et</button>
                        <button class="btn btn-sm btn-dark" onclick="triggerCrmAction('etiket_ekle')"><i class="bi bi-tag me-1.5"></i>Etiket Ekle</button>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Purchase History -->
            <div class="tab-pane fade" id="pill-history" role="tabpanel" aria-labelledby="pill-history-tab">
                <div class="crm-card p-4">
                    <h5 class="font-weight-800 mb-3 fs-6"><i class="bi bi-cart-check text-warning me-2"></i>Satın Alma Sipariş Geçmişi</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-white">
                            <thead>
                                <tr class="text-muted fs-8">
                                    <th>Sipariş No</th>
                                    <th>Tarih</th>
                                    <th>Ürünler</th>
                                    <th>Tutar</th>
                                    <th>Kargo</th>
                                    <th>Ödeme</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Sipariş kaydı bulunmamaktadır.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $o): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
                                            <td><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                                            <td>Dyson V15 Detect Wireless Vacuum</td>
                                            <td class="text-warning">₺<?= number_format((float)$o['total_amount'], 2, ',', '.') ?></td>
                                            <td>Yurtiçi Kargo</td>
                                            <td>Kredi Kartı</td>
                                            <td>
                                                <span class="badge" style="background: <?= htmlspecialchars($o['status_color'] ?? '#3b82f6') ?>; color:#fff;">
                                                    <?= htmlspecialchars($o['status_name'] ?? 'İşlemde') ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Müşteri Zaman Çizgisi (Timeline) -->
            <div class="tab-pane fade" id="pill-timeline" role="tabpanel" aria-labelledby="pill-timeline-tab">
                <div class="crm-card p-4">
                    <h5 class="font-weight-800 mb-4 fs-6"><i class="bi bi-clock-history text-warning me-2"></i>Müşteri Aktivite Zaman Çizgisi (Timeline)</h5>
                    <div class="timeline-crm ps-4" id="customerTimelineFeed">
                        <div class="timeline-item-crm position-relative pb-4 fs-8 text-muted">
                            <strong class="text-white d-block">AI Satın Alma Önerisi Alındı</strong>
                            <span>Dyson V15 ürünü için %10 indirim önerisi otomatik olarak kullanıcıya sunuldu.</span>
                            <small class="text-muted d-block mt-1">Şimdi</small>
                        </div>
                        <div class="timeline-item-crm position-relative pb-4 fs-8 text-muted">
                            <strong class="text-white d-block">Sipariş Oluşturuldu (#SM-4982)</strong>
                            <span>Toplam ₺24.999 tutarındaki Dyson V15 siparişi başarıyla oluşturuldu.</span>
                            <small class="text-muted d-block mt-1">3 saat önce</small>
                        </div>
                        <div class="timeline-item-crm position-relative pb-4 fs-8 text-muted">
                            <strong class="text-white d-block">Destek Talebi Açıldı (Ticket #284)</strong>
                            <span>Kullanıcı kargo gönderim süreleri hakkında canlı destek üzerinden soru sordu.</span>
                            <small class="text-muted d-block mt-1">Dün</small>
                        </div>
                        <div class="timeline-item-crm position-relative pb-4 fs-8 text-muted">
                            <strong class="text-white d-block">Sisteme Üye Oldu</strong>
                            <span>Müşteri e-posta doğrulamasını tamamlayarak SaintMonarc CRM veritabanına kaydoldu.</span>
                            <small class="text-muted d-block mt-1">5 gün önce</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: AI Customer Insight -->
            <div class="tab-pane fade" id="pill-ai" role="tabpanel" aria-labelledby="pill-ai-tab">
                <div class="crm-card p-4">
                    <h5 class="font-weight-800 mb-4 fs-6"><i class="bi-cpu text-warning me-2"></i>AI Customer Insight (Zekâ Analiz Modülü)</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 bg-white bg-opacity-2 border border-white border-opacity-5">
                                <strong class="text-white d-block mb-1"><i class="bi bi-graph-up-arrow text-success me-1.5"></i>Tekrar Satın Alma İhtimali</strong>
                                <p class="text-muted fs-8 mb-2" style="font-size: 13px;">Kullanıcının son sipariş sıklığı ve incelediği kategorilere göre tekrar sipariş verme olasılığı:</p>
                                <h4 class="font-weight-800 text-success mb-0">%84 (Çok Yüksek)</h4>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 bg-white bg-opacity-2 border border-white border-opacity-5">
                                <strong class="text-white d-block mb-1"><i class="bi bi-shield-exclamation text-danger me-1.5"></i>Müşteri Kayıp (Churn) Riski</strong>
                                <p class="text-muted fs-8 mb-2" style="font-size: 13px;">Kullanıcının sepeti terk etme ve rakip markalara yönelme risk analiz skoru:</p>
                                <h4 class="font-weight-800 text-danger mb-0">%16 (Çok Düşük)</h4>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="p-3 rounded-4 bg-white bg-opacity-2 border border-white border-opacity-5">
                                <strong class="text-white d-block mb-2"><i class="bi bi-stars text-warning me-1.5"></i>Tavsiye Edilen Ürünler & Kampanya Önerisi</strong>
                                <ul class="list-unstyled mb-0 d-flex flex-column gap-2 text-muted fs-8" style="font-size: 13.5px;">
                                    <li><i class="bi bi-lightbulb-fill text-warning me-1.5"></i> <strong>Birincil Öneri:</strong> Dyson V15 uyumlu ekstra HEPA filtre ve süpürme başlığı aksesuarları.</li>
                                    <li><i class="bi bi-percent text-success me-1.5"></i> <strong>Kişiselleştirilmiş İndirim:</strong> Aksesuar alımlarında geçerli sepette anında ₺250 indirim tanımlayın.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 5: CRM Notları & Görevler -->
            <div class="tab-pane fade" id="pill-notes" role="tabpanel" aria-labelledby="pill-notes-tab">
                <div class="crm-card p-4">
                    <h5 class="font-weight-800 mb-3 fs-6"><i class="bi bi-sticky text-warning me-2"></i>Müşteri Notları & CRM Görev HUD</h5>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <input type="text" class="search-input w-100 text-white mb-2 py-2" placeholder="Görev/Hatırlatma adı..." id="crmTaskTitle">
                            <textarea class="search-input w-100 text-white py-2" placeholder="Not veya hatırlatma detayını yazın..." id="crmTaskDesc" rows="3"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted fs-8 mb-1">Takip Tarihi</label>
                            <input type="date" class="search-input w-100 text-white mb-2 py-2" id="crmFollowUpDate">
                            <button class="btn btn-sm btn-warning w-100 py-2 border-0 text-dark font-weight-700" onclick="addCrmTask()"><i class="bi bi-plus-circle me-1.5"></i>Görev Ekle</button>
                        </div>
                    </div>

                    <div class="crm-tasks-list" id="crmTasksFeed">
                        <div class="p-3 rounded-4 mb-2 bg-white bg-opacity-2 border border-white border-opacity-5 d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-white d-block">Müşteriyi kargo hasarı için geri arayın</strong>
                                <span class="text-muted fs-8">Kargonun kutusu yırtık ulaşmış, memnuniyet için VIP kuponu teklif edin.</span>
                                <small class="text-warning d-block mt-1"><i class="bi bi-calendar-event me-1"></i> Takip: 02.08.2026</small>
                            </div>
                            <button class="btn btn-xs btn-outline-success rounded-pill px-2.5" onclick="this.closest('.p-3').remove()"><i class="bi bi-check-lg"></i> Tamamla</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 6: İletişim Geçmişi -->
            <div class="tab-pane fade" id="pill-comms" role="tabpanel" aria-labelledby="pill-comms-tab">
                <div class="crm-card p-4">
                    <h5 class="font-weight-800 mb-3 fs-6"><i class="bi bi-chat-left-text text-warning me-2"></i>Müşteri İletişim Log Geçmişi</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-white">
                            <thead>
                                <tr class="text-muted fs-8">
                                    <th>Kanal</th>
                                    <th>Mesaj Tipi / Başlık</th>
                                    <th>Tarih</th>
                                    <th>Gönderim Durumu</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7 text-muted">
                                <tr>
                                    <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 py-1 px-2.5">E-posta</span></td>
                                    <td><strong class="text-white">SaintMonarc Hoş Geldin E-postası</strong></td>
                                    <td>25.07.2026 14:32</td>
                                    <td><span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> İletildi (Açıldı)</span></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2.5">SMS</span></td>
                                    <td><strong class="text-white">Sipariş Kargoya Verildi SMS</strong></td>
                                    <td>28.07.2026 10:15</td>
                                    <td><span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> İletildi</span></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 py-1 px-2.5">Bildirim</span></td>
                                    <td><strong class="text-white">Haftalık İndirim Bülteni Kampanyası</strong></td>
                                    <td>29.07.2026 18:00</td>
                                    <td><span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Gönderildi</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 7: Sadakat & VIP Paneli -->
            <div class="tab-pane fade" id="pill-loyalty" role="tabpanel" aria-labelledby="pill-loyalty-tab">
                <div class="crm-card p-4">
                    <h5 class="font-weight-800 mb-4 fs-6"><i class="bi bi-trophy text-warning me-2"></i>Müşteri Sadakat, Puan & VIP Paneli</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 rounded-4 bg-white bg-opacity-2 border border-white border-opacity-5 text-center">
                                <span class="text-muted fs-8 font-weight-600 text-uppercase d-block mb-1">Mevcut Sadakat Puanı</span>
                                <h3 class="font-weight-800 text-warning m-0">1.250 Puan</h3>
                                <small class="text-muted d-block mt-1">Değeri: ₺125,00 Nakit İndirim</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-4 bg-white bg-opacity-2 border border-white border-opacity-5 text-center">
                                <span class="text-muted fs-8 font-weight-600 text-uppercase d-block mb-1">Aktif Kuponlar</span>
                                <h3 class="font-weight-800 text-white m-0">2 Adet Kupon</h3>
                                <small class="text-muted d-block mt-1">VIP10 (%%10), WELCOME50 (₺50)</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-4 bg-white bg-opacity-2 border border-white border-opacity-5 text-center">
                                <span class="text-muted fs-8 font-weight-600 text-uppercase d-block mb-1">Bir Sonraki Seviye</span>
                                <h3 class="font-weight-800 text-info m-0">Platin Seviye</h3>
                                <small class="text-muted d-block mt-1">Kalan Harcama Tutarı: ₺5.110</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 8: Müşteri Yoğunluk Haritası -->
            <div class="tab-pane fade" id="pill-map" role="tabpanel" aria-labelledby="pill-map-tab">
                <div class="crm-card p-4">
                    <h5 class="font-weight-800 mb-3 fs-6"><i class="bi bi-geo-alt text-warning me-2"></i>Müşteri Yoğunluk Haritası (Coğrafi Dağılım)</h5>
                    <div class="map-svg-container" style="position: relative;">
                        <!-- SVG Turkey Geographical map with regional paths colored for customer distribution -->
                        <svg viewBox="0 0 1000 480" class="w-100 h-100" style="fill: #262626; stroke: rgba(255,255,255,0.08); stroke-width: 1.5;">
                            <!-- Marmara (Current customer is highlighted here) -->
                            <path d="M 100,200 L 150,170 L 180,160 L 220,150 L 260,160 L 280,185 L 260,220 L 210,230 L 170,240 L 140,265 L 115,260 L 105,245 Z" class="map-density-path" style="fill: rgba(197, 168, 128, 0.45); stroke: var(--sm-gold);"/>
                            <!-- Ege -->
                            <path d="M 140,265 L 170,240 L 210,230 L 260,220 L 280,250 L 270,305 L 240,295 L 220,305 L 200,300 L 180,285 L 170,270 L 155,255 Z" class="map-density-path" style="fill: rgba(255,255,255,0.03);"/>
                            <!-- Akdeniz -->
                            <path d="M 270,305 L 280,250 L 330,260 L 390,265 L 440,270 L 460,290 L 490,310 L 490,345 L 485,360 L 480,370 L 470,360 L 460,345 L 450,335 L 440,325 L 420,315 L 390,310 L 360,305 L 330,300 L 300,310 Z" class="map-density-path" style="fill: rgba(255,255,255,0.03);"/>
                            <!-- Ic Anadolu -->
                            <path d="M 280,185 L 320,175 L 380,170 L 450,175 L 500,180 L 530,205 L 500,260 L 440,270 L 390,265 L 330,260 L 280,250 Z" class="map-density-path" style="fill: rgba(255,255,255,0.03);"/>
                            <!-- Karadeniz -->
                            <path d="M 260,160 L 320,155 L 380,150 L 440,150 L 500,150 L 560,145 L 620,140 L 650,155 L 680,160 L 740,165 L 770,160 L 760,200 L 680,210 L 600,215 L 530,205 L 500,180 L 450,175 L 380,170 L 320,175 L 280,185 Z" class="map-density-path" style="fill: rgba(255,255,255,0.03);"/>
                            <!-- Dogu Anadolu -->
                            <path d="M 530,205 L 600,215 L 680,210 L 760,200 L 800,165 L 860,180 L 900,195 L 910,210 L 915,230 L 900,250 L 870,280 L 820,300 L 790,305 L 760,295 L 730,290 L 680,270 L 580,265 L 500,260 Z" class="map-density-path" style="fill: rgba(255,255,255,0.03);"/>
                            <!-- Guneydogu Anadolu -->
                            <path d="M 500,260 L 580,265 L 680,270 L 730,290 L 760,295 L 790,305 L 820,300 L 850,295 L 820,300 L 790,305 L 760,295 L 730,290 L 700,295 L 670,305 L 640,310 L 610,315 L 580,310 L 550,305 L 530,300 L 515,315 L 500,325 L 490,345 Z" class="map-density-path" style="fill: rgba(255,255,255,0.03);"/>
                        </svg>
                        
                        <!-- Tooltip indicator displaying geographical stats -->
                        <div style="position: absolute; bottom: 20px; right: 20px; background: rgba(15,12,32,0.9); border: 1px solid var(--sm-gold); padding: 8px 14px; border-radius: 8px; font-size: 11px;">
                            <strong>Marmara Bölgesi (Aktif Konum)</strong><br>
                            <span>Toplam Ciro: ₺452.900</span><br>
                            <span>Müşteri Sayısı: 980</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function triggerCrmAction(action) {
        alert('CRM İşlemi Başlatıldı: ' + action.toUpperCase());
    }

    function addCrmTask() {
        const title = document.getElementById('crmTaskTitle').value;
        const desc = document.getElementById('crmTaskDesc').value;
        const date = document.getElementById('crmFollowUpDate').value;
        
        if (!title) return alert('Görev başlığı boş bırakılamaz.');
        
        const feed = document.getElementById('crmTasksFeed');
        const item = document.createElement('div');
        item.className = 'p-3 rounded-4 mb-2 bg-white bg-opacity-2 border border-white border-opacity-5 d-flex justify-content-between align-items-center';
        item.innerHTML = `
            <div>
                <strong class="text-white d-block">${title}</strong>
                <span class="text-muted fs-8">${desc}</span>
                <small class="text-warning d-block mt-1"><i class="bi bi-calendar-event me-1"></i> Takip: ${date || 'Girilmedi'}</small>
            </div>
            <button class="btn btn-xs btn-outline-success rounded-pill px-2.5" onclick="this.closest('.p-3').remove()"><i class="bi bi-check-lg"></i> Tamamla</button>
        `;
        feed.insertBefore(item, feed.firstChild);
        
        // Reset inputs
        document.getElementById('crmTaskTitle').value = '';
        document.getElementById('crmTaskDesc').value = '';
    }
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
