<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cartId = (int)($input['cart_id'] ?? 0);
$quantity = (int)($input['quantity'] ?? 1);

if ($cartId <= 0 || $quantity < 1) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$db = getDBConnection();

// Get cart item
$whereClause = isLoggedIn() 
    ? "id = :cart_id AND user_id = :user_id"
    : "id = :cart_id AND session_id = :session_id";
    
$params = [':cart_id' => $cartId];
if (isLoggedIn()) {
    $params[':user_id'] = $_SESSION['user_id'];
} else {
    $params[':session_id'] = session_id();
}

$stmt = $db->prepare("SELECT c.*, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE $whereClause");
$stmt->execute($params);
$cartItem = $stmt->fetch();

if (!$cartItem) {
    echo json_encode(['success' => false, 'error' => 'Cart item not found']);
    exit;
}

if ($quantity > $cartItem['stock']) {
    $quantity = $cartItem['stock'];
}

try {
    $stmt = $db->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id");
    $stmt->execute([':quantity' => $quantity, ':id' => $cartId]);
    
    // Calculate totals
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT SUM(c.quantity * p.price) as total FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
    } else {
        $stmt = $db->prepare("SELECT SUM(c.quantity * p.price) as total FROM cart c JOIN products p ON c.product_id = p.id WHERE c.session_id = :session_id");
        $stmt->execute([':session_id' => session_id()]);
    }
    $total = $stmt->fetchColumn() ?: 0;
    
    echo json_encode([
        'success' => true,
        'subtotal_formatted' => formatPrice($total),
        'total_formatted' => formatPrice($total)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

