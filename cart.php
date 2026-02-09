<?php
require_once 'config/config.php';

$db = getDBConnection();

// Get cart items
$cartItems = [];
if (isLoggedIn()) {
    $stmt = $db->prepare("SELECT c.*, p.name, p.price, p.image, p.sku, p.stock 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.user_id = :user_id 
                          ORDER BY c.created_at DESC");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll();
} else {
    $sessionId = session_id();
    $stmt = $db->prepare("SELECT c.*, p.name, p.price, p.image, p.sku, p.stock 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.session_id = :session_id 
                          ORDER BY c.created_at DESC");
    $stmt->execute([':session_id' => $sessionId]);
    $cartItems = $stmt->fetchAll();
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 0; // Можно добавить логику расчета доставки
$total = $subtotal + $shipping;

$pageTitle = 'Корзина';
include 'includes/header.php';
?>

<section class="cart-section">
    <div class="container">
        <h1 class="page-title">Корзина</h1>
        
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <p>Ваша корзина пуста</p>
                <a href="catalog.php" class="btn-primary">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Товар</th>
                                <th>Цена</th>
                                <th>Количество</th>
                                <th>Сумма</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr data-cart-item-id="<?php echo $item['id']; ?>">
                                    <td class="cart-item-info">
                                        <div class="cart-item-image">
                                            <?php if ($item['image']): ?>
                                                <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='<?php echo BASE_URL; ?>assets/images/placeholder.jpg';">
                                            <?php else: ?>
                                                <img src="<?php echo BASE_URL; ?>assets/images/placeholder.jpg" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php endif; ?>
                                        </div>
                                        <div class="cart-item-details">
                                            <a href="product.php?id=<?php echo $item['product_id']; ?>" class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></a>
                                            <div class="cart-item-sku">Артикул: <?php echo htmlspecialchars($item['sku']); ?></div>
                                        </div>
                                    </td>
                                    <td class="cart-item-price">
                                        <?php echo formatPrice($item['price']); ?>
                                    </td>
                                    <td class="cart-item-quantity">
                                        <div class="quantity-selector">
                                            <button class="qty-btn" onclick="updateCartQuantity(<?php echo $item['id']; ?>, -1)">−</button>
                                            <input type="number" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" id="qty-<?php echo $item['id']; ?>">
                                            <button class="qty-btn" onclick="updateCartQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                                        </div>
                                    </td>
                                    <td class="cart-item-total">
                                        <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                    </td>
                                    <td class="cart-item-remove">
                                        <button class="remove-btn" onclick="removeFromCart(<?php echo $item['id']; ?>)">×</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-card">
                        <h2>Итого</h2>
                        <div class="summary-row">
                            <span>Товаров на сумму:</span>
                            <span id="cart-subtotal"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Доставка:</span>
                            <span><?php echo formatPrice($shipping); ?></span>
                        </div>
                        <div class="summary-total">
                            <span>К оплате:</span>
                            <span id="cart-total"><?php echo formatPrice($total); ?></span>
                        </div>
                        
                        <a href="dashboard.php?section=checkout" class="btn-primary btn-large btn-block">Оформить заказ</a>
                        <a href="catalog.php" class="btn-secondary btn-block">Продолжить покупки</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function updateCartQuantity(cartId, change) {
    const input = document.getElementById('qty-' + cartId);
    const current = parseInt(input.value);
    const max = parseInt(input.getAttribute('max'));
    const newValue = Math.max(1, Math.min(max, current + change));
    
    fetch('api/cart-update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cart_id: cartId,
            quantity: newValue
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = newValue;
            updateCartTotals(data);
        }
    });
}

function removeFromCart(cartId) {
    if (!confirm('Удалить товар из корзины?')) return;
    
    fetch('api/cart-remove.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cart_id: cartId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`[data-cart-item-id="${cartId}"]`).remove();
            updateCartTotals(data);
            if (data.cart_count === 0) {
                location.reload();
            }
        }
    });
}

function updateCartTotals(data) {
    if (document.getElementById('cart-subtotal')) {
        document.getElementById('cart-subtotal').textContent = data.subtotal_formatted;
        document.getElementById('cart-total').textContent = data.total_formatted;
    }
    // Update cart count badge in header
    const cartCountBadge = document.getElementById('cart-count-badge');
    if (cartCountBadge) {
        const count = data.cart_count || 0;
        if (count > 0) {
            cartCountBadge.textContent = count;
            cartCountBadge.style.display = 'flex';
        } else {
            cartCountBadge.style.display = 'none';
        }
    }
}
</script>

<?php
include 'includes/footer.php';
?>

