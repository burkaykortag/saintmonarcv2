<?php
use App\Helpers\ComponentHelper;

$title = "Kampanya Ekle - SaintMonarc";
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
.rule-item {
    background: rgba(255,255,255,0.03);
    border: 1px dashed rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 12px;
}
</style>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb(['Yönetim Paneli' => url('/admin'), 'Kampanyalar' => url('/admin/promotions'), 'Yeni Kampanya' => '#']) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Yeni Kampanya Ekle</h2>
        <a href="<?= url('/admin/promotions') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Listeye Dön</a>
    </div>
</div>

<form action="<?= url('/admin/promotions/create') ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <div class="row g-4">
        <!-- SOL KOLON: KAMPANYA DETAYLARI VE KURAL OLUŞTURUCU -->
        <div class="col-lg-8">
            <!-- 1. Genel Bilgiler -->
            <div class="section-card text-white">
                <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-info-circle text-warning me-2"></i>Genel Bilgiler</h4>
                
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Kampanya Adı (Türkçe)</label>
                    <input type="text" name="name" required class="search-input w-100 text-white" placeholder="örn: Yaz Sezonu %20 Sepet İndirimi">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Kampanya Açıklaması</label>
                    <textarea name="description" class="search-input w-100 text-white" rows="2" placeholder="Sepette anında %20 indirim fırsatı!"></textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Kampanya Türü</label>
                        <select name="type" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                            <option value="percentage">Yüzdelik İndirim</option>
                            <option value="fixed_cart">Sepette Sabit İndirim</option>
                            <option value="fixed_product">Üründe Sabit İndirim</option>
                            <option value="free_shipping">Ücretsiz Kargo</option>
                            <option value="gift_product">Hediye Ürün</option>
                            <option value="happy_hour">Happy Hour</option>
                            <option value="flash_sale">Flash Sale</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Özel Kampanya Kodu (Boş bırakılırsa sepette otomatik uygulanır)</label>
                        <input type="text" name="code" class="search-input w-100 text-white" placeholder="örn: YAZ20">
                    </div>
                </div>
            </div>

            <!-- 2. Sürükle Bırak / IF-ELSE Rule Builder Simülasyonu -->
            <div class="section-card text-white">
                <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-code-slash text-warning me-2"></i>Dinamik Kural Oluşturucu (Rule Builder)</h4>
                
                <div class="rule-item">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-primary">IF (Eğer) Koşul Grubu</span>
                        <span class="text-muted fs-8">Mantıksal Operatör: AND (Ve)</span>
                    </div>
                    
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label text-muted fs-8 mb-1">Minimum Sepet Tutarı (TRY)</label>
                            <input type="number" step="0.01" name="rule_min_cart" class="search-input w-100 text-white py-2" placeholder="örn: 500">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fs-8 mb-1">Maksimum Sepet Tutarı (TRY)</label>
                            <input type="number" step="0.01" name="rule_max_cart" class="search-input w-100 text-white py-2" placeholder="örn: 10000">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label text-muted fs-8 mb-1">Minimum Toplam Ürün Adedi</label>
                            <input type="number" name="rule_min_items" class="search-input w-100 text-white py-2" placeholder="örn: 2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fs-8 mb-1">Geçerli Günler</label>
                            <div class="d-flex gap-2 flex-wrap mt-1 fs-8">
                                <label><input type="checkbox" name="rule_day_of_week[]" value="1" checked> Pzt</label>
                                <label><input type="checkbox" name="rule_day_of_week[]" value="2" checked> Sal</label>
                                <label><input type="checkbox" name="rule_day_of_week[]" value="3" checked> Çar</label>
                                <label><input type="checkbox" name="rule_day_of_week[]" value="4" checked> Per</label>
                                <label><input type="checkbox" name="rule_day_of_week[]" value="5" checked> Cum</label>
                                <label><input type="checkbox" name="rule_day_of_week[]" value="6" checked> Cmt</label>
                                <label><input type="checkbox" name="rule_day_of_week[]" value="0" checked> Paz</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rule-item">
                    <span class="badge bg-success">THEN (O Zaman) Uygulanacak İndirim Aksiyonu</span>
                    <div class="row g-2 mt-2">
                        <div class="col-md-4">
                            <label class="form-label text-muted fs-8 mb-1">Aksiyon Tipi</label>
                            <select name="action_type" class="form-select border-0 text-white fs-8 py-2" style="background: rgba(255,255,255,0.05); border: 1px solid var(--sm-border) !important;">
                                <option value="discount_percentage">Sepete Yüzdelik İndirim</option>
                                <option value="discount_fixed">Sepete Sabit İndirim</option>
                                <option value="free_shipping">Kargo Bedava</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted fs-8 mb-1">İndirim Oranı / Tutarı</label>
                            <input type="number" step="0.01" name="action_amount" class="search-input w-100 text-white py-2" placeholder="örn: 20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted fs-8 mb-1">Hedef Kapsamı</label>
                            <select name="action_target" class="form-select border-0 text-white fs-8 py-2" style="background: rgba(255,255,255,0.05); border: 1px solid var(--sm-border) !important;">
                                <option value="cart">Tüm Sepet</option>
                                <option value="product">Belirli Ürünler</option>
                                <option value="category">Belirli Kategoriler</option>
                                <option value="brand">Belirli Markalar</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Hediye Sistemi Eşleştirmesi -->
            <div class="section-card text-white">
                <h4 class="text-white font-weight-600 mb-3 fs-6"><i class="bi bi-gift text-warning me-2"></i>Hediye Modülü (Gifts Engine)</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 mb-1">Hediye Edilecek Ürün</label>
                        <select name="gift_product_id" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                            <option value="">Yok</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['sku']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">Hediye Adedi</label>
                        <input type="number" name="gift_qty" class="search-input w-100 text-white" value="1" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 mb-1">Hediye Puan Tutarı</label>
                        <input type="number" name="gift_points" class="search-input w-100 text-white" placeholder="Puan">
                    </div>
                </div>
            </div>
        </div>

        <!-- SAĞ KOLON: KAMPANYA AYARLARI, HEDEF KİTLE VE ZAMANLAMA -->
        <div class="col-lg-4">
            <!-- 1. Kampanya Durumu ve Öncelik -->
            <div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6">Zamanlama & Durum</h4>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Kampanya Durumu</label>
                    <select name="status" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px; border: 1px solid var(--sm-border) !important;">
                        <option value="draft">Taslak (Draft)</option>
                        <option value="active">Aktif (Active)</option>
                        <option value="passive">Pasif (Passive)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Kampanya Öncelik Derecesi</label>
                    <input type="number" name="priority" class="search-input w-100 text-white" value="0" placeholder="0">
                    <small class="text-muted fs-8">Yüksek rakam = Önce hesaplanır.</small>
                </div>
                <div class="form-check form-switch fs-7 mb-3 text-muted">
                    <input class="form-check-input" type="checkbox" name="is_exclusive" id="exclusiveCheck" value="1" checked>
                    <label class="form-check-label text-white" for="exclusiveCheck">Tek başına çalışsın (Diğer kampanyalarla birleşmez)</label>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Başlangıç Tarihi</label>
                    <input type="datetime-local" name="start_date" class="search-input w-100 text-white">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Bitiş Tarihi</label>
                    <input type="datetime-local" name="end_date" class="search-input w-100 text-white">
                </div>
            </div>

            <!-- 2. Kampanya Limitleri -->
            <div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6">Kullanım Sınırları</h4>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Toplam Kullanım Sınırı</label>
                    <input type="number" name="max_total_usage" class="search-input w-100 text-white" value="0">
                    <small class="text-muted fs-8">0: Sınırsız demektir.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Kullanıcı Başı Sınırı</label>
                    <input type="number" name="max_user_usage" class="search-input w-100 text-white" value="1">
                </div>
            </div>

            <!-- 3. Hedef Kitle Kısıtlamaları -->
            <div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <h4 class="text-white font-weight-600 mb-3 fs-6">Hedef Müşteri Grubu</h4>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Üye Grupları</label>
                    <div style="max-height: 150px; overflow-y: auto;" class="fs-7 text-muted">
                        <?php foreach ($groups as $g): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="customer_group_ids[]" value="<?= $g['id'] ?>" id="grp_<?= $g['id'] ?>">
                                <label class="form-check-label text-white" for="grp_<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">CRM Dinamik Segmentler</label>
                    <div style="max-height: 150px; overflow-y: auto;" class="fs-7 text-muted">
                        <?php foreach ($segments as $s): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="segment_ids[]" value="<?= $s['id'] ?>" id="seg_<?= $s['id'] ?>">
                                <label class="form-check-label text-white" for="seg_<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 4. Gönder -->
            <div class="card p-4 border-0 text-center" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
                <button type="submit" class="btn btn-warning text-dark border-0 fs-6 w-100 py-3 font-weight-700">Kampanyayı Kaydet</button>
                <a href="<?= url('/admin/promotions') ?>" class="btn btn-outline-secondary w-100 mt-2">Vazgeç</a>
            </div>
        </div>
    </div>
</form>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
