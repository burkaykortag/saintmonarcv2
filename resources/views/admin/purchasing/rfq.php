<?php
use App\Helpers\ComponentHelper;
$title = "Teklif İstekleri (RFQ) | SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="container-fluid py-4 text-white">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim' => url('/admin'), 'Satın Alma' => url('/admin/purchasing/dashboard'), 'Teklifler (RFQ)' => '#']) ?>
            <h2 class="mt-2 text-white font-weight-800 fs-3">Teklif İstekleri (Request for Quotation)</h2>
            <p class="text-muted mb-0 fs-7">Ürün tedariği için tedarikçilerden fiyat ve termin teklifleri toplama ve AI karşılaştırma.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-warning rounded-pill px-4 font-weight-600" data-bs-toggle="modal" data-bs-target="#createRfqModal"><i class="bi py-plus-circle me-1"></i> Yeni RFQ Talebi Oluştur</button>
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
        <!-- RFQ List -->
        <div class="col-12 col-lg-5">
            <div class="card bg-dark border-secondary border-opacity-10 p-3 h-100">
                <h5 class="font-weight-800 text-white mb-3 fs-6">Aktif RFQ Talepleri</h5>
                <div class="d-flex flex-column gap-3">
                    <?php if (empty($rfqs)): ?>
                        <div class="text-center py-5 text-muted">Aktif teklif talebi bulunmamaktadır.</div>
                    <?php else: ?>
                        <?php foreach ($rfqs as $r): ?>
                            <div class="p-3 bg-dark bg-opacity-50 border <?= $selectedRfqId === (int)$r['id'] ? 'border-warning' : 'border-secondary border-opacity-25' ?> rounded-3 hover-lift">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong class="text-white"><?= htmlspecialchars($r['title']) ?></strong>
                                    <span class="badge bg-<?= $r['status'] === 'active' ? 'warning' : 'success' ?> bg-opacity-10 text-<?= $r['status'] === 'active' ? 'warning' : 'success' ?> text-uppercase fs-9"><?= htmlspecialchars($r['status']) ?></span>
                                </div>
                                <p class="text-muted fs-8 mb-2">Ürün: <?= htmlspecialchars($r['product_name']) ?> (Adet: <?= $r['quantity'] ?>)</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fs-9 text-muted"><i class="bi bi-people-fill me-1"></i> <?= $r['response_count'] ?> Teklif Alındı</span>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-warning" onclick="openBidModal(<?= $r['id'] ?>)"><i class="bi bi-plus-lg"></i> Teklif Gir</button>
                                        <a href="<?= url('/admin/purchasing/rfq?id=' . $r['id']) ?>" class="btn btn-sm btn-warning font-weight-600">Karşılaştır</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RFQ Comparison & Bids Details -->
        <div class="col-12 col-lg-7">
            <div class="card bg-dark border-secondary border-opacity-10 p-4 h-100">
                <?php if (!$compareData): ?>
                    <div class="text-center py-5 text-muted my-auto">
                        <i class="bi bi-bar-chart-line fs-1 d-block mb-2"></i>
                        Teklif karşılaştırma analizi için sol listeden bir RFQ talebi seçin.
                    </div>
                <?php else: ?>
                    <h5 class="font-weight-800 text-white mb-4 fs-6">Teklif Karşılaştırma Analizi: <?= htmlspecialchars($compareData['rfq']['title']) ?></h5>
                    
                    <!-- Bids Grid Bids Summary cards -->
                    <div class="row g-3 mb-4 fs-8">
                        <div class="col-12 col-sm-4">
                            <div class="border border-secondary border-opacity-25 bg-dark bg-opacity-50 rounded p-3 text-center">
                                <small class="text-muted d-block mb-1">En Uygun Fiyat</small>
                                <?php if ($compareData['cheapest']): ?>
                                    <strong class="text-success fs-7">₺<?= number_format((float)$compareData['cheapest']['price'], 2) ?></strong>
                                    <small class="d-block text-muted mt-1"><?= htmlspecialchars($compareData['cheapest']['supplier_name']) ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Teklif Yok</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="border border-secondary border-opacity-25 bg-dark bg-opacity-50 rounded p-3 text-center">
                                <small class="text-muted d-block mb-1">En Kısa Teslimat</small>
                                <?php if ($compareData['fastest']): ?>
                                    <strong class="text-info fs-7"><?= $compareData['fastest']['delivery_lead_time'] ?> Gün</strong>
                                    <small class="d-block text-muted mt-1"><?= htmlspecialchars($compareData['fastest']['supplier_name']) ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Teklif Yok</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="border border-warning border-opacity-25 bg-warning bg-opacity-10 rounded p-3 text-center">
                                <small class="text-warning d-flex align-items-center justify-content-center gap-1 mb-1"><i class="bi bi-stars"></i> AI Önerisi</small>
                                <?php if ($compareData['ai_recommended']): ?>
                                    <strong class="text-warning fs-7">₺<?= number_format((float)$compareData['ai_recommended']['price'], 2) ?></strong>
                                    <small class="d-block text-muted mt-1"><?= htmlspecialchars($compareData['ai_recommended']['supplier_name']) ?> (<?= $compareData['ai_recommended']['delivery_lead_time'] ?> Gün)</small>
                                <?php else: ?>
                                    <span class="text-muted">Teklif Yok</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-white font-weight-700 mb-3 fs-8">Gelen Tüm Teklifler</h6>
                    <table class="table table-dark table-hover border-secondary border-opacity-10 align-middle fs-8 mb-0">
                        <thead>
                            <tr>
                                <th>Tedarikçi</th>
                                <th>Birim Fiyat</th>
                                <th>Teslimat Süresi</th>
                                <th>Tedarikçi AI Puanı</th>
                                <th>Öneri Durumu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($compareData['responses'])): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Bu talep için henüz verilmiş bir teklif bulunmamaktadır.</td></tr>
                            <?php else: ?>
                                <?php foreach ($compareData['responses'] as $resp): ?>
                                    <tr class="<?= $resp['is_recommended'] ? 'table-warning text-dark bg-warning bg-opacity-10' : '' ?>">
                                        <td><strong><?= htmlspecialchars($resp['supplier_name']) ?></strong></td>
                                        <td>₺<?= number_format((float)$resp['price'], 2) ?></td>
                                        <td><?= $resp['delivery_lead_time'] ?> Gün</td>
                                        <td>★ <?= number_format((float)$resp['supplier_score'], 1) ?></td>
                                        <td>
                                            <?php if ($resp['is_recommended']): ?>
                                                <span class="badge bg-warning text-dark font-weight-800"><i class="bi bi-stars"></i> En Uygun AI</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create RFQ Modal -->
<div class="modal fade" id="createRfqModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary border-opacity-25 text-white">
            <div class="modal-header border-secondary border-opacity-10">
                <h5 class="modal-title font-weight-800 text-white">Yeni Teklif Talebi (RFQ)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('/admin/purchasing/rfq/create') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="modal-body row g-3 fs-8">
                    <div class="col-12">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Talep / Proje Başlığı *</label>
                        <input type="text" name="title" class="form-control bg-dark border-secondary border-opacity-25 text-white" required placeholder="Örn: 2026 Kış Sezonu Mont Alımı">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Ürün Seçimi *</label>
                        <select name="product_id" class="form-select bg-dark border-secondary border-opacity-25 text-white" required>
                            <option value="">-- Ürün Seçin --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (SKU: <?= htmlspecialchars($p['sku']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Talep Edilen Miktar *</label>
                        <input type="number" name="quantity" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="100" min="1" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Detaylar / Şartname</label>
                        <textarea name="description" rows="3" class="form-control bg-dark border-secondary border-opacity-25 text-white" placeholder="Kumaş kalitesi, paketleme kuralları vb."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-10">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning font-weight-600">Talep Oluştur</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Submit Bid Modal -->
<div class="modal fade" id="submitBidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary border-opacity-25 text-white">
            <div class="modal-header border-secondary border-opacity-10">
                <h5 class="modal-title font-weight-800 text-white">Tedarikçi Teklifi Gir</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('/admin/purchasing/rfq/response') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="rfq_id" id="modalRfqId">
                <div class="modal-body row g-3 fs-8">
                    <div class="col-12">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Tedarikçi Firma *</label>
                        <select name="supplier_id" class="form-select bg-dark border-secondary border-opacity-25 text-white" required>
                            <option value="">-- Tedarikçi Seçin --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Birim Alış Teklifi (TL) *</label>
                        <input type="number" step="0.01" name="price" class="form-control bg-dark border-secondary border-opacity-25 text-white" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Teslimat Süresi (Gün) *</label>
                        <input type="number" name="delivery_lead_time" class="form-control bg-dark border-secondary border-opacity-25 text-white" value="5" min="1" required>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-10">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning font-weight-600">Teklifi Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openBidModal(rfqId) {
    document.getElementById('modalRfqId').value = rfqId;
    const modal = new bootstrap.Modal(document.getElementById('submitBidModal'));
    modal.show();
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
