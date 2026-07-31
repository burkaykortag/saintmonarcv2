<?php
use App\Helpers\ComponentHelper;

$title = "Arama Motoru Kontrol Paneli - SaintMonarc";
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
.stat-mini-box {
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--sm-border);
    border-radius: 12px;
    padding: 16px;
    text-align: center;
}
</style>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Arama Motoru' => '#', 'Genel Kontrol' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Arama Motoru Kontrol Paneli</h2>
        <div class="d-flex gap-2">
            <a href="<?= url('/admin/search/statistics') ?>" class="btn btn-outline-info border-0"><i class="bi bi-bar-chart-line me-2"></i>Arama İstatistikleri</a>
            <a href="<?= url('/admin/search/synonyms') ?>" class="btn btn-outline-warning border-0"><i class="bi bi-shuffle me-2"></i>Eş Anlamlılar & Stop Words</a>
            <a href="<?= url('/admin/search/boost') ?>" class="btn btn-outline-success border-0"><i class="bi bi-sort-numeric-up-alt me-2"></i>Boost Kuralları</a>
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

<div class="row g-4 text-white">
    <!-- SOL TARAF: İNDEKS VE YÖNETİM -->
    <div class="col-lg-6">
        <div class="section-card">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-cpu text-warning me-2"></i>İndeks Yönetim Paneli</h4>
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="stat-mini-box">
                        <div class="text-muted fs-8">Toplam İndekslenen İçerik</div>
                        <h3 class="text-warning font-weight-700 mt-2 mb-0"><?= number_format($indexCount) ?></h3>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-mini-box">
                        <div class="text-muted fs-8">Performans Kapasitesi</div>
                        <h3 class="text-success font-weight-700 mt-2 mb-0">5.000.000+</h3>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="<?= url('/admin/search/rebuild') ?>" class="btn btn-warning text-dark border-0 w-50 py-3 font-weight-700 fs-7" onclick="return confirm('Tüm arama indeksini yeniden oluşturmak istiyor musunuz? Bu işlem kayıt sayısına bağlı olarak birkaç dakika sürebilir.')">
                    <i class="bi bi-arrow-repeat me-2"></i>İndeksi Yeniden Oluştur
                </a>
                <a href="<?= url('/admin/search/clear-cache') ?>" class="btn btn-outline-danger w-50 py-3 font-weight-700 fs-7">
                    <i class="bi bi-trash3 me-2"></i>Arama Önbelleğini Temizle
                </a>
            </div>
        </div>

        <div class="section-card">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-clock-history text-warning me-2"></i>İndeks Yeniden Oluşturma Günlükleri</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Başlangıç</th>
                            <th>Bitiş</th>
                            <th>Toplam İndeks</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rebuildLogs)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Kayıtlı log bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rebuildLogs as $log): ?>
                                <tr>
                                    <td><?= date('d.m.Y H:i', strtotime($log['started_at'])) ?></td>
                                    <td><?= $log['finished_at'] ? date('d.m.Y H:i', strtotime($log['finished_at'])) : '-' ?></td>
                                    <td><strong><?= $log['total_indexed'] ?></strong></td>
                                    <td>
                                        <span class="badge bg-opacity-10 text-capitalize bg-<?= $log['status'] === 'success' ? 'success text-success' : 'danger text-danger' ?>">
                                            <?= htmlspecialchars($log['status']) ?>
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

    <!-- SAĞ TARAF: YÖNLENDİRMELER (SEARCH REDIRECTS) -->
    <div class="col-lg-6">
        <div class="section-card">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-reply-fill text-warning me-2"></i>Arama Yönlendirme Yönetimi (301 Redirects)</h4>
            
            <form action="<?= url('/admin/search/redirects/create') ?>" method="POST" class="mb-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="keyword" required class="search-input w-100 text-white" placeholder="örn: cep telefonu">
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="redirect_url" required class="search-input w-100 text-white" placeholder="örn: /categories/cep-telefonlari">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-warning text-dark border-0 w-100 py-2 fs-7 font-weight-700">Ekle</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle text-white table-borderless fs-7">
                    <thead>
                        <tr class="text-muted border-bottom border-secondary border-opacity-25">
                            <th>Aranan Kelime</th>
                            <th>Yönlendirilecek URL</th>
                            <th width="80" class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($redirects)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Kayıtlı yönlendirme bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($redirects as $r): ?>
                                <tr>
                                    <td><code class="text-warning"><?= htmlspecialchars($r['keyword']) ?></code></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($r['redirect_url']) ?></small></td>
                                    <td class="text-end">
                                        <form action="<?= url('/admin/search/redirects/delete') ?>" method="POST" onsubmit="return confirm('Bu yönlendirmeyi silmek istediğinize emin misiniz?')">
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
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
