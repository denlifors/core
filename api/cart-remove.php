<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cartId = (int)($input['cart_id'] ?? 0);

if ($cartId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid cart ID']);
    exit;
}

$db = getDBConnection();

try {
    $whereClause = isLoggedIn() 
        ? "id = :cart_id AND user_id = :user_id"
        : "id = :cart_id AND session_id = :session_id";
        
    $params = [':cart_id' => $cartId];
    if (isLoggedIn()) {
        $params[':user_id'] = $_SESSION['user_id'];
    } else {
        $params[':session_id'] = session_id();
    }
    
    $stmt = $db->prepare("DELETE FROM cart WHERE $whereClause");
    $stmt->execute($params);
    
    // Get cart count and totals
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $cartCount = $stmt->fetchColumn() ?: 0;
        
        $stmt = $db->prepare("SELECT SUM(c.quantity * p.price) as total FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
    } else {
        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = :session_id");
        $stmt->execute([':session_id' => session_id()]);
        $cartCount = $stmt->fetchColumn() ?: 0;
        
        $stmt = $db->prepare("SELECT SUM(c.quantity * p.price) as total FROM cart c JOIN products p ON c.product_id = p.id WHERE c.session_id = :session_id");
        $stmt->execute([':session_id' => session_id()]);
    }
    $total = $stmt->fetchColumn() ?: 0;
    
    require_once '../config/config.php';
    
    echo json_encode([
        'success' => true,
        'cart_count' => (int)$cartCount,
        'subtotal_formatted' => formatPrice($total),
        'total_formatted' => formatPrice($total)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>






