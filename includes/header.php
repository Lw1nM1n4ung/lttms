<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
$currentUser = getCurrentUser();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' : '' ?>LTTMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>(function(){var t=localStorage.getItem('lttms-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark')})()</script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="container nav-container">
        <a href="<?= BASE_URL ?>/" class="nav-brand">
            <span class="brand-icon">&#9992;</span> LTTMS
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
        <ul class="nav-menu" id="navMenu">
            <li><a href="<?= BASE_URL ?>/"><?= t('nav_home') ?></a></li>
            <li><a href="<?= BASE_URL ?>/customer/packages.php"><?= t('nav_packages') ?></a></li>
            <?php if ($currentUser): ?>
                <?php if ($currentUser['role'] === 'customer'): ?>
                    <li><a href="<?= BASE_URL ?>/customer/my-bookings.php"><?= t('nav_my_bookings') ?></a></li>
                <?php elseif ($currentUser['role'] === 'agent'): ?>
                    <li><a href="<?= BASE_URL ?>/agent/dashboard.php"><?= t('nav_dashboard') ?></a></li>
                <?php elseif ($currentUser['role'] === 'admin'): ?>
                    <li><a href="<?= BASE_URL ?>/admin/dashboard.php"><?= t('nav_dashboard') ?></a></li>
                    <li><a href="<?= BASE_URL ?>/admin/packages.php"><?= t('nav_packages_control') ?></a></li>
                <?php endif; ?>
                <li class="nav-user">
                    <span class="user-greeting"><?= sanitize($currentUser['full_name']) ?></span>
                    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline"><?= t('nav_logout') ?></a>
                </li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>/login.php" class="btn btn-sm btn-outline"><?= t('nav_login') ?></a></li>
                <li><a href="<?= BASE_URL ?>/register.php" class="btn btn-sm btn-primary"><?= t('nav_register') ?></a></li>
            <?php endif; ?>
            <li><button class="theme-toggle" id="themeToggle" title="<?= t('toggle_theme') ?>"><span id="themeIcon">&#9789;</span></button></li>
        </ul>
    </div>
</nav>

<?php if ($flash): ?>
<div class="container">
    <div class="alert"><?= sanitize($flash) ?></div>
</div>
<?php endif; ?>

<main>
