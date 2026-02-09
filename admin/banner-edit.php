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
    $bannerType = sanitize($_POST['type'] ?? 'detailed');
    $title = sanitize($_POST['title'] ?? '');
    $subtitle = sanitize($_POST['subtitle'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $link = sanitize($_POST['link'] ?? '');
    $page = sanitize($_POST['page'] ?? 'home');
    // sort_order is managed in banner-order.php, not here
    $status = sanitize($_POST['status'] ?? 'active');
    $image = sanitize($_POST['image'] ?? '');
    
    // Debug: Log all POST data for simple banners
    if ($bannerType === 'simple') {
        error_log("=== SIMPLE BANNER SUBMISSION ===");
        error_log("POST data: " . print_r($_POST, true));
        error_log("Image value: " . $image);
        error_log("Title value: " . $title);
        error_log("Link value: " . $link);
        error_log("Banner ID: " . $bannerId);
    }
    
    // Gradient settings (only for detailed type)
    $gradientColor1 = sanitize($_POST['gradient_color1'] ?? '#667eea');
    $gradientColor2 = sanitize($_POST['gradient_color2'] ?? '#764ba2');
    $gradientAngle = (int)($_POST['gradient_angle'] ?? 135);
    
    try {
        // Check if columns exist and create them if needed
        $columns = $db->query("SHOW COLUMNS FROM banners")->fetchAll(PDO::FETCH_COLUMN);
        
        // Create missing columns
        if (!in_array('type', $columns)) {
            $db->exec("ALTER TABLE banners ADD COLUMN type VARCHAR(20) DEFAULT 'detailed'");
        }
        if (!in_array('page', $columns)) {
            $db->exec("ALTER TABLE banners ADD COLUMN page VARCHAR(50) DEFAULT 'home'");
        }
        if (!in_array('description', $columns)) {
            $db->exec("ALTER TABLE banners ADD COLUMN description TEXT");
        }
        if (!in_array('subtitle', $columns)) {
            $db->exec("ALTER TABLE banners ADD COLUMN subtitle VARCHAR(255) DEFAULT NULL");
        }
        if (!in_array('gradient_color1', $columns)) {
            $db->exec("ALTER TABLE banners ADD COLUMN gradient_color1 VARCHAR(7) DEFAULT '#667eea'");
        }
        if (!in_array('gradient_color2', $columns)) {
            $db->exec("ALTER TABLE banners ADD COLUMN gradient_color2 VARCHAR(7) DEFAULT '#764ba2'");
        }
        if (!in_array('gradient_angle', $columns)) {
            $db->exec("ALTER TABLE banners ADD COLUMN gradient_angle INT DEFAULT 135");
        }
        
        // Refresh columns list
        $columns = $db->query("SHOW COLUMNS FROM banners")->fetchAll(PDO::FETCH_COLUMN);
        $hasSubtitle = in_array('subtitle', $columns);
        $hasDescription = in_array('description', $columns);
        $hasGradient = in_array('gradient_color1', $columns);
        
        // Build UPDATE or INSERT query dynamically
        if ($bannerId > 0) {
            // UPDATE
            $updateFields = ['type = :type', 'link = :link', 'page = :page', 'status = :status', 'image = :image'];
            $params = [
                ':type' => $bannerType,
                ':link' => $link,
                ':page' => $page,
                ':status' => $status,
                ':image' => $image,
                ':id' => $bannerId
            ];
            
            // For detailed type, include title, subtitle, description, gradient
            if ($bannerType === 'detailed') {
                $updateFields[] = 'title = :title';
                $params[':title'] = $title;
            
                if ($hasDescription) {
                    $updateFields[] = 'description = :description';
                    $params[':description'] = $description;
                }
                if ($hasSubtitle) {
                    $updateFields[] = 'subtitle = :subtitle';
                    $params[':subtitle'] = $subtitle;
                }
                if ($hasGradient) {
                    $updateFields[] = 'gradient_color1 = :gradient_color1';
                    $updateFields[] = 'gradient_color2 = :gradient_color2';
                    $updateFields[] = 'gradient_angle = :gradient_angle';
                    $params[':gradient_color1'] = $gradientColor1;
                    $params[':gradient_color2'] = $gradientColor2;
                    $params[':gradient_angle'] = $gradientAngle;
                }
            } else {
                // For simple type, include title (name) but clear other unused fields
                $updateFields[] = 'title = :title';
                $params[':title'] = $title; // Use title as name for simple banner
                if ($hasDescription) {
                    $updateFields[] = 'description = :description';
                    $params[':description'] = '';
                }
                if ($hasSubtitle) {
                    $updateFields[] = 'subtitle = :subtitle';
                    $params[':subtitle'] = '';
                }
            }
            
            $sql = "UPDATE banners SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            // Debug: Log what was saved
            if ($bannerType === 'simple') {
                error_log("UPDATE executed. SQL: " . $sql);
                error_log("UPDATE params: " . print_r($params, true));
                // Verify what was saved
                $verifyStmt = $db->prepare("SELECT * FROM banners WHERE id = :id");
                $verifyStmt->execute([':id' => $bannerId]);
                $saved = $verifyStmt->fetch();
                error_log("Saved banner data: " . print_r($saved, true));
            }
        } else {
            // INSERT
            // Get max sort_order and add 1 for new banner
            $maxOrderStmt = $db->query("SELECT COALESCE(MAX(sort_order), 0) as max_order FROM banners");
            $maxOrder = $maxOrderStmt->fetch()['max_order'] ?? 0;
            $newSortOrder = $maxOrder + 1;
            
            $insertFields = ['type', 'link', 'page', 'sort_order', 'status', 'image'];
            $insertValues = [':type', ':link', ':page', ':sort_order', ':status', ':image'];
            $params = [
                ':type' => $bannerType,
                ':link' => $link,
                ':page' => $page,
                ':sort_order' => $newSortOrder,
                ':status' => $status,
                ':image' => $image
            ];
            
            // For detailed type, include title, subtitle, description, gradient
            if ($bannerType === 'detailed') {
                $insertFields[] = 'title';
                $insertValues[] = ':title';
                $params[':title'] = $title;
                
                if ($hasDescription) {
                    $insertFields[] = 'description';
                    $insertValues[] = ':description';
                    $params[':description'] = $description;
                }
                if ($hasSubtitle) {
                    $insertFields[] = 'subtitle';
                    $insertValues[] = ':subtitle';
                    $params[':subtitle'] = $subtitle;
                }
                if ($hasGradient) {
                    $insertFields[] = 'gradient_color1';
                    $insertFields[] = 'gradient_color2';
                    $insertFields[] = 'gradient_angle';
                    $insertValues[] = ':gradient_color1';
                    $insertValues[] = ':gradient_color2';
                    $insertValues[] = ':gradient_angle';
                    $params[':gradient_color1'] = $gradientColor1;
                    $params[':gradient_color2'] = $gradientColor2;
                    $params[':gradient_angle'] = $gradientAngle;
                }
            } else {
                // For simple type, include title (name) but set other fields empty
                $insertFields[] = 'title';
                $insertValues[] = ':title';
                $params[':title'] = $title; // Use title as name for simple banner
                if ($hasDescription) {
                    $insertFields[] = 'description';
                    $insertValues[] = ':description';
                    $params[':description'] = '';
                }
                if ($hasSubtitle) {
                    $insertFields[] = 'subtitle';
                    $insertValues[] = ':subtitle';
                    $params[':subtitle'] = '';
                }
            }
            
            $sql = "INSERT INTO banners (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $insertValues) . ")";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $bannerId = $db->lastInsertId();
            
            // Debug: Log what was saved
            if ($bannerType === 'simple') {
                error_log("INSERT executed. SQL: " . $sql);
                error_log("INSERT params: " . print_r($params, true));
                // Verify what was saved
                $verifyStmt = $db->prepare("SELECT * FROM banners WHERE id = :id");
                $verifyStmt->execute([':id' => $bannerId]);
                $saved = $verifyStmt->fetch();
                error_log("Saved banner data: " . print_r($saved, true));
            }
        }
        
        redirect('banners.php');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Default values
$bannerType = $banner['type'] ?? 'detailed';
$title = $banner['title'] ?? '';
$subtitle = $banner['subtitle'] ?? '';
$description = $banner['description'] ?? '';
$link = $banner['link'] ?? '';
$page = isset($_GET['page']) ? sanitize($_GET['page']) : ($banner['page'] ?? 'home');
$image = $banner['image'] ?? '';

$gradientColor1 = $banner['gradient_color1'] ?? '#667eea';
$gradientColor2 = $banner['gradient_color2'] ?? '#764ba2';
$gradientAngle = $banner['gradient_angle'] ?? 135;

$pageTitle = $bannerId > 0 ? 'Редактировать баннер' : 'Создать баннер';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <h1><?php echo $bannerId > 0 ? 'Редактировать баннер' : 'Создать баннер'; ?></h1>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" class="admin-form" id="banner-form">
        <div class="form-section">
            <h2>Основная информация</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Тип баннера *</label>
                    <select name="type" id="banner-type" onchange="toggleBannerType()">
                        <option value="detailed" <?php echo $bannerType === 'detailed' ? 'selected' : ''; ?>>Детальный (с текстом и градиентом)</option>
                        <option value="simple" <?php echo $bannerType === 'simple' ? 'selected' : ''; ?>>Простой (только изображение)</option>
                    </select>
                </div>
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
                    <label>Статус</label>
                    <select name="status">
                        <option value="active" <?php echo ($banner['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Активен</option>
                        <option value="inactive" <?php echo ($banner['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Неактивен</option>
                    </select>
                </div>
            </div>
            
            <div id="simple-banner-fields" style="<?php echo $bannerType === 'simple' ? '' : 'display: none;'; ?>">
                <!-- Hidden input for image - must be in form, not in upload area -->
                <input type="hidden" name="image" value="<?php echo htmlspecialchars($image); ?>" id="banner-image-input-simple">
                
                <div class="form-group">
                    <label>Название баннера</label>
                    <input type="text" name="title" id="simple-banner-title" value="<?php echo htmlspecialchars($title); ?>" placeholder="Введите название баннера">
                </div>
                
                <div class="form-group">
                    <label>Изображение баннера *</label>
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.5rem;"><strong>Рекомендуемый размер:</strong> 1920×400 пикселей (ширина × высота)</p>
                    <div class="banner-image-upload">
                        <div class="upload-area" id="banner-image-upload-simple">
                            <?php if ($image): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/banners/<?php echo htmlspecialchars($image); ?>" alt="Banner image" class="upload-preview">
                            <?php else: ?>
                                <div class="upload-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <p>Перетащите изображение или нажмите для выбора</p>
                                    <small>JPG, PNG, GIF до 5MB. Рекомендуемый размер: 1920×400px</small>
                                </div>
                            <?php endif; ?>
                            <input type="file" accept="image/*" id="banner-image-file-simple" style="display:none;">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Ссылка (при клике на баннер)</label>
                    <input type="text" name="link" value="<?php echo htmlspecialchars($link); ?>" placeholder="catalog.php или http://...">
                </div>
            </div>
            
            <div id="detailed-banner-fields" style="<?php echo $bannerType === 'detailed' ? '' : 'display: none;'; ?>">
            
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="title" id="banner-title" value="<?php echo htmlspecialchars($title); ?>" placeholder="Введите заголовок баннера">
            </div>
            
            <div class="form-group">
                <label>Подзаголовок (отображается под заголовком)</label>
                <input type="text" name="subtitle" id="banner-subtitle" value="<?php echo htmlspecialchars($subtitle); ?>" placeholder="Введите подзаголовок баннера">
            </div>
            
            <div class="form-group">
                <label>Описание</label>
                <textarea name="description" id="banner-description" rows="4" placeholder="Введите описание баннера"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Ссылка (при клике на баннер)</label>
                <input type="text" name="link" value="<?php echo htmlspecialchars($link); ?>" placeholder="catalog.php или http://...">
            </div>
            </div>
        </div>
        
        <div class="form-section" id="detailed-visual-section" style="<?php echo $bannerType === 'detailed' ? '' : 'display: none;'; ?>">
            <h2>Визуальное оформление</h2>
            
            <div class="form-group">
                <label>Изображение (справа на баннере)</label>
                <div class="banner-image-upload">
                    <div class="upload-area" id="banner-image-upload">
                        <?php if ($image): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/banners/<?php echo htmlspecialchars($image); ?>" alt="Banner image" class="upload-preview">
                            <input type="hidden" name="image" value="<?php echo htmlspecialchars($image); ?>" id="banner-image-input">
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
                            <input type="hidden" name="image" value="" id="banner-image-input">
                        <?php endif; ?>
                        <input type="file" accept="image/*" id="banner-image-file" style="display:none;">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Фоновый градиент</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Цвет 1</label>
                        <input type="color" name="gradient_color1" id="gradient-color1" value="<?php echo htmlspecialchars($gradientColor1); ?>">
                    </div>
                    <div class="form-group">
                        <label>Цвет 2</label>
                        <input type="color" name="gradient_color2" id="gradient-color2" value="<?php echo htmlspecialchars($gradientColor2); ?>">
                    </div>
                    <div class="form-group">
                        <label>Угол (градусы)</label>
                        <input type="number" name="gradient_angle" id="gradient-angle" value="<?php echo $gradientAngle; ?>" min="0" max="360">
                    </div>
                </div>
                
                <div class="gradient-preview" id="gradient-preview" style="width: 100%; height: 80px; border-radius: 8px; margin-top: 1rem; background: linear-gradient(<?php echo $gradientAngle; ?>deg, <?php echo $gradientColor1; ?> 0%, <?php echo $gradientColor2; ?> 100%); border: 2px solid #e2e8f0;"></div>
            </div>
            
            <div class="form-section" id="detailed-preview-section" style="<?php echo $bannerType === 'detailed' ? '' : 'display: none;'; ?>">
                <h2>Предпросмотр баннера</h2>
                <div class="banner-preview" id="banner-preview" style="width: 100%; max-width: 1290px; height: 400px; border-radius: 30px; overflow: hidden; margin-top: 1rem; position: relative; box-shadow: 0 10px 40px rgba(0,0,0,0.1); background: linear-gradient(<?php echo $gradientAngle; ?>deg, <?php echo $gradientColor1; ?> 0%, <?php echo $gradientColor2; ?> 100%);">
                    <div class="banner-preview-content" style="display: flex; align-items: center; gap: 4rem; height: 100%; padding: 4rem 5rem; position: relative; z-index: 2;">
                        <div class="banner-preview-left" style="flex: 1; max-width: 620px; display: flex; flex-direction: column; justify-content: center; color: white; gap: 1.5rem;">
                            <h1 class="banner-preview-title" id="preview-title" style="font-family: 'Playfair Display', serif; font-size: 3rem; font-weight: 700; margin: 0; line-height: 1.15; color: white; text-shadow: 0 2px 20px rgba(0,0,0,0.2);"><?php echo htmlspecialchars($title ?: 'Заголовок баннера'); ?></h1>
                            <p class="banner-preview-subtitle" id="preview-subtitle" style="font-size: 1.5rem; line-height: 1.6; margin: 0; color: rgba(255,255,255,0.9); text-shadow: 0 1px 10px rgba(0,0,0,0.15); font-weight: 500; <?php echo empty($subtitle) ? 'display: none;' : ''; ?>"><?php echo htmlspecialchars($subtitle); ?></p>
                            <p class="banner-preview-description" id="preview-description" style="font-size: 1.1rem; line-height: 1.8; margin: 0; color: rgba(255,255,255,0.95); text-shadow: 0 1px 10px rgba(0,0,0,0.15); <?php echo empty($description) ? 'display: none;' : ''; ?>"><?php echo htmlspecialchars($description); ?></p>
                        </div>
                        <div class="banner-preview-right" style="flex: 0 0 auto; display: <?php echo $image ? 'flex' : 'none'; ?>; align-items: center; justify-content: center; max-width: 500px;">
                            <?php if ($image): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/banners/<?php echo htmlspecialchars($image); ?>" alt="Preview" id="preview-image" style="max-width: 100%; max-height: 350px; object-fit: contain; filter: drop-shadow(0 15px 40px rgba(0,0,0,0.25));">
                            <?php else: ?>
                            <img src="" alt="Preview" id="preview-image" style="max-width: 100%; max-height: 350px; object-fit: contain; filter: drop-shadow(0 15px 40px rgba(0,0,0,0.25)); display: none;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary" id="save-banner-btn">Сохранить баннер</button>
            <a href="banners.php" class="btn-secondary">Отмена</a>
        </div>
    </form>
    
    <script>
    // Validate form before submission
    document.getElementById('banner-form').addEventListener('submit', function(e) {
        const bannerType = document.getElementById('banner-type').value;
        if (bannerType === 'simple') {
            const imageInput = document.getElementById('banner-image-input-simple');
            if (!imageInput || !imageInput.value) {
                e.preventDefault();
                alert('Пожалуйста, загрузите изображение баннера перед сохранением.');
                return false;
            }
            // Log what will be submitted
            console.log('=== FORM SUBMISSION DEBUG ===');
            console.log('Banner type:', bannerType);
            console.log('Image input value:', imageInput.value);
            console.log('Image input in DOM:', imageInput);
            console.log('Image input name:', imageInput.name);
            
            const formData = new FormData(this);
            console.log('FormData image value:', formData.get('image'));
            console.log('FormData title value:', formData.get('title'));
            console.log('FormData link value:', formData.get('link'));
            
            // Check all inputs with name="image"
            const allImageInputs = this.querySelectorAll('input[name="image"]');
            console.log('All image inputs found:', allImageInputs.length);
            allImageInputs.forEach((input, index) => {
                console.log(`Image input ${index}:`, {
                    id: input.id,
                    value: input.value,
                    name: input.name,
                    inForm: this.contains(input)
                });
            });
        }
    });
    </script>
</div>

<link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/admin-upload.css">
<script src="<?php echo ASSETS_PATH; ?>/js/admin-banner-upload.js"></script>
<script src="<?php echo ASSETS_PATH; ?>/js/admin-banner-preview.js"></script>
<script>
function toggleBannerType() {
    const bannerType = document.getElementById('banner-type').value;
    const simpleFields = document.getElementById('simple-banner-fields');
    const detailedFields = document.getElementById('detailed-banner-fields');
    const detailedVisualSection = document.getElementById('detailed-visual-section');
    const detailedPreviewSection = document.getElementById('detailed-preview-section');
    
    // Disable/enable image inputs to prevent conflicts
    const simpleImageInput = document.getElementById('banner-image-input-simple');
    const detailedImageInput = document.getElementById('banner-image-input');
    
    // Disable/enable title inputs to prevent conflicts
    const simpleTitleInput = document.getElementById('simple-banner-title');
    const detailedTitleInput = document.getElementById('banner-title');
    
    if (bannerType === 'simple') {
        // Показываем только поля для простого баннера
        if (simpleFields) simpleFields.style.display = 'block';
        if (detailedFields) detailedFields.style.display = 'none';
        if (detailedVisualSection) detailedVisualSection.style.display = 'none';
        if (detailedPreviewSection) detailedPreviewSection.style.display = 'none';
        
        // Disable detailed inputs, enable simple ones
        if (simpleImageInput) {
            simpleImageInput.disabled = false;
            simpleImageInput.name = 'image';
        }
        if (detailedImageInput) {
            detailedImageInput.disabled = true;
            detailedImageInput.name = 'image_detailed_disabled';
        }
        if (simpleTitleInput) {
            simpleTitleInput.disabled = false;
            simpleTitleInput.name = 'title';
        }
        if (detailedTitleInput) {
            detailedTitleInput.disabled = true;
            detailedTitleInput.name = 'title_detailed_disabled';
        }
    } else {
        // Показываем поля для детального баннера
        if (simpleFields) simpleFields.style.display = 'none';
        if (detailedFields) detailedFields.style.display = 'block';
        if (detailedVisualSection) detailedVisualSection.style.display = 'block';
        if (detailedPreviewSection) detailedPreviewSection.style.display = 'block';
        
        // Disable simple inputs, enable detailed ones
        if (simpleImageInput) {
            simpleImageInput.disabled = true;
            simpleImageInput.name = 'image_simple_disabled';
        }
        if (detailedImageInput) {
            detailedImageInput.disabled = false;
            detailedImageInput.name = 'image';
        }
        if (simpleTitleInput) {
            simpleTitleInput.disabled = true;
            simpleTitleInput.name = 'title_simple_disabled';
        }
        if (detailedTitleInput) {
            detailedTitleInput.disabled = false;
            detailedTitleInput.name = 'title';
        }
    }
}

// Initialize upload for simple banner type
document.addEventListener('DOMContentLoaded', function() {
    // Initialize banner type on page load
    toggleBannerType();
    
    const simpleUploadArea = document.getElementById('banner-image-upload-simple');
    const simpleFileInput = document.getElementById('banner-image-file-simple');
    const simpleImageInput = document.getElementById('banner-image-input-simple');
    
    if (simpleUploadArea && simpleFileInput) {
        // Click to upload
        simpleUploadArea.addEventListener('click', function() {
            simpleFileInput.click();
        });
        
        // Drag and drop
        simpleUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            simpleUploadArea.style.borderColor = '#667eea';
        });
        
        simpleUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            simpleUploadArea.style.borderColor = '#e2e8f0';
        });
        
        simpleUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            simpleUploadArea.style.borderColor = '#e2e8f0';
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                uploadSimpleBannerImage(files[0]);
            }
        });
        
        // File input change
        simpleFileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                uploadSimpleBannerImage(e.target.files[0]);
            }
        });
    }
});

function uploadSimpleBannerImage(file) {
    const formData = new FormData();
    formData.append('image', file);
    
    const uploadArea = document.getElementById('banner-image-upload-simple');
    
    if (!uploadArea) {
        console.error('Upload area not found');
        return;
    }
    
    // Get or create the hidden input in the form
    const form = document.getElementById('banner-form');
    if (!form) {
        console.error('Form not found');
        return;
    }
    
    let imageInput = document.getElementById('banner-image-input-simple');
    if (!imageInput) {
        // Create the hidden input if it doesn't exist
        imageInput = document.createElement('input');
        imageInput.type = 'hidden';
        imageInput.name = 'image';
        imageInput.id = 'banner-image-input-simple';
        imageInput.value = '';
        form.appendChild(imageInput);
    }
    
    uploadArea.style.opacity = '0.6';
    uploadArea.style.pointerEvents = 'none';
    
    fetch('upload-banner.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        uploadArea.style.opacity = '1';
        uploadArea.style.pointerEvents = 'auto';
        
        if (data.success && data.filename) {
            // Update the hidden input value - it should already exist in the form
            let hiddenInput = document.getElementById('banner-image-input-simple');
            if (!hiddenInput) {
                // If it doesn't exist, create it in the form
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'image';
                hiddenInput.id = 'banner-image-input-simple';
                // Insert it at the beginning of simple-banner-fields
                const simpleFields = document.getElementById('simple-banner-fields');
                if (simpleFields) {
                    simpleFields.insertBefore(hiddenInput, simpleFields.firstChild);
                } else {
                    form.appendChild(hiddenInput);
                }
            }
            hiddenInput.value = data.filename;
            
            // Update the preview
            const existingPreview = uploadArea.querySelector('.upload-preview');
            const placeholder = uploadArea.querySelector('.upload-placeholder');
            
            if (existingPreview) {
                existingPreview.src = '<?php echo BASE_URL; ?>uploads/banners/' + data.filename;
            } else {
                // Hide placeholder and create preview
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
                const img = document.createElement('img');
                img.src = '<?php echo BASE_URL; ?>uploads/banners/' + data.filename;
                img.alt = 'Banner image';
                img.className = 'upload-preview';
                uploadArea.insertBefore(img, uploadArea.firstChild);
            }
            
            // Verify the value is set
            console.log('Image uploaded successfully:', data.filename);
            console.log('Hidden input value:', hiddenInput.value);
            console.log('Hidden input in form:', form.querySelector('input[name="image"]#banner-image-input-simple') !== null);
            
            // Double check - update all image inputs with this name
            const allImageInputs = form.querySelectorAll('input[name="image"]');
            allImageInputs.forEach(input => {
                if (input.id === 'banner-image-input-simple') {
                    input.value = data.filename;
                }
            });
        } else {
            const errorMsg = data.error || 'Ошибка при загрузке изображения';
            alert(errorMsg);
            console.error('Upload error:', data);
        }
    })
    .catch(error => {
        uploadArea.style.opacity = '1';
        uploadArea.style.pointerEvents = 'auto';
        console.error('Error:', error);
        alert('Ошибка при загрузке изображения: ' + error.message);
    });
}
</script>

<?php
include '../includes/admin-footer.php';
?>
