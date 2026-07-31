<?php
use App\Helpers\Ui;

$title = "Enterprise Design System & Component Showcase";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Yönetim Paneli</a></li>
            <li class="breadcrumb-item active" aria-current="page" style="color: var(--sm-gold);">Design System Showcase</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <div>
            <h2 class="text-white font-weight-800 m-0 fs-3">Enterprise Component Library</h2>
            <p class="text-muted mb-0 fs-7">SaintMonarc Premium UI Design System ve Ortak Bileşenler Kütüphanesi</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Theme Toggles -->
            <div class="btn-group btn-group-sm" style="border: 1px solid var(--sm-border); border-radius: 8px; overflow: hidden;">
                <button class="btn btn-secondary text-white border-0 py-2 px-3" onclick="ThemeEngine.setTheme('dark')"><i class="bi bi-moon-fill text-warning me-1"></i> Dark</button>
                <button class="btn btn-secondary text-white border-0 py-2 px-3" onclick="ThemeEngine.setTheme('light')"><i class="bi bi-sun-fill text-warning me-1"></i> Light</button>
                <button class="btn btn-secondary text-white border-0 py-2 px-3" onclick="ThemeEngine.setTheme('auto')"><i class="bi bi-laptop text-warning me-1"></i> Auto</button>
            </div>
            <span class="badge py-2 px-3 fs-8 bg-success bg-opacity-10 text-success border border-success border-opacity-25">Sürüm: v1.0 Enterprise</span>
        </div>
    </div>
    
    <!-- Keyboard Shortcut Quick Reference Alert -->
    <div class="alert bg-dark bg-opacity-50 border-0 mt-3 p-3 rounded-3 d-flex align-items-center justify-content-between" style="border: 1px solid var(--sm-border) !important;">
        <span class="fs-7 text-muted"><i class="bi bi-keyboard me-2 text-warning"></i><strong>Klavye Kısayolları Aktif:</strong> Ürünler için <code>N</code>, Siparişler için <code>O</code>, Medya için <code>M</code>, Design System için <code>C</code> tuşlarını kullanabilirsiniz.</span>
        <kbd class="bg-secondary text-white py-1 px-2 rounded fs-8">Ctrl + K Global Arama</kbd>
    </div>
</div>

<div class="row g-4">
    <!-- SOL MENÜ: BÖLÜMLER -->
    <div class="col-lg-3">
        <div class="card p-3 border-0 sticky-top" style="top: 100px; z-index: 10;">
            <div class="list-group list-group-flush">
                <a href="#section-buttons" class="list-group-item list-group-item-action text-white-50 border-0 bg-transparent py-2 fs-7 font-weight-600"><i class="bi bi-circle-fill text-warning me-2" style="font-size: 8px;"></i> Butonlar (Buttons)</a>
                <a href="#section-cards" class="list-group-item list-group-item-action text-white-50 border-0 bg-transparent py-2 fs-7 font-weight-600"><i class="bi bi-circle-fill text-warning me-2" style="font-size: 8px;"></i> Kartlar (Cards)</a>
                <a href="#section-datagrids" class="list-group-item list-group-item-action text-white-50 border-0 bg-transparent py-2 fs-7 font-weight-600"><i class="bi bi-circle-fill text-warning me-2" style="font-size: 8px;"></i> Veri Tabloları (DataGrids)</a>
                <a href="#section-forms" class="list-group-item list-group-item-action text-white-50 border-0 bg-transparent py-2 fs-7 font-weight-600"><i class="bi bi-circle-fill text-warning me-2" style="font-size: 8px;"></i> Form Elemanları (Forms)</a>
                <a href="#section-modals" class="list-group-item list-group-item-action text-white-50 border-0 bg-transparent py-2 fs-7 font-weight-600"><i class="bi bi-circle-fill text-warning me-2" style="font-size: 8px;"></i> Modal & Çekmece (Offcanvas)</a>
                <a href="#section-notifications" class="list-group-item list-group-item-action text-white-50 border-0 bg-transparent py-2 fs-7 font-weight-600"><i class="bi bi-circle-fill text-warning me-2" style="font-size: 8px;"></i> Bildirimler (Toasts)</a>
                <a href="#section-loaders" class="list-group-item list-group-item-action text-white-50 border-0 bg-transparent py-2 fs-7 font-weight-600"><i class="bi bi-circle-fill text-warning me-2" style="font-size: 8px;"></i> Yükleyiciler (Loaders)</a>
                <a href="#section-empty" class="list-group-item list-group-item-action text-white-50 border-0 bg-transparent py-2 fs-7 font-weight-600"><i class="bi bi-circle-fill text-warning me-2" style="font-size: 8px;"></i> Boş Durumlar (Empty States)</a>
            </div>
        </div>
    </div>

    <!-- SAĞ PANEL: GÖSTERİM ALANI -->
    <div class="col-lg-9 text-white">
        <!-- BUTONLAR -->
        <section id="section-buttons" class="mb-5">
            <h4 class="font-weight-700 text-white mb-3 fs-5"><i class="bi bi-check2-circle text-warning me-2"></i>Butonlar (Buttons)</h4>
            <div class="card p-4 border-0 mb-3">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?= Ui::button(['text' => 'Primary Button', 'type' => 'primary']) ?>
                    <?= Ui::button(['text' => 'Secondary Button', 'type' => 'secondary']) ?>
                    <?= Ui::button(['text' => 'Gold Button', 'type' => 'gold']) ?>
                    <?= Ui::button(['text' => 'Danger Button', 'type' => 'danger']) ?>
                    <?= Ui::button(['text' => 'Warning Button', 'type' => 'warning']) ?>
                    <?= Ui::button(['text' => 'Success Button', 'type' => 'success']) ?>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <?= Ui::button(['text' => 'Outline Button', 'type' => 'outline']) ?>
                    <?= Ui::button(['text' => 'Ghost Button', 'type' => 'ghost']) ?>
                    <?= Ui::button(['text' => 'Link Button', 'type' => 'link']) ?>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <?= Ui::button(['text' => 'Small Button', 'type' => 'gold', 'size' => 'small']) ?>
                    <?= Ui::button(['text' => 'Medium Button', 'type' => 'gold', 'size' => 'medium']) ?>
                    <?= Ui::button(['text' => 'Large Button', 'type' => 'gold', 'size' => 'large']) ?>
                    <?= Ui::button(['text' => 'Loading Button', 'type' => 'primary', 'loading' => true]) ?>
                    <?= Ui::button(['text' => 'Disabled Button', 'type' => 'primary', 'disabled' => true]) ?>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <?= Ui::button(['text' => 'Türkçe İşlem Butonu', 'type' => 'gold', 'icon' => 'check-circle']) ?>
                </div>
            </div>
        </section>

        <!-- KARTLAR -->
        <section id="section-cards" class="mb-5">
            <h4 class="font-weight-700 text-white mb-3 fs-5"><i class="bi bi-check2-circle text-warning me-2"></i>Kartlar (Cards)</h4>
            <div class="row g-3">
                <div class="col-md-4">
                    <?= Ui::card(['title' => 'Günlük Ciro', 'value' => '₺45,290.50', 'icon' => 'banknote', 'color' => 'var(--sm-gold)']) ?>
                </div>
                <div class="col-md-4">
                    <?= Ui::card(['title' => 'Yeni Üyeler', 'value' => '256 Üye', 'icon' => 'users', 'color' => 'var(--sm-info)']) ?>
                </div>
                <div class="col-md-4">
                    <?= Ui::card(['title' => 'Başarılı Teslimat', 'value' => '98.4%', 'icon' => 'truck', 'color' => 'var(--sm-success)']) ?>
                </div>
            </div>
        </section>

        <!-- DATA GRIDS -->
        <section id="section-datagrids" class="mb-5">
            <h4 class="font-weight-700 text-white mb-3 fs-5"><i class="bi bi-check2-circle text-warning me-2"></i>Veri Tabloları (DataGrids)</h4>
            <div class="card p-4 border-0">
                <?= Ui::datagrid([
                    'headers' => ['Kullanıcı Adı', 'E-Posta Adresi', 'Rol', 'Kayıt Tarihi'],
                    'rows' => [
                        ['Ahmet Demir', 'ahmet@saintmonarc.com', 'Süper Admin', '2026-07-01'],
                        ['Buse Kaya', 'buse@saintmonarc.com', 'Editör', '2026-07-15'],
                        ['Caner Yurt', 'caner@saintmonarc.com', 'Müşteri Temsilcisi', '2026-07-28']
                    ],
                    'bulk_actions' => [
                        'Toplu Sil' => '#',
                        'Toplu Aktive Et' => '#'
                    ],
                    'pagination' => '
                        <span class="text-muted fs-7">Toplam 3 kayıt listeleniyor.</span>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-secondary"><i class="bi bi-chevron-left"></i></button>
                            <button class="btn btn-sm btn-secondary active">1</button>
                            <button class="btn btn-sm btn-secondary"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    '
                ]) ?>
            </div>
        </section>

        <!-- FORM ELEMANLARI -->
        <section id="section-forms" class="mb-5">
            <h4 class="font-weight-700 text-white mb-3 fs-5"><i class="bi bi-check2-circle text-warning me-2"></i>Form Elemanları (Forms)</h4>
            <div class="card p-4 border-0">
                <div class="row">
                    <div class="col-md-6">
                        <?= Ui::input(['name' => 'sample_text', 'label' => 'Metin Alanı (Text Input)', 'placeholder' => 'Metin giriniz...']) ?>
                    </div>
                    <div class="col-md-6">
                        <?= Ui::input(['name' => 'sample_password', 'label' => 'Parola Alanı (Password)', 'type' => 'password', 'placeholder' => 'Şifrenizi giriniz...']) ?>
                    </div>
                    <div class="col-md-12">
                        <?= Ui::select([
                            'name' => 'sample_select',
                            'label' => 'Seçim Kutusu (Select Dropdown)',
                            'options' => [
                                'tr' => 'Türkçe (TR)',
                                'en' => 'English (EN)',
                                'de' => 'Deutsch (DE)'
                            ],
                            'selected' => 'tr'
                        ]) ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- MODAL & DRAWERS -->
        <section id="section-modals" class="mb-5">
            <h4 class="font-weight-700 text-white mb-3 fs-5"><i class="bi bi-check2-circle text-warning me-2"></i>Modaller & Çekmeceler (Drawers)</h4>
            <div class="card p-4 border-0 mb-3">
                <div class="d-flex gap-2">
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#showcaseModal">Modali Tetikle</button>
                    <button class="btn btn-secondary" data-bs-toggle="offcanvas" data-bs-target="#showcaseDrawer">Çekmeceyi Tetikle</button>
                </div>
            </div>

            <!-- Modal Render -->
            <?= Ui::modal([
                'id' => 'showcaseModal',
                'title' => 'Sistem Bildirim Detayı',
                'body' => 'Bu, SaintMonarc Design System modal bileşeninin önizleme içeriğidir. Başarıyla entegre edilmiştir.',
                'footer' => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button><button type="button" class="btn btn-warning">Onayla</button>'
            ]) ?>

            <!-- Drawer Render -->
            <?= Ui::drawer([
                'id' => 'showcaseDrawer',
                'title' => 'Kullanıcı İşlem Detayları',
                'body' => 'Bu çekmece (Offcanvas) bileşeni, detaylı işlem hareketlerini listelemek için mükemmel bir alternatiftir.',
                'direction' => 'right'
            ]) ?>
        </section>

        <!-- BİLDİRİMLER -->
        <section id="section-notifications" class="mb-5">
            <h4 class="font-weight-700 text-white mb-3 fs-5"><i class="bi bi-check2-circle text-warning me-2"></i>Anlık Bildirimler (Toasts)</h4>
            <div class="card p-4 border-0 mb-3">
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm" onclick="showToast('İşlem başarıyla tamamlandı.', 'success')">Success Toast</button>
                    <button class="btn btn-warning btn-sm text-dark" onclick="showToast('Girdiğiniz bilgileri kontrol edin.', 'warning')">Warning Toast</button>
                    <button class="btn btn-danger btn-sm" onclick="showToast('Sunucu hatası meydana geldi.', 'danger')">Danger Toast</button>
                </div>
            </div>

            <!-- Toast Container -->
            <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055;">
                <div id="liveToast" class="toast align-items-center text-white bg-dark border-0 rounded-3 shadow" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body fs-7" id="toastMessage">
                            Toast mesajı.
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            </div>

            <script>
                function showToast(message, type) {
                    const toastEl = document.getElementById('liveToast');
                    const messageEl = document.getElementById('toastMessage');
                    messageEl.textContent = message;
                    
                    toastEl.classList.remove('bg-success', 'bg-warning', 'bg-danger', 'bg-info');
                    if (type === 'success') toastEl.classList.add('bg-success');
                    if (type === 'warning') toastEl.classList.add('bg-warning');
                    if (type === 'danger') toastEl.classList.add('bg-danger');

                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                }
            </script>
        </section>

        <!-- LOADERS -->
        <section id="section-loaders" class="mb-5">
            <h4 class="font-weight-700 text-white mb-3 fs-5"><i class="bi bi-check2-circle text-warning me-2"></i>Yükleyiciler (Loaders)</h4>
            <div class="row g-3">
                <div class="col-md-6">
                    <p class="text-muted fs-7">Kart Skeleton Yükleme Önizleme</p>
                    <?= Ui::loader(['type' => 'card']) ?>
                </div>
                <div class="col-md-6">
                    <p class="text-muted fs-7">Tablo Skeleton Yükleme Önizleme</p>
                    <?= Ui::loader(['type' => 'table']) ?>
                </div>
            </div>
        </section>

        <!-- BOŞ DURUMLAR -->
        <section id="section-empty" class="mb-5">
            <h4 class="font-weight-700 text-white mb-3 fs-5"><i class="bi bi-check2-circle text-warning me-2"></i>Boş Durumlar (Empty States)</h4>
            <?= Ui::emptyState(['message' => 'Herhangi Bir Sipariş Talebi Bulunmuyor', 'sub_message' => 'Lütfen yeni sipariş taleplerinin gelmesini bekleyin veya filtreleri sıfırlayın.']) ?>
        </section>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
