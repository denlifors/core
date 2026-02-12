<?php
require_once '../config/config.php';
require_once '../includes/core-client.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT core_partner_id FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => (int)$_SESSION['user_id']]);
    $row = $stmt->fetch();
    $partnerId = trim((string)($row['core_partner_id'] ?? ''));

    if ($partnerId === '') {
        echo json_encode([
            'success' => true,
            'isPartner' => false,
            'hasDownline' => false,
            'bonusTotal' => 0,
            'checkedAt' => date('c'),
        ]);
        exit;
    }

    $hasDownline = false;
    $bonusTotal = 0;

    $treeErr = null;
    $treeRes = coreGetJson('/debug/tree?partnerId=' . urlencode($partnerId) . '&depth=8', $treeErr);
    if ($treeRes && ($treeRes['status'] ?? 500) < 400 && !empty($treeRes['data'])) {
        $root = $treeRes['data'];
        $children = is_array($root['children'] ?? null) ? $root['children'] : [];
        foreach ($children as $child) {
            if (is_array($child) && !empty($child['partnerId'])) {
                $hasDownline = true;
                break;
            }
        }
    }

    $from = date('Y-m-01');
    $to = date('Y-m-d');
    $bonusErr = null;
    $bonusRes = coreGetJson('/partner-bonus-history?partnerId=' . urlencode($partnerId) . '&type=all&from=' . urlencode($from) . '&to=' . urlencode($to) . '&page=1&perPage=1', $bonusErr);
    if ($bonusRes && ($bonusRes['status'] ?? 500) < 400) {
        $bonusTotal = (int)($bonusRes['data']['total'] ?? 0);
    }

    echo json_encode([
        'success' => true,
        'isPartner' => true,
        'hasDownline' => $hasDownline,
        'bonusTotal' => $bonusTotal,
        'checkedAt' => date('c'),
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

