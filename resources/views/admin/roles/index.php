<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rol Yönetimi - SaintMonarc</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }
        body {
            background: #0f0c20;
            color: #ffffff;
            min-height: 100vh;
            padding: 40px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        h1 {
            font-size: 26px;
            font-weight: 600;
            background: linear-gradient(90deg, #e5d1b8, #c5a880);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .btn {
            padding: 12px 24px;
            background: linear-gradient(90deg, #c5a880, #b09168);
            border: none;
            border-radius: 12px;
            color: #0f0c20;
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(197, 168, 128, 0.4);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            box-shadow: none;
        }
        .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .btn-danger:hover {
            background: #ef4444;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
        }
        .card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 28px;
            transition: all 0.3s ease;
            position: relative;
        }
        .card:hover {
            border-color: rgba(197, 168, 128, 0.3);
            background: rgba(255, 255, 255, 0.04);
            transform: translateY(-4px);
        }
        .role-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .role-desc {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 20px;
            line-height: 1.5;
            min-height: 42px;
        }
        .badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .badge-active {
            background: rgba(34, 197, 94, 0.1);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        .badge-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #86efac;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 100;
        }
        .modal-content {
            background: #15102a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            width: 100%;
            max-width: 400px;
        }
        .modal-content h3 {
            font-size: 18px;
            margin-bottom: 16px;
        }
        .modal-content input {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #ffffff;
            margin-bottom: 20px;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>Rol ve Yetki Yönetimi</h1>
                <p style="color: #94a3b8; font-size: 14px; margin-top: 4px;">Sistem rollerini oluşturun, çoğaltın ve yetki sınırlarını yapılandırın.</p>
            </div>
            <a href="<?= url('/admin/roles/create') ?>" class="btn">Yeni Rol Ekle</a>
        </header>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <?php
        $security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
        $csrfToken = $security->generateCsrfToken();
        ?>

        <div class="grid">
            <?php foreach ($roles as $role): ?>
                <div class="card">
                    <div class="role-name">
                        <div>
                            <span><?= htmlspecialchars($role['name']) ?></span>
                            <?php if (!empty($role['is_system'])): ?>
                                <span class="badge" style="background: rgba(212,175,55,0.15); color:#d4af37; border:1px solid rgba(212,175,55,0.3); margin-left: 6px;">Sistem Rolü</span>
                            <?php endif; ?>
                        </div>
                        <span class="badge <?= $role['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $role['is_active'] ? 'Aktif' : 'Pasif' ?>
                        </span>
                    </div>
                    <div class="role-desc">
                        <?= htmlspecialchars($role['description'] ?? '') ?>
                    </div>
                    <div style="font-size: 12px; color: #94a3b8; margin-bottom: 12px; display: flex; gap: 16px;">
                        <span>Üst Rol: <strong style="color: #e2e8f0;"><?= htmlspecialchars($role['parent_name'] ?? 'Kök Rol (Root)') ?></strong></span>
                        <span>Seviye (Priority): <strong style="color: #d4af37;"><?= (int)$role['priority'] ?></strong></span>
                        <span>Kullanıcı: <strong style="color: #e2e8f0;"><?= (int)($role['user_count'] ?? 0) ?></strong></span>
                    </div>
                    
                    <div class="actions">
                        <?php if (!empty($role['can_manage'])): ?>
                            <a href="<?= url('/admin/roles/edit?id=' . $role['id']) ?>" class="btn btn-secondary" style="padding: 8px 16px;">Düzenle</a>
                            <?php if (empty($role['is_system'])): ?>
                                <button class="btn btn-secondary" style="padding: 8px 16px;" onclick="openDuplicateModal(<?= $role['id'] ?>, '<?= htmlspecialchars($role['name']) ?>')">Kopyala</button>
                                <form action="<?= url('/admin/roles/delete') ?>" method="POST" style="display:inline;" onsubmit="return confirm('Bu rolü silmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="id" value="<?= $role['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 8px 16px;">Sil</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="font-size: 12px; color: #64748b; align-self: center;">🔒 Yetki Sınırı (Yönetilemez)</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Duplicate Modal -->
    <div id="duplicateModal" class="modal">
        <div class="modal-content">
            <h3>Rolü Kopyala</h3>
            <form action="<?= url('/admin/roles/duplicate') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" id="duplicate_id" name="id">
                <label style="font-size:12px; color:#94a3b8; display:block; margin-bottom:6px;">Yeni Rol Adı</label>
                <input type="text" id="new_role_name" name="name" required>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDuplicateModal()">Vazgeç</button>
                    <button type="submit" class="btn">Kopyasını Oluştur</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDuplicateModal(id, name) {
            document.getElementById('duplicate_id').value = id;
            document.getElementById('new_role_name').value = name + ' (Kopya)';
            document.getElementById('duplicateModal').style.display = 'flex';
        }
        function closeDuplicateModal() {
            document.getElementById('duplicateModal').style.display = 'none';
        }
    </script>
</body>
</html>
