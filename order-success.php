<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$orderNumber = $_GET['order'] ?? '';

if (empty($orderNumber)) {
    redirect('index.php');
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM orders WHERE order_number = :order_number AND user_id = :user_id");
$stmt->execute([':order_number' => $orderNumber, ':user_id' => $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('index.php');
}

$pageTitle = 'Заказ оформлен';
include 'includes/header.php';
?>

<section class="order-success-section">
    <div class="container">
        <div class="success-message">
            <div class="success-icon">✓</div>
            <h1>Спасибо за ваш заказ!</h1>
            <p>Номер вашего заказа: <strong><?php echo htmlspecialchars($order['order_number']); ?></strong></p>
            <p>Мы отправили подтверждение на email: <strong><?php echo htmlspecialchars($order['email']); ?></strong></p>
        </div>
        
        <div class="order-details">
            <h2>Детали заказа</h2>
            <div class="order-info-grid">
                <div class="order-info-item">
                    <strong>Статус:</strong>
                    <span><?php 
                        $statuses = [
                            'pending' => 'Ожидает обработки',
                            'processing' => 'В обработке',
                            'shipped' => 'Отправлен',
                            'delivered' => 'Доставлен',
                            'cancelled' => 'Отменен'
                        ];
                        echo $statuses[$order['status']] ?? $order['status'];
                    ?></span>
                </div>
                <div class="order-info-item">
                    <strong>Сумма:</strong>
                    <span><?php echo formatPrice($order['total']); ?></span>
                </div>
                <div class="order-info-item">
                    <strong>Способ оплаты:</strong>
                    <span><?php echo htmlspecialchars($order['payment_method']); ?></span>
                </div>
                <div class="order-info-item">
                    <strong>Дата заказа:</strong>
                    <span><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                </div>
            </div>
        </div>
        
        <div class="order-actions">
            <a href="profile.php?tab=orders" class="btn-primary">Мои заказы</a>
            <a href="catalog.php" class="btn-secondary">Продолжить покупки</a>
        </div>
    </div>
</section>

<?php
include 'includes/footer.php';
?>






