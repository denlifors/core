<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/includes/core-client.php';

$db = getDBConnection();

// Получаем данные пользователя
// Используем $isPartnerUser из dashboard-header.php, если он уже определен
if (!isset($isPartnerUser)) {
    $userStmt = $db->prepare("SELECT id, role, core_partner_id FROM users WHERE id = :user_id");
    $userStmt->execute([':user_id' => $_SESSION['user_id']]);
    $userData = $userStmt->fetch();
    $isPartnerUser = (($userData['role'] ?? 'user') === 'partner') && !empty($userData['core_partner_id']);
} else {
    // Если $isPartnerUser уже определен, получаем только нужные данные пользователя
    $userStmt = $db->prepare("SELECT id, role, core_partner_id FROM users WHERE id = :user_id");
    $userStmt->execute([':user_id' => $_SESSION['user_id']]);
    $userData = $userStmt->fetch();
}

// Получаем баланс кэшбэка
$cashbackBalance = 2300; // Значение по умолчанию для демонстрации
if ($isPartnerUser && !empty($userData['core_partner_id'])) {
    $partnerId = (string)$userData['core_partner_id'];
    // Пытаемся получить баланс кэшбэка из кошелька
    $walletErr = null;
    $walletRes = coreGetJson('/partner-cashback-wallet?partnerId=' . urlencode($partnerId), $walletErr);
    if ($walletRes && ($walletRes['status'] ?? 500) < 400 && !empty($walletRes['data'])) {
        $cashbackBalance = (int)($walletRes['data']['balance'] ?? 0);
    }
}

// Реферальная ссылка (только для партнеров)
$refConsultantId = (string)($userData['id'] ?? '');
$referralLink = '';
if ($isPartnerUser && $refConsultantId !== '') {
    $referralLink = BASE_URL . 'register.php?consultant_id=' . urlencode($refConsultantId);
}

// Берем 3 товара для текущего макета магазина
$productsStmt = $db->query("
    SELECT id, name, price, image, status
    FROM products
    WHERE status = 'active'
    ORDER BY created_at DESC
    LIMIT 3
");
$products = $productsStmt ? $productsStmt->fetchAll() : [];

$cartCount = 0;
if (isLoggedIn()) {
    $cartStmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = :uid");
    $cartStmt->execute([':uid' => $_SESSION['user_id']]);
    $cartCount = (int)$cartStmt->fetchColumn();
} else {
    $cartStmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE session_id = :sid");
    $cartStmt->execute([':sid' => session_id()]);
    $cartCount = (int)$cartStmt->fetchColumn();
}

// Фолбэк-картинки, если у товара в БД нет своего image
$fallbackImages = [
    BASE_URL . 'assets/images/products/image1.png',
    BASE_URL . 'assets/images/products/image2.png',
    BASE_URL . 'assets/images/products/image3.png',
];

// Если из БД пришло меньше 3-х карточек, добиваем заглушками
while (count($products) < 3) {
    $idx = count($products);
    $products[] = [
        'id' => 0,
        'name' => 'Товар ДенЛиФорс',
        'price' => 3000,
        'image' => null,
        'status' => $idx === 0 ? 'active' : 'out_of_stock',
    ];
}
?>

<section class="shop__top">
    <!-- Карточка кэшбэка -->
    <article class="shop__cashbackCard">
        <div class="shop__cashbackTop"></div>
        <div class="shop__cashbackIcon">₽</div>
        <div class="shop__cashbackMetric">
            <span class="shop__cashbackLabel">Кэшбэк:</span>
            <span class="shop__cashbackValue"><?php echo number_format($cashbackBalance, 0, ',', ' '); ?> ₽</span>
        </div>
        <div class="shop__cashbackBottom">
            <a class="shop__cashbackAction" href="#" onclick="return false;">
                <img src="<?php echo $assetsImg; ?>/icons/convert-card.svg" alt="" />
                <span>Операции</span>
            </a>
        </div>
    </article>

    <!-- Карточка реферальной программы (только для партнеров) -->
    <?php if ($isPartnerUser && !empty($referralLink)): ?>
    <article class="shop__referralCard">
        <div class="shop__referralContent">
            <div class="shop__referralText">
                Приглашайте клиентов по вашей ссылке и получайте 10% с каждой покупки. Накапливайте и оплачивайте до 50% с покупки ваших товаров.
            </div>
            <div class="shop__referralLinkCard">
                <span class="shop__referralLinkLabel">Клиентская ссылка:</span>
                <div class="shop__referralLinkRow">
                    <span class="shop__referralLinkText"><?php echo htmlspecialchars($referralLink); ?></span>
                    <div class="shop__referralLinkActions">
                        <button class="shop__referralLinkBtn" type="button" onclick="copyReferralLink()" title="Копировать">
                            <img src="<?php echo $assetsImg; ?>/icons/copy.svg" alt="Копировать" />
                        </button>
                        <button class="shop__referralLinkBtn" type="button" onclick="showQRCode()" title="QR код">
                            <img src="<?php echo $assetsImg; ?>/icons/qr.svg" alt="QR код" />
                        </button>
                        <button class="shop__referralLinkBtn" type="button" onclick="shareReferralLink()" title="Поделиться">
                            <img src="<?php echo $assetsImg; ?>/icons/share.svg" alt="Поделиться" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </article>
    <?php endif; ?>

    <!-- Карточка партнерства -->
    <article class="shop__partnershipCard">
        <div class="shop__partnershipImage">
            <img src="<?php echo $assetsImg; ?>/products/rukopojatie.jpg" alt="Партнерство" />
        </div>
        <button class="shop__partnershipBtn" onclick="window.location.href='partnership.php'">
            Стать партнёром
        </button>
    </article>
</section>

<section class="shop__catalog">
    <div class="shop__catalogHeader">
        <h2 class="shop__catalogTitle">Наша линейка продуктов</h2>
        <button class="shop__catalogCart" type="button" onclick="window.location.href='dashboard.php?section=cart'" aria-label="Корзина">
            <span class="shop__catalogCartIcon">🛒</span>
            <span id="shop-catalog-cart-badge" class="shop__catalogCartBadge <?php echo $cartCount > 0 ? '' : 'is-hidden'; ?>">
                <?php echo $cartCount > 0 ? $cartCount : ''; ?>
            </span>
        </button>
    </div>

    <div class="shop__catalogGrid">
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

                $productUrl = $productId > 0
                    ? ('dashboard.php?section=product&id=' . $productId)
                    : '#';
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

                <div class="shop__productPrice"><?php echo number_format($price, 0, ',', ' '); ?> ₽</div>

                <div class="shop__productMeta">
                    <span>ДенЛиФорс</span>
                    <span class="shop__metaCheck">✓</span>
                    <span>Оригинал</span>
                </div>

                <a class="shop__productName" href="<?php echo htmlspecialchars($productUrl); ?>">
                    <?php echo htmlspecialchars(mb_strimwidth($name, 0, 46, '...')); ?>
                </a>

                <div class="shop__productStock">
                    <span><?php echo $isInStock ? 'В наличии' : 'Нет в наличии'; ?></span>
                    <span class="shop__stockDot <?php echo $isInStock ? 'is-in' : 'is-out'; ?>">✓</span>
                </div>

                <button class="shop__cartBtn" type="button" <?php echo $productId > 0 ? ('onclick="addToCart(' . $productId . ')"') : 'disabled'; ?>>
                    <span class="shop__cartBtnIcon">🛒</span>
                    <span>В корзину</span>
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
            const catalogBadge = document.getElementById('shop-catalog-cart-badge');
            const count = (data && data.success) ? Number(data.count || 0) : 0;
            
            if (badge) {
                if (count > 0) {
                    badge.textContent = String(count);
                    badge.classList.remove('is-hidden');
                } else {
                    badge.textContent = '';
                    badge.classList.add('is-hidden');
                }
            }
            
            if (catalogBadge) {
                if (count > 0) {
                    catalogBadge.textContent = String(count);
                    catalogBadge.classList.remove('is-hidden');
                } else {
                    catalogBadge.textContent = '';
                    catalogBadge.classList.add('is-hidden');
                }
            }
        })
        .catch(() => {});
}

function copyReferralLink() {
    const input = document.querySelector('.shop__referralLinkField');
    if (input) {
        input.select();
        document.execCommand('copy');
        alert('Ссылка скопирована в буфер обмена');
    }
}

function copyReferralLink() {
    const linkText = document.querySelector('.shop__referralLinkText');
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

function showQRCode() {
    const linkText = document.querySelector('.shop__referralLinkText');
    if (linkText) {
        const url = linkText.textContent || '';
        const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(url);
        window.open(qrUrl, '_blank');
    }
}

function shareReferralLink() {
    const linkText = document.querySelector('.shop__referralLinkText');
    if (linkText) {
        const url = linkText.textContent || '';
        if (navigator.share) {
            navigator.share({
                title: 'Реферальная ссылка ДенЛиФорс',
                text: 'Присоединяйтесь к ДенЛиФорс',
                url: url
            }).catch(() => {});
        } else {
            copyReferralLink();
        }
    }
}

function addToCart(productId) {
    fetch('<?php echo BASE_URL; ?>api/cart-add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: 1 })
    })
    .then(r => r.json())
    .then(data => {
        if (!data || !data.success) {
            alert('Не удалось добавить товар в корзину');
            return;
        }
        refreshFloatingCartCount();
    })
    .catch(() => alert('Ошибка добавления в корзину'));
}

document.addEventListener('DOMContentLoaded', refreshFloatingCartCount);
</script>
