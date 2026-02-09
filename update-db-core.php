<?php
require_once 'config/config.php';

$db = getDBConnection();

echo "<h2>Обновление базы данных (ядро партнёров)</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 2rem; background: #f5f5f5; }
    h2 { color: #333; }
    p { padding: 0.5rem; background: white; margin: 0.5rem 0; border-radius: 4px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: #666; }
</style>";

try {
    // Ensure columns exist in users
    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('core_user_id', $columns)) {
        $db->exec("ALTER TABLE users ADD COLUMN core_user_id VARCHAR(64) NULL");
        echo "<p class='success'>✓ Добавлено поле core_user_id</p>";
    } else {
        echo "<p class='info'>⚠ Поле core_user_id уже существует</p>";
    }

    if (!in_array('core_partner_id', $columns)) {
        $db->exec("ALTER TABLE users ADD COLUMN core_partner_id VARCHAR(64) NULL");
        echo "<p class='success'>✓ Добавлено поле core_partner_id</p>";
    } else {
        echo "<p class='info'>⚠ Поле core_partner_id уже существует</p>";
    }

    if (!in_array('core_customer_id', $columns)) {
        $db->exec("ALTER TABLE users ADD COLUMN core_customer_id VARCHAR(64) NULL");
        echo "<p class='success'>✓ Добавлено поле core_customer_id</p>";
    } else {
        echo "<p class='info'>⚠ Поле core_customer_id уже существует</p>";
    }

    // Create partner_registrations table
    $db->exec(
        "CREATE TABLE IF NOT EXISTS partner_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            password_plain VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
            core_user_id VARCHAR(64) NULL,
            core_partner_id VARCHAR(64) NULL,
            core_customer_id VARCHAR(64) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            confirmed_at TIMESTAMP NULL,
            INDEX idx_email (email),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    // Ensure core_customer_id exists in partner_registrations
    try {
        $regColumns = $db->query("SHOW COLUMNS FROM partner_registrations")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('core_customer_id', $regColumns)) {
            $db->exec("ALTER TABLE partner_registrations ADD COLUMN core_customer_id VARCHAR(64) NULL");
            echo "<p class='success'>✓ Добавлено поле core_customer_id в partner_registrations</p>";
        }
    } catch (Exception $e) {
        echo "<p class='info'>⚠ Не удалось проверить partner_registrations: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    echo "<p class='success'>✓ Таблица partner_registrations готова</p>";

    echo "<h3 style='color:green;'>База данных успешно обновлена!</h3>";
    echo "<p><a href='index.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Перейти на главную страницу</a></p>";
} catch (Exception $e) {
    echo "<p class='error'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
