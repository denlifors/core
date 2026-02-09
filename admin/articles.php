<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM articles WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['delete']]);
    redirect('articles.php');
}

// Handle publish
if (isset($_GET['publish']) && is_numeric($_GET['publish'])) {
    $stmt = $db->prepare("UPDATE articles SET status = 'published' WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['publish']]);
    redirect('articles.php');
}

// Handle unpublish (set to draft)
if (isset($_GET['unpublish']) && is_numeric($_GET['unpublish'])) {
    $stmt = $db->prepare("UPDATE articles SET status = 'draft' WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['unpublish']]);
    redirect('articles.php');
}

// Get all articles
$articles = [];
$error = null;

try {
    $stmt = $db->query("SELECT * FROM articles ORDER BY created_at DESC");
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table doesn't exist yet
    $error = "Таблица статей не создана. <a href='create-articles-table.php' style='color: #667eea; text-decoration: underline;'>Создать таблицу</a>";
}

$pageTitle = 'Статьи';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page-header">
        <h1>Статьи</h1>
        <a href="article-edit.php" class="btn-primary">Добавить статью</a>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (empty($articles)): ?>
        <div class="alert alert-info">
            <p>Статей пока нет. <a href="article-edit.php">Создайте первую статью</a></p>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Краткое описание</th>
                    <th>Статус</th>
                    <th>Просмотры</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td><?php echo $article['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($article['title']); ?></strong></td>
                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php echo htmlspecialchars($article['short_description'] ?? $article['excerpt'] ?? '-'); ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $article['status']; ?>">
                                <?php echo $article['status'] === 'published' ? 'Опубликована' : 'Черновик'; ?>
                            </span>
                        </td>
                        <td><?php echo $article['view_count'] ?? 0; ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($article['created_at'])); ?></td>
                        <td>
                            <div class="actions-dropdown">
                                <button class="btn-small actions-toggle" onclick="toggleActions(<?php echo $article['id']; ?>)">Действия ▼</button>
                                <div class="actions-menu" id="actions-<?php echo $article['id']; ?>" style="display: none;">
                                    <a href="article-edit.php?id=<?php echo $article['id']; ?>" class="actions-item">Редактировать</a>
                                    <?php if ($article['status'] === 'draft'): ?>
                                        <a href="?publish=<?php echo $article['id']; ?>" class="actions-item" onclick="return confirm('Опубликовать статью?');">Опубликовать</a>
                                    <?php elseif ($article['status'] === 'published'): ?>
                                        <a href="?unpublish=<?php echo $article['id']; ?>" class="actions-item" onclick="return confirm('Снять статью с публикации? Статья станет черновиком.');">Не активен</a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $article['id']; ?>" class="actions-item actions-item-danger" onclick="return confirm('Удалить статью?')">Удалить</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.actions-dropdown {
    position: relative;
    display: inline-block;
}

.actions-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 180px;
    z-index: 1000;
    margin-top: 0.25rem;
}

.actions-item {
    display: block;
    padding: 0.75rem 1rem;
    color: #4a5568;
    text-decoration: none;
    border-bottom: 1px solid #f7fafc;
    transition: background 0.2s;
}

.actions-item:last-child {
    border-bottom: none;
}

.actions-item:hover {
    background: #f7fafc;
}

.actions-item-danger {
    color: #e53e3e;
}

.actions-item-danger:hover {
    background: #fee;
}
</style>

<script>
function toggleActions(articleId) {
    const menu = document.getElementById(`actions-${articleId}`);
    document.querySelectorAll('.actions-menu').forEach(m => {
        if (m.id !== `actions-${articleId}`) {
            m.style.display = 'none';
        }
    });
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('.actions-dropdown')) {
        document.querySelectorAll('.actions-menu').forEach(m => {
            m.style.display = 'none';
        });
    }
});
</script>

<?php
include '../includes/admin-footer.php';
?>

