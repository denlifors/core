<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();

try {
    // Check if columns exist
    $columns = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    
    $newFields = [
        'release_form' => 'TEXT',
        'active_substances' => 'TEXT',
        'duration' => 'TEXT',
        'nutritional_value' => 'TEXT',
        'storage_conditions' => 'TEXT',
        'shelf_life' => 'VARCHAR(255)',
        'manufacturer' => 'TEXT',
        'packaging' => 'TEXT',
        'documentation' => 'TEXT'
    ];
    
    $added = [];
    foreach ($newFields as $field => $type) {
        if (!in_array($field, $columns)) {
            $db->exec("ALTER TABLE products ADD COLUMN $field $type");
            $added[] = $field;
        }
    }
    
    if (empty($added)) {
        echo "Все поля уже существуют в таблице products.";
    } else {
        echo "Успешно добавлены поля: " . implode(', ', $added);
    }
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>


