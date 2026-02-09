<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('profile.php?tab=orders');
}

$db = getDBConnection();
$orderId = (int)$_GET['id'];

// Get order
$stmt = $db->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :user_id");
$stmt->execute([':id' => $orderId, ':user_id' => $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('profile.php?tab=orders');
}

// Get order items
$stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
$stmt->execute([':order_id' => $orderId]);
$orderItems = $stmt->fetchAll();

$pageTitle = 'Заказ #' . $order['order_number'];
include 'includes/header.php';
?>

<section class="order-details-section">
    <div class="container">
        <h1 class="page-title">Заказ #<?php echo htmlspecialchars($order['order_number']); ?></h1>
        
        <div class="order-details-content">
            <div class="order-info-card">
                <h2>Информация о заказе</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Статус:</strong>
                        <span>
                            <?php 
                            $statuses = [
                                'pending' => 'Ожидает обработки',
                                'processing' => 'В обработке',
                                'shipped' => 'Отправлен',
                                'delivered' => 'Доставлен',
                                'cancelled' => 'Отменен'
                            ];
                            echo $statuses[$order['status']] ?? $order['status'];
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <strong>Дата заказа:</strong>
                        <span><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Сумма:</strong>
                        <span><?php echo formatPrice($order['total']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Способ оплаты:</strong>
                        <span><?php echo htmlspecialchars($order['payment_method']); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="order-items-card">
                <h2>Товары в заказе</h2>
                <table class="order-items-table">
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
            
            <div class="order-address-card">
                <h2>Адрес доставки</h2>
                <p>
                    <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?><br>
                    <?php echo htmlspecialchars($order['city']); ?><br>
                    <?php echo htmlspecialchars($order['address']); ?><br>
                    <?php if ($order['postal_code']): ?>
                        Индекс: <?php echo htmlspecialchars($order['postal_code']); ?><br>
                    <?php endif; ?>
                    Телефон: <?php echo htmlspecialchars($order['phone']); ?><br>
                    Email: <?php echo htmlspecialchars($order['email']); ?>
                </p>
            </div>
            
            <div class="order-actions">
                <a href="profile.php?tab=orders" class="btn-secondary">Назад к заказам</a>
            </div>
        </div>
    </div>
</section>

<style>
.order-details-section {
    padding: 2rem 0 4rem;
}

.order-details-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.order-info-card,
.order-items-card,
.order-address-card,
.order-notes-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.order-items-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.order-items-table th {
    background: #f7fafc;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
}

.order-items-table td {
    padding: 1rem;
    border-top: 1px solid #e2e8f0;
}

.order-items-table tfoot {
    font-weight: 600;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.order-actions {
    margin-top: 1rem;
}
</style>

<?php
include 'includes/footer.php';
?>






