<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$db = getDBConnection();
$userId = (int)($_SESSION['user_id'] ?? 0);

$fromRaw = trim((string)($_GET['from'] ?? ''));
$toRaw = trim((string)($_GET['to'] ?? ''));
$queryRaw = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['opage'] ?? 1));
$perPage = 10;

$parseRuDate = static function (string $value): ?string {
    if ($value === '') {
        return null;
    }
    $dt = DateTime::createFromFormat('d.m.Y', $value);
    if (!$dt || $dt->format('d.m.Y') !== $value) {
        return null;
    }
    return $dt->format('Y-m-d');
};

$fromDate = $parseRuDate($fromRaw);
$toDate = $parseRuDate($toRaw);

$where = ['o.user_id = :uid'];
$params = [':uid' => $userId];

if ($fromDate !== null) {
    $where[] = 'DATE(o.created_at) >= :from_date';
    $params[':from_date'] = $fromDate;
}
if ($toDate !== null) {
    $where[] = 'DATE(o.created_at) <= :to_date';
    $params[':to_date'] = $toDate;
}
if ($queryRaw !== '') {
    $where[] = 'o.order_number LIKE :order_query';
    $params[':order_query'] = '%' . preg_replace('/\s+/', '', $queryRaw) . '%';
}

$whereSql = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM orders o WHERE {$whereSql}");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$ordersStmt = $db->prepare("
    SELECT o.id, o.order_number, o.status, o.total, o.created_at
    FROM orders o
    WHERE {$whereSql}
    ORDER BY o.created_at DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $value) {
    $ordersStmt->bindValue($key, $value);
}
$ordersStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$ordersStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$ordersStmt->execute();
$orders = $ordersStmt->fetchAll();

$itemsByOrder = [];
if (!empty($orders)) {
    $orderIds = array_map(static function ($r) {
        return (int)$r['id'];
    }, $orders);
    $idPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));

    $itemsStmt = $db->prepare("
        SELECT order_id, product_name, product_sku, price, quantity
        FROM order_items
        WHERE order_id IN ({$idPlaceholders})
        ORDER BY id ASC
    ");
    $itemsStmt->execute($orderIds);
    foreach ($itemsStmt->fetchAll() as $item) {
        $oid = (int)$item['order_id'];
        if (!isset($itemsByOrder[$oid])) {
            $itemsByOrder[$oid] = [];
        }
        $itemsByOrder[$oid][] = $item;
    }
}

$statusLabelMap = [
    'pending' => 'Ожидает',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'delivered' => 'Оплачен',
    'cancelled' => 'Отменен',
];
$statusClassMap = [
    'pending' => 'ordersx__status--pending',
    'processing' => 'ordersx__status--processing',
    'shipped' => 'ordersx__status--shipped',
    'delivered' => 'ordersx__status--delivered',
    'cancelled' => 'ordersx__status--cancelled',
];

$fmtRub = static fn(float $v): string => number_format($v, 0, ',', ' ') . ' ₽';
$fmtDv = static fn(float $v): string => number_format($v / 30, 2, '.', ' ') . ' DV';

$fromUi = $fromRaw !== '' ? $fromRaw : '__.__.____';
$toUi = $toRaw !== '' ? $toRaw : '__.__.____';
?>

<section class="ordersx">
    <form class="ordersx__filterCard" method="get" action="dashboard.php">
        <input type="hidden" name="section" value="orders" />

        <div class="ordersx__filterTop">
            <label class="ordersx__filterLabel" for="orders-from">Дата с:</label>
            <input id="orders-from" class="ordersx__filterInput" name="from" type="text" value="<?php echo htmlspecialchars($fromUi); ?>" placeholder="__.__.____" />

            <label class="ordersx__filterLabel" for="orders-to">Дата по:</label>
            <input id="orders-to" class="ordersx__filterInput" name="to" type="text" value="<?php echo htmlspecialchars($toUi); ?>" placeholder="__.__.____" />

            <button class="ordersx__showBtn" type="submit">Показать</button>
        </div>

        <div class="ordersx__searchRow">
            <span class="ordersx__searchIcon">🔍</span>
            <input class="ordersx__searchInput" type="text" name="q" value="<?php echo htmlspecialchars($queryRaw); ?>" placeholder="Поиск по заказу" />
        </div>
    </form>

    <div class="ordersx__listCard">
        <div class="ordersx__head">
            <div class="ordersx__col ordersx__col--num">№</div>
            <div class="ordersx__col ordersx__col--order">Мои заказы</div>
            <div class="ordersx__col ordersx__col--rub">Сумма (₽)</div>
            <div class="ordersx__col ordersx__col--dv">Сумма (DV)</div>
            <div class="ordersx__col ordersx__col--status">Статус</div>
            <div class="ordersx__col ordersx__col--date">Дата</div>
        </div>

        <div class="ordersx__rows">
            <?php foreach ($orders as $idx => $order): ?>
                <?php
                    $orderId = (int)$order['id'];
                    $orderNum = trim((string)$order['order_number']);
                    $orderNum = $orderNum !== '' ? $orderNum : (string)$orderId;
                    $orderStatus = (string)($order['status'] ?? 'pending');
                    $statusText = $statusLabelMap[$orderStatus] ?? $orderStatus;
                    $statusClass = $statusClassMap[$orderStatus] ?? 'ordersx__status--pending';
                    $orderTotal = (float)($order['total'] ?? 0);
                    $orderDate = !empty($order['created_at']) ? date('d.m.Y', strtotime((string)$order['created_at'])) : '';
                    $detailItems = $itemsByOrder[$orderId] ?? [];
                    $canExpand = !empty($detailItems);
                    $detailId = 'order-details-' . $orderId;
                ?>
                <div class="ordersx__rowWrap">
                    <div class="ordersx__row">
                        <div class="ordersx__col ordersx__col--num">
                            <?php echo $offset + $idx + 1; ?>
                        </div>

                        <div class="ordersx__col ordersx__col--order">
                            <span>Заказ №<?php echo htmlspecialchars($orderNum); ?></span>
                            <?php if ($canExpand): ?>
                                <button
                                    class="ordersx__expand"
                                    type="button"
                                    data-target="<?php echo htmlspecialchars($detailId); ?>"
                                    aria-expanded="false"
                                >⌄</button>
                            <?php endif; ?>
                        </div>

                        <div class="ordersx__col ordersx__col--rub"><?php echo $fmtRub($orderTotal); ?></div>
                        <div class="ordersx__col ordersx__col--dv"><?php echo $fmtDv($orderTotal); ?></div>
                        <div class="ordersx__col ordersx__col--status">
                            <span class="ordersx__status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                        </div>
                        <div class="ordersx__col ordersx__col--date"><?php echo htmlspecialchars($orderDate); ?></div>
                    </div>

                    <?php if ($canExpand): ?>
                        <div id="<?php echo htmlspecialchars($detailId); ?>" class="ordersx__details">
                            <?php foreach ($detailItems as $it): ?>
                                <?php
                                    $line = (float)$it['price'] * (int)$it['quantity'];
                                ?>
                                <div class="ordersx__detailRow">
                                    <span class="ordersx__detailName"><?php echo htmlspecialchars((string)$it['product_name']); ?></span>
                                    <span class="ordersx__detailMeta">
                                        SKU: <?php echo htmlspecialchars((string)$it['product_sku']); ?>,
                                        <?php echo (int)$it['quantity']; ?> шт.,
                                        <?php echo $fmtRub($line); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="ordersx__footer">
            <?php
                $fromRecord = $totalRows > 0 ? $offset + 1 : 0;
                $toRecord = min($offset + $perPage, $totalRows);
            ?>
            <div class="ordersx__range">Записи с <?php echo $fromRecord; ?> по <?php echo $toRecord; ?> из <?php echo $totalRows; ?></div>
            <div class="ordersx__pager">
                <?php
                    $base = [
                        'section' => 'orders',
                        'from' => $fromRaw,
                        'to' => $toRaw,
                        'q' => $queryRaw,
                    ];
                    $prevPage = max(1, $page - 1);
                    $nextPage = min($totalPages, $page + 1);
                ?>
                <a class="ordersx__pageBtn" href="dashboard.php?<?php echo http_build_query($base + ['opage' => $prevPage]); ?>">‹</a>
                <span class="ordersx__pageCurrent"><?php echo $page; ?></span>
                <a class="ordersx__pageBtn" href="dashboard.php?<?php echo http_build_query($base + ['opage' => $nextPage]); ?>">›</a>
            </div>
        </div>
    </div>
</section>

<script>
  (function () {
    const inputs = ['orders-from', 'orders-to']
      .map((id) => document.getElementById(id))
      .filter(Boolean);

    inputs.forEach((input) => {
      input.addEventListener('focus', () => {
        if (input.value === '__.__.____') input.value = '';
      });
      input.addEventListener('blur', () => {
        if (!input.value.trim()) input.value = '__.__.____';
      });
    });

    document.querySelectorAll('.ordersx__expand').forEach((btn) => {
      btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-target');
        const target = targetId ? document.getElementById(targetId) : null;
        if (!target) return;
        const opened = target.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', opened ? 'true' : 'false');
        btn.classList.toggle('is-open', opened);
      });
    });
  })();
</script>