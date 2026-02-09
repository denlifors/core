<?php
/**
 * Complete Database Setup Script
 * Creates all tables and inserts initial data
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Установка базы данных DenLiFors</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #667eea; }
        .success { color: #48bb78; font-weight: bold; }
        .error { color: #f56565; font-weight: bold; }
        .warning { color: #ed8936; }
        .info { color: #4299e1; }
        pre { background: #f7fafc; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .step { margin: 20px 0; padding: 15px; background: #f7fafc; border-left: 4px solid #667eea; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Установка базы данных DenLiFors</h1>
        
<?php

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'DenLiFors';

echo "<div class='step'><h2>Шаг 1: Подключение к MySQL</h2>";

try {
    // Connect to MySQL server
    $pdo = new PDO(
        "mysql:host=$db_host;charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p class='success'>✓ Успешно подключено к MySQL серверу</p>";
} catch (PDOException $e) {
    echo "<p class='error'>✗ Ошибка подключения: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Проверьте, что MySQL запущен в XAMPP</p>";
    exit;
}

echo "</div><div class='step'><h2>Шаг 2: Проверка/создание базы данных</h2>";

try {
    // Try to use the database
    $pdo->exec("USE `$db_name`");
    echo "<p class='success'>✓ База данных '$db_name' существует и выбрана</p>";
} catch (PDOException $e) {
    // Database doesn't exist, create it
    try {
        $pdo->exec("CREATE DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        echo "<p class='success'>✓ База данных '$db_name' создана</p>";
    } catch (PDOException $e2) {
        echo "<p class='error'>✗ Не удалось создать базу данных: " . htmlspecialchars($e2->getMessage()) . "</p>";
        exit;
    }
}

echo "</div><div class='step'><h2>Шаг 3: Создание таблиц</h2>";

// SQL statements to create tables
$sql_statements = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        phone VARCHAR(20),
        role ENUM('user', 'partner') DEFAULT 'user',
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        parent_id INT NULL,
        image VARCHAR(255),
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        sku VARCHAR(100) UNIQUE NOT NULL,
        description TEXT,
        full_description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        old_price DECIMAL(10, 2) NULL,
        stock INT DEFAULT 0,
        category_id INT,
        image VARCHAR(255),
        images TEXT,
        status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
        is_featured BOOLEAN DEFAULT FALSE,
        weight DECIMAL(8, 2),
        volume VARCHAR(50),
        composition TEXT,
        usage_method TEXT,
        contraindications TEXT,
        view_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        INDEX idx_category (category_id),
        INDEX idx_status (status),
        INDEX idx_featured (is_featured),
        INDEX idx_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS product_attributes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        type ENUM('text', 'number', 'select') DEFAULT 'text'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS product_attribute_values (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        attribute_id INT NOT NULL,
        value TEXT NOT NULL,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (attribute_id) REFERENCES product_attributes(id) ON DELETE CASCADE,
        INDEX idx_product (product_id),
        INDEX idx_attribute (attribute_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        session_id VARCHAR(255),
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_user (user_id),
        INDEX idx_session (session_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
        total DECIMAL(10, 2) NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        email VARCHAR(255),
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(100),
        postal_code VARCHAR(20),
        payment_method VARCHAR(50),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user (user_id),
        INDEX idx_status (status),
        INDEX idx_order_number (order_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        product_sku VARCHAR(100) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        quantity INT NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        content TEXT,
        meta_title VARCHAR(255),
        meta_description TEXT,
        status ENUM('published', 'draft') DEFAULT 'published',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        image VARCHAR(255) NOT NULL,
        link VARCHAR(255),
        position VARCHAR(50),
        sort_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

$tables_created = 0;
$tables_existed = 0;

foreach ($sql_statements as $sql) {
    try {
        // Extract table name for display
        if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $sql, $matches)) {
            $table_name = $matches[1];
        } else {
            $table_name = 'unknown';
        }
        
        $pdo->exec($sql);
        $tables_created++;
        echo "<p class='success'>✓ Таблица '$table_name' создана</p>";
    } catch (PDOException $e) {
        $error_msg = $e->getMessage();
        if (strpos($error_msg, 'already exists') !== false) {
            $tables_existed++;
            echo "<p class='warning'>⚠ Таблица '$table_name' уже существует</p>";
        } else {
            echo "<p class='error'>✗ Ошибка при создании таблицы '$table_name': " . htmlspecialchars($error_msg) . "</p>";
        }
    }
}

echo "</div><div class='step'><h2>Шаг 4: Вставка начальных данных</h2>";

// Insert default admin user (is_admin = TRUE, role = 'user')
try {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (email, password, first_name, last_name, role, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin@denlifors.ru', $admin_password, 'Admin', 'Admin', 'user', true]);
    echo "<p class='success'>✓ Администратор создан (email: admin@denlifors.ru, пароль: admin123, is_admin=TRUE, role=user)</p>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        // Update existing admin to set is_admin = TRUE
        $stmt = $pdo->prepare("UPDATE users SET is_admin = TRUE, role = 'user' WHERE email = ?");
        $stmt->execute(['admin@denlifors.ru']);
        echo "<p class='warning'>⚠ Администратор уже существует, обновлен (is_admin=TRUE, role=user)</p>";
    } else {
        echo "<p class='error'>✗ Ошибка создания администратора: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Insert categories
$categories = [
    ['Витамины и минералы', 'vitamins', 'Витаминные комплексы и минеральные добавки'],
    ['Для иммунитета', 'immunity', 'Средства для укрепления иммунной системы'],
    ['Для пищеварения', 'digestion', 'Продукты для улучшения пищеварения'],
    ['Для энергии', 'energy', 'Продукты для повышения энергии и тонуса'],
    ['Для красоты', 'beauty', 'Добавки для красоты кожи, волос и ногтей'],
    ['Для суставов', 'joints', 'Средства для здоровья суставов']
];

$stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)");
foreach ($categories as $cat) {
    try {
        $stmt->execute($cat);
        echo "<p class='info'>✓ Категория '{$cat[0]}' добавлена</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') === false) {
            echo "<p class='warning'>⚠ Категория '{$cat[0]}' уже существует или ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// Insert sample products
$products = [
    ['Витаминный комплекс "Энергия"', 'vitamin-energy', 'DL-001', 'Комплекс витаминов для повышения энергии и общего тонуса организма', 1290.00, 1590.00, 50, 1, 'Витамины группы B, витамин C, магний, цинк', 'По 1 капсуле в день во время еды'],
    ['Иммуно-форт', 'immuno-fort', 'DL-002', 'Укрепление иммунной системы и защита от вирусов', 1890.00, NULL, 30, 2, 'Эхинацея, витамин C, цинк, прополис', 'По 2 капсулы в день'],
    ['Детокс-комплекс', 'detox-complex', 'DL-003', 'Очищение организма и улучшение пищеварения', 1490.00, 1790.00, 25, 3, 'Расторопша, артишок, клетчатка', 'По 1 капсуле утром и вечером'],
    ['Энергия-плюс', 'energy-plus', 'DL-004', 'Повышение энергии и работоспособности', 2190.00, NULL, 40, 4, 'Коэнзим Q10, женьшень, витамины группы B', 'По 1 капсуле утром'],
    ['Красота и молодость', 'beauty-youth', 'DL-005', 'Комплекс для здоровья кожи, волос и ногтей', 2490.00, 2890.00, 35, 5, 'Коллаген, гиалуроновая кислота, биотин', 'По 2 капсулы в день'],
    ['Суставы-про', 'joints-pro', 'DL-006', 'Поддержка здоровья суставов и хрящей', 1690.00, NULL, 45, 6, 'Глюкозамин, хондроитин, MSM', 'По 3 капсулы в день']
];

$stmt = $pdo->prepare("INSERT IGNORE INTO products (name, slug, sku, description, price, old_price, stock, category_id, composition, usage_method, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($products as $prod) {
    try {
        $stmt->execute([$prod[0], $prod[1], $prod[2], $prod[3], $prod[4], $prod[5], $prod[6], $prod[7], $prod[8], $prod[9], 1]);
        echo "<p class='info'>✓ Товар '{$prod[0]}' добавлен</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') === false) {
            echo "<p class='warning'>⚠ Товар '{$prod[0]}' уже существует или ошибка</p>";
        }
    }
}

echo "</div>";

echo "<div class='step'>";
echo "<h2 class='success'>✅ Установка завершена!</h2>";
echo "<p><strong>Статистика:</strong></p>";
echo "<ul>";
echo "<li>Таблиц создано: $tables_created</li>";
if ($tables_existed > 0) {
    echo "<li>Таблиц уже существовало: $tables_existed</li>";
}
echo "</ul>";

echo "<p><strong>Учетные данные администратора:</strong></p>";
echo "<ul>";
echo "<li>Email: <strong>admin@denlifors.ru</strong></li>";
echo "<li>Пароль: <strong>admin123</strong></li>";
echo "</ul>";

echo "<p class='error'><strong>⚠ ВАЖНО: Измените пароль администратора после первого входа!</strong></p>";

echo "<p><a href='index.php' style='display:inline-block;padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;margin:5px;'>Перейти на сайт</a>";
echo "<a href='admin/' style='display:inline-block;padding:10px 20px;background:#48bb78;color:white;text-decoration:none;border-radius:5px;margin:5px;'>Войти в админ-панель</a></p>";
echo "</div>";

?>
    </div>
</body>
</html>






