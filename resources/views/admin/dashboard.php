<?php
use App\Helpers\ComponentHelper;

$title = "Dashboard - SaintMonarc Yönetim Paneli";
include __DIR__ . '/layouts/header.php';

// Prepare variables from analytics
$sales = $analytics['sales'];
$statusCounts = $analytics['status_counts'];
$stock = $analytics['stock'];
$recentOrders = $analytics['recent_orders'];
$recentMembers = $analytics['recent_members'];
$categorySales = $analytics['category_sales'];
$chartData = $analytics['chart_data'];
$currentFilter = $analytics['filter'];
$bounds = $analytics['bounds'];
?>

<!-- Date Filter & Dashboard Control -->
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Dashboard' => url('/admin')]) ?>
        <h2 class="mt-2 text-white font-weight-700" style="font-size: 26px;">Yönetim Paneli Analitiği</h2>
        <p class="text-muted mb-0 fs-6">Tüm e-ticaret metriklerini, sipariş durumlarını ve satış trendlerini anlık izleyin.</p>
    </div>
    
    <div class="d-flex flex-wrap align-items-center gap-2">
        <form method="GET" action="" class="d-flex align-items-center gap-2">
            <select name="filter" class="form-select border-0 text-white" style="background: rgba(255,255,255,0.05); border: 1px solid var(--sm-border) !important; border-radius: 10px; font-size:14px; padding: 10px 16px; min-width: 140px;" onchange="handleFilterChange(this)">
                <option value="today" <?= $currentFilter === 'today' ? 'selected' : '' ?>>Bugün</option>
                <option value="yesterday" <?= $currentFilter === 'yesterday' ? 'selected' : '' ?>>Dün</option>
                <option value="last_7_days" <?= $currentFilter === 'last_7_days' ? 'selected' : '' ?>>Son 7 Gün</option>
                <option value="last_30_days" <?= $currentFilter === 'last_30_days' ? 'selected' : '' ?>>Son 30 Gün</option>
                <option value="this_month" <?= $currentFilter === 'this_month' ? 'selected' : '' ?>>Bu Ay</option>
                <option value="this_year" <?= $currentFilter === 'this_year' ? 'selected' : '' ?>>Bu Yıl</option>
                <option value="custom" <?= $currentFilter === 'custom' ? 'selected' : '' ?>>Özel Tarih Aralığı</option>
            </select>

            <!-- Custom date range inputs -->
            <div id="customDateRange" class="d-flex align-items-center gap-2 <?= $currentFilter === 'custom' ? '' : 'd-none' ?>">
                <input type="date" name="start_date" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.05); border: 1px solid var(--sm-border) !important; font-size:13px;" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                <span class="text-muted">-</span>
                <input type="date" name="end_date" class="form-control border-0 text-white" style="background: rgba(255,255,255,0.05); border: 1px solid var(--sm-border) !important; font-size:13px;" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                <button type="submit" class="btn px-3" style="padding: 8px 12px; font-size:13px;">Uygula</button>
            </div>
        </form>

        <button class="btn btn-secondary border-0" onclick="exportReport('xlsx')">
            <i class="bi bi-file-earmark-excel me-2"></i> Excel Dışa Aktar
        </button>
    </div>
</div>

<!-- 1. SALES SUMMARY CARDS -->
<div class="row g-4 mb-4">
    <!-- Today Sales -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 18px;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted fs-7 text-uppercase" style="letter-spacing: 0.5px; font-weight: 500;">Filtrelenmiş Satışlar</span>
                <div class="p-2 rounded-3" style="background: rgba(197, 168, 128, 0.1); color: var(--sm-gold);"><i class="bi bi-wallet2 fs-5"></i></div>
            </div>
            <h3 class="font-weight-700 m-0" style="font-size: 26px; color:#ffffff;">₺<?= number_format($sales['total_sales'], 2) ?></h3>
            <div class="d-flex align-items-center justify-content-between mt-3">
                <span class="<?= $sales['total_sales_change'] >= 0 ? 'text-success' : 'text-danger' ?> font-weight-600" style="font-size: 13px;">
                    <i class="bi <?= $sales['total_sales_change'] >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short' ?>"></i> 
                    <?= number_format(abs($sales['total_sales_change']), 1) ?>%
                </span>
                <!-- Sparkline SVG mock -->
                <svg width="80" height="25">
                    <path d="M 0 18 Q 20 8, 40 16 T 80 4" fill="none" stroke="<?= $sales['total_sales_change'] >= 0 ? '#22c55e' : '#ef4444' ?>" stroke-width="2" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Order Count -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 18px;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted fs-7 text-uppercase" style="letter-spacing: 0.5px; font-weight: 500;">Sipariş Adeti</span>
                <div class="p-2 rounded-3" style="background: rgba(134, 239, 172, 0.1); color: #86efac;"><i class="bi bi-cart3 fs-5"></i></div>
            </div>
            <h3 class="font-weight-700 m-0" style="font-size: 26px; color:#ffffff;"><?= $sales['order_count'] ?> Adet</h3>
            <div class="d-flex align-items-center justify-content-between mt-3">
                <span class="<?= $sales['order_count_change'] >= 0 ? 'text-success' : 'text-danger' ?> font-weight-600" style="font-size: 13px;">
                    <i class="bi <?= $sales['order_count_change'] >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short' ?>"></i> 
                    <?= number_format(abs($sales['order_count_change']), 1) ?>%
                </span>
                <svg width="80" height="25">
                    <path d="M 0 10 Q 20 18, 40 5 T 80 15" fill="none" stroke="<?= $sales['order_count_change'] >= 0 ? '#22c55e' : '#ef4444' ?>" stroke-width="2" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Average Order Value (AOV) -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 18px;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted fs-7 text-uppercase" style="letter-spacing: 0.5px; font-weight: 500;">Ortalama Sepet</span>
                <div class="p-2 rounded-3" style="background: rgba(147, 197, 253, 0.1); color: #93c5fd;"><i class="bi bi-tag fs-5"></i></div>
            </div>
            <h3 class="font-weight-700 m-0" style="font-size: 26px; color:#ffffff;">₺<?= number_format($sales['aov'], 2) ?></h3>
            <div class="d-flex align-items-center justify-content-between mt-3">
                <span class="<?= $sales['aov_change'] >= 0 ? 'text-success' : 'text-danger' ?> font-weight-600" style="font-size: 13px;">
                    <i class="bi <?= $sales['aov_change'] >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short' ?>"></i> 
                    <?= number_format(abs($sales['aov_change']), 1) ?>%
                </span>
                <svg width="80" height="25">
                    <path d="M 0 15 Q 20 5, 40 18 T 80 10" fill="none" stroke="<?= $sales['aov_change'] >= 0 ? '#22c55e' : '#ef4444' ?>" stroke-width="2" />
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- PRODUCT MANAGEMENT SUMMARY CARDS -->
<h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;">Ürün Yönetim Özetleri</h4>
<div class="row g-3 mb-5">
    <!-- Toplam Ürün -->
    <div class="col-6 col-md-4 col-xl-2.4" style="width: 20%; min-width: 150px;">
        <div class="card p-3 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between mb-2 text-primary">
                <i class="bi bi-box-seam fs-6"></i>
                <span style="font-size: 11px; font-weight: 500;" class="text-muted text-uppercase">Toplam Ürün</span>
            </div>
            <h4 class="text-white font-weight-700 m-0" style="font-size: 20px;"><?= $stock['total_products'] ?? 0 ?></h4>
        </div>
    </div>
    <!-- Aktif Ürün -->
    <div class="col-6 col-md-4 col-xl-2.4" style="width: 20%; min-width: 150px;">
        <div class="card p-3 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between mb-2 text-success">
                <i class="bi bi-check-circle fs-6"></i>
                <span style="font-size: 11px; font-weight: 500;" class="text-muted text-uppercase">Aktif Ürün</span>
            </div>
            <h4 class="text-white font-weight-700 m-0" style="font-size: 20px;"><?= $stock['active_products'] ?? 0 ?></h4>
        </div>
    </div>
    <!-- Pasif Ürün -->
    <div class="col-6 col-md-4 col-xl-2.4" style="width: 20%; min-width: 150px;">
        <div class="card p-3 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between mb-2 text-warning">
                <i class="bi bi-pause-circle fs-6"></i>
                <span style="font-size: 11px; font-weight: 500;" class="text-muted text-uppercase">Pasif Ürün</span>
            </div>
            <h4 class="text-white font-weight-700 m-0" style="font-size: 20px;"><?= $stock['passive_products'] ?? 0 ?></h4>
        </div>
    </div>
    <!-- Taslak Ürün -->
    <div class="col-6 col-md-4 col-xl-2.4" style="width: 20%; min-width: 150px;">
        <div class="card p-3 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between mb-2 text-secondary">
                <i class="bi bi-file-earmark-text fs-6"></i>
                <span style="font-size: 11px; font-weight: 500;" class="text-muted text-uppercase">Taslak Ürün</span>
            </div>
            <h4 class="text-white font-weight-700 m-0" style="font-size: 20px;"><?= $stock['draft_products'] ?? 0 ?></h4>
        </div>
    </div>
    <!-- Kritik Stok -->
    <div class="col-6 col-md-4 col-xl-2.4" style="width: 20%; min-width: 150px;">
        <div class="card p-3 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between mb-2 text-danger">
                <i class="bi bi-exclamation-triangle fs-6"></i>
                <span style="font-size: 11px; font-weight: 500;" class="text-muted text-uppercase">Kritik Stok</span>
            </div>
            <h4 class="text-white font-weight-700 m-0" style="font-size: 20px;"><?= $stock['critical_stock'] ?? 0 ?></h4>
        </div>
    </div>
</div>

<!-- 2. ORDER STATUS CARDS -->
<h4 class="text-white font-weight-600 mb-3" style="font-size: 16px;">Sipariş Süreçleri</h4>
<div class="row g-3 mb-5">
    <?php
    $statusDetails = [
        'pending' => ['label' => 'Bekleyen', 'color' => 'warning', 'icon' => 'bi-hourglass-split'],
        'processing' => ['label' => 'Onaylanan & Hazırlanan', 'color' => 'info', 'icon' => 'bi-gear'],
        'shipped' => ['label' => 'Kargoda', 'color' => 'primary', 'icon' => 'bi-truck'],
        'delivered' => ['label' => 'Teslim Edildi', 'color' => 'success', 'icon' => 'bi-check-circle'],
        'cancelled' => ['label' => 'İptal', 'color' => 'danger', 'icon' => 'bi-x-circle'],
        'refunded' => ['label' => 'İade', 'color' => 'secondary', 'icon' => 'bi-arrow-counterclockwise'],
    ];

    foreach ($statusCounts as $status => $count):
        $detail = $statusDetails[$status] ?? ['label' => $status, 'color' => 'secondary', 'icon' => 'bi-question-circle'];
    ?>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= url('/admin/orders?status=' . $status) ?>" class="card p-3 border-0 text-decoration-none transition" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border) !important; border-radius: 12px; display: block;">
                <div class="d-flex align-items-center gap-2 mb-2 text-<?= $detail['color'] ?>">
                    <i class="bi <?= $detail['icon'] ?>"></i>
                    <span style="font-size: 12px; font-weight: 500;" class="text-truncate"><?= $detail['label'] ?></span>
                </div>
                <h4 class="text-white font-weight-700 m-0" style="font-size: 20px;"><?= $count ?></h4>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<!-- MAIN ANALYTICS COLUMN GRID -->
<div class="row g-4 mb-5">
    
    <!-- LEFT COLUMN: Charts & Recent Tables -->
    <div class="col-12 col-xl-8">
        
        <!-- Graph panel -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-white font-weight-500 m-0" style="font-size: 17px;">Satış Performans Grafiği</h4>
                <span class="badge" style="background: rgba(197, 168, 128, 0.1); color: var(--sm-gold); font-size: 11px;">Aktif Raporlama</span>
            </div>
            <div style="height: 320px; position: relative;">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

        <!-- Recent Orders Table -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-white font-weight-500 m-0" style="font-size: 17px;">Son Siparişler</h4>
                <a href="<?= url('/admin/orders') ?>" class="text-decoration-none" style="color: var(--sm-gold); font-size:13px; font-weight:500;">Tümünü Gör</a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-dark table-hover border-0 m-0" style="background: transparent;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--sm-border); color: var(--sm-text-muted); font-size:13px;">
                            <th class="py-3">Sipariş No</th>
                            <th class="py-3">Müşteri</th>
                            <th class="py-3">Tutar</th>
                            <th class="py-3">Durum</th>
                            <th class="py-3">Tarih</th>
                            <th class="py-3 text-end">Aksiyon</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentOrders)): ?>
                            <?php foreach ($recentOrders as $o): ?>
                                <tr style="border-bottom: 1px solid var(--sm-border); font-size: 13px;">
                                    <td class="py-3 font-weight-600"><?= htmlspecialchars($o['order_number']) ?></td>
                                    <td class="py-3"><?= htmlspecialchars($o['customer_name'] ?? 'Bilinmeyen Müşteri') ?></td>
                                    <td class="py-3">₺<?= number_format($o['grand_total'], 2) ?></td>
                                    <td class="py-3">
                                        <?= ComponentHelper::badge($o['status'] === 'delivered' ? 'Teslim Edildi' : ($o['status'] === 'pending' ? 'Bekliyor' : $o['status']), $o['status'] === 'delivered' ? 'success' : 'warning') ?>
                                    </td>
                                    <td class="py-3 text-muted"><?= date('d M H:i', strtotime($o['created_at'])) ?></td>
                                    <td class="py-3 text-end">
                                        <a href="<?= url('/admin/orders/view?id=' . $o['id']) ?>" class="btn btn-secondary py-1 px-3" style="font-size:11px;">Detay</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Kayıtlı sipariş bulunamadı.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Son Eklenen Ürünler Table -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-white font-weight-500 m-0" style="font-size: 17px;">Son Eklenen Ürünler</h4>
                <a href="<?= url('/admin/products') ?>" class="text-decoration-none" style="color: var(--sm-gold); font-size:13px; font-weight:500;">Tümünü Gör</a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-dark table-hover border-0 m-0" style="background: transparent;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--sm-border); color: var(--sm-text-muted); font-size:13px;">
                            <th class="py-3">Ürün ID</th>
                            <th class="py-3">Ürün Adı</th>
                            <th class="py-3">Satış Fiyatı</th>
                            <th class="py-3 text-end">Kayıt Tarihi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stock['newly_added'])): ?>
                            <?php foreach ($stock['newly_added'] as $p): ?>
                                <tr style="border-bottom: 1px solid var(--sm-border); font-size: 13px;">
                                    <td class="py-3 font-weight-600">#<?= $p['id'] ?></td>
                                    <td class="py-3"><?= htmlspecialchars($p['name']) ?></td>
                                    <td class="py-3">₺<?= number_format((float)$p['price'], 2) ?></td>
                                    <td class="py-3 text-muted text-end"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Kayıtlı ürün bulunamadı.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stock Status Area -->
        <div class="row g-4">
            <div class="col-12 col-md-6">
                <div class="card p-4 border-0 h-100" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                    <h4 class="text-white font-weight-500 mb-4" style="font-size: 16px;">Kritik Stok Uyarıları</h4>
                    <ul class="list-group list-group-flush" style="background: transparent;">
                        <?php if ($stock['critical_stock'] > 0 || $stock['out_of_stock'] > 0): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-0 px-0 py-2 text-danger">
                                <span><i class="bi bi-exclamation-triangle me-2"></i> Stoğu Biten Ürünler</span>
                                <span class="badge bg-danger rounded-pill"><?= $stock['out_of_stock'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-0 px-0 py-2 text-warning">
                                <span><i class="bi bi-exclamation-circle me-2"></i> Kritik Stok Seviyesindekiler (1-5)</span>
                                <span class="badge bg-warning text-dark rounded-pill"><?= $stock['critical_stock'] ?></span>
                            </li>
                        <?php else: ?>
                            <li class="list-group-item bg-transparent border-0 text-muted px-0">Tüm ürün stok seviyeleri ideal durumda.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="col-12 col-md-6">
                <div class="card p-4 border-0 h-100" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                    <h4 class="text-white font-weight-500 mb-3" style="font-size: 16px;">Kategori Satış Payları</h4>
                    <div style="height: 180px; position: relative;">
                        <canvas id="categorySalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- RIGHT COLUMN: Calendar, Notifications, Quick Actions, Members -->
    <div class="col-12 col-xl-4">
        
        <!-- 9. QUICK ACTIONS -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-500 mb-3" style="font-size: 16px;">Hızlı İşlemler</h4>
            <div class="row g-2">
                <div class="col-6">
                    <a href="<?= url('/admin/products/create') ?>" class="btn btn-secondary border-0 w-100 p-3 text-start d-flex flex-column gap-2" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 12px;">
                        <i class="bi bi-plus-circle text-warning fs-5"></i>
                        <span style="font-size: 12px; font-weight:500;">Ürün Ekle</span>
                    </a>
                </div>
                <div class="col-6">
                    <a href="<?= url('/admin/orders') ?>" class="btn btn-secondary border-0 w-100 p-3 text-start d-flex flex-column gap-2" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 12px;">
                        <i class="bi bi-cart-check text-info fs-5"></i>
                        <span style="font-size: 12px; font-weight:500;">Siparişler</span>
                    </a>
                </div>
                <div class="col-6">
                    <a href="<?= url('/admin/coupons') ?>" class="btn btn-secondary border-0 w-100 p-3 text-start d-flex flex-column gap-2" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 12px;">
                        <i class="bi bi-ticket-perforated text-success fs-5"></i>
                        <span style="font-size: 12px; font-weight:500;">Yeni Kupon</span>
                    </a>
                </div>
                <div class="col-6">
                    <a href="<?= url('/admin/banners') ?>" class="btn btn-secondary border-0 w-100 p-3 text-start d-flex flex-column gap-2" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 12px;">
                        <i class="bi bi-image text-danger fs-5"></i>
                        <span style="font-size: 12px; font-weight:500;">Banner</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 8. NOTIFICATION CENTER ALTYAPI -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-500 mb-3" style="font-size: 16px;">Bildirim Merkezi</h4>
            <div class="d-flex flex-column gap-3">
                <div class="d-flex gap-3 align-items-start p-2 rounded-3" style="background: rgba(255,255,255,0.01);">
                    <div class="p-2 rounded-circle bg-success text-dark"><i class="bi bi-bag-plus"></i></div>
                    <div>
                        <h6 class="m-0 text-white font-weight-600" style="font-size:13px;">Yeni Sipariş Alındı</h6>
                        <p class="text-muted m-0" style="font-size: 11px;">#SM-20260728-01 nolu sipariş tamamlandı.</p>
                    </div>
                </div>
                <div class="d-flex gap-3 align-items-start p-2 rounded-3" style="background: rgba(255,255,255,0.01);">
                    <div class="p-2 rounded-circle bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i></div>
                    <div>
                        <h6 class="m-0 text-white font-weight-600" style="font-size:13px;">Kritik Stok Uyarısı</h6>
                        <p class="text-muted m-0" style="font-size: 11px;">Kritik Stok Test Ürünü son 2 adet kaldı.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 10. MINI CALENDAR -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-500 mb-3" style="font-size: 16px;">Takvim</h4>
            <div class="p-3 rounded-3 text-center" style="background: rgba(255,255,255,0.01);">
                <div class="font-weight-600 text-uppercase mb-2" style="color: var(--sm-gold); font-size:14px;"><?= date('F Y') ?></div>
                <div class="d-grid gap-2" style="grid-template-columns: repeat(7, 1fr); font-size:12px;">
                    <span class="text-muted">Pt</span><span class="text-muted">Sa</span><span class="text-muted">Ça</span><span class="text-muted">Pe</span><span class="text-muted">Cu</span><span class="text-muted">Ct</span><span class="text-muted">Pz</span>
                    <?php
                    $todayNum = (int)date('d');
                    $daysInMonth = (int)date('t');
                    $firstDay = (int)date('N', strtotime(date('Y-m-01')));
                    
                    // Empty slots
                    for ($x = 1; $x < $firstDay; $x++) {
                        echo "<span></span>";
                    }
                    
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $isToday = ($day === $todayNum);
                        $style = $isToday ? "background: var(--sm-gold); color: #0f0c20; border-radius: 50%; font-weight:600;" : "";
                        echo "<span class='p-1 d-inline-flex justify-content-center align-items-center' style='width:24px; height:24px; {$style}'>{$day}</span>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- 7. RECENT MEMBERS -->
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-500 mb-3" style="font-size: 16px;">Son Kayıt Olan Üyeler</h4>
            <div class="d-flex flex-column gap-3">
                <?php if (!empty($recentMembers)): ?>
                    <?php foreach ($recentMembers as $m): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="m-0 text-white font-weight-500" style="font-size: 13px;"><?= htmlspecialchars($m['name']) ?></h6>
                                <span class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($m['email']) ?></span>
                            </div>
                            <span class="text-muted" style="font-size: 11px;"><?= date('d M', strtotime($m['created_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted m-0" style="font-size:12px;">Yeni kayıtlı üye bulunmamaktadır.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<!-- Chart JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    function handleFilterChange(select) {
        const customDiv = document.getElementById('customDateRange');
        if (select.value === 'custom') {
            customDiv.classList.remove('d-none');
        } else {
            customDiv.classList.add('d-none');
            // Redirect to apply filter instantly
            window.location.href = "?filter=" + select.value;
        }
    }

    function exportReport(format) {
        alert("Rapor " + format.toUpperCase() + " biçiminde dışa aktarılıyor...");
    }

    // Chart.js Setup
    document.addEventListener("DOMContentLoaded", () => {
        // Line Chart (Sales Performance)
        const ctxTrend = document.getElementById('salesTrendChart').getContext('2d');
        
        const labels = <?= json_encode($chartData['labels'] ?? []) ?>;
        const salesData = <?= json_encode($chartData['sales'] ?? []) ?>;
        const ordersData = <?= json_encode($chartData['orders'] ?? []) ?>;

        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: labels.length > 0 ? labels : ['1 M', '2 M', '3 M', '4 M'],
                datasets: [
                    {
                        label: 'Satış Cirosu (₺)',
                        data: salesData.length > 0 ? salesData : [0, 0, 0, 0],
                        borderColor: '#c5a880',
                        backgroundColor: 'rgba(197, 168, 128, 0.05)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3
                    },
                    {
                        label: 'Sipariş Sayısı',
                        data: ordersData.length > 0 ? ordersData : [0, 0, 0, 0],
                        borderColor: '#93c5fd',
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.3,
                        borderWidth: 2,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#94a3b8', font: { family: 'Outfit' } }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.03)' },
                        ticks: { color: '#94a3b8' }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.03)' },
                        ticks: { color: '#94a3b8' }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { color: '#93c5fd' }
                    }
                }
            }
        });

        // Category Share Chart (Doughnut)
        const ctxCat = document.getElementById('categorySalesChart').getContext('2d');
        
        <?php
        $catLabels = [];
        $catValues = [];
        foreach ($categorySales as $c) {
            $catLabels[] = $c['category_name'];
            $catValues[] = (float)$c['total_sales'];
        }
        ?>

        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($catLabels) ?>,
                datasets: [{
                    data: <?= json_encode($catValues) ?>,
                    backgroundColor: ['#c5a880', '#93c5fd', '#86efac', '#fef08a'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: '#94a3b8', font: { family: 'Outfit', size: 10 } }
                    }
                }
            }
        });
    });
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>
