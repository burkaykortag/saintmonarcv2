<?php
use App\Helpers\ComponentHelper;

$title = "Özellik Düzenle - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

// Prepare English translation
$enTranslation = '';
foreach ($attribute['translations'] ?? [] as $t) {
    if ((int)$t['language_id'] === 2) {
        $enTranslation = $t['name'];
        break;
    }
}
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Özellik Yönetimi' => url('/admin/attributes'),
        'Özellik Düzenle' => '#'
    ]) ?>
    <h2 class="mt-2 text-white font-weight-700 m-0" style="font-size: 26px;">Özellik Düzenle: <?= htmlspecialchars($attribute['name'] ?? '') ?></h2>
</div>

<div class="row">
    <div class="col-12 col-xl-8">
        <div class="p-4 rounded-4 mb-4" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
            <form action="<?= url('/admin/attributes/edit') ?>" method="POST">
                <input type="hidden" name="id" value="<?= $attribute['id'] ?>">

                <div class="mb-3">
                    <label class="form-label text-muted">Özellik Adı (TR)</label>
                    <input type="text" name="name" class="search-input w-100" required placeholder="Örn: Renk, Beden" value="<?= htmlspecialchars($attribute['name'] ?? '') ?>">
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Sistem Kodu</label>
                        <input type="text" name="code" class="search-input w-100" required placeholder="Örn: color, size" value="<?= htmlspecialchars($attribute['code'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Özellik Tipi</label>
                        <select name="type" id="typeSelector" class="form-select border-0 text-white fs-7" style="background: rgba(255,255,255,0.03); padding: 10px;" required>
                            <option value="text" <?= ($attribute['type'] ?? '') === 'text' ? 'selected' : '' ?>>Text (Metin Kutusu)</option>
                            <option value="textarea" <?= ($attribute['type'] ?? '') === 'textarea' ? 'selected' : '' ?>>Textarea (Detaylı Metin)</option>
                            <option value="number" <?= ($attribute['type'] ?? '') === 'number' ? 'selected' : '' ?>>Number (Tam Sayı)</option>
                            <option value="decimal" <?= ($attribute['type'] ?? '') === 'decimal' ? 'selected' : '' ?>>Decimal (Ondalıklı Sayı)</option>
                            <option value="color_picker" <?= ($attribute['type'] ?? '') === 'color_picker' ? 'selected' : '' ?>>Color Picker (Renk Seçici)</option>
                            <option value="image" <?= ($attribute['type'] ?? '') === 'image' ? 'selected' : '' ?>>Image (Görsel Seçici)</option>
                            <option value="date" <?= ($attribute['type'] ?? '') === 'date' ? 'selected' : '' ?>>Date (Tarih Seçici)</option>
                            <option value="checkbox" <?= ($attribute['type'] ?? '') === 'checkbox' ? 'selected' : '' ?>>Checkbox (Çoklu Seçim)</option>
                            <option value="radio" <?= ($attribute['type'] ?? '') === 'radio' ? 'selected' : '' ?>>Radio (Tekli Seçim)</option>
                            <option value="select" <?= ($attribute['type'] ?? '') === 'select' ? 'selected' : '' ?>>Select (Açılır Seçim)</option>
                            <option value="multi_select" <?= ($attribute['type'] ?? '') === 'multi_select' ? 'selected' : '' ?>>Multi Select (Çoklu Açılır Seçim)</option>
                            <option value="boolean" <?= ($attribute['type'] ?? '') === 'boolean' ? 'selected' : '' ?>>Boolean (Evet/Hayır)</option>
                            <option value="url" <?= ($attribute['type'] ?? '') === 'url' ? 'selected' : '' ?>>URL (Web Bağlantısı)</option>
                            <option value="file" <?= ($attribute['type'] ?? '') === 'file' ? 'selected' : '' ?>>File (Dosya Yükleme)</option>
                        </select>
                    </div>
                </div>

                <!-- Translations Panel -->
                <div class="mb-4">
                    <h5 class="text-white mt-4 font-weight-600">Dil Çevirileri</h5>
                    <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.01); border: 1px solid var(--sm-border);">
                        <div class="mb-3">
                            <label class="form-label text-muted">İngilizce İsim (EN)</label>
                            <input type="text" name="translations[2][name]" class="search-input w-100" placeholder="Örn: Color, Size" value="<?= htmlspecialchars($enTranslation) ?>">
                        </div>
                    </div>
                </div>

                <!-- Option Values Panel -->
                <div id="optionsPanel" class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-white font-weight-600 m-0">Özellik Seçenekleri (Değerler)</h5>
                        <button type="button" class="btn btn-sm btn-secondary border-0" id="addOptionBtn">
                            <i class="bi bi-plus-lg me-1"></i>Seçenek Ekle
                        </button>
                    </div>
                    
                    <div id="optionsContainer">
                        <?php 
                        $optionIndex = 0;
                        foreach ($attribute['values'] ?? [] as $val): 
                            $valEnTranslation = '';
                            foreach ($val['translations'] ?? [] as $vt) {
                                if ((int)$vt['language_id'] === 2) {
                                    $valEnTranslation = $vt['name'];
                                    break;
                                }
                            }
                        ?>
                            <div class="row g-2 mb-2 align-items-center option-row">
                                <input type="hidden" name="values[<?= $optionIndex ?>][id]" value="<?= $val['id'] ?>">
                                <div class="col-md-5">
                                    <input type="text" name="values[<?= $optionIndex ?>][name]" class="search-input w-100" placeholder="Seçenek Adı (Örn: Mavi)" required value="<?= htmlspecialchars($val['name'] ?? '') ?>">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="values[<?= $optionIndex ?>][code]" class="search-input w-100" placeholder="Sistem Kodu (Örn: blue)" value="<?= htmlspecialchars($val['code'] ?? '') ?>">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="values[<?= $optionIndex ?>][translations][2][name]" class="search-input w-100" placeholder="İngilizce (Örn: Blue)" value="<?= htmlspecialchars($valEnTranslation) ?>">
                                </div>
                                <div class="col-md-1 text-center">
                                    <button type="button" class="btn btn-sm btn-danger bg-opacity-10 border-0 remove-option-btn p-2">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </div>
                            </div>
                        <?php 
                            $optionIndex++;
                        endforeach; 
                        ?>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end mt-4">
                    <a href="<?= url('/admin/attributes') ?>" class="btn btn-secondary border-0">İptal</a>
                    <button type="submit" class="btn">Değişiklikleri Kaydet</button>
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

    let optionIndex = <?= $optionIndex ?>;
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

    // Bind remove event for pre-populated values
    document.querySelectorAll('.remove-option-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            btn.closest('.option-row').remove();
        });
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
