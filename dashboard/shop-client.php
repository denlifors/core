<?php
$db = getDBConnection();

$productsStmt = $db->query("
    SELECT id, name, price, image, status
    FROM products
    WHERE status = 'active'
    ORDER BY created_at DESC
    LIMIT 3
");
$products = $productsStmt ? $productsStmt->fetchAll() : [];

$cartCount = 0;
$cartStmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = :uid");
$cartStmt->execute([':uid' => $_SESSION['user_id']]);
$cartCount = (int)$cartStmt->fetchColumn();
$clientCashbackRub = 0;

$fallbackImages = [
    BASE_URL . 'assets/images/products/image1.png',
    BASE_URL . 'assets/images/products/image2.png',
    BASE_URL . 'assets/images/products/image3.png',
];

while (count($products) < 3) {
    $products[] = [
        'id' => 0,
        'name' => 'Товар ДенЛиФорс',
        'price' => 3000,
        'image' => null,
        'status' => 'out_of_stock',
    ];
}

$consultantRef = (string)($registrationId ?: ($userData['id'] ?? ''));
$clientInviteLink = BASE_URL . 'register.php?consultant_id=' . urlencode($consultantRef);
?>

<section class="shopc__heroRow">
    <article class="shopc__cashbackCard">
        <div class="shopc__cashbackTop"></div>
        <img class="shopc__cashbackVector" src="<?php echo $assetsImg; ?>/icons/vector.svg" alt="" />
        <div class="shopc__cashbackCurrency">₽</div>
        <div class="shopc__cashbackMetric">
            <span class="shopc__cashbackLabel">Кэшбэк:</span>
            <span class="shopc__cashbackValue"><?php echo number_format($clientCashbackRub, 0, '.', ' '); ?> ₽</span>
        </div>
        <div class="shopc__cashbackBottom">
            <a class="shopc__cashbackAction" href="#" onclick="return false;">
                <img src="<?php echo $assetsImg; ?>/icons/convert-card.svg" alt="" />
                <span>Операции</span>
            </a>
        </div>
    </article>

    <article class="shopc__leftCard">
        <div class="shopc__leftText">
            Приглашайте клиентов по вашей ссылке и получайте 10% с каждой покупки.
            Накапливайте и оплачивайте до 50% с покупки ваших товаров.
        </div>
        <div class="shopc__linkCard">
            <div class="shopc__linkLabel">Клиентская ссылка:</div>
            <div class="shopc__linkValue" id="shopc-client-link"><?php echo htmlspecialchars($clientInviteLink); ?></div>
            <div class="shopc__linkActions">
                <button type="button" class="shopc__linkBtn" onclick="copyClientShopLink()">⧉</button>
                <button type="button" class="shopc__linkBtn" onclick="window.open('https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(document.getElementById('shopc-client-link').textContent),'_blank')">⌗</button>
                <button type="button" class="shopc__linkBtn" onclick="navigator.share ? navigator.share({url: document.getElementById('shopc-client-link').textContent}) : copyClientShopLink()">↗</button>
            </div>
        </div>
    </article>

    <article class="shopc__activateCard">
        <button type="button" class="shopc__activateBtn" onclick="window.location.href='dashboard.php?section=cart'">Стать партнёром</button>
    </article>
</section>

<section class="shopc__catalog">
    <h2 class="shopc__title">Наша линейка продуктов</h2>
    <div class="shop__catalogGrid shopc__grid">
        <?php foreach ($products as $idx => $product): ?>
            <?php
                $productId = (int)($product['id'] ?? 0);
                $name = trim((string)($product['name'] ?? 'Товар ДенЛиФорс'));
                $name = $name !== '' ? $name : 'Товар ДенЛиФорс';
                $price = (float)($product['price'] ?? 3000);
                $status = (string)($product['status'] ?? 'active');
                $isInStock = ($status === 'active');
                $productImage = $fallbackImages[$idx] ?? $fallbackImages[0];
                if (!empty($product['image'])) {
                    $candidate = (string)$product['image'];
                    if (preg_match('#^https?://#i', $candidate)) {
                        $productImage = $candidate;
                    } else {
                        $uploadPath = ROOT_PATH . '/uploads/products/' . $candidate;
                        if (is_file($uploadPath)) {
                            $productImage = BASE_URL . 'uploads/products/' . rawurlencode($candidate);
                        }
                    }
                }
                $productUrl = $productId > 0 ? ('dashboard.php?section=product&id=' . $productId) : '#';
            ?>
            <article class="shop__productCard">
                <a class="shop__productImageWrap" href="<?php echo htmlspecialchars($productUrl); ?>">
                    <img
                        class="shop__productImage"
                        src="<?php echo htmlspecialchars($productImage); ?>"
                        alt="<?php echo htmlspecialchars($name); ?>"
                        onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallbackImages[$idx] ?? $fallbackImages[0]); ?>';"
                    />
                </a>

                <div class="shop__productDots">
                    <span class="is-active"></span><span></span><span></span><span></span><span></span>
                </div>
                <div class="shop__productPrice"><?php echo number_format($price, 0, ',', ' '); ?> ₽ (200DV)</div>
                <div class="shop__productMeta">
                    <span>ДенЛиФорс</span><span class="shop__metaCheck">✓</span><span>Оригинал</span>
                </div>
                <a class="shop__productName" href="<?php echo htmlspecialchars($productUrl); ?>">
                    <?php echo htmlspecialchars(mb_strimwidth($name, 0, 46, '...')); ?>
                </a>
                <div class="shop__productStock">
                    <span><?php echo $isInStock ? 'В наличии' : 'Нет в наличии'; ?></span>
                    <span class="shop__stockDot <?php echo $isInStock ? 'is-in' : 'is-out'; ?>">✓</span>
                </div>
                <button class="shop__cartBtn" type="button" <?php echo $productId > 0 ? ('onclick="addToCart(' . $productId . ')"') : 'disabled'; ?>>
                    🛒 В корзину
                </button>
            </article>
        <?php endforeach; ?>
    </div>

    <button class="shop__floatingCart" type="button" aria-label="Корзина" onclick="window.location.href='dashboard.php?section=cart'">
        <span class="shop__floatingCartIcon">🛒</span>
        <span id="shop-floating-cart-badge" class="shop__floatingCartBadge <?php echo $cartCount > 0 ? '' : 'is-hidden'; ?>">
            <?php echo $cartCount > 0 ? $cartCount : ''; ?>
        </span>
    </button>
</section>

<script>
function refreshFloatingCartCount() {
  fetch('<?php echo BASE_URL; ?>api/cart-count.php')
    .then((r) => r.json())
    .then((data) => {
      const badge = document.getElementById('shop-floating-cart-badge');
      if (!badge) return;
      const count = (data && data.success) ? Number(data.count || 0) : 0;
      badge.textContent = count > 0 ? String(count) : '';
      badge.classList.toggle('is-hidden', count < 1);
    })
    .catch(() => {});
}
function addToCart(productId) {
  fetch('<?php echo BASE_URL; ?>api/cart-add.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ product_id: productId, quantity: 1 })
  })
  .then((r) => r.json())
  .then((data) => {
    if (!data || !data.success) {
      alert((data && data.error) ? data.error : 'Не удалось добавить товар в корзину');
      return;
    }
    refreshFloatingCartCount();
  })
  .catch(() => alert('Ошибка добавления в корзину'));
}
function copyClientShopLink() {
  const text = document.getElementById('shopc-client-link')?.textContent || '';
  if (!text) return;
  navigator.clipboard.writeText(text).catch(() => {});
}
document.addEventListener('DOMContentLoaded', refreshFloatingCartCount);
</script>
