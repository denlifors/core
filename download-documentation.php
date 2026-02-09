<?php
require_once 'config/config.php';

if (!isset($_GET['id'])) {
    redirect('catalog.php');
}

$db = getDBConnection();
$productId = (int)$_GET['id'];

// Get product
$stmt = $db->prepare("SELECT documentation_file, name FROM products WHERE id = :id AND status = 'active'");
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product || empty($product['documentation_file'])) {
    redirect('catalog.php');
}

$filePath = ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . $product['documentation_file'];

if (!file_exists($filePath)) {
    redirect('catalog.php');
}

// Get file info
$fileName = basename($product['documentation_file']);
$originalName = $product['name'] . '_documentation.' . pathinfo($fileName, PATHINFO_EXTENSION);

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $originalName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output file
readfile($filePath);
exit;
?>

