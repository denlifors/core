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

$legRoot = $treeRoot ? findChildBySide($treeRoot, $selectedLeg) : null;
$levels = buildLevels($legRoot, $treeDepth);
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
                        <div class="team__pyramidNode <?php echo $node ? '' : 'is-empty'; ?>">
                            <?php if ($node): ?>
                                <div class="dash__userBadge">
                                    <img class="dash__rankTop" src="<?php echo $assetsImg; ?>/rank_top.png" alt="" />
                                    <img class="dash__rankMid" src="<?php echo $assetsImg; ?>/rank_mid.png" alt="" />
                                    <img class="dash__avatar" src="<?php echo $assetsImg; ?>/avatar.jpg" alt="" />
                                    <img class="dash__rankLabel" src="<?php echo $assetsImg; ?>/rank_label.png" alt="" />
                                    <div class="dash__rankText"><?php echo htmlspecialchars($rankOrder[0]['label']); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>


