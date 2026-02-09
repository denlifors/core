<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();
$bannerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$banner = null;

if ($bannerId > 0) {
    $stmt = $db->prepare("SELECT * FROM banners WHERE id = :id");
    $stmt->execute([':id' => $bannerId]);
    $banner = $stmt->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $buttonText = sanitize($_POST['button_text'] ?? '');
    $link = sanitize($_POST['link'] ?? '');
    $page = sanitize($_POST['page'] ?? 'home');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'active');
    $image = sanitize($_POST['image'] ?? '');
    
    // Position data
    $titlePosition = sanitize($_POST['title_position'] ?? 'left');
    $titleX = (int)($_POST['title_x'] ?? 50);
    $titleY = (int)($_POST['title_y'] ?? 100);
    $descX = (int)($_POST['desc_x'] ?? 50);
    $descY = (int)($_POST['desc_y'] ?? 200);
    $buttonX = (int)($_POST['button_x'] ?? 50);
    $buttonY = (int)($_POST['button_y'] ?? 300);
    $imagePosition = sanitize($_POST['image_position'] ?? 'right');
    $imageX = (int)($_POST['image_x'] ?? 60);
    $imageY = (int)($_POST['image_y'] ?? 50);
    
    try {
        // Check if new columns exist
        $columns = $db->query("SHOW COLUMNS FROM banners")->fetchAll(PDO::FETCH_COLUMN);
        $hasNewColumns = in_array('page', $columns);
        
        if ($bannerId > 0) {
            if ($hasNewColumns) {
                $stmt = $db->prepare("UPDATE banners SET title = :title, description = :description, button_text = :button_text, link = :link, page = :page, sort_order = :sort_order, status = :status, image = :image, title_position = :title_position, title_x = :title_x, title_y = :title_y, desc_x = :desc_x, desc_y = :desc_y, button_x = :button_x, button_y = :button_y, image_position = :image_position, image_x = :image_x, image_y = :image_y WHERE id = :id");
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':button_text' => $buttonText,
                    ':link' => $link,
                    ':page' => $page,
                    ':sort_order' => $sortOrder,
                    ':status' => $status,
                    ':image' => $image,
                    ':title_position' => $titlePosition,
                    ':title_x' => $titleX,
                    ':title_y' => $titleY,
                    ':desc_x' => $descX,
                    ':desc_y' => $descY,
                    ':button_x' => $buttonX,
                    ':button_y' => $buttonY,
                    ':image_position' => $imagePosition,
                    ':image_x' => $imageX,
                    ':image_y' => $imageY,
                    ':id' => $bannerId
                ]);
            } else {
                $stmt = $db->prepare("UPDATE banners SET title = :title, link = :link, position = :position, sort_order = :sort_order, status = :status, image = :image WHERE id = :id");
                $stmt->execute([
                    ':title' => $title,
                    ':link' => $link,
                    ':position' => 'hero',
                    ':sort_order' => $sortOrder,
                    ':status' => $status,
                    ':image' => $image,
                    ':id' => $bannerId
                ]);
            }
        } else {
            if ($hasNewColumns) {
                $stmt = $db->prepare("INSERT INTO banners (title, description, button_text, link, page, sort_order, status, image, title_position, title_x, title_y, desc_x, desc_y, button_x, button_y, image_position, image_x, image_y) VALUES (:title, :description, :button_text, :link, :page, :sort_order, :status, :image, :title_position, :title_x, :title_y, :desc_x, :desc_y, :button_x, :button_y, :image_position, :image_x, :image_y)");
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':button_text' => $buttonText,
                    ':link' => $link,
                    ':page' => $page,
                    ':sort_order' => $sortOrder,
                    ':status' => $status,
                    ':image' => $image,
                    ':title_position' => $titlePosition,
                    ':title_x' => $titleX,
                    ':title_y' => $titleY,
                    ':desc_x' => $descX,
                    ':desc_y' => $descY,
                    ':button_x' => $buttonX,
                    ':button_y' => $buttonY,
                    ':image_position' => $imagePosition,
                    ':image_x' => $imageX,
                    ':image_y' => $imageY
                ]);
            } else {
                $stmt = $db->prepare("INSERT INTO banners (title, link, position, sort_order, status, image) VALUES (:title, :link, :position, :sort_order, :status, :image)");
                $stmt->execute([
                    ':title' => $title,
                    ':link' => $link,
                    ':position' => 'hero',
                    ':sort_order' => $sortOrder,
                    ':status' => $status,
                    ':image' => $image
                ]);
            }
            $bannerId = $db->lastInsertId();
        }
        
        redirect('banners.php');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Default values
$title = $banner['title'] ?? '';
$description = $banner['description'] ?? '';
$buttonText = $banner['button_text'] ?? 'Узнать больше';
$link = $banner['link'] ?? '';
$page = isset($_GET['page']) ? sanitize($_GET['page']) : ($banner['page'] ?? 'home');
$image = $banner['image'] ?? '';

$titlePosition = $banner['title_position'] ?? 'left';
$titleX = $banner['title_x'] ?? 50;
$titleY = $banner['title_y'] ?? 100;
$descX = $banner['desc_x'] ?? 50;
$descY = $banner['desc_y'] ?? 200;
$buttonX = $banner['button_x'] ?? 50;
$buttonY = $banner['button_y'] ?? 300;
$imagePosition = $banner['image_position'] ?? 'right';
$imageX = $banner['image_x'] ?? 60;
$imageY = $banner['image_y'] ?? 50;

$pageTitle = $bannerId > 0 ? 'Редактировать баннер' : 'Создать баннер';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <h1><?php echo $bannerId > 0 ? 'Редактировать баннер' : 'Создать баннер'; ?></h1>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" class="admin-form" id="banner-visual-form">
        <div class="form-section">
            <h2>Настройки баннера</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Страница</label>
                    <select name="page" id="banner-page">
                        <option value="home" <?php echo $page === 'home' ? 'selected' : ''; ?>>Главная</option>
                        <option value="partnership" <?php echo $page === 'partnership' ? 'selected' : ''; ?>>Партнёры</option>
                        <option value="catalog" <?php echo $page === 'catalog' ? 'selected' : ''; ?>>Каталог</option>
                        <option value="all" <?php echo $page === 'all' ? 'selected' : ''; ?>>Все страницы</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Порядок сортировки</label>
                    <input type="number" name="sort_order" value="<?php echo $banner['sort_order'] ?? 0; ?>">
                </div>
                <div class="form-group">
                    <label>Статус</label>
                    <select name="status">
                        <option value="active" <?php echo ($banner['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Активен</option>
                        <option value="inactive" <?php echo ($banner['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Неактивен</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Ссылка</label>
                <input type="text" name="link" value="<?php echo htmlspecialchars($link); ?>" placeholder="catalog.php или http://...">
            </div>
        </div>
        
        <div class="form-section">
            <h2>Визуальный редактор</h2>
            
            <div class="banner-visual-editor">
                <div class="editor-canvas" id="banner-canvas">
                    <?php if ($image): ?>
                        <div class="canvas-background" style="background-image: url('<?php echo BASE_URL; ?>uploads/banners/<?php echo htmlspecialchars($image); ?>');"></div>
                    <?php else: ?>
                        <div class="canvas-background canvas-placeholder">
                            <p>Загрузите фоновое изображение</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="editable-element" id="title-element" style="left: <?php echo $titleX; ?>%; top: <?php echo $titleY; ?>px;">
                        <div class="element-label">Заголовок</div>
                        <div class="element-content" contenteditable="true"><?php echo htmlspecialchars($title); ?></div>
                    </div>
                    
                    <div class="editable-element" id="desc-element" style="left: <?php echo $descX; ?>%; top: <?php echo $descY; ?>px;">
                        <div class="element-label">Описание</div>
                        <div class="element-content" contenteditable="true"><?php echo htmlspecialchars($description); ?></div>
                    </div>
                    
                    <div class="editable-element" id="button-element" style="left: <?php echo $buttonX; ?>%; top: <?php echo $buttonY; ?>px;">
                        <div class="element-label">Кнопка</div>
                        <div class="element-content element-button" contenteditable="true"><?php echo htmlspecialchars($buttonText); ?></div>
                    </div>
                </div>
                
                <div class="editor-controls">
                    <div class="control-group">
                        <label>Загрузить фоновое изображение</label>
                        <div class="upload-area" id="banner-bg-upload">
                            <?php if ($image): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/banners/<?php echo htmlspecialchars($image); ?>" alt="Background" class="upload-preview">
                                <input type="hidden" name="image" value="<?php echo htmlspecialchars($image); ?>" id="banner-image-input">
                            <?php else: ?>
                                <div class="upload-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <p>Перетащите изображение или нажмите</p>
                                </div>
                                <input type="hidden" name="image" value="" id="banner-image-input">
                            <?php endif; ?>
                            <input type="file" accept="image/*" id="banner-bg-file" style="display:none;">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hidden inputs for positions -->
            <input type="hidden" name="title" id="title-input" value="<?php echo htmlspecialchars($title); ?>">
            <input type="hidden" name="description" id="description-input" value="<?php echo htmlspecialchars($description); ?>">
            <input type="hidden" name="button_text" id="button-text-input" value="<?php echo htmlspecialchars($buttonText); ?>">
            <input type="hidden" name="title_position" id="title-position-input" value="<?php echo htmlspecialchars($titlePosition); ?>">
            <input type="hidden" name="title_x" id="title-x-input" value="<?php echo $titleX; ?>">
            <input type="hidden" name="title_y" id="title-y-input" value="<?php echo $titleY; ?>">
            <input type="hidden" name="desc_x" id="desc-x-input" value="<?php echo $descX; ?>">
            <input type="hidden" name="desc_y" id="desc-y-input" value="<?php echo $descY; ?>">
            <input type="hidden" name="button_x" id="button-x-input" value="<?php echo $buttonX; ?>">
            <input type="hidden" name="button_y" id="button-y-input" value="<?php echo $buttonY; ?>">
            <input type="hidden" name="image_position" id="image-position-input" value="<?php echo htmlspecialchars($imagePosition); ?>">
            <input type="hidden" name="image_x" id="image-x-input" value="<?php echo $imageX; ?>">
            <input type="hidden" name="image_y" id="image-y-input" value="<?php echo $imageY; ?>">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">Сохранить баннер</button>
            <a href="banners.php" class="btn-secondary">Отмена</a>
        </div>
    </form>
</div>

<link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/admin-banner-editor.css">
<script src="<?php echo ASSETS_PATH; ?>/js/admin-banner-editor-new.js"></script>
<script src="<?php echo ASSETS_PATH; ?>/js/admin-banner-upload.js"></script>

<?php
include '../includes/admin-footer.php';
?>

