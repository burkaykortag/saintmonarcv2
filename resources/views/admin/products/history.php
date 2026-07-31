<?php
use App\Helpers\ComponentHelper;
$title = 'PIM – Ürün Geçmişi | SaintMonarc';
include dirname(__DIR__) . '/layouts/header.php';
$productId = (int)($_GET['id'] ?? 0);

// ── Mock timeline events ──────────────────────────────────
$allEvents = [
    ['type'=>'create',   'icon'=>'bi-plus-circle',       'dot'=>'dot-create',  'title'=>'Ürün oluşturuldu',       'desc'=>'Ürün sisteme ilk kez eklendi. Taslak durumunda kaydedildi.', 'user'=>'Süper Admin', 'time'=>strtotime('-45 days')],
    ['type'=>'update',   'icon'=>'bi-pencil',             'dot'=>'dot-update',  'title'=>'Ürün adı güncellendi',   'desc'=>'Ad: "Deri Cüzdan" → "Premium Deri Cüzdan – Kahverengi"',       'user'=>'Admin',       'time'=>strtotime('-40 days')],
    ['type'=>'media',    'icon'=>'bi-image',              'dot'=>'dot-update',  'title'=>'Görsel yüklendi',         'desc'=>'5 adet ürün görseli yüklendi. Kapak görseli atandı.',           'user'=>'Admin',       'time'=>strtotime('-38 days')],
    ['type'=>'seo',      'icon'=>'bi-search-heart',       'dot'=>'dot-update',  'title'=>'SEO bilgileri eklendi',  'desc'=>'Meta başlık, açıklama ve anahtar kelimeler güncellendi.',        'user'=>'SEO Uzmanı',  'time'=>strtotime('-35 days')],
    ['type'=>'price',    'icon'=>'bi-currency-exchange',  'dot'=>'dot-price',   'title'=>'Fiyat güncellendi',      'desc'=>'₺449.90 → ₺499.90 (Fiyat artışı: %11)',                          'user'=>'Admin',       'time'=>strtotime('-30 days')],
    ['type'=>'stock',    'icon'=>'bi-boxes',              'dot'=>'dot-stock',   'title'=>'Stok eklendi',            'desc'=>'+100 adet stok. Yeni seviye: 150 adet.',                         'user'=>'Depo Yöne.', 'time'=>strtotime('-28 days')],
    ['type'=>'status',   'icon'=>'bi-toggle-on',          'dot'=>'dot-update',  'title'=>'Durum değiştirildi',     'desc'=>'Taslak → Yayında. Ürün mağazada görünmeye başladı.',              'user'=>'Admin',       'time'=>strtotime('-25 days')],
    ['type'=>'order',    'icon'=>'bi-bag',                'dot'=>'dot-order',   'title'=>'İlk sipariş alındı',     'desc'=>'Sipariş #1042 – 2 adet satış. Müşteri: Ahmet Y.',                'user'=>'Sistem',      'time'=>strtotime('-22 days')],
    ['type'=>'ai',       'icon'=>'bi-cpu',                'dot'=>'dot-ai',      'title'=>'AI analizi çalıştırıldı','desc'=>'Fiyat önerisi: ₺519. Stok riski: %15 (Düşük). Satış tahmini: +18%.', 'user'=>'AI Motoru', 'time'=>strtotime('-20 days')],
    ['type'=>'variant',  'icon'=>'bi-sliders',            'dot'=>'dot-update',  'title'=>'Varyant eklendi',        'desc'=>'Kahverengi-L ve Siyah-M varyantları eklendi.',                   'user'=>'Admin',       'time'=>strtotime('-18 days')],
    ['type'=>'price',    'icon'=>'bi-currency-exchange',  'dot'=>'dot-price',   'title'=>'Kampanya başlatıldı',    'desc'=>'Kampanya fiyatı: ₺399.90 (%20 indirim). 7 gün süreli.',           'user'=>'Pazarlama',  'time'=>strtotime('-15 days')],
    ['type'=>'stock',    'icon'=>'bi-arrow-return-left',  'dot'=>'dot-stock',   'title'=>'İade işlendi',            'desc'=>'-3 adet stok. Sipariş #1089 iadesi.',                            'user'=>'Depo Yöne.', 'time'=>strtotime('-12 days')],
    ['type'=>'workflow', 'icon'=>'bi-diagram-3',          'dot'=>'dot-update',  'title'=>'Workflow tetiklendi',     'desc'=>'Kalite kontrol workflow\'u başlatıldı. Sorumlu: Editörler.',     'user'=>'Sistem',      'time'=>strtotime('-10 days')],
    ['type'=>'update',   'icon'=>'bi-pencil',             'dot'=>'dot-update',  'title'=>'Açıklama güncellendi',   'desc'=>'Ana açıklama ve kutu içeriği Türkçe/İngilizce güncellendi.',     'user'=>'İçerik Eki.','time'=>strtotime('-7 days')],
    ['type'=>'order',    'icon'=>'bi-bag-check',          'dot'=>'dot-order',   'title'=>'Toplu sipariş',          'desc'=>'Sipariş #1234 – 15 adet. Bayi siparişi.',                        'user'=>'Sistem',      'time'=>strtotime('-5 days')],
    ['type'=>'ai',       'icon'=>'bi-cpu',                'dot'=>'dot-ai',      'title'=>'AI stok uyarısı',        'desc'=>'Stok riski yükseldi: %65 (Orta). Stok eklenmesi önerilir.',       'user'=>'AI Motoru',  'time'=>strtotime('-3 days')],
    ['type'=>'stock',    'icon'=>'bi-boxes',              'dot'=>'dot-stock',   'title'=>'Stok eklendi',            'desc'=>'+50 adet stok. Yeni seviye: 87 adet.',                          'user'=>'Depo Yöne.', 'time'=>strtotime('-2 days')],
    ['type'=>'status',   'icon'=>'bi-toggle-off',         'dot'=>'dot-update',  'title'=>'Kampanya sona erdi',     'desc'=>'Kampanya fiyatı kaldırıldı. Normal fiyata dönüldü: ₺499.90.',    'user'=>'Sistem',      'time'=>strtotime('-1 day')],
    ['type'=>'update',   'icon'=>'bi-star',               'dot'=>'dot-update',  'title'=>'Öne çıkan işaretlendi',  'desc'=>'Ürün "Öne Çıkan" olarak işaretlendi. Ana sayfada gösterilecek.', 'user'=>'Admin',       'time'=>strtotime('-6 hours')],
    ['type'=>'seo',      'icon'=>'bi-search-heart',       'dot'=>'dot-update',  'title'=>'SEO skoru güncellendi',  'desc'=>'SEO skoru: 58 → 82. Meta açıklama optimize edildi.',              'user'=>'SEO Uzmanı',  'time'=>strtotime('-1 hour')],
];

// ── Event stats ──────────────────────────────────────────
$typeCount = array_count_values(array_column($allEvents,'type'));
$statsKpis = [
    ['Toplam Olay',   count($allEvents),            'bi-list-ul',        'gold'],
    ['Bu Hafta',      count(array_filter($allEvents,fn($e)=>$e['time']>strtotime('-7 days'))), 'bi-calendar-week','info'],
    ['Fiyat Değ.',    ($typeCount['price']??0),      'bi-currency-exchange','warning'],
    ['Stok Değ.',     ($typeCount['stock']??0),      'bi-boxes',          'success'],
];

// ── Versions mock ─────────────────────────────────────────
$versions = [
    ['v2.1','Güncel', date('d.m.Y H:i', strtotime('-1 day')),   'Admin',      'Açıklama, SEO, stok güncellendi. Öne çıkan işaretlendi.'],
    ['v2.0','',       date('d.m.Y H:i', strtotime('-15 days')), 'Admin',      'Varyantlar eklendi. Kampanya başlatıldı. Fiyat düzenlendi.'],
    ['v1.2','',       date('d.m.Y H:i', strtotime('-30 days')), 'SEO Uzm.',   'SEO alanları dolduruldu. Slug güncellendi.'],
    ['v1.1','',       date('d.m.Y H:i', strtotime('-38 days')), 'Admin',      'Görseller yüklendi. Ad ve açıklama düzenlendi.'],
    ['v1.0','',       date('d.m.Y H:i', strtotime('-45 days')), 'Süper Admin','İlk oluşturma. Taslak olarak kaydedildi.'],
];

$dotColorMap = [
    'create'  => 'var(--pim-success)',
    'update'  => 'var(--pim-info)',
    'price'   => 'var(--pim-gold)',
    'stock'   => 'var(--pim-purple)',
    'order'   => 'var(--pim-cyan)',
    'ai'      => 'var(--pim-warning)',
    'workflow'=> 'var(--pim-info)',
    'media'   => 'var(--pim-info)',
    'variant' => 'var(--pim-purple)',
    'seo'     => 'var(--pim-success)',
    'status'  => 'var(--pim-gold)',
];
?>

<style>
/* ── History-specific ────────────────────────────────── */
.hist-header-bar{display:flex;align-items:center;flex-wrap:wrap;gap:10px;padding:14px 18px;background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius);margin-bottom:20px}
.hist-filter-pill{padding:6px 14px;border-radius:20px;border:1px solid var(--pim-border);background:transparent;color:var(--pim-text-xs);font-size:12px;cursor:pointer;transition:var(--pim-transition);white-space:nowrap}
.hist-filter-pill:hover,.hist-filter-pill.active{background:var(--pim-gold-glow);border-color:var(--pim-gold);color:var(--pim-gold)}
.hist-search{background:rgba(255,255,255,.03);border:1px solid var(--pim-border);border-radius:8px;color:var(--pim-text);font-size:12px;padding:7px 12px;outline:none;width:180px;transition:var(--pim-transition)}
.hist-search:focus{border-color:var(--pim-gold);background:rgba(197,168,128,.04)}
.timeline-dot-custom{width:14px;height:14px;border-radius:50%;border:2px solid var(--pim-card-border, #1a1535);flex-shrink:0;margin-top:3px}
.hist-version-row{display:flex;align-items:flex-start;gap:16px;padding:16px 18px;border-bottom:1px solid var(--pim-border);transition:background .15s}
.hist-version-row:last-child{border-bottom:none}
.hist-version-row:hover{background:rgba(255,255,255,.02)}
.ver-badge{font-family:var(--pim-font-mono);font-size:11px;font-weight:700;padding:3px 9px;border-radius:6px;background:rgba(197,168,128,.12);color:var(--pim-gold);white-space:nowrap}
.ver-badge.current{background:var(--pim-success-bg);color:var(--pim-success)}
/* ── Compare Modal ───────────────────────────────────── */
#compareModal{display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);align-items:center;justify-content:center}
#compareModal.open{display:flex}
.compare-modal-body{background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius-lg);padding:28px;max-width:720px;width:95%;max-height:85vh;overflow-y:auto}
.compare-col2{flex:1;background:var(--pim-surface);border-radius:var(--pim-radius-sm);padding:14px;border:1px solid var(--pim-border)}
/* ── Event hidden ────────────────────────────────────── */
.pim-timeline-item.hidden{display:none}
</style>

<div class="pim-module">

<!-- ─── Header ────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Ürünler' => url('/admin/products'), 'Geçmiş' => '#']) ?>
        <h2 class="text-white fw-bold m-0 mt-1" style="font-size:22px">
            <i class="bi bi-clock-history me-2 text-pim-gold"></i>Ürün Geçmişi
            <?php if ($productId): ?><span class="pim-badge pim-badge-muted ms-2">Ürün #<?= $productId ?></span><?php endif; ?>
        </h2>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <?php if ($productId): ?>
        <a href="<?= url('/admin/products/edit?id='.$productId) ?>" class="pim-btn pim-btn-secondary pim-btn-sm"><i class="bi bi-pencil"></i> Ürünü Düzenle</a>
        <?php endif; ?>
        <a href="<?= url('/admin/products') ?>" class="pim-btn pim-btn-secondary pim-btn-sm"><i class="bi bi-arrow-left"></i> Ürünler</a>
        <button class="pim-btn pim-btn-ghost pim-btn-sm" onclick="window.print()"><i class="bi bi-download"></i> Dışa Aktar</button>
    </div>
</div>

<!-- ─── KPI Stats ───────────────────────────────────────── -->
<div class="pim-kpi-row mb-4" style="grid-template-columns:repeat(4,1fr)">
    <?php foreach ($statsKpis as [$lbl,$val,$ico,$col]): ?>
    <div class="pim-kpi" style="--kpi-icon-bg:var(--pim-<?= $col ?>-bg);--kpi-icon-color:var(--pim-<?= $col ?>)">
        <div class="pim-kpi-icon"><i class="bi <?= $ico ?>"></i></div>
        <div><div class="pim-kpi-val"><?= $val ?></div><div class="pim-kpi-lbl"><?= $lbl ?></div></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ─── Filter Bar ──────────────────────────────────────── -->
<div class="hist-header-bar">
    <div class="d-flex gap-1 flex-wrap">
        <button class="hist-filter-pill active" data-filter="all"     onclick="filterEvents(this,'all')">Tümü <span class="pim-badge pim-badge-muted ms-1"><?= count($allEvents) ?></span></button>
        <button class="hist-filter-pill"        data-filter="update"  onclick="filterEvents(this,'update')">Güncelleme</button>
        <button class="hist-filter-pill"        data-filter="price"   onclick="filterEvents(this,'price')">Fiyat</button>
        <button class="hist-filter-pill"        data-filter="stock"   onclick="filterEvents(this,'stock')">Stok</button>
        <button class="hist-filter-pill"        data-filter="status"  onclick="filterEvents(this,'status')">Durum</button>
        <button class="hist-filter-pill"        data-filter="order"   onclick="filterEvents(this,'order')">Sipariş</button>
        <button class="hist-filter-pill"        data-filter="ai"      onclick="filterEvents(this,'ai')">AI</button>
        <button class="hist-filter-pill"        data-filter="workflow" onclick="filterEvents(this,'workflow')">Workflow</button>
    </div>
    <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
        <div style="position:relative">
            <i class="bi bi-search" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--pim-text-xs);font-size:12px"></i>
            <input class="hist-search" id="histSearchInput" type="search" placeholder="Olaylarda ara..." oninput="searchEvents(this.value)" style="padding-left:28px">
        </div>
        <input class="hist-search" type="date" style="width:130px" onchange="filterByDate(this.value,'from')" title="Başlangıç tarihi">
        <input class="hist-search" type="date" style="width:130px" onchange="filterByDate(this.value,'to')" title="Bitiş tarihi">
    </div>
</div>

<!-- ─── Main Timeline ───────────────────────────────────── -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="pim-card mb-4" style="background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius-lg);padding:24px">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="fw-700 text-white fs-6"><i class="bi bi-clock-history text-pim-gold me-2"></i>İşlem Zaman Tüneli</div>
                <span class="text-muted fs-7" id="eventCountBadge"><?= count($allEvents) ?> olay</span>
            </div>
            <div class="pim-timeline" id="mainTimeline">
                <?php foreach (array_reverse($allEvents) as $ev): ?>
                <div class="pim-timeline-item" data-event-type="<?= $ev['type'] ?>" data-event-text="<?= strtolower(htmlspecialchars($ev['title'].' '.$ev['desc'].' '.$ev['user'])) ?>" data-event-time="<?= $ev['time'] ?>">
                    <div class="timeline-dot-custom" style="background:<?= $dotColorMap[$ev['type']] ?? 'var(--pim-info)' ?>;border-color:var(--pim-surface)"></div>
                    <div class="pim-timeline-content">
                        <div class="pim-timeline-header">
                            <span class="pim-timeline-action">
                                <i class="bi <?= $ev['icon'] ?> me-2" style="color:<?= $dotColorMap[$ev['type']] ?? 'var(--pim-info)' ?>"></i>
                                <?= htmlspecialchars($ev['title']) ?>
                            </span>
                            <span class="pim-timeline-time" title="<?= date('d.m.Y H:i:s', $ev['time']) ?>">
                                <?php
                                $diff = time() - $ev['time'];
                                if ($diff < 3600)      echo round($diff/60) . ' dk önce';
                                elseif ($diff < 86400) echo round($diff/3600) . ' sa önce';
                                elseif ($diff < 604800) echo round($diff/86400) . ' gün önce';
                                else echo date('d.m.Y H:i', $ev['time']);
                                ?>
                            </span>
                        </div>
                        <div class="pim-timeline-desc" style="margin-top:4px"><?= htmlspecialchars($ev['desc']) ?></div>
                        <div class="pim-timeline-actor">
                            <i class="bi bi-person-circle"></i><?= htmlspecialchars($ev['user']) ?>
                            <span class="pim-badge pim-badge-muted ms-2" style="font-size:10px"><?= ucfirst($ev['type']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="noEventsMsg" class="pim-empty py-5" style="display:none">
                <i class="bi bi-search"></i><h4>Olay Bulunamadı</h4><p>Arama kriterlerinizle eşleşen olay yok.</p>
            </div>
        </div>
    </div>

    <!-- ─── Sidebar ─────────────────────────────────────── -->
    <div class="col-lg-4">

        <!-- Event Type Summary -->
        <div class="pim-card mb-4" style="background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius-lg);padding:20px">
            <div class="fw-700 text-white mb-3 fs-6"><i class="bi bi-pie-chart text-pim-gold me-2"></i>Olay Özeti</div>
            <?php
            $typeLabelMap = ['create'=>'Oluşturma','update'=>'Güncelleme','price'=>'Fiyat','stock'=>'Stok','order'=>'Sipariş','ai'=>'AI','workflow'=>'Workflow','media'=>'Medya','variant'=>'Varyant','seo'=>'SEO','status'=>'Durum'];
            arsort($typeCount);
            $maxCount = max($typeCount);
            ?>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($typeCount as $type => $cnt): ?>
                <div class="d-flex align-items-center gap-2">
                    <span style="width:8px;height:8px;border-radius:50%;background:<?= $dotColorMap[$type] ?? 'var(--pim-info)' ?>;flex-shrink:0"></span>
                    <span class="fs-7 flex-1"><?= $typeLabelMap[$type] ?? ucfirst($type) ?></span>
                    <div style="width:70px;height:4px;border-radius:2px;background:rgba(255,255,255,.06);overflow:hidden">
                        <div style="width:<?= round($cnt/$maxCount*100) ?>%;height:100%;background:<?= $dotColorMap[$type] ?? 'var(--pim-info)' ?>"></div>
                    </div>
                    <span class="pim-code fs-7" style="width:20px;text-align:right"><?= $cnt ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Version History -->
        <div class="pim-card" style="background:var(--pim-card);border:1px solid var(--pim-border);border-radius:var(--pim-radius-lg);overflow:hidden">
            <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom border-opacity-10 border-white">
                <div class="fw-700 text-white fs-6"><i class="bi bi-git text-pim-gold me-2"></i>Versiyon Geçmişi</div>
                <button class="pim-btn pim-btn-ghost pim-btn-sm" style="font-size:11px" title="Versiyon kaydet"><i class="bi bi-bookmark-plus"></i></button>
            </div>
            <?php foreach ($versions as $vi => [$ver, $current, $date, $user, $changes]): ?>
            <div class="hist-version-row">
                <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                    <span class="ver-badge <?= $current ? 'current' : '' ?>"><?= $ver ?><?= $current ? ' ★' : '' ?></span>
                    <?php if ($vi < count($versions)-1): ?>
                    <div style="width:1px;flex:1;min-height:20px;background:var(--pim-border)"></div>
                    <?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fs-7 fw-600 text-white"><?= $changes ?></div>
                            <div class="text-muted mt-1" style="font-size:10px"><i class="bi bi-person-circle me-1"></i><?= $user ?> · <?= $date ?></div>
                        </div>
                    </div>
                    <div class="d-flex gap-1 mt-2 flex-wrap">
                        <button class="pim-btn pim-btn-ghost pim-btn-sm" style="font-size:10px;padding:3px 8px" onclick="viewVersion('<?= $ver ?>')"><i class="bi bi-eye"></i></button>
                        <?php if (!$current): ?>
                        <button class="pim-btn pim-btn-ghost pim-btn-sm" style="font-size:10px;padding:3px 8px" onclick="compareVersion('<?= $versions[0][0] ?>','<?= $ver ?>')"><i class="bi bi-arrow-left-right"></i></button>
                        <button class="pim-btn pim-btn-warning pim-btn-sm" style="font-size:10px;padding:3px 8px" onclick="restoreVersion('<?= $ver ?>')"><i class="bi bi-arrow-counterclockwise"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div><!-- /col-lg-4 -->
</div><!-- /row -->

</div><!-- /pim-module -->

<!-- ── Version Compare Modal ──────────────────────────────── -->
<div id="compareModal" role="dialog" aria-modal="true" aria-labelledby="compareModalTitle">
    <div class="compare-modal-body">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h5 class="text-white fw-700 m-0" id="compareModalTitle"><i class="bi bi-arrow-left-right text-pim-gold me-2"></i>Versiyon Karşılaştırma</h5>
            <button class="pim-btn pim-btn-ghost pim-btn-sm pim-btn-icon" onclick="closeCompare()" aria-label="Kapat"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="d-flex gap-3" id="compareBody">
            <div class="compare-col2">
                <div class="fw-700 text-pim-gold mb-3" id="cmpVerA">v2.1 (Güncel)</div>
                <div class="d-flex flex-column gap-2 fs-7">
                    <div class="d-flex justify-content-between"><span class="text-muted">Ürün Adı</span><span>Premium Deri Cüzdan</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Fiyat</span><span class="text-success">₺499.90</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Stok</span><span>87</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Durum</span><span class="pim-badge pim-badge-success">Yayında</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">SEO Skoru</span><span class="text-success">82</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Varyant</span><span>2 adet</span></div>
                </div>
            </div>
            <div class="compare-col2">
                <div class="fw-700 text-muted mb-3" id="cmpVerB">v1.0</div>
                <div class="d-flex flex-column gap-2 fs-7">
                    <div class="d-flex justify-content-between"><span class="text-muted">Ürün Adı</span><span>Deri Cüzdan</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Fiyat</span><span class="text-danger">₺449.90</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Stok</span><span>0</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Durum</span><span class="pim-badge pim-badge-muted">Taslak</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">SEO Skoru</span><span class="text-danger">0</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Varyant</span><span>—</span></div>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
            <button class="pim-btn pim-btn-secondary" onclick="closeCompare()">Kapat</button>
        </div>
    </div>
</div>

<script>
/* ── Filter Events ─────────────────────────── */
let activeType = 'all';
let searchTerm = '';
let dateFrom   = null;
let dateTo     = null;

function filterEvents(btn, type) {
    activeType = type;
    document.querySelectorAll('.hist-filter-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

function searchEvents(val) {
    searchTerm = val.toLowerCase();
    applyFilters();
}

function filterByDate(val, which) {
    if (which === 'from') dateFrom = val ? new Date(val).getTime()/1000 : null;
    else                  dateTo   = val ? new Date(val).getTime()/1000 + 86400 : null;
    applyFilters();
}

function applyFilters() {
    const items = document.querySelectorAll('#mainTimeline .pim-timeline-item');
    let visible = 0;
    items.forEach(item => {
        const type = item.dataset.eventType;
        const text = item.dataset.eventText || '';
        const time = parseInt(item.dataset.eventTime || '0');
        const typeOk  = activeType === 'all' || type === activeType;
        const textOk  = !searchTerm || text.includes(searchTerm);
        const fromOk  = !dateFrom || time >= dateFrom;
        const toOk    = !dateTo   || time <= dateTo;
        const show    = typeOk && textOk && fromOk && toOk;
        item.classList.toggle('hidden', !show);
        if (show) visible++;
    });
    document.getElementById('eventCountBadge').textContent = visible + ' olay';
    document.getElementById('noEventsMsg').style.display = visible === 0 ? '' : 'none';
}

/* ── Version Actions ───────────────────────── */
function viewVersion(ver) {
    PIM.toast.info('Versiyon ' + ver + ' görüntüleniyor...');
}

function compareVersion(vA, vB) {
    document.getElementById('cmpVerA').textContent = vA + ' (Güncel)';
    document.getElementById('cmpVerB').textContent = vB;
    document.getElementById('compareModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeCompare() {
    document.getElementById('compareModal').classList.remove('open');
    document.body.style.overflow = '';
}

function restoreVersion(ver) {
    if (!confirm('Bu versiyona geri yüklensin mi?\n' + ver + ' – Bu işlem geri alınamaz.')) return;
    PIM.toast.success('Versiyon ' + ver + ' geri yükleniyor...');
    setTimeout(() => window.location.reload(), 1500);
}

/* ── Close modal on backdrop click ────────── */
document.getElementById('compareModal').addEventListener('click', function(e) {
    if (e.target === this) closeCompare();
});

/* ── ESC to close ──────────────────────────── */
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCompare(); });
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
