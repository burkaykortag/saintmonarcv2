<?php
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rol Yetkilerini Düzenle - SaintMonarc</title>
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
            max-width: 1000px;
            margin: 0 auto;
        }
        header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 {
            font-size: 24px;
            font-weight: 600;
            background: linear-gradient(90deg, #e5d1b8, #c5a880);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 40px;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 14px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            color: #ffffff;
            font-size: 15px;
        }
        .search-box {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #ffffff;
            font-size: 14px;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }
        .search-box:focus {
            outline: none;
            border-color: #c5a880;
            box-shadow: 0 0 10px rgba(197, 168, 128, 0.2);
        }
        .control-panel {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }
        .category-group {
            background: rgba(255, 255, 255, 0.01);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .category-title {
            font-size: 16px;
            font-weight: 600;
            color: #c5a880;
            margin-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .category-select-all {
            font-size: 12px;
            color: #94a3b8;
            cursor: pointer;
            text-decoration: none;
        }
        .category-select-all:hover {
            color: #ffffff;
        }
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }
        .permission-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: rgba(255, 255, 255, 0.01);
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            transition: all 0.2s ease;
        }
        .permission-item:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        .permission-item input {
            margin-top: 3px;
            cursor: pointer;
            accent-color: #c5a880;
        }
        .permission-item label {
            font-size: 13px;
            line-height: 1.4;
            color: #e2e8f0;
            cursor: pointer;
        }
        .permission-item label span {
            display: block;
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .btn-row {
            display: flex;
            gap: 16px;
            margin-top: 30px;
        }
        .btn {
            padding: 14px 28px;
            background: linear-gradient(90deg, #c5a880, #b09168);
            border: none;
            border-radius: 12px;
            color: #0f0c20;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
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
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Rolü ve Yetkileri Düzenle: <?= htmlspecialchars($role['name']) ?></h1>
        </header>

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="id" value="<?= $role['id'] ?>">
                
                <div class="form-group">
                    <label for="name">Rol Adı</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($role['name']) ?>">
                </div>

                <div class="form-group">
                    <label for="description">Açıklama</label>
                    <input type="text" id="description" name="description" value="<?= htmlspecialchars($role['description'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="priority">Öncelik Seviyesi</label>
                    <input type="number" id="priority" name="priority" required value="<?= $role['priority'] ?>">
                </div>

                <h2 style="font-size: 18px; font-weight: 500; margin-bottom: 12px; margin-top: 40px; color: #e2e8f0;">Modül Yetkilendirmeleri</h2>
                <input type="text" class="search-box" id="searchPerms" placeholder="Yetki ara... (örn: sipariş, sil, vb.)" oninput="filterPermissions()">

                <div class="control-panel">
                    <button type="button" class="btn btn-secondary" style="padding: 8px 16px; font-size:12px;" onclick="toggleAllCheckboxes(true)">Tümünü Seç</button>
                    <button type="button" class="btn btn-secondary" style="padding: 8px 16px; font-size:12px;" onclick="toggleAllCheckboxes(false)">Tümünü Kaldır</button>
                </div>

                <!-- Grouped Permissions -->
                <?php
                $groups = [
                    'Dashboard' => [],
                    'Ürünler' => [],
                    'Siparişler' => [],
                    'Finans' => [],
                    'Kullanıcılar' => [],
                    'Sistem & Diğer Modüller' => []
                ];

                foreach ($permissions as $perm) {
                    $name = $perm['name'];
                    if (str_contains($name, 'dashboard')) {
                        $groups['Dashboard'][] = $perm;
                    } elseif (str_contains($name, 'product')) {
                        $groups['Ürünler'][] = $perm;
                    } elseif (str_contains($name, 'order')) {
                        $groups['Siparişler'][] = $perm;
                    } elseif (str_contains($name, 'finance')) {
                        $groups['Finans'][] = $perm;
                    } elseif (str_contains($name, 'user')) {
                        $groups['Kullanıcılar'][] = $perm;
                    } else {
                        $groups['Sistem & Diğer Modüller'][] = $perm;
                    }
                }

                foreach ($groups as $groupName => $groupPerms):
                    if (empty($groupPerms)) continue;
                ?>
                    <div class="category-group" id="group-<?= md5($groupName) ?>">
                        <div class="category-title">
                            <span><?= $groupName ?></span>
                            <span class="category-select-all" onclick="toggleGroupCheckboxes('group-<?= md5($groupName) ?>')">Grup Seç/Bırak</span>
                        </div>
                        <div class="permissions-grid">
                            <?php foreach ($groupPerms as $perm): ?>
                                <div class="permission-item" data-name="<?= htmlspecialchars(strtolower($perm['name'] . ' ' . $perm['description'])) ?>">
                                    <input type="checkbox" id="perm-<?= $perm['id'] ?>" name="permissions[]" value="<?= $perm['id'] ?>" <?= in_array($perm['id'], $assignedIds) ? 'checked' : '' ?>>
                                    <label for="perm-<?= $perm['id'] ?>">
                                        <?= htmlspecialchars($perm['name']) ?>
                                        <span><?= htmlspecialchars($perm['description'] ?? '') ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="btn-row">
                    <button type="submit" class="btn">Yetkileri Kaydet</button>
                    <a href="<?= url('/admin/roles') ?>" class="btn btn-secondary">Vazgeç</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleAllCheckboxes(checked) {
            const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
            checkboxes.forEach(cb => cb.checked = checked);
        }

        function toggleGroupCheckboxes(groupId) {
            const group = document.getElementById(groupId);
            const checkboxes = group.querySelectorAll('input[type="checkbox"]');
            let anyUnchecked = Array.from(checkboxes).some(cb => !cb.checked);
            checkboxes.forEach(cb => cb.checked = anyUnchecked);
        }

        function filterPermissions() {
            const query = document.getElementById('searchPerms').value.toLowerCase();
            const items = document.querySelectorAll('.permission-item');
            
            items.forEach(item => {
                const text = item.getAttribute('data-name');
                if (text.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });

            const groups = document.querySelectorAll('.category-group');
            groups.forEach(group => {
                const visibleItems = group.querySelectorAll('.permission-item[style="display: flex;"]');
                const defaultItems = group.querySelectorAll('.permission-item:not([style*="display: none"])');
                if (visibleItems.length === 0 && defaultItems.length === 0) {
                    group.style.display = 'none';
                } else {
                    group.style.display = 'block';
                }
            });
        }
    </script>
</body>
</html>
