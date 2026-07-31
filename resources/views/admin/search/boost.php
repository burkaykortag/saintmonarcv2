<?php
use App\Helpers\ComponentHelper;

$title = "Boost Kuralları - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<style>
.section-card {
    background: rgba(255,255,255,0.01);
    border: 1px solid var(--sm-border) !important;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
}
</style>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Arama Motoru' => url('/admin/search'),
        'Boost Kuralları' => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Arama Puanı Öne Çıkarma (Search Boost) Kuralları</h2>
        <a href="<?= url('/admin/search') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Kontrol Paneli</a>
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
    <!-- SOL KOLON: KURALLAR LİSTESİ -->
    <div class="col-lg-8">
        <div class="section-card">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-sort-numeric-up-alt text-warning me-2"></i>Aktif Arama Puanlama (Boost) Kuralları</h4>
            
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Tür</th>
                            <th>Hedef (Ürün ID / Anahtar Kelime)</th>
                            <th>Arama Puan Çarpanı</th>
                            <th width="80" class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rules)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Boost kuralı tanımlanmamış.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rules as $r): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($r['target_type']) ?></span></td>
                                    <td>
                                        <?php if ($r['target_type'] === 'product'): ?>
                                            <strong>Ürün ID: <?= $r['target_id'] ?></strong>
                                        <?php else: ?>
                                            <code class="text-warning"><?= htmlspecialchars($r['keyword'] ?? '') ?></code>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong class="text-success">x<?= number_format((float)$r['boost_value'], 2) ?></strong></td>
                                    <td class="text-end">
                                        <form action="<?= url('/admin/search/boost/delete') ?>" method="POST" onsubmit="return confirm('Bu kuralı silmek istediğinize emin misiniz?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SAĞ KOLON: KURAL EKLEME -->
    <div class="col-lg-4">
        <div class="section-card">
            <h4 class="text-white font-weight-600 mb-3 fs-6">Yeni Puan Çarpanı Ekle</h4>
            
            <form action="<?= url('/admin/search/boost/create') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Boost Türü</label>
                    <select name="target_type" id="boostType" required class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <option value="keyword">Anahtar Kelime Bazlı</option>
                        <option value="product">Ürün Bazlı</option>
                    </select>
                </div>

                <div class="mb-3" id="productSelectWrapper" style="display:none;">
                    <label class="form-label text-muted fs-7 mb-1">Ürün Seçin</label>
                    <select name="target_id" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>">SKU: <?= htmlspecialchars($p['sku']) ?> (ID: <?= $p['id'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3" id="keywordWrapper">
                    <label class="form-label text-muted fs-7 mb-1">Anahtar Kelime</label>
                    <input type="text" name="keyword" class="search-input w-100 text-white" placeholder="örn: telefon">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Puan Çarpanı (Boost Value)</label>
                    <input type="number" step="0.1" min="0.1" name="boost_value" required class="search-input w-100 text-white" value="1.5">
                    <small class="text-muted fs-8">1.0 üzeri arama sonuçlarında üst sıralara çıkarır.</small>
                </div>

                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 w-100 py-3 font-weight-700 mt-2">Kuralı Kaydet</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('boostType').addEventListener('change', function() {
    var isProduct = this.value === 'product';
    document.getElementById('productSelectWrapper').style.display = isProduct ? 'block' : 'none';
    document.getElementById('keywordWrapper').style.display = isProduct ? 'none' : 'block';
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
