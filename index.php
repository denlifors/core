<?php
require_once 'config/config.php';

// Если пользователь залогинен, перенаправляем в дашборд
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$db = getDBConnection();

// Get banners for hero slider (home page only)
$banners = [];

try {
    // Check if page column exists
    try {
        $testQuery = $db->query("SELECT page FROM banners LIMIT 1");
        $hasPageColumn = true;
    } catch (PDOException $e) {
        $hasPageColumn = false;
    }

    if ($hasPageColumn) {
        // Check if type column exists
        try {
            $testTypeQuery = $db->query("SELECT type FROM banners LIMIT 1");
            $hasTypeColumn = true;
        } catch (PDOException $e) {
            $hasTypeColumn = false;
        }
        
        if ($hasTypeColumn) {
            $stmt = $db->query("SELECT * FROM banners WHERE status = 'active' AND (page = 'home' OR page = 'all') ORDER BY sort_order ASC, created_at DESC");
        } else {
            $stmt = $db->query("SELECT *, 'detailed' as type FROM banners WHERE status = 'active' AND (page = 'home' OR page = 'all') ORDER BY sort_order ASC, created_at DESC");
        }
    } else {
        $stmt = $db->query("SELECT *, 'detailed' as type FROM banners WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC");
    }
    $banners = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table might not exist or error occurred
    $banners = [];
}

// Get superprice products (products with old_price) - carousel
// Check if is_superprice and sales_count columns exist
try {
    $testQuery = $db->query("SELECT is_superprice, sales_count FROM products LIMIT 1");
    $hasSuperpriceColumn = true;
    $hasSalesCount = true;
} catch (PDOException $e) {
    $hasSuperpriceColumn = false;
    $hasSalesCount = false;
}

$selectFields = "id, name, sku, slug, description, price, old_price, image, category_id, status, created_at";
if ($hasSalesCount) {
    $selectFields .= ", sales_count";
}

if ($hasSuperpriceColumn) {
    $stmt = $db->query("SELECT $selectFields FROM products WHERE status = 'active' AND (old_price IS NOT NULL OR is_superprice = 1) ORDER BY created_at DESC LIMIT 12");
} else {
    $stmt = $db->query("SELECT $selectFields FROM products WHERE status = 'active' AND old_price IS NOT NULL ORDER BY created_at DESC LIMIT 12");
}
$superpriceProducts = $stmt->fetchAll();

// Get categories with products count
$stmt = $db->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active' GROUP BY c.id ORDER BY c.sort_order ASC, c.name ASC");
$categories = $stmt->fetchAll();

// Get trending products - carousel
// Check if new columns exist
try {
    $testQuery = $db->query("SELECT is_trending, sales_count FROM products LIMIT 1");
    $hasNewColumns = true;
    $hasSalesCountTrending = true;
} catch (PDOException $e) {
    $hasNewColumns = false;
    $hasSalesCountTrending = false;
}

$selectFieldsTrending = "id, name, sku, slug, description, price, old_price, image, category_id, status, created_at";
if ($hasSalesCountTrending) {
    $selectFieldsTrending .= ", sales_count";
}

if ($hasNewColumns) {
    $stmt = $db->query("SELECT $selectFieldsTrending FROM products WHERE status = 'active' AND (is_trending = 1 OR sales_count > 0) ORDER BY is_trending DESC, sales_count DESC, view_count DESC LIMIT 12");
} else {
    // Fallback: use is_featured for trending if new columns don't exist
    $stmt = $db->query("SELECT $selectFieldsTrending FROM products WHERE status = 'active' AND is_featured = 1 ORDER BY view_count DESC LIMIT 12");
}
$trendingProducts = $stmt->fetchAll();

// Get new arrivals (newest products) - carousel
$selectFieldsNew = "id, name, sku, slug, description, price, old_price, image, category_id, status, created_at";
if ($hasSalesCountTrending) {
    $selectFieldsNew .= ", sales_count";
}
$stmt = $db->query("SELECT $selectFieldsNew FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 12");
$newProducts = $stmt->fetchAll();

// Get articles/blog posts
try {
    $stmt = $db->query("SELECT * FROM articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 6");
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    $articles = [];
}

$pageTitle = 'Главная';
$pageDescription = 'Интернет-магазин биологически активных добавок ДенЛиФорс';

include 'includes/header.php';
?>

<!-- Hero Banner Carousel -->
<section class="hero">
    <div class="container">
        <div class="hero-slider">
            <div class="hero-slider-wrapper">
                <div class="hero-slides-container" id="hero-slides-container">
                    <?php if (empty($banners)): ?>
                        <div class="hero-slide" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="hero-content-wrapper">
                                <div class="hero-content-left">
                                    <h1 class="hero-title">Премиум БАДы для здоровья и красоты</h1>
                                    <p class="hero-description">Высококачественные биологически активные добавки от проверенного производителя. Забота о вашем здоровье — наш приоритет.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($banners as $index => $banner): ?>
                            <?php 
                            $bannerType = $banner['type'] ?? 'detailed';
                            
                            if ($bannerType === 'simple'): 
                                // Simple banner - just image on full width
                            ?>
                                <div class="hero-slide hero-slide-simple" data-slide-index="<?php echo $index; ?>">
                                    <?php if (!empty($banner['image'])): ?>
                                        <a href="<?php echo !empty($banner['link']) ? htmlspecialchars($banner['link']) : 'catalog.php'; ?>" class="hero-slide-link-simple">
                                            <img src="<?php echo BASE_URL; ?>uploads/banners/<?php echo htmlspecialchars($banner['image']); ?>" alt="<?php echo htmlspecialchars($banner['title'] ?? 'Banner'); ?>" class="hero-simple-image" onerror="this.style.display='none'; console.error('Banner image not found: <?php echo htmlspecialchars($banner['image']); ?>');">
                                        </a>
                                    <?php else: ?>
                                        <div style="padding: 2rem; text-align: center; color: #999;">
                                            <p>Изображение баннера не загружено</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: 
                                // Detailed banner - with gradient, text, and image
                                // Check if banner has gradient settings
                                $hasGradient = isset($banner['gradient_color1']) && !empty($banner['gradient_color1']);
                                $gradientColor1 = $hasGradient ? ($banner['gradient_color1'] ?? '#667eea') : '#667eea';
                                $gradientColor2 = $hasGradient ? ($banner['gradient_color2'] ?? '#764ba2') : '#764ba2';
                                $gradientAngle = $hasGradient ? ($banner['gradient_angle'] ?? 135) : 135;
                                $gradientStyle = "background: linear-gradient({$gradientAngle}deg, {$gradientColor1} 0%, {$gradientColor2} 100%);";
                            ?>
                                <div class="hero-slide" data-slide-index="<?php echo $index; ?>" style="<?php echo $gradientStyle; ?>">
                                    <a href="<?php echo $banner['link'] ?: 'catalog.php'; ?>" class="hero-slide-link">
                                        <div class="hero-slide-overlay"></div>
                                        <div class="hero-content-wrapper">
                                            <div class="hero-content-left">
                                                <?php if (!empty($banner['title'])): ?>
                                                    <h1 class="hero-title"><?php echo htmlspecialchars($banner['title']); ?></h1>
                                                <?php endif; ?>
                                                <?php if (!empty($banner['subtitle'])): ?>
                                                    <p class="hero-subtitle"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($banner['description'])): ?>
                                                    <p class="hero-description"><?php echo nl2br(htmlspecialchars($banner['description'])); ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($banner['button_text'])): ?>
                                                    <div class="hero-button-wrapper">
                                                        <span class="hero-button"><?php echo htmlspecialchars($banner['button_text']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($banner['image'])): ?>
                                                <div class="hero-content-right">
                                                    <img src="<?php echo BASE_URL; ?>uploads/banners/<?php echo htmlspecialchars($banner['image']); ?>" alt="<?php echo htmlspecialchars($banner['title'] ?? ''); ?>" class="hero-image">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($banners) && count($banners) > 1): ?>
                    <div class="hero-pagination">
                        <?php foreach ($banners as $index => $banner): ?>
                            <button class="hero-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide-index="<?php echo $index; ?>" aria-label="Перейти к слайду <?php echo $index + 1; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Superprice Products Carousel -->
<?php if (!empty($superpriceProducts)): ?>
<section class="products-carousel-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Суперцена</h2>
            <a href="catalog.php?filter=superprice" class="section-link">Все товары →</a>
        </div>
        <div class="products-carousel" data-carousel="superprice">
            <button class="carousel-nav carousel-prev" aria-label="Предыдущий">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </button>
            <div class="products-carousel-track">
                <?php foreach ($superpriceProducts as $product): ?>
                    <div class="product-card carousel-item">
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
                                
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-primary">Подробнее</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-nav carousel-next" aria-label="Следующий">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Categories Carousel -->
<?php if (!empty($categories)): ?>
<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Товары по категориям</h2>
            <a href="catalog.php" class="section-link">Все категории →</a>
        </div>
        <div class="categories-carousel" data-carousel="categories">
            <button class="carousel-nav carousel-prev" aria-label="Предыдущий">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </button>
            <div class="categories-carousel-track">
                <?php foreach ($categories as $category): ?>
                    <a href="catalog.php?category=<?php echo $category['id']; ?>" class="category-card">
                        <?php if ($category['image']): ?>
                            <div class="category-image">
                                <img src="<?php echo BASE_URL; ?>uploads/categories/<?php echo $category['image']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                            </div>
                        <?php else: ?>
                            <div class="category-icon">
                                <span>📦</span>
                            </div>
                        <?php endif; ?>
                        <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <?php if ($category['product_count'] > 0): ?>
                            <p class="category-count"><?php echo $category['product_count']; ?> товаров</p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <button class="carousel-nav carousel-next" aria-label="Следующий">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Trending Products Carousel -->
<?php if (!empty($trendingProducts)): ?>
<section class="products-carousel-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Сегодня в трендах</h2>
            <a href="catalog.php?filter=trending" class="section-link">Все товары →</a>
        </div>
        <div class="products-carousel" data-carousel="trending">
            <button class="carousel-nav carousel-prev" aria-label="Предыдущий">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </button>
            <div class="products-carousel-track">
                <?php foreach ($trendingProducts as $product): ?>
                    <div class="product-card carousel-item">
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
                                
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-primary">Подробнее</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-nav carousel-next" aria-label="Следующий">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- New Arrivals Carousel -->
<?php if (!empty($newProducts)): ?>
<section class="products-carousel-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Новые поступления</h2>
            <a href="catalog.php?sort=newest" class="section-link">Все товары →</a>
        </div>
        <div class="products-carousel" data-carousel="new">
            <button class="carousel-nav carousel-prev" aria-label="Предыдущий">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </button>
            <div class="products-carousel-track">
                <?php foreach ($newProducts as $product): ?>
                    <div class="product-card carousel-item">
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
                                
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-primary">Подробнее</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-nav carousel-next" aria-label="Следующий">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Articles Section (placeholder for now) -->
<section class="articles-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Полезные статьи</h2>
            <a href="articles.php" class="section-link">Все статьи →</a>
        </div>
        <div class="articles-grid">
            <?php if (empty($articles)): ?>
                <p style="text-align: center; padding: 3rem; color: var(--text-light);">Статьи будут добавлены в ближайшее время</p>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <article class="article-card">
                        <?php if ($article['image']): ?>
                            <a href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="article-image">
                                <img src="<?php echo BASE_URL; ?>uploads/articles/<?php echo htmlspecialchars($article['image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                            </a>
                        <?php endif; ?>
                        <div class="article-content">
                            <h3 class="article-title">
                                <a href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>"><?php echo htmlspecialchars($article['title']); ?></a>
                            </h3>
                            <?php if ($article['excerpt']): ?>
                                <p class="article-excerpt"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                            <?php endif; ?>
                            <div class="article-meta">
                                <span class="article-date"><?php echo date('d.m.Y', strtotime($article['created_at'])); ?></span>
                                <?php if ($article['view_count'] > 0): ?>
                                    <span class="article-views"><?php echo $article['view_count']; ?> просмотров</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="features-section">
    <div class="container">
        <div class="features-header">
            <h2 class="section-title">Почему выбирают <span class="brand-name">ДенЛиФорс</span></h2>
            <p class="section-subtitle">Мы заботимся о вашем здоровье и предлагаем только лучшее качество продукции</p>
        </div>
        <div class="features-grid">
            <div class="feature-card gradient-blue">
                <div class="feature-icon-wrapper">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <h3>Премиум качество</h3>
                <p>Только проверенные и сертифицированные продукты от ведущих производителей</p>
            </div>
            <div class="feature-card gradient-pink">
                <div class="feature-icon-wrapper">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                </div>
                <h3>Быстрая доставка</h3>
                <p>Доставка по всей России в кратчайшие сроки. Удобная упаковка и бережная транспортировка</p>
            </div>
            <div class="feature-card gradient-green">
                <div class="feature-icon-wrapper">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"></path>
                    </svg>
                </div>
                <h3>Выгодные цены</h3>
                <p>Доступные цены, специальные предложения и система скидок для постоянных клиентов</p>
            </div>
            <div class="feature-card gradient-purple">
                <div class="feature-icon-wrapper">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <h3>Консультации</h3>
                <p>Бесплатные консультации специалистов по подбору и применению БАДов</p>
            </div>
        </div>
    </div>
</section>

<!-- Partnership CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-wrapper">
            <div class="cta-content">
                <h2>Станьте партнёром и зарабатывайте</h2>
                <p>Присоединяйтесь к партнёрской программе ДенЛиФорс и получайте дополнительный доход. Прозрачная система вознаграждений и поддержка на всех этапах.</p>
                <a href="partnership.php" class="btn-primary btn-large">Узнать больше</a>
            </div>
            <div class="cta-image">
                <!-- Placeholder for image - можно добавить изображение позже -->
                <div style="width: 100%; height: 100%; background: rgba(255,255,255,0.1); border-radius: 20px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.7); font-size: 1.2rem;">
                    Изображение партнёрства
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$additionalScripts = ['home.js', 'carousel.js'];
include 'includes/footer.php';
?>
