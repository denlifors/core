<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    die('Access denied');
}

$db = getDBConnection();

// Название файла изображения бутылки
$bottleImageName = 'bottle.jpg'; // Пользователь должен загрузить изображение с этим именем

// Проверяем, есть ли файл
$imagePath = '../uploads/products/' . $bottleImageName;
$imageExists = file_exists($imagePath);

if (isset($_GET['do']) && $_GET['do'] == 'update') {
    if (!$imageExists) {
        die("Ошибка: Файл $bottleImageName не найден в папке uploads/products/. Пожалуйста, загрузите изображение туда.");
    }
    
    // Обновляем все товары
    $stmt = $db->query("SELECT id, name FROM products");
    $products = $stmt->fetchAll();
    
    $updated = 0;
    foreach ($products as $product) {
        $stmt = $db->prepare("UPDATE products SET image = :image WHERE id = :id");
        $stmt->execute([
            ':image' => $bottleImageName,
            ':id' => $product['id']
        ]);
        $updated++;
    }
    
    echo "<h2>Результат обновления</h2>";
    echo "<style>body { font-family: Arial, sans-serif; padding: 2rem; } h2 { color: #333; } p { padding: 0.5rem; } .success { color: green; } .error { color: red; }</style>";
    echo "<p class='success'>✓ Обновлено товаров: $updated</p>";
    echo "<p><a href='products.php'>Вернуться к товарам</a></p>";
    exit;
}

$pageTitle = 'Установка изображения бутылки';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <h1>Установка изображения бутылки для всех товаров</h1>
    
    <?php if (!$imageExists): ?>
        <div class="alert alert-warning">
            <strong>Внимание!</strong> Файл <code><?php echo $bottleImageName; ?></code> не найден в папке <code>uploads/products/</code>.
            <p>Пожалуйста:</p>
            <ol>
                <li>Загрузите изображение бутылки в папку <code>uploads/products/</code></li>
                <li>Переименуйте его в <code><?php echo $bottleImageName; ?></code></li>
                <li>Обновите эту страницу</li>
            </ol>
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <strong>✓</strong> Файл <code><?php echo $bottleImageName; ?></code> найден.
        </div>
        
        <div style="margin: 2rem 0;">
            <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo $bottleImageName; ?>" alt="Bottle image" style="max-width: 300px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        </div>
        
        <div style="background: white; padding: 2rem; border-radius: 8px; margin: 2rem 0;">
            <h2>Установить изображение для всех товаров</h2>
            <p>Это действие заменит изображения у всех существующих товаров на изображение бутылки.</p>
            
            <p style="margin-top: 2rem;">
                <a href="?do=update" class="btn-primary" onclick="return confirm('Заменить изображения у всех товаров на изображение бутылки?');">
                    Установить изображение для всех товаров
                </a>
            </p>
        </div>
    <?php endif; ?>
    
    <p><a href="products.php">← Вернуться к товарам</a></p>
</div>

<?php
include '../includes/admin-footer.php';
?>






