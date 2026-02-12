<?php
if (!isset($_GET['id'])) {
    redirect('dashboard.php?section=shop');
}

$db = getDBConnection();
$productId = (int)$_GET['id'];

$stmt = $db->prepare("
    SELECT p.*
    FROM products p
    WHERE p.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('dashboard.php?section=shop');
}

$price = (float)($product['price'] ?? 0);
$productName = trim((string)($product['name'] ?? 'Товар ДенЛиФорс'));
$productName = $productName !== '' ? $productName : 'Товар ДенЛиФорс';

$imageUrl = BASE_URL . 'assets/images/products/image1.png';
if (!empty($product['image'])) {
    $candidate = (string)$product['image'];
    if (preg_match('#^https?://#i', $candidate)) {
        $imageUrl = $candidate;
    } elseif (is_file(ROOT_PATH . '/uploads/products/' . $candidate)) {
        $imageUrl = BASE_URL . 'uploads/products/' . rawurlencode($candidate);
    }
}

$whatIsItText = trim((string)($product['full_description'] ?? $product['description'] ?? ''));
if ($whatIsItText === '') {
    $whatIsItText = 'Описание товара будет добавлено позже.';
}

$advantages = [];
if (!empty($product['what_is_it'])) {
    $decoded = json_decode($product['what_is_it'], true);
    if (is_array($decoded) && !empty($decoded['advantages']) && is_array($decoded['advantages'])) {
        $advantages = array_values(array_filter(array_map('trim', $decoded['advantages'])));
    }
}
if (empty($advantages)) {
    $advantages = [
        'Ведут активный образ жизни',
        'Заботятся о своем здоровье',
        'Хотят сохранить молодость и красоту как можно дольше',
        'Стремятся к активному долголетию',
    ];
}

$relatedStmt = $db->prepare("
    SELECT id, name, sku, price, image
    FROM products
    WHERE id != :id AND status = 'active'
    ORDER BY created_at DESC
    LIMIT 2
");
$relatedStmt->execute([':id' => $productId]);
$relatedProducts = $relatedStmt->fetchAll();

while (count($relatedProducts) < 2) {
    $relatedProducts[] = [
        'id' => 0,
        'name' => 'Формула сна',
        'sku' => 'di- 487295037',
        'price' => 3000,
        'image' => null,
    ];
}
?>

<div class="dashboard-product">
    <a href="dashboard.php?section=shop" class="dashboard-product-back">
        <svg viewBox="0 0 24 24" fill="none">
            <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Назад
    </a>

    <section class="productv2">
        <div class="productv2__imageCard">
            <img
                class="productv2__image"
                src="<?php echo htmlspecialchars($imageUrl); ?>"
                alt="<?php echo htmlspecialchars($productName); ?>"
                onerror="this.onerror=null;this.src='<?php echo BASE_URL; ?>assets/images/products/image1.png';"
            />
        </div>

        <div class="productv2__infoCard">
            <h2 class="productv2__title"><?php echo htmlspecialchars(mb_strimwidth($productName, 0, 60, '...')); ?></h2>
            <p class="productv2__desc"><?php echo htmlspecialchars(mb_strimwidth($whatIsItText, 0, 260, '...')); ?></p>
        </div>

        <div class="productv2__benefitsCard">
            <h3 class="productv2__subTitle"><?php echo htmlspecialchars(mb_strimwidth($productName, 0, 60, '...')); ?></h3>
            <div class="productv2__forWho">Для людей, которые:</div>
            <div class="productv2__chips">
                <?php foreach (array_slice($advantages, 0, 4) as $i => $adv): ?>
                    <div class="productv2__chip productv2__chip--<?php echo $i + 1; ?>">
                        <span class="productv2__chipDot"></span>
                        <span><?php echo htmlspecialchars($adv); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="productv2__docsCard">
            <h4 class="productv2__docsTitle">Товар соответствует высоким стандартам качества</h4>
            <p class="productv2__docsText">Мы используем современные технологии для изготовления нашей продукции</p>
            <p class="productv2__docsSub">Ознакомиться с документами:</p>

            <a class="productv2__docBtn" href="#" onclick="return false;">
                <img src="<?php echo $assetsImg; ?>/icons/document-box.svg" alt="" />
                <span>ДЕКЛАРАЦИЯ СООТВЕТСТВИЯ</span>
            </a>
            <a class="productv2__docBtn" href="#" onclick="return false;">
                <img src="<?php echo $assetsImg; ?>/icons/document-box.svg" alt="" />
                <span>СЕРТИФИКАТ ПРОИЗВОДСТВА</span>
            </a>
        </div>

        <button class="productv2__accordionRow" type="button" onclick="toggleProductRow(this)">
            <span>Описание товара</span>
            <span class="productv2__arrow">⌄</span>
        </button>
        <div class="productv2__accordionBody">
            <?php echo nl2br(htmlspecialchars($whatIsItText)); ?>
        </div>

        <button class="productv2__accordionRow productv2__accordionRow--second" type="button" onclick="toggleProductRow(this)">
            <span>Преимущества товара</span>
            <span class="productv2__arrow">⌄</span>
        </button>
        <div class="productv2__accordionBody productv2__accordionBody--second">
            <?php echo htmlspecialchars(implode(' • ', $advantages)); ?>
        </div>

        <div class="productv2__buyBar">
            <div class="productv2__pay">
                <span>К оплате:</span>
                <strong><?php echo number_format($price, 0, ',', ' '); ?> ₽</strong>
            </div>
            <button class="productv2__buyBtn" type="button" onclick="addToCart(<?php echo $productId; ?>)">
                🛒 В корзину
            </button>
        </div>

        <section class="productv2__related">
            <?php foreach ($relatedProducts as $related): ?>
                <?php
                    $rId = (int)($related['id'] ?? 0);
                    $rName = trim((string)($related['name'] ?? 'Формула сна'));
                    $rSku = trim((string)($related['sku'] ?? 'di- 487295037'));
                    $rPrice = (float)($related['price'] ?? 3000);

                    $rImage = BASE_URL . 'assets/images/products/image2.png';
                    if (!empty($related['image'])) {
                        $cand = (string)$related['image'];
                        if (preg_match('#^https?://#i', $cand)) {
                            $rImage = $cand;
                        } elseif (is_file(ROOT_PATH . '/uploads/products/' . $cand)) {
                            $rImage = BASE_URL . 'uploads/products/' . rawurlencode($cand);
                        }
                    }
                ?>
                <article class="productv2__relatedCard">
                    <div class="productv2__relatedHead">С этим товаром покупают</div>

                    <div class="productv2__relatedBody">
                        <div class="productv2__relatedThumb">
                            <img src="<?php echo htmlspecialchars($rImage); ?>" alt="<?php echo htmlspecialchars($rName); ?>" onerror="this.onerror=null;this.src='<?php echo BASE_URL; ?>assets/images/products/image2.png';" />
                        </div>
                        <div class="productv2__relatedInfo">
                            <div class="productv2__relatedName"><?php echo htmlspecialchars($rName); ?></div>
                            <div class="productv2__relatedSku">Артикул: <?php echo htmlspecialchars($rSku); ?></div>
                            <div class="productv2__relatedPrice"><?php echo number_format($rPrice, 0, ',', ' '); ?> ₽ (100 DV)</div>
                        </div>
                        <button class="productv2__relatedAdd" type="button" <?php echo $rId > 0 ? ('onclick="addToCart(' . $rId . ')"') : 'disabled'; ?>>
                            Добавить
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </section>
</div>

<script>
function addToCart(productId) {
    fetch('<?php echo BASE_URL; ?>api/cart-add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: 1 })
    }).catch(() => {});
}

function toggleProductRow(btn) {
    const body = btn.nextElementSibling;
    if (!body || !body.classList.contains('productv2__accordionBody')) return;
    body.classList.toggle('is-open');
    btn.classList.toggle('is-open');
}
</script>

