<div class="history">
    <div class="history__filterCard" style="background: rgba(255,255,255,0.08) !important; backdrop-filter: blur(20px);">
        <div class="history__filterRow history__filterRow--top">
            <div class="history__filterLabel">Дата с:</div>
            <div class="history__filterInput">__.__.__</div>

            <div class="history__filterLabel">Дата по:</div>
            <div class="history__filterInput">__.__.__</div>

            <div class="history__filterSelectWrap">
                <div class="history__filterSelect" id="history-bonus-select">
                    <span id="history-bonus-selected">Тип бонуса</span>
                    <span class="history__chevron">▼</span>
                </div>
                <div class="history__bonusDropdown" id="history-bonus-dropdown">
                    <div class="history__bonusList">
                        <div class="history__bonusItem" data-type="all">Все</div>
                        <div class="history__bonusItem" data-type="3">Бонус “3 круга влияния”</div>
                        <div class="history__bonusItem" data-type="balance">Бонус “Баланс”</div>
                        <div class="history__bonusItem" data-type="growth">Бонус “Роста”</div>
                        <div class="history__bonusItem" data-type="global">Бонус “Глобальный”</div>
                        <div class="history__bonusItem" data-type="rep">Бонус “Представительский”</div>
                    </div>
                </div>
            </div>

            <button class="history__filterBtn" type="button">Показать</button>
        </div>

        <div class="history__filterRow history__filterRow--search">
            <div class="history__searchIcon">🔍</div>
            <div class="history__searchPlaceholder">Поиск по пользователю / бонусу</div>
        </div>
    </div>

    <div class="history__listCard" style="background: rgba(255,255,255,0.08) !important; backdrop-filter: blur(20px);">
        <div class="history__listHeader">
            <div class="history__col history__col--event">Событие</div>
            <div class="history__col history__col--status">Статус</div>
            <div class="history__col history__col--amount">Сумма DV</div>
            <div class="history__col history__col--date">Дата</div>
        </div>

        <div class="history__list">
            <div class="history__row" data-type="3">
                <div class="history__col history__col--event">Получен бонус "3 круга влияния" с 1 круга от Ивана Иванова</div>
                <div class="history__col history__col--status history__status history__status--partner">Партнер</div>
                <div class="history__col history__col--amount">150.00 DV</div>
                <div class="history__col history__col--date">12.01.2026</div>
            </div>
            <div class="history__row" data-type="balance">
                <div class="history__col history__col--event">Бонус "Баланс" 5%</div>
                <div class="history__col history__col--status history__status history__status--bronze">Бронзовый лидер</div>
                <div class="history__col history__col--amount">150.00 DV</div>
                <div class="history__col history__col--date">12.01.2026</div>
            </div>
            <div class="history__row" data-type="growth">
                <div class="history__col history__col--event">Присвоен статус Серебряный лидер</div>
                <div class="history__col history__col--status history__status history__status--silver">Серебряный лидер</div>
                <div class="history__col history__col--amount">150.00 DV</div>
                <div class="history__col history__col--date">12.01.2026</div>
            </div>
            <div class="history__row" data-type="growth">
                <div class="history__col history__col--event">Бонус "Роста" с 1 уровня от Пети Петрова</div>
                <div class="history__col history__col--status history__status history__status--gold">Золотой лидер</div>
                <div class="history__col history__col--amount">150.00 DV</div>
                <div class="history__col history__col--date">12.01.2026</div>
            </div>
            <div class="history__row" data-type="global">
                <div class="history__col history__col--event">Глобальный бонус 1% от всего товарооборота компании</div>
                <div class="history__col history__col--status history__status history__status--platinum">Платиновый лидер</div>
                <div class="history__col history__col--amount">150.00 DV</div>
                <div class="history__col history__col--date">12.01.2026</div>
            </div>
            <div class="history__row" data-type="rep">
                <div class="history__col history__col--event">Представительский бонус 1% от всего товарооборота команды</div>
                <div class="history__col history__col--status history__status history__status--diamond">Бриллиантовый лидер</div>
                <div class="history__col history__col--amount">150.00 DV</div>
                <div class="history__col history__col--date">12.01.2026</div>
            </div>
            <div class="history__row" data-type="3">
                <div class="history__col history__col--event">Получен бонус "3 круга влияния" с 1 круга от Ивана Иванова</div>
                <div class="history__col history__col--status history__status history__status--director">Директор</div>
                <div class="history__col history__col--amount">150.00 DV</div>
                <div class="history__col history__col--date">12.01.2026</div>
            </div>
            <div class="history__row" data-type="balance">
                <div class="history__col history__col--event">Бонус "Баланс" 5%</div>
                <div class="history__col history__col--status history__status history__status--executive">Исполнительный директор</div>
                <div class="history__col history__col--amount">150.00 DV</div>
                <div class="history__col history__col--date">12.01.2026</div>
            </div>
            <div class="history__row" data-type="growth">
                <div class="history__col history__col--event">Присвоен статус Коммерческий директор</div>
                <div class="history__col history__col--status history__status history__status--commercial">Коммерческий директор</div>
                <div class="history__col history__col--amount">150.00 DV</div>
                <div class="history__col history__col--date">12.01.2026</div>
            </div>
            <div class="history__row" data-type="growth">
                <div class="history__col history__col--event">Присвоен статус Генеральный директор</div>
                <div class="history__col history__col--status history__status history__status--general">Генеральный директор</div>
                <div class="history__col history__col--amount">150.00 DV</div>
                <div class="history__col history__col--date">12.01.2026</div>
            </div>
        </div>

        <div class="history__footer">
            <div class="history__range">Записи с 1 по 10 из 192</div>
            <div class="history__pager">
                <button class="history__pageBtn" type="button">‹</button>
                <span class="history__page">1</span>
                <span class="history__page">2</span>
                <span class="history__page">3</span>
                <span class="history__page">…</span>
                <span class="history__page">10</span>
                <span class="history__page">192</span>
                <button class="history__pageBtn" type="button">›</button>
            </div>
        </div>
    </div>
</div>

<script>
  (function() {
    const select = document.getElementById('history-bonus-select');
    const selectedLabel = document.getElementById('history-bonus-selected');
    const dropdown = document.getElementById('history-bonus-dropdown');
    const rows = Array.from(document.querySelectorAll('.history__row'));

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
      rows.forEach((row) => {
        const rowType = row.getAttribute('data-type');
        row.style.display = (type === 'all' || rowType === type) ? '' : 'none';
      });
      closeDropdown();
    });
    document.addEventListener('click', (e) => {
      if (!dropdown || !select) return;
      if (dropdown.contains(e.target) || select.contains(e.target)) return;
      closeDropdown();
    });
  })();
</script>


