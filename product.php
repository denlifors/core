<?php
require_once 'config/config.php';

if (!isset($_GET['id'])) {
    redirect('catalog.php');
}

$db = getDBConnection();
$productId = (int)$_GET['id'];

// Get product
$stmt = $db->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.id = :id AND p.status = 'active'");
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('catalog.php');
}

// Increment view count
$db->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = :id")->execute([':id' => $productId]);

// Get related products
$relatedStmt = $db->prepare("SELECT * FROM products 
                             WHERE category_id = :category_id AND id != :id AND status = 'active' 
                             LIMIT 4");
$relatedStmt->execute([':category_id' => $product['category_id'], ':id' => $productId]);
$relatedProducts = $relatedStmt->fetchAll();

// Parse images
$images = [];
if ($product['images']) {
    $images = json_decode($product['images'], true);
    if (!is_array($images)) {
        $images = [];
    }
}
if ($product['image']) {
    array_unshift($images, $product['image']);
}

// Parse documents
$documents = [];
if (!empty($product['documentation'])) {
    $decoded = json_decode($product['documentation'], true);
    if (is_array($decoded)) {
        $documents = $decoded;
    }
}

// Parse "Что это такое?"
$whatIsIt = [
    'description' => '',
    'consists_of' => [],
    'release_form' => [],
    'how_to_take' => '',
    'recommendation' => '',
    'nutritional_value' => [],
    'duration' => '',
    'contraindications' => '',
    'precautions' => '',
    'advantages' => []
];
if (!empty($product['what_is_it'])) {
    $decoded = json_decode($product['what_is_it'], true);
    if (is_array($decoded)) {
        $whatIsIt = array_merge($whatIsIt, $decoded);
    }
}

$pageTitle = $product['name'];
$pageDescription = $product['description'];

include 'includes/header.php';
?>

<section class="product-page">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="<?php echo BASE_URL; ?>">Главная</a>
            <span>/</span>
            <a href="catalog.php">Каталог</a>
            <?php if ($product['category_name']): ?>
                <span>/</span>
                <a href="catalog.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
            <?php endif; ?>
            <span>/</span>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>
        
        <div class="product-detail-new">
            <!-- Левая колонка -->
            <div class="product-left-column">
                <!-- Изображение товара -->
                <div class="product-image-section">
                    <?php if (!empty($images)): ?>
                        <img id="main-product-image" src="<?php echo BASE_URL; ?>uploads/products/<?php echo $images[0]; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='<?php echo BASE_URL; ?>assets/images/placeholder.jpg';">
                    <?php else: ?>
                        <img id="main-product-image" src="<?php echo BASE_URL; ?>assets/images/placeholder.jpg" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php endif; ?>
                </div>
                
                <!-- Текст о качестве и документы -->
                <div class="product-quality-text">
                    <p>Вы можете быть уверены в том, что приобретаете сертифицированный товар, который соответствует высоким стандартам качества.</p>
                    <p>Мы используем современные технологии для очистки компонентов, из которых создаётся наша продукция.</p>
                    
                    <!-- Документы -->
                    <?php if (!empty($documents)): ?>
                    <div class="product-documents-section">
                        <h3>Ознакомиться с документами:</h3>
                        <div class="documents-buttons">
                            <?php foreach ($documents as $doc): ?>
                                <?php if (!empty($doc['file'])): ?>
                                    <a href="<?php echo BASE_URL; ?>uploads/documents/<?php echo htmlspecialchars($doc['file']); ?>" target="_blank" class="document-button">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                        </svg>
                                        <?php echo htmlspecialchars($doc['name'] ?: 'Документ'); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Правая колонка -->
            <div class="product-right-column">
                <!-- Блок с названием и описанием -->
                <div class="product-title-description-block">
                    <div class="product-title-row">
                        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                        <div class="product-availability-inline">
                            <?php if ($product['stock'] > 0): ?>
                                <span class="in-stock">В наличии</span>
                            <?php else: ?>
                                <span class="out-of-stock">Нет в наличии</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($product['full_description']): ?>
                        <div class="product-description">
                            <?php echo htmlspecialchars($product['full_description']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Блок "Стоимость" -->
                <div class="product-price-accordion-item">
                    <div class="product-price-section-compact">
                        <?php if ($product['old_price']): ?>
                            <span class="product-old-price"><?php echo formatPrice($product['old_price']); ?></span>
                            <span class="product-discount-badge">-<?php echo round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>%</span>
                        <?php endif; ?>
                        <span class="product-current-price"><?php echo formatPrice($product['price']); ?></span>
                    </div>
                    
                    <div class="product-actions-compact">
                        <?php if (isLoggedIn()): ?>
                            <button class="btn-primary btn-add-to-cart" 
                                    data-product-id="<?php echo $product['id']; ?>"
                                    <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                В корзину
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-primary btn-buy-now" 
                                    data-product-id="<?php echo $product['id']; ?>"
                                    <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                Купить
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Блок "Главные преимущества" -->
                <?php if (!empty($whatIsIt['advantages'])): ?>
                <div class="product-advantages-block">
                    <h2 class="product-advantages-title">Главные преимущества</h2>
                    <ul class="product-advantages-list">
                        <?php foreach ($whatIsIt['advantages'] as $advantage): ?>
                            <li><?php echo htmlspecialchars($advantage); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Раскрывающиеся секции -->
                <div class="product-accordion">
                    <!-- Блок "Что это такое?" -->
                    <?php if (!empty($whatIsIt['description']) || !empty($whatIsIt['consists_of']) || !empty($whatIsIt['release_form']) || !empty($whatIsIt['how_to_take']) || !empty($whatIsIt['recommendation']) || !empty($whatIsIt['nutritional_value']) || !empty($whatIsIt['duration']) || !empty($whatIsIt['contraindications']) || !empty($whatIsIt['precautions'])): ?>
                    <div class="accordion-item">
                        <button class="accordion-header" onclick="toggleAccordion(this)">
                            <span>Что это такое?</span>
                            <svg class="accordion-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="accordion-content">
                            <?php if (!empty($whatIsIt['description'])): ?>
                                <div class="what-is-it-section">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p><?php echo nl2br(htmlspecialchars($whatIsIt['description'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($whatIsIt['consists_of'])): ?>
                                <div class="what-is-it-section">
                                    <h4>Из чего состоит:</h4>
                                    <ul class="what-is-it-list">
                                        <?php foreach ($whatIsIt['consists_of'] as $item): ?>
                                            <li><?php echo htmlspecialchars($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($whatIsIt['release_form'])): ?>
                                <div class="what-is-it-section">
                                    <h4>Форма выпуска:</h4>
                                    <ul class="what-is-it-list">
                                        <?php foreach ($whatIsIt['release_form'] as $item): ?>
                                            <li><?php echo htmlspecialchars($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($whatIsIt['how_to_take'])): ?>
                                <div class="what-is-it-section">
                                    <h4>Как принимать:</h4>
                                    <p><?php echo nl2br(htmlspecialchars($whatIsIt['how_to_take'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($whatIsIt['recommendation'])): ?>
                                <div class="what-is-it-section">
                                    <h4>Рекомендованная суточная доза:</h4>
                                    <p><?php echo nl2br(htmlspecialchars($whatIsIt['recommendation'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($whatIsIt['nutritional_value'])): ?>
                                <div class="what-is-it-section">
                                    <h4>Пищевая и энергетическая ценность:</h4>
                                    <ul class="what-is-it-list">
                                        <?php foreach ($whatIsIt['nutritional_value'] as $item): ?>
                                            <li><?php echo htmlspecialchars($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($whatIsIt['duration'])): ?>
                                <div class="what-is-it-section">
                                    <h4>Продолжительность приёма:</h4>
                                    <p><?php echo nl2br(htmlspecialchars($whatIsIt['duration'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($whatIsIt['contraindications'])): ?>
                                <div class="what-is-it-section">
                                    <h4>Возможные противопоказания:</h4>
                                    <p><?php echo nl2br(htmlspecialchars($whatIsIt['contraindications'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($whatIsIt['precautions'])): ?>
                                <div class="what-is-it-section">
                                    <h4>Меры предосторожности:</h4>
                                    <p><?php echo nl2br(htmlspecialchars($whatIsIt['precautions'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
        
        <?php if (!empty($relatedProducts)): ?>
            <section class="related-products">
                <h2 class="section-title">Похожие товары</h2>
                <div class="products-grid">
                    <?php foreach ($relatedProducts as $related): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="product.php?id=<?php echo $related['id']; ?>">
                                    <?php if ($related['image']): ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo $related['image']; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" onerror="this.src='<?php echo BASE_URL; ?>assets/images/placeholder.jpg';">
                                    <?php else: ?>
                                        <img src="<?php echo BASE_URL; ?>assets/images/placeholder.jpg" alt="<?php echo htmlspecialchars($related['name']); ?>">
                                    <?php endif; ?>
                                </a>
                                <?php if ($related['old_price']): ?>
                                    <span class="product-discount">-<?php echo round((($related['old_price'] - $related['price']) / $related['old_price']) * 100); ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name">
                                    <a href="product.php?id=<?php echo $related['id']; ?>"><?php echo htmlspecialchars($related['name']); ?></a>
                                </h3>
                                <div class="product-price">
                                    <?php if ($related['old_price']): ?>
                                        <span class="old-price"><?php echo formatPrice($related['old_price']); ?></span>
                                    <?php endif; ?>
                                    <span class="current-price"><?php echo formatPrice($related['price']); ?></span>
                                </div>
                                <button class="btn-primary btn-add-to-cart" data-product-id="<?php echo $related['id']; ?>">В корзину</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<script>
function changeMainImage(src, element) {
    document.getElementById('main-product-image').src = src;
    document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
    element.classList.add('active');
}

function changeQuantity(change) {
    const input = document.getElementById('product-quantity');
    if (input) {
        const current = parseInt(input.value);
        const min = parseInt(input.getAttribute('min'));
        const max = parseInt(input.getAttribute('max'));
        const newValue = Math.max(min, Math.min(max, current + change));
        input.value = newValue;
    }
}

function toggleAccordion(button) {
    const item = button.closest('.accordion-item');
    const content = item.querySelector('.accordion-content');
    const arrow = button.querySelector('.accordion-arrow');
    const isOpen = item.classList.contains('active');
    
    // Закрываем все другие элементы
    document.querySelectorAll('.accordion-item').forEach(accordionItem => {
        if (accordionItem !== item) {
            accordionItem.classList.remove('active');
            const otherContent = accordionItem.querySelector('.accordion-content');
            const otherArrow = accordionItem.querySelector('.accordion-arrow');
            if (otherContent) {
                otherContent.style.maxHeight = null;
            }
            if (otherArrow) {
                otherArrow.style.transform = 'rotate(0deg)';
            }
        }
    });
    
    // Переключаем текущий элемент
    if (isOpen) {
        item.classList.remove('active');
        if (content) content.style.maxHeight = null;
        if (arrow) arrow.style.transform = 'rotate(0deg)';
    } else {
        item.classList.add('active');
        if (content) {
            content.style.maxHeight = content.scrollHeight + 'px';
        }
        if (arrow) arrow.style.transform = 'rotate(180deg)';
    }
}

<!-- Модальное окно для входа -->
<div id="login-modal" class="login-modal" style="display: none;">
    <div class="login-modal-content">
        <button class="login-modal-close" id="close-login-modal" aria-label="Закрыть">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
        <div class="login-modal-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <h2 class="login-modal-title">Войдите в аккаунт</h2>
        <p class="login-modal-text">Для покупки товаров необходимо войти в ваш аккаунт</p>
        <div class="login-modal-actions">
            <a href="login.php" class="btn-primary" id="login-link">Войти</a>
            <a href="register.php" class="btn-secondary">Регистрация</a>
        </div>
    </div>
</div>


<script>
// Модальное окно для кнопки "Купить" - выполняется после загрузки всех скриптов
window.addEventListener('load', function() {
    function initBuyModal() {
        const buyButtons = document.querySelectorAll('.btn-buy-now');
        const loginModal = document.getElementById('login-modal');
        const closeModal = document.getElementById('close-login-modal');
        
        if (!loginModal) {
            console.warn('Login modal not found');
            return;
        }
        
        if (buyButtons.length === 0) {
            return;
        }
        
        // Обработчик для кнопок "Купить"
        buyButtons.forEach(button => {
            // Удаляем все предыдущие обработчики
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Добавляем новый обработчик
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const modal = document.getElementById('login-modal');
                if (modal) {
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            }, true); // Используем capture phase для приоритета
        });
        
        // Обработчик закрытия модального окна
        if (closeModal) {
            closeModal.addEventListener('click', function() {
                if (loginModal) {
                    loginModal.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        }
        
        // Закрытие при клике вне модального окна
        loginModal.addEventListener('click', function(e) {
            if (e.target === loginModal) {
                loginModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
        
        // Закрытие по Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && loginModal.style.display === 'flex') {
                loginModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    }
    
    // Инициализируем после небольшой задержки, чтобы все скрипты загрузились
    setTimeout(initBuyModal, 200);
});
</script>

<?php
$additionalScripts = ['product.js'];
include 'includes/footer.php';
?>

