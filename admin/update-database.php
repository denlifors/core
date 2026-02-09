<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    die('Access denied');
}

$db = getDBConnection();

echo "<h2>Обновление базы данных</h2>";

try {
    // Check and add new columns
    $columns = $db->query("SHOW COLUMNS FROM products LIKE 'is_trending'")->fetch();
    if (!$columns) {
        $db->exec("ALTER TABLE products ADD COLUMN is_trending BOOLEAN DEFAULT FALSE");
        echo "<p>✓ Добавлено поле is_trending</p>";
    } else {
        echo "<p>⚠ Поле is_trending уже существует</p>";
    }
    
    $columns = $db->query("SHOW COLUMNS FROM products LIKE 'is_superprice'")->fetch();
    if (!$columns) {
        $db->exec("ALTER TABLE products ADD COLUMN is_superprice BOOLEAN DEFAULT FALSE");
        echo "<p>✓ Добавлено поле is_superprice</p>";
    } else {
        echo "<p>⚠ Поле is_superprice уже существует</p>";
    }
    
    $columns = $db->query("SHOW COLUMNS FROM products LIKE 'sales_count'")->fetch();
    if (!$columns) {
        $db->exec("ALTER TABLE products ADD COLUMN sales_count INT DEFAULT 0");
        echo "<p>✓ Добавлено поле sales_count</p>";
    } else {
        echo "<p>⚠ Поле sales_count уже существует</p>";
    }
    
    // Add indexes
    try {
        $db->exec("CREATE INDEX idx_trending ON products(is_trending)");
        echo "<p>✓ Создан индекс idx_trending</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Индекс idx_trending уже существует</p>";
    }
    
    try {
        $db->exec("CREATE INDEX idx_superprice ON products(is_superprice)");
        echo "<p>✓ Создан индекс idx_superprice</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Индекс idx_superprice уже существует</p>";
    }
    
    try {
        $db->exec("CREATE INDEX idx_sales_count ON products(sales_count)");
        echo "<p>✓ Создан индекс idx_sales_count</p>";
    } catch (Exception $e) {
        echo "<p>⚠ Индекс idx_sales_count уже существует</p>";
    }
    
    echo "<h3 style='color:green;'>База данных успешно обновлена!</h3>";
    echo "<p><a href='products.php'>Вернуться к товарам</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

