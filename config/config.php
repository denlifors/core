<?php
// Application configuration
session_start();

// Base URL
define('BASE_URL', 'http://localhost/DenLiFors/');

// Core API
define('CORE_API_BASE_URL', 'http://localhost:3000');

// Partner confirmation settings
define('PARTNER_CONFIRM_TTL_HOURS', 48);
// Local demo mode: instantly confirm partner registrations via referral link.
define('PARTNER_AUTO_CONFIRM', true);

// Mail settings
define('MAIL_FROM', 'admin@denlifors.ru');
define('MAIL_FROM_NAME', 'ДенЛиФорс');

// Site settings
define('SITE_NAME', 'ДенЛиФорс');
define('SITE_DESCRIPTION', 'Интернет-магазин биологически активных добавок');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('ASSETS_PATH', BASE_URL . 'assets');

// Timezone
date_default_timezone_set('Europe/Moscow');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database
require_once ROOT_PATH . '/config/database.php';

// Helper functions
function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check if user has is_admin flag in database
    $db = getDBConnection();
    
    // Ensure is_admin column exists
    require_once ROOT_PATH . '/config/ensure-admin-field.php';
    ensureAdminFieldExists($db);
    
    // Check if is_admin column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        // New method: check is_admin
        $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user && $user['is_admin'] == 1;
    } else {
        // Fallback: check role = 'admin' (old method)
        $stmt = $db->prepare("SELECT role FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user && $user['role'] === 'admin';
    }
}

function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' ₽';
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>





