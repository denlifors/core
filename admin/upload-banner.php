<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

$uploadDir = '../uploads/banners/';
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$response = ['success' => false, 'filename' => null, 'error' => null];

// Support both 'file' and 'image' field names for compatibility
$fileField = null;
if (isset($_FILES['file'])) {
    $fileField = 'file';
} elseif (isset($_FILES['image'])) {
    $fileField = 'image';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $fileField !== null) {
    if ($_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Файл превышает максимальный размер',
            UPLOAD_ERR_FORM_SIZE => 'Файл превышает максимальный размер формы',
            UPLOAD_ERR_PARTIAL => 'Файл загружен частично',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Ошибка записи файла на диск',
            UPLOAD_ERR_EXTENSION => 'Загрузка остановлена расширением'
        ];
        $errorCode = $_FILES[$fileField]['error'];
        $errorMsg = $errorMessages[$errorCode] ?? 'Ошибка загрузки файла';
        echo json_encode(['success' => false, 'error' => $errorMsg]);
        exit;
    }
    
    $fileSize = $_FILES[$fileField]['size'];
    $fileType = $_FILES[$fileField]['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Недопустимый тип файла. Разрешены: JPG, PNG, GIF, WEBP']);
        exit;
    }
    
    if ($fileSize > $maxFileSize) {
        echo json_encode(['success' => false, 'error' => 'Файл слишком большой. Максимальный размер: 5MB']);
        exit;
    }
    
    $originalName = $_FILES[$fileField]['name'];
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $newFileName = uniqid('banner_', true) . '.' . $extension;
    $targetPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $targetPath)) {
        $response['success'] = true;
        $response['filename'] = $newFileName;
        $response['file'] = [
            'name' => $newFileName,
            'originalName' => $originalName,
            'url' => BASE_URL . 'uploads/banners/' . $newFileName
        ];
    } else {
        $response['error'] = 'Ошибка при сохранении файла';
    }
} else {
    $response['error'] = 'Файл не был отправлен';
}

echo json_encode($response);
?>






