<?php
if (!isset($_GET['id'])) {
    redirect('dashboard.php?section=shop');
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
    redirect('dashboard.php?section=shop');
}

// Increment view count
$db->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = :id")->execute([':id' => $productId]);

// Get related products
$relatedStmt = $db->prepare("SELECT * FROM products 
                             WHERE category_id = :category_id AND id != :id AND status = 'active' 
                             LIMIT 2");
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
?>

<div class="dashboard-product">
    <!-- Кнопка Назад -->
    <a href="dashboard.php?section=shop" class="dashboard-product-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Назад
    </a>
    
    <div class="dashboard-product-content">
        <!-- Левая колонка - Изображение товара -->
        <div class="dashboard-product-left">
            <div class="dashboard-product-image-card">
                <?php if (!empty($images)): ?>
                    <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo $images[0]; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="dashboard-product-main-image" onerror="this.style.display='none';">
                <?php else: ?>
                    <div class="dashboard-product-image-placeholder">Нет фото</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Правая колонка - Информация о товаре -->
        <div class="dashboard-product-right">
            <!-- Карточка с описанием -->
            <div class="dashboard-product-card">
                <h1 class="dashboard-product-card-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                <?php if ($product['full_description']): ?>
                    <p class="dashboard-product-card-description"><?php echo nl2br(htmlspecialchars($product['full_description'])); ?></p>
                <?php elseif ($product['description']): ?>
                    <p class="dashboard-product-card-description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Карточка "Для людей, которые:" -->
            <?php if (!empty($whatIsIt['advantages'])): ?>
            <div class="dashboard-product-card">
                <h2 class="dashboard-product-card-title"><?php echo htmlspecialchars($product['name']); ?></h2>
                <h3 class="dashboard-product-card-subtitle">Для людей, которые:</h3>
                <div class="dashboard-product-benefits-list">
                    <?php foreach ($whatIsIt['advantages'] as $advantage): ?>
                        <div class="dashboard-product-benefit-item">
                            <div class="dashboard-product-benefit-checkbox checked"></div>
                            <span><?php echo htmlspecialchars($advantage); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Карточка с документами -->
            <?php if (!empty($documents)): ?>
            <div class="dashboard-product-card">
                <h2 class="dashboard-product-card-title">Товар соответствует высоким стандартам качества</h2>
                <p class="dashboard-product-card-description">Мы используем современные технологии для изготовления нашей продукции</p>
                <h3 class="dashboard-product-card-subtitle">Ознакомиться с документами:</h3>
                <div class="dashboard-product-documents">
                    <?php foreach ($documents as $doc): ?>
                        <?php if (!empty($doc['file'])): ?>
                            <a href="<?php echo BASE_URL; ?>download-documentation.php?file=<?php echo urlencode($doc['file']); ?>" target="_blank" class="dashboard-product-document-btn">
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
            
            <!-- Collapsible секции -->
            <div class="dashboard-product-accordion">
                <?php if ($product['description'] || $product['full_description']): ?>
                <div class="dashboard-product-accordion-item">
                    <button class="dashboard-product-accordion-header" onclick="toggleDashboardAccordion(this)">
                        <span>Описание товара</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="dashboard-accordion-arrow">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="dashboard-product-accordion-content">
                        <?php if ($product['full_description']): ?>
                            <p><?php echo nl2br(htmlspecialchars($product['full_description'])); ?></p>
                        <?php elseif ($product['description']): ?>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($whatIsIt['advantages'])): ?>
                <div class="dashboard-product-accordion-item">
                    <button class="dashboard-product-accordion-header" onclick="toggleDashboardAccordion(this)">
                        <span>Преимущества товара</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="dashboard-accordion-arrow">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="dashboard-product-accordion-content">
                        <ul class="dashboard-product-advantages-list">
                            <?php foreach ($whatIsIt['advantages'] as $advantage): ?>
                                <li><?php echo htmlspecialchars($advantage); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Цена и кнопка В корзину -->
            <div class="dashboard-product-purchase">
                <div class="dashboard-product-price-section">
                    <span class="dashboard-product-price-label">К оплате:</span>
                    <span class="dashboard-product-price-value"><?php echo formatPrice($product['price']); ?></span>
                </div>
                <button class="dashboard-product-add-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    В корзину
                </button>
            </div>
        </div>
    </div>
    
    <!-- Секция "С этим товаром покупают" -->
    <?php if (!empty($relatedProducts)): ?>
    <div class="dashboard-product-related">
        <h2 class="dashboard-product-related-title">С этим товаром покупают</h2>
        <div class="dashboard-product-related-grid">
            <?php foreach ($relatedProducts as $related): ?>
                <div class="dashboard-product-related-card">
                    <div class="dashboard-product-related-header">
                        <span>С этим товаром покупают</span>
                    </div>
                    <div class="dashboard-product-related-image">
                        <?php if ($related['image']): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo $related['image']; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" onerror="this.style.display='none';">
                        <?php else: ?>
                            <div class="dashboard-product-related-placeholder">Нет фото</div>
                        <?php endif; ?>
                    </div>
                    <div class="dashboard-product-related-info">
                        <h3 class="dashboard-product-related-name"><?php echo htmlspecialchars($related['name']); ?></h3>
                        <div class="dashboard-product-related-sku">Артикул: <?php echo htmlspecialchars($related['sku']); ?></div>
                        <div class="dashboard-product-related-price"><?php echo formatPrice($related['price']); ?></div>
                        <button class="dashboard-product-related-add" onclick="addToCart(<?php echo $related['id']; ?>)">
                            Добавить
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleDashboardAccordion(button) {
    const item = button.closest('.dashboard-product-accordion-item');
    const content = item.querySelector('.dashboard-product-accordion-content');
    const arrow = button.querySelector('.dashboard-accordion-arrow');
    const isOpen = item.classList.contains('active');
    
    // Закрываем все другие элементы
    document.querySelectorAll('.dashboard-product-accordion-item').forEach(accordionItem => {
        if (accordionItem !== item) {
            accordionItem.classList.remove('active');
            const otherContent = accordionItem.querySelector('.dashboard-product-accordion-content');
            const otherArrow = accordionItem.querySelector('.dashboard-accordion-arrow');
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

function addToCart(productId) {
    const baseUrl = window.BASE_URL || '<?php echo BASE_URL; ?>';
    fetch(baseUrl + 'api/cart-add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof updateDashboardCartCount === 'function') {
                updateDashboardCartCount();
            }
            if (typeof showNotification === 'function') {
                showNotification('Товар добавлен в корзину', 'success');
            } else {
                alert('Товар добавлен в корзину');
            }
        } else {
            alert('Ошибка: ' + (data.message || 'Не удалось добавить товар'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при добавлении товара в корзину');
    });
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `dashboard-notification dashboard-notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#48bb78' : '#f56565'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10001;
        animation: slideIn 0.3s ease;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}
</script>

