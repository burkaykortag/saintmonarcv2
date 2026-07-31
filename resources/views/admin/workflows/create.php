<?php
use App\Helpers\Ui;

$title = "İş Akışı Tasarla - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid py-4 text-white">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <a href="<?= url('/admin/workflows') ?>" class="text-warning text-decoration-none fs-7"><i class="bi bi-arrow-left me-1"></i> İş Akışlarına Geri Dön</a>
            <h2 class="font-weight-700 mt-2 m-0">Yeni İş Akışı Editörü</h2>
            <p class="text-muted mb-0 fs-7">Süreç adımlarını görsel olarak bağlayın ve otomatik kurallar ekleyin.</p>
        </div>
        <div class="d-flex gap-2">
            <?= Ui::button(['text' => 'Yeniden Hizala', 'type' => 'outline', 'icon' => 'distribute-vertical', 'onclick' => 'autoAlignNodes()']) ?>
            <?= Ui::button(['text' => 'İş Akışını Kaydet', 'type' => 'gold', 'icon' => 'save', 'onclick' => 'saveWorkflow()']) ?>
        </div>
    </div>

    <!-- Node-Based Visual Workflow Canvas Layout -->
    <div class="row g-4">
        <!-- Sol Panel: Drag & Drop Node Kütüphanesi -->
        <div class="col-lg-3">
            <div class="card p-3 border-0 mb-3">
                <h4 class="font-weight-700 fs-6 mb-3">1. Tetikleyici Seç</h4>
                <div class="d-flex flex-column gap-2">
                    <div class="node-source p-2 bg-dark rounded-3 border border-secondary border-opacity-10 cursor-grab" draggable="true" data-type="trigger" data-event="order_created">
                        <i class="bi bi-plus-circle-fill text-warning me-2"></i> Yeni Sipariş Geldi
                    </div>
                    <div class="node-source p-2 bg-dark rounded-3 border border-secondary border-opacity-10 cursor-grab" draggable="true" data-type="trigger" data-event="stock_low">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Stok Azaldı
                    </div>
                    <div class="node-source p-2 bg-dark rounded-3 border border-secondary border-opacity-10 cursor-grab" draggable="true" data-type="trigger" data-event="customer_registered">
                        <i class="bi bi-person-fill-add text-success me-2"></i> Yeni Üye Kaydı
                    </div>
                </div>
            </div>

            <div class="card p-3 border-0 mb-3">
                <h4 class="font-weight-700 fs-6 mb-3">2. Koşul Ekle (IF)</h4>
                <div class="d-flex flex-column gap-2">
                    <div class="node-source p-2 bg-dark rounded-3 border border-secondary border-opacity-10 cursor-grab" draggable="true" data-type="condition" data-field="total_amount" data-op="greater_than">
                        <i class="bi bi-calculator-fill text-info me-2"></i> Sipariş Tutarı > X
                    </div>
                    <div class="node-source p-2 bg-dark rounded-3 border border-secondary border-opacity-10 cursor-grab" draggable="true" data-type="condition" data-field="city" data-op="equals">
                        <i class="bi bi-geo-alt-fill text-info me-2"></i> Şehir Eşitse
                    </div>
                </div>
            </div>

            <div class="card p-3 border-0">
                <h4 class="font-weight-700 fs-6 mb-3">3. Eylemler (Actions)</h4>
                <div class="d-flex flex-column gap-2" style="max-height: 250px; overflow-y: auto;">
                    <div class="node-source p-2 bg-dark rounded-3 border border-secondary border-opacity-10 cursor-grab" draggable="true" data-type="action" data-action="mail">
                        <i class="bi bi-envelope-fill text-primary me-2"></i> E-posta Gönder
                    </div>
                    <div class="node-source p-2 bg-dark rounded-3 border border-secondary border-opacity-10 cursor-grab" draggable="true" data-type="action" data-action="sms">
                        <i class="bi bi-chat-text-fill text-success me-2"></i> SMS Gönder
                    </div>
                    <div class="node-source p-2 bg-dark rounded-3 border border-secondary border-opacity-10 cursor-grab" draggable="true" data-type="action" data-action="webhook">
                        <i class="bi bi-arrow-left-right text-warning me-2"></i> Webhook Çağır
                    </div>
                    <div class="node-source p-2 bg-dark rounded-3 border border-secondary border-opacity-10 cursor-grab" draggable="true" data-type="action" data-action="slack">
                        <i class="bi bi-slack text-danger me-2"></i> Slack Bildirimi
                    </div>
                </div>
            </div>
        </div>

        <!-- Sağ Taraf: İnteraktif Canvas & Grid Arka Planı -->
        <div class="col-lg-9">
            <div class="card p-0 border-0 position-relative" style="height: 600px; background-color: #0b0b0d; background-image: radial-gradient(rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 20px 20px; overflow: hidden;" id="canvasContainer">
                
                <!-- Zoom & Controls HUD -->
                <div class="position-absolute top-3 end-3 bg-dark bg-opacity-75 p-2 rounded-3 border border-secondary border-opacity-10 d-flex gap-2" style="z-index: 10;">
                    <button class="btn btn-xs btn-outline-light" onclick="zoomIn()"><i class="bi bi-zoom-in"></i></button>
                    <button class="btn btn-xs btn-outline-light" onclick="zoomOut()"><i class="bi bi-zoom-out"></i></button>
                    <button class="btn btn-xs btn-outline-light" onclick="resetZoom()"><i class="bi bi-fullscreen-exit"></i> 100%</button>
                </div>

                <!-- Minimap Panel -->
                <div class="position-absolute bottom-3 end-3 bg-dark bg-opacity-90 rounded-3 border border-secondary border-opacity-25" style="width: 120px; height: 90px; z-index: 10;">
                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted fs-8">Mini Map</div>
                </div>

                <!-- Interactive SVG connections overlay -->
                <svg class="position-absolute w-100 h-100" style="pointer-events: none; z-index: 2;" id="svgConnections"></svg>

                <!-- Workflow Canvas Workspace -->
                <div id="workflowCanvas" class="w-100 h-100 position-absolute" style="transform-origin: 0 0; transition: transform 0.1s ease-out;">
                    <!-- Default Initial Nodes -->
                    <div class="node-element bg-dark border border-warning rounded-3 p-3 position-absolute cursor-move" style="width: 220px; top: 180px; left: 100px;" id="node-trigger">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-warning font-weight-700 fs-7">Tetikleyici</span>
                            <i class="bi bi-lightning-fill text-warning"></i>
                        </div>
                        <div class="fs-7">Yeni Sipariş Geldi</div>
                    </div>

                    <div class="node-element bg-dark border border-info rounded-3 p-3 position-absolute cursor-move" style="width: 220px; top: 180px; left: 420px;" id="node-action-1">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-info font-weight-700 fs-7">Aksiyon</span>
                            <i class="bi bi-envelope text-info"></i>
                        </div>
                        <div class="fs-7">Müşteriye SMS Gönder</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .cursor-grab { cursor: grab; }
    .cursor-move { cursor: move; }
    .node-source:hover {
        background-color: var(--bg-hover) !important;
        border-color: var(--sm-gold) !important;
    }
</style>

<script>
    let scale = 1;
    function zoomIn() {
        scale = Math.min(scale + 0.1, 2);
        applyZoom();
    }
    function zoomOut() {
        scale = Math.max(scale - 0.1, 0.5);
        applyZoom();
    }
    function resetZoom() {
        scale = 1;
        applyZoom();
    }
    function applyZoom() {
        document.getElementById('workflowCanvas').style.transform = `scale(${scale})`;
    }

    function autoAlignNodes() {
        const node1 = document.getElementById('node-trigger');
        const node2 = document.getElementById('node-action-1');
        node1.style.top = '200px';
        node1.style.left = '100px';
        node2.style.top = '200px';
        node2.style.left = '450px';
        drawConnections();
    }

    function drawConnections() {
        const svg = document.getElementById('svgConnections');
        const node1 = document.getElementById('node-trigger');
        const node2 = document.getElementById('node-action-1');
        if (!node1 || !node2 || !svg) return;

        const x1 = node1.offsetLeft + node1.offsetWidth;
        const y1 = node1.offsetTop + (node1.offsetHeight / 2);

        const x2 = node2.offsetLeft;
        const y2 = node2.offsetTop + (node2.offsetHeight / 2);

        svg.innerHTML = `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="var(--sm-gold)" stroke-width="2" stroke-dasharray="5,5" />`;
    }

    document.addEventListener('DOMContentLoaded', () => {
        drawConnections();
    });

    function saveWorkflow() {
        alert('İş Akışı Şablon Olarak Kaydedildi!');
    }
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
