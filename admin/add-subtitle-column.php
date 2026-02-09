<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();

try {
    // Check if subtitle column exists
    $columns = $db->query("SHOW COLUMNS FROM banners")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('subtitle', $columns)) {
        $db->exec("ALTER TABLE banners ADD COLUMN subtitle VARCHAR(255) DEFAULT NULL");
        echo "Поле 'subtitle' успешно добавлено в таблицу banners.";
    } else {
        echo "Поле 'subtitle' уже существует в таблице banners.";
    }
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>


