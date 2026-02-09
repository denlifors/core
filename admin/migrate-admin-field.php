<?php
/**
 * Migration script to add is_admin field and update role structure
 * This separates admin status from user role (user/partner)
 */

require_once '../config/database.php';

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Миграция: Админ как отдельное свойство - ДенЛиФорс</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #2E2216;
        }
        .success {
            background: #c6f6d5;
            color: #22543d;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border: 1px solid #9ae6b4;
        }
        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border: 1px solid #fc8181;
        }
        .info {
            background: #bee3f8;
            color: #2c5282;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border: 1px solid #90cdf4;
        }
        .step {
            margin: 1.5rem 0;
            padding: 1rem;
            background: #f7fafc;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Миграция: Админ как отдельное свойство</h1>";

try {
    $db = getDBConnection();
    
    echo "<div class='step'>";
    echo "<h2>Шаг 1: Проверка структуры таблицы</h2>";
    
    // Check if is_admin column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "<div class='info'>Добавляем поле is_admin...</div>";
        
        // Add is_admin column
        $db->exec("ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE AFTER role");
        echo "<div class='success'>✓ Поле is_admin добавлено</div>";
    } else {
        echo "<div class='info'>✓ Поле is_admin уже существует</div>";
    }
    
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>Шаг 2: Миграция существующих администраторов</h2>";
    
    // Find all users with role = 'admin'
    $stmt = $db->query("SELECT id, email, role FROM users WHERE role = 'admin'");
    $adminUsers = $stmt->fetchAll();
    
    if (count($adminUsers) > 0) {
        echo "<div class='info'>Найдено администраторов: " . count($adminUsers) . "</div>";
        
        foreach ($adminUsers as $admin) {
            // Set is_admin = TRUE and change role to 'user' or 'partner'
            // We'll set to 'user' by default, but keep existing role if it's 'partner'
            $newRole = ($admin['role'] === 'partner') ? 'partner' : 'user';
            
            $stmt = $db->prepare("UPDATE users SET is_admin = TRUE, role = :role WHERE id = :id");
            $stmt->execute([
                ':role' => $newRole,
                ':id' => $admin['id']
            ]);
            
            echo "<div class='success'>✓ Пользователь {$admin['email']} (ID: {$admin['id']}) обновлен: is_admin=TRUE, role={$newRole}</div>";
        }
    } else {
        echo "<div class='info'>Администраторов с role='admin' не найдено</div>";
    }
    
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>Шаг 3: Обновление структуры ENUM для role</h2>";
    
    // Note: MySQL doesn't support removing values from ENUM directly
    // We'll need to alter the column to remove 'admin' from ENUM
    // This is a complex operation, so we'll do it carefully
    
    try {
        // Get current ENUM values
        $stmt = $db->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
        $column = $stmt->fetch();
        
        if ($column && strpos($column['Type'], 'enum') !== false) {
            // Check if 'admin' is still in ENUM
            if (strpos($column['Type'], "'admin'") !== false) {
                echo "<div class='info'>Обновляем ENUM role, убирая 'admin'...</div>";
                
                // Alter column to remove 'admin' from ENUM
                // We'll change it to only 'user' and 'partner'
                $db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'partner') DEFAULT 'user'");
                
                echo "<div class='success'>✓ ENUM role обновлен (теперь только 'user' и 'partner')</div>";
            } else {
                echo "<div class='info'>✓ ENUM role уже не содержит 'admin'</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='error'>⚠ Предупреждение при обновлении ENUM: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<div class='info'>Это нормально, если структура уже обновлена</div>";
    }
    
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>Шаг 4: Проверка результата</h2>";
    
    // Check final state
    $stmt = $db->query("SELECT id, email, role, is_admin FROM users WHERE is_admin = TRUE");
    $finalAdmins = $stmt->fetchAll();
    
    if (count($finalAdmins) > 0) {
        echo "<div class='success'><strong>Администраторы после миграции:</strong></div>";
        foreach ($finalAdmins as $admin) {
            echo "<div class='info'>ID: {$admin['id']}, Email: {$admin['email']}, Role: {$admin['role']}, is_admin: " . ($admin['is_admin'] ? 'TRUE' : 'FALSE') . "</div>";
        }
    } else {
        echo "<div class='info'>Администраторов не найдено. Создайте администратора через fix-admin.php</div>";
    }
    
    echo "</div>";
    
    echo "<div class='success' style='margin-top: 2rem;'><strong>✅ Миграция завершена успешно!</strong></div>";
    echo "<div class='info' style='margin-top: 1rem;'>Теперь админ - это отдельное свойство (is_admin), а не роль. Пользователь может быть одновременно клиентом/партнёром и администратором.</div>";
    
    echo "<div style='margin-top: 2rem;'>";
    echo "<a href='fix-admin.php' style='display:inline-block;padding:0.875rem 2rem;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;text-decoration:none;border-radius:50px;font-weight:600;margin-right:1rem;'>Исправить администратора</a>";
    echo "<a href='login.php' style='display:inline-block;padding:0.875rem 2rem;background:#48bb78;color:white;text-decoration:none;border-radius:50px;font-weight:600;'>Войти в админ-панель</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Ошибка: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>Проверьте настройки подключения к базе данных в config/database.php</div>";
}

echo "</div></body></html>";
?>

