<?php
use App\Helpers\ComponentHelper;
$title = 'WMS – AI Akıllı Depo Asistanı | SaintMonarc';
include dirname(dirname(__DIR__)) . '/layouts/header.php';
?>
<style>
.ai-card {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid var(--pim-border);
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
}
.ai-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}
.ai-card.priority-Kritik::before { background-color: #ef4444; }
.ai-card.priority-Yüksek::before { background-color: #fbbf24; }
.ai-card.priority-Orta::before { background-color: #3b82f6; }
</style>

<div class="pim-module">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Depo Yönetimi'=>'#','AI Asistan'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-stars me-2" style="color:#c5a880"></i>AI Akıllı Depo Asistanı</h2>
        </div>
    </div>

    <div class="alert alert-info border-0 mb-4 p-3 rounded-4" style="background:rgba(197,168,128,0.1);color:#c5a880;font-size:13px">
        <i class="bi bi-robot me-2" style="font-size:16px"></i> SaintMonarc AI Engine, son 30 günlük satış trendlerini, stok devir hızlarını ve depo koridor yerleşimlerini tarayarak aşağıdaki operasyonel optimizasyon önerilerini oluşturdu.
    </div>

    <!-- AI Suggestion Cards -->
    <div class="d-flex flex-column gap-3">
        <?php foreach ($suggestions as $s): 
            $icon = 'bi-arrow-left-right';
            if ($s['type'] === 'lokasyon_degisimi') $icon = 'bi-grid-3x3-gap';
            elseif ($s['type'] === 'kritik_stok') $icon = 'bi-exclamation-triangle';
        ?>
        <div class="ai-card priority-<?= $s['priority'] ?> text-white">
            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div style="background:rgba(255,255,255,0.05);padding:10px;border-radius:12px;color:#c5a880">
                        <i class="bi <?= $icon ?> fs-5"></i>
                    </div>
                    <div>
                        <h4 class="text-white fw-bold m-0" style="font-size:15px"><?= htmlspecialchars($s['title']) ?></h4>
                        <span style="font-size:11px" class="text-muted">Öneri Tipi: <?= strtoupper(str_replace('_', ' ', $s['type'])) ?></span>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <span class="badge bg-dark" style="font-size:10px;border:1px solid rgba(255,255,255,0.1)">Etki: <?= htmlspecialchars($s['impact']) ?></span>
                    <span class="badge" style="background:rgba(255,255,255,0.05);font-size:10px">Öncelik: <?= htmlspecialchars($s['priority']) ?></span>
                </div>
            </div>

            <p style="font-size:12px;color:var(--pim-text-sm);line-height:1.6" class="m-0 mb-3"><?= htmlspecialchars($s['description']) ?></p>

            <div class="d-flex gap-2 justify-content-end">
                <button class="btn btn-xs btn-outline-warning" style="font-size:11px;padding:4px 10px"><i class="bi bi-check2"></i> Öneriyi Uygula</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include dirname(dirname(__DIR__)) . '/layouts/footer.php'; ?>
