<?php
use App\Helpers\ComponentHelper;

$title = "Sipariş Durumları Yönetimi - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Siparişler' => url('/admin/orders'),
        'Sipariş Durumları' => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Sipariş Durumları Yönetimi</h2>
        <a href="<?= url('/admin/orders') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Sipariş Listesi</a>
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

<div class="row g-4">
    <!-- SOL TARAF: DURUM LİSTESİ -->
    <div class="col-lg-8">
        <div class="card p-4 border-0" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6">Kayıtlı Sipariş Durumları</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-hover">
                    <thead>
                        <tr class="text-muted fs-7 border-bottom border-secondary">
                            <th>Kod</th>
                            <th>Durum Adı</th>
                            <th>Renk</th>
                            <th>İkon</th>
                            <th>Sıralama</th>
                            <th width="150" class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        <?php foreach ($statuses as $st): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($st['code']) ?></code></td>
                                <td>
                                    <span class="badge" style="background: <?= $st['color'] ?>22; color: <?= $st['color'] ?>; border: 1px solid <?= $st['color'] ?>44; padding: 6px 12px; border-radius: 20px;">
                                        <i class="bi <?= $st['icon'] ?> me-1"></i><?= htmlspecialchars($st['name']) ?>
                                    </span>
                                </td>
                                <td><span style="display:inline-block; width:20px; height:20px; background:<?= $st['color'] ?>; border-radius:4px; vertical-align:middle; margin-right:5px;"></span> <?= $st['color'] ?></td>
                                <td><i class="bi <?= $st['icon'] ?> fs-5"></i> <small class="text-muted">(<?= $st['icon'] ?>)</small></td>
                                <td><?= $st['sort_order'] ?></td>
                                <td class="text-end">
                                    <?php if ($st['is_system']): ?>
                                        <span class="badge bg-secondary text-dark fs-8 p-1">Sistem Durumu</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-dark" onclick="editStatus(<?= htmlspecialchars(json_encode($st)) ?>)"><i class="bi bi-pencil"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteStatus('<?= $st['code'] ?>')"><i class="bi bi-trash"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SAĞ TARAF: YENİ EKLE / DÜZENLE FORMU -->
    <div class="col-lg-4">
        <!-- YENİ EKLE PANELİ -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;" id="createBox">
            <h4 class="text-white font-weight-600 mb-3 fs-6">Yeni Durum Ekle</h4>
            <form action="<?= url('/admin/orders/statuses/create') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Durum Kodu (Benzersiz, Küçük Harf)</label>
                    <input type="text" name="code" required class="search-input w-100 text-white" placeholder="örn: custom_status">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Durum Adı (Türkçe)</label>
                    <input type="text" name="name" required class="search-input w-100 text-white" placeholder="örn: Özel Hazırlıkta">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Renk Seçimi (Hex)</label>
                    <div class="d-flex gap-2">
                        <input type="color" name="color" value="#c5a880" class="form-control form-control-color bg-transparent border-secondary" style="width: 50px; height: 38px;">
                        <input type="text" id="colorHex" class="search-input w-100 text-white" value="#c5a880" placeholder="#ffffff">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Bootstrap İkon Sınıfı</label>
                    <input type="text" name="icon" class="search-input w-100 text-white" value="bi-circle" placeholder="bi-truck">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Sıralama (Sort Order)</label>
                    <input type="number" name="sort_order" class="search-input w-100 text-white" value="0">
                </div>
                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 w-100 py-2 font-weight-700">Durumu Ekle</button>
            </form>
        </div>

        <!-- DÜZENLEME PANELİ (Başlangıçta Gizli) -->
        <div class="card p-4 border-0 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px; display:none;" id="editBox">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="text-white font-weight-600 m-0 fs-6">Durumu Düzenle</h4>
                <button type="button" class="btn-close btn-close-white btn-sm" onclick="cancelEdit()"></button>
            </div>
            <form action="<?= url('/admin/orders/statuses/update') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="code" id="editCode">
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Durum Kodu</label>
                    <input type="text" id="editCodeDisplay" disabled class="search-input w-100 text-muted">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Durum Adı</label>
                    <input type="text" name="name" id="editName" required class="search-input w-100 text-white">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Renk Seçimi</label>
                    <div class="d-flex gap-2">
                        <input type="color" name="color" id="editColor" class="form-control form-control-color bg-transparent border-secondary" style="width: 50px; height: 38px;">
                        <input type="text" id="editColorHex" class="search-input w-100 text-white">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Bootstrap İkon Sınıfı</label>
                    <input type="text" name="icon" id="editIcon" class="search-input w-100 text-white">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Sıralama (Sort Order)</label>
                    <input type="number" name="sort_order" id="editSortOrder" class="search-input w-100 text-white">
                </div>
                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 w-100 py-2 font-weight-700">Değişiklikleri Kaydet</button>
                <button type="button" class="btn btn-outline-secondary w-100 mt-2 fs-7" onclick="cancelEdit()">Vazgeç</button>
            </form>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;" action="<?= url('/admin/orders/statuses/delete') ?>">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="code" id="deleteCode">
</form>

<script>
// Renk inputları ile hex alanlarını bağlayalım
document.querySelector('#createBox input[type="color"]').addEventListener('input', function() {
    document.getElementById('colorHex').value = this.value;
});
document.getElementById('colorHex').addEventListener('input', function() {
    document.querySelector('#createBox input[type="color"]').value = this.value;
});

document.getElementById('editColor').addEventListener('input', function() {
    document.getElementById('editColorHex').value = this.value;
});
document.getElementById('editColorHex').addEventListener('input', function() {
    document.getElementById('editColor').value = this.value;
});

function editStatus(st) {
    document.getElementById('createBox').style.display = 'none';
    document.getElementById('editBox').style.display = 'block';

    document.getElementById('editCode').value = st.code;
    document.getElementById('editCodeDisplay').value = st.code;
    document.getElementById('editName').value = st.name;
    document.getElementById('editColor').value = st.color;
    document.getElementById('editColorHex').value = st.color;
    document.getElementById('editIcon').value = st.icon;
    document.getElementById('editSortOrder').value = st.sort_order;
}

function cancelEdit() {
    document.getElementById('editBox').style.display = 'none';
    document.getElementById('createBox').style.display = 'block';
}

function deleteStatus(code) {
    if (confirm('Bu sipariş durumunu silmek istediğinize emin misiniz?')) {
        document.getElementById('deleteCode').value = code;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
