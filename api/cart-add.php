<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$productId = (int)($input['product_id'] ?? 0);
$quantity = (int)($input['quantity'] ?? 1);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

$db = getDBConnection();

// Check if product exists and is available
$stmt = $db->prepare("SELECT * FROM products WHERE id = :id AND status = 'active'");
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

if ((int)$product['stock'] > 0 && $quantity > (int)$product['stock']) {
    echo json_encode(['success' => false, 'error' => 'Not enough stock']);
    exit;
}

try {
    if (isLoggedIn()) {
        // Check if item already in cart
        $stmt = $db->prepare("SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id");
        $stmt->execute([':user_id' => $_SESSION['user_id'], ':product_id' => $productId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update quantity
            $newQuantity = $existing['quantity'] + $quantity;
            if ((int)$product['stock'] > 0 && $newQuantity > (int)$product['stock']) {
                $newQuantity = (int)$product['stock'];
            }
            $stmt = $db->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id");
            $stmt->execute([':quantity' => $newQuantity, ':id' => $existing['id']]);
        } else {
            // Add new item
            $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
            $stmt->execute([':user_id' => $_SESSION['user_id'], ':product_id' => $productId, ':quantity' => $quantity]);
        }
    } else {
        $sessionId = session_id();
        // Check if item already in cart
        $stmt = $db->prepare("SELECT * FROM cart WHERE session_id = :session_id AND product_id = :product_id");
        $stmt->execute([':session_id' => $sessionId, ':product_id' => $productId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update quantity
            $newQuantity = $existing['quantity'] + $quantity;
            if ((int)$product['stock'] > 0 && $newQuantity > (int)$product['stock']) {
                $newQuantity = (int)$product['stock'];
            }
            $stmt = $db->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id");
            $stmt->execute([':quantity' => $newQuantity, ':id' => $existing['id']]);
        } else {
            // Add new item
            $stmt = $db->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (:session_id, :product_id, :quantity)");
            $stmt->execute([':session_id' => $sessionId, ':product_id' => $productId, ':quantity' => $quantity]);
        }
    }
    
    // Get cart count
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
    } else {
        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = :session_id");
        $stmt->execute([':session_id' => session_id()]);
    }
    $cartCount = $stmt->fetchColumn() ?: 0;
    
    echo json_encode(['success' => true, 'cart_count' => (int)$cartCount]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>






