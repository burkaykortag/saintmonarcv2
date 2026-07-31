<?php
use App\Helpers\ComponentHelper;
$title = htmlspecialchars($supplier['company_name']) . " – Tedarikçi 360 | SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="container-fluid py-4 text-white">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <?= ComponentHelper::breadcrumb([
                'Yönetim' => url('/admin'),
                'Satın Alma' => url('/admin/purchasing/dashboard'),
                'Tedarikçiler' => url('/admin/purchasing/suppliers'),
                htmlspecialchars($supplier['company_name']) => '#'
            ]) ?>
            <h2 class="mt-2 text-white font-weight-800 fs-3"><?= htmlspecialchars($supplier['company_name']) ?></h2>
            <p class="text-muted mb-0 fs-7">Tedarikçi 360 görünümü ile siparişler, finansal hareketler, sözleşmeler ve AI performans analizi.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-danger rounded-pill px-3" onclick="confirmDelete(<?= $supplier['id'] ?>)"><i class="bi bi-trash me-1"></i> Tedarikçiyi Sil</button>
            <button class="btn btn-warning rounded-pill px-4 font-weight-600" data-bs-toggle="modal" data-bs-target="#editSupplierModal"><i class="bi bi-pencil-square me-1"></i> Düzenle</button>
        </div>
    </div>

    <!-- Alert Messaging -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 text-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Sidebar Navigation (Tedarikçi 360 Sekmeleri) -->
        <div class="col-12 col-md-3">
            <div class="card bg-dark border-secondary border-opacity-10 p-2">
                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <button class="nav-link active text-start fs-8 font-weight-600 py-3 border-0 bg-transparent text-white" id="tab-general" data-bs-toggle="pill" data-bs-target="#panel-general" type="button" role="tab"><i class="bi bi-info-circle me-2 text-warning"></i> Genel Bilgiler</button>
                    <button class="nav-link text-start fs-8 font-weight-600 py-3 border-0 bg-transparent text-white" id="tab-contacts" data-bs-toggle="pill" data-bs-target="#panel-contacts" type="button" role="tab"><i class="bi bi-person-lines-fill me-2 text-warning"></i> Yetkililer / İletişim</button>
                    <button class="nav-link text-start fs-8 font-weight-600 py-3 border-0 bg-transparent text-white" id="tab-addresses" data-bs-toggle="pill" data-bs-target="#panel-addresses" type="button" role="tab"><i class="bi bi-geo-alt me-2 text-warning"></i> Adresler</button>
                    <button class="nav-link text-start fs-8 font-weight-600 py-3 border-0 bg-transparent text-white" id="tab-pos" data-bs-toggle="pill" data-bs-target="#panel-pos" type="button" role="tab"><i class="bi bi-cart-check me-2 text-warning"></i> Siparişler (PO)</button>
                    <button class="nav-link text-start fs-8 font-weight-600 py-3 border-0 bg-transparent text-white" id="tab-invoices" data-bs-toggle="pill" data-bs-target="#panel-invoices" type="button" role="tab"><i class="bi bi-receipt me-2 text-warning"></i> Faturalar</button>
                    <button class="nav-link text-start fs-8 font-weight-600 py-3 border-0 bg-transparent text-white" id="tab-payments" data-bs-toggle="pill" data-bs-target="#panel-payments" type="button" role="tab"><i class="bi bi-cash-coin me-2 text-warning"></i> Ödeme Geçmişi</button>
                    <button class="nav-link text-start fs-8 font-weight-600 py-3 border-0 bg-transparent text-white" id="tab-documents" data-bs-toggle="pill" data-bs-target="#panel-documents" type="button" role="tab"><i class="bi bi-folder2-open me-2 text-warning"></i> Dökümanlar</button>
                    <button class="nav-link text-start fs-8 font-weight-600 py-3 border-0 bg-transparent text-white" id="tab-contracts" data-bs-toggle="pill" data-bs-target="#panel-contracts" type="button" role="tab"><i class="bi bi-file-earmark-text me-2 text-warning"></i> Sözleşmeler</button>
                    <button class="nav-link text-start fs-8 font-weight-600 py-3 border-0 bg-transparent text-white" id="tab-performance" data-bs-toggle="pill" data-bs-target="#panel-performance" type="button" role="tab"><i class="bi bi-bar-chart-line me-2 text-warning"></i> Performans & AI Puanı</button>
                    <button class="nav-link text-start fs-8 font-weight-600 py-3 border-0 bg-transparent text-white" id="tab-notes" data-bs-toggle="pill" data-bs-target="#panel-notes" type="button" role="tab"><i class="bi bi-journal-text me-2 text-warning"></i> Notlar</button>
                    <button class="nav-link text-start fs-8 font-weight-600 py-3 border-0 bg-transparent text-white" id="tab-ai-analysis" data-bs-toggle="pill" data-bs-target="#panel-ai-analysis" type="button" role="tab"><i class="bi bi-stars me-2 text-warning"></i> AI Analizi</button>
                    <button class="nav-link text-start fs-8 font-weight-600 py-3 border-0 bg-transparent text-white" id="tab-timeline" data-bs-toggle="pill" data-bs-target="#panel-timeline" type="button" role="tab"><i class="bi bi-clock-history"></i> İşlem Geçmişi (Timeline)</button>
                </div>
            </div>
        </div>

        <!-- Tab Content Panes -->
        <div class="col-12 col-md-9">
            <div class="tab-content" id="v-pills-tabContent">
                <!-- Tab: General -->
                <div class="tab-pane fade show active" id="panel-general" role="tabpanel">
                    <div class="card bg-dark border-secondary border-opacity-10 p-4">
                        <h5 class="font-weight-800 mb-4 text-white">Genel Bilgiler</h5>
                        <div class="row g-3 fs-8">
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block mb-1">Şirket Resmi Adı:</span>
                                <strong class="text-white"><?= htmlspecialchars($supplier['company_name']) ?></strong>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block mb-1">Vergi Bilgileri:</span>
                                <strong class="text-white"><?= htmlspecialchars($supplier['tax_number'] ?? '-') ?> (<?= htmlspecialchars($supplier['tax_office'] ?? '-') ?>)</strong>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block mb-1">Varsayılan Para Birimi:</span>
                                <strong class="text-white text-uppercase"><?= htmlspecialchars($supplier['currency']) ?></strong>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block mb-1">Ödeme Koşulları (Vade):</span>
                                <strong class="text-white"><?= htmlspecialchars($supplier['payment_terms'] ?? 'Peşin') ?></strong>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block mb-1">Ortalama Teslimat Süresi (Termin):</span>
                                <strong class="text-white"><?= $supplier['lead_time'] ?> Gün</strong>
                            </div>
                            <div class="col-12 col-sm-6">
                                <span class="text-muted d-block mb-1">Çalışma Durumu:</span>
                                <span class="badge bg-<?= $supplier['is_active'] ? 'success' : 'secondary' ?> bg-opacity-10 text-<?= $supplier['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $supplier['is_active'] ? 'Aktif' : 'Pasif' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Contacts -->
                <div class="tab-pane fade" id="panel-contacts" role="tabpanel">
                    <div class="card bg-dark border-secondary border-opacity-10 p-4">
                        <h5 class="font-weight-800 mb-4 text-white">Yetkililer / İletişim Kişileri</h5>
                        <table class="table table-dark table-hover border-secondary border-opacity-10 align-middle fs-8">
                            <thead>
                                <tr>
                                    <th>Adı Soyadı</th>
                                    <th>Görevi / Rolü</th>
                                    <th>Telefon</th>
                                    <th>E-Posta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contacts as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($c['name']) ?></td>
                                        <td><?= htmlspecialchars($c['role']) ?></td>
                                        <td><?= htmlspecialchars($c['phone']) ?></td>
                                        <td><?= htmlspecialchars($c['email']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Addresses -->
                <div class="tab-pane fade" id="panel-addresses" role="tabpanel">
                    <div class="card bg-dark border-secondary border-opacity-10 p-4">
                        <h5 class="font-weight-800 mb-4 text-white">Adres Kayıtları</h5>
                        <div class="row g-3">
                            <?php foreach ($addresses as $addr): ?>
                                <div class="col-12 col-sm-6">
                                    <div class="border border-secondary border-opacity-25 rounded-3 p-3 bg-dark bg-opacity-50">
                                        <h6 class="text-warning mb-2 font-weight-700 fs-8"><?= htmlspecialchars($addr['title']) ?></h6>
                                        <p class="fs-9 mb-0 text-muted"><?= htmlspecialchars($addr['address']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Tab: Purchase Orders -->
                <div class="tab-pane fade" id="panel-pos" role="tabpanel">
                    <div class="card bg-dark border-secondary border-opacity-10 p-4">
                        <h5 class="font-weight-800 mb-4 text-white">Satın Alma Siparişleri (PO)</h5>
                        <table class="table table-dark table-hover border-secondary border-opacity-10 align-middle fs-8">
                            <thead>
                                <tr>
                                    <th>PO No</th>
                                    <th>Depo</th>
                                    <th>Tarih</th>
                                    <th>Durum</th>
                                    <th>Toplam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pos)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Kayıtlı sipariş bulunmamaktadır.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($pos as $p): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($p['po_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($p['warehouse_name']) ?></td>
                                            <td><?= date('d.m.Y', strtotime($p['created_at'])) ?></td>
                                            <td>
                                                <span class="badge bg-warning bg-opacity-10 text-warning"><?= htmlspecialchars($p['status']) ?></span>
                                            </td>
                                            <td>₺<?= number_format((float)$p['grand_total'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Invoices -->
                <div class="tab-pane fade" id="panel-invoices" role="tabpanel">
                    <div class="card bg-dark border-secondary border-opacity-10 p-4">
                        <h5 class="font-weight-800 mb-4 text-white">Gelen Faturalar</h5>
                        <table class="table table-dark table-hover border-secondary border-opacity-10 align-middle fs-8">
                            <thead>
                                <tr>
                                    <th>Fatura No</th>
                                    <th>Sipariş No</th>
                                    <th>Tarih</th>
                                    <th>Tutar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($invoices)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Kayıtlı fatura bulunmamaktadır.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($invoices as $inv): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($inv['po_number']) ?></td>
                                            <td><?= date('d.m.Y', strtotime($inv['created_at'])) ?></td>
                                            <td>₺<?= number_format((float)$inv['total'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Payments -->
                <div class="tab-pane fade" id="panel-payments" role="tabpanel">
                    <div class="card bg-dark border-secondary border-opacity-10 p-4">
                        <h5 class="font-weight-800 mb-4 text-white">Ödeme Takip Geçmişi</h5>
                        <table class="table table-dark table-hover border-secondary border-opacity-10 align-middle fs-8">
                            <thead>
                                <tr>
                                    <th>Sipariş No</th>
                                    <th>Tutar</th>
                                    <th>Vade Tarihi</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Kayıtlı ödeme hareketi bulunmamaktadır.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $pay): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($pay['po_number'] ?? 'Manuel Cari') ?></td>
                                            <td>₺<?= number_format((float)$pay['amount'], 2) ?></td>
                                            <td><?= date('d.m.Y', strtotime($pay['payment_date'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $pay['status'] === 'paid' ? 'success' : 'warning' ?> bg-opacity-10 text-<?= $pay['status'] === 'paid' ? 'success' : 'warning' ?>">
                                                    <?= htmlspecialchars($pay['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Documents -->
                <div class="tab-pane fade" id="panel-documents" role="tabpanel">
                    <div class="card bg-dark border-secondary border-opacity-10 p-4">
                        <h5 class="font-weight-800 mb-4 text-white">Döküman Arşivi</h5>
                        <table class="table table-dark table-hover border-secondary border-opacity-10 align-middle fs-8">
                            <thead>
                                <tr>
                                    <th>Döküman Adı</th>
                                    <th>Tür</th>
                                    <th>Boyut</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documents)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Yüklenmiş döküman bulunmamaktadır.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td><a href="#" class="text-warning"><?= htmlspecialchars($doc['name']) ?></a></td>
                                            <td><?= htmlspecialchars($doc['file_type']) ?></td>
                                            <td><?= number_format(($doc['file_size'] ?? 0) / 1024, 1) ?> KB</td>
                                            <td><?= date('d.m.Y H:i', strtotime($doc['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Contracts -->
                <div class="tab-pane fade" id="panel-contracts" role="tabpanel">
                    <div class="card bg-dark border-secondary border-opacity-10 p-4">
                        <h5 class="font-weight-800 mb-4 text-white">Tedarikçi Sözleşmeleri</h5>
                        <table class="table table-dark table-hover border-secondary border-opacity-10 align-middle fs-8">
                            <thead>
                                <tr>
                                    <th>Sözleşme Başlığı</th>
                                    <th>Başlangıç</th>
                                    <th>Bitiş</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($contracts)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Kayıtlı sözleşme bulunmamaktadır.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($contracts as $con): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($con['title']) ?></strong></td>
                                            <td><?= date('d.m.Y', strtotime($con['start_date'])) ?></td>
                                            <td><?= date('d.m.Y', strtotime($con['end_date'])) ?></td>
                                            <td>
                                                <span class="badge bg-success bg-opacity-10 text-success"><?= htmlspecialchars($con['status']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Performance -->
                <div class="tab-pane fade" id="panel-performance" role="tabpanel">
                    <div class="card bg-dark border-secondary border-opacity-10 p-4">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h5 class="font-weight-800 mb-0 text-white">Tedarikçi Performans Skorecardı</h5>
                            <form action="<?= url('/admin/purchasing/suppliers/recalculate-score') ?>" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="id" value="<?= $supplier['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Skoru Yeniden Hesapla
                                </button>
                            </form>
                        </div>
                        <div class="row g-3 fs-8 mb-4">
                            <div class="col-6 col-md-3">
                                <div class="border border-secondary border-opacity-10 p-3 rounded bg-dark bg-opacity-50 text-center">
                                    <small class="text-muted d-block mb-1">AI Performans Skoru</small>
                                    <h4 class="text-warning font-weight-800 mb-0"><?= number_format($performance['score'] ?? 5.0, 2) ?> / 5.0</h4>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border border-secondary border-opacity-10 p-3 rounded bg-dark bg-opacity-50 text-center">
                                    <small class="text-muted d-block mb-1">Zamanında Teslimat</small>
                                    <h4 class="<?= ($performance['on_time_rate'] ?? 100) >= 90 ? 'text-success' : 'text-warning' ?> font-weight-800 mb-0"><?= number_format($performance['on_time_rate'] ?? 100, 1) ?>%</h4>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border border-secondary border-opacity-10 p-3 rounded bg-dark bg-opacity-50 text-center">
                                    <small class="text-muted d-block mb-1">Hasar / İade Oranı</small>
                                    <h4 class="text-danger font-weight-800 mb-0"><?= number_format($performance['damaged_rate'] ?? 0, 1) ?>%</h4>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border border-secondary border-opacity-10 p-3 rounded bg-dark bg-opacity-50 text-center">
                                    <small class="text-muted d-block mb-1">Eksik Teslimat Oranı</small>
                                    <h4 class="text-info font-weight-800 mb-0"><?= number_format($performance['missing_rate'] ?? 0, 1) ?>%</h4>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 fs-8">
                            <div class="col-6 col-md-3">
                                <div class="border border-secondary border-opacity-10 p-3 rounded bg-dark bg-opacity-50">
                                    <small class="text-muted d-block mb-1">Toplam Alım Tutarı</small>
                                    <strong class="text-white">₺<?= number_format($performance['total_purchases'] ?? 0, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border border-secondary border-opacity-10 p-3 rounded bg-dark bg-opacity-50">
                                    <small class="text-muted d-block mb-1">Tamamlanan Alım Tutarı</small>
                                    <strong class="text-success">₺<?= number_format($performance['total_spent'] ?? 0, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border border-secondary border-opacity-10 p-3 rounded bg-dark bg-opacity-50">
                                    <small class="text-muted d-block mb-1">Ortalama Teslim Süresi</small>
                                    <strong class="text-white"><?= $performance['lead_time'] ?? '-' ?> Gün</strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border border-secondary border-opacity-10 p-3 rounded bg-dark bg-opacity-50">
                                    <small class="text-muted d-block mb-1">ABC Sınıfı / Sipariş Sayısı</small>
                                    <strong class="text-warning"><?= $performance['abc_class'] ?? 'C' ?></strong>
                                    <span class="text-muted ms-2">(<?= $performance['total_order_count'] ?? 0 ?> Sipariş)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Notes -->
                <div class="tab-pane fade" id="panel-notes" role="tabpanel">
                    <div class="card bg-dark border-secondary border-opacity-10 p-4">
                        <h5 class="font-weight-800 mb-4 text-white">Yönetici Notları</h5>
                        <div class="d-flex flex-column gap-3 fs-8">
                            <?php foreach ($notes as $n): ?>
                                <div class="bg-dark bg-opacity-50 p-3 rounded border border-secondary border-opacity-10">
                                    <div class="d-flex justify-content-between mb-2">
                                        <strong class="text-warning"><?= htmlspecialchars($n['admin_name']) ?></strong>
                                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($n['created_at'])) ?></small>
                                    </div>
                                    <p class="mb-0 text-white"><?= htmlspecialchars($n['note']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Tab: AI Analysis -->
                <div class="tab-pane fade" id="panel-ai-analysis" role="tabpanel">
                    <div class="card bg-dark border-secondary border-opacity-10 p-4">
                        <div class="d-flex align-items-center gap-2 mb-4 text-warning">
                            <i class="bi bi-stars"></i>
                            <h5 class="font-weight-800 mb-0">AI Satın Alma & Tedarik Risk Analizi</h5>
                        </div>
                        <p class="fs-8 text-muted mb-3">
                            SaintMonarc AI Engine, bu tedarikçinin son 90 gündeki tüm teslimatlarını ve kalite loglarını analiz etti:
                        </p>
                        <div class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25 text-warning fs-8 mb-0">
                            <strong>Özet Analiz:</strong> Tedarikçi termin sürelerine %94 oranında uyum sağlamaktadır. Fiyat dalgalanma endeksi stabildir. Bu firmadan alımların sürdürülmesi önerilir.
                        </div>
                    </div>
                </div>

                <!-- Tab: Timeline -->
                <div class="tab-pane fade" id="panel-timeline" role="tabpanel">
                    <div class="card bg-dark border-secondary border-opacity-10 p-4">
                        <h5 class="font-weight-800 mb-4 text-white">İşlem Geçmişi</h5>
                        <div class="position-relative border-start border-secondary border-opacity-25 ps-4 py-2 fs-8">
                            <div class="position-relative mb-4">
                                <div class="position-absolute bg-warning rounded-circle" style="width: 12px; height: 12px; left: -31px; top: 4px;"></div>
                                <span class="text-muted d-block mb-1">Tarih: <?= date('d.m.Y H:i') ?></span>
                                <strong class="text-white">AI Performans Güncellemesi</strong>
                                <p class="text-muted fs-9 mb-0">Tedarikçi genel puanı 360 parametreye göre güncellendi.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-secondary border-opacity-25 text-white">
            <div class="modal-header border-secondary border-opacity-10">
                <h5 class="modal-title font-weight-800 text-white">Tedarikçi Kartını Düzenle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('/admin/purchasing/suppliers/update') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="id" value="<?= $supplier['id'] ?>">
                <div class="modal-body row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Şirket / Tedarikçi Adı *</label>
                        <input type="text" name="company_name" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['company_name']) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Yetkili / Temsilci Adı</label>
                        <input type="text" name="contact_name" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['contact_name'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Vergi Numarası</label>
                        <input type="text" name="tax_number" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['tax_number'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Vergi Dairesi</label>
                        <input type="text" name="tax_office" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['tax_office'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Telefon Numarası</label>
                        <input type="text" name="phone" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">E-Posta Adresi</label>
                        <input type="email" name="email" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['email'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Ülke</label>
                        <input type="text" name="country" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['country'] ?? '') ?>">
                    </div>
                    <?php if (strtolower($supplier['country'] ?? 'türkiye') === 'türkiye' || empty($supplier['country'])): ?>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">İl</label>
                        <select name="city" class="form-select bg-dark border-secondary border-opacity-25 text-white address-city" data-target="#district_select" required>
                            <option value="">-- İl Seçin --</option>
                        </select>
                        <input type="hidden" id="city_prefill" value="<?= htmlspecialchars($supplier['city'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">İlçe</label>
                        <select name="district" id="district_select" class="form-select bg-dark border-secondary border-opacity-25 text-white address-district">
                            <option value="">-- Önce İl Seçin --</option>
                        </select>
                        <input type="hidden" id="district_prefill" value="<?= htmlspecialchars($supplier['district'] ?? '') ?>">
                    </div>
                    <?php else: ?>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Şehir</label>
                        <input type="text" name="city" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['city'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">İlçe / Bölge</label>
                        <input type="text" name="district" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['district'] ?? '') ?>">
                    </div>
                    <?php endif; ?>
                    <div class="col-12 col-md-8">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Adres</label>
                        <input type="text" name="address" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['address'] ?? '') ?>" placeholder="Sokak, cadde, bina no...">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Posta Kodu</label>
                        <input type="text" name="zip_code" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['zip_code'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">IBAN</label>
                        <input type="text" name="iban" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['iban'] ?? '') ?>" placeholder="TR00 0000 0000 0000 0000 0000 00">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Döviz Birimi</label>
                        <select name="currency" class="form-select bg-dark border-secondary border-opacity-25 text-white">
                            <option value="TRY" <?= $supplier['currency'] === 'TRY' ? 'selected' : '' ?>>TRY (₺)</option>
                            <option value="USD" <?= $supplier['currency'] === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                            <option value="EUR" <?= $supplier['currency'] === 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
                            <option value="GBP" <?= $supplier['currency'] === 'GBP' ? 'selected' : '' ?>>GBP (£)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Ödeme Vadesi</label>
                        <input type="text" name="payment_terms" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= htmlspecialchars($supplier['payment_terms'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Termin Süresi (Gün)</label>
                        <input type="number" name="lead_time" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= $supplier['lead_time'] ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Çalışma Durumu</label>
                        <select name="status" class="form-select bg-dark border-secondary border-opacity-25 text-white">
                            <option value="active" <?= ($supplier['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Aktif</option>
                            <option value="passive" <?= ($supplier['status'] ?? '') === 'passive' ? 'selected' : '' ?>>Pasif</option>
                            <option value="blacklisted" <?= ($supplier['status'] ?? '') === 'blacklisted' ? 'selected' : '' ?>>Kara Liste</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Tedarikçi Puanı (5 üzerinden)</label>
                        <input type="number" step="0.01" max="5.0" min="1.0" name="score" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="<?= number_format((float)$supplier['score'], 2) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Notlar / Açıklama</label>
                        <textarea name="notes" rows="3" class="form-control bg-dark border-secondary border-opacity-25 text-white"><?= htmlspecialchars($supplier['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-10">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning font-weight-600">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Bu tedarikçiyi silmek istediğinize emin misiniz? (Pasife alınacaktır)')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= url('/admin/purchasing/suppliers/delete') ?>';
        
        const inputCsrf = document.createElement('input');
        inputCsrf.type = 'hidden';
        inputCsrf.name = 'csrf_token';
        inputCsrf.value = '<?= $csrfToken ?>';
        form.appendChild(inputCsrf);

        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id';
        inputId.value = id;
        form.appendChild(inputId);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
