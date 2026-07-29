<?php
$rbac = \Core\Application::getInstance()->getContainer()->get(\App\Services\RbacService::class);
$adminId = $_SESSION['admin_id'] ?? null;
?>
<div id="sidebar-wrapper">
    <div class="sidebar-heading">SAINTMONARC</div>
    <div class="list-group list-group-flush">
        <a href="<?= url('/admin') ?>" class="list-group-item active">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <!-- Katalog Bölümü -->
        <?php if (
            $rbac->adminHasPermission((int)$adminId, 'view_products') || 
            $rbac->adminHasPermission((int)$adminId, 'view_brands') ||
            $rbac->adminHasPermission((int)$adminId, 'view_media') ||
            $rbac->adminHasPermission((int)$adminId, 'view_categories')
        ): ?>
            <div class="menu-category">Katalog</div>
            <?php if ($rbac->adminHasPermission((int)$adminId, 'view_products')): ?>
                <a href="<?= url('/admin/products') ?>" class="list-group-item">
                    <i class="bi bi-box-seam"></i> Ürünler
                </a>
            <?php endif; ?>
            <?php if ($rbac->adminHasPermission((int)$adminId, 'view_categories')): ?>
                <a href="<?= url('/admin/categories') ?>" class="list-group-item">
                    <i class="bi bi-tags"></i> Kategoriler
                </a>
            <?php endif; ?>
            <?php if ($rbac->adminHasPermission((int)$adminId, 'view_brands')): ?>
                <a href="<?= url('/admin/brands') ?>" class="list-group-item">
                    <i class="bi bi-award"></i> Markalar
                </a>
            <?php endif; ?>
            <?php if ($rbac->adminHasPermission((int)$adminId, 'view_media')): ?>
                <a href="<?= url('/admin/media') ?>" class="list-group-item">
                    <i class="bi bi-images"></i> Medya Kütüphanesi
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Satış & Müşteri -->
        <?php if (
            $rbac->adminHasPermission((int)$adminId, 'view_orders') || 
            $rbac->adminHasPermission((int)$adminId, 'view_users')
        ): ?>
            <div class="menu-category">Satış & Müşteri</div>
            <?php if ($rbac->adminHasPermission((int)$adminId, 'view_orders')): ?>
                <a href="<?= url('/admin/orders') ?>" class="list-group-item">
                    <i class="bi bi-cart3"></i> Sipariş Yönetimi
                </a>
            <?php endif; ?>
            <?php if ($rbac->adminHasPermission((int)$adminId, 'view_users')): ?>
                <a href="<?= url('/admin/customers') ?>" class="list-group-item">
                    <i class="bi bi-people"></i> Müşteriler
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Finans & Raporlar -->
        <?php if ($rbac->adminHasPermission((int)$adminId, 'view_finance')): ?>
            <div class="menu-category">Finans</div>
            <a href="<?= url('/admin/finance') ?>" class="list-group-item">
                <i class="bi bi-wallet2"></i> Finans Yönetimi
            </a>
            <a href="<?= url('/admin/reports') ?>" class="list-group-item">
                <i class="bi bi-graph-up-arrow"></i> Raporlar
            </a>
        <?php endif; ?>

        <!-- Pazarlama & CMS -->
        <?php if (
            $rbac->adminHasPermission((int)$adminId, 'manage_coupons') || 
            $rbac->adminHasPermission((int)$adminId, 'manage_cms') ||
            $rbac->adminHasPermission((int)$adminId, 'manage_seo')
        ): ?>
            <div class="menu-category">Pazarlama & İçerik</div>
            <?php if ($rbac->adminHasPermission((int)$adminId, 'manage_coupons')): ?>
                <a href="<?= url('/admin/coupons') ?>" class="list-group-item">
                    <i class="bi bi-ticket-perforated"></i> Pazarlama & Kuponlar
                </a>
            <?php endif; ?>
            <?php if ($rbac->adminHasPermission((int)$adminId, 'manage_cms')): ?>
                <a href="<?= url('/admin/pages') ?>" class="list-group-item">
                    <i class="bi bi-file-earmark-text"></i> İçerik Yönetimi (CMS)
                </a>
            <?php endif; ?>
            <?php if ($rbac->adminHasPermission((int)$adminId, 'manage_seo')): ?>
                <a href="<?= url('/admin/seo') ?>" class="list-group-item">
                    <i class="bi bi-search-heart"></i> SEO Ayarları
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Entegrasyonlar -->
        <div class="menu-category">Entegrasyonlar</div>
        <a href="<?= url('/admin/ai') ?>" class="list-group-item">
            <i class="bi bi-cpu"></i> Yapay Zekâ
        </a>

        <!-- Sistem Ayarları -->
        <?php if (
            $rbac->adminHasPermission((int)$adminId, 'manage_settings') || 
            $rbac->adminHasPermission((int)$adminId, 'manage_users') ||
            $rbac->adminHasPermission((int)$adminId, 'view_logs')
        ): ?>
            <div class="menu-category">Sistem</div>
            <?php if ($rbac->adminHasPermission((int)$adminId, 'manage_users')): ?>
                <a href="<?= url('/admin/roles') ?>" class="list-group-item">
                    <i class="bi bi-shield-lock"></i> Rol ve Yetkiler
                </a>
            <?php endif; ?>
            <?php if ($rbac->adminHasPermission((int)$adminId, 'manage_settings')): ?>
                <a href="<?= url('/admin/settings') ?>" class="list-group-item">
                    <i class="bi bi-sliders"></i> Sistem Ayarları
                </a>
                <a href="<?= url('/admin/themes') ?>" class="list-group-item">
                    <i class="bi bi-palette"></i> Tema Yönetimi
                </a>
                <a href="<?= url('/admin/plugins') ?>" class="list-group-item">
                    <i class="bi bi-plugin"></i> Eklenti Yönetimi
                </a>
            <?php endif; ?>
            <?php if ($rbac->adminHasPermission((int)$adminId, 'view_logs')): ?>
                <a href="<?= url('/admin/logs') ?>" class="list-group-item">
                    <i class="bi bi-list-stars"></i> Sistem Logları
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
