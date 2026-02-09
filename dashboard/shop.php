<?php
$db = getDBConnection();

// Get categories for filters
$stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Get filter parameters
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build query - check if sales_count column exists
$columns = $db->query("SHOW COLUMNS FROM products LIKE 'sales_count'")->fetch();
$hasSalesCount = !empty($columns);

$selectFields = "id, name, sku, slug, description, price, old_price, image, category_id, status, created_at";
if ($hasSalesCount) {
    $selectFields .= ", sales_count";
}

// Initialize arrays
$where = ["status = 'active'"];
$params = [];

if ($categoryId > 0) {
    $where[] = "category_id = :category_id";
    $params[':category_id'] = $categoryId;
}

if (!empty($search)) {
    $where[] = "(name LIKE :search OR description LIKE :search OR sku LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($minPrice > 0) {
    $where[] = "price >= :min_price";
    $params[':min_price'] = $minPrice;
}

if ($maxPrice > 0) {
    $where[] = "price <= :max_price";
    $params[':max_price'] = $maxPrice;
}

$whereClause = implode(' AND ', $where);

// Get sort order
$orderBy = "created_at DESC";
switch ($sort) {
    case 'price_asc':
        $orderBy = "price ASC";
        break;
    case 'price_desc':
        $orderBy = "price DESC";
        break;
    case 'name':
        $orderBy = "name ASC";
        break;
    case 'popular':
        $orderBy = "view_count DESC";
        break;
}

// Get total count
$countStmt = $db->prepare("SELECT COUNT(*) FROM products WHERE $whereClause");
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get products
$stmt = $db->prepare("SELECT $selectFields FROM products WHERE $whereClause ORDER BY $orderBy LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Get price range for filter
$priceStmt = $db->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE status = 'active'");
$priceRange = $priceStmt->fetch();
?>

<div class="dashboard-shop">
    <div class="dashboard-shop-header">
        <h1 class="dashboard-shop-title">Магазин</h1>
    </div>
    
    <div class="dashboard-shop-content">
        <aside class="dashboard-shop-filters">
            <form method="GET" action="dashboard.php" class="dashboard-filters-form">
                <input type="hidden" name="section" value="shop">
                
                <div class="dashboard-filter-group">
                    <label class="dashboard-filter-label">Поиск</label>
                    <input 
                        type="text" 
                        name="search" 
                        class="dashboard-filter-input" 
                        placeholder="Название товара..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                
                <div class="dashboard-filter-group">
                    <label class="dashboard-filter-label">Категория</label>
                    <select name="category" class="dashboard-filter-select">
                        <option value="0">Все категории</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($priceRange): ?>
                <div class="dashboard-filter-group">
                    <label class="dashboard-filter-label">Цена</label>
                    <div class="dashboard-price-range">
                        <input 
                            type="number" 
                            name="min_price" 
                            class="dashboard-filter-input" 
                            placeholder="От"
                            value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>"
                            min="0"
                        >
                        <input 
                            type="number" 
                            name="max_price" 
                            class="dashboard-filter-input" 
                            placeholder="До"
                            value="<?php echo $maxPrice > 0 ? $maxPrice : ''; ?>"
                            min="0"
                        >
                    </div>
                    <?php if ($priceRange): ?>
                        <small>от <?php echo formatPrice($priceRange['min_price']); ?> до <?php echo formatPrice($priceRange['max_price']); ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="dashboard-filter-btn">Применить фильтры</button>
            </form>
        </aside>
        
        <div class="dashboard-shop-main">
            <div class="dashboard-shop-toolbar">
                <div class="dashboard-shop-results">
                    Найдено товаров: <strong><?php echo $totalProducts; ?></strong>
                </div>
                <div class="dashboard-shop-sort">
                    <label>Сортировка:</label>
                    <select onchange="changeSort(this.value)" class="dashboard-sort-select">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Сначала новые</option>
                        <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Цена: по возрастанию</option>
                        <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Цена: по убыванию</option>
                        <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>По названию</option>
                        <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>По популярности</option>
                    </select>
                </div>
            </div>
            
            <?php if (empty($products)): ?>
                <div class="dashboard-empty-state">
                    <p>Товары не найдены</p>
                    <a href="dashboard.php?section=shop" class="dashboard-btn-primary">Показать все товары</a>
                </div>
            <?php else: ?>
                <div class="dashboard-products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="dashboard-product-card">
                            <div class="dashboard-product-image">
                                <a href="dashboard.php?section=product&id=<?php echo $product['id']; ?>">
                                    <?php if ($product['image']): ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.style.display='none';">
                                    <?php else: ?>
                                        <div class="dashboard-product-placeholder">Нет фото</div>
                                    <?php endif; ?>
                                </a>
                                <?php if ($product['old_price']): ?>
                                    <span class="dashboard-product-discount">-<?php echo round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="dashboard-product-info">
                                <div class="dashboard-product-header">
                                    <h3 class="dashboard-product-name">
                                        <a href="dashboard.php?section=product&id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a>
                                    </h3>
                                    <span class="dashboard-product-sku">Арт: <?php echo htmlspecialchars($product['sku']); ?></span>
                                </div>
                                
                                <?php if (!empty($product['description'])): ?>
                                <p class="dashboard-product-description"><?php echo mb_substr(strip_tags($product['description']), 0, 60) . '...'; ?></p>
                                <?php endif; ?>
                                
                                <div class="dashboard-product-bottom">
                                    <div class="dashboard-product-price">
                                        <?php if ($product['old_price']): ?>
                                            <span class="dashboard-old-price"><?php echo formatPrice($product['old_price']); ?></span>
                                        <?php endif; ?>
                                        <span class="dashboard-current-price"><?php echo formatPrice($product['price']); ?></span>
                                    </div>
                                    
                                    <button 
                                        class="dashboard-add-to-cart" 
                                        onclick="addToCart(<?php echo $product['id']; ?>)"
                                        data-product-id="<?php echo $product['id']; ?>"
                                    >
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
                    <?php endforeach; ?>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <div class="dashboard-pagination">
                        <?php if ($page > 1): ?>
                            <a href="dashboard.php?section=shop&page=<?php echo $page - 1; ?>&category=<?php echo $categoryId; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="dashboard-pagination-btn">Назад</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <a href="dashboard.php?section=shop&page=<?php echo $i; ?>&category=<?php echo $categoryId; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="dashboard-pagination-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <span class="dashboard-pagination-dots">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="dashboard.php?section=shop&page=<?php echo $page + 1; ?>&category=<?php echo $categoryId; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="dashboard-pagination-btn">Вперёд</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changeSort(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
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
            // Update cart count
            if (typeof updateDashboardCartCount === 'function') {
                updateDashboardCartCount();
            }
            // Show notification
            showNotification('Товар добавлен в корзину', 'success');
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

