<?php
// Подключаем config если еще не подключен
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$orderNumber = $_GET['order'] ?? '';

if (empty($orderNumber)) {
    redirect('dashboard.php?section=cart');
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM orders WHERE order_number = :order_number AND user_id = :user_id");
$stmt->execute([':order_number' => $orderNumber, ':user_id' => $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('dashboard.php?section=cart');
}
?>

<div class="dashboard-order-success">
    <div class="dashboard-order-success-content">
        <div class="dashboard-order-success-icon">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <h1 class="dashboard-order-success-title">Спасибо за ваш заказ!</h1>
        <p class="dashboard-order-success-text">
            Номер вашего заказа: <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
        </p>
        <p class="dashboard-order-success-text">
            Мы отправили подтверждение на email: <strong><?php echo htmlspecialchars($order['email']); ?></strong>
        </p>
        
        <div class="dashboard-order-details">
            <h2 class="dashboard-order-details-title">Детали заказа</h2>
            <div class="dashboard-order-info-grid">
                <div class="dashboard-order-info-item">
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
                <div class="dashboard-order-info-item">
                    <strong>Сумма:</strong>
                    <span><?php echo formatPrice($order['total']); ?></span>
                </div>
                <div class="dashboard-order-info-item">
                    <strong>Способ оплаты:</strong>
                    <span><?php echo htmlspecialchars($order['payment_method']); ?></span>
                </div>
                <div class="dashboard-order-info-item">
                    <strong>Дата заказа:</strong>
                    <span><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                </div>
            </div>
        </div>
        
        <div class="dashboard-order-success-actions">
            <a href="dashboard.php?section=orders" class="dashboard-btn-primary">Мои заказы</a>
            <a href="dashboard.php?section=shop" class="dashboard-btn-secondary">Продолжить покупки</a>
        </div>
    </div>
</div>


