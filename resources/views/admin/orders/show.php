<?php
use App\Helpers\ComponentHelper;

$title = "Sipariş Detayı - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Siparişler' => url('/admin/orders'),
        'Sipariş Detayı: ' . $order['order_number'] => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Sipariş: <?= htmlspecialchars($order['order_number']) ?></h2>
            <span class="badge" style="background: <?= $order['status_color'] ?>22; color: <?= $order['status_color'] ?>; border: 1px solid <?= $order['status_color'] ?>44; padding: 8px 16px; border-radius: 30px;">
                <i class="bi <?= $order['status_icon'] ?> me-1"></i><?= htmlspecialchars($order['status_name'] ?? $order['status']) ?>
            </span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-warning border-0" data-bs-toggle="modal" data-bs-target="#statusModal"><i class="bi bi-gear-wide-connected me-2"></i>Durum Değiştir</button>
            <div class="dropdown">
                <button class="btn btn-warning text-dark dropdown-toggle border-0" type="button" data-bs-toggle="dropdown"><i class="bi bi-printer me-2"></i>Yazdır / Belge</button>
                <ul class="dropdown-menu dropdown-menu-dark">
                    <li><a class="dropdown-item" href="<?= url('/admin/orders/pdf?id=' . $order['id'] . '&type=invoice') ?>" target="_blank"><i class="bi bi-file-earmark-pdf me-2"></i>E-Arşiv Fatura PDF</a></li>
                    <li><a class="dropdown-item" href="<?= url('/admin/orders/pdf?id=' . $order['id'] . '&type=packing_slip') ?>" target="_blank"><i class="bi bi-truck me-2"></i>Sevk İrsaliyesi PDF</a></li>
                    <li><a class="dropdown-item" href="<?= url('/admin/orders/pdf?id=' . $order['id'] . '&type=order_form') ?>" target="_blank"><i class="bi bi-file-text me-2"></i>Sipariş Formu PDF</a></li>
                    <li><a class="dropdown-item" href="<?= url('/admin/orders/pdf?id=' . $order['id'] . '&type=shipping_label') ?>" target="_blank"><i class="bi bi-tag me-2"></i>Kargo Barkod Etiketi PDF</a></li>
                </ul>
            </div>
            <a href="<?= url('/admin/orders') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Listeye Dön</a>
        </div>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger p-3 rounded-3 mb-4">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- SOL KOLON: MÜŞTERİ, ADRESLER, KALEMLER -->
    <div class="col-lg-8">
        <!-- 1. Müşteri & Adres Bilgileri -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <div class="row g-3">
                <div class="col-md-6 border-end border-secondary">
                    <h4 class="text-warning font-weight-600 mb-3 fs-6"><i class="bi bi-person-fill me-2"></i>Fatura Adresi & Alıcı</h4>
                    <p class="text-white m-0"><strong><?= htmlspecialchars($order['billing_first_name'] . ' ' . $order['billing_last_name']) ?></strong></p>
                    <p class="text-muted m-0 fs-7"><?= htmlspecialchars($order['billing_address']) ?></p>
                    <p class="text-muted m-0 fs-7"><?= htmlspecialchars($order['billing_city'] . ' / ' . $order['billing_country'] . ' ' . $order['billing_zip']) ?></p>
                    <p class="text-muted mt-2 mb-0 fs-7"><strong>Telefon:</strong> <?= htmlspecialchars($order['customer_phone'] ?? '-') ?></p>
                    <p class="text-muted m-0 fs-7"><strong>E-Posta:</strong> <?= htmlspecialchars($order['customer_email'] ?? '-') ?></p>
                </div>
                <div class="col-md-6">
                    <h4 class="text-warning font-weight-600 mb-3 fs-6"><i class="bi bi-truck me-2"></i>Teslimat Adresi & Alıcı</h4>
                    <p class="text-white m-0"><strong><?= htmlspecialchars($order['shipping_first_name'] . ' ' . $order['shipping_last_name']) ?></strong></p>
                    <p class="text-muted m-0 fs-7"><?= htmlspecialchars($order['shipping_address']) ?></p>
                    <p class="text-muted m-0 fs-7"><?= htmlspecialchars($order['shipping_city'] . ' / ' . $order['shipping_country'] . ' ' . $order['shipping_zip']) ?></p>
                </div>
            </div>
        </div>

        <!-- 2. Sipariş Kalemleri -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="text-white font-weight-600 m-0 fs-6"><i class="bi bi-basket2-fill me-2 text-warning"></i>Sipariş Kalemleri (<?= count($items) ?>)</h4>
                <a href="<?= url('/admin/orders/edit?id=' . $order['id']) ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil-square me-1"></i>Kalemleri Düzenle</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless">
                    <thead>
                        <tr class="text-muted fs-7 border-bottom border-secondary">
                            <th>Ürün Görseli</th>
                            <th>Ürün / SKU</th>
                            <th>Birim Fiyat</th>
                            <th>KDV</th>
                            <th>Adet</th>
                            <th class="text-end">Toplam</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        <?php foreach ($items as $item): ?>
                            <tr class="border-bottom border-secondary border-opacity-25">
                                <td width="70">
                                    <div style="width: 50px; height: 50px; background: rgba(255,255,255,0.03); border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid var(--sm-border);">
                                        <?php if (!empty($item['product_image'])): ?>
                                            <img src="<?= url($item['product_image']) ?>" style="width:100%; height:100%; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="bi bi-image text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-weight-600 text-white"><?= htmlspecialchars($item['product_name']) ?></div>
                                    <small class="text-muted">SKU: <?= htmlspecialchars($item['product_sku']) ?></small>
                                </td>
                                <td><?= number_format((float)$item['price'], 2) ?> TRY</td>
                                <td><?= number_format((float)$item['tax_amount'], 2) ?> TRY</td>
                                <td><?= $item['quantity'] ?></td>
                                <td class="text-end font-weight-600 text-warning"><?= number_format((float)$item['total'], 2) ?> TRY</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Raporlar & Ekstra Tablar -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <ul class="nav nav-tabs border-0 mb-4 gap-2 flex-wrap" id="detailTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active rounded-3 py-2 px-3 fs-7" id="tab-ship" data-bs-toggle="tab" data-bs-target="#panel-ship" type="button" role="tab">Kargo Sevkleri (<?= count($shipments) ?>)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-pay" data-bs-toggle="tab" data-bs-target="#panel-pay" type="button" role="tab">Ödemeler & POS (<?= count($transactions) ?>)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-ref" data-bs-toggle="tab" data-bs-target="#panel-ref" type="button" role="tab">İadeler (<?= count($refunds) ?>)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-3 py-2 px-3 fs-7" id="tab-audit" data-bs-toggle="tab" data-bs-target="#panel-audit" type="button" role="tab">Geçmiş & Audit</button>
                </li>
            </ul>

            <div class="tab-content" id="detailTabsContent">
                <!-- KARGOLAR SEKMESİ -->
                <div class="tab-pane fade show active" id="panel-ship" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-white fs-7 m-0">Kargo Gönderileri</h5>
                        <div>
                            <a href="<?= url('/admin/orders/partial-shipment?id=' . $order['id']) ?>" class="btn btn-sm btn-outline-info me-2 border-0"><i class="bi bi-truck-flatbed me-1"></i>Kısmi Gönderim</a>
                            <button class="btn btn-sm btn-warning text-dark border-0" data-bs-toggle="modal" data-bs-target="#shipmentModal"><i class="bi bi-plus-circle me-1"></i>Yeni Kargo Ekle</button>
                        </div>
                    </div>
                    <?php if (empty($shipments)): ?>
                        <div class="text-center py-3 text-muted fs-7">Henüz sevk edilmiş kargo kaydı yok.</div>
                    <?php else: ?>
                        <div class="table-responsive fs-7 text-white">
                            <table class="table">
                                <thead>
                                    <tr class="text-muted">
                                        <th>Firma</th>
                                        <th>Takip No</th>
                                        <th>Durum</th>
                                        <th>Tahmini Teslim</th>
                                        <th>Kargo Notu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($shipments as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['carrier_name'] ?? 'Diğer') ?></td>
                                            <td><strong><?= htmlspecialchars($s['tracking_number'] ?? '-') ?></strong></td>
                                            <td><?= htmlspecialchars($s['status']) ?></td>
                                            <td><?= $s['estimated_delivery'] ? date('d.m.Y', strtotime($s['estimated_delivery'])) : '-' ?></td>
                                            <td><small><?= htmlspecialchars($s['notes'] ?? '') ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ÖDEMELER SEKMESİ -->
                <div class="tab-pane fade" id="panel-pay" role="tabpanel">
                    <h5 class="text-white fs-7 mb-3">Ödeme vePOS Kayıtları</h5>
                    <?php if (empty($transactions)): ?>
                        <div class="text-center py-3 text-muted fs-7">Ödeme hareketi bulunamadı.</div>
                    <?php else: ?>
                        <div class="table-responsive fs-7 text-white">
                            <table class="table">
                                <thead>
                                    <tr class="text-muted">
                                        <th>Referans</th>
                                        <th>Yöntem</th>
                                        <th>Tutar</th>
                                        <th>Durum</th>
                                        <th>Tarih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $t): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($t['transaction_reference']) ?></td>
                                            <td><?= htmlspecialchars($t['payment_method_name'] ?? 'Sanal POS') ?></td>
                                            <td class="<?= $t['amount'] < 0 ? 'text-danger' : 'text-success' ?>"><strong><?= number_format((float)$t['amount'], 2) ?> TRY</strong></td>
                                            <td><?= htmlspecialchars($t['status']) ?></td>
                                            <td><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- İADELER SEKMESİ -->
                <div class="tab-pane fade" id="panel-ref" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-white fs-7 m-0">İade ve İptal Kayıtları</h5>
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#refundModal"><i class="bi bi-plus-circle me-1"></i>Yeni İade Kaydı Ekle</button>
                    </div>
                    <?php if (empty($refunds)): ?>
                        <div class="text-center py-3 text-muted fs-7">İade kaydı bulunamadı.</div>
                    <?php else: ?>
                        <div class="table-responsive fs-7 text-white">
                            <table class="table">
                                <thead>
                                    <tr class="text-muted">
                                        <th>İade Tipi</th>
                                        <th>İade Nedeni</th>
                                        <th>Tutar</th>
                                        <th>Durum</th>
                                        <th>Tarih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($refunds as $rf): ?>
                                        <tr>
                                            <td><?= $rf['type'] === 'full' ? 'Tam İade' : 'Kısmi İade' ?></td>
                                            <td><?= htmlspecialchars($rf['reason']) ?></td>
                                            <td class="text-danger font-weight-600"><?= number_format((float)$rf['amount'], 2) ?> TRY</td>
                                            <td><?= htmlspecialchars($rf['status']) ?></td>
                                            <td><?= date('d.m.Y', strtotime($rf['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- GEÇMİŞ SEKMESİ -->
                <div class="tab-pane fade" id="panel-audit" role="tabpanel">
                    <h5 class="text-white fs-7 mb-3">Denetim Tarihçesi (Audit Logs)</h5>
                    <div class="table-responsive fs-7 text-white">
                        <table class="table">
                            <thead>
                                <tr class="text-muted">
                                    <th>İşlem Yapan</th>
                                    <th>Açıklama</th>
                                    <th>IP</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($h['admin_name'] ?? 'Sistem') ?></strong></td>
                                        <td><?= htmlspecialchars($h['comment'] ?? $h['status']) ?></td>
                                        <td>-</td>
                                        <td><?= date('d.m.Y H:i', strtotime($h['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SAĞ KOLON: ÖDEME BİLGİSİ, TUTARLAR, SİPARİŞ NOTLARI -->
    <div class="col-lg-4">
        <!-- 1. Tutar Bilgileri Card -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6">Sipariş Tutarı</h4>
            <div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25 fs-7 text-white">
                <span class="text-muted">Ara Toplam</span>
                <span><?= number_format((float)$order['subtotal'], 2) ?> TRY</span>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25 fs-7 text-white">
                <span class="text-muted">KDV Toplamı</span>
                <span><?= number_format((float)$order['tax_total'], 2) ?> TRY</span>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25 fs-7 text-white">
                <span class="text-muted">İndirim Tutar</span>
                <span class="text-danger">-<?= number_format((float)$order['discount_total'], 2) ?> TRY</span>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25 fs-7 text-white">
                <span class="text-muted">Kargo Tutar</span>
                <span><?= number_format((float)$order['shipping_total'], 2) ?> TRY</span>
            </div>
            <div class="d-flex justify-content-between py-3 border-bottom border-secondary fs-6 text-white font-weight-700">
                <span class="text-warning">Genel Toplam</span>
                <span class="text-warning"><?= number_format((float)$order['grand_total'], 2) ?> TRY</span>
            </div>
        </div>

        <!-- 2. Sipariş Notları -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-chat-text-fill me-2 text-warning"></i>Sipariş Notları</h4>
            
            <form action="<?= url('/admin/orders/add-note') ?>" method="POST" class="mb-3">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <textarea name="note" class="search-input w-100 text-white mb-2 fs-7" rows="3" placeholder="Yeni not ekleyin..."></textarea>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="form-check form-check-inline fs-7 text-muted">
                        <input class="form-check-input" type="checkbox" name="is_internal" id="internalCheck" value="1" checked>
                        <label class="form-check-input-label" for="internalCheck">İç Not (Müşteri görmez)</label>
                    </div>
                    <button type="submit" class="btn btn-sm btn-warning text-dark border-0">Ekle</button>
                </div>
            </form>

            <div style="max-height: 250px; overflow-y: auto;">
                <?php foreach ($notes as $n): ?>
                    <div class="p-2 mb-2 rounded fs-7" style="background: <?= $n['is_internal'] ? 'rgba(255, 193, 7, 0.05)' : 'rgba(255, 255, 255, 0.03)' ?>; border: 1px solid <?= $n['is_internal'] ? 'rgba(255, 193, 7, 0.15)' : 'var(--sm-border)' ?>;">
                        <div class="d-flex justify-content-between text-muted fs-8 mb-1">
                            <span><?= htmlspecialchars($n['admin_name'] ?? 'Müşteri') ?></span>
                            <span><?= date('d.m.Y H:i', strtotime($n['created_at'])) ?></span>
                        </div>
                        <p class="text-white m-0" style="font-size:12px;"><?= nl2br(htmlspecialchars($n['note'])) ?></p>
                        <?php if ($n['is_internal']): ?>
                            <span class="badge bg-warning text-dark fs-9 p-1 mt-1" style="font-size:9px;">İÇ NOT</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ───────────────────────────────────────────────────────────── -->
<!-- MODALLAR -->
<!-- ───────────────────────────────────────────────────────────── -->

<!-- Durum Güncelle Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content text-white" action="<?= url('/admin/orders/edit') ?>" method="POST" style="background: #111; border: 1px solid var(--sm-border);">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" value="<?= $order['id'] ?>">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Sipariş Durumunu Güncelle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted fs-7">Sipariş Durumu</label>
                    <select name="status" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.05); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= $st['code'] ?>" <?= $order['status'] === $st['code'] ? 'selected' : '' ?>><?= htmlspecialchars($st['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7">Güncelleme Notu (Tarihçe ve Log için)</label>
                    <textarea name="status_comment" class="search-input w-100 text-white" rows="3" placeholder="Örn: Ödeme onaylandı, kargo paketleniyor..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-warning text-dark border-0">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- Kargo Ekle Modal -->
<div class="modal fade" id="shipmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content text-white" action="<?= url('/admin/orders/add-shipment') ?>" method="POST" style="background: #111; border: 1px solid var(--sm-border);">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Yeni Kargo Sevk Kaydı Ekle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted fs-7">Kargo Firması</label>
                    <input type="text" name="carrier_name" class="search-input w-100 text-white" value="Yurtiçi Kargo" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7">Takip Numarası</label>
                    <input type="text" name="tracking_number" class="search-input w-100 text-white" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7">Tahmini Teslimat Tarihi</label>
                    <input type="date" name="estimated_delivery" class="search-input w-100 text-white">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7">Kargo Notu</label>
                    <textarea name="notes" class="search-input w-100 text-white" rows="2"></textarea>
                </div>
                <div class="form-check form-switch fs-7 mb-2">
                    <input class="form-check-input" type="checkbox" name="update_order_status" id="updateOrderStatusCheck" value="1" checked>
                    <label class="form-check-label text-muted" for="updateOrderStatusCheck">Sipariş Durumunu 'Kargoya Verildi' Yap</label>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-warning text-dark border-0">Kargoya Ver</button>
            </div>
        </form>
    </div>
</div>

<!-- İade Kaydı Ekle Modal -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content text-white" action="<?= url('/admin/orders/add-refund') ?>" method="POST" enctype="multipart/form-data" style="background: #111; border: 1px solid var(--sm-border);">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">İade Talebi / Tutar İadesi Girişi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted fs-7">İade Tipi</label>
                    <select name="type" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.05); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <option value="full">Tam İade (Sipariş Tutarının Tamamı)</option>
                        <option value="partial">Kısmi İade</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7">İade Edilecek Tutar (TRY)</label>
                    <input type="number" step="0.01" name="amount" class="search-input w-100 text-white" value="<?= $order['grand_total'] ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7">İade Nedeni</label>
                    <textarea name="reason" class="search-input w-100 text-white" rows="2" required placeholder="Müşteri vazgeçti, kusurlu ürün vb."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7">İade Durumu</label>
                    <select name="status" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.05); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <option value="pending">Onay Bekliyor</option>
                        <option value="approved">Onaylandı (Ödeme çıkışı kaydı oluşturur)</option>
                        <option value="rejected">Reddedildi</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7">Kanıt Fotoğraf / Belge</label>
                    <input type="file" name="image" class="form-control bg-dark border-secondary text-white fs-7">
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-warning text-dark border-0">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
