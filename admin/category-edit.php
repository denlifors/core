<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category = null;

if ($categoryId > 0) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute([':id' => $categoryId]);
    $category = $stmt->fetch();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    
    // Generate slug if empty
    if (empty($slug)) {
        $slug = mb_strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }
    
    try {
        if ($categoryId > 0) {
            $stmt = $db->prepare("UPDATE categories SET name = :name, slug = :slug, description = :description WHERE id = :id");
            $stmt->execute([':name' => $name, ':slug' => $slug, ':description' => $description, ':id' => $categoryId]);
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :description)");
            $stmt->execute([':name' => $name, ':slug' => $slug, ':description' => $description]);
        }
        redirect('categories.php');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = $categoryId > 0 ? 'Редактировать категорию' : 'Добавить категорию';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <h1><?php echo $categoryId > 0 ? 'Редактировать категорию' : 'Добавить категорию'; ?></h1>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" class="admin-form">
        <div class="form-group">
            <label>Название *</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label>URL (slug)</label>
            <input type="text" name="slug" value="<?php echo htmlspecialchars($category['slug'] ?? ''); ?>">
            <small>Оставьте пустым для автогенерации</small>
        </div>
        
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">Сохранить</button>
            <a href="categories.php" class="btn-secondary">Отмена</a>
        </div>
    </form>
</div>

<?php
include '../includes/admin-footer.php';
?>

