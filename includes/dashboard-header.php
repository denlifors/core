<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/includes/core-client.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$currentSection = isset($section) ? $section : (isset($_GET['section']) ? $_GET['section'] : 'cabinet');

$db = getDBConnection();
$userStmt = $db->prepare("SELECT id, first_name, last_name, email, phone, birth_date, core_partner_id FROM users WHERE id = :user_id");
$userStmt->execute([':user_id' => $_SESSION['user_id']]);
$userData = $userStmt->fetch();

$registrationId = null;
if (!empty($userData['email'])) {
    $regStmt = $db->prepare("SELECT id FROM partner_registrations WHERE email = :email AND status = 'confirmed' LIMIT 1");
    $regStmt->execute([':email' => $userData['email']]);
    $regRow = $regStmt->fetch();
    $registrationId = $regRow['id'] ?? null;
}

$fullName = trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''));
$fullName = $fullName ?: ($userData['email'] ?? 'Пользователь');
$partnerLink = !empty($userData['core_partner_id'])
    ? (BASE_URL . 'register.php?ref=' . $userData['core_partner_id'])
    : '';

$consultantName = 'Данные появятся позже';
$consultantId = '—';
if (!empty($userData['core_partner_id'])) {
    $coreErr = null;
    $partnerRes = coreGetJson('/partner-summary?partnerId=' . urlencode($userData['core_partner_id']), $coreErr);
    $sponsorId = $partnerRes['data']['sponsorId'] ?? null;

    if ($sponsorId) {
        $sponsorUserStmt = $db->prepare("SELECT id, first_name, last_name, email FROM users WHERE core_partner_id = :pid LIMIT 1");
        $sponsorUserStmt->execute([':pid' => $sponsorId]);
        $sponsorUser = $sponsorUserStmt->fetch();

        if ($sponsorUser) {
            $consultantName = trim(($sponsorUser['first_name'] ?? '') . ' ' . ($sponsorUser['last_name'] ?? ''));
            $consultantName = $consultantName ?: ($sponsorUser['email'] ?? '—');

            $sRegStmt = $db->prepare("SELECT id FROM partner_registrations WHERE email = :email AND status = 'confirmed' LIMIT 1");
            $sRegStmt->execute([':email' => $sponsorUser['email']]);
            $sReg = $sRegStmt->fetch();
            $consultantId = $sReg['id'] ?? $sponsorUser['id'];
        } else {
            $consultantId = $sponsorId;
        }
    }
}

$assetsImg = BASE_URL . 'assets/images';
$activeSection = $currentSection ?: 'cabinet';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : SITE_DESCRIPTION; ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/dashboard.css?v=<?php echo filemtime(dirname(__DIR__) . '/assets/css/dashboard.css'); ?>">
</head>
<body>
<div class="dash" data-active-section="<?php echo htmlspecialchars($activeSection); ?>">
  <div class="dash__layout">
    <aside class="dash__menu">
      <div class="dash__menuInner">
        <nav class="dash__nav">
          <a class="dash__navItem <?php echo $activeSection === 'cabinet' ? 'is-active' : ''; ?>" href="dashboard.php?section=cabinet">
            <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/bento-menu.svg" alt="" /></span>
            <span class="dash__navLabel">Кабинет</span>
          </a>
          <a class="dash__navItem <?php echo $activeSection === 'profile' ? 'is-active' : ''; ?>" href="dashboard.php?section=profile">
            <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/user-circle.svg" alt="" /></span>
            <span class="dash__navLabel">Профиль</span>
          </a>
          <a class="dash__navItem <?php echo $activeSection === 'shop' ? 'is-active' : ''; ?>" href="dashboard.php?section=shop">
            <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/shopping-bag.svg" alt="" /></span>
            <span class="dash__navLabel">Магазин</span>
          </a>
          <a class="dash__navItem <?php echo $activeSection === 'orders' ? 'is-active' : ''; ?>" href="dashboard.php?section=orders">
            <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/shopping-cart.svg" alt="" /></span>
            <span class="dash__navLabel">Заказы</span>
          </a>
          <a class="dash__navItem <?php echo $activeSection === 'team' ? 'is-active' : ''; ?>" href="dashboard.php?section=team">
            <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/Two-user.svg" alt="" /></span>
            <span class="dash__navLabel">Команда</span>
          </a>
          <a class="dash__navItem <?php echo $activeSection === 'history' ? 'is-active' : ''; ?>" href="dashboard.php?section=history">
            <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/document-box.svg" alt="" /></span>
            <span class="dash__navLabel">История<br />событий</span>
          </a>
          <a class="dash__navItem <?php echo $activeSection === 'partners' ? 'is-active' : ''; ?>" href="dashboard.php?section=partners">
            <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/switch-user.svg" alt="" /></span>
            <span class="dash__navLabel">Партнёры</span>
          </a>
          <a class="dash__navItem <?php echo $activeSection === 'news' ? 'is-active' : ''; ?>" href="dashboard.php?section=news">
            <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/document-7.svg" alt="" /></span>
            <span class="dash__navLabel">Новости</span>
          </a>
          <a class="dash__navItem <?php echo $activeSection === 'library' ? 'is-active' : ''; ?>" href="dashboard.php?section=library">
            <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/book.svg" alt="" /></span>
            <span class="dash__navLabel">Библиотека</span>
          </a>
          <a class="dash__navItem <?php echo $activeSection === 'support' ? 'is-active' : ''; ?>" href="dashboard.php?section=support">
            <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/headphones.svg" alt="" /></span>
            <span class="dash__navLabel">Поддержка</span>
          </a>
          <a class="dash__navItem" href="logout.php">
            <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/exit.svg" alt="" /></span>
            <span class="dash__navLabel">Выйти</span>
          </a>
        </nav>
      </div>
    </aside>
    <div class="dash__glass">
      <div class="dash__grid">
        <main class="dash__main">
