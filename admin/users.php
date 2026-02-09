<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();

// Get all users
$stmt = $db->query("SELECT id, email, first_name, last_name, phone, role, is_admin, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$pageTitle = 'Пользователи';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <h1>Пользователи</h1>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Имя</th>
                <th>Телефон</th>
                <th>Роль</th>
                <th>Дата регистрации</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                    <td>
                        <?php 
                        $roles = ['user' => 'Клиент', 'partner' => 'Партнёр'];
                        $roleName = $roles[$user['role']] ?? $user['role'];
                        $isAdmin = isset($user['is_admin']) && $user['is_admin'];
                        ?>
                        <span class="status-badge">
                            <?php echo $roleName; ?>
                        </span>
                        <?php if ($isAdmin): ?>
                            <span class="status-badge" style="background: #667eea; margin-left: 0.5rem;">
                                Администратор
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
include '../includes/admin-footer.php';
?>

