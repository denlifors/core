<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

$uploadDir = '../uploads/products/';
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$response = ['success' => false, 'files' => [], 'file' => null];

// Handle single file upload (for main image)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Upload error']);
        exit;
    }
    
    $fileSize = $_FILES['file']['size'];
    $fileType = $_FILES['file']['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit;
    }
    
    if ($fileSize > $maxFileSize) {
        echo json_encode(['success' => false, 'error' => 'File too large']);
        exit;
    }
    
    $originalName = $_FILES['file']['name'];
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $newFileName = uniqid('img_', true) . '.' . $extension;
    $targetPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        $response['success'] = true;
        $response['file'] = [
            'name' => $newFileName,
            'originalName' => $originalName,
            'url' => BASE_URL . 'uploads/products/' . $newFileName
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Handle multiple files upload (for gallery)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $uploadedFiles = [];
    
    // Handle array of files
    if (is_array($_FILES['files']['tmp_name'])) {
        foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $fileSize = $_FILES['files']['size'][$key];
            $fileType = $_FILES['files']['type'][$key];
            
            if (!in_array($fileType, $allowedTypes)) {
                continue;
            }
            
            if ($fileSize > $maxFileSize) {
                continue;
            }
            
            $originalName = $_FILES['files']['name'][$key];
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $newFileName = uniqid('img_', true) . '.' . $extension;
            $targetPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                $uploadedFiles[] = [
                    'name' => $newFileName,
                    'originalName' => $originalName,
                    'url' => BASE_URL . 'uploads/products/' . $newFileName
                ];
            }
        }
    }
    
    if (!empty($uploadedFiles)) {
        $response['success'] = true;
        $response['files'] = $uploadedFiles;
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode($response);
?>

