<?php
use App\Helpers\ComponentHelper;
$title = "Tedarikçi Sözleşmeleri | SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="container-fluid py-4 text-white">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim' => url('/admin'), 'Satın Alma' => url('/admin/purchasing/dashboard'), 'Sözleşmeler' => '#']) ?>
            <h2 class="mt-2 text-white font-weight-800 fs-3">Tedarikçi Sözleşmeleri & Kontrat Yönetimi</h2>
            <p class="text-muted mb-0 fs-7">Sözleşme süreleri, yenileme periyotları ve otomatik hatırlatma servis ayarları.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-warning rounded-pill px-4 font-weight-600" data-bs-toggle="modal" data-bs-target="#createContractModal"><i class="bi bi-plus-circle me-1"></i> Yeni Sözleşme Yükle</button>
        </div>
    </div>

    <!-- Contracts Grid -->
    <div class="card bg-dark border-secondary border-opacity-10 p-3">
        <div class="table-responsive">
            <table class="table table-dark table-hover border-secondary border-opacity-10 align-middle fs-8">
                <thead>
                    <tr>
                        <th>Tedarikçi</th>
                        <th>Sözleşme Başlığı</th>
                        <th>Başlangıç Tarihi</th>
                        <th>Bitiş Tarihi</th>
                        <th>Yenileme Tarihi</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contracts)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>Kayıtlı sözleşme bulunmamaktadır.</td></tr>
                    <?php else: ?>
                        <?php foreach ($contracts as $c): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['supplier_name']) ?></strong></td>
                                <td><?= htmlspecialchars($c['title']) ?></td>
                                <td><?= date('d.m.Y', strtotime($c['start_date'])) ?></td>
                                <td><?= date('d.m.Y', strtotime($c['end_date'])) ?></td>
                                <td><?= $c['renewal_date'] ? date('d.m.Y', strtotime($c['renewal_date'])) : '-' ?></td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success text-uppercase">
                                        <?= htmlspecialchars($c['status']) ?>
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

<!-- Create Contract Modal -->
<div class="modal fade" id="createContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary border-opacity-25 text-white">
            <div class="modal-header border-secondary border-opacity-10">
                <h5 class="modal-title font-weight-800 text-white">Yeni Sözleşme Tanımla</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="#" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="modal-body row g-3 fs-8">
                    <div class="col-12">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Tedarikçi Seçin *</label>
                        <select name="supplier_id" class="form-select bg-dark border-secondary border-opacity-25 text-white" required>
                            <option value="">-- Tedarikçi Seçin --</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Sözleşme Başlığı *</label>
                        <input type="text" name="title" class="form-control bg-dark border-secondary border-opacity-25 text-white" required>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Başlangıç Tarihi *</label>
                        <input type="date" name="start_date" class="form-control bg-dark border-secondary border-opacity-25 text-white" required>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label text-muted fs-8 font-weight-700 text-uppercase">Bitiş Tarihi *</label>
                        <input type="date" name="end_date" class="form-control bg-dark border-secondary border-opacity-25 text-white" required>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-10">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-warning font-weight-600" onclick="alert('Entegrasyon aktif edildiğinde yüklenecektir.')">Kontratı Tanımla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
