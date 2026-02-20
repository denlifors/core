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

<!-- Верхняя секция с карточками -->
<section class="shopc__top">
    <!-- Карточка кэшбэка -->
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

    <!-- Карточка с текстом приглашения -->
    <article class="shopc__leftCard">
        <div class="shopc__leftContent">
            <div class="shopc__leftText">
                Приглашайте клиентов по вашей ссылке и получайте 10% с каждой покупки. Накапливайте и оплачивайте до 50% с покупки ваших товаров.
            </div>
            <div class="shopc__linkCard">
                <span class="shopc__linkLabel">Клиентская ссылка:</span>
                <div class="shopc__linkRow">
                    <span class="shopc__linkText" id="shopc-client-link"><?php echo htmlspecialchars($clientInviteLink); ?></span>
                    <div class="shopc__linkActions">
                        <button type="button" class="shopc__linkBtn" onclick="copyClientShopLink()" title="Копировать">
                            <img src="<?php echo $assetsImg; ?>/icons/copy.svg" alt="Копировать" />
                        </button>
                        <button type="button" class="shopc__linkBtn" onclick="showQRCodeClient()" title="QR код">
                            <img src="<?php echo $assetsImg; ?>/icons/qr.svg" alt="QR код" />
                        </button>
                        <button type="button" class="shopc__linkBtn" onclick="shareClientShopLink()" title="Поделиться">
                            <img src="<?php echo $assetsImg; ?>/icons/share.svg" alt="Поделиться" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </article>

    <!-- Карточка "Стать партнёром" с фоном -->
    <article class="shopc__activateCard">
        <div class="shopc__activateBg">
            <img class="shopc__activateImg" src="<?php echo $assetsImg; ?>/products/rukopojatie.jpg" alt="" />
        </div>
        <button type="button" class="shopc__activateBtn" onclick="window.location.href='partnership.php'">Стать партнёром</button>
    </article>
</section>

<!-- Секция каталога -->
<section class="shopc__catalog">
    <div class="shopc__catalogHeader">
        <h2 class="shopc__catalogTitle">Наша линейка продуктов</h2>
        <button class="shopc__catalogCart" type="button" onclick="window.location.href='dashboard.php?section=cart'" aria-label="Корзина">
            <span class="shopc__catalogCartIcon">
                <img src="<?php echo $assetsImg; ?>/icons/Basket.svg" alt="Корзина" />
            </span>
            <span id="shopc-catalog-cart-badge" class="shopc__catalogCartBadge <?php echo $cartCount > 0 ? '' : 'is-hidden'; ?>">
                <?php echo $cartCount > 0 ? $cartCount : ''; ?>
            </span>
        </button>
    </div>
    <div class="shopc__catalogGrid">
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
            <article class="shopc__productCard">
                <a class="shopc__productImageWrap" href="<?php echo htmlspecialchars($productUrl); ?>">
                    <img
                        class="shopc__productImage"
                        src="<?php echo htmlspecialchars($productImage); ?>"
                        alt="<?php echo htmlspecialchars($name); ?>"
                        onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallbackImages[$idx] ?? $fallbackImages[0]); ?>';"
                    />
                </a>

                <div class="shopc__productDots">
                    <span class="is-active"></span><span></span><span></span><span></span><span></span>
                </div>

                <div class="shopc__productPrice"><?php echo number_format($price, 0, ',', ' '); ?> ₽</div>

                <div class="shopc__productMeta">
                    <span>ДенЛиФорс</span>
                    <span class="shopc__metaCheck">✓</span>
                    <span>Оригинал</span>
                </div>

                <a class="shopc__productName" href="<?php echo htmlspecialchars($productUrl); ?>">
                    <?php echo htmlspecialchars(mb_strimwidth($name, 0, 46, '...')); ?>
                </a>

                <div class="shopc__productStock">
                    <span><?php echo $isInStock ? 'В наличии' : 'Нет в наличии'; ?></span>
                    <span class="shopc__stockDot <?php echo $isInStock ? 'is-in' : 'is-out'; ?>">✓</span>
                </div>

                <button class="shopc__cartBtn" type="button" <?php echo $productId > 0 ? ('onclick="addToCart(' . $productId . ')"') : 'disabled'; ?>>
                    🛒 В корзину
                </button>
            </article>
        <?php endforeach; ?>
    </div>

</section>

<script>
function refreshFloatingCartCount() {
  fetch('<?php echo BASE_URL; ?>api/cart-count.php')
    .then((r) => r.json())
    .then((data) => {
      const catalogBadge = document.getElementById('shopc-catalog-cart-badge');
      const count = (data && data.success) ? Number(data.count || 0) : 0;
      
      if (catalogBadge) {
        catalogBadge.textContent = count > 0 ? String(count) : '';
        catalogBadge.classList.toggle('is-hidden', count < 1);
      }
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
  const linkText = document.getElementById('shopc-client-link');
  if (linkText) {
    const text = linkText.textContent || '';
    navigator.clipboard.writeText(text).then(() => {
      alert('Ссылка скопирована в буфер обмена');
    }).catch(() => {
      // Fallback для старых браузеров
      const textarea = document.createElement('textarea');
      textarea.value = text;
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
      alert('Ссылка скопирована в буфер обмена');
    });
  }
}

function showQRCodeClient() {
  const linkText = document.getElementById('shopc-client-link');
  if (linkText) {
    const url = linkText.textContent || '';
    const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(url);
    window.open(qrUrl, '_blank');
  }
}

function shareClientShopLink() {
  const linkText = document.getElementById('shopc-client-link');
  if (linkText) {
    const url = linkText.textContent || '';
    if (navigator.share) {
      navigator.share({
        title: 'Реферальная ссылка ДенЛиФорс',
        text: 'Присоединяйтесь к ДенЛиФорс',
        url: url
      }).catch(() => {});
    } else {
      copyClientShopLink();
    }
  }
}
document.addEventListener('DOMContentLoaded', refreshFloatingCartCount);
</script>
