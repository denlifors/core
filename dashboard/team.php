<?php
$db = getDBConnection();
require_once dirname(__DIR__) . '/includes/partner-rank.php';
$isPartnerUser = (($userData['role'] ?? 'user') === 'partner') && !empty($userData['core_partner_id']);

$rankOrder = [
    ['code' => 'PARTNER', 'icon' => 'partner.svg', 'label' => 'Партнёр'],
    ['code' => 'BRONZE', 'icon' => 'bronza.svg', 'label' => 'Бронзовый лидер'],
    ['code' => 'SILVER', 'icon' => 'serebro.svg', 'label' => 'Серебряный лидер'],
    ['code' => 'PLATINUM', 'icon' => 'platina.svg', 'label' => 'Платиновый лидер'],
    ['code' => 'DIRECTOR', 'icon' => 'director.svg', 'label' => 'Директор'],
    ['code' => 'COMMERCIAL_DIRECTOR', 'icon' => 'komer-director.svg', 'label' => 'Коммерческий директор'],
    ['code' => 'GOLD', 'icon' => 'zoloto.svg', 'label' => 'Золотой лидер'],
    ['code' => 'DIAMOND', 'icon' => 'briliant.svg', 'label' => 'Бриллиантовый лидер'],
    ['code' => 'EXECUTIVE_DIRECTOR', 'icon' => 'ispol-director.svg', 'label' => 'Исполнительный директор'],
    ['code' => 'GENERAL_DIRECTOR', 'icon' => 'gen-director.svg', 'label' => 'Генеральный директор'],
];
$rankByCode = ['CLIENT' => ['code' => 'CLIENT', 'icon' => 'partner.svg', 'label' => 'Клиент']];
$rankIndexByCode = [];
foreach ($rankOrder as $idx => $rankItem) {
    $rankByCode[$rankItem['code']] = $rankItem;
    $rankIndexByCode[$rankItem['code']] = $idx + 1;
}
$rankIndexByCode['CLIENT'] = 0;

ensurePartnerStatusEventsTable($db);

$statusEvent = null;
if ($isPartnerUser) {
    $statusEventStmt = $db->prepare("
        SELECT id, event_type, old_status_code, new_status_code, bonus_percent, message, created_at
        FROM partner_status_events
        WHERE user_id = :uid AND is_shown = 0
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ");
    $statusEventStmt->execute([':uid' => (int)($_SESSION['user_id'] ?? 0)]);
    $statusEvent = $statusEventStmt->fetch() ?: null;
}

$fallbackRankCode = strtoupper((string)($currentUserRankCode ?? 'CLIENT'));
if (!isset($rankByCode[$fallbackRankCode])) {
    $fallbackRankCode = 'CLIENT';
}
$currentRankCode = strtoupper((string)($statusEvent['new_status_code'] ?? $fallbackRankCode));
if (!isset($rankByCode[$currentRankCode])) {
    $currentRankCode = 'CLIENT';
}

// Debug-переопределение для ручной проверки уровней (не влияет на события)
if (!empty($_GET['rank'])) {
    $debugRankCode = strtoupper((string)$_GET['rank']);
    if (isset($rankByCode[$debugRankCode])) {
        $currentRankCode = $debugRankCode;
    }
}

$currentRankIndex = -1;
foreach ($rankOrder as $idx => $rank) {
    if ($rank['code'] === $currentRankCode) {
        $currentRankIndex = $idx;
        break;
    }
}

$selectedLeg = strtoupper($_GET['leg'] ?? 'LEFT');
if (!in_array($selectedLeg, ['LEFT', 'RIGHT'], true)) {
    $selectedLeg = 'LEFT';
}

$treeDepth = 3;
$treeRoot = null;
if ($isPartnerUser) {
    $err = null;
    $treeRes = coreGetJson('/debug/tree?partnerId=' . urlencode($userData['core_partner_id']) . '&depth=' . $treeDepth, $err);
    if ($treeRes && ($treeRes['status'] ?? 500) < 400) {
        $treeRoot = $treeRes['data'] ?? null;
    }
}
$ensureDemoLeftLeg = function (&$root) use ($userData) {
    if (!$root) {
        $root = [
            'side' => 'ROOT',
            'partnerName' => ($userData['first_name'] ?? 'Тест') . ' ' . ($userData['last_name'] ?? 'Партнёр'),
            'registeredAt' => '01.02.2026',
            'children' => [],
        ];
    }
    if (empty($root['children'])) {
        $root['children'] = [];
    }
    $leftNode = [
        'side' => 'LEFT',
        'partnerName' => 'Иван Петров',
        'registeredAt' => '05.02.2026',
        'rankLabel' => 'Бронзовый лидер',
        'sponsorName' => ($root['partnerName'] ?? 'Вы'),
        'sponsorRegisteredAt' => ($root['registeredAt'] ?? '01.02.2026'),
        'children' => [
            [
                'side' => 'LEFT',
                'partnerName' => 'Андрей Ковалёв',
                'registeredAt' => '07.02.2026',
                'rankLabel' => 'Золотой лидер',
                'sponsorName' => 'Иван Петров',
                'sponsorRegisteredAt' => '05.02.2026',
                'children' => [],
            ],
            null,
        ],
    ];
    $rightNode = null;
    $root['children'][0] = $leftNode;
    $root['children'][1] = $rightNode;
};
if (!$treeRoot || empty($treeRoot['children'])) {
    $treeRoot = [
        'side' => 'ROOT',
        'partnerName' => ($userData['first_name'] ?? 'Пользователь') . ' ' . ($userData['last_name'] ?? ''),
        'registeredAt' => date('d.m.Y'),
        'rankLabel' => (string)($currentUserRankLabel ?? 'Клиент'),
        'children' => [],
    ];
}

$partnerProfileByCoreId = [];
$collectPartnerIds = function ($node) use (&$collectPartnerIds) {
    $ids = [];
    if (!is_array($node)) return $ids;
    $pid = (string)($node['partnerId'] ?? '');
    if ($pid !== '') $ids[] = $pid;
    foreach (($node['children'] ?? []) as $child) {
        if (is_array($child)) {
            $ids = array_merge($ids, $collectPartnerIds($child));
        }
    }
    return $ids;
};

$allTreePartnerIds = array_values(array_unique($collectPartnerIds($treeRoot)));
if (!empty($allTreePartnerIds)) {
    $ph = implode(',', array_fill(0, count($allTreePartnerIds), '?'));
    $uStmt = $db->prepare("
        SELECT id, first_name, last_name, email, role, core_partner_id, created_at
        FROM users
        WHERE core_partner_id IN ($ph)
    ");
    $uStmt->execute($allTreePartnerIds);
    foreach ($uStmt->fetchAll() as $u) {
        $coreId = (string)($u['core_partner_id'] ?? '');
        if ($coreId !== '') {
            $name = trim((string)($u['first_name'] ?? '') . ' ' . (string)($u['last_name'] ?? ''));
            if ($name === '') $name = (string)($u['email'] ?? 'Пользователь');
            $partnerProfileByCoreId[$coreId] = [
                'name' => $name,
                'registeredAt' => !empty($u['created_at']) ? date('d.m.Y', strtotime((string)$u['created_at'])) : '—',
            ];
        }
    }
}

$getProfileByPartnerId = function ($partnerId) use (&$partnerProfileByCoreId, $db) {
    $pid = trim((string)$partnerId);
    if ($pid === '') return null;
    if (isset($partnerProfileByCoreId[$pid])) return $partnerProfileByCoreId[$pid];

    $uStmt = $db->prepare("
        SELECT first_name, last_name, email, created_at
        FROM users
        WHERE core_partner_id = :pid
        LIMIT 1
    ");
    $uStmt->execute([':pid' => $pid]);
    $u = $uStmt->fetch();
    if (!$u) {
        // Fallback через core: получаем email владельца partnerId.
        $coreErr = null;
        $coreRes = coreGetJson('/partner-summary?partnerId=' . urlencode($pid), $coreErr);
        $coreEmail = (string)($coreRes['data']['user']['email'] ?? '');
        if ($coreEmail !== '') {
            $uByEmailStmt = $db->prepare("
                SELECT first_name, last_name, email, created_at
                FROM users
                WHERE email = :email
                LIMIT 1
            ");
            $uByEmailStmt->execute([':email' => $coreEmail]);
            $u = $uByEmailStmt->fetch();
            if (!$u) {
                $profile = [
                    'name' => $coreEmail,
                    'registeredAt' => '—',
                ];
                $partnerProfileByCoreId[$pid] = $profile;
                return $profile;
            }
        } else {
            return null;
        }
    }

    $name = trim((string)($u['first_name'] ?? '') . ' ' . (string)($u['last_name'] ?? ''));
    if ($name === '') $name = (string)($u['email'] ?? 'Пользователь');
    $profile = [
        'name' => $name,
        'registeredAt' => !empty($u['created_at']) ? date('d.m.Y', strtotime((string)$u['created_at'])) : '—',
    ];
    $partnerProfileByCoreId[$pid] = $profile;
    return $profile;
};

$enrichTree = function (&$node, $parentName = null, $parentDate = null) use (&$enrichTree, $getProfileByPartnerId) {
    if (!is_array($node)) return;

    $nodePartnerId = (string)($node['partnerId'] ?? '');
    $profile = $getProfileByPartnerId($nodePartnerId);

    if ($profile) {
        $node['partnerName'] = $profile['name'];
        $node['registeredAt'] = $profile['registeredAt'];
    } else {
        if (empty($node['partnerName'])) $node['partnerName'] = 'Пользователь';
        if (empty($node['registeredAt'])) $node['registeredAt'] = '—';
    }

    if ($parentName !== null) {
        $node['sponsorName'] = $parentName;
    } elseif (empty($node['sponsorName'])) {
        $node['sponsorName'] = 'Данные появятся позже';
    }

    if ($parentDate !== null) {
        $node['sponsorRegisteredAt'] = $parentDate;
    } elseif (empty($node['sponsorRegisteredAt'])) {
        $node['sponsorRegisteredAt'] = '—';
    }

    $curName = (string)$node['partnerName'];
    $curDate = (string)$node['registeredAt'];
    foreach (($node['children'] ?? []) as &$child) {
        if (is_array($child)) {
            $enrichTree($child, $curName, $curDate);
        }
    }
    unset($child);
};

$enrichTree($treeRoot, (string)($consultantName ?? ''), '—');

$statusEventModal = null;
if ($statusEvent) {
    $oldCode = strtoupper((string)($statusEvent['old_status_code'] ?? 'PARTNER'));
    $newCode = strtoupper((string)($statusEvent['new_status_code'] ?? 'PARTNER'));
    if (!isset($rankByCode[$oldCode])) {
        $oldCode = 'PARTNER';
    }
    if (!isset($rankByCode[$newCode])) {
        $newCode = 'PARTNER';
    }

    $oldIdx = $rankIndexByCode[$oldCode] ?? 0;
    $newIdx = $rankIndexByCode[$newCode] ?? 0;
    $kind = (string)($statusEvent['event_type'] ?? '');
    if ($kind !== 'upgrade' && $kind !== 'downgrade') {
        $kind = ($newIdx >= $oldIdx) ? 'upgrade' : 'downgrade';
    }

    $bonusPercent = $statusEvent['bonus_percent'] !== null ? (float)$statusEvent['bonus_percent'] : null;
    $defaultMessage = $kind === 'upgrade'
        ? ($bonusPercent !== null
            ? ('Вам доступны дополнительные бонусы по статусу с 1 уровня - ' . rtrim(rtrim(number_format($bonusPercent, 2, '.', ''), '0'), '.') . '%')
            : 'Вам доступны дополнительные бонусы по статусу')
        : 'Но не навсегда!';

    $statusEventModal = [
        'id' => (int)$statusEvent['id'],
        'kind' => $kind,
        'title' => $kind === 'upgrade' ? 'Поздравляем!' : 'Ваш статус понижен!',
        'subtitle' => $kind === 'upgrade' ? 'Вам присвоен статус' : 'Вы понижены до статуса:',
        'statusLabel' => $rankByCode[$newCode]['label'] ?? 'Партнёр',
        'statusCode' => $newCode,
        'statusIcon' => $rankByCode[$newCode]['icon'] ?? 'partner.svg',
        'message' => trim((string)($statusEvent['message'] ?? '')) !== '' ? (string)$statusEvent['message'] : $defaultMessage,
        'line1' => $kind === 'upgrade' ? 'Двигайтесь дальше!' : 'Не расстраивайтесь, все в ваших руках!',
        'line2' => $kind === 'upgrade' ? 'Удача и успех с Вами!' : 'Удача и успех с Вами!',
        'actionText' => $kind === 'upgrade' ? 'Отлично' : 'Хорошо',
    ];
}

function findChildBySide($node, $side) {
    if (!$node || empty($node['children'])) return null;
    foreach ($node['children'] as $child) {
        if (($child['side'] ?? null) === $side) return $child;
    }
    return null;
}

function buildLevels($root, $depth) {
    $levels = [];
    $current = [$root];
    for ($level = 0; $level < $depth; $level++) {
        $levels[] = $current;
        $next = [];
        foreach ($current as $node) {
            if ($node) {
                $left = findChildBySide($node, 'LEFT');
                $right = findChildBySide($node, 'RIGHT');
                $next[] = $left;
                $next[] = $right;
            } else {
                $next[] = null;
                $next[] = null;
            }
        }
        $current = $next;
    }
    return $levels;
}

$displayRoot = $treeRoot;
$levels = $isPartnerUser ? buildLevels($displayRoot, $treeDepth) : [];
$hasTeamDownline = false;
if ($isPartnerUser && is_array($treeRoot)) {
    foreach ((array)($treeRoot['children'] ?? []) as $child) {
        if (is_array($child) && !empty($child['partnerId'])) {
            $hasTeamDownline = true;
            break;
        }
    }
}
?>
<div class="team">
    <div class="team__levels">
        <div class="team__levelsCard"></div>
        <div class="team__levelsGrid">
            <?php foreach ($rankOrder as $idx => $rank): ?>
                <?php $isActive = $idx <= $currentRankIndex; ?>
                <div class="team__levelCard <?php echo $isActive ? 'is-active' : 'is-inactive'; ?>">
                    <img class="team__levelImage" src="<?php echo $assetsImg; ?>/icons/<?php echo $rank['icon']; ?>" alt="" />
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="team__directionRow">
        <div class="team__directionBox team__directionBox--label">
            Выбрать активное направление:
        </div>
        <a class="team__directionBox team__directionBox--left <?php echo $selectedLeg === 'LEFT' ? 'is-active' : ''; ?>" href="dashboard.php?section=team&leg=LEFT">
            <span class="team__directionCheck"><?php echo $selectedLeg === 'LEFT' ? '✓' : ''; ?></span>
            Левое направление
        </a>
        <a class="team__directionBox team__directionBox--right <?php echo $selectedLeg === 'RIGHT' ? 'is-active' : ''; ?>" href="dashboard.php?section=team&leg=RIGHT">
            <span class="team__directionCheck"><?php echo $selectedLeg === 'RIGHT' ? '✓' : ''; ?></span>
            Правое направление
        </a>
    </div>

    <div class="team__tree">
        <div class="team__treeCard"></div>
        <div class="team__pyramid">
            <?php foreach ($levels as $levelIndex => $nodes): ?>
                <div class="team__pyramidLevel team__pyramidLevel--<?php echo $levelIndex + 1; ?>">
                    <?php foreach ($nodes as $node): ?>
                        <?php
                            // В /debug/tree root может иметь side LEFT/RIGHT относительно аплайна спонсора.
                            // Корневой узел для текущего экрана определяем по первому уровню пирамиды.
                            $isRootNode = ($levelIndex === 0);
                            $nodePartnerId = trim((string)($node['partnerId'] ?? ''));
                            $refName = trim((string)($node['partnerName'] ?? ''));
                            $refDate = trim((string)($node['registeredAt'] ?? ''));

                            // Жёсткий fallback: тянем данные напрямую из users по core_partner_id.
                            if (!$isRootNode && $nodePartnerId !== '' && ($refName === '' || $refDate === '')) {
                                $nameStmt = $db->prepare("
                                    SELECT first_name, last_name, email, created_at
                                    FROM users
                                    WHERE core_partner_id = :pid
                                    LIMIT 1
                                ");
                                $nameStmt->execute([':pid' => $nodePartnerId]);
                                $nameRow = $nameStmt->fetch();
                                if ($nameRow) {
                                    if ($refName === '') {
                                        $tmpName = trim((string)($nameRow['first_name'] ?? '') . ' ' . (string)($nameRow['last_name'] ?? ''));
                                        $refName = $tmpName !== '' ? $tmpName : (string)($nameRow['email'] ?? '');
                                    }
                                    if ($refDate === '' && !empty($nameRow['created_at'])) {
                                        $refDate = date('d.m.Y', strtotime((string)$nameRow['created_at']));
                                    }
                                }
                            }

                            if ($refName === '') {
                                $refName = $isRootNode ? (string)($fullName ?? 'Пользователь') : 'Реферал';
                            }
                            if (!$isRootNode && mb_strtolower($refName, 'UTF-8') === mb_strtolower('Партнёр', 'UTF-8')) {
                                $refName = 'Реферал';
                            }

                            if ($refDate === '') {
                                $refDate = '—';
                            }

                            $modalConsultantName = trim((string)($node['sponsorName'] ?? ''));
                            if ($modalConsultantName === '') {
                                $modalConsultantName = $isRootNode
                                    ? (string)($consultantName ?? 'Данные появятся позже')
                                    : (string)($fullName ?? 'Данные появятся позже');
                            }
                            if (!$isRootNode && mb_strtolower($modalConsultantName, 'UTF-8') === mb_strtolower('Данные появятся позже', 'UTF-8')) {
                                $modalConsultantName = (string)($fullName ?? 'Данные появятся позже');
                            }

                            $modalConsultantDate = trim((string)($node['sponsorRegisteredAt'] ?? ''));
                            if ($modalConsultantDate === '') {
                                $modalConsultantDate = '—';
                            }

                            $rankLabel = trim((string)($node['rankLabel'] ?? ''));
                            if ($rankLabel === '') {
                                $rankLabel = $isRootNode
                                    ? (string)($currentUserRankLabel ?? $rankOrder[0]['label'])
                                    : $rankOrder[0]['label'];
                            }

                            $modalConsultantRank = trim((string)($node['sponsorRankLabel'] ?? ''));
                            if ($modalConsultantRank === '') {
                                $modalConsultantRank = $isRootNode
                                    ? (string)($consultantRankLabel ?? 'Партнёр')
                                    : (string)($consultantRankLabel ?? 'Партнёр');
                            }
                        ?>
                        <div class="team__pyramidNode <?php echo $node ? '' : 'is-empty'; ?>"
                             <?php if ($node): ?>
                                 data-ref-name="<?php echo htmlspecialchars($refName); ?>"
                                 data-ref-date="<?php echo htmlspecialchars($refDate); ?>"
                                 data-ref-rank="<?php echo htmlspecialchars($rankLabel); ?>"
                                data-consultant-name="<?php echo htmlspecialchars($modalConsultantName); ?>"
                                data-consultant-date="<?php echo htmlspecialchars($modalConsultantDate); ?>"
                                data-consultant-rank="<?php echo htmlspecialchars($modalConsultantRank); ?>"
                             <?php endif; ?>
                        >
                            <?php if ($node): ?>
                                <div class="dash__userBadge">
                                    <img class="dash__rankTop" src="<?php echo $assetsImg; ?>/rank_top.png" alt="" />
                                    <img class="dash__rankMid" src="<?php echo $assetsImg; ?>/rank_mid.png" alt="" />
                                    <img class="dash__avatar" src="<?php echo $assetsImg; ?>/avatar.jpg" alt="" />
                                    <img class="dash__rankLabel" src="<?php echo $assetsImg; ?>/rank_label.png" alt="" />
                                    <div class="dash__rankText"><?php echo htmlspecialchars($rankLabel); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($isPartnerUser && !$hasTeamDownline): ?>
<script>
  (function () {
    const startedAt = Date.now();
    const maxWaitMs = 120000;
    const pollMs = 5000;

    async function pollLiveState() {
      if (Date.now() - startedAt > maxWaitMs) return;
      try {
        const res = await fetch('api/dashboard-live-state.php', { cache: 'no-store' });
        if (!res.ok) throw new Error('Live state request failed');
        const data = await res.json();
        if (data && data.success && data.hasDownline) {
          window.location.reload();
          return;
        }
      } catch (_) {
        // ignore transient network errors
      }
      window.setTimeout(pollLiveState, pollMs);
    }

    window.setTimeout(pollLiveState, 2000);
  })();
</script>
<?php endif; ?>

<div class="ref-modal" id="ref-modal">
    <div class="ref-modal__card">
        <button class="ref-modal__close" type="button" aria-label="Закрыть">✕</button>

        <div class="ref-modal__user">
            <div class="ref-modal__badge">
                <img class="ref-modal__rankTop" src="<?php echo $assetsImg; ?>/rank_top.png" alt="" />
                <img class="ref-modal__rankMid" src="<?php echo $assetsImg; ?>/rank_mid.png" alt="" />
                <img class="ref-modal__avatar" src="<?php echo $assetsImg; ?>/avatar.jpg" alt="" />
                <img class="ref-modal__rankLabel" src="<?php echo $assetsImg; ?>/rank_label.png" alt="" />
                <div class="ref-modal__rankText" id="ref-modal-rank">Бронзовый лидер</div>
            </div>
            <div class="ref-modal__name" id="ref-modal-name">Иван Петров</div>
            <div class="ref-modal__date">
                <span>Дата регистрации:</span>
                <span id="ref-modal-date">12.01.2026</span>
            </div>
        </div>

        <div class="ref-modal__contacts ref-modal__contacts--top">
            <button class="ref-modal__icon ref-modal__icon--tg" type="button" aria-label="Telegram">
                <img src="<?php echo $assetsImg; ?>/icons/tg.svg" alt="" />
            </button>
            <button class="ref-modal__icon ref-modal__icon--vk" type="button" aria-label="VK">
                <img src="<?php echo $assetsImg; ?>/icons/vk.svg" alt="" />
            </button>
            <button class="ref-modal__icon ref-modal__icon--mail" type="button" aria-label="Email">
                <img src="<?php echo $assetsImg; ?>/icons/mail.svg" alt="" />
            </button>
            <button class="ref-modal__icon ref-modal__icon--phone" type="button" aria-label="Phone">
                <img src="<?php echo $assetsImg; ?>/icons/phone.svg" alt="" />
            </button>
        </div>

        <div class="ref-modal__consultant">
            <div class="ref-modal__consultantTitle">Ваш консультант</div>
            <div class="ref-modal__consultantBody">
                <div class="ref-modal__consultantBadge">
                    <img class="ref-modal__rankTop" src="<?php echo $assetsImg; ?>/rank_top.png" alt="" />
                    <img class="ref-modal__rankMid" src="<?php echo $assetsImg; ?>/rank_mid.png" alt="" />
                    <img class="ref-modal__avatar" src="<?php echo $assetsImg; ?>/avatar.jpg" alt="" />
                    <img class="ref-modal__rankLabel" src="<?php echo $assetsImg; ?>/rank_label.png" alt="" />
                    <div class="ref-modal__rankText" id="ref-modal-consultant-rank">Золотой лидер</div>
                </div>
                <div class="ref-modal__consultantInfo">
                    <div class="ref-modal__consultantName" id="ref-modal-consultant-name">Алексей Сидоров</div>
                    <div class="ref-modal__consultantDate">
                        <span>Дата регистрации:</span>
                        <span id="ref-modal-consultant-date">16.09.2024</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="ref-modal__contacts ref-modal__contacts--bottom">
            <button class="ref-modal__icon ref-modal__icon--tg" type="button" aria-label="Telegram">
                <img src="<?php echo $assetsImg; ?>/icons/tg.svg" alt="" />
            </button>
            <button class="ref-modal__icon ref-modal__icon--vk" type="button" aria-label="VK">
                <img src="<?php echo $assetsImg; ?>/icons/vk.svg" alt="" />
            </button>
            <button class="ref-modal__icon ref-modal__icon--mail" type="button" aria-label="Email">
                <img src="<?php echo $assetsImg; ?>/icons/mail.svg" alt="" />
            </button>
            <button class="ref-modal__icon ref-modal__icon--phone" type="button" aria-label="Phone">
                <img src="<?php echo $assetsImg; ?>/icons/phone.svg" alt="" />
            </button>
        </div>
    </div>
</div>

<?php if ($statusEventModal): ?>
    <?php
        $eventIsUpgrade = $statusEventModal['kind'] === 'upgrade';
        $eventStatusClass = 'team-status-modal__status--' . strtolower((string)$statusEventModal['statusCode']);
    ?>
    <div class="team-status-backdrop is-open" id="team-status-backdrop"></div>

    <div
        class="team-status-modal team-status-modal--upgrade <?php echo $eventIsUpgrade ? 'is-open' : ''; ?>"
        id="team-status-modal-upgrade"
        data-event-id="<?php echo (int)$statusEventModal['id']; ?>"
    >
        <button class="team-status-modal__close" type="button" aria-label="Закрыть">✕</button>
        <div class="team-status-modal__hero"></div>
        <div class="team-status-modal__emblem">
            <img class="team-status-modal__emblemImg" src="<?php echo $assetsImg; ?>/icons/<?php echo htmlspecialchars($statusEventModal['statusIcon']); ?>" alt="" />
        </div>
        <div class="team-status-modal__content">
            <h3 class="team-status-modal__title"><?php echo htmlspecialchars($statusEventModal['title']); ?></h3>
            <div class="team-status-modal__subtitle"><?php echo htmlspecialchars($statusEventModal['subtitle']); ?></div>
            <div class="team-status-modal__status <?php echo htmlspecialchars($eventStatusClass); ?>">
                <?php echo htmlspecialchars($statusEventModal['statusLabel']); ?>
            </div>
            <div class="team-status-modal__message"><?php echo htmlspecialchars($statusEventModal['message']); ?></div>
            <div class="team-status-modal__line team-status-modal__line--1"><?php echo htmlspecialchars($statusEventModal['line1']); ?></div>
            <div class="team-status-modal__line team-status-modal__line--2"><?php echo htmlspecialchars($statusEventModal['line2']); ?></div>
            <button class="team-status-modal__action" type="button"><?php echo htmlspecialchars($statusEventModal['actionText']); ?></button>
        </div>
    </div>

    <div
        class="team-status-modal team-status-modal--downgrade <?php echo !$eventIsUpgrade ? 'is-open' : ''; ?>"
        id="team-status-modal-downgrade"
        data-event-id="<?php echo (int)$statusEventModal['id']; ?>"
    >
        <button class="team-status-modal__close" type="button" aria-label="Закрыть">✕</button>
        <div class="team-status-modal__hero"></div>
        <div class="team-status-modal__emblem">
            <img class="team-status-modal__emblemImg" src="<?php echo $assetsImg; ?>/icons/<?php echo htmlspecialchars($statusEventModal['statusIcon']); ?>" alt="" />
        </div>
        <div class="team-status-modal__content">
            <h3 class="team-status-modal__title"><?php echo htmlspecialchars($statusEventModal['title']); ?></h3>
            <div class="team-status-modal__subtitle"><?php echo htmlspecialchars($statusEventModal['subtitle']); ?></div>
            <div class="team-status-modal__status <?php echo htmlspecialchars($eventStatusClass); ?>">
                <?php echo htmlspecialchars($statusEventModal['statusLabel']); ?>
            </div>
            <div class="team-status-modal__message"><?php echo htmlspecialchars($statusEventModal['message']); ?></div>
            <div class="team-status-modal__line team-status-modal__line--1"><?php echo htmlspecialchars($statusEventModal['line1']); ?></div>
            <div class="team-status-modal__line team-status-modal__line--2"><?php echo htmlspecialchars($statusEventModal['line2']); ?></div>
            <button class="team-status-modal__action" type="button"><?php echo htmlspecialchars($statusEventModal['actionText']); ?></button>
        </div>
    </div>
<?php endif; ?>

<script>
  (function() {
    const modal = document.getElementById('ref-modal');
    const closeBtn = modal?.querySelector('.ref-modal__close');
    const cardEl = modal?.querySelector('.ref-modal__card');
    const nameEl = document.getElementById('ref-modal-name');
    const dateEl = document.getElementById('ref-modal-date');
    const rankEl = document.getElementById('ref-modal-rank');
    const consNameEl = document.getElementById('ref-modal-consultant-name');
    const consDateEl = document.getElementById('ref-modal-consultant-date');
    const consRankEl = document.getElementById('ref-modal-consultant-rank');

    function setText(el, value) {
      if (el) el.textContent = value;
    }

    function openModal(node) {
      if (!modal || !node) return;
      setText(nameEl, node.getAttribute('data-ref-name') || 'Иван Петров');
      setText(dateEl, node.getAttribute('data-ref-date') || '12.01.2026');
      setText(rankEl, node.getAttribute('data-ref-rank') || 'Партнёр');
      setText(consNameEl, node.getAttribute('data-consultant-name') || 'Алексей Сидоров');
      setText(consDateEl, node.getAttribute('data-consultant-date') || '16.09.2024');
      setText(consRankEl, node.getAttribute('data-consultant-rank') || 'Партнёр');
      modal.classList.add('is-open');
    }

    function closeModal() {
      modal?.classList.remove('is-open');
    }

    document.querySelectorAll('.team__pyramidNode[data-ref-name]').forEach((node) => {
      node.addEventListener('click', () => openModal(node));
    });

    closeBtn?.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeModal();
    });
    cardEl?.addEventListener('click', (e) => e.stopPropagation());
    modal?.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });

    const statusBackdrop = document.getElementById('team-status-backdrop');
    const statusModalUpgrade = document.getElementById('team-status-modal-upgrade');
    const statusModalDowngrade = document.getElementById('team-status-modal-downgrade');
    const openedStatusModal = statusModalUpgrade?.classList.contains('is-open')
      ? statusModalUpgrade
      : (statusModalDowngrade?.classList.contains('is-open') ? statusModalDowngrade : null);

    async function markStatusEventSeen(modalEl) {
      if (!modalEl) return;
      const eventId = parseInt(modalEl.getAttribute('data-event-id') || '0', 10);
      if (!eventId) return;
      try {
        await fetch('api/partner-status-event-seen.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ event_id: eventId })
        });
      } catch (_) {}
    }

    async function closeStatusModal() {
      if (!openedStatusModal) return;
      openedStatusModal.classList.remove('is-open');
      statusBackdrop?.classList.remove('is-open');
      await markStatusEventSeen(openedStatusModal);
    }

    openedStatusModal?.querySelectorAll('.team-status-modal__close, .team-status-modal__action').forEach((btn) => {
      btn.addEventListener('click', closeStatusModal);
    });

    statusBackdrop?.addEventListener('click', closeStatusModal);
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      closeModal();
      closeStatusModal();
    });
  })();
</script>


