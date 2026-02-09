<?php
require_once 'config/config.php';
require_once 'includes/core-client.php';

// Перенаправляем в дашборд
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'dashboard.php?section=checkout';
    redirect('login.php');
} else {
    redirect('dashboard.php?section=checkout');
}

$db = getDBConnection();

// Get user info
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

// Get cart items
$stmt = $db->prepare("SELECT c.*, p.name, p.price, p.sku, p.stock 
                      FROM cart c 
                      JOIN products p ON c.product_id = p.id 
                      WHERE c.user_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    redirect('cart.php');
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 0;
$total = $subtotal + $shipping;

// Process order
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

        // Sync purchase to core (Supabase)
        $coreError = null;
        $buyerType = null;
        $buyerId = null;

        if (!empty($user['core_partner_id']) && $user['role'] === 'partner') {
            $buyerType = 'PARTNER';
            $buyerId = $user['core_partner_id'];
        } elseif (!empty($user['core_customer_id'])) {
            $buyerType = 'CUSTOMER';
            $buyerId = $user['core_customer_id'];
        }

        if ($buyerType && $buyerId) {
            $items = [];
            foreach ($cartItems as $item) {
                $linePrice = $item['price'] * $item['quantity'];
                $lineDv = (int)floor($linePrice / 30);
                if ($lineDv < 1) {
                    $lineDv = 1;
                }
                $items[] = [
                    'name' => $item['name'],
                    'priceRub' => $linePrice,
                    'dv' => $lineDv
                ];
            }

            $coreResult = corePostJson('/purchase', [
                'buyerType' => $buyerType,
                'buyerId' => $buyerId,
                'items' => $items,
                'useCashbackRub' => 0
            ], $coreError);

            if ($coreResult && isset($coreResult['data']['upgradedPartner']['id'])) {
                $newPartnerId = $coreResult['data']['upgradedPartner']['id'];
                $db->prepare("UPDATE users SET role = 'partner', core_partner_id = :core_partner_id WHERE id = :id")
                   ->execute([':core_partner_id' => $newPartnerId, ':id' => $_SESSION['user_id']]);
                $_SESSION['user_role'] = 'partner';
            } elseif ($coreError) {
                error_log('Core purchase sync failed: ' . $coreError);
            }
        }
        
        redirect('order-success.php?order=' . $orderNumber);
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Ошибка при создании заказа. Попробуйте еще раз.";
    }
}

$pageTitle = 'Оформление заказа';
include 'includes/header.php';
?>

<section class="checkout-section">
    <div class="container">
        <h1 class="page-title">Оформление заказа</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="checkout-form">
            <div class="checkout-layout">
                <div class="checkout-main">
                    <div class="checkout-section-block">
                        <h2>Контактная информация</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Имя *</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Фамилия *</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Телефон *</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="checkout-section-block">
                        <h2>Адрес доставки</h2>
                        <div class="form-group">
                            <label>Город *</label>
                            <input type="text" name="city" required>
                        </div>
                        <div class="form-group">
                            <label>Адрес *</label>
                            <textarea name="address" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Почтовый индекс</label>
                            <input type="text" name="postal_code">
                        </div>
                    </div>
                    
                    <div class="checkout-section-block">
                        <h2>Способ оплаты</h2>
                        <div class="form-group">
                            <label>
                                <input type="radio" name="payment_method" value="card" checked>
                                Банковская карта
                            </label>
                            <label>
                                <input type="radio" name="payment_method" value="cash">
                                Наложенный платеж
                            </label>
                            <label>
                                <input type="radio" name="payment_method" value="online">
                                Онлайн оплата
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="checkout-sidebar">
                    <div class="order-summary">
                        <h2>Ваш заказ</h2>
                        <div class="order-items">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="order-item">
                                    <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?></span>
                                    <span><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="order-totals">
                            <div class="order-total-row">
                                <span>Товаров на сумму:</span>
                                <span><?php echo formatPrice($subtotal); ?></span>
                            </div>
                            <div class="order-total-row">
                                <span>Доставка:</span>
                                <span><?php echo formatPrice($shipping); ?></span>
                            </div>
                            <div class="order-total-final">
                                <span>Итого:</span>
                                <span><?php echo formatPrice($total); ?></span>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary btn-large btn-block">Оформить заказ</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php
include 'includes/footer.php';
?>

