<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['delete']]);
    redirect('products.php');
}

// Handle activate (выпустить товар)
if (isset($_GET['activate']) && is_numeric($_GET['activate'])) {
    $stmt = $db->prepare("UPDATE products SET status = 'active' WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['activate']]);
    redirect('products.php');
}

// Handle deactivate (убрать из магазина)
if (isset($_GET['deactivate']) && is_numeric($_GET['deactivate'])) {
    $stmt = $db->prepare("UPDATE products SET status = 'inactive' WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['deactivate']]);
    redirect('products.php');
}

// Get all products
$stmt = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
$products = $stmt->fetchAll();

$pageTitle = 'Управление товарами';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page-header">
        <h1>Товары</h1>
        <a href="product-edit.php" class="btn-primary">Добавить товар</a>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Артикул</th>
                <th>Категория</th>
                <th>Цена</th>
                <th>Остаток</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                    <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                    <td><?php echo formatPrice($product['price']); ?></td>
                    <td><?php echo $product['stock']; ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $product['status']; ?>">
                            <?php 
                            $statuses = [
                                'active' => 'Активен',
                                'inactive' => 'Неактивен',
                                'out_of_stock' => 'Нет в наличии'
                            ];
                            echo $statuses[$product['status']] ?? $product['status'];
                            ?>
                        </span>
                    </td>
                    <td>
                        <div class="actions-dropdown">
                            <button class="btn-small actions-toggle" onclick="toggleActions(<?php echo $product['id']; ?>)">Действия ▼</button>
                            <div class="actions-menu" id="actions-<?php echo $product['id']; ?>" style="display: none;">
                                <a href="product-edit.php?id=<?php echo $product['id']; ?>" class="actions-item">Редактировать</a>
                                <?php if ($product['status'] === 'inactive'): ?>
                                    <a href="?activate=<?php echo $product['id']; ?>" class="actions-item" onclick="return confirm('Выпустить товар в магазин? Товар станет активным.');">Выпустить товар</a>
                                <?php elseif ($product['status'] === 'active'): ?>
                                    <a href="?deactivate=<?php echo $product['id']; ?>" class="actions-item" onclick="return confirm('Убрать товар из магазина? Товар станет неактивным и не будет виден покупателям.');">Убрать из магазина</a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $product['id']; ?>" class="actions-item actions-item-danger" onclick="return confirm('Удалить товар?')">Удалить</a>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
.actions-dropdown {
    position: relative;
    display: inline-block;
}

.actions-toggle {
    background: #667eea;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.actions-toggle:hover {
    background: #5568d3;
}

.actions-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 160px;
    z-index: 100;
    margin-top: 4px;
    overflow: hidden;
}

.actions-item {
    display: block;
    padding: 10px 16px;
    color: #2d3748;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.2s ease;
    border-bottom: 1px solid #f7fafc;
}

.actions-item:last-child {
    border-bottom: none;
}

.actions-item:hover {
    background: #f7fafc;
    color: #667eea;
}

.actions-item-danger {
    color: #e53e3e;
}

.actions-item-danger:hover {
    background: #fed7d7;
    color: #c53030;
}
</style>
<script>
function toggleActions(productId) {
    const menu = document.getElementById('actions-' + productId);
    const isVisible = menu.style.display === 'block';
    
    // Закрываем все меню
    document.querySelectorAll('.actions-menu').forEach(m => {
        m.style.display = 'none';
    });
    
    // Открываем нужное меню, если оно было закрыто
    if (!isVisible) {
        menu.style.display = 'block';
    }
    
    // Закрываем меню при клике вне его
    setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
            if (!e.target.closest('.actions-dropdown')) {
                document.querySelectorAll('.actions-menu').forEach(m => {
                    m.style.display = 'none';
                });
                document.removeEventListener('click', closeMenu);
            }
        });
    }, 0);
}
</script>

<?php
include '../includes/admin-footer.php';
?>

