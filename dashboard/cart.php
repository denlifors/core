<?php
// Подключаем config если еще не подключен
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

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
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 0; // Можно добавить логику расчета доставки
$total = $subtotal + $shipping;
?>

<div class="dashboard-cart">
    <div class="dashboard-cart-header">
        <h1 class="dashboard-cart-title">Корзина</h1>
    </div>
    
    <?php if (empty($cartItems)): ?>
        <div class="dashboard-empty-cart">
            <div class="dashboard-empty-cart-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6l-3-4H6zM3 6h18M16 10a4 4 0 11-8 0"/>
                </svg>
            </div>
            <p class="dashboard-empty-cart-text">Ваша корзина пуста</p>
            <a href="dashboard.php?section=shop" class="dashboard-btn-primary">Перейти в магазин</a>
        </div>
    <?php else: ?>
        <div class="dashboard-cart-content">
            <div class="dashboard-cart-items">
                <?php foreach ($cartItems as $item): ?>
                    <div class="dashboard-cart-item" data-cart-item-id="<?php echo $item['id']; ?>">
                        <div class="dashboard-cart-item-image">
                            <?php if ($item['image']): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.style.display='none';">
                            <?php else: ?>
                                <div class="dashboard-cart-item-placeholder">Нет фото</div>
                            <?php endif; ?>
                        </div>
                        <div class="dashboard-cart-item-info">
                            <h3 class="dashboard-cart-item-name">
                                <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                            </h3>
                            <div class="dashboard-cart-item-sku">Артикул: <?php echo htmlspecialchars($item['sku']); ?></div>
                            <div class="dashboard-cart-item-price"><?php echo formatPrice($item['price']); ?></div>
                        </div>
                        <div class="dashboard-cart-item-quantity">
                            <div class="dashboard-quantity-selector">
                                <button class="dashboard-qty-btn" onclick="updateCartQuantity(<?php echo $item['id']; ?>, -1)">−</button>
                                <input type="number" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" id="qty-<?php echo $item['id']; ?>" class="dashboard-qty-input">
                                <button class="dashboard-qty-btn" onclick="updateCartQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                            </div>
                        </div>
                        <div class="dashboard-cart-item-total">
                            <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                        </div>
                        <button class="dashboard-cart-item-remove" onclick="removeFromCart(<?php echo $item['id']; ?>)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="dashboard-cart-summary">
                <div class="dashboard-summary-card">
                    <h3 class="dashboard-summary-title">Итого</h3>
                    <div class="dashboard-summary-row">
                        <span>Товары:</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="dashboard-summary-row">
                        <span>Доставка:</span>
                        <span><?php echo formatPrice($shipping); ?></span>
                    </div>
                    <div class="dashboard-summary-divider"></div>
                    <div class="dashboard-summary-row dashboard-summary-total">
                        <span>Всего:</span>
                        <span><?php echo formatPrice($total); ?></span>
                    </div>
                    <a href="dashboard.php?section=checkout" class="dashboard-checkout-btn">Оформить заказ</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateCartQuantity(itemId, change) {
    const input = document.getElementById('qty-' + itemId);
    const currentQty = parseInt(input.value) || 1;
    const newQty = Math.max(1, currentQty + change);
    const maxQty = parseInt(input.getAttribute('max')) || 999;
    const finalQty = Math.min(newQty, maxQty);
    
    input.value = finalQty;
    
    const baseUrl = window.BASE_URL || '<?php echo BASE_URL; ?>';
    fetch(baseUrl + 'api/cart-update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cart_id: itemId,
            quantity: finalQty
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + (data.message || 'Не удалось обновить количество'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при обновлении количества');
    });
}

function removeFromCart(itemId) {
    if (!confirm('Удалить товар из корзины?')) {
        return;
    }
    
    const baseUrl = window.BASE_URL || '<?php echo BASE_URL; ?>';
    fetch(baseUrl + 'api/cart-remove.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cart_id: itemId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + (data.message || 'Не удалось удалить товар'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при удалении товара');
    });
}

// Обновление счетчика корзины
document.addEventListener('DOMContentLoaded', function() {
    updateDashboardCartCount();
});

function updateDashboardCartCount() {
    fetch('<?php echo BASE_URL; ?>api/cart-count.php')
        .then(response => response.json())
        .then(data => {
            const cartCount = document.getElementById('dashboard-cart-count');
            const count = data.count || 0;
            if (cartCount) {
                if (count > 0) {
                    cartCount.textContent = count;
                    cartCount.style.display = 'flex';
                } else {
                    cartCount.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}
</script>

