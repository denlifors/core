<?php
require_once 'config/config.php';
require_once 'includes/core-client.php';

$token = sanitize($_GET['token'] ?? '');
if (!$token) {
    http_response_code(400);
    echo 'Некорректная ссылка подтверждения.';
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM partner_registrations WHERE token = :token LIMIT 1");
$stmt->execute([':token' => $token]);
$registration = $stmt->fetch();

if (!$registration) {
    http_response_code(404);
    echo 'Заявка не найдена или уже подтверждена.';
    exit;
}

if ($registration['status'] === 'confirmed') {
    redirect('dashboard.php?section=shop');
}

$createdAt = new DateTime($registration['created_at']);
$expiresAt = clone $createdAt;
$expiresAt->modify('+' . PARTNER_CONFIRM_TTL_HOURS . ' hours');
if (new DateTime() > $expiresAt) {
    http_response_code(410);
    echo 'Срок действия ссылки истёк. Отправьте заявку повторно.';
    exit;
}

// Create partner in core (Supabase)
$error = null;
$coreResult = corePostJson('/register-customer', [
    'email' => $registration['email'],
    'password' => $registration['password_plain']
], $error);

if (!$coreResult || $coreResult['status'] >= 400) {
    http_response_code(500);
    echo 'Ошибка регистрации в ядре. Попробуйте позже.';
    exit;
}

$coreUserId = $coreResult['data']['user']['id'] ?? null;
$coreCustomerId = $coreResult['data']['customer']['id'] ?? null;

// Update registration status and clear plain password
$stmt = $db->prepare(
    "UPDATE partner_registrations
     SET status = 'confirmed',
         confirmed_at = NOW(),
         core_user_id = :core_user_id,
         core_partner_id = NULL,
         core_customer_id = :core_customer_id,
         password_plain = NULL
     WHERE id = :id"
);
$stmt->execute([
    ':core_user_id' => $coreUserId,
    ':core_customer_id' => $coreCustomerId,
    ':id' => $registration['id']
]);

// Create or update local user for role linkage
$stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $registration['email']]);
$existingUser = $stmt->fetch();

if ($existingUser) {
    $stmt = $db->prepare(
        "UPDATE users
         SET role = 'user',
             core_user_id = :core_user_id,
             core_partner_id = NULL,
             core_customer_id = :core_customer_id
         WHERE id = :id"
    );
    $stmt->execute([
        ':core_user_id' => $coreUserId,
        ':core_customer_id' => $coreCustomerId,
        ':id' => $existingUser['id']
    ]);
    $localUserId = $existingUser['id'];
} else {
    $stmt = $db->prepare(
        "INSERT INTO users (email, password, first_name, phone, role, core_user_id, core_partner_id, core_customer_id)
         VALUES (:email, :password, :first_name, :phone, 'user', :core_user_id, NULL, :core_customer_id)"
    );
    $stmt->execute([
        ':email' => $registration['email'],
        ':password' => $registration['password_hash'],
        ':first_name' => $registration['name'],
        ':phone' => $registration['phone'],
        ':core_user_id' => $coreUserId,
        ':core_customer_id' => $coreCustomerId
    ]);
    $localUserId = $db->lastInsertId();
}

// Auto-login after confirmation
$_SESSION['user_id'] = $localUserId;
$_SESSION['user_email'] = $registration['email'];
$_SESSION['user_role'] = 'user';

redirect('dashboard.php?section=shop');
?>
