<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    die('Access denied');
}

$db = getDBConnection();

// Название файла изображения по умолчанию
// Пользователь должен загрузить изображение бутылки в uploads/products/ с этим именем
$defaultImageName = 'product-default.jpg';

// Проверяем, есть ли файл
$imagePath = '../uploads/products/' . $defaultImageName;
$imageExists = file_exists($imagePath);

if (isset($_GET['do']) && $_GET['do'] == 'update') {
    if (!$imageExists) {
        die("Ошибка: Файл $defaultImageName не найден в папке uploads/products/. Пожалуйста, загрузите изображение туда.");
    }
    
    $stmt = $db->query("SELECT id, name FROM products WHERE image IS NULL OR image = ''");
    $products = $stmt->fetchAll();
    
    $updated = 0;
    foreach ($products as $product) {
        $stmt = $db->prepare("UPDATE products SET image = :image WHERE id = :id");
        $stmt->execute([
            ':image' => $defaultImageName,
            ':id' => $product['id']
        ]);
        $updated++;
    }
    
    echo "<h2>Результат обновления</h2>";
    echo "<p style='color:green;'>✓ Обновлено товаров: $updated</p>";
    echo "<p><a href='products.php'>Вернуться к товарам</a></p>";
    exit;
}

// Получаем список товаров без изображений
$stmt = $db->query("SELECT id, name FROM products WHERE image IS NULL OR image = ''");
$products = $stmt->fetchAll();

$pageTitle = 'Установка изображения по умолчанию';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <h1>Установка изображения по умолчанию для товаров</h1>
    
    <?php if (!$imageExists): ?>
        <div class="alert alert-warning">
            <strong>Внимание!</strong> Файл <code><?php echo $defaultImageName; ?></code> не найден в папке <code>uploads/products/</code>.
            <p>Пожалуйста:</p>
            <ol>
                <li>Загрузите изображение бутылки в папку <code>uploads/products/</code></li>
                <li>Переименуйте его в <code><?php echo $defaultImageName; ?></code></li>
                <li>Обновите эту страницу</li>
            </ol>
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <strong>✓</strong> Файл <code><?php echo $defaultImageName; ?></code> найден.
        </div>
        
        <div style="margin: 2rem 0;">
            <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo $defaultImageName; ?>" alt="Default product image" style="max-width: 300px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        </div>
    <?php endif; ?>
    
    <div style="background: white; padding: 2rem; border-radius: 8px; margin: 2rem 0;">
        <h2>Товары без изображений</h2>
        <p>Найдено: <strong><?php echo count($products); ?></strong> товаров</p>
        
        <?php if (count($products) > 0): ?>
            <ul style="max-height: 300px; overflow-y: auto; margin-top: 1rem;">
                <?php foreach ($products as $product): ?>
                    <li><?php echo htmlspecialchars($product['name']); ?> (ID: <?php echo $product['id']; ?>)</li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($imageExists): ?>
                <p style="margin-top: 2rem;">
                    <a href="?do=update" class="btn-primary" onclick="return confirm('Установить изображение <?php echo $defaultImageName; ?> для всех товаров без изображений?');">
                        Установить изображение для всех товаров
                    </a>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p style="color:green;">✓ Все товары уже имеют изображения!</p>
        <?php endif; ?>
    </div>
    
    <p><a href="products.php">← Вернуться к товарам</a></p>
</div>

<?php
include '../includes/admin-footer.php';
?>






