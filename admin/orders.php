<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();

// Get filter
$statusFilter = $_GET['status'] ?? '';

// Build query
$where = [];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "status = :status";
    $params[':status'] = $statusFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get orders
$sql = "SELECT * FROM orders $whereClause ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Заказы';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page-header">
        <h1>Заказы</h1>
        <div class="filter-buttons">
            <a href="orders.php" class="btn-small <?php echo empty($statusFilter) ? 'active' : ''; ?>">Все</a>
            <a href="?status=pending" class="btn-small <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Ожидают</a>
            <a href="?status=processing" class="btn-small <?php echo $statusFilter === 'processing' ? 'active' : ''; ?>">В обработке</a>
            <a href="?status=shipped" class="btn-small <?php echo $statusFilter === 'shipped' ? 'active' : ''; ?>">Отправлены</a>
            <a href="?status=delivered" class="btn-small <?php echo $statusFilter === 'delivered' ? 'active' : ''; ?>">Доставлены</a>
        </div>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Номер заказа</th>
                <th>Клиент</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                    <td><?php echo htmlspecialchars($order['phone'] ?? '-'); ?></td>
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

<?php
include '../includes/admin-footer.php';
?>

