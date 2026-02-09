<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : SITE_DESCRIPTION; ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="<?php echo BASE_URL; ?>">
                        <img src="<?php echo ASSETS_PATH; ?>/images/logo.svg" alt="<?php echo SITE_NAME; ?>" class="logo-img">
                    </a>
                </div>
                
                <nav class="nav">
                    <?php
                    $currentPage = basename($_SERVER['PHP_SELF']);
                    $isCatalog = ($currentPage == 'catalog.php');
                    $isPartnership = ($currentPage == 'partnership.php');
                    $isContacts = ($currentPage == 'contacts.php');
                    ?>
                    <a href="<?php echo BASE_URL; ?>catalog.php" class="<?php echo $isCatalog ? 'active' : ''; ?>">Каталог</a>
                    <a href="<?php echo BASE_URL; ?>partnership.php" class="<?php echo $isPartnership ? 'active' : ''; ?>">Партнёрство</a>
                    <a href="<?php echo BASE_URL; ?>articles.php" class="<?php echo $currentPage == 'articles.php' ? 'active' : ''; ?>">Статьи</a>
                    <a href="<?php echo BASE_URL; ?>contacts.php" class="<?php echo $isContacts ? 'active' : ''; ?>">Контакты</a>
                </nav>
                
                <div class="header-actions">
                    <?php if (isLoggedIn()): ?>
                        <div class="account-dropdown">
                            <button class="account-toggle">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <span>Аккаунт</span>
                            </button>
                            <div class="account-menu">
                                <a href="<?php echo BASE_URL; ?>profile.php">Профиль</a>
                                <a href="<?php echo BASE_URL; ?>profile.php?tab=settings">Настройки</a>
                                <a href="<?php echo BASE_URL; ?>profile.php?tab=cashback">Кэшбэк</a>
                                <a href="<?php echo BASE_URL; ?>logout.php">Выход</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>login.php" class="btn-login">
                            <span>Вход</span>
                            <img src="<?php echo ASSETS_PATH; ?>/images/arrow-icon.svg" alt="" class="btn-arrow">
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main-content">

