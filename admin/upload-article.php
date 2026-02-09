<?php
require_once '../config/admin-config.php';

// Set JSON header
header('Content-Type: application/json');

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$uploadDir = dirname(__DIR__) . '/uploads/articles/';

// Create directory if it doesn't exist
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
        exit;
    }
}

// Support both 'file' and 'image' field names
$fileField = null;
if (isset($_FILES['file'])) {
    $fileField = 'file';
} elseif (isset($_FILES['image'])) {
    $fileField = 'image';
}

if ($fileField === null || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = 'File upload error';
    if ($fileField && isset($_FILES[$fileField]['error'])) {
        switch ($_FILES[$fileField]['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = 'File too large';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg = 'File partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg = 'No file uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMsg = 'Missing temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMsg = 'Failed to write file';
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMsg = 'File upload stopped by extension';
                break;
        }
    }
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}

$file = $_FILES[$fileField];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File too large. Maximum size: 5MB']);
    exit;
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($extension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file extension']);
    exit;
}

$fileName = uniqid('article_') . '.' . $extension;
$filePath = $uploadDir . $fileName;

if (move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode([
        'success' => true,
        'filename' => $fileName,
        'file' => [
            'name' => $fileName,
            'url' => BASE_URL . 'uploads/articles/' . $fileName
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file. Check directory permissions.']);
}
?>





