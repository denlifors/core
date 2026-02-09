<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$db = getDBConnection();

try {
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
    } else {
        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = :session_id");
        $stmt->execute([':session_id' => session_id()]);
    }
    $cartCount = $stmt->fetchColumn() ?: 0;
    
    echo json_encode(['success' => true, 'count' => (int)$cartCount]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'count' => 0]);
}
?>






