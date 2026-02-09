<?php
// Admin panel configuration with separate session
// This allows admin to be logged in separately from regular users

// Use different session name for admin
session_name('admin_session');
session_start();

// Base URL
define('BASE_URL', 'http://localhost/DenLiFors/');

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

// Admin-specific login check
function isAdminLoggedIn() {
    return isset($_SESSION['admin_user_id']);
}

// Check if current admin user has admin status
function isAdmin() {
    if (!isset($_SESSION['admin_user_id'])) {
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
        $stmt->execute([':id' => $_SESSION['admin_user_id']]);
        $user = $stmt->fetch();
        return $user && $user['is_admin'] == 1;
    } else {
        // Fallback: check role = 'admin' (old method)
        $stmt = $db->prepare("SELECT role FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['admin_user_id']]);
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






