<?php
require_once 'config/config.php';

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

$pageTitle = 'Каталог товаров';
$pageDescription = 'Каталог биологически активных добавок ДенЛиФорс';

include 'includes/header.php';
?>

<section class="catalog-section">
    <div class="container">
        <h1 class="page-title">Каталог товаров</h1>
        
        <div class="catalog-layout">
            <aside class="catalog-filters">
                <div class="filters-header">
                    <h2>Фильтры</h2>
                    <button class="filters-reset" onclick="resetFilters()">Сбросить</button>
                </div>
                
                <form method="GET" action="catalog.php" class="filters-form">
                    <input type="hidden" name="page" value="1">
                    
                    <div class="filter-group">
                        <h3>Поиск</h3>
                        <input type="text" name="search" placeholder="Название или артикул" value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
                    </div>
                    
                    <div class="filter-group">
                        <h3>Категория</h3>
                        <div class="filter-checkboxes">
                            <label>
                                <input type="radio" name="category" value="" <?php echo $categoryId == 0 ? 'checked' : ''; ?>>
                                <span>Все категории</span>
                            </label>
                            <?php foreach ($categories as $category): ?>
                                <label>
                                    <input type="radio" name="category" value="<?php echo $category['id']; ?>" <?php echo $categoryId == $category['id'] ? 'checked' : ''; ?>>
                                    <span><?php echo htmlspecialchars($category['name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <h3>Цена</h3>
                        <div class="price-range">
                            <input type="number" name="min_price" placeholder="От" value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>" min="0" class="filter-input">
                            <span>—</span>
                            <input type="number" name="max_price" placeholder="До" value="<?php echo $maxPrice > 0 ? $maxPrice : ''; ?>" min="0" class="filter-input">
                        </div>
                        <div class="price-info">
                            <?php if ($priceRange): ?>
                                <small>от <?php echo formatPrice($priceRange['min_price']); ?> до <?php echo formatPrice($priceRange['max_price']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">Применить фильтры</button>
                </form>
            </aside>
            
            <div class="catalog-content">
                <div class="catalog-toolbar">
                    <div class="results-count">
                        Найдено товаров: <strong><?php echo $totalProducts; ?></strong>
                    </div>
                    <div class="sort-select">
                        <label>Сортировка:</label>
                        <select onchange="changeSort(this.value)">
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Сначала новые</option>
                            <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Цена: по возрастанию</option>
                            <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Цена: по убыванию</option>
                            <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>По названию</option>
                            <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>По популярности</option>
                        </select>
                    </div>
                </div>
                
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <p>Товары не найдены</p>
                        <a href="catalog.php" class="btn-primary">Показать все товары</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <a href="product.php?id=<?php echo $product['id']; ?>">
                                        <?php if ($product['image']): ?>
                                            <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='<?php echo BASE_URL; ?>assets/images/placeholder.jpg';">
                                        <?php else: ?>
                                            <img src="<?php echo BASE_URL; ?>assets/images/placeholder.jpg" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php endif; ?>
                                    </a>
                                    <?php if ($product['old_price']): ?>
                                        <span class="product-discount">-<?php echo round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <div class="product-header">
                                        <h3 class="product-name">
                                            <a href="product.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a>
                                        </h3>
                                        <span class="product-sku">Арт: <?php echo htmlspecialchars($product['sku']); ?></span>
                                    </div>
                                    
                                    <?php if (!empty($product['description'])): ?>
                                    <p class="product-description"><?php echo mb_substr(strip_tags($product['description']), 0, 60) . '...'; ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="product-bottom">
                                        <div class="product-price-rating">
                                            <div class="product-price">
                                                <?php if ($product['old_price']): ?>
                                                    <span class="old-price"><?php echo formatPrice($product['old_price']); ?></span>
                                                <?php endif; ?>
                                                <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                                            </div>
                                            
                                            <div class="product-rating">
                                                <div class="stars">
                                                    <?php 
                                                    $rating = 4.5;
                                                    if (isset($product['sales_count']) && $product['sales_count'] > 0) {
                                                        $rating = min(5.0, 4.0 + ($product['sales_count'] / 100));
                                                    }
                                                    $fullStars = round($rating);
                                                    for ($i = 1; $i <= 5; $i++): 
                                                        if ($i <= $fullStars): ?>
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="#FFB800" stroke="#FFB800">
                                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                                            </svg>
                                                        <?php else: ?>
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#E0E0E0" stroke-width="1">
                                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                                            </svg>
                                                        <?php endif;
                                                    endfor; ?>
                                                </div>
                                                <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                                                <?php if (isset($product['sales_count']) && $product['sales_count'] > 0): ?>
                                                    <span class="sales-count"><?php echo $product['sales_count']; ?> куп.</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <button class="btn-primary btn-add-to-cart" data-product-id="<?php echo $product['id']; ?>">В корзину</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-link">← Назад</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <span class="pagination-ellipsis">...</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-link">Вперёд →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function changeSort(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function resetFilters() {
    window.location.href = 'catalog.php';
}
</script>

<?php
$additionalScripts = ['catalog.js'];
include 'includes/footer.php';
?>

