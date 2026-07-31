<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'SaintMonarc Storefront') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --store-bg: #f8f9fa;
            --store-card: #ffffff;
            --store-navy: #0f172a;
            --store-accent: #c5a880;
            --store-border: rgba(0, 0, 0, 0.06);
            --store-text: #334155;
            --store-text-muted: #64748b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--store-bg);
            color: var(--store-text);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Sticky Header styling */
        .store-header {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--store-border);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .mega-menu {
            position: static;
        }

        .mega-menu-content {
            background: #ffffff;
            border: 1px solid var(--store-border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
            border-radius: 12px;
            padding: 24px;
        }

        /* Product Card styling */
        .product-card {
            background: var(--store-card);
            border: 1px solid var(--store-border);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
            border-color: var(--store-accent);
        }

        .product-image-wrapper {
            position: relative;
            background-color: #f1f5f9;
            padding-bottom: 100%;
            overflow: hidden;
        }

        .product-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .badge-ai {
            background: linear-gradient(135deg, #6366f1, #a855f7);
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 4px 8px;
            border-radius: 20px;
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 2;
        }

        .badge-discount {
            background: #ef4444;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 20px;
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 2;
        }

        .nav-link-premium {
            font-size: 14px;
            font-weight: 500;
            color: var(--store-navy);
            padding: 8px 12px;
            transition: color 0.2s ease;
        }

        .nav-link-premium:hover {
            color: var(--store-accent);
        }
    </style>
</head>
<body>

<!-- Modern Sticky Header -->
<header class="store-header py-3">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <!-- Logo -->
            <a href="<?= url('/') ?>" class="text-decoration-none d-flex align-items-center gap-2">
                <span class="fs-4 font-weight-800 text-dark" style="font-weight: 800; letter-spacing: -1px; color: var(--store-navy) !important;">SAINTMONARC</span>
                <span class="badge bg-dark rounded-pill fs-9" style="font-size: 9px;">PREMIUM</span>
            </a>

            <!-- Navigation Links -->
            <nav class="d-none d-lg-flex align-items-center gap-2">
                <a href="<?= url('/') ?>" class="nav-link-premium text-decoration-none">Ana Sayfa</a>
                <a href="<?= url('/products') ?>" class="nav-link-premium text-decoration-none">Ürünler</a>
                <a href="<?= url('/category/elektronik') ?>" class="nav-link-premium text-decoration-none">Elektronik</a>
                <a href="<?= url('/category/moda') ?>" class="nav-link-premium text-decoration-none">Moda</a>
                <a href="<?= url('/blog') ?>" class="nav-link-premium text-decoration-none">Blog</a>
            </nav>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-3">
                <!-- Search bar -->
                <form action="<?= url('/search') ?>" method="GET" class="d-none d-md-flex align-items-center bg-light px-3 py-2 rounded-pill border border-secondary border-opacity-10">
                    <input type="text" name="q" placeholder="Ürün, marka ara..." class="border-0 bg-transparent outline-none text-dark fs-7" style="font-size: 13px; width: 180px;">
                    <button type="submit" class="border-0 bg-transparent"><i class="bi bi-search text-muted"></i></button>
                </form>

                <a href="<?= url('/account') ?>" class="text-dark position-relative"><i class="bi bi-person fs-5"></i></a>
                <a href="<?= url('/cart') ?>" class="text-dark position-relative">
                    <i class="bi bi-bag fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 9px;">2</span>
                </a>
            </div>
        </div>
    </div>
</header>
