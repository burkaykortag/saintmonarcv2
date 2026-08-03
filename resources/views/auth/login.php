<?php
$security = \Core\Application::getInstance()->getContainer()->get(\Core\Security::class);
$csrfToken = $security->generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - SaintMonarc</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #0f0c20 0%, #15102a 50%, #060210 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
        }
        .container {
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.8s ease-in-out;
        }
        .logo {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 2px;
            background: linear-gradient(90deg, #c5a880, #e5d1b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        .title {
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 24px;
            text-align: center;
            color: #e2e8f0;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
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
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #ffffff;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #c5a880;
            box-shadow: 0 0 10px rgba(197, 168, 128, 0.2);
            background: rgba(255, 255, 255, 0.05);
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(90deg, #c5a880, #b09168);
            border: none;
            border-radius: 12px;
            color: #0f0c20;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(197, 168, 128, 0.3);
            margin-top: 10px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(197, 168, 128, 0.5);
            background: linear-gradient(90deg, #e5d1b8, #c5a880);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.4;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #86efac;
        }
        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 13px;
        }
        .links a {
            color: #c5a880;
            text-decoration: none;
            transition: color 0.3s;
        }
        .links a:hover {
            color: #e5d1b8;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">SAINTMONARC</div>
            <div class="title">Giriş Yap</div>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="form-group">
                    <label for="email">E-posta Adresi</label>
                    <input type="email" id="email" name="email" required placeholder="örnek@mail.com">
                </div>
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn">Giriş Yap</button>
            </form>

            <div class="links">
                <a href="<?= url('/register') ?>">Kayıt Ol</a>
                <a href="<?= url('/password/forgot') ?>">Şifremi Unuttum</a>
            </div>
        </div>
    </div>
</body>
</html>
