<?php
use App\Helpers\ComponentHelper;

$title = "Müşteri Segmentasyonu - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Müşteriler' => url('/admin/customers'),
        'Dinamik Segmentler' => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Dinamik Segmentasyon Yönetimi</h2>
        <a href="<?= url('/admin/customers') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Müşteri Listesi</a>
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
    <!-- SOL TARAF: SEGMENT LİSTESİ -->
    <div class="col-lg-8">
        <div class="card p-4 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6">Kayıtlı Dinamik Segmentasyon Kuralları</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-hover">
                    <thead>
                        <tr class="text-muted fs-7 border-bottom border-secondary">
                            <th>Segment Adı</th>
                            <th>Açıklama</th>
                            <th>Aktif Kurallar</th>
                            <th width="100" class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        <?php foreach ($segments as $s): ?>
                            <?php $rules = json_decode($s['rules'], true); ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                <td><span class="text-muted"><?= htmlspecialchars($s['description'] ?? '-') ?></span></td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <?php if (isset($rules['days_since_last_order'])): ?>
                                            <span><i class="bi bi-calendar-check me-1 text-warning"></i>Son Sipariş Tarihi < <?= $rules['days_since_last_order'] ?> Gün</span>
                                        <?php endif; ?>
                                        <?php if (isset($rules['min_total_spent'])): ?>
                                            <span><i class="bi bi-cash me-1 text-success"></i>Min Toplam Harcama >= <?= number_format($rules['min_total_spent'], 2) ?> TRY</span>
                                        <?php endif; ?>
                                        <?php if (isset($rules['min_orders_count'])): ?>
                                            <span><i class="bi bi-cart me-1 text-info"></i>Min Sipariş Sayısı >= <?= $rules['min_orders_count'] ?></span>
                                        <?php endif; ?>
                                        <?php if (isset($rules['orders_count']) && $rules['orders_count'] === 0): ?>
                                            <span><i class="bi bi-cart-x me-1 text-danger"></i>Hiç Sipariş Vermeyenler</span>
                                        <?php endif; ?>
                                        <?php if (isset($rules['days_since_last_login'])): ?>
                                            <span><i class="bi bi-clock-history me-1 text-muted"></i>Giriş Yapılmayan Gün >= <?= $rules['days_since_last_login'] ?> Gün</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <?php if ($s['id'] <= 5): ?>
                                        <span class="badge bg-secondary text-dark fs-8 p-1">Sistem Segmenti</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSegment(<?= $s['id'] ?>)"><i class="bi bi-trash"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SAĞ TARAF: YENİ EKLE FORMU -->
    <div class="col-lg-4">
        <div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;" id="createBox">
            <h4 class="text-white font-weight-600 mb-3 fs-6">Dinamik Segment Oluştur</h4>
            <form action="<?= url('/admin/customers/segments/create') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Segment Adı</label>
                    <input type="text" name="name" required class="search-input w-100 text-white" placeholder="örn: Yeni Fırsat Segmenti">
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Açıklama</label>
                    <input type="text" name="description" class="search-input w-100 text-white" placeholder="örn: Bu segmenttekilere özel kampanya yapılacak.">
                </div>

                <hr class="border-secondary opacity-25">
                <h5 class="text-warning fs-7 mb-3">Segmentasyon Kuralları</h5>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Maksimum Son Sipariş Tarihi (Gün)</label>
                    <input type="number" name="rule_days_since_last_order" class="search-input w-100 text-white" placeholder="örn: 30">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Minimum Toplam Harcama (TRY)</label>
                    <input type="number" step="0.01" name="rule_min_total_spent" class="search-input w-100 text-white" placeholder="örn: 5000">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Minimum Sipariş Adedi</label>
                    <input type="number" name="rule_min_orders_count" class="search-input w-100 text-white" placeholder="örn: 5">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Sipariş Sayısı Eşitlik (Hiç Sipariş Vermeyen = 0)</label>
                    <input type="number" name="rule_orders_count" class="search-input w-100 text-white" placeholder="örn: 0">
                </div>

                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 w-100 py-2 font-weight-700">Segmenti Oluştur ve Uygula</button>
            </form>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;" action="<?= url('/admin/customers/segments/delete') ?>">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function deleteSegment(id) {
    if (confirm('Bu dinamik segmenti silmek istediğinize emin misiniz?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
