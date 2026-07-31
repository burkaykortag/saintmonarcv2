<?php
use App\Helpers\ComponentHelper;

$title = "Eş Anlamlılar & Stop Words - SaintMonarc";
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
        'Eş Anlamlılar & Stop Words' => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Eş Anlamlı Kelimeler & Stop Words Filtresi</h2>
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
    <!-- SOL KOLON: EŞ ANLAMLI KELİMELER -->
    <div class="col-lg-6">
        <div class="section-card">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-shuffle text-warning me-2"></i>Synonym Engine (Eş Anlamlı Kelime Yönetimi)</h4>
            
            <!-- Synonym Ekleme Formu -->
            <form action="<?= url('/admin/search/synonyms/create') ?>" method="POST" class="mb-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="source_word" required class="search-input w-100 text-white" placeholder="Aranan (örn: notebook)">
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="target_words" required class="search-input w-100 text-white" placeholder="Eş anlamlılar (örn: laptop, bilgisayar)">
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
                            <th>Eş Anlamlı Hedefler</th>
                            <th width="80" class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($synonyms)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Eş anlamlı kelime tanımlanmamış.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($synonyms as $s): ?>
                                <tr>
                                    <td><code class="text-warning"><?= htmlspecialchars($s['source_word']) ?></code></td>
                                    <td><span class="text-muted"><?= htmlspecialchars($s['target_words']) ?></span></td>
                                    <td class="text-end">
                                        <form action="<?= url('/admin/search/synonyms/delete') ?>" method="POST" onsubmit="return confirm('Bu eş anlamlı kelimeyi silmek istediğinize emin misiniz?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
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

    <!-- SAĞ KOLON: STOP WORDS -->
    <div class="col-lg-6">
        <div class="section-card">
            <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-x-circle text-warning me-2"></i>Stop Words (Yok Sayılacak Kelime Filtresi)</h4>
            
            <!-- Stop Word Ekleme Formu -->
            <form action="<?= url('/admin/search/stopwords/create') ?>" method="POST" class="mb-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="word" required class="search-input w-100 text-white" placeholder="Filtrelenecek kelime (örn: veya, zira, fakat)">
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
                            <th>Filtrelenen Kelime</th>
                            <th>Durum</th>
                            <th width="80" class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stopWords)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Stop word tanımlanmamış.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stopWords as $sw): ?>
                                <tr>
                                    <td><code class="text-warning"><?= htmlspecialchars($sw['word']) ?></code></td>
                                    <td><span class="badge bg-success bg-opacity-10 text-success">Aktif</span></td>
                                    <td class="text-end">
                                        <form action="<?= url('/admin/search/stopwords/delete') ?>" method="POST" onsubmit="return confirm('Bu kelimeyi stop-words listesinden silmek istediğinize emin misiniz?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $sw['id'] ?>">
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
