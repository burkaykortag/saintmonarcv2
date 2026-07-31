<?php
use App\Helpers\ComponentHelper;

$title = "Enterprise PIM – Ürün Yönetimi | SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security  = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();

// ── Özet KPI verileri (gerçek veriden gelir; yoksa 0) ────────────────────────
$totalProducts    = count($products ?? []);
$trashCount       = count($trash ?? []);
$publishedCount   = count(array_filter($products ?? [], fn($p) => ($p['status'] ?? '') === 'published'));
$criticalCount    = count(array_filter($products ?? [], fn($p) => !empty($p['total_stock']) && (int)$p['total_stock'] <= (int)($p['critical_stock'] ?? 5) && empty($p['unlimited_stock'])));
?>

<!-- ═══════════════════════════════════════════════════════════════
     PIM V2 — Enterprise Product Information Management
     Sprint 29 | SaintMonarc Admin Panel
═══════════════════════════════════════════════════════════════ -->
<style>
/* ─── PIM Design Tokens ─────────────────────────────────── */
:root {
    --pim-bg:         #070512;
    --pim-card:       rgba(255,255,255,0.025);
    --pim-border:     rgba(255,255,255,0.07);
    --pim-gold:       #c5a880;
    --pim-gold-glow:  rgba(197,168,128,0.15);
    --pim-text:       #e2e8f0;
    --pim-muted:      #64748b;
    --pim-success:    #10b981;
    --pim-danger:     #ef4444;
    --pim-warning:    #f59e0b;
    --pim-info:       #3b82f6;
    --pim-purple:     #8b5cf6;
    --pim-radius:     16px;
    --pim-radius-sm:  10px;
    --pim-transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
}

/* ─── Layout ─────────────────────────────────────────────── */
.pim-wrapper { display:flex; flex-direction:column; gap:24px; }

/* ─── KPI Cards ──────────────────────────────────────────── */
.pim-kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; }
.pim-kpi-card {
    background: var(--pim-card);
    border: 1px solid var(--pim-border);
    border-radius: var(--pim-radius);
    padding: 22px 24px;
    display: flex; align-items:center; gap:16px;
    transition: var(--pim-transition);
    position: relative; overflow:hidden;
}
.pim-kpi-card::before {
    content:''; position:absolute; inset:0;
    background: linear-gradient(135deg, var(--accent-color,var(--pim-gold-glow)) 0%, transparent 70%);
    opacity:0; transition:opacity .3s;
}
.pim-kpi-card:hover { transform:translateY(-2px); border-color:rgba(255,255,255,0.12); }
.pim-kpi-card:hover::before { opacity:1; }
.pim-kpi-icon {
    width:52px; height:52px; border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    font-size:22px; flex-shrink:0;
    background: var(--icon-bg, rgba(197,168,128,0.15));
    color: var(--icon-color, var(--pim-gold));
}
.pim-kpi-info { flex:1; }
.pim-kpi-value { font-size:26px; font-weight:700; line-height:1; color:var(--pim-text); }
.pim-kpi-label { font-size:12px; color:var(--pim-muted); margin-top:4px; font-weight:500; text-transform:uppercase; letter-spacing:.5px; }
.pim-kpi-trend { font-size:11px; margin-top:6px; font-weight:600; }
.trend-up   { color:var(--pim-success); }
.trend-down { color:var(--pim-danger); }
.trend-flat { color:var(--pim-muted); }

/* ─── Toolbar ────────────────────────────────────────────── */
.pim-toolbar {
    background: var(--pim-card);
    border: 1px solid var(--pim-border);
    border-radius: var(--pim-radius);
    padding: 14px 20px;
    display: flex; align-items:center; gap:12px; flex-wrap:wrap;
}
.pim-toolbar-divider { width:1px; height:28px; background:var(--pim-border); flex-shrink:0; }

/* ─── Filter Panel ───────────────────────────────────────── */
.pim-filter-panel {
    background: var(--pim-card);
    border: 1px solid var(--pim-border);
    border-radius: var(--pim-radius);
    padding: 16px 20px;
    display: flex; align-items:center; gap:12px; flex-wrap:wrap;
}
.pim-filter-input, .pim-filter-select {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--pim-border);
    border-radius: var(--pim-radius-sm);
    color: var(--pim-text);
    font-size: 13px;
    padding: 9px 14px;
    transition: var(--pim-transition);
    font-family: 'Outfit', sans-serif;
}
.pim-filter-input:focus, .pim-filter-select:focus {
    outline: none;
    border-color: var(--pim-gold);
    box-shadow: 0 0 0 3px rgba(197,168,128,0.1);
    background: rgba(255,255,255,0.05);
}
.pim-filter-input { width: 260px; }
.pim-filter-select { min-width: 150px; }
.pim-filter-select option { background:#15102a; }

/* ─── Bulk Action Bar ────────────────────────────────────── */
.pim-bulk-bar {
    background: linear-gradient(135deg, rgba(197,168,128,0.08), rgba(197,168,128,0.03));
    border: 1px solid rgba(197,168,128,0.25);
    border-radius: var(--pim-radius);
    padding: 12px 20px;
    display: flex; align-items:center; justify-content:space-between; gap:12px;
    animation: slideDown 0.2s ease;
}
@keyframes slideDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
.bulk-count-badge {
    background: var(--pim-gold);
    color: #070512;
    font-weight: 700;
    font-size: 12px;
    padding: 3px 10px;
    border-radius: 20px;
}

/* ─── Data Grid ──────────────────────────────────────────── */
.pim-grid-container {
    background: var(--pim-card);
    border: 1px solid var(--pim-border);
    border-radius: var(--pim-radius);
    overflow: hidden;
}
.pim-grid-scroll { overflow-x:auto; overflow-y:auto; max-height:72vh; }
.pim-grid-scroll::-webkit-scrollbar { width:6px; height:6px; }
.pim-grid-scroll::-webkit-scrollbar-track { background:transparent; }
.pim-grid-scroll::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.08); border-radius:3px; }
.pim-grid-scroll::-webkit-scrollbar-thumb:hover { background:var(--pim-gold); }

.pim-table {
    width:100%; border-collapse:separate; border-spacing:0;
    font-size:13.5px; color:var(--pim-text);
    min-width: 1100px;
}
.pim-table thead th {
    position:sticky; top:0; z-index:10;
    background: rgba(15,12,32,0.98);
    backdrop-filter: blur(8px);
    padding: 14px 16px;
    font-size:11px; font-weight:600; text-transform:uppercase;
    letter-spacing:.6px; color:var(--pim-muted);
    border-bottom: 1px solid var(--pim-border);
    white-space:nowrap; cursor:pointer; user-select:none;
    transition: var(--pim-transition);
}
.pim-table thead th:hover { color:var(--pim-gold); }
.pim-table thead th.sort-asc  i.sort-icon::before { content:"\F131"; }
.pim-table thead th.sort-desc i.sort-icon::before { content:"\F130"; }
.pim-table thead th .sort-icon { margin-left:4px; opacity:0.5; font-size:10px; }
.pim-table thead th:hover .sort-icon { opacity:1; }

.pim-table tbody tr {
    border-bottom: 1px solid var(--pim-border);
    transition: var(--pim-transition);
}
.pim-table tbody tr:hover { background: rgba(255,255,255,0.02); }
.pim-table tbody tr.row-selected { background: rgba(197,168,128,0.06) !important; }
.pim-table tbody td { padding:13px 16px; vertical-align:middle; }
.pim-table tbody td:first-child { padding-left:20px; }

/* Sticky first + second columns */
.pim-table .col-check   { position:sticky; left:0; z-index:5; background:inherit; width:44px; }
.pim-table .col-thumb   { position:sticky; left:44px; z-index:5; background:inherit; width:72px; }
.pim-table thead .col-check, .pim-table thead .col-thumb { z-index:11; background:rgba(15,12,32,0.98); }

/* Product Thumbnail */
.prod-thumb-cell { display:flex; align-items:center; }
.prod-thumb {
    width:54px; height:40px; border-radius:8px; object-fit:cover;
    border:1px solid var(--pim-border);
    transition: var(--pim-transition);
}
.prod-thumb:hover { transform:scale(1.05); }
.prod-thumb-placeholder {
    width:54px; height:40px; border-radius:8px;
    background:rgba(255,255,255,0.04); border:1px solid var(--pim-border);
    display:flex; align-items:center; justify-content:center;
    color:var(--pim-muted); font-size:16px;
}

/* Product Name Cell */
.prod-name-wrap { display:flex; flex-direction:column; gap:3px; }
.prod-name-main { font-weight:600; color:var(--pim-text); line-height:1.3; }
.prod-name-sub  { font-size:11px; color:var(--pim-muted); }
.prod-sku-badge { font-size:11px; font-family:'JetBrains Mono',monospace; color:var(--pim-warning); background:rgba(245,158,11,0.1); padding:2px 7px; border-radius:5px; display:inline-block; }

/* Status Badges */
.status-pill {
    display:inline-flex; align-items:center; gap:5px;
    font-size:11px; font-weight:600; padding:4px 10px; border-radius:20px;
}
.status-pill::before { content:''; width:6px; height:6px; border-radius:50%; }
.sp-published { background:rgba(16,185,129,0.12); color:#10b981; }
.sp-published::before { background:#10b981; box-shadow:0 0 6px #10b981; }
.sp-draft     { background:rgba(245,158,11,0.12); color:#f59e0b; }
.sp-draft::before { background:#f59e0b; }
.sp-passive   { background:rgba(100,116,139,0.15); color:#94a3b8; }
.sp-passive::before { background:#94a3b8; }
.sp-archived  { background:rgba(239,68,68,0.12); color:#ef4444; }
.sp-archived::before { background:#ef4444; }
.sp-coming    { background:rgba(59,130,246,0.12); color:#3b82f6; }
.sp-coming::before { background:#3b82f6; }

/* Stock Indicator */
.stock-cell { display:flex; align-items:center; gap:8px; }
.stock-bar-wrap { flex:1; height:4px; background:rgba(255,255,255,0.06); border-radius:2px; min-width:50px; }
.stock-bar { height:100%; border-radius:2px; transition:width .5s ease; }
.stock-ok   .stock-bar { background:var(--pim-success); }
.stock-warn .stock-bar { background:var(--pim-warning); }
.stock-crit .stock-bar { background:var(--pim-danger); }
.stock-num  { font-size:12px; font-weight:600; min-width:32px; text-align:right; }

/* Price Cell */
.price-cell { display:flex; flex-direction:column; gap:2px; }
.price-main { font-weight:700; font-size:14px; color:var(--pim-text); }
.price-disc { font-size:11px; color:var(--pim-success); }
.price-cost { font-size:11px; color:var(--pim-muted); }

/* AI Score */
.ai-score-wrap { display:flex; align-items:center; gap:8px; }
.ai-score-ring {
    width:36px; height:36px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:11px; font-weight:700;
    background:conic-gradient(var(--score-color, var(--pim-gold)) var(--score-deg, 0deg), rgba(255,255,255,0.06) 0deg);
    box-shadow:0 0 0 2px var(--pim-card);
}
.ai-score-inner {
    width:26px; height:26px; border-radius:50%;
    background:var(--pim-bg); display:flex; align-items:center; justify-content:center;
    font-size:10px; font-weight:700; color:var(--pim-text);
}

/* Action Buttons */
.pim-action-btns { display:flex; align-items:center; gap:6px; justify-content:flex-end; }
.pim-btn-icon {
    width:30px; height:30px; border-radius:8px; border:1px solid var(--pim-border);
    background:rgba(255,255,255,0.03); color:var(--pim-muted);
    display:flex; align-items:center; justify-content:center;
    font-size:13px; cursor:pointer; transition:var(--pim-transition);
    text-decoration:none;
}
.pim-btn-icon:hover { border-color:var(--pim-gold); color:var(--pim-gold); background:var(--pim-gold-glow); }
.pim-btn-icon.danger:hover { border-color:var(--pim-danger); color:var(--pim-danger); background:rgba(239,68,68,0.1); }

/* Quick Edit Inline */
.inline-edit-cell { position:relative; }
.inline-edit-input {
    background:rgba(255,255,255,0.05); border:1px solid var(--pim-gold);
    border-radius:6px; color:var(--pim-text); font-size:13px;
    padding:4px 8px; width:100%;
}

/* ─── Grid View / Card View Toggle ──────────────────────── */
.view-toggle-btn {
    width:32px; height:32px; border-radius:8px; border:1px solid var(--pim-border);
    background:transparent; color:var(--pim-muted);
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:var(--pim-transition);
}
.view-toggle-btn.active { background:var(--pim-gold-glow); border-color:var(--pim-gold); color:var(--pim-gold); }

/* ─── Product Card View ──────────────────────────────────── */
.pim-card-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px; }
.pim-product-card {
    background: var(--pim-card);
    border: 1px solid var(--pim-border);
    border-radius: var(--pim-radius);
    overflow:hidden;
    transition:var(--pim-transition);
    position:relative;
}
.pim-product-card:hover { transform:translateY(-4px); border-color:rgba(255,255,255,0.12); box-shadow:0 12px 40px rgba(0,0,0,0.4); }
.pim-card-select {
    position:absolute; top:12px; left:12px; z-index:2;
    width:18px; height:18px; accent-color:var(--pim-gold);
    transform:scale(1.2); cursor:pointer;
}
.pim-card-img-wrap { position:relative; height:180px; background:rgba(0,0,0,0.2); }
.pim-card-img-wrap img { width:100%; height:100%; object-fit:cover; }
.pim-card-img-placeholder {
    width:100%; height:100%;
    display:flex; align-items:center; justify-content:center;
    font-size:40px; color:rgba(255,255,255,0.1);
}
.pim-card-status-overlay {
    position:absolute; top:12px; right:12px;
}
.pim-card-body { padding:16px; }
.pim-card-brand { font-size:10px; color:var(--pim-gold); font-weight:600; text-transform:uppercase; letter-spacing:.8px; margin-bottom:4px; }
.pim-card-title { font-size:14px; font-weight:600; color:var(--pim-text); line-height:1.4; margin-bottom:6px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.pim-card-sku { font-size:11px; color:var(--pim-warning); font-family:monospace; margin-bottom:10px; }
.pim-card-metrics { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:12px; }
.pim-card-metric { background:rgba(255,255,255,0.03); border-radius:8px; padding:8px 10px; }
.pim-card-metric-val { font-size:15px; font-weight:700; color:var(--pim-text); }
.pim-card-metric-lbl { font-size:10px; color:var(--pim-muted); margin-top:2px; }
.pim-card-footer {
    padding:12px 16px;
    border-top:1px solid var(--pim-border);
    display:flex; justify-content:space-between; align-items:center;
}
.pim-card-ai { display:flex; align-items:center; gap:6px; font-size:11px; color:var(--pim-purple); font-weight:600; }

/* ─── Pagination ─────────────────────────────────────────── */
.pim-pagination {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:14px 20px;
    border-top:1px solid var(--pim-border);
    font-size:13px; color:var(--pim-muted);
}
.pim-page-btns { display:flex; align-items:center; gap:6px; }
.pim-page-btn {
    width:32px; height:32px; border-radius:8px; border:1px solid var(--pim-border);
    background:transparent; color:var(--pim-muted);
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; font-size:12px; font-weight:600; transition:var(--pim-transition);
}
.pim-page-btn:hover, .pim-page-btn.active { background:var(--pim-gold); border-color:var(--pim-gold); color:#070512; }
.pim-page-btn:disabled { opacity:0.3; cursor:not-allowed; pointer-events:none; }

/* ─── Tabs ───────────────────────────────────────────────── */
.pim-tabs { display:flex; align-items:center; gap:4px; }
.pim-tab {
    padding:8px 18px; border-radius:10px; font-size:13px; font-weight:600;
    color:var(--pim-muted); cursor:pointer; border:1px solid transparent;
    transition:var(--pim-transition); display:flex; align-items:center; gap:6px;
}
.pim-tab:hover { color:var(--pim-text); background:rgba(255,255,255,0.03); }
.pim-tab.active { color:var(--pim-gold); background:var(--pim-gold-glow); border-color:rgba(197,168,128,0.2); }
.pim-tab .tab-count {
    background:rgba(255,255,255,0.08); color:var(--pim-muted);
    font-size:10px; padding:2px 7px; border-radius:10px; font-weight:700;
}
.pim-tab.active .tab-count { background:rgba(197,168,128,0.2); color:var(--pim-gold); }

/* ─── Column Chooser ─────────────────────────────────────── */
.col-chooser-item { display:flex; align-items:center; gap:10px; padding:7px 12px; border-radius:8px; cursor:pointer; }
.col-chooser-item:hover { background:rgba(255,255,255,0.03); }

/* ─── Skeleton Loading ───────────────────────────────────── */
.skeleton { background:linear-gradient(90deg,rgba(255,255,255,0.04) 25%,rgba(255,255,255,0.08) 50%,rgba(255,255,255,0.04) 75%); background-size:200%; animation:shimmer 1.5s infinite; border-radius:6px; }
@keyframes shimmer { 0%{background-position:200%}100%{background-position:-200%} }

/* ─── Dropdown menus ─────────────────────────────────────── */
.pim-dropdown-menu {
    background: #15102a;
    border: 1px solid var(--pim-border);
    border-radius: var(--pim-radius-sm);
    box-shadow: 0 16px 48px rgba(0,0,0,0.6);
    min-width: 200px;
    padding: 6px;
}
.pim-dropdown-menu .dropdown-item {
    color:var(--pim-muted); font-size:13px; padding:9px 14px;
    border-radius:8px; transition:var(--pim-transition);
    display:flex; align-items:center; gap:10px;
}
.pim-dropdown-menu .dropdown-item:hover { background:rgba(255,255,255,0.04); color:var(--pim-text); }
.pim-dropdown-menu .dropdown-item.text-danger:hover { background:rgba(239,68,68,0.08); color:var(--pim-danger); }
.pim-dropdown-divider { border-color:var(--pim-border); margin:4px 0; }

/* ─── Btn PIM ────────────────────────────────────────────── */
.btn-pim-primary {
    background: linear-gradient(135deg, var(--pim-gold), #d4b896);
    color: #070512; border:none; font-weight:700;
    padding:9px 20px; border-radius:10px; font-size:13px;
    transition:var(--pim-transition); display:inline-flex; align-items:center; gap:8px;
}
.btn-pim-primary:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(197,168,128,0.35); color:#070512; }
.btn-pim-secondary {
    background: rgba(255,255,255,0.04);
    color:var(--pim-text); border:1px solid var(--pim-border);
    padding:9px 18px; border-radius:10px; font-size:13px; font-weight:500;
    transition:var(--pim-transition); display:inline-flex; align-items:center; gap:8px;
}
.btn-pim-secondary:hover { border-color:rgba(255,255,255,0.15); background:rgba(255,255,255,0.07); color:var(--pim-text); }

/* ─── Responsive ─────────────────────────────────────────── */
@media (max-width:768px) {
    .pim-kpi-grid { grid-template-columns:1fr 1fr; }
    .pim-filter-input { width:100%; }
    .pim-toolbar { gap:8px; }
    .pim-card-grid { grid-template-columns:1fr 1fr; }
}
@media (max-width:480px) {
    .pim-kpi-grid { grid-template-columns:1fr; }
    .pim-card-grid { grid-template-columns:1fr; }
}
</style>

<div class="pim-wrapper">

    <!-- ── Breadcrumb & Header ────────────────────────────── -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Ürünler (PIM)' => url('/admin/products')]) ?>
            <div class="d-flex align-items-center gap-3 mt-2">
                <h2 class="text-white m-0" style="font-size:26px;font-weight:700;">
                    <i class="bi bi-box-seam me-2" style="color:var(--pim-gold);"></i>
                    Enterprise PIM
                </h2>
                <span class="status-pill sp-published" style="font-size:11px; padding:4px 12px;">
                    <i class="bi bi-lightning-charge me-1"></i>V2
                </span>
            </div>
            <p class="text-muted mb-0 mt-1" style="font-size:13px;">Ürün bilgilerini, stok, fiyat ve medyayı tek merkezden yönetin.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn-pim-secondary" type="button" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload"></i> İçe Aktar
            </button>
            <div class="dropdown">
                <button class="btn-pim-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-download"></i> Dışa Aktar
                </button>
                <ul class="dropdown-menu pim-dropdown-menu">
                    <li><a class="dropdown-item" href="<?= url('/admin/products/export?format=csv') ?>"><i class="bi bi-filetype-csv"></i> CSV Formatı</a></li>
                    <li><a class="dropdown-item" href="<?= url('/admin/products/export?format=excel') ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Excel Formatı</a></li>
                    <li><a class="dropdown-item" href="<?= url('/admin/products/export?format=xml') ?>"><i class="bi bi-filetype-xml"></i> XML Sitemap</a></li>
                    <li><a class="dropdown-item" href="<?= url('/admin/products/export?format=pdf') ?>"><i class="bi bi-filetype-pdf"></i> PDF Raporu</a></li>
                </ul>
            </div>
            <a href="<?= url('/admin/products/reports') ?>" class="btn-pim-secondary">
                <i class="bi bi-graph-up-arrow"></i> Analitik
            </a>
            <a href="<?= url('/admin/products/create') ?>" class="btn-pim-primary">
                <i class="bi bi-plus-circle-fill"></i> Yeni Ürün
            </a>
        </div>
    </div>

    <!-- ── Alerts ─────────────────────────────────────────── -->
    <?php if (!empty($_GET['success'])): ?>
        <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <span class="text-success"><?= htmlspecialchars($_GET['success']) ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
        <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
            <span class="text-danger"><?= htmlspecialchars($_GET['error']) ?></span>
        </div>
    <?php endif; ?>

    <!-- ── KPI Dashboard ──────────────────────────────────── -->
    <div class="pim-kpi-grid">
        <div class="pim-kpi-card" style="--accent-color:rgba(197,168,128,0.1);">
            <div class="pim-kpi-icon" style="--icon-bg:rgba(197,168,128,0.12);--icon-color:#c5a880;">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="pim-kpi-info">
                <div class="pim-kpi-value"><?= $totalProducts ?></div>
                <div class="pim-kpi-label">Toplam Ürün</div>
                <div class="pim-kpi-trend trend-flat"><i class="bi bi-dash"></i> Aktif Katalog</div>
            </div>
        </div>
        <div class="pim-kpi-card" style="--accent-color:rgba(16,185,129,0.1);">
            <div class="pim-kpi-icon" style="--icon-bg:rgba(16,185,129,0.12);--icon-color:#10b981;">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="pim-kpi-info">
                <div class="pim-kpi-value"><?= $publishedCount ?></div>
                <div class="pim-kpi-label">Yayında</div>
                <div class="pim-kpi-trend trend-up"><i class="bi bi-arrow-up-short"></i> Canlı Ürünler</div>
            </div>
        </div>
        <div class="pim-kpi-card" style="--accent-color:rgba(239,68,68,0.1);">
            <div class="pim-kpi-icon" style="--icon-bg:rgba(239,68,68,0.12);--icon-color:#ef4444;">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="pim-kpi-info">
                <div class="pim-kpi-value"><?= $criticalCount ?></div>
                <div class="pim-kpi-label">Kritik Stok</div>
                <div class="pim-kpi-trend trend-down"><i class="bi bi-arrow-down-short"></i> Acil Tedarik</div>
            </div>
        </div>
        <div class="pim-kpi-card" style="--accent-color:rgba(139,92,246,0.1);">
            <div class="pim-kpi-icon" style="--icon-bg:rgba(139,92,246,0.12);--icon-color:#8b5cf6;">
                <i class="bi bi-cpu"></i>
            </div>
            <div class="pim-kpi-info">
                <div class="pim-kpi-value">AI</div>
                <div class="pim-kpi-label">AI Destekli</div>
                <div class="pim-kpi-trend" style="color:#8b5cf6;"><i class="bi bi-stars"></i> Analiz Aktif</div>
            </div>
        </div>
        <div class="pim-kpi-card" style="--accent-color:rgba(245,158,11,0.1);">
            <div class="pim-kpi-icon" style="--icon-bg:rgba(245,158,11,0.12);--icon-color:#f59e0b;">
                <i class="bi bi-trash3"></i>
            </div>
            <div class="pim-kpi-info">
                <div class="pim-kpi-value"><?= $trashCount ?></div>
                <div class="pim-kpi-label">Arşiv / Silinen</div>
                <div class="pim-kpi-trend trend-flat"><i class="bi bi-recycle"></i> Geri Yüklenebilir</div>
            </div>
        </div>
    </div>

    <!-- ── Tabs + View Toggle ──────────────────────────────── -->
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div class="pim-tabs">
            <div class="pim-tab active" onclick="switchPimTab('active', this)" id="tab-active">
                <i class="bi bi-box-seam"></i>
                Aktif Ürünler
                <span class="tab-count"><?= $totalProducts ?></span>
            </div>
            <div class="pim-tab" onclick="switchPimTab('trash', this)" id="tab-trash">
                <i class="bi bi-trash3"></i>
                Geri Dönüşüm
                <span class="tab-count"><?= $trashCount ?></span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="view-toggle-btn active" id="viewList" onclick="setView('list')" title="Liste Görünümü">
                <i class="bi bi-list-ul"></i>
            </button>
            <button class="view-toggle-btn" id="viewCard" onclick="setView('card')" title="Kart Görünümü">
                <i class="bi bi-grid-3x3-gap"></i>
            </button>
        </div>
    </div>

    <!-- ── Filter & Toolbar Panel ─────────────────────────── -->
    <div class="pim-filter-panel">
        <div class="position-relative flex-grow-1" style="max-width:300px;">
            <input type="text" id="pimInstantSearch" class="pim-filter-input w-100" placeholder="Ürün adı, SKU, barkod ara..." value="<?= htmlspecialchars($q ?? '') ?>">
            <i class="bi bi-search position-absolute text-muted" style="right:12px;top:50%;transform:translateY(-50%);pointer-events:none;"></i>
        </div>
        <form method="GET" action="" class="d-flex align-items-center gap-2 flex-wrap" id="pimFilterForm">
            <select name="category_id" class="pim-filter-select" onchange="this.form.submit()">
                <option value="">Tüm Kategoriler</option>
                <?php foreach ($categories ?? [] as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($category_id ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="brand_id" class="pim-filter-select" onchange="this.form.submit()">
                <option value="">Tüm Markalar</option>
                <?php foreach ($brands ?? [] as $br): ?>
                    <option value="<?= $br['id'] ?>" <?= ($brand_id ?? '') == $br['id'] ? 'selected' : '' ?>><?= htmlspecialchars($br['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="pim-filter-select" onchange="this.form.submit()">
                <option value="">Tüm Durumlar</option>
                <option value="published" <?= ($status ?? '') === 'published' ? 'selected' : '' ?>>Yayında</option>
                <option value="draft"     <?= ($status ?? '') === 'draft'     ? 'selected' : '' ?>>Taslak</option>
                <option value="passive"   <?= ($status ?? '') === 'passive'   ? 'selected' : '' ?>>Pasif</option>
                <option value="archived"  <?= ($status ?? '') === 'archived'  ? 'selected' : '' ?>>Arşiv</option>
                <option value="out_of_stock" <?= ($status ?? '') === 'out_of_stock' ? 'selected' : '' ?>>Stokta Yok</option>
            </select>
        </form>

        <div class="pim-toolbar-divider"></div>

        <!-- Column Chooser -->
        <div class="dropdown">
            <button class="btn-pim-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" style="padding:8px 14px;font-size:12px;">
                <i class="bi bi-layout-three-columns"></i> Kolonlar
            </button>
            <div class="dropdown-menu pim-dropdown-menu p-2" style="min-width:180px;">
                <?php
                $cols = [
                    'col-thumb'    => ['label' => 'Resim',        'default' => true],
                    'col-name'     => ['label' => 'Ürün Adı',     'default' => true],
                    'col-cat'      => ['label' => 'Kategori',      'default' => true],
                    'col-brand'    => ['label' => 'Marka',         'default' => true],
                    'col-sku'      => ['label' => 'SKU',           'default' => true],
                    'col-stock'    => ['label' => 'Stok',          'default' => true],
                    'col-price'    => ['label' => 'Fiyat',         'default' => true],
                    'col-status'   => ['label' => 'Durum',         'default' => true],
                    'col-ai'       => ['label' => 'AI Skoru',      'default' => false],
                    'col-date'     => ['label' => 'Tarih',         'default' => true],
                ];
                foreach ($cols as $key => $col):
                ?>
                <div class="col-chooser-item">
                    <input type="checkbox" class="form-check-input pim-col-toggle" id="chk-<?= $key ?>"
                           data-col="<?= $key ?>" <?= $col['default'] ? 'checked' : '' ?>
                           style="accent-color:var(--pim-gold);width:16px;height:16px;">
                    <label class="form-check-label text-white" for="chk-<?= $key ?>" style="font-size:13px;cursor:pointer;">
                        <?= $col['label'] ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <a href="<?= url('/admin/products') ?>" class="btn-pim-secondary" style="padding:8px 14px;font-size:12px;" title="Filtreleri Temizle">
            <i class="bi bi-x-circle"></i>
        </a>
    </div>

    <!-- ── Bulk Action Bar ────────────────────────────────── -->
    <div id="pimBulkBar" style="display:none;">
        <div class="pim-bulk-bar">
            <div class="d-flex align-items-center gap-3">
                <span class="bulk-count-badge" id="pimSelectedCount">0</span>
                <span class="text-white" style="font-size:13px;font-weight:500;">ürün seçildi</span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <form action="<?= url('/admin/products/bulk') ?>" method="POST" id="pimBulkForm" class="d-flex align-items-center gap-2 flex-wrap">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" id="pimBulkAction" value="">
                    <div id="pimBulkIds"></div>

                    <div id="pimBulkCat" class="d-none">
                        <select name="target_category_id" class="pim-filter-select" style="min-width:160px;">
                            <option value="">Hedef Kategori</option>
                            <?php foreach ($categories ?? [] as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="pimBulkBrand" class="d-none">
                        <select name="target_brand_id" class="pim-filter-select" style="min-width:160px;">
                            <option value="">Hedef Marka</option>
                            <?php foreach ($brands ?? [] as $br): ?>
                                <option value="<?= $br['id'] ?>"><?= htmlspecialchars($br['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="pimBulkPrice" class="d-none">
                        <input type="number" step="0.01" name="bulk_price" class="pim-filter-input" placeholder="Yeni Fiyat" style="width:120px;">
                    </div>
                    <div id="pimBulkStock" class="d-none">
                        <input type="number" name="bulk_stock" class="pim-filter-input" placeholder="Stok Miktarı" style="width:120px;">
                    </div>

                    <div class="dropdown">
                        <button class="btn-pim-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" style="padding:7px 14px;font-size:12px;">
                            <i class="bi bi-lightning-charge"></i> Toplu İşlem
                        </button>
                        <ul class="dropdown-menu pim-dropdown-menu">
                            <li><a class="dropdown-item" onclick="pimBulkPrepare('publish')"><i class="bi bi-check2-circle text-success"></i> Toplu Yayınla</a></li>
                            <li><a class="dropdown-item" onclick="pimBulkPrepare('passive')"><i class="bi bi-pause-circle text-warning"></i> Toplu Pasif Yap</a></li>
                            <li><a class="dropdown-item" onclick="pimBulkPrepare('category')"><i class="bi bi-tags text-info"></i> Kategori Değiştir</a></li>
                            <li><a class="dropdown-item" onclick="pimBulkPrepare('brand')"><i class="bi bi-award text-info"></i> Marka Değiştir</a></li>
                            <li><a class="dropdown-item" onclick="pimBulkPrepare('price')"><i class="bi bi-currency-exchange text-success"></i> Fiyat Güncelle</a></li>
                            <li><a class="dropdown-item" onclick="pimBulkPrepare('stock')"><i class="bi bi-boxes text-warning"></i> Stok Güncelle</a></li>
                            <li><hr class="pim-dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" onclick="pimBulkPrepare('delete')"><i class="bi bi-trash"></i> Toplu Sil</a></li>
                        </ul>
                    </div>
                    <button type="button" id="pimBulkSubmit" class="btn-pim-primary d-none" onclick="pimBulkSubmit()" style="padding:7px 16px;font-size:12px;">
                        <i class="bi bi-check2"></i> Uygula
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══ ACTIVE PRODUCTS PANEL ══════════════════════════ -->
    <div id="panel-active">
        <!-- ── LIST VIEW ─────────────────────────────────── -->
        <div id="view-list" class="pim-grid-container">
            <div class="pim-grid-scroll">
                <table class="pim-table" id="pimTable">
                    <thead>
                        <tr>
                            <th class="col-check">
                                <input type="checkbox" id="pimSelectAll" onclick="pimToggleAll(this)"
                                       style="accent-color:var(--pim-gold);transform:scale(1.15);width:16px;height:16px;">
                            </th>
                            <th class="col-thumb col-thumb-hdr">Görsel</th>
                            <th class="col-name" onclick="pimSort('name')">Ürün Adı <i class="bi bi-chevron-expand sort-icon"></i></th>
                            <th class="col-cat" onclick="pimSort('category')">Kategori <i class="bi bi-chevron-expand sort-icon"></i></th>
                            <th class="col-brand" onclick="pimSort('brand')">Marka <i class="bi bi-chevron-expand sort-icon"></i></th>
                            <th class="col-sku">SKU / Barkod</th>
                            <th class="col-stock" onclick="pimSort('stock')">Stok <i class="bi bi-chevron-expand sort-icon"></i></th>
                            <th class="col-price" onclick="pimSort('price')">Fiyat <i class="bi bi-chevron-expand sort-icon"></i></th>
                            <th class="col-status">Durum</th>
                            <th class="col-ai" style="display:none;">AI Skoru</th>
                            <th class="col-date">Tarih</th>
                            <th class="text-end" style="padding-right:20px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="pimTableBody">
                        <?php if (!empty($products)): ?>
                            <?php foreach ($products as $p):
                                $stock      = (int)($p['total_stock'] ?? 0);
                                $critStock  = (int)($p['critical_stock'] ?? 5);
                                $maxStock   = max($stock, $critStock * 10, 100);
                                $stockPct   = $maxStock > 0 ? round(($stock / $maxStock) * 100) : 0;
                                $stockClass = $stock <= $critStock ? 'stock-crit' : ($stock <= $critStock * 2 ? 'stock-warn' : 'stock-ok');
                                $price      = (float)($p['price'] ?? 0);
                                $salePrice  = (float)($p['sale_price'] ?? 0);
                                $margin     = $price > 0 ? round((($price - (float)($p['cost_price'] ?? 0)) / $price) * 100) : 0;
                                $aiScore    = rand(40, 98); // Demo - gerçek veriden gelecek
                                $aiDeg      = round($aiScore / 100 * 360);
                                $aiColor    = $aiScore >= 70 ? '#10b981' : ($aiScore >= 50 ? '#f59e0b' : '#ef4444');
                                $statusMap  = ['published' => 'sp-published', 'draft' => 'sp-draft', 'passive' => 'sp-passive', 'archived' => 'sp-archived', 'coming_soon' => 'sp-coming'];
                                $statusLabel = ['published' => 'Yayında', 'draft' => 'Taslak', 'passive' => 'Pasif', 'archived' => 'Arşiv', 'coming_soon' => 'Yakında'];
                                $spClass    = $statusMap[$p['status']] ?? 'sp-passive';
                                $spLabel    = $statusLabel[$p['status']] ?? ucfirst($p['status']);
                            ?>
                            <tr class="pim-row" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>" data-sku="<?= htmlspecialchars($p['sku'] ?? '') ?>">
                                <td class="col-check">
                                    <input type="checkbox" class="pim-row-cb" value="<?= $p['id'] ?>" onchange="pimCheckChange()"
                                           style="accent-color:var(--pim-gold);transform:scale(1.15);width:16px;height:16px;">
                                </td>
                                <td class="col-thumb">
                                    <div class="prod-thumb-cell">
                                        <?php if (!empty($p['cover_path'])): ?>
                                            <img src="<?= url('/' . $p['cover_path']) ?>" class="prod-thumb" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                                        <?php else: ?>
                                            <div class="prod-thumb-placeholder"><i class="bi bi-box"></i></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="col-name">
                                    <div class="prod-name-wrap">
                                        <span class="prod-name-main"><?= htmlspecialchars($p['name']) ?></span>
                                        <?php if (!empty($p['subtitle'])): ?>
                                            <span class="prod-name-sub"><?= htmlspecialchars($p['subtitle']) ?></span>
                                        <?php endif; ?>
                                        <span class="prod-sku-badge"><?= htmlspecialchars($p['sku'] ?? '-') ?></span>
                                    </div>
                                </td>
                                <td class="col-cat" style="color:var(--pim-muted);font-size:13px;"><?= htmlspecialchars($p['category_name'] ?? 'Kategorisiz') ?></td>
                                <td class="col-brand" style="color:var(--pim-muted);font-size:13px;"><?= htmlspecialchars($p['brand_name'] ?? '—') ?></td>
                                <td class="col-sku">
                                    <div class="d-flex flex-column gap-1">
                                        <code class="prod-sku-badge"><?= htmlspecialchars($p['sku'] ?? '—') ?></code>
                                        <?php if (!empty($p['barcode'])): ?>
                                            <span style="font-size:10px;color:var(--pim-muted);">
                                                <i class="bi bi-upc-scan me-1"></i><?= htmlspecialchars($p['barcode']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="col-stock">
                                    <?php if (!empty($p['unlimited_stock'])): ?>
                                        <span style="color:var(--pim-info);font-size:12px;font-weight:600;">
                                            <i class="bi bi-infinity me-1"></i>Sınırsız
                                        </span>
                                    <?php else: ?>
                                        <div class="stock-cell <?= $stockClass ?>">
                                            <span class="stock-num"><?= $stock ?></span>
                                            <div class="stock-bar-wrap">
                                                <div class="stock-bar" style="width:<?= $stockPct ?>%;"></div>
                                            </div>
                                        </div>
                                        <?php if ($stock <= $critStock): ?>
                                            <span style="font-size:10px;color:var(--pim-danger);">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Kritik Seviye
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="col-price">
                                    <div class="price-cell">
                                        <span class="price-main"><?= htmlspecialchars($p['currency_code'] ?? '₺') ?> <?= number_format($price, 2) ?></span>
                                        <?php if ($salePrice > 0 && $salePrice < $price): ?>
                                            <span class="price-disc"><i class="bi bi-tag-fill me-1"></i><?= number_format($salePrice, 2) ?></span>
                                        <?php endif; ?>
                                        <?php if ($margin > 0): ?>
                                            <span class="price-cost">Marj: %<?= $margin ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="col-status">
                                    <span class="status-pill <?= $spClass ?>"><?= $spLabel ?></span>
                                </td>
                                <td class="col-ai" style="display:none;">
                                    <div class="ai-score-wrap">
                                        <div class="ai-score-ring" style="--score-color:<?= $aiColor ?>;--score-deg:<?= $aiDeg ?>deg;">
                                            <div class="ai-score-inner"><?= $aiScore ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-date" style="color:var(--pim-muted);font-size:12px;"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                <td>
                                    <div class="pim-action-btns">
                                        <a href="<?= url('/admin/products/edit?id=' . $p['id']) ?>" class="pim-btn-icon" title="Düzenle">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="<?= url('/admin/products/duplicate') ?>" method="POST" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="pim-btn-icon" title="Kopyala"><i class="bi bi-copy"></i></button>
                                        </form>
                                        <!-- Quick Actions Dropdown -->
                                        <div class="dropdown">
                                            <button class="pim-btn-icon dropdown-toggle" style="border:none;" data-bs-toggle="dropdown" title="Daha Fazla">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu pim-dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="<?= url('/admin/products/edit?id=' . $p['id'] . '&tab=seo') ?>"><i class="bi bi-search-heart"></i> SEO Düzenle</a></li>
                                                <li><a class="dropdown-item" href="<?= url('/admin/products/edit?id=' . $p['id'] . '&tab=media') ?>"><i class="bi bi-images"></i> Medya Yönet</a></li>
                                                <li><a class="dropdown-item" href="<?= url('/admin/products/edit?id=' . $p['id'] . '&tab=pricing') ?>"><i class="bi bi-currency-exchange"></i> Fiyatlandırma</a></li>
                                                <li><a class="dropdown-item" href="<?= url('/admin/products/edit?id=' . $p['id'] . '&tab=stock') ?>"><i class="bi bi-boxes"></i> Stok Yönetimi</a></li>
                                                <li><a class="dropdown-item" href="<?= url('/admin/products/edit?id=' . $p['id'] . '&tab=variants') ?>"><i class="bi bi-sliders"></i> Varyantlar</a></li>
                                                <li><hr class="pim-dropdown-divider"></li>
                                                <li>
                                                    <form action="<?= url('/admin/products/delete') ?>" method="POST" class="m-0" onsubmit="return confirm('Bu ürünü silmek istediğinize emin misiniz?')">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                        <button type="submit" class="dropdown-item text-danger border-0 bg-transparent w-100 text-start p-0" style="padding:9px 14px !important;">
                                                            <i class="bi bi-trash"></i> Sil
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center py-5">
                                    <div style="color:var(--pim-muted);">
                                        <i class="bi bi-box-seam" style="font-size:48px;display:block;margin-bottom:12px;"></i>
                                        <p class="mb-0" style="font-size:14px;font-weight:500;">Aktif ürün bulunamadı</p>
                                        <p style="font-size:12px;margin-top:4px;">
                                            <a href="<?= url('/admin/products/create') ?>" class="text-decoration-none" style="color:var(--pim-gold);">
                                                <i class="bi bi-plus-circle me-1"></i>İlk ürünü ekle
                                            </a>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pim-pagination">
                <span id="pimRowInfo"><?= $totalProducts ?> ürün listeleniyor</span>
                <div class="pim-page-btns">
                    <button class="pim-page-btn" id="pimPrevPage" onclick="pimPage(-1)" disabled><i class="bi bi-chevron-left"></i></button>
                    <button class="pim-page-btn active" id="pimPage1">1</button>
                    <button class="pim-page-btn" id="pimNextPage" onclick="pimPage(1)" <?= $totalProducts <= 25 ? 'disabled' : '' ?>><i class="bi bi-chevron-right"></i></button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size:12px;">Sayfa başı:</span>
                    <select class="pim-filter-select" id="pimPerPage" onchange="pimChangePerPage(this.value)" style="min-width:70px;padding:5px 10px;font-size:12px;">
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ── CARD VIEW ──────────────────────────────────── -->
        <div id="view-card" style="display:none;">
            <div class="pim-card-grid" id="pimCardGrid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p):
                        $stock     = (int)($p['total_stock'] ?? 0);
                        $critStock = (int)($p['critical_stock'] ?? 5);
                        $price     = (float)($p['price'] ?? 0);
                        $aiScore   = rand(40, 98);
                        $statusMap  = ['published' => 'sp-published', 'draft' => 'sp-draft', 'passive' => 'sp-passive', 'archived' => 'sp-archived', 'coming_soon' => 'sp-coming'];
                        $statusLabel = ['published' => 'Yayında', 'draft' => 'Taslak', 'passive' => 'Pasif', 'archived' => 'Arşiv', 'coming_soon' => 'Yakında'];
                        $spClass = $statusMap[$p['status']] ?? 'sp-passive';
                        $spLabel = $statusLabel[$p['status']] ?? ucfirst($p['status']);
                    ?>
                    <div class="pim-product-card" data-name="<?= htmlspecialchars($p['name']) ?>" data-sku="<?= htmlspecialchars($p['sku'] ?? '') ?>">
                        <input type="checkbox" class="pim-card-select pim-row-cb" value="<?= $p['id'] ?>" onchange="pimCheckChange()">

                        <div class="pim-card-img-wrap">
                            <?php if (!empty($p['cover_path'])): ?>
                                <img src="<?= url('/' . $p['cover_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="pim-card-img-placeholder"><i class="bi bi-box"></i></div>
                            <?php endif; ?>
                            <div class="pim-card-status-overlay">
                                <span class="status-pill <?= $spClass ?>" style="font-size:10px;"><?= $spLabel ?></span>
                            </div>
                        </div>

                        <div class="pim-card-body">
                            <div class="pim-card-brand"><?= htmlspecialchars($p['brand_name'] ?? 'Marka Yok') ?></div>
                            <div class="pim-card-title"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="pim-card-sku"><i class="bi bi-upc me-1"></i><?= htmlspecialchars($p['sku'] ?? '—') ?></div>
                            <div class="pim-card-metrics">
                                <div class="pim-card-metric">
                                    <div class="pim-card-metric-val" style="font-size:13px;"><?= htmlspecialchars($p['currency_code'] ?? '₺') ?> <?= number_format($price, 2) ?></div>
                                    <div class="pim-card-metric-lbl">Satış Fiyatı</div>
                                </div>
                                <div class="pim-card-metric">
                                    <div class="pim-card-metric-val <?= $stock <= $critStock ? 'text-danger' : 'text-success' ?>" style="font-size:13px;"><?= $stock ?></div>
                                    <div class="pim-card-metric-lbl">Stok</div>
                                </div>
                            </div>
                        </div>

                        <div class="pim-card-footer">
                            <div class="pim-card-ai">
                                <i class="bi bi-stars"></i>
                                AI: <?= $aiScore ?>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="<?= url('/admin/products/edit?id=' . $p['id']) ?>" class="pim-btn-icon" title="Düzenle"><i class="bi bi-pencil-square"></i></a>
                                <form action="<?= url('/admin/products/delete') ?>" method="POST" class="m-0" onsubmit="return confirm('Silmek istiyor musunuz?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="pim-btn-icon danger" title="Sil"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 w-100" style="grid-column:1/-1;color:var(--pim-muted);">
                        <i class="bi bi-box-seam" style="font-size:48px;display:block;margin-bottom:12px;"></i>
                        <p>Aktif ürün bulunamadı</p>
                        <a href="<?= url('/admin/products/create') ?>" class="btn-pim-primary" style="display:inline-flex;margin-top:8px;">
                            <i class="bi bi-plus-circle-fill"></i> Yeni Ürün Ekle
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══ TRASH / RECYCLE BIN PANEL ══════════════════════ -->
    <div id="panel-trash" style="display:none;">
        <div class="pim-grid-container">
            <div class="pim-grid-scroll">
                <table class="pim-table">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" style="accent-color:var(--pim-gold);transform:scale(1.15);"></th>
                            <th>Görsel</th>
                            <th>Ürün Adı</th>
                            <th>SKU</th>
                            <th>Fiyat</th>
                            <th>Silinme Tarihi</th>
                            <th class="text-end" style="padding-right:20px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($trash)): ?>
                            <?php foreach ($trash as $p): ?>
                                <tr>
                                    <td class="col-check">
                                        <input type="checkbox" class="pim-row-cb" value="<?= $p['id'] ?>"
                                               style="accent-color:var(--pim-gold);transform:scale(1.15);">
                                    </td>
                                    <td>
                                        <?php if (!empty($p['cover_path'])): ?>
                                            <img src="<?= url('/' . $p['cover_path']) ?>" class="prod-thumb" alt="">
                                        <?php else: ?>
                                            <div class="prod-thumb-placeholder"><i class="bi bi-box"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-weight-600"><?= htmlspecialchars($p['name']) ?></td>
                                    <td><code class="prod-sku-badge"><?= htmlspecialchars($p['sku'] ?? '—') ?></code></td>
                                    <td style="font-weight:600;">₺<?= number_format((float)($p['price'] ?? 0), 2) ?></td>
                                    <td style="color:var(--pim-muted);font-size:12px;"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                    <td>
                                        <div class="pim-action-btns">
                                            <form action="<?= url('/admin/products/restore') ?>" method="POST" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="pim-btn-icon" title="Geri Yükle" style="color:var(--pim-success);border-color:rgba(16,185,129,0.3);">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                            <form action="<?= url('/admin/products/force-delete') ?>" method="POST" class="m-0" onsubmit="return confirm('Kalıcı olarak silinsin mi? Bu işlem geri alınamaz!')">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="pim-btn-icon danger" title="Kalıcı Sil">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div style="color:var(--pim-muted);">
                                        <i class="bi bi-trash3" style="font-size:48px;display:block;margin-bottom:12px;"></i>
                                        <p class="mb-0" style="font-size:14px;">Geri dönüşüm kutusu boş</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /.pim-wrapper -->

<!-- ─── Import Modal ─────────────────────────────────────── -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= url('/admin/products/import') ?>" method="POST" enctype="multipart/form-data"
              class="modal-content border-0 text-white" style="background:#15102a;border-radius:20px;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="modal-header" style="border-color:var(--pim-border);">
                <h5 class="modal-title"><i class="bi bi-upload me-2" style="color:var(--pim-gold);"></i>Ürün İçe Aktar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" style="color:var(--pim-muted);font-size:13px;">Dosya Seçin (Excel, CSV, XML, JSON)</label>
                    <input type="file" name="import_file" accept=".csv,.xls,.xlsx,.xml,.json" required
                           class="form-control" style="background:rgba(255,255,255,0.03);border:1px solid var(--pim-border);color:var(--pim-text);border-radius:10px;">
                </div>
                <div class="p-3 rounded-3" style="background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.2);">
                    <p class="mb-0" style="font-size:12px;color:#93c5fd;">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Gelişmiş İçe Aktarım:</strong> Dosyanızı yükledikten sonra kolon eşleştirme ekranına yönlendirileceksiniz.
                    </p>
                </div>
            </div>
            <div class="modal-footer" style="border-color:var(--pim-border);">
                <button type="button" class="btn-pim-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="submit" class="btn-pim-primary"><i class="bi bi-upload me-1"></i> Yükle ve Devam Et</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── PIM JavaScript ────────────────────────────────────── -->
<script>
// ═══ State ════════════════════════════════════════════════
let pimCurrentTab  = 'active';
let pimCurrentView = 'list';
let pimSortCol     = null;
let pimSortDir     = 'asc';
let pimPage        = 1;
let pimPerPage     = 25;

// ═══ Tab Switch ═══════════════════════════════════════════
function switchPimTab(tab, el) {
    pimCurrentTab = tab;
    document.querySelectorAll('.pim-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('panel-active').style.display = tab === 'active' ? '' : 'none';
    document.getElementById('panel-trash').style.display  = tab === 'trash'  ? '' : 'none';
    document.getElementById('pimBulkBar').style.display = 'none';
    document.querySelectorAll('.pim-row-cb').forEach(cb => cb.checked = false);
    const allCb = document.getElementById('pimSelectAll');
    if (allCb) allCb.checked = false;
}

// ═══ View Toggle ══════════════════════════════════════════
function setView(v) {
    pimCurrentView = v;
    document.getElementById('view-list').style.display = v === 'list' ? '' : 'none';
    document.getElementById('view-card').style.display = v === 'card' ? '' : 'none';
    document.getElementById('viewList').classList.toggle('active', v === 'list');
    document.getElementById('viewCard').classList.toggle('active', v === 'card');
}

// ═══ Select All ═══════════════════════════════════════════
function pimToggleAll(master) {
    const panel = document.getElementById('panel-' + pimCurrentTab);
    const cbs   = panel ? panel.querySelectorAll('.pim-row-cb') : [];
    cbs.forEach(cb => cb.checked = master.checked);
    pimCheckChange();
}

function pimCheckChange() {
    const checked = document.querySelectorAll('#panel-' + pimCurrentTab + ' .pim-row-cb:checked');
    const bar = document.getElementById('pimBulkBar');
    const cnt = document.getElementById('pimSelectedCount');
    if (checked.length > 0) {
        bar.style.display = '';
        cnt.textContent = checked.length;
    } else {
        bar.style.display = 'none';
    }
}

// ═══ Bulk Actions ══════════════════════════════════════════
function pimBulkPrepare(action) {
    document.getElementById('pimBulkAction').value = action;
    ['Cat','Brand','Price','Stock'].forEach(id => {
        document.getElementById('pimBulk' + id).classList.add('d-none');
    });
    const submit = document.getElementById('pimBulkSubmit');

    if (action === 'category') { document.getElementById('pimBulkCat').classList.remove('d-none'); submit.classList.remove('d-none'); }
    else if (action === 'brand') { document.getElementById('pimBulkBrand').classList.remove('d-none'); submit.classList.remove('d-none'); }
    else if (action === 'price') { document.getElementById('pimBulkPrice').classList.remove('d-none'); submit.classList.remove('d-none'); }
    else if (action === 'stock') { document.getElementById('pimBulkStock').classList.remove('d-none'); submit.classList.remove('d-none'); }
    else { submit.classList.add('d-none'); pimBulkSubmit(); }
}

function pimBulkSubmit() {
    if (!confirm('Toplu işlem uygulanacak. Devam edilsin mi?')) return;
    const checked = document.querySelectorAll('#panel-' + pimCurrentTab + ' .pim-row-cb:checked');
    const container = document.getElementById('pimBulkIds');
    container.innerHTML = '';
    checked.forEach(cb => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'product_ids[]'; inp.value = cb.value;
        container.appendChild(inp);
    });
    document.getElementById('pimBulkForm').submit();
}

// ═══ Column Toggle ══════════════════════════════════════════
document.querySelectorAll('.pim-col-toggle').forEach(el => {
    el.addEventListener('change', function() {
        const col = this.getAttribute('data-col');
        const cells = document.querySelectorAll('.' + col);
        cells.forEach(c => { c.style.display = this.checked ? '' : 'none'; });
    });
});

// ═══ Instant Search ════════════════════════════════════════
document.getElementById('pimInstantSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();

    // List View filter
    document.querySelectorAll('#panel-active .pim-row').forEach(row => {
        const name = (row.getAttribute('data-name') || '').toLowerCase();
        const sku  = (row.getAttribute('data-sku')  || '').toLowerCase();
        row.style.display = (!q || name.includes(q) || sku.includes(q)) ? '' : 'none';
    });

    // Card View filter
    document.querySelectorAll('#pimCardGrid .pim-product-card').forEach(card => {
        const name = (card.getAttribute('data-name') || '').toLowerCase();
        const sku  = (card.getAttribute('data-sku')  || '').toLowerCase();
        card.style.display = (!q || name.includes(q) || sku.includes(q)) ? '' : 'none';
    });
});

// ═══ Sort ══════════════════════════════════════════════════
function pimSort(col) {
    if (pimSortCol === col) { pimSortDir = pimSortDir === 'asc' ? 'desc' : 'asc'; }
    else { pimSortCol = col; pimSortDir = 'asc'; }

    const tbody = document.getElementById('pimTableBody');
    const rows  = Array.from(tbody.querySelectorAll('.pim-row'));

    rows.sort((a, b) => {
        let aVal = '', bVal = '';
        if (col === 'name')     { aVal = a.getAttribute('data-name') || ''; bVal = b.getAttribute('data-name') || ''; }
        else if (col === 'sku') { aVal = a.getAttribute('data-sku')  || ''; bVal = b.getAttribute('data-sku')  || ''; }
        else if (col === 'price') {
            aVal = parseFloat(a.querySelector('.price-main')?.textContent.replace(/[^0-9.]/g,'') || 0);
            bVal = parseFloat(b.querySelector('.price-main')?.textContent.replace(/[^0-9.]/g,'') || 0);
            return pimSortDir === 'asc' ? aVal - bVal : bVal - aVal;
        }
        return pimSortDir === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
    });
    rows.forEach(r => tbody.appendChild(r));

    // Update sort indicators
    document.querySelectorAll('.pim-table thead th').forEach(th => {
        th.classList.remove('sort-asc','sort-desc');
    });
}

// ═══ Keyboard Navigation ════════════════════════════════════
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('pimBulkBar').style.display = 'none';
        document.querySelectorAll('.pim-row-cb').forEach(cb => cb.checked = false);
        const sel = document.getElementById('pimSelectAll');
        if (sel) sel.checked = false;
    }
});

// ═══ Stock bar animation on load ════════════════════════════
window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.stock-bar').forEach(bar => {
        const w = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => { bar.style.width = w; }, 200);
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
