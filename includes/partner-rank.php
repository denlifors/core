<?php
require_once dirname(__DIR__) . '/includes/core-client.php';

function partnerRankConfig(): array
{
    return [
        'order' => ['CLIENT', 'PARTNER', 'BRONZE', 'SILVER', 'PLATINUM', 'DIRECTOR', 'COMMERCIAL_DIRECTOR', 'GOLD', 'DIAMOND', 'EXECUTIVE_DIRECTOR', 'GENERAL_DIRECTOR'],
        'labels' => [
            'CLIENT' => 'Клиент',
            'PARTNER' => 'Партнёр',
            'BRONZE' => 'Бронзовый лидер',
            'SILVER' => 'Серебряный лидер',
            'PLATINUM' => 'Платиновый лидер',
            'DIRECTOR' => 'Директор',
            'COMMERCIAL_DIRECTOR' => 'Коммерческий директор',
            'GOLD' => 'Золотой лидер',
            'DIAMOND' => 'Бриллиантовый лидер',
            'EXECUTIVE_DIRECTOR' => 'Исполнительный директор',
            'GENERAL_DIRECTOR' => 'Генеральный директор',
        ],
        'icons' => [
            'PARTNER' => 'partner.svg',
            'BRONZE' => 'bronza.svg',
            'SILVER' => 'serebro.svg',
            'PLATINUM' => 'platina.svg',
            'DIRECTOR' => 'director.svg',
            'COMMERCIAL_DIRECTOR' => 'komer-director.svg',
            'GOLD' => 'zoloto.svg',
            'DIAMOND' => 'briliant.svg',
            'EXECUTIVE_DIRECTOR' => 'ispol-director.svg',
            'GENERAL_DIRECTOR' => 'gen-director.svg',
        ],
        'bonus' => [
            'CLIENT' => 0,
            'PARTNER' => 0,
            'BRONZE' => 10,
            'SILVER' => 12,
            'PLATINUM' => 15,
            'DIRECTOR' => 18,
            'COMMERCIAL_DIRECTOR' => 20,
            'GOLD' => 22,
            'DIAMOND' => 25,
            'EXECUTIVE_DIRECTOR' => 28,
            'GENERAL_DIRECTOR' => 30,
        ],
    ];
}

function ensurePartnerStatusEventsTable(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS partner_status_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            event_type ENUM('upgrade', 'downgrade') DEFAULT 'upgrade',
            old_status_code VARCHAR(64) NULL,
            new_status_code VARCHAR(64) NOT NULL,
            bonus_percent DECIMAL(5,2) NULL,
            message TEXT NULL,
            event_key VARCHAR(128) NULL,
            is_shown TINYINT(1) NOT NULL DEFAULT 0,
            shown_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_event_key (event_key),
            INDEX idx_user_shown_created (user_id, is_shown, created_at),
            CONSTRAINT fk_partner_status_events_user
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function getUserMonthDv(PDO $db, int $userId): float
{
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(total), 0)
        FROM orders
        WHERE user_id = :uid
          AND status <> 'cancelled'
          AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
    ");
    $stmt->execute([':uid' => $userId]);
    $sumRub = (float)$stmt->fetchColumn();
    return $sumRub / 30;
}

function resolveUserRankCode(array $user, float $monthDv): string
{
    $isPartner = (($user['role'] ?? 'user') === 'partner') && !empty($user['core_partner_id']);
    if (!$isPartner) {
        return 'CLIENT';
    }
    if ($monthDv >= 200) {
        return 'BRONZE';
    }
    return 'PARTNER';
}

function resolveUserRankFromCore(array $user): ?string
{
    $isPartner = (($user['role'] ?? 'user') === 'partner') && !empty($user['core_partner_id']);
    if (!$isPartner) {
        return 'CLIENT';
    }

    $partnerId = (string)($user['core_partner_id'] ?? '');
    if ($partnerId === '') {
        return 'CLIENT';
    }

    $error = null;
    $res = coreGetJson('/partner-marketing-summary?partnerId=' . urlencode($partnerId), $error);
    if (!$res || ($res['status'] ?? 500) >= 400) {
        return null;
    }

    $rankCode = strtoupper((string)($res['data']['rankCode'] ?? 'PARTNER'));
    $cfg = partnerRankConfig();
    if (!isset($cfg['labels'][$rankCode])) {
        return 'PARTNER';
    }
    return $rankCode;
}

function getLatestPartnerStatusCode(PDO $db, int $userId): ?string
{
    $stmt = $db->prepare("
        SELECT new_status_code
        FROM partner_status_events
        WHERE user_id = :uid
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ");
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch();
    if (!$row || empty($row['new_status_code'])) {
        return null;
    }
    return strtoupper((string)$row['new_status_code']);
}

function createPartnerStatusEvent(PDO $db, int $userId, string $oldCode, string $newCode, ?string $message = null): void
{
    $cfg = partnerRankConfig();
    $index = array_flip($cfg['order']);
    $oldIdx = $index[$oldCode] ?? 0;
    $newIdx = $index[$newCode] ?? 0;
    $eventType = $newIdx >= $oldIdx ? 'upgrade' : 'downgrade';

    $eventKey = 'auto-' . $userId . '-' . $oldCode . '-' . $newCode . '-' . date('Ym');
    $insert = $db->prepare("
        INSERT INTO partner_status_events (
            user_id, event_type, old_status_code, new_status_code, bonus_percent, message, event_key, is_shown
        ) VALUES (
            :uid, :event_type, :old_code, :new_code, :bonus, :message, :event_key, 0
        )
        ON DUPLICATE KEY UPDATE
            event_type = VALUES(event_type),
            old_status_code = VALUES(old_status_code),
            new_status_code = VALUES(new_status_code),
            bonus_percent = VALUES(bonus_percent),
            message = VALUES(message),
            is_shown = 0,
            shown_at = NULL
    ");
    $insert->execute([
        ':uid' => $userId,
        ':event_type' => $eventType,
        ':old_code' => $oldCode,
        ':new_code' => $newCode,
        ':bonus' => $cfg['bonus'][$newCode] ?? null,
        ':message' => $message,
        ':event_key' => $eventKey,
    ]);
}

function syncUserRankEvent(PDO $db, array $user): string
{
    ensurePartnerStatusEventsTable($db);
    $cfg = partnerRankConfig();
    $uid = (int)($user['id'] ?? 0);
    $target = resolveUserRankFromCore($user);
    if ($target === null) {
        $monthDv = getUserMonthDv($db, $uid);
        $target = resolveUserRankCode($user, $monthDv);
    }
    if (!isset($cfg['labels'][$target])) {
        $target = 'CLIENT';
    }

    // Клиенту без активированного партнёрства не показываем
    // "понижение" и другие статусные модалки.
    if ($target === 'CLIENT') {
        $db->prepare("
            UPDATE partner_status_events
            SET is_shown = 1, shown_at = COALESCE(shown_at, NOW())
            WHERE user_id = :uid AND is_shown = 0
        ")->execute([':uid' => $uid]);
        return 'CLIENT';
    }

    $latest = getLatestPartnerStatusCode($db, $uid);
    if ($latest === null) {
        // Если события ещё не было ни разу, считаем стартовым состоянием CLIENT,
        // чтобы первое реальное получение партнёрского статуса показало модалку.
        $latest = 'CLIENT';
    }
    if (!isset($cfg['labels'][$latest])) {
        $latest = 'CLIENT';
    }

    if ($latest !== $target && !($latest === 'CLIENT' && $target === 'CLIENT')) {
        createPartnerStatusEvent($db, $uid, $latest, $target, null);
    }

    return $target;
}
