<?php
/**
 * Script to check and fix admin user
 * Run this file to ensure admin user exists with correct credentials
 */

require_once '../config/database.php';

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Исправление администратора - ДенЛиФорс</title>
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
            max-width: 600px;
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
        .credentials {
            background: #f7fafc;
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1.5rem 0;
            border: 2px solid #e2e8f0;
        }
        .credentials strong {
            color: #1a202c;
            font-size: 1.1rem;
        }
        .btn {
            display: inline-block;
            padding: 0.875rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            margin-top: 1rem;
            transition: transform 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Исправление администратора</h1>";

try {
    $db = getDBConnection();
    
    // Check if admin exists
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => 'admin@denlifors.ru']);
    $admin = $stmt->fetch();
    
    // Check if is_admin column exists, if not, run migration first
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "<div class='error'>✗ Поле is_admin не найдено в таблице users</div>";
        echo "<div class='info'>Пожалуйста, сначала запустите миграцию: <a href='migrate-admin-field.php'>migrate-admin-field.php</a></div>";
    } else {
        if ($admin) {
            echo "<div class='info'>✓ Пользователь с email admin@denlifors.ru найден</div>";
            
            // Check is_admin flag
            if (!isset($admin['is_admin']) || !$admin['is_admin']) {
                echo "<div class='error'>⚠ Пользователь не является администратором (is_admin = FALSE)</div>";
                
                // Set is_admin = TRUE and ensure role is 'user' or 'partner' (not 'admin')
                $newRole = ($admin['role'] === 'partner') ? 'partner' : 'user';
                $stmt = $db->prepare("UPDATE users SET is_admin = TRUE, role = :role WHERE email = :email");
                $stmt->execute([
                    ':role' => $newRole,
                    ':email' => 'admin@denlifors.ru'
                ]);
                echo "<div class='success'>✓ Администратор активирован (is_admin=TRUE, role={$newRole})</div>";
            } else {
                echo "<div class='success'>✓ Пользователь уже является администратором</div>";
                echo "<div class='info'>Текущая роль: " . htmlspecialchars($admin['role'] ?? 'user') . "</div>";
            }
            
            // Reset password to admin123
            $new_password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = :password WHERE email = :email");
            $stmt->execute([
                ':password' => $new_password,
                ':email' => 'admin@denlifors.ru'
            ]);
            echo "<div class='success'>✓ Пароль сброшен на 'admin123'</div>";
            
        } else {
            echo "<div class='error'>✗ Пользователь с email admin@denlifors.ru не найден</div>";
            
            // Create admin user with is_admin = TRUE and role = 'user'
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (email, password, first_name, last_name, role, is_admin) VALUES (:email, :password, :first_name, :last_name, :role, :is_admin)");
            $stmt->execute([
                ':email' => 'admin@denlifors.ru',
                ':password' => $admin_password,
                ':first_name' => 'Admin',
                ':last_name' => 'Admin',
                ':role' => 'user',
                ':is_admin' => true
            ]);
            echo "<div class='success'>✓ Администратор создан (is_admin=TRUE, role=user)</div>";
        }
    }
    
    echo "<div class='credentials'>
        <h2 style='margin-bottom: 1rem;'>Учетные данные администратора:</h2>
        <p><strong>Email:</strong> admin@denlifors.ru</p>
        <p><strong>Пароль:</strong> admin123</p>
    </div>";
    
    echo "<div class='info'>
        <strong>⚠ ВАЖНО:</strong> После входа в админ-панель обязательно измените пароль на более безопасный!
    </div>";
    
    echo "<a href='login.php' class='btn'>Перейти к входу в админ-панель</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Ошибка: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>Проверьте настройки подключения к базе данных в config/database.php</div>";
}

echo "</div></body></html>";
?>

