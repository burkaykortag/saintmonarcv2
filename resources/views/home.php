<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaintMonarc - Premium E-Commerce Framework</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #060210 0%, #0f0c20 50%, #060210 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-x: hidden;
        }
        header {
            padding: 30px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 3px;
            background: linear-gradient(90deg, #c5a880, #e5d1b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        nav a {
            color: #94a3b8;
            text-decoration: none;
            margin-left: 30px;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s;
        }
        nav a:hover {
            color: #c5a880;
        }
        .hero {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            padding: 100px 20px;
        }
        .badge {
            background: rgba(197, 168, 128, 0.1);
            border: 1px solid rgba(197, 168, 128, 0.2);
            color: #c5a880;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 24px;
        }
        h1 {
            font-size: 54px;
            font-weight: 700;
            line-height: 1.15;
            margin-bottom: 24px;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            font-size: 18px;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .btn {
            padding: 16px 36px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(90deg, #c5a880, #b09168);
            color: #0f0c20;
            box-shadow: 0 4px 20px rgba(197, 168, 128, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(197, 168, 128, 0.5);
            background: linear-gradient(90deg, #e5d1b8, #c5a880);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }
        footer {
            padding: 40px;
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.05);
            font-size: 13px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">SAINTMONARC</div>
        <nav>
            <a href="<?= url('/login') ?>">Müşteri Girişi</a>
            <a href="<?= url('/admin') ?>">Yönetim Paneli</a>
        </nav>
    </header>

    <div class="hero">
        <span class="badge">SaintMonarc E-Commerce Platform</span>
        <h1>Lüks ve Hızın Buluştuğu Kurumsal Mimari</h1>
        <p>Gelişmiş modüler yapısı, güvenli oturum denetimleri ve modern yönetim paneli ile SaintMonarc e-ticaret altyapısını keşfedin.</p>
        <div class="btn-group">
            <a href="<?= url('/login') ?>" class="btn btn-primary">Platforma Giriş Yap</a>
            <a href="<?= url('/admin') ?>" class="btn btn-secondary">Yönetim Paneli</a>
        </div>
    </div>

    <footer>
        &copy; <?= date('Y') ?> SaintMonarc. Tüm Hakları Saklıdır. v1.0.0
    </footer>
</body>
</html>
