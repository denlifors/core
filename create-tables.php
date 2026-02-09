<?php
/**
 * Quick script to create tables in existing DenLiFors database
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Creating Tables</title>";
echo "<style>body{font-family:Arial;padding:20px;max-width:800px;margin:0 auto;}";
echo ".success{color:green;} .error{color:red;} .warning{color:orange;}</style></head><body>";
echo "<h1>Создание таблиц в базе данных DenLiFors</h1>";

try {
    $db = getDBConnection();
    echo "<p class='success'>✓ Подключение к базе данных успешно</p>";
    
    // Read schema file
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    
    // Remove CREATE DATABASE and USE statements
    $schema = preg_replace('/CREATE DATABASE.*?;/i', '', $schema);
    $schema = preg_replace('/USE.*?;/i', '', $schema);
    
    // Split into statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    $created = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || preg_match('/^--/', $statement)) {
            continue;
        }
        
        try {
            $db->exec($statement);
            $created++;
            
            // Check if it was a CREATE TABLE statement
            if (preg_match('/CREATE TABLE/i', $statement)) {
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    $tableName = $matches[1];
                    echo "<p class='success'>✓ Таблица '$tableName' создана</p>";
                }
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Ignore if table already exists
            if (strpos($errorMsg, 'already exists') !== false) {
                $skipped++;
                if (preg_match('/Table.*?`?(\w+)`?/i', $errorMsg, $matches)) {
                    echo "<p class='warning'>⚠ Таблица '{$matches[1]}' уже существует</p>";
                }
            } 
            // Ignore duplicate entries in INSERT
            elseif (strpos($errorMsg, 'Duplicate entry') !== false) {
                // Skip duplicate inserts
                continue;
            } else {
                $errors[] = $errorMsg;
                echo "<p class='error'>✗ Ошибка: " . htmlspecialchars($errorMsg) . "</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h2>Результат:</h2>";
    echo "<p>Создано таблиц/записей: <strong>$created</strong></p>";
    if ($skipped > 0) {
        echo "<p>Пропущено (уже существуют): <strong>$skipped</strong></p>";
    }
    
    if (empty($errors)) {
        echo "<h2 class='success'>✓ Готово! Все таблицы созданы успешно.</h2>";
        echo "<p><a href='index.php'>Перейти на главную страницу</a></p>";
        echo "<p><a href='admin/'>Перейти в админ-панель</a></p>";
    } else {
        echo "<h2 class='error'>Обнаружены ошибки при создании некоторых таблиц</h2>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Ошибка подключения: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Проверьте настройки в config/database.php</p>";
}

echo "</body></html>";
?>






