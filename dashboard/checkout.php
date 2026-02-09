<?php
// Подключаем config если еще не подключен
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$db = getDBConnection();

// Get user info
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

// Get cart items
$stmt = $db->prepare("SELECT c.*, p.name, p.price, p.image, p.sku, p.stock 
                      FROM cart c 
                      JOIN products p ON c.product_id = p.id 
                      WHERE c.user_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    redirect('dashboard.php?section=cart');
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 0;
$total = $subtotal + $shipping;

// Process order
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $postalCode = sanitize($_POST['postal_code'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Generate order number
    $orderNumber = 'DL-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    try {
        $db->beginTransaction();
        
        // Create order
        $stmt = $db->prepare("INSERT INTO orders (user_id, order_number, total, first_name, last_name, email, phone, address, city, postal_code, payment_method, notes, status) 
                              VALUES (:user_id, :order_number, :total, :first_name, :last_name, :email, :phone, :address, :city, :postal_code, :payment_method, :notes, 'pending')");
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':order_number' => $orderNumber,
            ':total' => $total,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':city' => $city,
            ':postal_code' => $postalCode,
            ':payment_method' => $paymentMethod,
            ':notes' => $notes
        ]);
        
        $orderId = $db->lastInsertId();
        
        // Create order items
        $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_sku, price, quantity) 
                              VALUES (:order_id, :product_id, :product_name, :product_sku, :price, :quantity)");
        
        foreach ($cartItems as $item) {
            $stmt->execute([
                ':order_id' => $orderId,
                ':product_id' => $item['product_id'],
                ':product_name' => $item['name'],
                ':product_sku' => $item['sku'],
                ':price' => $item['price'],
                ':quantity' => $item['quantity']
            ]);
            
            // Update product stock and sales count
            $db->prepare("UPDATE products SET stock = stock - :quantity, sales_count = sales_count + :quantity WHERE id = :id")
               ->execute([':quantity' => $item['quantity'], ':id' => $item['product_id']]);
        }
        
        // Clear cart
        $db->prepare("DELETE FROM cart WHERE user_id = :user_id")->execute([':user_id' => $_SESSION['user_id']]);
        
        $db->commit();
        
        redirect('dashboard.php?section=order-success&order=' . $orderNumber);
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Ошибка при создании заказа. Попробуйте еще раз.";
    }
}
?>

<div class="dashboard-checkout">
    <div class="dashboard-checkout-header">
        <a href="dashboard.php?section=cart" class="dashboard-checkout-back">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Назад к корзине
        </a>
        <h1 class="dashboard-checkout-title">Оформление заказа</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="dashboard-checkout-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="dashboard-checkout-form">
        <div class="dashboard-checkout-content">
            <div class="dashboard-checkout-main">
                <div class="dashboard-checkout-block">
                    <h2 class="dashboard-checkout-block-title">Контактная информация</h2>
                    <div class="dashboard-checkout-form-row">
                        <div class="dashboard-checkout-form-group">
                            <label class="dashboard-checkout-label">Имя *</label>
                            <input type="text" name="first_name" class="dashboard-checkout-input" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="dashboard-checkout-form-group">
                            <label class="dashboard-checkout-label">Фамилия *</label>
                            <input type="text" name="last_name" class="dashboard-checkout-input" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="dashboard-checkout-form-row">
                        <div class="dashboard-checkout-form-group">
                            <label class="dashboard-checkout-label">Email *</label>
                            <input type="email" name="email" class="dashboard-checkout-input" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                        <div class="dashboard-checkout-form-group">
                            <label class="dashboard-checkout-label">Телефон *</label>
                            <input type="tel" name="phone" class="dashboard-checkout-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-checkout-block">
                    <h2 class="dashboard-checkout-block-title">Адрес доставки</h2>
                    <div class="dashboard-checkout-form-group">
                        <label class="dashboard-checkout-label">Город *</label>
                        <input type="text" name="city" class="dashboard-checkout-input" required>
                    </div>
                    <div class="dashboard-checkout-form-group">
                        <label class="dashboard-checkout-label">Адрес *</label>
                        <textarea name="address" class="dashboard-checkout-textarea" rows="3" required></textarea>
                    </div>
                    <div class="dashboard-checkout-form-group">
                        <label class="dashboard-checkout-label">Почтовый индекс</label>
                        <input type="text" name="postal_code" class="dashboard-checkout-input">
                    </div>
                </div>
                
                <div class="dashboard-checkout-block">
                    <h2 class="dashboard-checkout-block-title">Способ оплаты</h2>
                    <div class="dashboard-checkout-payment-methods">
                        <label class="dashboard-checkout-payment-option">
                            <input type="radio" name="payment_method" value="card" checked>
                            <span>Банковская карта</span>
                        </label>
                        <label class="dashboard-checkout-payment-option">
                            <input type="radio" name="payment_method" value="cash">
                            <span>Наложенный платеж</span>
                        </label>
                        <label class="dashboard-checkout-payment-option">
                            <input type="radio" name="payment_method" value="online">
                            <span>Онлайн оплата</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-checkout-sidebar">
                <div class="dashboard-checkout-summary">
                    <h2 class="dashboard-checkout-summary-title">Ваш заказ</h2>
                    <div class="dashboard-checkout-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="dashboard-checkout-item">
                                <div class="dashboard-checkout-item-info">
                                    <?php if ($item['image']): ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="dashboard-checkout-item-image" onerror="this.style.display='none';">
                                    <?php endif; ?>
                                    <div>
                                        <div class="dashboard-checkout-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="dashboard-checkout-item-details">× <?php echo $item['quantity']; ?></div>
                                    </div>
                                </div>
                                <div class="dashboard-checkout-item-price"><?php echo formatPrice($item['price'] * $item['quantity']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="dashboard-checkout-totals">
                        <div class="dashboard-checkout-total-row">
                            <span>Товаров на сумму:</span>
                            <span><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        <div class="dashboard-checkout-total-row">
                            <span>Доставка:</span>
                            <span><?php echo formatPrice($shipping); ?></span>
                        </div>
                        <div class="dashboard-checkout-total-final">
                            <span>Итого:</span>
                            <span><?php echo formatPrice($total); ?></span>
                        </div>
                    </div>
                    <button type="submit" class="dashboard-checkout-submit-btn">Оформить заказ</button>
                </div>
            </div>
        </div>
    </form>
</div>


