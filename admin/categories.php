<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['delete']]);
    redirect('categories.php');
}

// Get all categories
$stmt = $db->query("SELECT c.*, COUNT(p.id) as products_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name ASC");
$categories = $stmt->fetchAll();

$pageTitle = 'Категории';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page-header">
        <h1>Категории</h1>
        <a href="category-edit.php" class="btn-primary">Добавить категорию</a>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>URL</th>
                <th>Товаров</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?php echo $category['id']; ?></td>
                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                    <td><?php echo htmlspecialchars($category['slug']); ?></td>
                    <td><?php echo $category['products_count']; ?></td>
                    <td>
                        <a href="category-edit.php?id=<?php echo $category['id']; ?>" class="btn-small">Редактировать</a>
                        <a href="?delete=<?php echo $category['id']; ?>" class="btn-small btn-danger" onclick="return confirm('Удалить категорию?')">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
include '../includes/admin-footer.php';
?>

