<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$db = getDBConnection();
$partnerId = (string)($userData['core_partner_id'] ?? '');
$itemsPerPage = 6; // 2 строки по 3 карточки
$page = max(1, (int)($_GET['ppage'] ?? 1));
$pSearch = trim((string)($_GET['psearch'] ?? ''));
$partnerOnly = (string)($_GET['partner_only'] ?? '1') === '1';
$showMode = (string)($_GET['show'] ?? 'all'); // all | 9
$levelFilter = (string)($_GET['level'] ?? 'all'); // all | number
$allLevels = (string)($_GET['all_levels'] ?? '1') === '1';
$sortByDate = (string)($_GET['sort_date'] ?? '0') === '1';

$teamUsers = [];
$directInvites = 0;
$teamTotal = 0;

if ($partnerId !== '') {
    $treeErr = null;
    $treeRes = coreGetJson('/debug/tree?partnerId=' . urlencode($partnerId) . '&depth=8', $treeErr);
    $tree = ($treeRes && ($treeRes['status'] ?? 500) < 400) ? ($treeRes['data'] ?? null) : null;

    $flatten = static function ($node, $depth = 0) use (&$flatten, &$teamUsers, &$teamTotal, &$directInvites) {
        if (!$node || empty($node['partnerId'])) {
            return;
        }
        $pid = (string)$node['partnerId'];
        $side = (string)($node['side'] ?? 'ROOT');

        $teamTotal++;
        if ($depth === 1 && in_array($side, ['LEFT', 'RIGHT'], true)) {
            $directInvites++;
        }

        $teamUsers[] = [
            'partner_id' => $pid,
            'depth' => $depth,
            'side' => $side,
            'left_volume' => (int)($node['leftVolume'] ?? 0),
            'right_volume' => (int)($node['rightVolume'] ?? 0),
        ];

        foreach ((array)($node['children'] ?? []) as $child) {
            $flatten($child, $depth + 1);
        }
    };
    $flatten($tree, 0);

    $partnerIds = array_values(array_unique(array_map(static function ($r) {
        return $r['partner_id'];
    }, $teamUsers)));

    $userByPartnerId = [];
    if (!empty($partnerIds)) {
        $ph = implode(',', array_fill(0, count($partnerIds), '?'));
        $uStmt = $db->prepare("SELECT id, first_name, last_name, email, role, created_at, core_partner_id FROM users WHERE core_partner_id IN ($ph)");
        $uStmt->execute($partnerIds);
        foreach ($uStmt->fetchAll() as $u) {
            $userByPartnerId[(string)$u['core_partner_id']] = $u;
        }
    }

    $teamUsers = array_values(array_filter($teamUsers, static function ($row) use ($partnerId) {
        return $row['partner_id'] !== $partnerId; // в карточках показываем команду без себя
    }));

    $teamUsers = array_map(static function ($row) use ($userByPartnerId) {
        $u = $userByPartnerId[$row['partner_id']] ?? null;
        $fullName = trim((string)($u['first_name'] ?? '') . ' ' . (string)($u['last_name'] ?? ''));
        if ($fullName === '') {
            $fullName = !empty($u['email']) ? (string)$u['email'] : ('ID: ' . substr($row['partner_id'], 0, 8));
        }
        $row['name'] = $fullName;
        $row['local_id'] = (int)($u['id'] ?? 0);
        // Узел в бинарном дереве core всегда является партнёром.
        $row['role'] = (string)($u['role'] ?? 'partner');
        $row['registered_at'] = (string)($u['created_at'] ?? '');
        $row['registered_ts'] = !empty($u['created_at']) ? strtotime((string)$u['created_at']) : 0;
        return $row;
    }, $teamUsers);
}

$filteredUsers = $teamUsers;
if ($pSearch !== '') {
    $needle = mb_strtolower($pSearch, 'UTF-8');
    $filteredUsers = array_values(array_filter($filteredUsers, static function ($row) use ($needle) {
        $hay = mb_strtolower(
            ((string)($row['name'] ?? '')) . ' ' .
            ((string)($row['partner_id'] ?? '')) . ' ' .
            ((string)($row['local_id'] ?? '')),
            'UTF-8'
        );
        return mb_strpos($hay, $needle) !== false;
    }));
}
if ($partnerOnly) {
    $filteredUsers = array_values(array_filter($filteredUsers, static function ($row) {
        return ((string)($row['role'] ?? 'user')) === 'partner';
    }));
}
if ($showMode === '9') {
    $filteredUsers = array_values(array_filter($filteredUsers, static function ($row) {
        return (int)($row['depth'] ?? 0) <= 9;
    }));
}
if (!$allLevels && $levelFilter !== 'all') {
    $needLevel = max(1, (int)$levelFilter);
    $filteredUsers = array_values(array_filter($filteredUsers, static function ($row) use ($needLevel) {
        return (int)($row['depth'] ?? 0) === $needLevel;
    }));
}
if ($sortByDate) {
    usort($filteredUsers, static function ($a, $b) {
        return (int)($b['registered_ts'] ?? 0) <=> (int)($a['registered_ts'] ?? 0);
    });
} else {
    usort($filteredUsers, static function ($a, $b) {
        $d = (int)($a['depth'] ?? 0) <=> (int)($b['depth'] ?? 0);
        if ($d !== 0) return $d;
        return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
    });
}

$cardsTotal = count($filteredUsers);
$pagesTotal = max(1, (int)ceil($cardsTotal / $itemsPerPage));
$page = min($page, $pagesTotal);
$offset = ($page - 1) * $itemsPerPage;
$cards = array_slice($filteredUsers, $offset, $itemsPerPage);

$buildInitials = static function (string $fullName): string {
    $parts = preg_split('/\s+/u', trim($fullName)) ?: [];
    $letters = [];
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        if (function_exists('mb_substr')) {
            $letters[] = mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
        } else {
            $letters[] = strtoupper(substr($part, 0, 1));
        }
        if (count($letters) >= 2) {
            break;
        }
    }
    return !empty($letters) ? implode('', $letters) : 'U';
};
?>

<section class="partnersx">
    <div class="partnersx__stats">
        <article class="partnersx__statCard partnersx__statCard--team">
            <div class="partnersx__statLabel">Регистраций в команде</div>
            <div class="partnersx__statValue"><?php echo max(0, (int)$teamTotal - 1); ?></div>
        </article>
        <article class="partnersx__statCard partnersx__statCard--direct">
            <div class="partnersx__statLabel">Лично приглашенные</div>
            <div class="partnersx__statValue"><?php echo (int)$directInvites; ?></div>
        </article>
    </div>

    <form class="partnersx__tools" method="get" action="dashboard.php">
        <input type="hidden" name="section" value="partners" />
        <div class="partnersx__toolsLine">
            <div class="partnersx__searchTitleTop">Поиск и фильтр партнёров</div>
            <div class="partnersx__searchWrap">
                <button class="partnersx__searchIconBtn" type="submit" aria-label="Искать">🔍</button>
                <input class="partnersx__searchInput" type="text" name="psearch" value="<?php echo htmlspecialchars($pSearch); ?>" placeholder="Поиск партнёров..." />
            </div>

            <button class="partnersx__showBtn partnersx__showBtn--inline" type="submit">Показать</button>
        </div>

        <div class="partnersx__onlyWrap">
            <select class="partnersx__select partnersx__select--only" name="partner_only">
                <option value="1" <?php echo $partnerOnly ? 'selected' : ''; ?>>Только партнёры</option>
                <option value="0" <?php echo !$partnerOnly ? 'selected' : ''; ?>>Все участники</option>
            </select>
        </div>

        <div class="partnersx__levelWrapRight">
            <select class="partnersx__select partnersx__select--levelRight" name="level">
                <?php for ($i = 1; $i <= 9; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ((string)$i === $levelFilter) ? 'selected' : ''; ?>>Уровень - <?php echo $i; ?></option>
                <?php endfor; ?>
                <option value="all" <?php echo $levelFilter === 'all' ? 'selected' : ''; ?>>Все уровни</option>
            </select>
        </div>

        <div class="partnersx__subline">
            <div class="partnersx__resultsTitle">Результаты поиска</div>
            <button class="partnersx__chip partnersx__chip--all" type="submit" name="show" value="all">Показать всех</button>
            <button class="partnersx__chip partnersx__chip--nine" type="submit" name="show" value="9">9 - Уровней</button>

            <div class="partnersx__sortTitle">Сортировка команды</div>
            <div class="partnersx__levelsTitle">Уровни</div>
            <label class="partnersx__switchLabel partnersx__switchLabel--all">
                <input type="hidden" name="all_levels" value="0" />
                <input type="checkbox" name="all_levels" value="1" <?php echo $allLevels ? 'checked' : ''; ?> />
                <span class="partnersx__switch <?php echo $allLevels ? 'is-on' : ''; ?>"></span>
                <span class="partnersx__switchText">Все уровни</span>
            </label>

            <label class="partnersx__switchLabel partnersx__switchLabel--date">
                <input type="hidden" name="sort_date" value="0" />
                <input type="checkbox" name="sort_date" value="1" <?php echo $sortByDate ? 'checked' : ''; ?> />
                <span class="partnersx__switch <?php echo $sortByDate ? 'is-on' : ''; ?>"></span>
                <span class="partnersx__switchText">По дате регистрации</span>
            </label>
        </div>
    </form>

    <div class="partnersx__listCard">
        <div class="partnersx__grid">
            <?php foreach ($cards as $card): ?>
                <?php
                    $name = (string)$card['name'];
                    $shortId = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', (string)$card['partner_id']), 0, 10));
                    $idFormatted = strlen($shortId) > 3 ? (substr($shortId, 0, 3) . '-' . substr($shortId, 3)) : $shortId;
                    $volume = (int)$card['left_volume'] + (int)$card['right_volume'];
                    $level = max(1, (int)$card['depth']);
                    $statusLabel = ((string)($card['role'] ?? 'user')) === 'partner' ? 'Партнёр' : 'Клиент';
                    $statusClass = ((string)($card['role'] ?? 'user')) === 'partner' ? 'is-partner' : 'is-client';
                    $initials = $buildInitials($name);
                ?>
                <article class="partnersx__card">
                    <div class="partnersx__left">
                        <div class="partnersx__volBadge">
                            <span class="partnersx__volIcon">◫</span>
                            <span><?php echo $volume; ?></span>
                        </div>
                        <div class="partnersx__avatarWrap">
                            <div class="partnersx__avatar"><?php echo htmlspecialchars($initials); ?></div>
                            <div class="partnersx__rankMini"><?php echo $statusLabel === 'Партнёр' ? 'Партнёр' : 'Клиент'; ?></div>
                        </div>
                        <div class="partnersx__lvlBadge"><?php echo $level; ?> - Уровень</div>
                    </div>

                    <div class="partnersx__main">
                        <div class="partnersx__topRow">
                            <div class="partnersx__name"><?php echo htmlspecialchars($name); ?></div>
                            <span class="partnersx__eye" aria-hidden="true">◉</span>
                        </div>
                        <div class="partnersx__idText">ID: <?php echo htmlspecialchars($idFormatted); ?></div>
                        <div class="partnersx__meta">Линия: <?php echo htmlspecialchars((string)$card['side']); ?></div>
                        <div class="partnersx__meta">Объём L/R: <?php echo (int)$card['left_volume']; ?> / <?php echo (int)$card['right_volume']; ?> DV</div>
                        <?php if (!empty($card['registered_at'])): ?>
                            <div class="partnersx__meta">Дата регистрации: <?php echo htmlspecialchars(date('d.m.Y', strtotime((string)$card['registered_at']))); ?></div>
                        <?php endif; ?>
                        <div class="partnersx__statusChip <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (empty($cards)): ?>
            <div class="partnersx__empty">Пока в вашей структуре нет зарегистрированных партнёров.</div>
        <?php endif; ?>

        <div class="partnersx__footer">
            <div class="partnersx__range">
                Записи с <?php echo $cardsTotal > 0 ? ($offset + 1) : 0; ?> по <?php echo min($offset + $itemsPerPage, $cardsTotal); ?> из <?php echo $cardsTotal; ?>
            </div>
            <div class="partnersx__pager">
                <?php
                    $baseQ = [
                        'section' => 'partners',
                        'psearch' => $pSearch,
                        'partner_only' => $partnerOnly ? '1' : '0',
                        'show' => $showMode,
                        'level' => $levelFilter,
                        'all_levels' => $allLevels ? '1' : '0',
                        'sort_date' => $sortByDate ? '1' : '0',
                    ];
                    $prev = max(1, $page - 1);
                    $next = min($pagesTotal, $page + 1);
                    $pageSet = array_unique(array_filter([1, 2, 3, $page, $pagesTotal], static function ($p) use ($pagesTotal) {
                        return $p >= 1 && $p <= $pagesTotal;
                    }));
                    sort($pageSet);
                ?>
                <a class="partnersx__pageBtn" href="dashboard.php?<?php echo http_build_query($baseQ + ['ppage' => $prev]); ?>">‹</a>
                <?php foreach ($pageSet as $idx => $p): ?>
                    <?php if ($idx > 0 && $p - $pageSet[$idx - 1] > 1): ?>
                        <span class="partnersx__pageDots">...</span>
                    <?php endif; ?>
                    <?php if ($p === $page): ?>
                        <span class="partnersx__pageNow"><?php echo $p; ?></span>
                    <?php else: ?>
                        <a class="partnersx__pageNum" href="dashboard.php?<?php echo http_build_query($baseQ + ['ppage' => $p]); ?>"><?php echo $p; ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
                <a class="partnersx__pageBtn" href="dashboard.php?<?php echo http_build_query($baseQ + ['ppage' => $next]); ?>">›</a>
            </div>
        </div>
    </div>
</section>

<?php if ($partnerId !== '' && $cardsTotal === 0): ?>
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