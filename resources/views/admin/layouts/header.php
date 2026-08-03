<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$adminId = $_SESSION['admin_id'] ?? null;
$adminUsername = $_SESSION['admin_username'] ?? 'Yönetici';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'SaintMonarc Kontrol Paneli') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Enterprise Design System CSS Files -->
    <link rel="stylesheet" href="/SaintMonarc/public/css/tokens.css">
    <link rel="stylesheet" href="/SaintMonarc/public/css/components.css">
    <link rel="stylesheet" href="/SaintMonarc/public/css/layout.css">
    <!-- Enterprise PIM V2 Design System -->
    <link rel="stylesheet" href="/SaintMonarc/public/css/pim.css">
    <!-- Premium Dark/Gold Design System -->
    <link rel="stylesheet" href="/SaintMonarc/public/css/saintmonarc-dark-gold.css">
    <style>
        :root {
            --sm-dark: #14131B;
            --sm-darker: #08070D;
            --sm-dark-card: #181720;
            --sm-gold: #D4AF37;
            --sm-gold-hover: #E5C766;
            --sm-border: rgba(212, 175, 55, 0.18);
            --sm-text-muted: #AAA7B2;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--sm-darker);
            color: #ffffff;
            overflow-x: hidden;
        }

        #sidebar-wrapper {
            min-height: 100vh;
            max-height: 100vh;
            overflow-y: auto;
            width: 260px;
            background-color: var(--sm-dark);
            border-right: 1px solid var(--sm-border);
            transition: margin 0.25s ease-out;
            position: fixed;
            z-index: 1000;
        }
        #sidebar-wrapper::-webkit-scrollbar {
            width: 6px;
        }
        #sidebar-wrapper::-webkit-scrollbar-track {
            background: transparent;
        }
        #sidebar-wrapper::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 3px;
        }
        #sidebar-wrapper::-webkit-scrollbar-thumb:hover {
            background: var(--sm-gold);
        }

        #sidebar-wrapper .sidebar-heading {
            padding: 24px;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 2px;
            background: linear-gradient(90deg, var(--sm-gold), var(--sm-gold-hover));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            border-bottom: 1px solid var(--sm-border);
        }

        #sidebar-wrapper .list-group-item {
            background-color: transparent;
            color: #94a3b8;
            border: none;
            padding: 14px 24px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #sidebar-wrapper .list-group-item:hover,
        #sidebar-wrapper .list-group-item.active {
            color: var(--sm-gold);
            background-color: rgba(255, 255, 255, 0.02);
            padding-left: 28px;
        }

        #sidebar-wrapper .list-group-item i {
            font-size: 18px;
        }

        .menu-category {
            padding: 18px 24px 8px 24px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--sm-text-muted);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: color 0.2s ease;
        }

        .menu-category:hover {
            color: var(--sm-gold);
        }

        .menu-category .bi-chevron-down {
            transition: transform 0.2s ease;
            font-size: 10px;
        }

        .menu-category[aria-expanded="false"] .bi-chevron-down {
            transform: rotate(-90deg);
        }

        #page-content-wrapper {
            margin-left: 260px;
            min-width: 0;
            width: calc(100% - 260px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar-custom {
            background-color: var(--sm-dark);
            border-bottom: 1px solid var(--sm-border);
            padding: 16px 30px;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .search-input {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--sm-border);
            border-radius: 10px;
            padding: 10px 16px;
            color: #ffffff;
            font-size: 14px;
            width: 280px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--sm-gold);
            box-shadow: 0 0 10px rgba(197, 168, 128, 0.2);
            background: rgba(255, 255, 255, 0.05);
            width: 320px;
        }

        .nav-icon {
            color: #94a3b8;
            font-size: 20px;
            padding: 8px;
            border-radius: 10px;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }

        .nav-icon:hover {
            color: var(--sm-gold);
            background: rgba(255, 255, 255, 0.02);
        }

        .nav-icon .badge-dot {
            width: 8px;
            height: 8px;
            background-color: var(--sm-gold);
            border-radius: 50%;
            position: absolute;
            top: 8px;
            right: 8px;
        }

        .profile-dropdown {
            background-color: var(--sm-dark) !important;
            border: 1px solid var(--sm-border) !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
            margin-top: 10px !important;
        }

        .profile-dropdown .dropdown-item {
            color: #94a3b8;
            padding: 10px 20px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .profile-dropdown .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.02);
            color: var(--sm-gold);
        }

        #menu-toggle {
            display: none;
        }

        @media (max-width: 992px) {
            #sidebar-wrapper {
                margin-left: -260px;
            }
            #page-content-wrapper {
                margin-left: 0;
                width: 100%;
            }
            #sidebar-wrapper.toggled {
                margin-left: 0;
            }
            #menu-toggle {
                display: block;
            }
        }

        /* Enterprise Design System Form Element Visibility Fixes */
        .form-label, label {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        .text-muted {
            color: var(--sm-text-muted, #9ca3af) !important;
        }
        h4.text-white, h5.text-white, h6.text-white {
            color: #ffffff !important;
        }
        .search-input {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.04) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        .search-input:focus {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
            border-color: var(--sm-gold, #c5a880) !important;
            box-shadow: 0 0 10px rgba(197, 168, 128, 0.2) !important;
        }
        .w-100.search-input:focus {
            width: 100% !important;
        }
        select.form-select {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.04) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        select.form-select option {
            background-color: #141125 !important;
            color: #ffffff !important;
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-custom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-secondary border-0" id="menu-toggle" style="background: rgba(255,255,255,0.03); border: 1px solid var(--sm-border) !important; color: var(--sm-gold);">
                    <i class="bi bi-list"></i>
                </button>
                <div class="d-none d-md-flex align-items-center position-relative">
                    <input type="text" class="search-input" placeholder="Arama yapın...">
                    <i class="bi bi-search position-absolute text-muted" style="right: 16px; top: 12px;"></i>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="nav-icon">
                    <i class="bi bi-bell"></i>
                    <span class="badge-dot"></span>
                </div>
                <div class="nav-icon">
                    <i class="bi bi-chat-left-text"></i>
                </div>
                
                <div class="dropdown">
                    <a class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle text-white" href="#" role="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle d-flex justify-content-center align-items-center text-dark" style="width: 36px; height: 36px; background-color: var(--sm-gold); font-size:14px; font-weight: 600;">
                            <?= strtoupper(substr($adminUsername, 0, 2)) ?>
                        </div>
                        <span class="d-none d-md-inline" style="font-size:14px; font-weight:500;"><?= htmlspecialchars($adminUsername) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end profile-dropdown" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="<?= url('/admin/profile') ?>"><i class="bi bi-person me-2"></i> Profilim</a></li>
                        <li><a class="dropdown-item" href="<?= url('/admin/settings') ?>"><i class="bi bi-gear me-2"></i> Ayarlar</a></li>
                        <li><hr class="dropdown-divider" style="border-color: var(--sm-border);"></li>
                        <li><a class="dropdown-item text-danger" href="<?= url('/admin/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i> Çıkış Yap</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <div class="container-fluid p-5">
