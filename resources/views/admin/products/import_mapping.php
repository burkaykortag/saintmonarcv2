<?php
use App\Helpers\ComponentHelper;

$title = "Ürün İçe Aktarım Kolon Eşleştirme - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Katalog' => '#', 'Ürünler' => url('/admin/products'), 'Eşleştirme' => '#']) ?>
    <h2 class="text-white font-weight-700 mt-2" style="font-size: 26px;">Kolon Eşleştirme Ekranı</h2>
    <p class="text-muted fs-6">Yüklediğiniz dosyadaki sütunlar ile veritabanındaki ürün alanlarını eşleştirin.</p>
</div>

<div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
    <form action="<?= url('/admin/products/import/process') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        
        <div class="table-responsive rounded-3 mb-4">
            <table class="table table-hover align-middle mb-0 text-white">
                <thead class="text-muted" style="background: rgba(255,255,255,0.01);">
                    <tr>
                        <th style="width: 40%;">Ürün Veritabanı Alanı</th>
                        <th style="width: 60%;">Dosya Sütunu / Alanı</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="text-white font-weight-600">Ürün Adı <span class="text-danger">*</span></span></td>
                        <td>
                            <select name="mapping[name]" required class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                                <option value="">Seçiniz...</option>
                                <?php foreach ($headers as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= str_contains(strtolower($val), 'ad') || str_contains(strtolower($val), 'name') ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="text-white font-weight-600">Stok Kodu (SKU) <span class="text-danger">*</span></span></td>
                        <td>
                            <select name="mapping[sku]" required class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                                <option value="">Seçiniz...</option>
                                <?php foreach ($headers as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= str_contains(strtolower($val), 'sku') || str_contains(strtolower($val), 'kod') ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="text-white">Barkod</span></td>
                        <td>
                            <select name="mapping[barcode]" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                                <option value="">Eşleştirme Yok</option>
                                <?php foreach ($headers as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= str_contains(strtolower($val), 'barkod') || str_contains(strtolower($val), 'barcode') ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="text-white font-weight-600">Satış Fiyatı <span class="text-danger">*</span></span></td>
                        <td>
                            <select name="mapping[price]" required class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                                <option value="">Seçiniz...</option>
                                <?php foreach ($headers as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= str_contains(strtolower($val), 'fiyat') || str_contains(strtolower($val), 'price') ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="text-white">Alış Fiyatı (Maliyet)</span></td>
                        <td>
                            <select name="mapping[cost_price]" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                                <option value="">Eşleştirme Yok</option>
                                <?php foreach ($headers as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= str_contains(strtolower($val), 'maliyet') || str_contains(strtolower($val), 'cost') || str_contains(strtolower($val), 'alis') ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="text-white font-weight-600">Stok Adedi <span class="text-danger">*</span></span></td>
                        <td>
                            <select name="mapping[total_stock]" required class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                                <option value="">Seçiniz...</option>
                                <?php foreach ($headers as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= str_contains(strtolower($val), 'stok') || str_contains(strtolower($val), 'stock') || str_contains(strtolower($val), 'adet') ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="text-white">Durum</span></td>
                        <td>
                            <select name="mapping[status]" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                                <option value="">Eşleştirme Yok (Varsayılan: Taslak)</option>
                                <?php foreach ($headers as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= str_contains(strtolower($val), 'durum') || str_contains(strtolower($val), 'status') ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('/admin/products') ?>" class="btn btn-secondary border-0">İptal Et</a>
            <button type="submit" class="btn">Aktarımı Tamamla</button>
        </div>
    </form>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
