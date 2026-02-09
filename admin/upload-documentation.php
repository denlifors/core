<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

$uploadDir = '../uploads/documents/';
$allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
$maxFileSize = 10 * 1024 * 1024; // 10MB

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Upload error']);
        exit;
    }
    
    $fileSize = $_FILES['file']['size'];
    $fileType = $_FILES['file']['type'];
    $mimeType = mime_content_type($_FILES['file']['tmp_name']);
    
    // Check both declared and actual MIME type
    if (!in_array($fileType, $allowedTypes) && !in_array($mimeType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG']);
        exit;
    }
    
    if ($fileSize > $maxFileSize) {
        echo json_encode(['success' => false, 'error' => 'File too large. Max size: 10MB']);
        exit;
    }
    
    $originalName = $_FILES['file']['name'];
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $newFileName = 'doc_' . uniqid('', true) . '.' . $extension;
    $targetPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        echo json_encode([
            'success' => true,
            'file' => [
                'name' => $newFileName,
                'originalName' => $originalName,
                'url' => BASE_URL . 'uploads/documents/' . $newFileName
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
}
?>


