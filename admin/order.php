<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('orders.php');
}

$db = getDBConnection();
$orderId = (int)$_GET['id'];

// Get order
$stmt = $db->prepare("SELECT * FROM orders WHERE id = :id");
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    redirect('orders.php');
}

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = sanitize($_POST['status']);
    $stmt = $db->prepare("UPDATE orders SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $newStatus, ':id' => $orderId]);
    redirect('order.php?id=' . $orderId);
}

// Get order items
$stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
$stmt->execute([':order_id' => $orderId]);
$orderItems = $stmt->fetchAll();

$pageTitle = 'Заказ #' . $order['order_number'];
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <h1>Заказ #<?php echo htmlspecialchars($order['order_number']); ?></h1>
    
    <div class="order-details-admin">
        <div class="order-info-section">
            <h2>Информация о заказе</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Статус:</strong>
                    <form method="POST" style="display: inline;">
                        <select name="status" onchange="this.form.submit()">
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Ожидает обработки</option>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>В обработке</option>
                            <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Отправлен</option>
                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Доставлен</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                        </select>
                        <input type="hidden" name="update_status" value="1">
                    </form>
                </div>
                <div class="info-item">
                    <strong>Дата заказа:</strong>
                    <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                </div>
                <div class="info-item">
                    <strong>Сумма:</strong>
                    <?php echo formatPrice($order['total']); ?>
                </div>
                <div class="info-item">
                    <strong>Способ оплаты:</strong>
                    <?php echo htmlspecialchars($order['payment_method'] ?? '-'); ?>
                </div>
            </div>
        </div>
        
        <div class="order-customer-section">
            <h2>Контактная информация</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Имя:</strong>
                    <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                </div>
                <div class="info-item">
                    <strong>Email:</strong>
                    <?php echo htmlspecialchars($order['email']); ?>
                </div>
                <div class="info-item">
                    <strong>Телефон:</strong>
                    <?php echo htmlspecialchars($order['phone'] ?? '-'); ?>
                </div>
                <div class="info-item">
                    <strong>Адрес:</strong>
                    <?php echo htmlspecialchars($order['city'] . ', ' . $order['address']); ?>
                    <?php if ($order['postal_code']): ?>
                        <br>Индекс: <?php echo htmlspecialchars($order['postal_code']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="order-items-section">
            <h2>Товары в заказе</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Товар</th>
                        <th>Артикул</th>
                        <th>Цена</th>
                        <th>Количество</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['product_sku']); ?></td>
                            <td><?php echo formatPrice($item['price']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align: right;"><strong>Итого:</strong></td>
                        <td><strong><?php echo formatPrice($order['total']); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <div class="form-actions">
        <a href="orders.php" class="btn-secondary">Назад к заказам</a>
    </div>
</div>

<?php
include '../includes/admin-footer.php';
?>

