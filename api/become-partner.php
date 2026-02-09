<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action !== 'become_partner') {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

$db = getDBConnection();

try {
    // Проверяем текущий статус пользователя
    $stmt = $db->prepare("SELECT role FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    if ($user['role'] === 'partner') {
        echo json_encode(['success' => false, 'error' => 'You are already a partner']);
        exit;
    }
    
    // Изменяем статус на партнёр
    $stmt = $db->prepare("UPDATE users SET role = 'partner' WHERE id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    
    // Обновляем сессию
    $_SESSION['user_role'] = 'partner';
    
    echo json_encode(['success' => true, 'message' => 'Status changed to partner']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>


