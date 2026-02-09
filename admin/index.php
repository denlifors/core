<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/admin-config.php';

// Check if user is logged in and is admin
if (!isAdmin()) {
    // Store the intended destination
    $_SESSION['admin_redirect_after_login'] = 'index.php';
    redirect('login.php');
}

$db = getDBConnection();

// Get statistics
$stats = [];

// Total products
$stmt = $db->query("SELECT COUNT(*) FROM products");
$stats['total_products'] = $stmt->fetchColumn();

// Active products
$stmt = $db->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
$stats['active_products'] = $stmt->fetchColumn();

// Total orders
$stmt = $db->query("SELECT COUNT(*) FROM orders");
$stats['total_orders'] = $stmt->fetchColumn();

// Pending orders
$stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $stmt->fetchColumn();

// Total revenue
$stmt = $db->query("SELECT SUM(total) FROM orders WHERE status IN ('processing', 'shipped', 'delivered')");
$stats['total_revenue'] = $stmt->fetchColumn() ?: 0;

// Total users
$stmt = $db->query("SELECT COUNT(*) FROM users");
$stats['total_users'] = $stmt->fetchColumn();

// Recent orders
$stmt = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");
$recentOrders = $stmt->fetchAll();

$pageTitle = 'Админ-панель';
include '../includes/admin-header.php';
?>

<div class="admin-dashboard">
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Всего товаров</h3>
            <div class="stat-value"><?php echo $stats['total_products']; ?></div>
            <div class="stat-label">Активных: <?php echo $stats['active_products']; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Всего заказов</h3>
            <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
            <div class="stat-label">Ожидают: <?php echo $stats['pending_orders']; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Выручка</h3>
            <div class="stat-value"><?php echo formatPrice($stats['total_revenue']); ?></div>
            <div class="stat-label">За все время</div>
        </div>
        
        <div class="stat-card">
            <h3>Пользователи</h3>
            <div class="stat-value"><?php echo $stats['total_users']; ?></div>
            <div class="stat-label">Всего зарегистрировано</div>
        </div>
    </div>
    
    <div class="admin-section">
        <h2>Последние заказы</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Номер заказа</th>
                    <th>Пользователь</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                        <td><?php echo htmlspecialchars($order['email']); ?></td>
                        <td><?php echo formatPrice($order['total']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php 
                                $statuses = [
                                    'pending' => 'Ожидает',
                                    'processing' => 'Обработка',
                                    'shipped' => 'Отправлен',
                                    'delivered' => 'Доставлен',
                                    'cancelled' => 'Отменен'
                                ];
                                echo $statuses[$order['status']] ?? $order['status'];
                                ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                        <td>
                            <a href="order.php?id=<?php echo $order['id']; ?>" class="btn-small">Просмотр</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../includes/admin-footer.php';
?>

