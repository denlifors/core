<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

if (!isLoggedIn()) {
    redirect('login.php');
}

$currentPage = basename($_SERVER['PHP_SELF']);
// Получаем секцию из GET или используем значение из dashboard.php если оно определено
$currentSection = isset($section) ? $section : (isset($_GET['section']) ? $_GET['section'] : 'shop');

// Получаем информацию о пользователе (если еще не определена в dashboard.php)
if (!isset($userRole)) {
    $db = getDBConnection();
    $userStmt = $db->prepare("SELECT role FROM users WHERE id = :user_id");
    $userStmt->execute([':user_id' => $_SESSION['user_id']]);
    $userInfo = $userStmt->fetch();
    $userRole = $userInfo['role'] ?? 'user';
    $isClient = ($userRole === 'user');
}

// Определяем статус для отображения (только Клиент или Партнёр)
// Админ - это отдельное свойство (is_admin), не роль
if ($userRole === 'partner') {
    $userStatus = 'Партнёр';
    $isClient = false;
} else {
    // Для 'user' показываем как "Клиент"
    $userStatus = 'Клиент';
    $isClient = true;
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/dashboard.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-container">
        <!-- Left Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="dashboard-logo">
                <div class="dashboard-logo-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2C8.5 2 5.5 4 4 7c-1.5 3-1.5 6 0 9 1.5 3 4.5 5 8 5s6.5-2 8-5c1.5-3 1.5-6 0-9C18.5 4 15.5 2 12 2z"/>
                        <path d="M12 8v8M8 12h8"/>
                        <path d="M9 9l6 6M15 9l-6 6"/>
                        <circle cx="12" cy="12" r="1" fill="currentColor"/>
                    </svg>
                </div>
                <div class="dashboard-logo-text">ДенЛиФорс</div>
            </div>
            
            <nav class="dashboard-nav">
                <a href="dashboard.php?section=cabinet" class="dashboard-nav-item <?php echo $currentSection == 'cabinet' ? 'active' : ''; ?>">
                    <div class="dashboard-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                    </div>
                    <span class="dashboard-nav-text">Кабинет</span>
                </a>
                
                <a href="dashboard.php?section=profile" class="dashboard-nav-item <?php echo $currentSection == 'profile' ? 'active' : ''; ?>">
                    <div class="dashboard-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <span class="dashboard-nav-text">Профиль</span>
                </a>
                
                <a href="dashboard.php?section=shop" class="dashboard-nav-item <?php echo $currentSection == 'shop' ? 'active' : ''; ?>">
                    <div class="dashboard-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 2L3 6v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6l-3-4H6zM3 6h18M16 10a4 4 0 11-8 0"/>
                        </svg>
                    </div>
                    <span class="dashboard-nav-text">Магазин</span>
                </a>
                
                <a href="dashboard.php?section=orders" class="dashboard-nav-item <?php echo $currentSection == 'orders' ? 'active' : ''; ?>">
                    <div class="dashboard-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"/>
                            <circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                    </div>
                    <span class="dashboard-nav-text">Заказы</span>
                </a>
                
                <a href="dashboard.php?section=team" class="dashboard-nav-item <?php echo $currentSection == 'team' ? 'active' : ''; ?>">
                    <div class="dashboard-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <span class="dashboard-nav-text">Команда</span>
                </a>
                
                <a href="dashboard.php?section=history" class="dashboard-nav-item <?php echo $currentSection == 'history' ? 'active' : ''; ?>">
                    <div class="dashboard-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <span class="dashboard-nav-text">История событий</span>
                </a>
                
                <a href="dashboard.php?section=partners" class="dashboard-nav-item <?php echo $currentSection == 'partners' ? 'active' : ''; ?>">
                    <div class="dashboard-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <span class="dashboard-nav-text">Партнёры</span>
                </a>
                
                <a href="dashboard.php?section=news" class="dashboard-nav-item <?php echo $currentSection == 'news' ? 'active' : ''; ?>">
                    <div class="dashboard-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </div>
                    <span class="dashboard-nav-text">Новости</span>
                </a>
                
                <a href="dashboard.php?section=library" class="dashboard-nav-item <?php echo $currentSection == 'library' ? 'active' : ''; ?>">
                    <div class="dashboard-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                        </svg>
                    </div>
                    <span class="dashboard-nav-text">Библиотека</span>
                </a>
                
                <a href="dashboard.php?section=support" class="dashboard-nav-item <?php echo $currentSection == 'support' ? 'active' : ''; ?>">
                    <div class="dashboard-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                            <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
                        </svg>
                    </div>
                    <span class="dashboard-nav-text">Поддержка</span>
                </a>
                
                <a href="logout.php" class="dashboard-nav-item dashboard-nav-logout">
                    <div class="dashboard-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </div>
                    <span class="dashboard-nav-text">Выйти</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="dashboard-main">
            <!-- Top Header -->
            <header class="dashboard-header">
                <div class="dashboard-header-content">
                    <div class="dashboard-breadcrumb">
                        <a href="dashboard.php?section=cabinet">Главная</a>
                        <span class="breadcrumb-separator">/</span>
                        <?php if ($currentSection == 'product'): ?>
                            <a href="dashboard.php?section=shop">Магазин</a>
                            <span class="breadcrumb-separator">/</span>
                            <span class="breadcrumb-current"><?php echo isset($pageTitle) ? $pageTitle : 'Описание'; ?></span>
                        <?php else: ?>
                            <span class="breadcrumb-current"><?php echo isset($pageTitle) ? $pageTitle : 'Кабинет'; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="dashboard-status-menu">
                        <span class="dashboard-status-label">Статус:</span>
                        <span class="dashboard-status-badge dashboard-status-<?php echo $userRole; ?>"><?php echo htmlspecialchars($userStatus); ?></span>
                        <?php if ($isClient): ?>
                            <button class="dashboard-become-partner-btn" id="become-partner-btn">
                                Стать партнёром
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="dashboard-header-actions">
                        <button class="dashboard-search-toggle" id="dashboard-search-toggle" title="Поиск">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="M21 21l-4.35-4.35"/>
                            </svg>
                        </button>
                        <a href="dashboard.php?section=cart" class="dashboard-cart-link" title="Корзина" id="dashboard-cart-link">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 2L3 6v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6l-3-4H6zM3 6h18M16 10a4 4 0 11-8 0"/>
                            </svg>
                            <span class="dashboard-cart-count" id="dashboard-cart-count" style="display: none;">0</span>
                        </a>
                    </div>
                </div>
            </header>
            
            <!-- Модальное окно для становления партнёром -->
            <?php if ($isClient): ?>
            <div id="become-partner-modal" class="become-partner-modal" style="display: none;">
                <div class="become-partner-modal-content">
                    <button class="become-partner-modal-close" id="close-become-partner-modal" aria-label="Закрыть">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                    <div class="become-partner-modal-header">
                        <h2 class="become-partner-modal-title">Стать партнёром</h2>
                        <p class="become-partner-modal-subtitle">Чтобы стать партнёром, вы должны подписать все документы</p>
                    </div>
                    <div class="become-partner-documents">
                        <div class="become-partner-document-item">
                            <div class="become-partner-document-checkbox">
                                <input type="checkbox" id="doc-agreement" class="become-partner-doc-checkbox">
                                <label for="doc-agreement">
                                    <a href="<?php echo BASE_URL; ?>terms.php" target="_blank">Пользовательское соглашение</a>
                                </label>
                            </div>
                        </div>
                        <div class="become-partner-document-item">
                            <div class="become-partner-document-checkbox">
                                <input type="checkbox" id="doc-privacy" class="become-partner-doc-checkbox">
                                <label for="doc-privacy">
                                    <a href="<?php echo BASE_URL; ?>privacy.php" target="_blank">Политика конфиденциальности</a>
                                </label>
                            </div>
                        </div>
                        <div class="become-partner-document-item">
                            <div class="become-partner-document-checkbox">
                                <input type="checkbox" id="doc-partnership" class="become-partner-doc-checkbox">
                                <label for="doc-partnership">
                                    <a href="<?php echo BASE_URL; ?>partnership.php" target="_blank">Стандарты сотрудничества</a>
                                </label>
                            </div>
                        </div>
                        <div class="become-partner-document-item">
                            <div class="become-partner-document-checkbox">
                                <input type="checkbox" id="doc-ethics" class="become-partner-doc-checkbox">
                                <label for="doc-ethics">
                                    <a href="<?php echo BASE_URL; ?>partnership.php" target="_blank">Этический кодекс</a>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="become-partner-modal-actions">
                        <button class="become-partner-submit-btn" id="become-partner-submit" disabled>
                            Подписать
                        </button>
                        <button class="become-partner-cancel-btn" id="become-partner-cancel">
                            Отмена
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Search Modal -->
            <div id="dashboard-search-modal" class="dashboard-search-modal" style="display: none;">
                <div class="dashboard-search-modal-content">
                    <button class="dashboard-search-close" id="dashboard-search-close" aria-label="Закрыть">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                    <form method="GET" action="dashboard.php" class="dashboard-search-form">
                        <input type="hidden" name="section" value="shop">
                        <div class="dashboard-search-input-wrapper">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="dashboard-search-icon">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="M21 21l-4.35-4.35"/>
                            </svg>
                            <input 
                                type="text" 
                                name="search" 
                                class="dashboard-search-input" 
                                placeholder="Поиск товаров..."
                                autofocus
                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                            >
                        </div>
                        <button type="submit" class="dashboard-search-submit">Найти</button>
                    </form>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="dashboard-content">

