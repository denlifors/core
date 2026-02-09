<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = null;
$error = '';
$success = '';

// Check and create article_blocks table if needed
try {
    $db->query("SELECT 1 FROM article_blocks LIMIT 1");
} catch (PDOException $e) {
    // Table doesn't exist, create it
    $db->exec("CREATE TABLE IF NOT EXISTS article_blocks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        article_id INT NOT NULL,
        template_type VARCHAR(50) NOT NULL,
        sort_order INT DEFAULT 0,
        content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_article (article_id),
        INDEX idx_sort (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Add foreign key if articles table exists
    try {
        $db->exec("ALTER TABLE article_blocks ADD CONSTRAINT fk_article_blocks_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE");
    } catch (PDOException $e) {
        // Foreign key might already exist or table structure issue
    }
}

// Check and add short_description column if needed
try {
    $columns = $db->query("SHOW COLUMNS FROM articles")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('short_description', $columns)) {
        $db->exec("ALTER TABLE articles ADD COLUMN short_description TEXT AFTER excerpt");
    }
} catch (PDOException $e) {
    // Table might not exist
}

if ($articleId > 0) {
    $stmt = $db->prepare("SELECT * FROM articles WHERE id = :id");
    $stmt->execute([':id' => $articleId]);
    $article = $stmt->fetch();
    
    if (!$article) {
        redirect('articles.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $shortDescription = sanitize($_POST['short_description'] ?? '');
    $status = sanitize($_POST['status'] ?? 'draft');
    $action = sanitize($_POST['action'] ?? 'save'); // 'save' or 'publish'
    $coverImage = sanitize($_POST['cover_image'] ?? '');
    
    // Generate slug from title if not provided
    if (empty($slug) && !empty($title)) {
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = $baseSlug;
        $counter = 1;
        while (true) {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM articles WHERE slug = :slug AND id != :id");
            $checkStmt->execute([':slug' => $slug, ':id' => $articleId]);
            if ($checkStmt->fetchColumn() == 0) {
                break;
            }
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }
    
    // If action is 'save', set status to draft
    if ($action === 'save') {
        $status = 'draft';
    } elseif ($action === 'publish') {
        $status = 'published';
    }
    
    try {
        // Check if image column exists, if not add it
        try {
            $columns = $db->query("SHOW COLUMNS FROM articles")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('image', $columns)) {
                $db->exec("ALTER TABLE articles ADD COLUMN image VARCHAR(255) AFTER short_description");
            }
        } catch (PDOException $e) {
            // Column might already exist
        }
        
        // Save article
        if ($articleId > 0) {
            $updateFields = ['title = :title', 'slug = :slug', 'short_description = :short_description', 'status = :status'];
            $params = [
                ':title' => $title,
                ':slug' => $slug,
                ':short_description' => $shortDescription,
                ':status' => $status,
                ':id' => $articleId
            ];
            
            if (!empty($coverImage)) {
                $updateFields[] = 'image = :image';
                $params[':image'] = $coverImage;
            }
            
            $stmt = $db->prepare("UPDATE articles SET " . implode(', ', $updateFields) . " WHERE id = :id");
            $stmt->execute($params);
        } else {
            $insertFields = ['title', 'slug', 'short_description', 'status'];
            $insertValues = [':title', ':slug', ':short_description', ':status'];
            $params = [
                ':title' => $title,
                ':slug' => $slug,
                ':short_description' => $shortDescription,
                ':status' => $status
            ];
            
            if (!empty($coverImage)) {
                $insertFields[] = 'image';
                $insertValues[] = ':image';
                $params[':image'] = $coverImage;
            }
            
            $stmt = $db->prepare("INSERT INTO articles (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $insertValues) . ")");
            $stmt->execute($params);
            $articleId = $db->lastInsertId();
        }
        
        // Save blocks
        if (isset($_POST['blocks']) && is_array($_POST['blocks'])) {
            // Delete existing blocks
            $deleteStmt = $db->prepare("DELETE FROM article_blocks WHERE article_id = :article_id");
            $deleteStmt->execute([':article_id' => $articleId]);
            
            // Insert new blocks
            $insertStmt = $db->prepare("INSERT INTO article_blocks (article_id, template_type, sort_order, content) VALUES (:article_id, :template_type, :sort_order, :content)");
            
            foreach ($_POST['blocks'] as $index => $block) {
                $templateType = sanitize($block['template_type'] ?? '');
                $content = json_encode($block['content'] ?? []);
                $sortOrder = $index;
                
                $insertStmt->execute([
                    ':article_id' => $articleId,
                    ':template_type' => $templateType,
                    ':sort_order' => $sortOrder,
                    ':content' => $content
                ]);
            }
        }
        
        $success = $action === 'save' ? 'Статья сохранена как черновик!' : 'Статья опубликована!';
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

// Get existing blocks
$blocks = [];
if ($articleId > 0) {
    $stmt = $db->prepare("SELECT * FROM article_blocks WHERE article_id = :id ORDER BY sort_order ASC");
    $stmt->execute([':id' => $articleId]);
    $blocks = $stmt->fetchAll();
    
    // Decode JSON content
    foreach ($blocks as &$block) {
        $block['content'] = json_decode($block['content'], true) ?? [];
    }
    unset($block);
}

// Default values
$title = $article['title'] ?? '';
$slug = $article['slug'] ?? '';
$shortDescription = $article['short_description'] ?? '';
$status = $article['status'] ?? 'draft';
$coverImage = $article['image'] ?? '';

$pageTitle = $articleId > 0 ? 'Редактировать статью' : 'Создать статью';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1><?php echo $articleId > 0 ? 'Редактировать статью' : 'Создать статью'; ?></h1>
        <div style="display: flex; gap: 1rem;">
            <button type="submit" form="article-form" name="action" value="save" class="btn-primary">Сохранить</button>
            <?php if ($articleId > 0): ?>
                <button type="submit" form="article-form" name="action" value="publish" class="btn-primary" style="background: #10b981;">Опубликовать</button>
            <?php endif; ?>
            <a href="articles.php" class="btn-secondary">Отмена</a>
        </div>
    </div>
    
    <!-- Fixed save button -->
    <div class="fixed-save-buttons" id="fixed-save-buttons" style="position: fixed; top: 80px; right: 20px; z-index: 1000; display: none; flex-direction: column; gap: 0.5rem; background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <button type="submit" form="article-form" name="action" value="save" class="btn-primary">Сохранить</button>
        <?php if ($articleId > 0): ?>
            <button type="submit" form="article-form" name="action" value="publish" class="btn-primary" style="background: #10b981;">Опубликовать</button>
        <?php endif; ?>
        <a href="articles.php" class="btn-secondary">Отмена</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" class="admin-form" id="article-form">
        <div class="form-section">
            <h2>Основная информация</h2>
            
            <div class="form-group">
                <label>Название статьи *</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" required placeholder="Введите название статьи">
            </div>
            
            <div class="form-group">
                <label>Краткое описание (отображается в списке) *</label>
                <textarea name="short_description" rows="3" required placeholder="Краткое описание статьи"><?php echo htmlspecialchars($shortDescription); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>URL (slug)</label>
                    <input type="text" name="slug" value="<?php echo htmlspecialchars($slug); ?>" placeholder="Автоматически генерируется из названия">
                    <small>Только латинские буквы, цифры и дефисы. Оставьте пустым для автоматической генерации.</small>
                </div>
                <div class="form-group">
                    <label>Статус</label>
                    <select name="status" disabled>
                        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Черновик</option>
                        <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Опубликована</option>
                    </select>
                    <small>Статус управляется кнопками "Сохранить" (черновик) и "Опубликовать"</small>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Обложка статьи</h2>
            <p style="color: #666; margin-bottom: 1rem;">Изображение, которое будет отображаться в списке статей и на странице статьи</p>
            
            <div class="article-cover-upload">
                <div class="upload-area" id="article-cover-upload" style="cursor: pointer;">
                    <?php if ($coverImage): ?>
                        <img src="<?php echo BASE_URL; ?>uploads/articles/<?php echo htmlspecialchars($coverImage); ?>" alt="Cover image" class="upload-preview">
                        <input type="hidden" name="cover_image" value="<?php echo htmlspecialchars($coverImage); ?>" id="article-cover-input">
                    <?php else: ?>
                        <div class="upload-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p>Перетащите изображение или нажмите для выбора</p>
                            <small>JPG, PNG, GIF до 5MB. Рекомендуемый размер: 1200×600px</small>
                        </div>
                        <input type="hidden" name="cover_image" value="" id="article-cover-input">
                    <?php endif; ?>
                    <input type="file" accept="image/*" id="article-cover-file" style="display:none;">
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Блоки статьи</h2>
            <p style="color: #666; margin-bottom: 1rem;">Добавляйте блоки с разными шаблонами для создания структуры статьи</p>
            
            <div id="article-blocks-container">
                <?php 
                // Render existing blocks using PHP include
                foreach ($blocks as $index => $block): 
                    $blockIndex = $index;
                    $blockType = $block['template_type'] ?? '';
                    $blockContent = $block['content'] ?? [];
                    $blockId = $block['id'] ?? 'new-' . $blockIndex;
                ?>
                    <?php include __DIR__ . '/includes/article-block-template.php'; ?>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="btn-secondary" id="add-block-btn" style="margin-top: 1rem;">
                + Добавить блок шаблона
            </button>
        </div>
        
        <div class="form-actions" style="display: none;">
            <!-- Buttons moved to header -->
        </div>
    </form>
</div>

<!-- Template selection modal -->
<div id="template-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 800px; max-height: 90vh; overflow-y: auto; width: 90%;">
        <h2 style="margin-top: 0;">Выберите шаблон блока</h2>
        <div id="template-options" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
            <!-- Templates will be added here by JavaScript -->
        </div>
        <button type="button" class="btn-secondary" onclick="closeTemplateModal()" style="margin-top: 1.5rem;">Отмена</button>
    </div>
</div>

<link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/admin-upload.css">
<link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/admin-article-editor.css">
<script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo ASSETS_PATH; ?>/js/admin-article-editor.js"></script>

<script>
// Fixed save button visibility
document.addEventListener('DOMContentLoaded', function() {
    const fixedButtonContainer = document.getElementById('fixed-save-buttons');
    const header = document.querySelector('.admin-page-header');
    
    if (fixedButtonContainer && header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                fixedButtonContainer.style.display = 'flex';
            } else {
                fixedButtonContainer.style.display = 'none';
            }
        });
    }
    
    // Initialize article editor with existing blocks
    const existingBlocks = <?php echo json_encode($blocks); ?>;
    if (typeof initArticleEditor === 'function') {
        initArticleEditor(existingBlocks);
    }
    
    // Initialize cover image upload
    const coverUploadArea = document.getElementById('article-cover-upload');
    const coverFileInput = document.getElementById('article-cover-file');
    const coverImageInput = document.getElementById('article-cover-input');
    
    if (coverUploadArea && coverFileInput) {
        // Click to upload
        coverUploadArea.addEventListener('click', function(e) {
            // Don't trigger if clicking on the image itself
            if (e.target.tagName !== 'IMG') {
                coverFileInput.click();
            }
        });
        
        // File input change
        coverFileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                uploadCoverImage(e.target.files[0]);
            }
        });
        
        // Drag and drop
        coverUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            coverUploadArea.style.borderColor = '#667eea';
            coverUploadArea.style.backgroundColor = '#f7fafc';
        });
        
        coverUploadArea.addEventListener('dragleave', () => {
            coverUploadArea.style.borderColor = '#e2e8f0';
            coverUploadArea.style.backgroundColor = '';
        });
        
        coverUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            coverUploadArea.style.borderColor = '#e2e8f0';
            coverUploadArea.style.backgroundColor = '';
            if (e.dataTransfer.files.length > 0) {
                uploadCoverImage(e.dataTransfer.files[0]);
            }
        });
    }
    
    function uploadCoverImage(file) {
        const formData = new FormData();
        formData.append('image', file);
        
        coverUploadArea.style.opacity = '0.6';
        coverUploadArea.style.pointerEvents = 'none';
        
        fetch('upload-article.php', {
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
            coverUploadArea.style.opacity = '1';
            coverUploadArea.style.pointerEvents = 'auto';
            
            if (data.success && data.filename) {
                if (coverImageInput) {
                    coverImageInput.value = data.filename;
                }
                
                const existingPreview = coverUploadArea.querySelector('.upload-preview');
                const placeholder = coverUploadArea.querySelector('.upload-placeholder');
                
                if (existingPreview) {
                    existingPreview.src = window.BASE_URL + 'uploads/articles/' + data.filename;
                } else {
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    const img = document.createElement('img');
                    img.src = window.BASE_URL + 'uploads/articles/' + data.filename;
                    img.alt = 'Cover image';
                    img.className = 'upload-preview';
                    img.style.cssText = 'max-width: 100%; max-height: 300px; border-radius: 8px; object-fit: contain;';
                    coverUploadArea.insertBefore(img, coverUploadArea.firstChild);
                }
            } else {
                alert(data.error || 'Ошибка при загрузке изображения');
            }
        })
        .catch(error => {
            coverUploadArea.style.opacity = '1';
            coverUploadArea.style.pointerEvents = 'auto';
            console.error('Error:', error);
            alert('Ошибка при загрузке изображения: ' + error.message);
        });
    }
});
</script>

<?php
include '../includes/admin-footer.php';
?>
