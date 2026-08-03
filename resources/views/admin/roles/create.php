<?php
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Rol Ekle - SaintMonarc</title>
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
            max-width: 600px;
            margin: 0 auto;
        }
        header {
            margin-bottom: 30px;
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
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 14px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            color: #ffffff;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .form-group textarea {
            height: 100px;
            resize: none;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #c5a880;
            box-shadow: 0 0 10px rgba(197, 168, 128, 0.2);
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
            text-align: center;
            text-decoration: none;
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
            <h1>Yeni Rol Oluştur</h1>
        </header>

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="form-group">
                    <label for="name">Rol Adı (Kodlama adı, örn: product_manager)</label>
                    <input type="text" id="name" name="name" required placeholder="product_manager">
                </div>
                <div class="form-group">
                    <label for="description">Açıklama</label>
                    <textarea id="description" name="description" placeholder="Bu rol ürün ve stok yönetimini denetler."></textarea>
                </div>
                <div class="form-group">
                    <label for="priority">Öncelik Derecesi (Sayısal değer, örn: 80)</label>
                    <input type="number" id="priority" name="priority" required value="0" min="0">
                </div>
                
                <div class="btn-row">
                    <button type="submit" class="btn">Kaydet</button>
                    <a href="<?= url('/admin/roles') ?>" class="btn btn-secondary">Vazgeç</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
