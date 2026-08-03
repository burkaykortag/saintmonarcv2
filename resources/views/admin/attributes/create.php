<?php
use App\Helpers\ComponentHelper;

$title = "Özellik Ekle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Özellik Yönetimi' => url('/admin/attributes'),
        'Yeni Özellik Ekle' => '#'
    ]) ?>
    <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Yeni Özellik Ekle</h2>
</div>

<div class="row">
    <div class="col-12 col-xl-8">
        <div class="p-4 rounded-4 mb-4" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
            <form action="<?= url('/admin/attributes/create') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="mb-3">
                    <label class="form-label text-muted">Özellik Adı (TR)</label>
                    <input type="text" name="name" class="search-input w-100" required placeholder="Örn: Renk, Beden, Materyal">
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Sistem Kodu</label>
                        <input type="text" name="code" class="search-input w-100" required placeholder="Örn: color, size, material">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Özellik Tipi</label>
                        <select name="type" id="typeSelector" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" required>
                            <option value="text">Text (Metin Kutusu)</option>
                            <option value="textarea">Textarea (Detaylı Metin)</option>
                            <option value="number">Number (Tam Sayı)</option>
                            <option value="decimal">Decimal (Ondalıklı Sayı)</option>
                            <option value="color_picker">Color Picker (Renk Seçici)</option>
                            <option value="image">Image (Görsel Seçici)</option>
                            <option value="date">Date (Tarih Seçici)</option>
                            <option value="checkbox">Checkbox (Çoklu Seçim)</option>
                            <option value="radio">Radio (Tekli Seçim)</option>
                            <option value="select">Select (Açılır Seçim)</option>
                            <option value="multi_select">Multi Select (Çoklu Açılır Seçim)</option>
                            <option value="boolean">Boolean (Evet/Hayır)</option>
                            <option value="url">URL (Web Bağlantısı)</option>
                            <option value="file">File (Dosya Yükleme)</option>
                        </select>
                    </div>
                </div>

                <!-- Multilingual Translations Panel -->
                <div class="mb-4">
                    <h5 class="text-white mt-4 font-weight-600">Dil Çevirileri</h5>
                    <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
                        <div class="mb-3">
                            <label class="form-label text-muted">İngilizce İsim (EN)</label>
                            <input type="text" name="translations[2][name]" class="search-input w-100" placeholder="Örn: Color, Size">
                        </div>
                    </div>
                </div>

                <!-- Option Values Panel (Shows if Selectable type is selected) -->
                <div id="optionsPanel" class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-white font-weight-600 m-0">Özellik Seçenekleri (Değerler)</h5>
                        <button type="button" class="btn btn-sm btn-secondary border-0" id="addOptionBtn">
                            <i class="bi bi-plus-lg me-1"></i>Seçenek Ekle
                        </button>
                    </div>
                    
                    <div id="optionsContainer">
                        <!-- Dynamic Options will be appended here -->
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end mt-4">
                    <a href="<?= url('/admin/attributes') ?>" class="btn btn-secondary border-0">İptal</a>
                    <button type="submit" class="btn">Özelliği Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelector = document.getElementById('typeSelector');
    const optionsPanel = document.getElementById('optionsPanel');
    const addOptionBtn = document.getElementById('addOptionBtn');
    const optionsContainer = document.getElementById('optionsContainer');

    function toggleOptionsPanel() {
        const type = typeSelector.value;
        const selectableTypes = ['select', 'multi_select', 'checkbox', 'radio', 'color_picker'];
        if (selectableTypes.includes(type)) {
            optionsPanel.style.display = 'block';
        } else {
            optionsPanel.style.display = 'none';
        }
    }

    typeSelector.addEventListener('change', toggleOptionsPanel);
    toggleOptionsPanel();

    let optionIndex = 0;
    function addOptionRow() {
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 align-items-center option-row';
        row.innerHTML = `
            <div class="col-md-5">
                <input type="text" name="values[${optionIndex}][name]" class="search-input w-100" placeholder="Seçenek Adı (Örn: Mavi)" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="values[${optionIndex}][code]" class="search-input w-100" placeholder="Sistem Kodu (Örn: blue)">
            </div>
            <div class="col-md-3">
                <input type="text" name="values[${optionIndex}][translations][2][name]" class="search-input w-100" placeholder="İngilizce (Örn: Blue)">
            </div>
            <div class="col-md-1 text-center">
                <button type="button" class="btn btn-sm btn-danger bg-opacity-10 border-0 remove-option-btn p-2">
                    <i class="bi bi-trash text-danger"></i>
                </button>
            </div>
        `;
        optionsContainer.appendChild(row);

        row.querySelector('.remove-option-btn').addEventListener('click', function() {
            row.remove();
        });

        optionIndex++;
    }

    addOptionBtn.addEventListener('click', addOptionRow);
    
    // Add first row as default
    addOptionRow();
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
