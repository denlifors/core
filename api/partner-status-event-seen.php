<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$eventId = (int)($input['event_id'] ?? 0);
if ($eventId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid event ID']);
    exit;
}

try {
    $db = getDBConnection();
    $stmt = $db->prepare("
        UPDATE partner_status_events
        SET is_shown = 1, shown_at = NOW()
        WHERE id = :id AND user_id = :uid
    ");
    $stmt->execute([
        ':id' => $eventId,
        ':uid' => (int)$_SESSION['user_id'],
    ]);

    echo json_encode(['success' => true, 'updated' => (int)$stmt->rowCount()]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

