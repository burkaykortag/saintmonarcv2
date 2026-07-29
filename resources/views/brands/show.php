<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($brand['name']) ?> - SaintMonarc</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
            overflow-x: hidden;
        }
        header {
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            background: rgba(15,12,32,0.8);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 3px;
            background: linear-gradient(90deg, #c5a880, #e5d1b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
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
        .brand-banner {
            width: 100%;
            height: 300px;
            background-size: cover;
            background-position: center;
            position: relative;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .brand-banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(6,2,16,0.3) 0%, rgba(6,2,16,0.9) 100%);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            width: 100%;
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 30px;
        }
        .breadcrumb a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.3s;
        }
        .breadcrumb a:hover {
            color: #c5a880;
        }
        .brand-profile {
            display: flex;
            flex-direction: column;
            md-flex-direction: row;
            gap: 30px;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.05);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 50px;
            align-items: center;
        }
        @media(min-width: 768px) {
            .brand-profile {
                flex-direction: row;
                align-items: flex-start;
            }
        }
        .brand-logo-container {
            width: 120px;
            height: 120px;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        .brand-logo-container img {
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
        }
        .brand-info {
            flex-grow: 1;
        }
        .brand-info h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #ffffff;
        }
        .brand-info .website-link {
            color: #c5a880;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 15px;
            transition: color 0.3s;
        }
        .brand-info .website-link:hover {
            color: #e5d1b8;
        }
        .brand-info .slogan {
            font-size: 16px;
            color: #cbd5e1;
            font-weight: 500;
            margin-bottom: 10px;
        }
        .brand-info .description {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.6;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
        }
        .product-card {
            background: rgba(255,255,255,0.01);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s;
            text-align: center;
        }
        .product-card:hover {
            border-color: #c5a880;
            transform: translateY(-4px);
            background: rgba(255,255,255,0.03);
        }
        .product-img-box {
            height: 180px;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }
        .product-card h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #ffffff;
        }
        .product-card p {
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 15px;
            height: 36px;
            overflow: hidden;
        }
        .product-price {
            color: #c5a880;
            font-weight: 700;
            font-size: 18px;
        }
        footer {
            margin-top: auto;
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
        <a href="<?= url('/') ?>" class="logo">SAINTMONARC</a>
        <nav>
            <a href="<?= url('/login') ?>">Müşteri Girişi</a>
            <a href="<?= url('/admin') ?>">Yönetim Paneli</a>
        </nav>
    </header>

    <!-- Banner -->
    <?php if (!empty($brand['banner_path'])): ?>
        <div class="brand-banner" style="background-image: url('<?= url('/' . $brand['banner_path']) ?>');">
            <div class="brand-banner-overlay"></div>
        </div>
    <?php else: ?>
        <div class="brand-banner" style="background: linear-gradient(135deg, #1c0e35 0%, #060210 100%);">
            <div class="brand-banner-overlay"></div>
        </div>
    <?php endif; ?>

    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="<?= url('/') ?>">Ana Sayfa</a>
            <i class="bi bi-chevron-right text-muted" style="font-size: 10px;"></i>
            <span class="text-muted">Markalar</span>
            <i class="bi bi-chevron-right text-muted" style="font-size: 10px;"></i>
            <span class="text-white"><?= htmlspecialchars($brand['name']) ?></span>
        </div>

        <!-- Brand Profile Card -->
        <div class="brand-profile">
            <div class="brand-logo-container">
                <?php if (!empty($brand['logo_path'])): ?>
                    <img src="<?= url('/' . $brand['logo_path']) ?>" alt="<?= htmlspecialchars($brand['name']) ?>">
                <?php else: ?>
                    <i class="bi bi-award text-muted" style="font-size: 40px;"></i>
                <?php endif; ?>
            </div>
            
            <div class="brand-info">
                <h1><?= htmlspecialchars($brand['name']) ?></h1>
                <?php if (!empty($brand['website'])): ?>
                    <a href="<?= htmlspecialchars($brand['website']) ?>" target="_blank" class="website-link">
                        <i class="bi bi-globe"></i> <?= htmlspecialchars($brand['website']) ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($brand['short_description'])): ?>
                    <p class="slogan"><?= htmlspecialchars($brand['short_description']) ?></p>
                <?php endif; ?>
                <?php if (!empty($brand['description'])): ?>
                    <p class="description"><?= nl2br(htmlspecialchars($brand['description'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Brand Products Grid -->
        <h2 class="text-white font-weight-700 mb-4" style="font-size: 22px; letter-spacing: 0.5px;">Markaya Ait Ürünler</h2>
        
        <?php if (!empty($products)): ?>
            <div class="products-grid">
                <?php foreach ($products as $prod): ?>
                    <div class="product-card">
                        <div class="product-img-box">
                            <i class="bi bi-box-seam" style="font-size: 40px;"></i>
                        </div>
                        <h3><?= htmlspecialchars($prod['name']) ?></h3>
                        <p><?= htmlspecialchars($prod['short_description'] ?? '') ?></p>
                        <div class="product-price">₺<?= number_format((float)$prod['price'], 2) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 rounded-4" style="background: rgba(255,255,255,0.01); border: 1px dashed rgba(255,255,255,0.08);">
                <i class="bi bi-box-seam text-muted fs-1 mb-3 d-block"></i>
                <h5 class="text-white">Bu markaya ait ürün bulunmamaktadır.</h5>
                <p class="text-muted fs-7 mb-0">Ürün eklerken bu markayı atayarak listelenmesini sağlayabilirsiniz.</p>
            </div>
        <?php endif; ?>

    </div>

    <footer>
        &copy; <?= date('Y') ?> SaintMonarc. Tüm Hakları Saklıdır. v1.0.0
    </footer>
</body>
</html>
