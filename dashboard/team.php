<?php
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

// Временная логика: можно задавать ?rank=PLATINUM для теста
$currentRankCode = $_GET['rank'] ?? 'PARTNER';
$currentRankIndex = 0;
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
if (!empty($userData['core_partner_id'])) {
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
    $ensureDemoLeftLeg($treeRoot);
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
$levels = buildLevels($displayRoot, $treeDepth);
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
                            $refName = $node['partnerName'] ?? 'Иван Петров';
                            $refDate = $node['registeredAt'] ?? '12.01.2026';
                            $consultantName = $node['sponsorName'] ?? 'Алексей Сидоров';
                            $consultantDate = $node['sponsorRegisteredAt'] ?? '16.09.2024';
                            $rankLabel = $node['rankLabel'] ?? $rankOrder[0]['label'];
                        ?>
                        <div class="team__pyramidNode <?php echo $node ? '' : 'is-empty'; ?>"
                             <?php if ($node): ?>
                                 data-ref-name="<?php echo htmlspecialchars($refName); ?>"
                                 data-ref-date="<?php echo htmlspecialchars($refDate); ?>"
                                 data-consultant-name="<?php echo htmlspecialchars($consultantName); ?>"
                                 data-consultant-date="<?php echo htmlspecialchars($consultantDate); ?>"
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

<div class="ref-modal" id="ref-modal">
    <div class="ref-modal__card">
        <button class="ref-modal__close" type="button" aria-label="Закрыть">✕</button>

        <div class="ref-modal__user">
            <div class="ref-modal__badge">
                <img class="ref-modal__rankTop" src="<?php echo $assetsImg; ?>/rank_top.png" alt="" />
                <img class="ref-modal__rankMid" src="<?php echo $assetsImg; ?>/rank_mid.png" alt="" />
                <img class="ref-modal__avatar" src="<?php echo $assetsImg; ?>/avatar.jpg" alt="" />
                <img class="ref-modal__rankLabel" src="<?php echo $assetsImg; ?>/rank_label.png" alt="" />
                <div class="ref-modal__rankText">Бронзовый лидер</div>
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
                    <div class="ref-modal__rankText">Золотой лидер</div>
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

<script>
  (function() {
    const modal = document.getElementById('ref-modal');
    const closeBtn = modal?.querySelector('.ref-modal__close');
    const nameEl = document.getElementById('ref-modal-name');
    const dateEl = document.getElementById('ref-modal-date');
    const consNameEl = document.getElementById('ref-modal-consultant-name');
    const consDateEl = document.getElementById('ref-modal-consultant-date');

    function openModal(node) {
      if (!modal || !node) return;
      nameEl.textContent = node.getAttribute('data-ref-name') || 'Иван Петров';
      dateEl.textContent = node.getAttribute('data-ref-date') || '12.01.2026';
      consNameEl.textContent = node.getAttribute('data-consultant-name') || 'Алексей Сидоров';
      consDateEl.textContent = node.getAttribute('data-consultant-date') || '16.09.2024';
      modal.classList.add('is-open');
    }

    function closeModal() {
      modal?.classList.remove('is-open');
    }

    document.querySelectorAll('.team__pyramidNode[data-ref-name]').forEach((node) => {
      node.addEventListener('click', () => openModal(node));
    });

    closeBtn?.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeModal();
    });
  })();
</script>


