<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    die('Access denied');
}

$db = getDBConnection();

echo "<h2>Создание таблицы статей</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 2rem; background: #f5f5f5; }
    h2 { color: #333; }
    p { padding: 0.5rem; background: white; margin: 0.5rem 0; border-radius: 4px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: #666; }
</style>";

try {
    // Check if table exists
    $tableExists = false;
    try {
        $db->query("SELECT 1 FROM articles LIMIT 1");
        $tableExists = true;
    } catch (PDOException $e) {
        $tableExists = false;
    }
    
    if ($tableExists) {
        echo "<p class='info'>⚠ Таблица articles уже существует.</p>";
    } else {
        // Create table directly
        $sql = "CREATE TABLE IF NOT EXISTS articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            excerpt TEXT,
            content TEXT,
            image VARCHAR(255),
            status ENUM('published', 'draft') DEFAULT 'published',
            view_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "<p class='success'>✓ Таблица articles успешно создана!</p>";
    }
    
    echo "<h3 style='color:green;'>Готово!</h3>";
    echo "<p><a href='articles.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Перейти к статьям</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

