<?php
require_once 'config/config.php';

$db = getDBConnection();

echo "<h2>Обновление базы данных</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 2rem; background: #f5f5f5; }
    h2 { color: #333; }
    p { padding: 0.5rem; background: white; margin: 0.5rem 0; border-radius: 4px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: #666; }
</style>";

try {
    // Check if columns exist and add them
    $columns = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('is_trending', $columns)) {
        $db->exec("ALTER TABLE products ADD COLUMN is_trending BOOLEAN DEFAULT FALSE");
        echo "<p class='success'>✓ Добавлено поле is_trending</p>";
    } else {
        echo "<p class='info'>⚠ Поле is_trending уже существует</p>";
    }
    
    if (!in_array('is_superprice', $columns)) {
        $db->exec("ALTER TABLE products ADD COLUMN is_superprice BOOLEAN DEFAULT FALSE");
        echo "<p class='success'>✓ Добавлено поле is_superprice</p>";
    } else {
        echo "<p class='info'>⚠ Поле is_superprice уже существует</p>";
    }
    
    if (!in_array('sales_count', $columns)) {
        $db->exec("ALTER TABLE products ADD COLUMN sales_count INT DEFAULT 0");
        echo "<p class='success'>✓ Добавлено поле sales_count</p>";
    } else {
        echo "<p class='info'>⚠ Поле sales_count уже существует</p>";
    }
    
    // Add indexes
    try {
        $db->exec("CREATE INDEX idx_trending ON products(is_trending)");
        echo "<p class='success'>✓ Создан индекс idx_trending</p>";
    } catch (Exception $e) {
        echo "<p class='info'>⚠ Индекс idx_trending уже существует или ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    try {
        $db->exec("CREATE INDEX idx_superprice ON products(is_superprice)");
        echo "<p class='success'>✓ Создан индекс idx_superprice</p>";
    } catch (Exception $e) {
        echo "<p class='info'>⚠ Индекс idx_superprice уже существует или ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    try {
        $db->exec("CREATE INDEX idx_sales_count ON products(sales_count)");
        echo "<p class='success'>✓ Создан индекс idx_sales_count</p>";
    } catch (Exception $e) {
        echo "<p class='info'>⚠ Индекс idx_sales_count уже существует или ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h3 style='color:green;'>База данных успешно обновлена!</h3>";
    echo "<p><a href='index.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Перейти на главную страницу</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>






