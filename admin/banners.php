<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM banners WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['delete']]);
    redirect('banners.php');
}

// Get all banners
$stmt = $db->query("SELECT * FROM banners ORDER BY sort_order ASC, created_at DESC");
$banners = $stmt->fetchAll();

$pageTitle = 'Баннеры';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page-header">
        <h1>Баннеры</h1>
        <div style="display: flex; gap: 1rem;">
            <a href="banner-order.php" class="btn-secondary">Порядок баннеров</a>
            <a href="banner-edit.php" class="btn-primary">Создать баннер</a>
        </div>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Изображение</th>
                <th>Тип</th>
                <th>Название</th>
                <th>Страница</th>
                <th>Ссылка</th>
                <th>Порядок</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($banners as $banner): ?>
                <tr>
                    <td><?php echo $banner['id']; ?></td>
                    <td>
                        <?php if ($banner['image']): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/banners/<?php echo htmlspecialchars($banner['image']); ?>" alt="Banner" style="width: 100px; height: 60px; object-fit: cover; border-radius: 4px;">
                        <?php else: ?>
                            <span style="color: #999;">Нет изображения</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $bannerType = $banner['type'] ?? 'detailed';
                        $typeNames = [
                            'detailed' => 'Детальный',
                            'simple' => 'Простой'
                        ];
                        echo $typeNames[$bannerType] ?? 'Детальный';
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($banner['title'] ?? '-'); ?></td>
                    <td>
                        <?php 
                        $pageNames = [
                            'home' => 'Главная',
                            'partnership' => 'Партнёры',
                            'catalog' => 'Каталог',
                            'all' => 'Все страницы'
                        ];
                        echo $pageNames[$banner['page'] ?? 'home'] ?? 'Главная';
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($banner['link'] ?? '-'); ?></td>
                    <td><?php echo $banner['sort_order']; ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $banner['status']; ?>">
                            <?php echo $banner['status'] === 'active' ? 'Активен' : 'Неактивен'; ?>
                        </span>
                    </td>
                    <td>
                        <a href="banner-edit.php?id=<?php echo $banner['id']; ?>" class="btn-small">Редактировать</a>
                        <a href="?delete=<?php echo $banner['id']; ?>" class="btn-small btn-danger" onclick="return confirm('Удалить баннер?')">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
include '../includes/admin-footer.php';
?>

