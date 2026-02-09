<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$name = sanitize($_POST['name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$consent = isset($_POST['consent']) ? true : false;

if (empty($name) || empty($email) || empty($phone) || !$consent) {
    echo json_encode(['success' => false, 'error' => 'Заполните все поля и дайте согласие']);
    exit;
}

// In production, save to database or send email
// For now, just return success
// $db = getDBConnection();
// Save partner registration...

echo json_encode(['success' => true, 'message' => 'Заявка отправлена. Мы свяжемся с вами в ближайшее время.']);
?>






