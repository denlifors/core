<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$db = getDBConnection();
$tab = $_GET['tab'] ?? 'profile';

// Get user info
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    
    $stmt = $db->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, phone = :phone WHERE id = :id");
    $stmt->execute([
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':phone' => $phone,
        ':id' => $_SESSION['user_id']
    ]);
    
    $success = "Профиль успешно обновлен";
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (password_verify($currentPassword, $user['password'])) {
        if ($newPassword === $confirmPassword && strlen($newPassword) >= 6) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute([':password' => $hashedPassword, ':id' => $_SESSION['user_id']]);
            $success = "Пароль успешно изменен";
        } else {
            $error = "Пароли не совпадают или слишком короткие";
        }
    } else {
        $error = "Неверный текущий пароль";
    }
}

// Get orders
$ordersStmt = $db->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
$ordersStmt->execute([':user_id' => $_SESSION['user_id']]);
$orders = $ordersStmt->fetchAll();

$pageTitle = 'Профиль';
include 'includes/header.php';
?>

<section class="profile-section">
    <div class="container">
        <h1 class="page-title">Личный кабинет</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="profile-layout">
            <aside class="profile-sidebar">
                <nav class="profile-nav">
                    <a href="?tab=profile" class="<?php echo $tab === 'profile' ? 'active' : ''; ?>">Профиль</a>
                    <a href="?tab=orders" class="<?php echo $tab === 'orders' ? 'active' : ''; ?>">Заказы</a>
                    <a href="?tab=password" class="<?php echo $tab === 'password' ? 'active' : ''; ?>">Смена пароля</a>
                </nav>
            </aside>
            
            <div class="profile-content">
                <?php if ($tab === 'profile'): ?>
                    <div class="profile-tab">
                        <h2>Личные данные</h2>
                        <form method="POST" class="profile-form">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small>Email нельзя изменить</small>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Имя</label>
                                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Фамилия</label>
                                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Телефон</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <button type="submit" class="btn-primary">Сохранить изменения</button>
                        </form>
                    </div>
                    
                <?php elseif ($tab === 'orders'): ?>
                    <div class="profile-tab">
                        <h2>Мои заказы</h2>
                        <?php if (empty($orders)): ?>
                            <p>У вас пока нет заказов</p>
                            <a href="catalog.php" class="btn-primary">Перейти в каталог</a>
                        <?php else: ?>
                            <div class="orders-list">
                                <?php foreach ($orders as $order): ?>
                                    <div class="order-card">
                                        <div class="order-header">
                                            <div class="order-number">
                                                <strong>Заказ №<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                <span class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                                            </div>
                                            <div class="order-status">
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
                                            </div>
                                        </div>
                                        <div class="order-body">
                                            <div class="order-total">
                                                Сумма: <strong><?php echo formatPrice($order['total']); ?></strong>
                                            </div>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn-secondary">Детали заказа</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif ($tab === 'password'): ?>
                    <div class="profile-tab">
                        <h2>Смена пароля</h2>
                        <form method="POST" class="profile-form">
                            <input type="hidden" name="change_password" value="1">
                            <div class="form-group">
                                <label>Текущий пароль</label>
                                <input type="password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label>Новый пароль</label>
                                <input type="password" name="new_password" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label>Подтвердите новый пароль</label>
                                <input type="password" name="confirm_password" required minlength="6">
                            </div>
                            <button type="submit" class="btn-primary">Изменить пароль</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
include 'includes/footer.php';
?>






