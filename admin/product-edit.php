<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($productId > 0) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
}

// Get categories
$stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Check if new columns exist
$hasNewColumns = false;
try {
    $testQuery = $db->query("SELECT is_trending, is_superprice, sales_count FROM products LIMIT 1");
    $hasNewColumns = true;
} catch (PDOException $e) {
    $hasNewColumns = false;
}

// Handle product activation
if (isset($_GET['activate']) && is_numeric($_GET['activate'])) {
    $activateId = (int)$_GET['activate'];
    $stmt = $db->prepare("UPDATE products SET status = 'active' WHERE id = :id");
    $stmt->execute([':id' => $activateId]);
    redirect('product-edit.php?id=' . $activateId);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    // Автоматическая генерация артикула, если не указан
    $sku = sanitize($_POST['sku'] ?? '');
    if (empty($sku) && $productId == 0) {
        // Генерируем артикул DL-XXXXXXXXX (9 цифр)
        do {
            $sku = 'DL-' . str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT);
            $checkStmt = $db->prepare("SELECT id FROM products WHERE sku = :sku");
            $checkStmt->execute([':sku' => $sku]);
        } while ($checkStmt->fetch()); // Проверяем уникальность
    }
    $slug = sanitize($_POST['slug'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $fullDescription = sanitize($_POST['full_description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $oldPrice = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
    $stock = (int)($_POST['stock'] ?? 0);
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    // По умолчанию статус inactive, если не указан явно
    $status = 'inactive'; // Всегда неактивен при сохранении
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $image = sanitize($_POST['image'] ?? '');
    $imagesJson = sanitize($_POST['images_json'] ?? '[]');
    $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
    $volume = sanitize($_POST['volume'] ?? '');
    $composition = sanitize($_POST['composition'] ?? '');
    $usageMethod = sanitize($_POST['usage_method'] ?? '');
    $contraindications = sanitize($_POST['contraindications'] ?? '');
    
    // New detailed fields
    $releaseForm = sanitize($_POST['release_form'] ?? '');
    $activeSubstances = sanitize($_POST['active_substances'] ?? '');
    $duration = sanitize($_POST['duration'] ?? '');
    $nutritionalValue = sanitize($_POST['nutritional_value'] ?? '');
    $storageConditions = sanitize($_POST['storage_conditions'] ?? '');
    $shelfLife = sanitize($_POST['shelf_life'] ?? '');
    $manufacturer = sanitize($_POST['manufacturer'] ?? '');
    $packaging = sanitize($_POST['packaging'] ?? '');
    // Обработка множественных документов
    $documentsJson = '[]';
    if (isset($_POST['documents']) && is_array($_POST['documents'])) {
        $documents = [];
        foreach ($_POST['documents'] as $doc) {
            if (!empty($doc['name']) || !empty($doc['file'])) {
                $documents[] = [
                    'name' => sanitize($doc['name'] ?? ''),
                    'file' => sanitize($doc['file'] ?? '')
                ];
            }
        }
        $documentsJson = json_encode($documents);
    }
    
    // Обработка блока "Что это такое?"
    $whatIsItJson = '[]';
    if (isset($_POST['what_is_it'])) {
        $whatIsIt = [
            'description' => sanitize($_POST['what_is_it']['description'] ?? ''),
            'consists_of' => isset($_POST['what_is_it']['consists_of']) && is_array($_POST['what_is_it']['consists_of']) 
                ? array_map('sanitize', $_POST['what_is_it']['consists_of']) 
                : [],
            'release_form' => isset($_POST['what_is_it']['release_form']) && is_array($_POST['what_is_it']['release_form']) 
                ? array_map('sanitize', $_POST['what_is_it']['release_form']) 
                : [],
            'how_to_take' => sanitize($_POST['what_is_it']['how_to_take'] ?? ''),
            'recommendation' => sanitize($_POST['what_is_it']['recommendation'] ?? ''),
            'nutritional_value' => isset($_POST['what_is_it']['nutritional_value']) && is_array($_POST['what_is_it']['nutritional_value']) 
                ? array_map('sanitize', $_POST['what_is_it']['nutritional_value']) 
                : [],
            'duration' => sanitize($_POST['what_is_it']['duration'] ?? ''),
            'contraindications' => sanitize($_POST['what_is_it']['contraindications'] ?? ''),
            'precautions' => sanitize($_POST['what_is_it']['precautions'] ?? ''),
            'advantages' => isset($_POST['what_is_it']['advantages']) && is_array($_POST['what_is_it']['advantages']) 
                ? array_map('sanitize', $_POST['what_is_it']['advantages']) 
                : []
        ];
        $whatIsItJson = json_encode($whatIsIt);
    }
    // Для обратной совместимости сохраняем в documentation
    $documentation = $documentsJson;
    $documentationFile = ''; // Старое поле больше не используется
    
    // Only include new columns if they exist
    $isTrending = $hasNewColumns && isset($_POST['is_trending']) ? 1 : 0;
    $isSuperprice = $hasNewColumns && isset($_POST['is_superprice']) ? 1 : 0;
    
    // Generate slug if empty
    if (empty($slug)) {
        $slug = mb_strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }
    
    try {
        // Check and create missing columns
        $columns = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        
        $newFields = [
            'documentation' => 'TEXT',
            'documentation_file' => 'VARCHAR(255)',
            'what_is_it' => 'TEXT' // JSON для блока "Что это такое?"
        ];
        
        foreach ($newFields as $field => $type) {
            if (!in_array($field, $columns)) {
                try {
                    $db->exec("ALTER TABLE products ADD COLUMN $field $type");
                } catch (Exception $e) {
                    // Column might already exist or error occurred
                }
            }
        }
        
        // Refresh columns list
        $columns = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        $hasNewProductFields = in_array('documentation', $columns);
        $hasDocumentationFile = in_array('documentation_file', $columns);
        $hasWhatIsIt = in_array('what_is_it', $columns);
        
        // Build dynamic query
        $baseFields = [
            'name' => $name,
            'sku' => $sku,
            'slug' => $slug,
            'description' => $description,
            'full_description' => $fullDescription,
            'price' => $price,
            'old_price' => $oldPrice,
            'stock' => $stock,
            'category_id' => $categoryId,
            'status' => $status,
            'is_featured' => $isFeatured,
            'weight' => $weight,
            'volume' => $volume,
            'composition' => $composition,
            'usage_method' => $usageMethod,
            'contraindications' => $contraindications,
            'image' => $image,
            'images' => $imagesJson
        ];
        
        if ($hasNewColumns) {
            $baseFields['is_trending'] = $isTrending;
            $baseFields['is_superprice'] = $isSuperprice;
        }
        
        if ($hasNewProductFields) {
            $baseFields['documentation'] = $documentation;
        }
        
        if ($hasDocumentationFile) {
            $baseFields['documentation_file'] = $documentationFile;
        }
        
        if ($hasWhatIsIt) {
            $baseFields['what_is_it'] = $whatIsItJson;
        }
        
        if ($productId > 0) {
            // UPDATE
            $updateFields = [];
            $params = [':id' => $productId];
            
            foreach ($baseFields as $field => $value) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $value;
            }
            
            $sql = "UPDATE products SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        } else {
            // INSERT
            $insertFields = array_keys($baseFields);
            $insertValues = array_map(function($field) { return ":$field"; }, $insertFields);
            $params = [];
            
            foreach ($baseFields as $field => $value) {
                $params[":$field"] = $value;
            }
            
            $sql = "INSERT INTO products (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $insertValues) . ")";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $productId = $db->lastInsertId();
        }
        
        redirect('products.php');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Parse existing images
$existingImages = [];
if ($product && $product['images']) {
    $existingImages = json_decode($product['images'], true);
    if (!is_array($existingImages)) {
        $existingImages = [];
    }
}

// Parse existing documents
$existingDocuments = [];
if ($product && !empty($product['documentation'])) {
    $decoded = json_decode($product['documentation'], true);
    if (is_array($decoded)) {
        $existingDocuments = $decoded;
    } else {
        // Старый формат - один документ
        if (!empty($product['documentation_file'])) {
            $existingDocuments = [[
                'name' => basename($product['documentation_file']),
                'file' => $product['documentation_file']
            ]];
        }
    }
}

// Parse existing "Что это такое?"
$existingWhatIsIt = [
    'description' => '',
    'consists_of' => [],
    'release_form' => [],
    'how_to_take' => '',
    'recommendation' => '',
    'nutritional_value' => [],
    'duration' => '',
    'contraindications' => '',
    'precautions' => '',
    'advantages' => []
];
if ($product && !empty($product['what_is_it'])) {
    $decoded = json_decode($product['what_is_it'], true);
    if (is_array($decoded)) {
        $existingWhatIsIt = array_merge($existingWhatIsIt, $decoded);
    }
}

$pageTitle = $productId > 0 ? 'Редактировать товар' : 'Добавить товар';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1><?php echo $productId > 0 ? 'Редактировать товар' : 'Добавить товар'; ?></h1>
        <div style="display: flex; gap: 1rem;">
            <?php if ($productId > 0 && ($product['status'] ?? '') === 'inactive'): ?>
                <a href="?activate=<?php echo $productId; ?>" class="btn-primary" onclick="return confirm('Добавить товар в магазин? Товар станет активным и будет виден покупателям.');">Добавить в магазин</a>
            <?php endif; ?>
            <a href="products.php" class="btn-secondary">Отмена</a>
        </div>
    </div>
    
    <!-- Закрепленная кнопка сохранения -->
    <div class="fixed-save-button" id="fixed-save-button">
        <button type="submit" form="product-form" class="btn-primary">Сохранить</button>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($productId > 0 && ($product['status'] ?? '') === 'inactive'): ?>
        <div class="alert" style="background: #fff3cd; color: #856404; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <strong>Товар неактивен.</strong> Нажмите "Добавить в магазин" чтобы сделать товар видимым для покупателей.
        </div>
    <?php endif; ?>
    
    <form method="POST" class="admin-form" id="product-form">
        <div class="form-section">
            <h2>Основная информация</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Название *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Артикул (SKU) <?php echo $productId > 0 ? '*' : ''; ?></label>
                    <input type="text" name="sku" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>" <?php echo $productId > 0 ? 'required' : 'placeholder="Оставьте пустым для автогенерации (DL-XXXXXXXXX)"'; ?>>
                    <?php if ($productId == 0): ?>
                        <small>Оставьте пустым для автоматической генерации в формате DL-XXXXXXXXX (9 цифр)</small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>URL (slug)</label>
                    <input type="text" name="slug" value="<?php echo htmlspecialchars($product['slug'] ?? ''); ?>">
                    <small>Оставьте пустым для автогенерации</small>
                </div>
                <div class="form-group">
                    <label>Категория</label>
                    <select name="category_id">
                        <option value="">Не выбрано</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] ?? 0) == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Изображения</h2>
            
            <div class="image-upload-section">
                <div class="main-image-upload">
                    <label>Главное изображение</label>
                    <div class="upload-area" id="main-image-upload">
                        <?php if ($product && $product['image']): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="Main image" class="upload-preview">
                            <input type="hidden" name="image" value="<?php echo htmlspecialchars($product['image']); ?>" id="main-image-input">
                        <?php else: ?>
                            <div class="upload-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <p>Перетащите изображение или нажмите для выбора</p>
                                <small>JPG, PNG, GIF до 5MB</small>
                            </div>
                            <input type="hidden" name="image" value="" id="main-image-input">
                        <?php endif; ?>
                        <input type="file" accept="image/*" id="main-image-file" style="display:none;">
                    </div>
                </div>
                
                <div class="gallery-upload">
                    <label>Галерея изображений</label>
                    <div class="upload-area gallery-area" id="gallery-upload">
                        <div class="gallery-preview" id="gallery-preview">
                            <?php foreach ($existingImages as $img): ?>
                                <div class="gallery-item">
                                    <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo htmlspecialchars($img['file']); ?>" alt="Gallery image">
                                    <button type="button" class="gallery-remove" data-file="<?php echo htmlspecialchars($img['file']); ?>">×</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="upload-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p>Перетащите изображения или нажмите для выбора</p>
                            <small>Можно выбрать несколько файлов</small>
                        </div>
                        <input type="file" accept="image/*" id="gallery-files" multiple style="display:none;">
                        <input type="hidden" name="images_json" id="images-json-input" value="<?php echo htmlspecialchars(json_encode($existingImages)); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Цены и наличие</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Цена *</label>
                    <input type="number" name="price" step="0.01" value="<?php echo $product['price'] ?? 0; ?>" required>
                </div>
                <div class="form-group">
                    <label>Старая цена</label>
                    <input type="number" name="old_price" step="0.01" value="<?php echo $product['old_price'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Остаток</label>
                    <input type="number" name="stock" value="<?php echo $product['stock'] ?? 0; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Статус</label>
                    <select name="status">
                        <option value="active" <?php echo ($product['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Активен</option>
                        <option value="inactive" <?php echo ($product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Неактивен</option>
                        <option value="out_of_stock" <?php echo ($product['status'] ?? '') === 'out_of_stock' ? 'selected' : ''; ?>>Нет в наличии</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_featured" value="1" <?php echo ($product['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                        Рекомендуемый товар
                    </label>
                </div>
                <?php if ($hasNewColumns): ?>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_trending" value="1" <?php echo ($product['is_trending'] ?? 0) ? 'checked' : ''; ?>>
                        В тренде
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_superprice" value="1" <?php echo ($product['is_superprice'] ?? 0) ? 'checked' : ''; ?>>
                        Суперцена
                    </label>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Описание</h2>
            
            <div class="form-group">
                <label>Краткое описание</label>
                <textarea name="description" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Полное описание</label>
                <textarea name="full_description" rows="8"><?php echo htmlspecialchars($product['full_description'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Дополнительная информация</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Вес (г)</label>
                    <input type="number" name="weight" step="0.01" value="<?php echo $product['weight'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Объем</label>
                    <input type="text" name="volume" value="<?php echo htmlspecialchars($product['volume'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Состав</label>
                <textarea name="composition" rows="4"><?php echo htmlspecialchars($product['composition'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Способ применения</label>
                <textarea name="usage_method" rows="4"><?php echo htmlspecialchars($product['usage_method'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Противопоказания</label>
                <textarea name="contraindications" rows="4"><?php echo htmlspecialchars($product['contraindications'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Что это такое?</h2>
            
            <div class="form-group">
                <label>Описание</label>
                <textarea name="what_is_it[description]" rows="8" placeholder="Подробное описание товара..."><?php echo htmlspecialchars($existingWhatIsIt['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Из чего состоит</label>
                <div id="consists-of-container">
                    <?php foreach ($existingWhatIsIt['consists_of'] ?? [] as $index => $item): ?>
                        <div class="list-item" data-index="<?php echo $index; ?>">
                            <input type="text" name="what_is_it[consists_of][]" value="<?php echo htmlspecialchars($item); ?>" placeholder="Пункт списка">
                            <button type="button" class="btn-small btn-danger remove-item">Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-secondary add-list-item" data-target="consists-of-container" data-name="what_is_it[consists_of][]">+ Добавить пункт</button>
            </div>
            
            <div class="form-group">
                <label>Форма выпуска</label>
                <div id="release-form-container">
                    <?php foreach ($existingWhatIsIt['release_form'] ?? [] as $index => $item): ?>
                        <div class="list-item" data-index="<?php echo $index; ?>">
                            <input type="text" name="what_is_it[release_form][]" value="<?php echo htmlspecialchars($item); ?>" placeholder="Пункт списка">
                            <button type="button" class="btn-small btn-danger remove-item">Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-secondary add-list-item" data-target="release-form-container" data-name="what_is_it[release_form][]">+ Добавить пункт</button>
            </div>
            
            <div class="form-group">
                <label>Как принимать</label>
                <textarea name="what_is_it[how_to_take]" rows="4" placeholder="Инструкция по применению..."><?php echo htmlspecialchars($existingWhatIsIt['how_to_take'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Рекомендация</label>
                <textarea name="what_is_it[recommendation]" rows="3" placeholder="Рекомендации..."><?php echo htmlspecialchars($existingWhatIsIt['recommendation'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Пищевая и энергетическая ценность</label>
                <div id="nutritional-value-container">
                    <?php foreach ($existingWhatIsIt['nutritional_value'] ?? [] as $index => $item): ?>
                        <div class="list-item" data-index="<?php echo $index; ?>">
                            <input type="text" name="what_is_it[nutritional_value][]" value="<?php echo htmlspecialchars($item); ?>" placeholder="Пункт списка">
                            <button type="button" class="btn-small btn-danger remove-item">Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-secondary add-list-item" data-target="nutritional-value-container" data-name="what_is_it[nutritional_value][]">+ Добавить пункт</button>
            </div>
            
            <div class="form-group">
                <label>Продолжительность приёма</label>
                <textarea name="what_is_it[duration]" rows="2" placeholder="Продолжительность приёма..."><?php echo htmlspecialchars($existingWhatIsIt['duration'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Возможные противопоказания</label>
                <textarea name="what_is_it[contraindications]" rows="3" placeholder="Противопоказания..."><?php echo htmlspecialchars($existingWhatIsIt['contraindications'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Меры предосторожности</label>
                <textarea name="what_is_it[precautions]" rows="3" placeholder="Меры предосторожности..."><?php echo htmlspecialchars($existingWhatIsIt['precautions'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Главные преимущества</label>
                <div id="advantages-container">
                    <?php foreach ($existingWhatIsIt['advantages'] ?? [] as $index => $item): ?>
                        <div class="list-item" data-index="<?php echo $index; ?>">
                            <input type="text" name="what_is_it[advantages][]" value="<?php echo htmlspecialchars($item); ?>" placeholder="Преимущество">
                            <button type="button" class="btn-small btn-danger remove-item">Удалить</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-secondary add-list-item" data-target="advantages-container" data-name="what_is_it[advantages][]">+ Добавить пункт</button>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Документация</h2>
            
            <div class="form-group">
                <label>Документы</label>
                <div id="documents-container">
                    <?php foreach ($existingDocuments as $index => $doc): ?>
                        <div class="document-item" data-index="<?php echo $index; ?>">
                            <div class="form-row">
                                <div class="form-group" style="flex: 1;">
                                    <label>Название документа</label>
                                    <input type="text" name="documents[<?php echo $index; ?>][name]" value="<?php echo htmlspecialchars($doc['name'] ?? ''); ?>" placeholder="Название документа">
                                </div>
                                <div class="form-group" style="flex: 0 0 auto; align-self: flex-end;">
                                    <button type="button" class="btn-small btn-danger remove-document" style="margin-top: 1.5rem;">Удалить</button>
                                </div>
                            </div>
                            <div class="documentation-upload">
                                <div class="upload-area documentation-upload-area" data-index="<?php echo $index; ?>">
                                    <?php if (!empty($doc['file'])): ?>
                                        <div class="documentation-preview">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                                <polyline points="10 9 9 9 8 9"></polyline>
                                            </svg>
                                            <div class="documentation-info">
                                                <p class="documentation-name"><?php echo htmlspecialchars(basename($doc['file'])); ?></p>
                                                <button type="button" class="documentation-remove">Удалить</button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="documents[<?php echo $index; ?>][file]" value="<?php echo htmlspecialchars($doc['file']); ?>" class="documentation-file-input">
                                    <?php else: ?>
                                        <div class="upload-placeholder">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="17 8 12 3 7 8"></polyline>
                                                <line x1="12" y1="3" x2="12" y2="15"></line>
                                            </svg>
                                            <p>Перетащите файл или нажмите для выбора</p>
                                            <small>PDF, DOC, DOCX, JPG, PNG до 10MB</small>
                                        </div>
                                        <input type="hidden" name="documents[<?php echo $index; ?>][file]" value="" class="documentation-file-input">
                                    <?php endif; ?>
                                    <input type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="documentation-file" style="display:none;">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add-document" class="btn-secondary" style="margin-top: 1rem;">+ Добавить документ</button>
            </div>
        </div>
        
        <div class="form-actions" style="display: none;">
            <!-- Кнопки перенесены в header -->
        </div>
    </form>
</div>

<link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/admin-upload.css">
<style>
.fixed-save-button {
    position: fixed;
    top: 80px;
    right: 60px;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.fixed-save-button.visible {
    opacity: 1;
    visibility: visible;
}

.fixed-save-button button {
    padding: 12px 24px;
    font-size: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>
<script src="<?php echo ASSETS_PATH; ?>/js/admin-upload.js"></script>
<script>
// Закрепленная кнопка сохранения
document.addEventListener('DOMContentLoaded', function() {
    const fixedButton = document.getElementById('fixed-save-button');
    const header = document.querySelector('.admin-page-header');
    
    if (fixedButton && header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                fixedButton.classList.add('visible');
            } else {
                fixedButton.classList.remove('visible');
            }
        });
    }
});
</script>
<script>
// Управление множественными документами
let documentIndex = <?php echo count($existingDocuments); ?>;
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('documents-container');
    const addBtn = document.getElementById('add-document');
    
    if (!container || !addBtn) return;
    
    addBtn.addEventListener('click', function() {
        const newDoc = document.createElement('div');
        newDoc.className = 'document-item';
        newDoc.setAttribute('data-index', documentIndex);
        newDoc.innerHTML = `
            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label>Название документа</label>
                    <input type="text" name="documents[${documentIndex}][name]" placeholder="Название документа">
                </div>
                <div class="form-group" style="flex: 0 0 auto; align-self: flex-end;">
                    <button type="button" class="btn-small btn-danger remove-document" style="margin-top: 1.5rem;">Удалить</button>
                </div>
            </div>
            <div class="documentation-upload">
                <div class="upload-area documentation-upload-area" data-index="${documentIndex}">
                    <div class="upload-placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p>Перетащите файл или нажмите для выбора</p>
                        <small>PDF, DOC, DOCX, JPG, PNG до 10MB</small>
                    </div>
                    <input type="hidden" name="documents[${documentIndex}][file]" value="" class="documentation-file-input">
                    <input type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="documentation-file" style="display:none;">
                </div>
            </div>
        `;
        container.appendChild(newDoc);
        documentIndex++;
        initDocumentUpload(newDoc);
    });
    
    // Удаление документа
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-document')) {
            e.target.closest('.document-item').remove();
        }
    });
    
    // Инициализация загрузки для существующих документов
    container.querySelectorAll('.document-item').forEach(function(item) {
        initDocumentUpload(item);
    });
    
    function initDocumentUpload(item) {
        const uploadArea = item.querySelector('.documentation-upload-area');
        const fileInput = item.querySelector('.documentation-file');
        const fileInputHidden = item.querySelector('.documentation-file-input');
        
        if (!uploadArea || !fileInput) return;
        
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#667eea';
        });
        
        uploadArea.addEventListener('dragleave', function() {
            uploadArea.style.borderColor = '';
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '';
            if (e.dataTransfer.files.length > 0) {
                handleFileUpload(e.dataTransfer.files[0], fileInput, fileInputHidden, uploadArea);
            }
        });
        
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleFileUpload(this.files[0], fileInput, fileInputHidden, uploadArea);
            }
        });
        
        // Удаление файла
        const removeBtn = item.querySelector('.documentation-remove');
        if (removeBtn) {
            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                fileInputHidden.value = '';
                uploadArea.innerHTML = `
                    <div class="upload-placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p>Перетащите файл или нажмите для выбора</p>
                        <small>PDF, DOC, DOCX, JPG, PNG до 10MB</small>
                    </div>
                    <input type="hidden" name="${fileInputHidden.name}" value="" class="documentation-file-input">
                    <input type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="documentation-file" style="display:none;">
                `;
                initDocumentUpload(item);
            });
        }
    }
    
    function handleFileUpload(file, fileInput, fileInputHidden, uploadArea) {
        // Показываем индикатор загрузки
        uploadArea.style.opacity = '0.6';
        uploadArea.style.pointerEvents = 'none';
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', 'documentation');
        
        const uploadUrl = '<?php echo BASE_URL; ?>admin/upload-documentation.php';
        console.log('Uploading to:', uploadUrl);
        
        fetch(uploadUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Upload response:', data);
            uploadArea.style.opacity = '1';
            uploadArea.style.pointerEvents = 'auto';
            
            if (data.success && data.file) {
                fileInputHidden.value = data.file.name;
                uploadArea.innerHTML = `
                    <div class="documentation-preview">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <div class="documentation-info">
                            <p class="documentation-name">${data.file.name}</p>
                            <button type="button" class="documentation-remove">Удалить</button>
                        </div>
                    </div>
                    <input type="hidden" name="${fileInputHidden.name}" value="${data.file.name}" class="documentation-file-input">
                    <input type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="documentation-file" style="display:none;">
                `;
                const item = uploadArea.closest('.document-item');
                initDocumentUpload(item);
            } else {
                const errorMsg = data.error || 'Неизвестная ошибка';
                console.error('Upload error:', data);
                alert('Ошибка загрузки файла: ' + errorMsg);
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            uploadArea.style.opacity = '1';
            uploadArea.style.pointerEvents = 'auto';
            alert('Ошибка загрузки файла: ' + error.message);
        });
    }
});

// Управление списками (Из чего состоит, Форма выпуска, Пищевая ценность)
document.addEventListener('DOMContentLoaded', function() {
    // Добавление пункта в список
    document.querySelectorAll('.add-list-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const container = document.getElementById(this.getAttribute('data-target'));
            const name = this.getAttribute('data-name');
            const index = container.children.length;
            
            const item = document.createElement('div');
            item.className = 'list-item';
            item.setAttribute('data-index', index);
            item.innerHTML = `
                <input type="text" name="${name}" placeholder="Пункт списка">
                <button type="button" class="btn-small btn-danger remove-item">Удалить</button>
            `;
            container.appendChild(item);
        });
    });
    
    // Удаление пункта из списка
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            e.target.closest('.list-item').remove();
        }
    });
});
</script>
<style>
.list-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.75rem;
    align-items: center;
}

.list-item input {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
}
</style>

<?php
include '../includes/admin-footer.php';
?>
