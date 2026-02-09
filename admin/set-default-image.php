<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    die('Access denied');
}

$db = getDBConnection();

// Название файла изображения по умолчанию (пользователь должен загрузить его в uploads/products/)
$defaultImage = 'product-default.jpg'; // Или другое название

// Проверяем, есть ли товары без изображений
$stmt = $db->query("SELECT id, name FROM products WHERE image IS NULL OR image = ''");
$products = $stmt->fetchAll();

echo "<h2>Установка изображения по умолчанию для товаров</h2>";
echo "<p>Найдено товаров без изображений: " . count($products) . "</p>";

if (isset($_GET['do']) && $_GET['do'] == 'update') {
    $updated = 0;
    foreach ($products as $product) {
        $stmt = $db->prepare("UPDATE products SET image = :image WHERE id = :id");
        $stmt->execute([
            ':image' => $defaultImage,
            ':id' => $product['id']
        ]);
        $updated++;
    }
    echo "<p style='color:green;'>Обновлено товаров: $updated</p>";
    echo "<p><a href='products.php'>Вернуться к товарам</a></p>";
} else {
    echo "<p>Имя файла изображения: <strong>$defaultImage</strong></p>";
    echo "<p>Убедитесь, что файл находится в папке uploads/products/</p>";
    echo "<p><a href='?do=update' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Обновить все товары</a></p>";
}
?>






