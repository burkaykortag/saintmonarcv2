<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Girişi - SaintMonarc</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }
        body {
            background: radial-gradient(circle at center, #1b1633 0%, #0d0a1b 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .card {
            background: rgba(18, 14, 36, 0.65);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(197, 168, 128, 0.15);
            border-radius: 28px;
            padding: 45px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            text-align: center;
        }
        .badge {
            background: rgba(197, 168, 128, 0.1);
            border: 1px solid rgba(197, 168, 128, 0.3);
            color: #c5a880;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 3px;
            background: linear-gradient(90deg, #e5d1b8, #c5a880);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 22px;
            text-align: left;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 8px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            color: #ffffff;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #c5a880;
            box-shadow: 0 0 12px rgba(197, 168, 128, 0.25);
            background: rgba(255, 255, 255, 0.06);
        }
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(90deg, #c5a880, #9f8258);
            border: none;
            border-radius: 14px;
            color: #0f0c20;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(197, 168, 128, 0.25);
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(197, 168, 128, 0.45);
            background: linear-gradient(90deg, #e5d1b8, #c5a880);
        }
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 24px;
            text-align: left;
            line-height: 1.4;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <span class="badge">Control Panel</span>
            <div class="logo">SAINTMONARC</div>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı</label>
                    <input type="text" id="username" name="username" required placeholder="admin">
                </div>
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn">Giriş Yetkilendir</button>
            </form>
        </div>
    </div>
</body>
</html>
