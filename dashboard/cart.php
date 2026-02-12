<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$db = getDBConnection();

if (isLoggedIn()) {
    $stmt = $db->prepare("
        SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image, p.sku
        FROM cart c
        JOIN products p ON p.id = c.product_id
        WHERE c.user_id = :uid
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
} else {
    $stmt = $db->prepare("
        SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image, p.sku
        FROM cart c
        JOIN products p ON p.id = c.product_id
        WHERE c.session_id = :sid
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([':sid' => session_id()]);
}
$cartItems = $stmt->fetchAll();

$relatedStmt = $db->query("
    SELECT id, name, sku, price, image
    FROM products
    WHERE status = 'active'
    ORDER BY created_at DESC
    LIMIT 2
");
$relatedProducts = $relatedStmt ? $relatedStmt->fetchAll() : [];

$subtotalRub = 0.0;
foreach ($cartItems as $item) {
    $subtotalRub += ((float)$item['price'] * (int)$item['quantity']);
}
$subtotalDv = $subtotalRub / 30;

$isPartner = false;
if (isLoggedIn()) {
    $u = $db->prepare("SELECT core_partner_id FROM users WHERE id = :id LIMIT 1");
    $u->execute([':id' => $_SESSION['user_id']]);
    $uRow = $u->fetch();
    $isPartner = !empty($uRow['core_partner_id']);
}

// Маркетинговая логика скидки (текущая версия):
// - Партнер: 2/3/5% по порогу DV (100/200)
// - Клиент: 15%
$discountPercent = 0;
if ($isPartner) {
    if ($subtotalDv >= 200) {
        $discountPercent = 5;
    } elseif ($subtotalDv >= 100) {
        $discountPercent = 3;
    } else {
        $discountPercent = 2;
    }
} else {
    $discountPercent = 15;
}

$discountRub = $subtotalRub * ($discountPercent / 100);
$payRub = max(0, $subtotalRub - $discountRub);
$payDv = $payRub / 30;

$orderNumber = 437000 + (int)($cartItems[0]['cart_id'] ?? 578);
?>

<section class="cartx">
    <a href="dashboard.php?section=shop" class="cartx__back">
        <svg viewBox="0 0 24 24" fill="none">
            <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Назад
    </a>

    <h2 class="cartx__orderTitle">Заказ №<?php echo (int)$orderNumber; ?></h2>

    <div class="cartx__wrap">
        <div class="cartx__left">
            <div class="cartx__tableHead">
                <span>Товар</span>
                <span>Цена в (₽)</span>
                <span>Цена в (DV)</span>
                <span>Количество</span>
                <span>Сумма в (₽)</span>
                <span>Сумма в (DV)</span>
            </div>

            <div class="cartx__rows">
                <?php if (empty($cartItems)): ?>
                    <div class="cartx__emptyRow">Корзина пуста</div>
                <?php else: ?>
                    <?php foreach ($cartItems as $item): ?>
                        <?php
                            $name = trim((string)($item['name'] ?? 'Товар'));
                            $name = $name !== '' ? $name : 'Товар';
                            $sku = trim((string)($item['sku'] ?? '—'));
                            $qty = (int)($item['quantity'] ?? 1);
                            $priceRub = (float)($item['price'] ?? 0);
                            $priceDv = $priceRub / 30;
                            $sumRub = $priceRub * $qty;
                            $sumDv = $priceDv * $qty;

                            $img = BASE_URL . 'assets/images/products/image1.png';
                            if (!empty($item['image'])) {
                                $candidate = (string)$item['image'];
                                if (preg_match('#^https?://#i', $candidate)) {
                                    $img = $candidate;
                                } elseif (is_file(ROOT_PATH . '/uploads/products/' . $candidate)) {
                                    $img = BASE_URL . 'uploads/products/' . rawurlencode($candidate);
                                }
                            }
                        ?>
                        <article class="cartx__row" data-cart-id="<?php echo (int)$item['cart_id']; ?>">
                            <div class="cartx__productCol">
                                <div class="cartx__thumb"><img src="<?php echo htmlspecialchars($img); ?>" alt="" /></div>
                                <div>
                                    <div class="cartx__name"><?php echo htmlspecialchars($name); ?></div>
                                    <div class="cartx__sku">Артикул: <?php echo htmlspecialchars($sku); ?></div>
                                </div>
                            </div>

                            <div class="cartx__cell"><?php echo number_format($priceRub, 0, ',', ' '); ?> ₽</div>
                            <div class="cartx__cell"><?php echo number_format($priceDv, 0, ',', ' '); ?> DV</div>

                            <div class="cartx__qty">
                                <button type="button" onclick="changeCartQty(<?php echo (int)$item['cart_id']; ?>, -1)">−</button>
                                <input id="cart-qty-<?php echo (int)$item['cart_id']; ?>" type="number" min="1" value="<?php echo $qty; ?>" onchange="setCartQty(<?php echo (int)$item['cart_id']; ?>, this.value)" />
                                <button type="button" onclick="changeCartQty(<?php echo (int)$item['cart_id']; ?>, 1)">+</button>
                            </div>

                            <div class="cartx__cell"><?php echo number_format($sumRub, 0, ',', ' '); ?> ₽</div>
                            <div class="cartx__cell"><?php echo number_format($sumDv, 0, ',', ' '); ?> DV</div>

                            <button class="cartx__remove" type="button" onclick="removeCartItem(<?php echo (int)$item['cart_id']; ?>)">✕</button>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="cartx__related">
                <?php foreach ($relatedProducts as $related): ?>
                    <?php
                        $rId = (int)($related['id'] ?? 0);
                        $rName = trim((string)($related['name'] ?? 'Формула сна'));
                        $rSku = trim((string)($related['sku'] ?? 'di- 487295037'));
                        $rPrice = (float)($related['price'] ?? 3000);

                        $rImg = BASE_URL . 'assets/images/products/image2.png';
                        if (!empty($related['image'])) {
                            $cand = (string)$related['image'];
                            if (preg_match('#^https?://#i', $cand)) {
                                $rImg = $cand;
                            } elseif (is_file(ROOT_PATH . '/uploads/products/' . $cand)) {
                                $rImg = BASE_URL . 'uploads/products/' . rawurlencode($cand);
                            }
                        }
                    ?>
                    <article class="cartx__relatedCard">
                        <div class="cartx__relatedHead">С этим товаром покупают</div>
                        <div class="cartx__relatedBody">
                            <div class="cartx__relatedThumb"><img src="<?php echo htmlspecialchars($rImg); ?>" alt="" /></div>
                            <div class="cartx__relatedInfo">
                                <div class="cartx__relatedName"><?php echo htmlspecialchars($rName); ?></div>
                                <div class="cartx__relatedSku">Артикул: <?php echo htmlspecialchars($rSku); ?></div>
                                <div class="cartx__relatedPrice"><?php echo number_format($rPrice, 0, ',', ' '); ?> ₽ (100 DV)</div>
                            </div>
                            <button class="cartx__relatedAdd" type="button" <?php echo $rId > 0 ? ('onclick="addToCart(' . $rId . ')"') : 'disabled'; ?>>Добавить</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <aside class="cartx__summary">
            <h3>Итого</h3>
            <div class="cartx__line"></div>

            <div class="cartx__row2"><span>Товар на сумму:</span><strong><?php echo number_format($subtotalRub, 0, ',', ' '); ?> ₽ (<?php echo number_format($subtotalDv, 0, ',', ' '); ?> DV)</strong></div>
            <div class="cartx__line"></div>
            <div class="cartx__row2"><span>Ваша скидка:</span><strong id="cart-discount-percent"><?php echo $discountPercent; ?>%</strong></div>
            <div class="cartx__line"></div>

            <div class="cartx__cashbackAsk">Использовать кэшбэк?</div>
            <div class="cartx__cashbackToggle">
                <button type="button" class="is-active" onclick="setUseCashback(true, this)">Да</button>
                <button type="button" onclick="setUseCashback(false, this)">Нет</button>
            </div>

            <div class="cartx__line"></div>
            <div class="cartx__toPay">К оплате:</div>
            <div class="cartx__toPayValue" id="cart-to-pay"><?php echo number_format($payRub, 0, ',', ' '); ?> ₽ (<?php echo number_format($payDv, 0, ',', ' '); ?> DV)</div>
            <div class="cartx__line"></div>

            <button class="cartx__addrBtn" type="button" onclick="openQuickOrderModal()">Адрес доставки</button>
            <button class="cartx__checkoutBtn" type="button" onclick="openQuickOrderModal()">Оформить заказ</button>

            <div class="cartx__safe">🛡 Оплата защищена</div>
            <div class="cartx__warn">⚠ Стоимость доставки каждого заказа рассчитывается индивидуально исходя из региона и оплачивается отдельно покупателем по приходу товара</div>
        </aside>
    </div>
</section>

<div class="cartxModal" id="quick-order-modal" hidden>
    <div class="cartxModal__backdrop" onclick="closeQuickOrderModal()"></div>
    <div class="cartxModal__card" role="dialog" aria-modal="true" aria-labelledby="quick-order-title">
        <button class="cartxModal__close" type="button" onclick="closeQuickOrderModal()" aria-label="Закрыть">✕</button>
        <h3 class="cartxModal__title" id="quick-order-title">Подтверждение покупки</h3>
        <p class="cartxModal__text">Нажмите "Купить товары", чтобы сразу оформить заказ из корзины и запустить расчеты в системе.</p>
        <div class="cartxModal__actions">
            <button class="cartxModal__btn cartxModal__btn--cancel" type="button" onclick="closeQuickOrderModal()">Отмена</button>
            <button class="cartxModal__btn cartxModal__btn--buy" type="button" id="quick-order-submit" onclick="submitQuickOrder()">Купить товары</button>
        </div>
    </div>
</div>

<script>
const cartRawSubtotal = <?php echo json_encode((float)$subtotalRub); ?>;
const cartDiscountPercent = <?php echo json_encode((int)$discountPercent); ?>;

function formatRub(v) {
    return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 }).format(v) + ' ₽';
}
function formatDv(v) {
    return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 }).format(v) + ' DV';
}

function recalcPay(useCashback) {
    const discount = useCashback ? (cartRawSubtotal * cartDiscountPercent / 100) : 0;
    const pay = Math.max(0, cartRawSubtotal - discount);
    const payDv = pay / 30;
    const node = document.getElementById('cart-to-pay');
    if (node) node.textContent = `${formatRub(pay)} (${formatDv(payDv)})`;
}

function setUseCashback(use, btn) {
    const wrap = btn.closest('.cartx__cashbackToggle');
    if (!wrap) return;
    wrap.querySelectorAll('button').forEach((b) => b.classList.remove('is-active'));
    btn.classList.add('is-active');
    recalcPay(use);
}

function changeCartQty(cartId, delta) {
    const input = document.getElementById('cart-qty-' + cartId);
    if (!input) return;
    const current = parseInt(input.value || '1', 10) || 1;
    const next = Math.max(1, current + delta);
    input.value = next;
    setCartQty(cartId, next);
}
function setCartQty(cartId, qty) {
    const quantity = Math.max(1, parseInt(qty || '1', 10) || 1);
    fetch('<?php echo BASE_URL; ?>api/cart-update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart_id: cartId, quantity: quantity })
    }).then((r) => r.json()).then((data) => {
        if (!data || !data.success) {
            alert((data && data.error) ? ('Не удалось обновить количество: ' + data.error) : 'Не удалось обновить количество');
            return;
        }
        window.location.reload();
    }).catch((e) => alert('Ошибка обновления корзины: ' + (e && e.message ? e.message : 'network')));
}
function removeCartItem(cartId) {
    fetch('<?php echo BASE_URL; ?>api/cart-remove.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart_id: cartId })
    }).then((r) => r.json()).then((data) => {
        if (!data || !data.success) {
            alert('Не удалось удалить товар');
            return;
        }
        window.location.reload();
    }).catch(() => alert('Ошибка удаления товара'));
}
function addToCart(productId) {
    fetch('<?php echo BASE_URL; ?>api/cart-add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: 1 })
    }).then((r) => r.json()).then((data) => {
        if (!data || !data.success) {
            alert('Не удалось добавить товар');
            return;
        }
        window.location.reload();
    }).catch(() => alert('Ошибка добавления товара'));
}

function openQuickOrderModal() {
    const modal = document.getElementById('quick-order-modal');
    if (!modal) return;
    modal.hidden = false;
}
function closeQuickOrderModal() {
    const modal = document.getElementById('quick-order-modal');
    if (!modal) return;
    modal.hidden = true;
}
function submitQuickOrder() {
    const btn = document.getElementById('quick-order-submit');
    if (!btn) return;
    btn.disabled = true;
    btn.textContent = 'Покупка...';

    fetch('<?php echo BASE_URL; ?>api/cart-place-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({})
    })
    .then((r) => r.json())
    .then((data) => {
        if (!data || !data.success) {
            alert((data && data.error) ? ('Не удалось оформить заказ: ' + data.error) : 'Не удалось оформить заказ');
            btn.disabled = false;
            btn.textContent = 'Купить товары';
            return;
        }
        closeQuickOrderModal();
        window.location.href = data.redirect || '<?php echo BASE_URL; ?>dashboard.php?section=team';
    })
    .catch((e) => {
        alert('Ошибка оформления заказа: ' + (e && e.message ? e.message : 'network'));
        btn.disabled = false;
        btn.textContent = 'Купить товары';
    });
}
</script>
