<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/admin-config.php';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Админ-панель</title>
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <a href="<?php echo BASE_URL; ?>admin/" style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem; text-decoration: none;">
                    <img src="<?php echo ASSETS_PATH; ?>/images/logo.svg" alt="Logo" style="height: 50px; width: auto;">
                    <small style="color: var(--text-light); font-size: 0.9rem; margin-top: 0.25rem;">Админ-панель</small>
                </a>
            </div>
            
            <nav class="admin-nav">
                <a href="<?php echo BASE_URL; ?>admin/" class="admin-nav-item">
                    <span>📊</span> Дашборд
                </a>
                <a href="<?php echo BASE_URL; ?>admin/products.php" class="admin-nav-item">
                    <span>📦</span> Товары
                </a>
                <a href="<?php echo BASE_URL; ?>admin/orders.php" class="admin-nav-item">
                    <span>🛒</span> Заказы
                </a>
                <a href="<?php echo BASE_URL; ?>admin/categories.php" class="admin-nav-item">
                    <span>📁</span> Категории
                </a>
                <a href="<?php echo BASE_URL; ?>admin/users.php" class="admin-nav-item">
                    <span>👥</span> Пользователи
                </a>
                <a href="<?php echo BASE_URL; ?>admin/articles.php" class="admin-nav-item">
                    <span>📝</span> Статьи
                </a>
                <div class="admin-nav-dropdown">
                    <a href="<?php echo BASE_URL; ?>admin/banners.php" class="admin-nav-item admin-nav-toggle">
                        <span>🖼️</span> Баннеры
                        <span class="dropdown-arrow">▼</span>
                    </a>
                    <div class="admin-nav-submenu">
                        <a href="<?php echo BASE_URL; ?>admin/banner-edit.php?page=home" class="admin-nav-subitem">Главная</a>
                        <a href="<?php echo BASE_URL; ?>admin/banner-edit.php?page=partnership" class="admin-nav-subitem">Партнёры</a>
                        <a href="<?php echo BASE_URL; ?>admin/banner-edit.php?page=catalog" class="admin-nav-subitem">Каталог</a>
                        <a href="<?php echo BASE_URL; ?>admin/banners.php" class="admin-nav-subitem">Все баннеры</a>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>" class="admin-nav-item" target="_blank">
                    <span>🌐</span> На сайт
                </a>
                <a href="<?php echo BASE_URL; ?>admin/logout.php" class="admin-nav-item">
                    <span>🚪</span> Выход
                </a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <header class="admin-topbar">
                <h1><?php echo $pageTitle ?? 'Админ-панель'; ?></h1>
            </header>
            
            <div class="admin-content">

