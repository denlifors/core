<?php
require_once 'config/config.php';

$db = getDBConnection();

echo "<h2>Отладка баннеров</h2>";
echo "<style>body { font-family: Arial; padding: 2rem; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background: #f2f2f2; }</style>";

// Проверяем структуру таблицы
echo "<h3>Структура таблицы banners:</h3>";
try {
    $columns = $db->query("SHOW COLUMNS FROM banners")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>Поле</th><th>Тип</th><th>Null</th><th>Ключ</th><th>По умолчанию</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Ошибка: " . $e->getMessage() . "</p>";
}

// Проверяем все баннеры
echo "<h3>Все баннеры в базе:</h3>";
try {
    $stmt = $db->query("SELECT * FROM banners");
    $allBanners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Всего баннеров: " . count($allBanners) . "</p>";
    if (count($allBanners) > 0) {
        echo "<table><tr><th>ID</th><th>Title</th><th>Page</th><th>Status</th><th>Image</th><th>Link</th></tr>";
        foreach ($allBanners as $b) {
            echo "<tr>";
            echo "<td>{$b['id']}</td>";
            echo "<td>" . htmlspecialchars($b['title'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($b['page'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($b['status'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($b['image'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($b['link'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Ошибка: " . $e->getMessage() . "</p>";
}

// Проверяем активные баннеры для главной
echo "<h3>Активные баннеры для главной (как в index.php):</h3>";
try {
    $testQuery = $db->query("SELECT page FROM banners LIMIT 1");
    $hasPageColumn = true;
} catch (PDOException $e) {
    $hasPageColumn = false;
}

if ($hasPageColumn) {
    $stmt = $db->query("SELECT * FROM banners WHERE status = 'active' AND (page = 'home' OR page = 'all') ORDER BY sort_order ASC, created_at DESC");
} else {
    $stmt = $db->query("SELECT * FROM banners WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC");
}
$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<p>Найдено активных баннеров для главной: " . count($banners) . "</p>";
if (count($banners) > 0) {
    echo "<table><tr><th>ID</th><th>Title</th><th>Page</th><th>Status</th><th>Image</th></tr>";
    foreach ($banners as $b) {
        echo "<tr>";
        echo "<td>{$b['id']}</td>";
        echo "<td>" . htmlspecialchars($b['title'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($b['page'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($b['status'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($b['image'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>






