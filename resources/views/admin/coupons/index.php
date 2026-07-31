<?php
use App\Helpers\ComponentHelper;

$title = "Kupon Yönetimi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Kampanyalar' => url('/admin/promotions'), 'Kuponlar' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Kupon ve İndirim Kodları</h2>
        <a href="<?= url('/admin/promotions') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Kampanya Listesi</a>
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

<div class="row g-4 text-white">
    <!-- SOL KOLON: KUPONLAR LİSTESİ -->
    <div class="col-lg-8">
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6">Aktif Kupon Kodları</h4>
            
            <div class="table-responsive">
                <table class="table align-middle text-white table-hover">
                    <thead>
                        <tr class="text-muted fs-7 border-bottom border-secondary">
                            <th>Kod</th>
                            <th>İlişkili Kampanya</th>
                            <th>Limitler (Kişi/Top)</th>
                            <th>Min Sepet</th>
                            <th>Kullanım Adedi</th>
                            <th width="100" class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        <?php if (empty($coupons)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Kayıtlı kupon bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($coupons as $c): ?>
                                <tr>
                                    <td><code class="text-warning fs-6"><?= htmlspecialchars($c['code']) ?></code></td>
                                    <td><?= htmlspecialchars($c['promotion_name']) ?></td>
                                    <td>
                                        <small>Kişi: <?= $c['user_limit'] ?></small><br>
                                        <small class="text-muted">Top: <?= $c['total_limit'] > 0 ? $c['total_limit'] : 'Sınırsız' ?></small>
                                    </td>
                                    <td><?= number_format((float)$c['min_cart_amount'], 2) ?> TRY</td>
                                    <td><span class="badge bg-success"><?= $c['used_count'] ?> Kullanım</span></td>
                                    <td class="text-end">
                                        <button type="button" onclick="deleteCoupon(<?= $c['id'] ?>)" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SAĞ KOLON: KUPON OLUŞTURMA -->
    <div class="col-lg-4">
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6">Kupon Kodu Tanımla</h4>
            
            <form action="<?= url('/admin/coupons/create') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Bağlanacak Kampanya</label>
                    <select name="promotion_id" required class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <?php foreach ($promotions as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Kupon Kodu (Örn: SAVE15)</label>
                    <input type="text" name="code" class="search-input w-100 text-white" placeholder="Bırakırsanız otomatik üretilir...">
                    <div class="form-check mt-1 fs-8">
                        <input class="form-check-input" type="checkbox" name="auto_code" value="1" id="autoCheck">
                        <label class="form-check-label text-muted" for="autoCheck">Rastgele kod üretilsin</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Kullanım Türü</label>
                    <select name="usage_type" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <option value="multiple">Çoklu Kullanım (Farklı Kişiler)</option>
                        <option value="single">Tek Kullanımlık (Özel Seri)</option>
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted fs-8 mb-1">Toplam Kullanım Sınırı</label>
                        <input type="number" name="total_limit" class="search-input w-100 text-white" value="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted fs-8 mb-1">Kişi Başı Kullanım Sınırı</label>
                        <input type="number" name="user_limit" class="search-input w-100 text-white" value="1">
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted fs-8 mb-1">Min Sepet (TRY)</label>
                        <input type="number" step="0.01" name="min_cart_amount" class="search-input w-100 text-white" value="0.00">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted fs-8 mb-1">Maks İndirim (TRY)</label>
                        <input type="number" step="0.01" name="max_discount_amount" class="search-input w-100 text-white" value="0.00">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Başlangıç Tarihi</label>
                    <input type="datetime-local" name="start_date" class="search-input w-100 text-white">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Bitiş Tarihi</label>
                    <input type="datetime-local" name="end_date" class="search-input w-100 text-white">
                </div>

                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 w-100 py-3 font-weight-700 mt-2">Kupon Kodunu Oluştur</button>
            </form>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;" action="<?= url('/admin/coupons/delete') ?>">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function deleteCoupon(id) {
    if (confirm('Bu kupon kodunu silmek istediğinize emin misiniz?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
