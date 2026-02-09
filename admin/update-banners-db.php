<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    die('Access denied');
}

$db = getDBConnection();

echo "<h2>Обновление таблицы баннеров</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 2rem; background: #f5f5f5; }
    h2 { color: #333; }
    p { padding: 0.5rem; background: white; margin: 0.5rem 0; border-radius: 4px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: #666; }
</style>";

try {
    $columns = $db->query("SHOW COLUMNS FROM banners")->fetchAll(PDO::FETCH_COLUMN);
    
    $fields = [
        'page' => "VARCHAR(50) DEFAULT 'home'",
        'description' => 'TEXT',
        'button_text' => 'VARCHAR(255)',
        'title_position' => "VARCHAR(20) DEFAULT 'left'",
        'title_x' => 'INT DEFAULT 50',
        'title_y' => 'INT DEFAULT 100',
        'desc_x' => 'INT DEFAULT 50',
        'desc_y' => 'INT DEFAULT 200',
        'button_x' => 'INT DEFAULT 50',
        'button_y' => 'INT DEFAULT 300',
        'image_position' => "VARCHAR(20) DEFAULT 'right'",
        'image_x' => 'INT DEFAULT 60',
        'image_y' => 'INT DEFAULT 50'
    ];
    
    foreach ($fields as $field => $type) {
        if (!in_array($field, $columns)) {
            $db->exec("ALTER TABLE banners ADD COLUMN $field $type");
            echo "<p class='success'>✓ Добавлено поле $field</p>";
        } else {
            echo "<p class='info'>⚠ Поле $field уже существует</p>";
        }
    }
    
    echo "<h3 style='color:green;'>Таблица баннеров успешно обновлена!</h3>";
    echo "<p><a href='banners.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Вернуться к баннерам</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>






