<?php
use App\Helpers\ComponentHelper;

$title = "Müşteri Grupları - SaintMonarc";
include dirname(__DIR__) . '/layouts/header.php';

$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>

<div class="mb-4">
    <?= ComponentHelper::breadcrumb([
        'Yönetim Paneli' => url('/admin'),
        'Müşteriler' => url('/admin/customers'),
        'Müşteri Grupları' => '#'
    ]) ?>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="text-white font-weight-700 m-0" style="font-size: 26px;">Müşteri Grupları Yönetimi</h2>
        <a href="<?= url('/admin/customers') ?>" class="btn btn-secondary border-0"><i class="bi bi-arrow-left me-2"></i>Müşteri Listesi</a>
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
    <!-- SOL TARAF: GRUP LİSTESİ -->
    <div class="col-lg-8">
        <div class="card p-4 border-0 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;">
            <h4 class="text-white font-weight-600 mb-3 fs-6">Tanımlı Müşteri Grupları</h4>
            <div class="table-responsive">
                <table class="table align-middle text-white table-hover">
                    <thead>
                        <tr class="text-muted fs-7 border-bottom border-secondary">
                            <th>ID</th>
                            <th>Grup Adı</th>
                            <th>İndirim Oranı</th>
                            <th width="150" class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        <?php foreach ($groups as $g): ?>
                            <tr>
                                <td><code>#<?= $g['id'] ?></code></td>
                                <td><strong><?= htmlspecialchars($g['name']) ?></strong></td>
                                <td class="text-warning"><strong>%<?= $g['discount_rate'] ?></strong></td>
                                <td class="text-end">
                                    <?php if ($g['id'] <= 1): ?>
                                        <span class="badge bg-secondary text-dark fs-8 p-1">Sistem Grubu</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-dark" onclick="editGroup(<?= htmlspecialchars(json_encode($g)) ?>)"><i class="bi bi-pencil"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteGroup(<?= $g['id'] ?>)"><i class="bi bi-trash"></i></button>
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
        <!-- EKLEME FORMU -->
        <div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px;" id="createBox">
            <h4 class="text-white font-weight-600 mb-3 fs-6">Yeni Grup Ekle</h4>
            <form action="<?= url('/admin/customers/groups/create') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Grup Adı (Türkçe)</label>
                    <input type="text" name="name" required class="search-input w-100 text-white" placeholder="örn: Toptan Alıcı">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Grup Özel İndirim Oranı (%)</label>
                    <input type="number" step="0.01" name="discount_rate" required class="search-input w-100 text-white" value="0.00" min="0" max="100">
                </div>
                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 w-100 py-2 font-weight-700">Grubu Ekle</button>
            </form>
        </div>

        <!-- DÜZENLEME FORMU (Başlangıçta Gizli) -->
        <div class="card p-4 border-0 mb-4 text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--sm-border) !important; border-radius: 20px; display:none;" id="editBox">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="text-white font-weight-600 m-0 fs-6">Grubu Düzenle</h4>
                <button type="button" class="btn-close btn-close-white btn-sm" onclick="cancelEdit()"></button>
            </div>
            <form action="<?= url('/admin/customers/groups/update') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="id" id="editId">
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Grup Adı</label>
                    <input type="text" name="name" id="editName" required class="search-input w-100 text-white">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 mb-1">Grup Özel İndirim Oranı (%)</label>
                    <input type="number" step="0.01" name="discount_rate" id="editRate" required class="search-input w-100 text-white" min="0" max="100">
                </div>
                <button type="submit" class="btn btn-warning text-dark border-0 fs-7 w-100 py-2 font-weight-700">Değişiklikleri Kaydet</button>
                <button type="button" class="btn btn-outline-secondary w-100 mt-2 fs-7" onclick="cancelEdit()">Vazgeç</button>
            </form>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;" action="<?= url('/admin/customers/groups/delete') ?>">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function editGroup(g) {
    document.getElementById('createBox').style.display = 'none';
    document.getElementById('editBox').style.display = 'block';

    document.getElementById('editId').value = g.id;
    document.getElementById('editName').value = g.name;
    document.getElementById('editRate').value = g.discount_rate;
}

function cancelEdit() {
    document.getElementById('editBox').style.display = 'none';
    document.getElementById('createBox').style.display = 'block';
}

function deleteGroup(id) {
    if (confirm('Bu müşteri grubunu silmek istediğinize emin misiniz?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
