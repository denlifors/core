<?php
$bonusTypeMap = [
    'all' => ['label' => 'Все', 'core' => 'all'],
    '3' => ['label' => 'Бонус "3 круга влияния"', 'core' => 'INFLUENCE'],
    'balance' => ['label' => 'Бонус "Баланс"', 'core' => 'BALANCE_WEEKLY'],
    'growth' => ['label' => 'Бонус "Роста"', 'core' => 'GROWTH_BONUS'],
    'global' => ['label' => 'Бонус "Глобальный"', 'core' => 'GLOBAL_BONUS'],
    'rep' => ['label' => 'Бонус "Представительский"', 'core' => 'REPRESENTATIVE_BONUS'],
    'cashback' => ['label' => 'Партнёрский кэшбэк', 'core' => 'PARTNER_CASHBACK'],
];

$fromRaw = trim((string)($_GET['from'] ?? ''));
$toRaw = trim((string)($_GET['to'] ?? ''));
$searchRaw = trim((string)($_GET['q'] ?? ''));
$selectedType = (string)($_GET['bonus_type'] ?? 'all');
$selectedType = isset($bonusTypeMap[$selectedType]) ? $selectedType : 'all';
$page = max(1, (int)($_GET['hpage'] ?? 1));
$perPage = 50;

$parseRuDate = static function (string $value): ?string {
    if ($value === '') return null;
    $dt = DateTime::createFromFormat('d.m.Y', $value);
    if (!$dt || $dt->format('d.m.Y') !== $value) return null;
    return $dt->format('Y-m-d');
};

$fromDateIso = $parseRuDate($fromRaw);
$toDateIso = $parseRuDate($toRaw);

$historyItems = [];
$totalRows = 0;
$currentRankLabel = $currentUserRankLabel ?? 'Партнёр';

if (!empty($userData['core_partner_id'])) {
    $qs = [
        'partnerId' => (string)$userData['core_partner_id'],
        'page' => $page,
        'perPage' => $perPage,
    ];
    $coreType = $bonusTypeMap[$selectedType]['core'] ?? 'all';
    if ($coreType !== 'all' && $coreType !== 'INFLUENCE') {
        $qs['type'] = $coreType;
    }
    if ($fromDateIso) $qs['from'] = $fromDateIso;
    if ($toDateIso) $qs['to'] = $toDateIso;

    $err = null;
    $historyRes = coreGetJson('/partner-bonus-history?' . http_build_query($qs), $err);
    if ($historyRes && ($historyRes['status'] ?? 500) < 400) {
        $items = $historyRes['data']['items'] ?? [];

        if ($coreType === 'INFLUENCE') {
            $items = array_values(array_filter($items, static function ($row) {
                return strpos((string)($row['type'] ?? ''), 'INFLUENCE_') === 0;
            }));
        }

        if ($searchRaw !== '') {
            $needle = mb_strtolower($searchRaw, 'UTF-8');
            $items = array_values(array_filter($items, static function ($row) use ($needle) {
                $txt = mb_strtolower((string)($row['note'] ?? ''), 'UTF-8') . ' ' . mb_strtolower((string)($row['type'] ?? ''), 'UTF-8');
                return mb_strpos($txt, $needle) !== false;
            }));
        }

        $historyItems = $items;
        $totalRows = (int)($historyRes['data']['total'] ?? count($historyItems));
    }
}

$eventText = static function (array $row): string {
    $type = (string)($row['type'] ?? '');
    $note = trim((string)($row['note'] ?? ''));
    if (strpos($type, 'INFLUENCE_L') === 0) {
        $lvl = (int)str_replace('INFLUENCE_L', '', $type);
        return 'Получен бонус "3 круга влияния" с ' . max(1, $lvl) . ' круга';
    }
    if ($type === 'BALANCE_WEEKLY') return 'Начислен бонус "Баланс"';
    if ($type === 'GROWTH_BONUS') return 'Начислен бонус "Роста"';
    if ($type === 'GLOBAL_BONUS') return 'Начислен "Глобальный" бонус';
    if ($type === 'REPRESENTATIVE_BONUS') return 'Начислен "Представительский" бонус';
    if ($type === 'PARTNER_CASHBACK') return 'Начислен партнёрский кэшбэк';
    return $note !== '' ? $note : $type;
};

$rowTypeCategory = static function (array $row): string {
    $type = (string)($row['type'] ?? '');
    if (strpos($type, 'INFLUENCE_L') === 0) return '3';
    if ($type === 'BALANCE_WEEKLY') return 'balance';
    if ($type === 'GROWTH_BONUS') return 'growth';
    if ($type === 'GLOBAL_BONUS') return 'global';
    if ($type === 'REPRESENTATIVE_BONUS') return 'rep';
    if ($type === 'PARTNER_CASHBACK') return 'cashback';
    return 'all';
};
?>

<div class="history">
    <form class="history__filterCard" method="get" action="dashboard.php" style="background: rgba(255,255,255,0.08) !important; backdrop-filter: blur(20px);">
        <input type="hidden" name="section" value="history" />
        <input type="hidden" name="bonus_type" id="history-bonus-type" value="<?php echo htmlspecialchars($selectedType); ?>" />

        <div class="history__filterRow history__filterRow--top">
            <label class="history__filterLabel" for="history-from">Дата с:</label>
            <input class="history__filterInput" id="history-from" name="from" type="text" value="<?php echo htmlspecialchars($fromRaw); ?>" placeholder="__.__.____" />

            <label class="history__filterLabel" for="history-to">Дата по:</label>
            <input class="history__filterInput" id="history-to" name="to" type="text" value="<?php echo htmlspecialchars($toRaw); ?>" placeholder="__.__.____" />

            <div class="history__filterSelectWrap">
                <div class="history__filterSelect" id="history-bonus-select">
                    <span id="history-bonus-selected"><?php echo htmlspecialchars($bonusTypeMap[$selectedType]['label']); ?></span>
                    <span class="history__chevron">▼</span>
                </div>
                <div class="history__bonusDropdown" id="history-bonus-dropdown">
                    <div class="history__bonusList">
                        <?php foreach ($bonusTypeMap as $key => $cfg): ?>
                            <div class="history__bonusItem" data-type="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($cfg['label']); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button class="history__filterBtn" type="submit">Показать</button>
        </div>

        <div class="history__filterRow history__filterRow--search">
            <div class="history__searchIcon">🔍</div>
            <input class="history__searchInput" type="text" name="q" value="<?php echo htmlspecialchars($searchRaw); ?>" placeholder="Поиск по бонусу / примечанию" />
        </div>
    </form>

    <div class="history__listCard" style="background: rgba(255,255,255,0.08) !important; backdrop-filter: blur(20px);">
        <div class="history__listHeader">
            <div class="history__col history__col--event">Событие</div>
            <div class="history__col history__col--status">Статус</div>
            <div class="history__col history__col--amount">Сумма DV</div>
            <div class="history__col history__col--date">Дата</div>
        </div>

        <div class="history__list">
            <?php if (empty($historyItems)): ?>
                <div class="history__row">
                    <div class="history__col history__col--event">Пока нет начислений бонусов</div>
                    <div class="history__col history__col--status history__status history__status--partner"><?php echo htmlspecialchars($currentRankLabel); ?></div>
                    <div class="history__col history__col--amount">0.00 DV</div>
                    <div class="history__col history__col--date">—</div>
                </div>
            <?php else: ?>
                <?php foreach ($historyItems as $row): ?>
                    <?php
                        $cat = $rowTypeCategory($row);
                        $amountDv = (float)($row['amountDv'] ?? 0);
                        $createdAt = !empty($row['createdAt']) ? date('d.m.Y', strtotime((string)$row['createdAt'])) : '—';
                    ?>
                    <div class="history__row" data-type="<?php echo htmlspecialchars($cat); ?>">
                        <div class="history__col history__col--event"><?php echo htmlspecialchars($eventText($row)); ?></div>
                        <div class="history__col history__col--status history__status history__status--partner"><?php echo htmlspecialchars($currentRankLabel); ?></div>
                        <div class="history__col history__col--amount"><?php echo number_format($amountDv, 2, '.', ' '); ?> DV</div>
                        <div class="history__col history__col--date"><?php echo htmlspecialchars($createdAt); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="history__footer">
            <?php
                $fromRecord = $totalRows > 0 ? (($page - 1) * $perPage + 1) : 0;
                $toRecord = min($page * $perPage, max($totalRows, count($historyItems)));
                $totalPages = max(1, (int)ceil(max($totalRows, count($historyItems)) / $perPage));
                $prevPage = max(1, $page - 1);
                $nextPage = min($totalPages, $page + 1);
                $baseQ = [
                    'section' => 'history',
                    'from' => $fromRaw,
                    'to' => $toRaw,
                    'q' => $searchRaw,
                    'bonus_type' => $selectedType,
                ];
            ?>
            <div class="history__range">Записи с <?php echo $fromRecord; ?> по <?php echo $toRecord; ?> из <?php echo max($totalRows, count($historyItems)); ?></div>
            <div class="history__pager">
                <a class="history__pageBtn" href="dashboard.php?<?php echo http_build_query($baseQ + ['hpage' => $prevPage]); ?>">‹</a>
                <span class="history__page"><?php echo $page; ?></span>
                <a class="history__pageBtn" href="dashboard.php?<?php echo http_build_query($baseQ + ['hpage' => $nextPage]); ?>">›</a>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($userData['core_partner_id']) && empty($historyItems)): ?>
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
        if (data && data.success && Number(data.bonusTotal || 0) > 0) {
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

<script>
  (function() {
    const select = document.getElementById('history-bonus-select');
    const selectedLabel = document.getElementById('history-bonus-selected');
    const dropdown = document.getElementById('history-bonus-dropdown');
    const hiddenType = document.getElementById('history-bonus-type');

    function closeDropdown() {
      dropdown?.classList.remove('is-open');
      select?.classList.remove('is-open');
    }

    select?.addEventListener('click', (e) => {
      e.preventDefault();
      if (!dropdown) return;
      const isOpen = dropdown.classList.toggle('is-open');
      select?.classList.toggle('is-open', isOpen);
    });

    dropdown?.addEventListener('click', (e) => {
      const item = e.target.closest('.history__bonusItem');
      if (!item) return;
      const type = item.getAttribute('data-type') || 'all';
      const label = item.textContent?.trim() || 'Тип бонуса';
      if (selectedLabel) selectedLabel.textContent = label;
      if (hiddenType) hiddenType.value = type;
      closeDropdown();
    });

    document.addEventListener('click', (e) => {
      if (!dropdown || !select) return;
      if (dropdown.contains(e.target) || select.contains(e.target)) return;
      closeDropdown();
    });
  })();
</script>


