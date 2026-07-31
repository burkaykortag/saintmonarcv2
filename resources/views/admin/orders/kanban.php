<?php
use App\Helpers\ComponentHelper;
$title = 'OMS – Sipariş Kanban Tahtası | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>
<style>
.kanban-board {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding: 10px 0;
    min-height: calc(100vh - 250px);
    align-items: flex-start;
}
.kanban-column {
    flex: 1;
    min-width: 280px;
    background: var(--pim-card);
    border: 1px solid var(--pim-border);
    border-radius: var(--pim-radius-lg);
    padding: 14px;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 200px);
}
.kanban-column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.kanban-column-title {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--pim-text);
}
.kanban-column-count {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 20px;
    padding: 2px 8px;
    font-size: 11px;
    color: var(--pim-text-xs);
}
.kanban-cards {
    display: flex;
    flex-direction: column;
    gap: 10px;
    overflow-y: auto;
    flex: 1;
    min-height: 150px;
    padding: 2px;
}
.kanban-card {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid var(--pim-border);
    border-radius: var(--pim-radius);
    padding: 12px;
    cursor: grab;
    transition: all 0.2s;
}
.kanban-card:hover {
    background: rgba(255, 255, 255, 0.04);
    border-color: #c5a880;
    transform: translateY(-2px);
}
.kanban-card:active {
    cursor: grabbing;
}
.kanban-card.delayed {
    border-left: 3px solid #ef4444!important;
}
.kanban-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}
.kanban-card-num {
    font-size: 12px;
    font-weight: 700;
    color: #c5a880;
}
.kanban-card-date {
    font-size: 10px;
    color: var(--pim-text-xs);
}
.kanban-card-cust {
    font-size: 12px;
    color: var(--pim-text-sm);
    margin-bottom: 8px;
    font-weight: 500;
}
.kanban-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 6px;
    border-top: 1px solid rgba(255, 255, 255, 0.03);
}
.kanban-card-amount {
    font-size: 12px;
    font-weight: 700;
    color: var(--pim-text);
}
.sla-badge {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 700;
}
.sla-ok {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}
.sla-warn {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}
.sla-danger {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    animation: alertPulse 1.5s infinite;
}
@keyframes alertPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>

<div class="pim-module">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli'=>url('/admin'),'Siparişler'=>url('/admin/orders'),'Sipariş Kanban tahtası'=>'#']) ?>
            <h2 class="text-white fw-bold m-0 mt-2" style="font-size:24px"><i class="bi bi-columns-gap me-2" style="color:#c5a880"></i>Sipariş Kanban Tahtası</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/admin/orders') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-ul me-1"></i>Liste Görünümü</a>
        </div>
    </div>

    <!-- Kanban Board Grid -->
    <div class="kanban-board">
        <?php 
        $cols = [
            'pending' => ['title' => 'Yeni Sipariş', 'color' => '#fbbf24'],
            'processing' => ['title' => 'Hazırlanıyor', 'color' => '#3b82f6'],
            'packing' => ['title' => 'Paketleniyor', 'color' => '#8b5cf6'],
            'shipped' => ['title' => 'Kargoda', 'color' => '#06b6d4'],
            'delivered' => ['title' => 'Teslim Edildi', 'color' => '#10b981']
        ];
        
        foreach ($cols as $colKey => $colData): 
            $colOrders = $board[$colKey] ?? [];
        ?>
        <div class="kanban-column" id="col_<?= $colKey ?>" ondragover="allowDrop(event)" ondrop="drop(event, '<?= $colKey ?>')">
            <div class="kanban-column-header">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:8px;height:8px;border-radius:50%;background:<?= $colData['color'] ?>"></div>
                    <span class="kanban-column-title"><?= $colData['title'] ?></span>
                </div>
                <span class="kanban-column-count" id="count_<?= $colKey ?>"><?= count($colOrders) ?></span>
            </div>
            
            <div class="kanban-cards" id="cards_<?= $colKey ?>">
                <?php foreach ($colOrders as $o): 
                    $isDelayed = $o['is_delayed'] ?? false;
                    $slaSeconds = $o['sla_remaining_seconds'] ?? 0;
                    
                    if ($slaSeconds < 0) {
                        $slaText = 'GECİKTİ';
                        $slaClass = 'sla-danger';
                    } elseif ($slaSeconds < 3600 * 4) {
                        $slaText = round($slaSeconds/60) . ' dk';
                        $slaClass = 'sla-warn';
                    } else {
                        $slaText = round($slaSeconds/3600) . ' sa';
                        $slaClass = 'sla-ok';
                    }
                ?>
                <div class="kanban-card <?= $isDelayed ? 'delayed' : '' ?>" id="card_<?= $o['id'] ?>" draggable="true" ondragstart="drag(event, <?= $o['id'] ?>)">
                    <div class="kanban-card-header">
                        <span class="kanban-card-num" onclick="location.href='<?= url("/admin/orders/show?id=" . $o['id']) ?>'"><?= htmlspecialchars($o['order_number']) ?></span>
                        <span class="kanban-card-date"><?= date('H:i', strtotime($o['created_at'])) ?></span>
                    </div>
                    <div class="kanban-card-cust"><?= htmlspecialchars($o['billing_first_name'] . ' ' . $o['billing_last_name']) ?></div>
                    <div class="kanban-card-footer">
                        <span class="kanban-card-amount">₺<?= number_format($o['grand_total'], 2, ',', '.') ?></span>
                        <?php if (!in_array($colKey, ['shipped', 'delivered'])): ?>
                            <span class="sla-badge <?= $slaClass ?>" title="SLA Kalan Süre"><?= $slaText ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const csrfToken = '<?= $csrfToken ?>';

function allowDrop(ev) {
    ev.preventDefault();
}

function drag(ev, orderId) {
    ev.dataTransfer.setData("text", orderId);
}

function drop(ev, targetStatus) {
    ev.preventDefault();
    const orderId = ev.dataTransfer.getData("text");
    const card = document.getElementById("card_" + orderId);
    if (!card) return;

    // Move in UI
    const targetContainer = document.getElementById("cards_" + targetStatus);
    targetContainer.appendChild(card);

    // Update column counters
    updateCounts();

    // Call backend
    const formData = new FormData();
    formData.append('action', 'status');
    formData.append('target_status', targetStatus);
    formData.append('order_ids[]', orderId);
    formData.append('csrf_token', csrfToken);

    fetch('<?= url("/admin/orders/bulk") ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        showToast('Sipariş durumu güncellendi: ' + targetStatus.toUpperCase(), '#10b981');
    })
    .catch(err => {
        console.error(err);
        showToast('Hata: Durum güncellenemedi.', '#ef4444');
    });
}

function updateCounts() {
    const cols = ['pending', 'processing', 'packing', 'shipped', 'delivered'];
    cols.forEach(col => {
        const count = document.getElementById("cards_" + col).children.length;
        document.getElementById("count_" + col).textContent = count;
    });
}

function showToast(msg, color) {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:24px;right:24px;background:#0f0c20;border:1px solid ${color};border-radius:12px;padding:12px 20px;color:${color};font-size:13px;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.5)`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(()=>t.remove(), 3000);
}
</script>
<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
