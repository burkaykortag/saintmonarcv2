<?php
use App\Helpers\ComponentHelper;

$title = "AI Öneri Motoru - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<style>
.ai-header-card {
    background: linear-gradient(135deg, rgba(197, 168, 128, 0.15) 0%, rgba(0, 0, 0, 0.2) 100%);
    border: 1px solid rgba(197, 168, 128, 0.3) !important;
    border-radius: 20px;
    padding: 24px;
}
.ai-card {
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--sm-border);
    border-radius: 16px;
    transition: all 0.3s ease;
}
.ai-card:hover {
    transform: translateY(-4px);
    border-color: var(--sm-gold, #c5a880);
    box-shadow: 0 8px 20px rgba(0,0,0,0.4);
}
.badge-ai {
    background: rgba(197, 168, 128, 0.1);
    color: var(--sm-gold, #c5a880);
    border: 1px solid rgba(197, 168, 128, 0.3);
    font-size: 10px;
    padding: 4px 8px;
    border-radius: 6px;
}
</style>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'AI Önerileri' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">AI Yapay Zeka Öneri Motoru</h2>
        <a href="<?= url('/admin/recommendations/generate') ?>" class="btn btn-warning text-dark border-0 px-4 py-2 font-weight-600">
            <i class="bi bi-cpu-fill me-2"></i>Verileri Yeniden Analiz Et
        </a>
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

<!-- AI Tanıtım Paneli -->
<div class="ai-header-card text-white mb-4">
    <div class="row align-items-center">
        <div class="col-md-9">
            <h4 class="text-warning font-weight-700 mb-2"><i class="bi bi-robot me-2"></i>Yapay Zeka & Akıllı Pazarlama Danışmanı</h4>
            <p class="text-muted fs-7 mb-0">
                SaintMonarc AI Öneri Motoru; satış geçmişinizi, stok devir hızlarınızı, kategori cirolarını ve ürün görüntülenme sayılarını gerçek zamanlı analiz eder. 
                Sizin için ciro artırıcı çapraz satış paketleri, yavaşlayan stokları eritme kampanyaları ve dönüşümü düşük ürünleri canlandırma önerileri sunar.
                <strong>İleride tek tıkla OpenAI API anahtarı eklenerek büyük dil modelleri ile entegre çalışabilir.</strong>
            </p>
        </div>
        <div class="col-md-3 text-end d-none d-md-block">
            <i class="bi bi-cpu text-warning" style="font-size: 80px; opacity: 0.8;"></i>
        </div>
    </div>
</div>

<!-- Öneri Kartları -->
<div class="row g-4 text-white">
    <?php if (empty($recommendations)): ?>
        <div class="col-12 text-center py-5 text-muted">
            <i class="bi bi-info-circle fs-3 mb-2"></i>
            <p>Şu an için yeni bir kampanya veya stok eritme önerisi bulunmuyor. Satış ve stok hareketleri gerçekleştikçe yeni öneriler oluşacaktır.</p>
        </div>
    <?php else: ?>
        <?php foreach ($recommendations as $rec): ?>
            <?php 
            $payload = json_decode($rec['payload'] ?? '', true); 
            $badgeText = "AI Kampanya";
            $icon = "bi-tags-fill";
            if ($rec['type'] === 'cross_sell_bundle') {
                $badgeText = "Çapraz Satış Paketi";
                $icon = "bi-bag-plus-fill";
            } elseif ($rec['type'] === 'aging_stock') {
                $badgeText = "Stok Eritme Önerisi";
                $icon = "bi-archive-fill";
            } elseif ($rec['type'] === 'product_campaign') {
                $badgeText = "Satış Dönüşüm Fırsatı";
                $icon = "bi-graph-down-arrow";
            } elseif ($rec['type'] === 'category_discount') {
                $badgeText = "Kategori Kampanyası";
                $icon = "bi-grid-fill";
            }
            ?>
            <div class="col-md-6 col-sm-12">
                <div class="ai-card p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge-ai"><i class="bi <?= $icon ?> me-1"></i><?= $badgeText ?></span>
                            <span class="text-muted fs-8"><?= date('d.m.Y H:i', strtotime($rec['created_at'])) ?></span>
                        </div>
                        <h4 class="text-white font-weight-700 fs-6 mb-2"><?= htmlspecialchars($rec['title']) ?></h4>
                        <p class="text-muted fs-7 mb-3"><?= htmlspecialchars($rec['description']) ?></p>

                        <!-- Öneri Detay Detayları (Payload) -->
                        <?php if (!empty($payload)): ?>
                            <div class="p-3 rounded mb-3" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                                <div class="fs-8 text-muted mb-2">Öneri Parametreleri:</div>
                                <ul class="list-unstyled m-0 fs-8 text-white">
                                    <?php if ($rec['type'] === 'cross_sell_bundle' && !empty($payload['products'])): ?>
                                        <li><strong>Birlikte Önerilen Ürünler:</strong></li>
                                        <?php foreach ($payload['products'] as $p): ?>
                                            <li class="ms-2">• <?= htmlspecialchars($p['name']) ?> (SKU: <?= htmlspecialchars($p['sku']) ?>)</li>
                                        <?php endforeach; ?>
                                    <?php elseif (isset($payload['sku'])): ?>
                                        <li><strong>Hedef Ürün:</strong> <?= htmlspecialchars($payload['name'] ?? '') ?> (<?= htmlspecialchars($payload['sku']) ?>)</li>
                                    <?php elseif (isset($payload['category_name'])): ?>
                                        <li><strong>Hedef Kategori:</strong> <?= htmlspecialchars($payload['category_name']) ?></li>
                                    <?php endif; ?>
                                    <li class="mt-1"><strong>Önerilen İndirim:</strong> <span class="text-warning">%<?= $payload['proposed_discount'] ?> İndirim</span></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-between gap-2 mt-3 pt-3 border-top border-secondary border-opacity-10">
                        <button type="button" onclick="dismissRec(<?= $rec['id'] ?>)" class="btn btn-sm btn-outline-secondary px-3">Yoksay</button>
                        <button type="button" onclick="applyRec(<?= $rec['id'] ?>)" class="btn btn-sm btn-warning text-dark font-weight-600 px-4"><i class="bi bi-check-circle-fill me-1"></i>Öneriyi Kampanyaya Dönüştür</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Arka Plan Formları -->
<form id="applyForm" method="POST" action="<?= url('/admin/recommendations/apply') ?>" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" id="applyId">
</form>

<form id="dismissForm" method="POST" action="<?= url('/admin/recommendations/dismiss') ?>" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" id="dismissId">
</form>

<script>
function applyRec(id) {
    if (confirm('Bu öneriyi otomatik olarak sepet kuralı kampanyasına dönüştürmek istediğinize emin misiniz?')) {
        document.getElementById('applyId').value = id;
        document.getElementById('applyForm').submit();
    }
}

function dismissRec(id) {
    if (confirm('Bu öneriyi yoksaymak istiyor musunuz?')) {
        document.getElementById('dismissId').value = id;
        document.getElementById('dismissForm').submit();
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
