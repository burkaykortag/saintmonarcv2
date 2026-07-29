<?php
use App\Helpers\ComponentHelper;

$title = "Ürün Yönetimi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Ürünler' => url('/admin/products')]) ?>
        <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Ürün Yönetimi</h2>
        <p class="text-muted mb-0 fs-6">Katalog ürünlerini, stok miktarlarını, fiyatlandırmaları ve ürün durumlarını kapsamlı olarak yönetin.</p>
    </div>
    
    <div class="d-flex gap-2">
        <button class="btn btn-secondary border-0" type="button" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload me-2"></i> İçe Aktar
        </button>
        <div class="dropdown">
            <button class="btn btn-secondary border-0 dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download me-2"></i> Dışa Aktar
            </button>
            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="exportDropdown" style="background:#15102a; border: 1px solid var(--sm-border);">
                <li><a class="dropdown-item" href="<?= url('/admin/products/export?format=csv') ?>"><i class="bi bi-file-earmark-excel me-2"></i> CSV Formatı</a></li>
                <li><a class="dropdown-item" href="<?= url('/admin/products/export?format=excel') ?>"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Excel Formatı</a></li>
                <li><a class="dropdown-item" href="<?= url('/admin/products/export?format=xml') ?>"><i class="bi bi-filetype-xml me-2"></i> XML Sitemap</a></li>
            </ul>
        </div>
        <div class="dropdown">
            <button class="btn btn-secondary border-0 dropdown-toggle" type="button" id="columnToggleDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-grid-3x3-gap me-2"></i> Kolonlar
            </button>
            <ul class="dropdown-menu dropdown-menu-dark p-3" aria-labelledby="columnToggleDropdown" style="background:#15102a; border: 1px solid var(--sm-border); min-width: 200px;">
                <li><div class="form-check"><input class="form-check-input col-toggle" type="checkbox" checked data-col="1" id="col1"><label class="form-check-label text-white" for="col1">Resim</label></div></li>
                <li><div class="form-check"><input class="form-check-input col-toggle" type="checkbox" checked data-col="2" id="col2"><label class="form-check-label text-white" for="col2">Adı</label></div></li>
                <li><div class="form-check"><input class="form-check-input col-toggle" type="checkbox" checked data-col="3" id="col3"><label class="form-check-label text-white" for="col3">Kategori</label></div></li>
                <li><div class="form-check"><input class="form-check-input col-toggle" type="checkbox" checked data-col="4" id="col4"><label class="form-check-label text-white" for="col4">Marka</label></div></li>
                <li><div class="form-check"><input class="form-check-input col-toggle" type="checkbox" checked data-col="5" id="col5"><label class="form-check-label text-white" for="col5">SKU</label></div></li>
                <li><div class="form-check"><input class="form-check-input col-toggle" type="checkbox" checked data-col="6" id="col6"><label class="form-check-label text-white" for="col6">Stok</label></div></li>
                <li><div class="form-check"><input class="form-check-input col-toggle" type="checkbox" checked data-col="7" id="col7"><label class="form-check-label text-white" for="col7">Fiyat</label></div></li>
                <li><div class="form-check"><input class="form-check-input col-toggle" type="checkbox" checked data-col="8" id="col8"><label class="form-check-label text-white" for="col8">Durum</label></div></li>
            </ul>
        </div>
        <a href="<?= url('/admin/products/reports') ?>" class="btn btn-secondary border-0">
            <i class="bi bi-bar-chart me-2"></i> Raporlar
        </a>
        <a href="<?= url('/admin/products/create') ?>" class="btn">
            <i class="bi bi-plus-circle me-2"></i> Yeni Ürün Ekle
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

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= url('/admin/products/import') ?>" method="POST" enctype="multipart/form-data" class="modal-content text-white border-0" style="background: #15102a; border-radius: 20px;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="modal-header border-bottom border-secondary-subtle">
                <h5 class="modal-title">Ürün İçe Aktar (Excel, CSV, XML, JSON)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Dosya Seçin (Excel, CSV, XML, JSON)</label>
                    <input type="file" name="import_file" accept=".csv,.xls,.xlsx,.xml,.json" required class="form-control bg-dark border-secondary text-white">
                </div>
                <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info fs-7">
                    <strong>Gelişmiş İçe Aktarım:</strong> Dosyanızı yükledikten sonra kolonları veritabanı alanları ile eşleştirebileceğiniz eşleştirme ekranına yönlendirileceksiniz.
                </div>
            </div>
            <div class="modal-footer border-top border-secondary-subtle">
                <button type="button" class="btn btn-secondary border-0" data-bs-dismiss="modal">Kapat</button>
                <button type="submit" class="btn">Dosyayı Yükle ve Devam Et</button>
            </div>
        </form>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs border-bottom-0 mb-4 gap-2" id="productTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active text-white px-4 py-2 border-0 rounded-3" id="active-products-tab" data-bs-toggle="tab" data-bs-target="#active-products" type="button" role="tab" style="background: rgba(255,255,255,0.03);" onclick="switchTab('active')">
            <i class="bi bi-box me-2"></i> Aktif Ürünler (<?= count($products) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-white px-4 py-2 border-0 rounded-3" id="trash-products-tab" data-bs-toggle="tab" data-bs-target="#trash-products" type="button" role="tab" style="background: rgba(255,255,255,0.03);" onclick="switchTab('trash')">
            <i class="bi bi-trash me-2 text-danger"></i> Geri Dönüşüm Kutusu (<?= count($trash) ?>)
        </button>
    </li>
</ul>

<!-- Filters -->
<div class="p-3 rounded-4 mb-4" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
    <form method="GET" action="" class="row g-2">
        <div class="col-12 col-md-3">
            <div class="position-relative">
                <input type="text" name="q" id="instantSearchInput" class="search-input w-100" placeholder="Ürün adı, SKU, barkod ara..." value="<?= htmlspecialchars($q ?? '') ?>">
                <i class="bi bi-search position-absolute text-muted" style="right: 16px; top: 12px;"></i>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <select name="category_id" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" onchange="this.form.submit()">
                <option value="">Tüm Kategoriler</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="brand_id" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" onchange="this.form.submit()">
                <option value="">Tüm Markalar</option>
                <?php foreach ($brands as $br): ?>
                    <option value="<?= $br['id'] ?>" <?= $brand_id == $br['id'] ? 'selected' : '' ?>><?= htmlspecialchars($br['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="status" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" onchange="this.form.submit()">
                <option value="">Tüm Durumlar</option>
                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Yayında</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Taslak</option>
                <option value="passive" <?= $status === 'passive' ? 'selected' : '' ?>>Pasif</option>
                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Arşiv</option>
                <option value="coming_soon" <?= $status === 'coming_soon' ? 'selected' : '' ?>>Yakında</option>
                <option value="out_of_stock" <?= $status === 'out_of_stock' ? 'selected' : '' ?>>Stokta Yok</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <button type="submit" class="btn btn-secondary border-0 w-100 fs-7" style="padding: 10px 0;"><i class="bi bi-funnel me-2"></i>Filtrele</button>
        </div>
    </form>
</div>

<!-- Bulk Actions Bar -->
<div id="bulkActionBar" class="d-none p-3 rounded-4 mb-4 d-flex justify-content-between align-items-center" style="background: rgba(197, 168, 128, 0.1); border: 1px solid rgba(197, 168, 128, 0.3);">
    <div class="d-flex align-items-center gap-2">
        <span class="text-white font-weight-600 fs-7"><span id="selectedCount">0</span> ürün seçildi</span>
    </div>
    <div class="d-flex gap-2">
        <form action="<?= url('/admin/products/bulk') ?>" method="POST" id="bulkForm" class="d-flex align-items-center gap-3">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" id="bulkActionInput" value="">
            <div id="bulkIdsContainer"></div>

            <div id="bulkCategorySelector" class="d-none">
                <select name="target_category_id" class="form-select border-0 text-white bg-dark fs-7 py-1 px-2" style="width:180px;">
                    <option value="">Hedef Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="bulkBrandSelector" class="d-none">
                <select name="target_brand_id" class="form-select border-0 text-white bg-dark fs-7 py-1 px-2" style="width:180px;">
                    <option value="">Hedef Marka</option>
                    <?php foreach ($brands as $br): ?>
                        <option value="<?= $br['id'] ?>"><?= htmlspecialchars($br['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="bulkPriceInput" class="d-none">
                <input type="number" step="0.01" name="bulk_price" class="form-control text-white bg-dark fs-7 py-1 px-2" placeholder="Fiyat" style="width:110px;">
            </div>

            <div id="bulkStockInput" class="d-none">
                <input type="number" name="bulk_stock" class="form-control text-white bg-dark fs-7 py-1 px-2" placeholder="Stok" style="width:110px;">
            </div>

            <div class="dropdown">
                <button class="btn btn-secondary py-1 px-3 fs-7 dropdown-toggle" type="button" id="bulkActionDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    İşlem Seçin
                </button>
                <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="bulkActionDropdown">
                    <li><a class="dropdown-item cursor-pointer" onclick="prepareBulkAction('publish')">Toplu Yayınla</a></li>
                    <li><a class="dropdown-item cursor-pointer" onclick="prepareBulkAction('passive')">Toplu Pasif Yap</a></li>
                    <li><a class="dropdown-item cursor-pointer" onclick="prepareBulkAction('category')">Toplu Kategori Değiştir</a></li>
                    <li><a class="dropdown-item cursor-pointer" onclick="prepareBulkAction('brand')">Toplu Marka Değiştir</a></li>
                    <li><a class="dropdown-item cursor-pointer" onclick="prepareBulkAction('price')">Toplu Fiyat Güncelle</a></li>
                    <li><a class="dropdown-item cursor-pointer" onclick="prepareBulkAction('stock')">Toplu Stok Güncelle</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger cursor-pointer" onclick="prepareBulkAction('delete')">Toplu Sil</a></li>
                </ul>
            </div>
            
            <button type="button" id="bulkSubmitBtn" class="btn btn-primary py-1 px-3 fs-7 d-none" onclick="submitBulkAction()">Değişiklikleri Uygula</button>
        </form>
    </div>
</div>

<!-- Tab Content -->
<div class="tab-content" id="productTabsContent">
    
    <!-- Active Products Tab -->
    <div class="tab-pane fade show active" id="active-products" role="tabpanel" aria-labelledby="active-products-tab">
        <div class="card border-0 p-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <div class="table-responsive">
                <table id="productsTable" class="table table-dark table-hover border-0 m-0" style="background: transparent;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--sm-border); color: var(--sm-text-muted); font-size:13px;">
                            <th width="40"><input type="checkbox" class="selectAllCheckbox" onclick="toggleSelectAll(this)" style="accent-color:var(--sm-gold); transform:scale(1.1);"></th>
                            <th class="col-idx-1" width="80">Resim</th>
                            <th class="col-idx-2">Ürün Adı</th>
                            <th class="col-idx-3">Kategori</th>
                            <th class="col-idx-4">Marka</th>
                            <th class="col-idx-5">SKU</th>
                            <th class="col-idx-6">Stok</th>
                            <th class="col-idx-7">Satış Fiyatı</th>
                            <th class="col-idx-8">Durum</th>
                            <th>Tarih</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="productsTableBody">
                        <?php if (!empty($products)): ?>
                            <?php foreach ($products as $p): ?>
                                <tr class="align-middle border-bottom border-secondary-subtle">
                                    <td><input type="checkbox" class="row-checkbox" value="<?= $p['id'] ?>" onclick="handleCheckboxChange()" style="accent-color:var(--sm-gold); transform:scale(1.1);"></td>
                                    <td class="col-idx-1">
                                        <?php if (!empty($p['cover_path'])): ?>
                                            <img src="<?= url('/' . $p['cover_path']) ?>" class="img-fluid rounded-3" style="max-height: 40px; max-width: 60px; object-fit: contain;">
                                        <?php else: ?>
                                            <div class="rounded-3 border border-secondary d-flex align-items-center justify-content-center" style="width: 50px; height: 35px; background: rgba(0,0,0,0.2);">
                                                <i class="bi bi-box text-muted" style="font-size: 14px;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-idx-2 font-weight-600">
                                        <?= htmlspecialchars($p['name']) ?>
                                        <div class="text-muted fs-8 font-weight-400 mt-1"><?= htmlspecialchars($p['subtitle'] ?? '') ?></div>
                                    </td>
                                    <td class="col-idx-3 text-muted"><?= htmlspecialchars($p['category_name'] ?? 'Kategorisiz') ?></td>
                                    <td class="col-idx-4 text-muted"><?= htmlspecialchars($p['brand_name'] ?? 'Markasız') ?></td>
                                    <td class="col-idx-5"><code class="text-warning" style="font-size:12px;"><?= htmlspecialchars($p['sku']) ?></code></td>
                                    <td class="col-idx-6">
                                        <?php if (!empty($p['unlimited_stock'])): ?>
                                            <span class="badge bg-info bg-opacity-10 text-info">Sınırsız</span>
                                        <?php elseif ($p['total_stock'] <= $p['critical_stock']): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger"><?= $p['total_stock'] ?> (Kritik)</span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success"><?= $p['total_stock'] ?> Adet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-idx-7 font-weight-600"><?= htmlspecialchars($p['currency_code'] ?? 'TRY') ?> <?= number_format((float)$p['price'], 2) ?></td>
                                    <td class="col-idx-8">
                                        <?php if ($p['status'] === 'published'): ?>
                                            <span class="badge bg-success">Yayında</span>
                                        <?php elseif ($p['status'] === 'draft'): ?>
                                            <span class="badge bg-warning text-dark">Taslak</span>
                                        <?php elseif ($p['status'] === 'passive'): ?>
                                            <span class="badge bg-secondary">Pasif</span>
                                        <?php elseif ($p['status'] === 'archived'): ?>
                                            <span class="badge bg-danger">Arşiv</span>
                                        <?php elseif ($p['status'] === 'coming_soon'): ?>
                                            <span class="badge bg-info">Yakında</span>
                                        <?php else: ?>
                                            <span class="badge bg-dark"><?= htmlspecialchars($p['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted" style="font-size:12px;"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="<?= url('/admin/products/edit?id=' . $p['id']) ?>" class="btn btn-secondary py-1 px-2 fs-8"><i class="bi bi-pencil-square"></i> Düzenle</a>
                                            <form action="<?= url('/admin/products/duplicate') ?>" method="POST" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-secondary py-1 px-2 fs-8"><i class="bi bi-copy"></i> Kopyala</button>
                                            </form>
                                            <form action="<?= url('/admin/products/delete') ?>" method="POST" onsubmit="return confirm('Bu ürünü geri dönüşüm kutusuna göndermek istediğinize emin misiniz?');" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-danger py-1 px-2 fs-8"><i class="bi bi-trash"></i> Sil</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">Aktif ürün bulunamadı.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Trash / Recycle Bin Tab -->
    <div class="tab-pane fade" id="trash-products" role="tabpanel" aria-labelledby="trash-products-tab">
        <div class="card border-0 p-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <div class="table-responsive">
                <table class="table table-dark table-hover border-0 m-0" style="background: transparent;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--sm-border); color: var(--sm-text-muted); font-size:13px;">
                            <th width="40"><input type="checkbox" class="selectAllCheckbox" onclick="toggleSelectAll(this)" style="accent-color:var(--sm-gold); transform:scale(1.1);"></th>
                            <th width="80">Resim</th>
                            <th>Ürün Adı</th>
                            <th>SKU</th>
                            <th>Satış Fiyatı</th>
                            <th>Kayıt Tarihi</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="productsTableBody">
                        <?php if (!empty($trash)): ?>
                            <?php foreach ($trash as $p): ?>
                                <tr class="align-middle border-bottom border-secondary-subtle">
                                    <td><input type="checkbox" class="row-checkbox" value="<?= $p['id'] ?>" onclick="handleCheckboxChange()" style="accent-color:var(--sm-gold); transform:scale(1.1);"></td>
                                    <td>
                                        <?php if (!empty($p['cover_path'])): ?>
                                            <img src="<?= url('/' . $p['cover_path']) ?>" class="img-fluid rounded-3" style="max-height: 40px; max-width: 60px; object-fit: contain;">
                                        <?php else: ?>
                                            <div class="rounded-3 border border-secondary d-flex align-items-center justify-content-center" style="width: 50px; height: 35px; background: rgba(0,0,0,0.2);">
                                                <i class="bi bi-box text-muted" style="font-size: 14px;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-weight-600"><?= htmlspecialchars($p['name']) ?></td>
                                    <td><code class="text-warning" style="font-size:12px;"><?= htmlspecialchars($p['sku']) ?></code></td>
                                    <td class="font-weight-600">₺<?= number_format((float)$p['price'], 2) ?></td>
                                    <td class="text-muted" style="font-size:12px;"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <form action="<?= url('/admin/products/restore') ?>" method="POST" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-success py-1 px-2 fs-8"><i class="bi bi-arrow-counterclockwise"></i> Geri Yükle</button>
                                            </form>
                                            <form action="<?= url('/admin/products/force-delete') ?>" method="POST" onsubmit="return confirm('Bu ürünü kalıcı olarak silmek istediğinize emin misiniz? Bu işlem geri alınamaz!');" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-danger py-1 px-2 fs-8"><i class="bi bi-trash-fill"></i> Kalıcı Sil</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">Geri dönüşüm kutusu boş.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    let activeTabMode = 'active';

    function switchTab(mode) {
        activeTabMode = mode;
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.selectAllCheckbox').forEach(cb => cb.checked = false);
        handleCheckboxChange();
    }

    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('#' + activeTabMode + '-products .row-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
        handleCheckboxChange();
    }

    function handleCheckboxChange() {
        const checked = document.querySelectorAll('#' + activeTabMode + '-products .row-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionBar');
        const countSpan = document.getElementById('selectedCount');

        if (checked.length > 0) {
            bulkBar.classList.remove('d-none');
            countSpan.textContent = checked.length;
        } else {
            bulkBar.classList.add('d-none');
        }
    }

    function prepareBulkAction(action) {
        document.getElementById('bulkActionInput').value = action;
        
        document.getElementById('bulkCategorySelector').classList.add('d-none');
        document.getElementById('bulkBrandSelector').classList.add('d-none');
        document.getElementById('bulkPriceInput').classList.add('d-none');
        document.getElementById('bulkStockInput').classList.add('d-none');
        document.getElementById('bulkSubmitBtn').classList.remove('d-none');

        if (action === 'category') {
            document.getElementById('bulkCategorySelector').classList.remove('d-none');
        } else if (action === 'brand') {
            document.getElementById('bulkBrandSelector').classList.remove('d-none');
        } else if (action === 'price') {
            document.getElementById('bulkPriceInput').classList.remove('d-none');
        } else if (action === 'stock') {
            document.getElementById('bulkStockInput').classList.remove('d-none');
        } else {
            submitBulkAction();
        }
    }

    function submitBulkAction() {
        if (confirm('Toplu işlemi uygulamak istediğinize emin misiniz?')) {
            const checked = document.querySelectorAll('#' + activeTabMode + '-products .row-checkbox:checked');
            const container = document.getElementById('bulkIdsContainer');
            container.innerHTML = '';

            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'product_ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });

            document.getElementById('bulkForm').submit();
        }
    }

    // Column Show/Hide Logic
    document.querySelectorAll('.col-toggle').forEach(el => {
        el.addEventListener('change', function() {
            const colIdx = this.getAttribute('data-col');
            const targetCells = document.querySelectorAll('.col-idx-' + colIdx);
            targetCells.forEach(cell => {
                if (this.checked) {
                    cell.style.display = '';
                } else {
                    cell.style.display = 'none';
                }
            });
        });
    });

    // Instant Search Filter on Rows
    document.getElementById('instantSearchInput').addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        const rows = document.querySelectorAll('#' + activeTabMode + '-products tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(query)) {
                row.classList.remove('d-none');
            } else {
                row.classList.add('d-none');
            }
        });
    });
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
