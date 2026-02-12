<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/includes/core-client.php';
require_once dirname(__DIR__) . '/includes/partner-rank.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$currentSection = isset($section) ? $section : (isset($_GET['section']) ? $_GET['section'] : 'cabinet');

$db = getDBConnection();
$userStmt = $db->prepare("SELECT id, first_name, last_name, email, phone, birth_date, role, consultant_id, core_partner_id FROM users WHERE id = :user_id");
$userStmt->execute([':user_id' => $_SESSION['user_id']]);
$userData = $userStmt->fetch();

$isPartnerUser = (($userData['role'] ?? 'user') === 'partner') && !empty($userData['core_partner_id']);

$registrationId = null;
if (!empty($userData['email'])) {
    $regStmt = $db->prepare("SELECT id FROM partner_registrations WHERE email = :email AND status = 'confirmed' LIMIT 1");
    $regStmt->execute([':email' => $userData['email']]);
    $regRow = $regStmt->fetch();
    $registrationId = $regRow['id'] ?? null;
}

$fullName = trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''));
$fullName = $fullName ?: ($userData['email'] ?? 'Пользователь');
$refConsultantId = (string)($userData['id'] ?? '');
$partnerLink = ($isPartnerUser && $refConsultantId !== '')
    ? (BASE_URL . 'register.php?consultant_id=' . urlencode($refConsultantId))
    : '';

$rankCfg = partnerRankConfig();
$currentUserRankCode = syncUserRankEvent($db, $userData);
$currentUserRankLabel = $rankCfg['labels'][$currentUserRankCode] ?? 'Партнёр';

$consultantName = 'Данные появятся позже';
$consultantId = '—';
$consultantRankLabel = 'Партнёр';

// Определяем спонсора:
// 1) В приоритете consultant_id из local DB (кто реально дал ссылку).
// 2) Далее core sponsor для партнёра (/partner-summary).
// 3) Далее sponsor_partner_id из partner_registrations для клиента.
$sponsorCorePartnerId = null;
$consultantCorePartnerId = null;
if (!empty($userData['consultant_id'])) {
    $consultantRef = (int)$userData['consultant_id'];
    if ($consultantRef > 0) {
        // 1) consultant_id как users.id партнёра.
        $userMapStmt = $db->prepare("
            SELECT core_partner_id
            FROM users
            WHERE id = :uid
              AND role = 'partner'
              AND core_partner_id IS NOT NULL
            LIMIT 1
        ");
        $userMapStmt->execute([':uid' => $consultantRef]);
        $userMap = $userMapStmt->fetch();
        if (!empty($userMap['core_partner_id'])) {
            $consultantCorePartnerId = (string)$userMap['core_partner_id'];
        }

        // 2) consultant_id как registration id.
        if (!$consultantCorePartnerId) {
            $regMapStmt = $db->prepare("
                SELECT core_partner_id
                FROM partner_registrations
                WHERE id = :rid
                  AND status = 'confirmed'
                LIMIT 1
            ");
            $regMapStmt->execute([':rid' => $consultantRef]);
            $regMap = $regMapStmt->fetch();
            if (!empty($regMap['core_partner_id'])) {
                $consultantCorePartnerId = (string)$regMap['core_partner_id'];
            }
        }
    }
}
if ($consultantCorePartnerId) {
    $sponsorCorePartnerId = $consultantCorePartnerId;
}
if (!$sponsorCorePartnerId && $isPartnerUser) {
    $coreErr = null;
    $partnerRes = coreGetJson('/partner-summary?partnerId=' . urlencode((string)$userData['core_partner_id']), $coreErr);
    $sponsorCorePartnerId = $partnerRes['data']['sponsorId'] ?? null;
}
if (!$sponsorCorePartnerId && !empty($userData['email'])) {
    $sponsorRegStmt = $db->prepare("
        SELECT sponsor_partner_id
        FROM partner_registrations
        WHERE email = :email
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ");
    $sponsorRegStmt->execute([':email' => $userData['email']]);
    $sponsorReg = $sponsorRegStmt->fetch();
    $sponsorCorePartnerId = $sponsorReg['sponsor_partner_id'] ?? null;
}

if ($sponsorCorePartnerId) {
    $sponsorUserStmt = $db->prepare("
        SELECT id, first_name, last_name, email, role
        FROM users
        WHERE core_partner_id = :pid AND role = 'partner'
        LIMIT 1
    ");
    $sponsorUserStmt->execute([':pid' => $sponsorCorePartnerId]);
    $sponsorUser = $sponsorUserStmt->fetch();

    if ($sponsorUser) {
        $consultantName = trim(($sponsorUser['first_name'] ?? '') . ' ' . ($sponsorUser['last_name'] ?? ''));
        $consultantName = $consultantName ?: ($sponsorUser['email'] ?? '—');
        $consultantRankLabel = 'Партнёр';

        $sponsorRankStmt = $db->prepare("
            SELECT new_status_code
            FROM partner_status_events
            WHERE user_id = :uid
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ");
        $sponsorRankStmt->execute([':uid' => (int)$sponsorUser['id']]);
        $sponsorRankRow = $sponsorRankStmt->fetch();
        if (!empty($sponsorRankRow['new_status_code'])) {
            $code = strtoupper((string)$sponsorRankRow['new_status_code']);
            $sponsorRankMap = partnerRankConfig()['labels'] ?? [];
            if (!empty($sponsorRankMap[$code]) && $code !== 'CLIENT') {
                $consultantRankLabel = $sponsorRankMap[$code];
            }
        }

        $sRegStmt = $db->prepare("SELECT id FROM partner_registrations WHERE email = :email AND status = 'confirmed' LIMIT 1");
        $sRegStmt->execute([':email' => $sponsorUser['email']]);
        $sReg = $sRegStmt->fetch();
        $consultantId = $sReg['id'] ?? $sponsorUser['id'];
    } else {
        $consultantId = $sponsorCorePartnerId;
    }
}

$assetsImg = BASE_URL . 'assets/images';
$activeSection = $currentSection ?: 'cabinet';

$dashMetrics = [
    'personalMonthDv' => 0.0,
    'personalWeekDv' => 0.0,
    'partnerCashbackPercent' => 0.0,
    'influenceCircles' => 0,
    'bonusTotalRub' => 0.0,
    'bonusByTypeRub' => [
        'cashback' => 0.0,
        'influence' => 0.0,
        'balance' => 0.0,
        'growth' => 0.0,
        'global' => 0.0,
        'representative' => 0.0,
    ],
];

if ($isPartnerUser && !empty($userData['core_partner_id'])) {
    $partnerId = (string)$userData['core_partner_id'];

    $marketingErr = null;
    $marketingRes = coreGetJson('/partner-marketing-summary?partnerId=' . urlencode($partnerId), $marketingErr);
    if ($marketingRes && ($marketingRes['status'] ?? 500) < 400 && !empty($marketingRes['data'])) {
        $dashMetrics['personalMonthDv'] = (float)($marketingRes['data']['personalMonthDv'] ?? 0);
        $dashMetrics['personalWeekDv'] = (float)($marketingRes['data']['personalWeekDv'] ?? 0);
        $dashMetrics['partnerCashbackPercent'] = (float)($marketingRes['data']['partnerCashbackPercent'] ?? 0);
        $dashMetrics['influenceCircles'] = (int)($marketingRes['data']['influenceCircles'] ?? 0);
    }

    $from = date('Y-m-01');
    $to = date('Y-m-d');
    $page = 1;
    $maxPages = 10;
    while ($page <= $maxPages) {
        $bonusErr = null;
        $bonusUrl = '/partner-bonus-history?partnerId=' . urlencode($partnerId)
            . '&from=' . urlencode($from)
            . '&to=' . urlencode($to)
            . '&type=all&page=' . $page . '&perPage=100';
        $bonusRes = coreGetJson($bonusUrl, $bonusErr);
        if (!$bonusRes || ($bonusRes['status'] ?? 500) >= 400 || empty($bonusRes['data'])) {
            break;
        }

        $items = $bonusRes['data']['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            break;
        }

        foreach ($items as $row) {
            $type = strtoupper((string)($row['type'] ?? ''));
            $amountRub = (float)($row['amountRub'] ?? 0);
            $dashMetrics['bonusTotalRub'] += $amountRub;

            if ($type === 'PARTNER_CASHBACK') {
                $dashMetrics['bonusByTypeRub']['cashback'] += $amountRub;
            } elseif ($type === 'BALANCE_WEEKLY') {
                $dashMetrics['bonusByTypeRub']['balance'] += $amountRub;
            } elseif (strpos($type, 'INFLUENCE_') === 0) {
                $dashMetrics['bonusByTypeRub']['influence'] += $amountRub;
            } elseif ($type === 'GROWTH_BONUS') {
                $dashMetrics['bonusByTypeRub']['growth'] += $amountRub;
            } elseif ($type === 'GLOBAL_BONUS') {
                $dashMetrics['bonusByTypeRub']['global'] += $amountRub;
            } elseif ($type === 'REPRESENTATIVE_BONUS') {
                $dashMetrics['bonusByTypeRub']['representative'] += $amountRub;
            }
        }

        if (count($items) < 100) {
            break;
        }
        $page++;
    }
}

$dashBonusTotalDv = $dashMetrics['bonusTotalRub'] / 30;
$dashBonusCashbackDv = $dashMetrics['bonusByTypeRub']['cashback'] / 30;
$dashBonusInfluenceDv = $dashMetrics['bonusByTypeRub']['influence'] / 30;
$dashBonusBalanceDv = $dashMetrics['bonusByTypeRub']['balance'] / 30;
$dashBonusGrowthDv = $dashMetrics['bonusByTypeRub']['growth'] / 30;
$dashBonusGlobalDv = $dashMetrics['bonusByTypeRub']['global'] / 30;
$dashBonusRepresentativeDv = $dashMetrics['bonusByTypeRub']['representative'] / 30;
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
          <?php if ($isPartnerUser): ?>
            <a class="dash__navItem dash__navItem--cabinet <?php echo $activeSection === 'cabinet' ? 'is-active' : ''; ?>" href="dashboard.php?section=cabinet">
              <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/bento-menu.svg" alt="" /></span>
              <span class="dash__navLabel">Кабинет</span>
            </a>
            <a class="dash__navItem dash__navItem--profile <?php echo $activeSection === 'profile' ? 'is-active' : ''; ?>" href="dashboard.php?section=profile">
              <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/user-circle.svg" alt="" /></span>
              <span class="dash__navLabel">Профиль</span>
            </a>
            <a class="dash__navItem dash__navItem--shop <?php echo $activeSection === 'shop' ? 'is-active' : ''; ?>" href="dashboard.php?section=shop">
              <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/shopping-bag.svg" alt="" /></span>
              <span class="dash__navLabel">Магазин</span>
            </a>
            <a class="dash__navItem dash__navItem--orders <?php echo $activeSection === 'orders' ? 'is-active' : ''; ?>" href="dashboard.php?section=orders">
              <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/shopping-cart.svg" alt="" /></span>
              <span class="dash__navLabel">Заказы</span>
            </a>
            <a class="dash__navItem dash__navItem--team <?php echo $activeSection === 'team' ? 'is-active' : ''; ?>" href="dashboard.php?section=team">
              <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/Two-user.svg" alt="" /></span>
              <span class="dash__navLabel">Команда</span>
            </a>
            <a class="dash__navItem dash__navItem--history <?php echo $activeSection === 'history' ? 'is-active' : ''; ?>" href="dashboard.php?section=history">
              <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/document-box.svg" alt="" /></span>
              <span class="dash__navLabel">История<br />событий</span>
            </a>
            <a class="dash__navItem dash__navItem--partners <?php echo $activeSection === 'partners' ? 'is-active' : ''; ?>" href="dashboard.php?section=partners">
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
          <?php else: ?>
            <a class="dash__navItem dash__navItem--profile <?php echo $activeSection === 'profile' ? 'is-active' : ''; ?>" href="dashboard.php?section=profile">
              <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/user-circle.svg" alt="" /></span>
              <span class="dash__navLabel">Профиль</span>
            </a>
            <a class="dash__navItem dash__navItem--shop <?php echo $activeSection === 'shop' ? 'is-active' : ''; ?>" href="dashboard.php?section=shop">
              <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/shopping-bag.svg" alt="" /></span>
              <span class="dash__navLabel">Магазин</span>
            </a>
            <a class="dash__navItem dash__navItem--orders <?php echo $activeSection === 'orders' ? 'is-active' : ''; ?>" href="dashboard.php?section=orders">
              <span class="dash__navIcon"><img src="<?php echo $assetsImg; ?>/icons/shopping-cart.svg" alt="" /></span>
              <span class="dash__navLabel">Заказы</span>
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
          <?php endif; ?>
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
