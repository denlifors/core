<?php
require_once 'config/config.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isLoggedIn()) {
    redirect('login.php');
}

$section = $_GET['section'] ?? 'shop';
$pageTitle = 'Кабинет';
$breadcrumbTitle = null;

// Получаем информацию о пользователе для определения статуса
$db = getDBConnection();
$userStmt = $db->prepare("SELECT role FROM users WHERE id = :user_id");
$userStmt->execute([':user_id' => $_SESSION['user_id']]);
$userInfo = $userStmt->fetch();
$userRole = $userInfo['role'] ?? 'user';
$isClient = ($userRole === 'user');

// Определяем заголовок страницы в зависимости от секции
switch ($section) {
    case 'cabinet':
        $pageTitle = 'Кабинет';
        break;
    case 'profile':
        $pageTitle = 'Профиль';
        break;
    case 'shop':
        $pageTitle = 'Магазин';
        break;
    case 'orders':
        $pageTitle = 'Мои заказы';
        $breadcrumbTitle = 'Заказы';
        break;
    case 'team':
        $pageTitle = 'Команда';
        break;
    case 'history':
        $pageTitle = 'История событий';
        break;
    case 'partners':
        $pageTitle = 'Партнёры';
        break;
    case 'news':
        $pageTitle = 'Новости';
        break;
    case 'library':
        $pageTitle = 'Библиотека';
        break;
    case 'support':
        $pageTitle = 'Поддержка';
        break;
    case 'cart':
        $pageTitle = 'Корзина';
        $breadcrumbTitle = 'Магазин';
        break;
    case 'product':
        $pageTitle = 'Описание';
        $breadcrumbTitle = 'Магазин';
        break;
    case 'checkout':
        $pageTitle = 'Оформление заказа';
        break;
    case 'order-success':
        $pageTitle = 'Заказ оформлен';
        break;
    default:
        $pageTitle = 'Кабинет';
}

if ($breadcrumbTitle === null) {
    $breadcrumbTitle = $pageTitle;
}

include 'includes/dashboard-header.php';
?>
<section id="<?php echo htmlspecialchars($section); ?>" class="dash__section is-active" data-section="<?php echo htmlspecialchars($section); ?>">
    <header class="dash__header">
        <div class="dash__logo">
            <img class="dash__logoImg" src="<?php echo $assetsImg; ?>/image0_1_37.png" alt="Logo" />
        </div>
        <div class="dash__titleWrap">
            <h1 class="dash__title"><?php echo htmlspecialchars($pageTitle); ?></h1>
            <div class="dash__crumbs">
                <span class="dash__crumb">Главная</span>
                <span class="dash__crumbSep">/</span>
                <span class="dash__crumbActive"><?php echo htmlspecialchars($breadcrumbTitle); ?></span>
            </div>
        </div>
    </header>

<?php
// Подключаем контент в зависимости от секции
switch ($section) {
    case 'shop':
        if (isset($isPartnerUser) && !$isPartnerUser) {
            include 'dashboard/shop-client.php';
        } else {
            include 'dashboard/shop.php';
        }
        break;
    case 'cabinet':
        include 'dashboard/cabinet.php';
        break;
    case 'profile':
        include 'dashboard/profile.php';
        break;
    case 'orders':
        include 'dashboard/orders.php';
        break;
    case 'team':
        include 'dashboard/team.php';
        break;
    case 'history':
        include 'dashboard/history.php';
        break;
    case 'partners':
        include 'dashboard/partners.php';
        break;
    case 'news':
        include 'dashboard/news.php';
        break;
    case 'library':
        include 'dashboard/library.php';
        break;
    case 'support':
        include 'dashboard/support.php';
        break;
    case 'cart':
        include 'dashboard/cart.php';
        break;
    case 'product':
        include 'dashboard/product.php';
        break;
    case 'checkout':
        include 'dashboard/checkout.php';
        break;
    case 'order-success':
        include 'dashboard/order-success.php';
        break;
    default:
        include 'dashboard/shop.php';
}
?>
</section>
<?php
include 'includes/dashboard-footer.php';
?>

