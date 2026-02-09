<?php
/*
Template Name: Partner Dashboard
*/
?>
<!doctype html>
<html lang="ru">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Кабинет партнёра</title>
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/dashboard.css" />
    <style>
      .dash__section {
        display: none;
      }
      .dash__section.is-active {
        display: block;
      }
    </style>
  </head>
  <body>
    <div class="dash" data-active-section="dashboard">
      <div class="dash__layout">
        <!-- LEFT MENU -->
        <aside class="dash__menu">
          <div class="dash__menuInner">
            <nav class="dash__nav">
              <a class="dash__navItem is-active" href="#dashboard" data-target="dashboard">
                <span class="dash__navIcon">
                  <img src="<?php echo get_template_directory_uri(); ?>/img/icons/bento-menu.svg" alt="" />
                </span>
                <span class="dash__navLabel">Кабинет</span>
              </a>
              <a class="dash__navItem" href="#profile" data-target="profile">
                <span class="dash__navIcon">
                  <img src="<?php echo get_template_directory_uri(); ?>/img/icons/user-circle.svg" alt="" />
                </span>
                <span class="dash__navLabel">Профиль</span>
              </a>
              <a class="dash__navItem" href="#shop" data-target="shop">
                <span class="dash__navIcon">
                  <img src="<?php echo get_template_directory_uri(); ?>/img/icons/shopping-bag.svg" alt="" />
                </span>
                <span class="dash__navLabel">Магазин</span>
              </a>
              <a class="dash__navItem" href="#orders" data-target="orders">
                <span class="dash__navIcon">
                  <img src="<?php echo get_template_directory_uri(); ?>/img/icons/shopping-cart.svg" alt="" />
                </span>
                <span class="dash__navLabel">Заказы</span>
              </a>
              <a class="dash__navItem" href="#team" data-target="team">
                <span class="dash__navIcon">
                  <img src="<?php echo get_template_directory_uri(); ?>/img/icons/two-user.svg" alt="" />
                </span>
                <span class="dash__navLabel">Команда</span>
              </a>
              <a class="dash__navItem" href="#events" data-target="events">
                <span class="dash__navIcon">
                  <img src="<?php echo get_template_directory_uri(); ?>/img/icons/document-box.svg" alt="" />
                </span>
                <span class="dash__navLabel">История<br />событий</span>
              </a>
              <a class="dash__navItem" href="#partners" data-target="partners">
                <span class="dash__navIcon">
                  <img src="<?php echo get_template_directory_uri(); ?>/img/icons/switch-user.svg" alt="" />
                </span>
                <span class="dash__navLabel">Партнёры</span>
              </a>
              <a class="dash__navItem" href="#news" data-target="news">
                <span class="dash__navIcon">
                  <img src="<?php echo get_template_directory_uri(); ?>/img/icons/document-7.svg" alt="" />
                </span>
                <span class="dash__navLabel">Новости</span>
              </a>
              <a class="dash__navItem" href="#library" data-target="library">
                <span class="dash__navIcon">
                  <img src="<?php echo get_template_directory_uri(); ?>/img/icons/book.svg" alt="" />
                </span>
                <span class="dash__navLabel">Библиотека</span>
              </a>
              <a class="dash__navItem" href="#support" data-target="support">
                <span class="dash__navIcon">
                  <img src="<?php echo get_template_directory_uri(); ?>/img/icons/headphones.svg" alt="" />
                </span>
                <span class="dash__navLabel">Поддержка</span>
              </a>
              <a class="dash__navItem" href="#logout" data-target="logout">
                <span class="dash__navIcon">
                  <img src="<?php echo get_template_directory_uri(); ?>/img/icons/exit.svg" alt="" />
                </span>
                <span class="dash__navLabel">Выйти</span>
              </a>
            </nav>
          </div>
        </aside>

        <!-- GLASS CONTAINER -->
        <div class="dash__glass">
          <!-- MAIN + RIGHT PROFILE -->
          <div class="dash__grid">
            <!-- MAIN COLUMN -->
            <main class="dash__main">
              <section id="dashboard" class="dash__section is-active" data-section="dashboard">
                <!-- HEADER -->
                <header class="dash__header">
                  <div class="dash__logo">
                    <img class="dash__logoImg" src="<?php echo get_template_directory_uri(); ?>/img/image0_1_37.png" alt="Logo" />
                  </div>

                  <div class="dash__titleWrap">
                    <h1 class="dash__title">Кабинет</h1>

                    <div class="dash__crumbs">
                      <span class="dash__crumb">Главная</span>
                      <span class="dash__crumbSep">/</span>
                      <span class="dash__crumbActive">Кабинет</span>
                    </div>
                  </div>
                </header>

                <!-- PROMO / "Жилищная программа" -->
                <section class="dash__promo">
                  <div class="dash__promoText">
                    Станьте партнером и участвуйте в розыгрыше “Жилищной программы”
                  </div>

                  <div class="dash__promoRight">
                    <img class="dash__promoImg" src="<?php echo get_template_directory_uri(); ?>/img/dom.png" alt="" />
                  </div>

                  <img
                    class="dash__promoObj dash__promoObj--a"
                    src="<?php echo get_template_directory_uri(); ?>/img/derevannyi-brelok-na-belom-Photoroom.png"
                    alt=""
                  />
                  <img
                    class="dash__promoObj dash__promoObj--b"
                    src="<?php echo get_template_directory_uri(); ?>/img/2669627_1751-Photoroom.png"
                    alt=""
                  />
                </section>

                <!-- TWO BIG CARDS -->
                <section class="dash__cards2">
                  <article class="dash__card dash__card--pink">
                    <img class="dash__cardVector" src="<?php echo get_template_directory_uri(); ?>/img/icons/vector.svg" alt="" />
                    <div class="dash__cardTop">
                      <div class="dash__cardDvPill">DV</div>
                    </div>

                    <div class="dash__cardBody">
                      <div class="dash__cardMetric">
                        <div class="dash__cardMetricLabel">Бонусы:</div>
                        <div class="dash__cardMetricValue">100.000</div>
                      </div>
                      <div class="dash__cardMetric">
                        <div class="dash__cardMetricLabel">Ожидают вывода:</div>
                        <div class="dash__cardMetricValue dash__cardMetricValue--sm">10.000</div>
                      </div>
                    </div>

                    <div class="dash__cardActions">
                      <a class="dash__action" href="#">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/icons/add-circle.svg" alt="" />
                        <span>Пополнить</span>
                      </a>
                      <span class="dash__actionSep"></span>
                      <a class="dash__action" href="#">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/icons/card-send.svg" alt="" />
                        <span>Вывести</span>
                      </a>
                      <span class="dash__actionSep"></span>
                      <a class="dash__action" href="#">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/icons/convert-card.svg" alt="" />
                        <span>Операции</span>
                      </a>
                    </div>
                  </article>

                  <article class="dash__card dash__card--cyan">
                    <img class="dash__cardVector" src="<?php echo get_template_directory_uri(); ?>/img/icons/vector.svg" alt="" />
                    <div class="dash__cardTop">
                      <div class="dash__cardDvPill">DV</div>
                    </div>

                    <div class="dash__cardBody dash__cardBody--cashback">
                      <div class="dash__cardMetric">
                        <div class="dash__cardMetricLabel">Кэшбэк:</div>
                        <div class="dash__cardMetricValue">2 %</div>
                      </div>
                    </div>

                    <div class="dash__cardActions">
                      <a class="dash__action dash__action--single" href="#">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/icons/convert-card.svg" alt="" />
                        <span>Операции</span>
                      </a>
                    </div>
                  </article>
                </section>

                <!-- BIG STATS BLOCK -->
                <section class="dash__stats">
                  <div class="dash__statsHead">
                    <div class="dash__statsTitle">Полученные бонсы:</div>
                    <div class="dash__statsRange">за 1.12.2025 - 1.12.2026</div>
                    <button class="dash__iconBtn" type="button" aria-label="Календарь">
                      <img src="<?php echo get_template_directory_uri(); ?>/img/icons/calendar.svg" alt="" />
                    </button>
                  </div>

                  <div class="dash__statsBody">
                    <div class="dash__donutWrap">
                      <div class="dash__donutTop">
                        <div class="dash__donutLabel">Общая сумма:</div>
                        <div class="dash__donutValue">10 000 DV</div>
                      </div>

                      <div class="dash__donut"></div>

                      <div class="dash__donutCaption">10 000 DV</div>
                    </div>

                    <div class="dash__bonusGrid">
                      <article class="dash__bonusCard dash__bonusCard--pink">
                        <div class="dash__bonusTitle">«Накопительный» кешбэк</div>
                        <div class="dash__bonusValue">10 000 DV</div>
                      </article>
                      <article class="dash__bonusCard dash__bonusCard--gold">
                        <div class="dash__bonusTitle">Бонус «3 круга влияния»</div>
                        <div class="dash__bonusValue">10 000 DV</div>
                      </article>
                      <article class="dash__bonusCard dash__bonusCard--green">
                        <div class="dash__bonusTitle">Бонус «Баланс»</div>
                        <div class="dash__bonusValue">10 000 DV</div>
                      </article>
                      <article class="dash__bonusCard dash__bonusCard--violet">
                        <div class="dash__bonusTitle">Бонус «Роста»</div>
                        <div class="dash__bonusValue">10 000 DV</div>
                      </article>
                      <article class="dash__bonusCard dash__bonusCard--cyan">
                        <div class="dash__bonusTitle">«Глобальный» бонус</div>
                        <div class="dash__bonusValue">10 000 DV</div>
                      </article>
                      <article class="dash__bonusCard dash__bonusCard--lilac">
                        <div class="dash__bonusTitle">«Представительский» бонус</div>
                        <div class="dash__bonusValue">10 000 DV</div>
                      </article>
                    </div>
                  </div>
                </section>
              </section>

              <section id="profile" class="dash__section" data-section="profile" aria-hidden="true">
                <header class="dash__header">
                  <div class="dash__logo">
                    <img class="dash__logoImg" src="<?php echo get_template_directory_uri(); ?>/img/image0_1_37.png" alt="Logo" />
                  </div>
                  <div class="dash__titleWrap">
                    <h1 class="dash__title">Профиль</h1>
                    <div class="dash__crumbs">
                      <span class="dash__crumb">Главная</span>
                      <span class="dash__crumbSep">/</span>
                      <span class="dash__crumbActive">Профиль</span>
                    </div>
                  </div>
                </header>

                <div class="profile">
                  <div class="profile__panel">
                    <div class="profile__userBadge">
                      <img class="profile__rankTop" src="<?php echo get_template_directory_uri(); ?>/img/rank_top.png" alt="" />
                      <img class="profile__rankMid" src="<?php echo get_template_directory_uri(); ?>/img/rank_mid.png" alt="" />
                      <img class="profile__avatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                      <img class="profile__rankLabel" src="<?php echo get_template_directory_uri(); ?>/img/rank_label.png" alt="" />
                    </div>
                    <div class="profile__name">Михаил Орешкин</div>
                    <div class="profile__regRow">
                      <img class="profile__regIcon" src="<?php echo get_template_directory_uri(); ?>/img/icons/document-7.svg" alt="" />
                      <span>Регистрационный номер: 254637395048</span>
                    </div>

                    <div class="profile__divider"></div>

                    <div class="profile__consultant">
                      <div class="profile__consultantHead">Ваш консультант</div>
                      <div class="profile__consultantBody">
                        <div class="profile__consultantBadge">
                          <img class="profile__rankTop" src="<?php echo get_template_directory_uri(); ?>/img/rank_top.png" alt="" />
                          <img class="profile__rankMid" src="<?php echo get_template_directory_uri(); ?>/img/rank_mid.png" alt="" />
                          <img class="profile__avatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                          <img class="profile__rankLabel" src="<?php echo get_template_directory_uri(); ?>/img/rank_label.png" alt="" />
                        </div>
                        <div class="profile__consultantInfo">
                          <div class="profile__consultantName">Еремина Мария</div>
                          <div class="profile__consultantId">ID: 007-4356000</div>
                        </div>
                      </div>
                      <div class="profile__consultantActions">
                        <a class="profile__social profile__social--tg" href="#">
                          <img src="<?php echo get_template_directory_uri(); ?>/img/icons/tg.svg" alt="" />
                        </a>
                        <a class="profile__social profile__social--vk" href="#">
                          <img src="<?php echo get_template_directory_uri(); ?>/img/icons/vk.svg" alt="" />
                        </a>
                        <a class="profile__social profile__social--mail" href="#">
                          <img src="<?php echo get_template_directory_uri(); ?>/img/icons/mail.svg" alt="" />
                        </a>
                        <a class="profile__social profile__social--phone" href="#">
                          <img src="<?php echo get_template_directory_uri(); ?>/img/icons/phone.svg" alt="" />
                        </a>
                      </div>
                    </div>
                  </div>

                  <div class="profile__content">
                    <div class="profile__title">Мои данные</div>

                    <div class="profile__row profile__row--top">
                      <div class="profile__card profile__card--half">
                        <div class="profile__cardIcon profile__cardIcon--magenta">
                          <img src="<?php echo get_template_directory_uri(); ?>/img/icons/user-circle.svg" alt="" />
                        </div>
                        <div class="profile__cardText">
                          <div class="profile__cardLabel">Имя и фамилия</div>
                          <div class="profile__cardValue">Михаил Орешкин</div>
                        </div>
                      </div>

                      <div class="profile__card profile__card--half">
                        <div class="profile__cardIcon profile__cardIcon--orange">
                          <img src="<?php echo get_template_directory_uri(); ?>/img/icons/calendar.svg" alt="" />
                        </div>
                        <div class="profile__cardText">
                          <div class="profile__cardLabel">Дата рождения</div>
                          <div class="profile__cardValue">15.01.1984</div>
                        </div>
                      </div>
                    </div>

                   
                      

                    <div class="profile__card profile__card--full profile__card--email">
                      <div class="profile__cardIcon profile__cardIcon--green">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/icons/mail.svg" alt="" />
                      </div>
                      <div class="profile__cardText">
                        <div class="profile__cardLabel">E-mail</div>
                        <div class="profile__cardValue">User_Profile@gmail.com</div>
                      </div>
                      <div class="profile__cardStatus profile__cardStatus--ok">Подтвержден</div>
                      <button class="profile__cardChevron" type="button" aria-label="Изменить">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/icons/Icon (1).svg" alt="" />
                      </button>
                    </div>

                    <div class="profile__card profile__card--full profile__card--phone">
                      <div class="profile__cardIcon profile__cardIcon--cyan">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/icons/phone.svg" alt="" />
                      </div>
                      <div class="profile__cardText">
                        <div class="profile__cardLabel">Номер телефона</div>
                        <div class="profile__cardValue">+7 999-777-8888</div>
                      </div>
                      <div class="profile__cardDivider"></div>
                      <div class="profile__cardIcon profile__cardIcon--violet">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/icons/document-box.svg" alt="" />
                      </div>
                      <div class="profile__cardText">
                        <div class="profile__cardLabel">Страна, город и адрес доставки</div>
                        <div class="profile__cardValue">Россия, г.Иркутск</div>
                      </div>
                      <div class="profile__cardStatus profile__cardStatus--warn">Не подтвержден</div>
                      <button class="profile__cardChevron" type="button" aria-label="Изменить">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/icons/Icon (1).svg" alt="" />
                      </button>
                    </div>

                    <div class="profile__row profile__row--links">
                      <div class="profile__card profile__card--half">
                        <div class="profile__cardIcon profile__cardIcon--vk">
                          <img src="<?php echo get_template_directory_uri(); ?>/img/icons/vk.svg" alt="" />
                        </div>
                        <div class="profile__cardText">
                          <div class="profile__cardLabel">Ссылка</div>
                          <div class="profile__cardValue">https://vk.com/im</div>
                        </div>
                      </div>

                      <div class="profile__card profile__card--half">
                        <div class="profile__cardIcon profile__cardIcon--tg">
                          <img src="<?php echo get_template_directory_uri(); ?>/img/icons/tg.svg" alt="" />
                        </div>
                        <div class="profile__cardText">
                          <div class="profile__cardLabel">Username</div>
                          <div class="profile__cardValue">@gashdy</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section id="shop" class="dash__section" data-section="shop" aria-hidden="true">
                <header class="dash__header">
                  <div class="dash__logo">
                    <img class="dash__logoImg" src="<?php echo get_template_directory_uri(); ?>/img/image0_1_37.png" alt="Logo" />
                  </div>
                  <div class="dash__titleWrap">
                    <h1 class="dash__title">Магазин</h1>
                    <div class="dash__crumbs">
                      <span class="dash__crumb">Главная</span>
                      <span class="dash__crumbSep">/</span>
                      <span class="dash__crumbActive">Магазин</span>
                    </div>
                  </div>
                </header>

              </section>

              <section id="orders" class="dash__section" data-section="orders" aria-hidden="true">
                <header class="dash__header">
                  <div class="dash__logo">
                    <img class="dash__logoImg" src="<?php echo get_template_directory_uri(); ?>/img/image0_1_37.png" alt="Logo" />
                  </div>
                  <div class="dash__titleWrap">
                    <h1 class="dash__title">Заказы</h1>
                    <div class="dash__crumbs">
                      <span class="dash__crumb">Главная</span>
                      <span class="dash__crumbSep">/</span>
                      <span class="dash__crumbActive">Заказы</span>
                    </div>
                  </div>
                </header>
              </section>

              <section id="team" class="dash__section" data-section="team" aria-hidden="true">
                <header class="dash__header">
                  <div class="dash__logo">
                    <img class="dash__logoImg" src="<?php echo get_template_directory_uri(); ?>/img/image0_1_37.png" alt="Logo" />
                  </div>
                  <div class="dash__titleWrap">
                    <h1 class="dash__title">Команда</h1>
                    <div class="dash__crumbs">
                      <span class="dash__crumb">Главная</span>
                      <span class="dash__crumbSep">/</span>
                      <span class="dash__crumbActive">Команда</span>
                    </div>
                  </div>
                </header>

                <div class="team">
                  <div class="team__levels">
                    <div class="team__levelsCard"></div>
                    <div class="team__levelsGrid">
                      <div class="team__levelCard">
                        <img class="team__levelImage" src="<?php echo get_template_directory_uri(); ?>/img/icons/partner.svg" alt="" />
                      </div>
                      <div class="team__levelCard">
                        <img class="team__levelImage" src="<?php echo get_template_directory_uri(); ?>/img/icons/bronza.svg" alt="" />
                      </div>
                      <div class="team__levelCard">
                        <img class="team__levelImage" src="<?php echo get_template_directory_uri(); ?>/img/icons/serebro.svg" alt="" />
                      </div>
                      <div class="team__levelCard">
                        <img class="team__levelImage" src="<?php echo get_template_directory_uri(); ?>/img/icons/platina.svg" alt="" />
                      </div>
                      <div class="team__levelCard">
                        <img class="team__levelImage" src="<?php echo get_template_directory_uri(); ?>/img/icons/director.svg" alt="" />
                      </div>
                      <div class="team__levelCard">
                        <img class="team__levelImage" src="<?php echo get_template_directory_uri(); ?>/img/icons/komer-director.svg" alt="" />
                      </div>
                      <div class="team__levelCard">
                        <img class="team__levelImage" src="<?php echo get_template_directory_uri(); ?>/img/icons/zoloto.svg" alt="" />
                      </div>
                      <div class="team__levelCard">
                        <img class="team__levelImage" src="<?php echo get_template_directory_uri(); ?>/img/icons/briliant.svg" alt="" />
                      </div>
                      <div class="team__levelCard">
                        <img class="team__levelImage" src="<?php echo get_template_directory_uri(); ?>/img/icons/ispol-director.svg" alt="" />
                      </div>
                      <div class="team__levelCard">
                        <img class="team__levelImage" src="<?php echo get_template_directory_uri(); ?>/img/icons/gen-director.svg" alt="" />
                      </div>
                    </div>
                  </div>

                  

                  <div class="team__tree">
                    <div class="team__treeCard"></div>
                    <div class="team__treeGrid">
                      <div class="team__treeNode">
                        <img class="team__treeAvatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                        <div class="team__treeBadge team__treeBadge--partner">Партнёр</div>
                      </div>
                      <div class="team__treeNode">
                        <img class="team__treeAvatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                        <div class="team__treeBadge team__treeBadge--bronze">Бронзовый лидер</div>
                      </div>
                      <div class="team__treeNode">
                        <img class="team__treeAvatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                        <div class="team__treeBadge team__treeBadge--silver">Серебрянный лидер</div>
                      </div>
                      <div class="team__treeNode">
                        <img class="team__treeAvatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                        <div class="team__treeBadge team__treeBadge--gold">Золотой лидер</div>
                      </div>
                      <div class="team__treeNode">
                        <img class="team__treeAvatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                        <div class="team__treeBadge team__treeBadge--director">Директор</div>
                      </div>
                      <div class="team__treeNode">
                        <img class="team__treeAvatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                        <div class="team__treeBadge team__treeBadge--diamond">Бриллиантовый лидер</div>
                      </div>
                      <div class="team__treeNode">
                        <img class="team__treeAvatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                        <div class="team__treeBadge team__treeBadge--platinum">Платиновый лидер</div>
                      </div>
                      <div class="team__treeNode">
                        <img class="team__treeAvatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                        <div class="team__treeBadge team__treeBadge--executive">Исполнительный директор</div>
                      </div>
                      <div class="team__treeNode">
                        <img class="team__treeAvatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                        <div class="team__treeBadge team__treeBadge--commercial">Коммерческий директор</div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section id="events" class="dash__section" data-section="events" aria-hidden="true">
                <header class="dash__header">
                  <div class="dash__logo">
                    <img class="dash__logoImg" src="<?php echo get_template_directory_uri(); ?>/img/image0_1_37.png" alt="Logo" />
                  </div>
                  <div class="dash__titleWrap">
                    <h1 class="dash__title">История событий</h1>
                    <div class="dash__crumbs">
                      <span class="dash__crumb">Главная</span>
                      <span class="dash__crumbSep">/</span>
                      <span class="dash__crumbActive">История событий</span>
                    </div>
                  </div>
                </header>
              </section>

              <section id="partners" class="dash__section" data-section="partners" aria-hidden="true">
                <header class="dash__header">
                  <div class="dash__logo">
                    <img class="dash__logoImg" src="<?php echo get_template_directory_uri(); ?>/img/image0_1_37.png" alt="Logo" />
                  </div>
                  <div class="dash__titleWrap">
                    <h1 class="dash__title">Партнёры</h1>
                    <div class="dash__crumbs">
                      <span class="dash__crumb">Главная</span>
                      <span class="dash__crumbSep">/</span>
                      <span class="dash__crumbActive">Партнёры</span>
                    </div>
                  </div>
                </header>
              </section>

              <section id="news" class="dash__section" data-section="news" aria-hidden="true">
                <header class="dash__header">
                  <div class="dash__logo">
                    <img class="dash__logoImg" src="<?php echo get_template_directory_uri(); ?>/img/image0_1_37.png" alt="Logo" />
                  </div>
                  <div class="dash__titleWrap">
                    <h1 class="dash__title">Новости</h1>
                    <div class="dash__crumbs">
                      <span class="dash__crumb">Главная</span>
                      <span class="dash__crumbSep">/</span>
                      <span class="dash__crumbActive">Новости</span>
                    </div>
                  </div>
                </header>
              </section>

              <section id="library" class="dash__section" data-section="library" aria-hidden="true">
                <header class="dash__header">
                  <div class="dash__logo">
                    <img class="dash__logoImg" src="<?php echo get_template_directory_uri(); ?>/img/image0_1_37.png" alt="Logo" />
                  </div>
                  <div class="dash__titleWrap">
                    <h1 class="dash__title">Библиотека</h1>
                    <div class="dash__crumbs">
                      <span class="dash__crumb">Главная</span>
                      <span class="dash__crumbSep">/</span>
                      <span class="dash__crumbActive">Библиотека</span>
                    </div>
                  </div>
                </header>
              </section>

              <section id="support" class="dash__section" data-section="support" aria-hidden="true">
                <header class="dash__header">
                  <div class="dash__logo">
                    <img class="dash__logoImg" src="<?php echo get_template_directory_uri(); ?>/img/image0_1_37.png" alt="Logo" />
                  </div>
                  <div class="dash__titleWrap">
                    <h1 class="dash__title">Поддержка</h1>
                    <div class="dash__crumbs">
                      <span class="dash__crumb">Главная</span>
                      <span class="dash__crumbSep">/</span>
                      <span class="dash__crumbActive">Поддержка</span>
                    </div>
                  </div>
                </header>
              </section>

              <section id="logout" class="dash__section" data-section="logout" aria-hidden="true">
                <header class="dash__header">
                  <div class="dash__logo">
                    <img class="dash__logoImg" src="<?php echo get_template_directory_uri(); ?>/img/image0_1_37.png" alt="Logo" />
                  </div>
                  <div class="dash__titleWrap">
                    <h1 class="dash__title">Выйти</h1>
                    <div class="dash__crumbs">
                      <span class="dash__crumb">Главная</span>
                      <span class="dash__crumbSep">/</span>
                      <span class="dash__crumbActive">Выход</span>
                    </div>
                  </div>
                </header>
              </section>
            </main>

            <!-- RIGHT PROFILE PANEL -->
            <aside class="dash__profile">
              <button class="dash__collapse" type="button" aria-label="Свернуть/развернуть">
                <img src="<?php echo get_template_directory_uri(); ?>/img/icons/arrow-left.svg" alt="" />
              </button>

              <div class="dash__profileTop">
                <div class="dash__notifRow">
                  <div class="dash__notifBox">
                    <img src="<?php echo get_template_directory_uri(); ?>/img/icons/notification.svg" alt="" />
                    <span class="dash__badge dash__badge--cyan">5</span>
                  </div>
                  <div class="dash__notifBox">
                    <img src="<?php echo get_template_directory_uri(); ?>/img/icons/mail.svg" alt="" />
                    <span class="dash__badge dash__badge--magenta">25</span>
                  </div>
                </div>

                <div class="dash__user">
                  <div class="dash__userBadge">
                    <img class="dash__rankTop" src="<?php echo get_template_directory_uri(); ?>/img/rank_top.png" alt="" />
                    <img class="dash__rankMid" src="<?php echo get_template_directory_uri(); ?>/img/rank_mid.png" alt="" />
                    <img class="dash__avatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                    <img class="dash__rankLabel" src="<?php echo get_template_directory_uri(); ?>/img/rank_label.png" alt="" />
                    <div class="dash__rankText">Бронзовый лидер</div>
                  </div>

                  <div class="dash__userNameRow">
                    <div class="dash__userName">Третьяков Иван</div>
                    <span class="dash__onlineDot" aria-hidden="true"></span>
                  </div>
                  <div class="dash__userId">ID: 007-4356000</div>
                </div>

                <div class="dash__partnerLink">
                  <div class="dash__partnerLinkLabel">Партнерская ссылка:</div>
                  <div class="dash__partnerLinkRow">
                    <div class="dash__partnerLinkValue">http://endless.horse/http://horse/http://</div>
                    <div class="dash__partnerLinkIcons">
                      <button class="dash__miniIcon" type="button" aria-label="Копировать">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/icons/copy.svg" alt="" />
                      </button>
                      <button class="dash__miniIcon" type="button" aria-label="QR">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/icons/qr.svg" alt="" />
                      </button>
                      <button class="dash__miniIcon" type="button" aria-label="Поделиться">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/icons/share.svg" alt="" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="dash__profileMid">
                <div class="dash__kpis">
                  <div class="dash__kpiRow">
                    <div class="dash__kpiIcon dash__kpiIcon--cyan">
                      <img src="<?php echo get_template_directory_uri(); ?>/img/icons/credit-card.svg" alt="" />
                    </div>
                    <div class="dash__kpiText">
                      <div class="dash__kpiValueRow is-bold">
                        <span class="dash__kpiValue">150.00</span>
                        <span class="dash__kpiUnit">DV</span>
                      </div>
                      <div class="dash__kpiLabel">Общая сумма DV</div>
                    </div>
                  </div>

                  <div class="dash__kpiRow">
                    <div class="dash__kpiIcon dash__kpiIcon--magenta">
                      <img src="<?php echo get_template_directory_uri(); ?>/img/icons/coins-1.svg" alt="" />
                    </div>
                    <div class="dash__kpiText">
                      <div class="dash__kpiValueRow">
                        <span class="dash__kpiValue">150.00</span>
                        <span class="dash__kpiUnit">DV</span>
                      </div>
                      <div class="dash__kpiLabel">«Накопительный» кешбэк</div>
                    </div>
                  </div>

                  <div class="dash__kpiRow">
                    <div class="dash__kpiIcon dash__kpiIcon--gold">
                      <img src="<?php echo get_template_directory_uri(); ?>/img/icons/coins-1-plus.svg" alt="" />
                    </div>
                    <div class="dash__kpiText">
                      <div class="dash__kpiValueRow">
                        <span class="dash__kpiValue">150.00</span>
                        <span class="dash__kpiUnit">DV</span>
                      </div>
                      <div class="dash__kpiLabel">Бонус «3 круга влияния»</div>
                    </div>
                  </div>

                  <div class="dash__kpiRow">
                    <div class="dash__kpiIcon dash__kpiIcon--green">
                      <img src="<?php echo get_template_directory_uri(); ?>/img/icons/coins-2.svg" alt="" />
                    </div>
                    <div class="dash__kpiText">
                      <div class="dash__kpiValueRow">
                        <span class="dash__kpiValue">150.00</span>
                        <span class="dash__kpiUnit">DV</span>
                      </div>
                      <div class="dash__kpiLabel">Бонус «Баланс»</div>
                    </div>
                  </div>

                  <div class="dash__kpiRow">
                    <div class="dash__kpiIcon dash__kpiIcon--violet">
                      <img src="<?php echo get_template_directory_uri(); ?>/img/icons/coins-2-plus.svg" alt="" />
                    </div>
                    <div class="dash__kpiText">
                      <div class="dash__kpiValueRow">
                        <span class="dash__kpiValue">150.00</span>
                        <span class="dash__kpiUnit">DV</span>
                      </div>
                      <div class="dash__kpiLabel">Бонус «Роста»</div>
                    </div>
                  </div>

                  <div class="dash__kpiRow">
                    <div class="dash__kpiIcon dash__kpiIcon--cyan">
                      <img src="<?php echo get_template_directory_uri(); ?>/img/icons/coins.svg" alt="" />
                    </div>
                    <div class="dash__kpiText">
                      <div class="dash__kpiValueRow">
                        <span class="dash__kpiValue">150.00</span>
                        <span class="dash__kpiUnit">DV</span>
                      </div>
                      <div class="dash__kpiLabel">«Глобальный» бонус</div>
                    </div>
                  </div>

                  <div class="dash__kpiRow">
                    <div class="dash__kpiIcon dash__kpiIcon--lilac">
                      <img src="<?php echo get_template_directory_uri(); ?>/img/icons/coins-2.svg" alt="" />
                    </div>
                    <div class="dash__kpiText">
                      <div class="dash__kpiValueRow">
                        <span class="dash__kpiValue">150.00</span>
                        <span class="dash__kpiUnit">DV</span>
                      </div>
                      <div class="dash__kpiLabel">«Представительский» бонус</div>
                    </div>
                  </div>
                </div>

                <div class="dash__consultant">
                  <div class="dash__consultantHead">Ваш консультант</div>

                  <div class="dash__consultantBody">
                    <div class="dash__consultantBadge">
                      <img class="dash__rankTop" src="<?php echo get_template_directory_uri(); ?>/img/rank_top.png" alt="" />
                      <img class="dash__rankMid" src="<?php echo get_template_directory_uri(); ?>/img/rank_mid.png" alt="" />
                      <img class="dash__avatar" src="<?php echo get_template_directory_uri(); ?>/img/avatar.jpg" alt="" />
                      <img class="dash__rankLabel" src="<?php echo get_template_directory_uri(); ?>/img/rank_label.png" alt="" />
                      <div class="dash__rankText">Бронзовый лидер</div>
                    </div>

                    <div class="dash__consultantInfo">
                      <div class="dash__consultantName">Еремина Мария</div>
                      <div class="dash__consultantId">ID: 007-4356000</div>
                    </div>
                  </div>

                  <div class="dash__consultantActions">
                    <a class="dash__social dash__social--tg" href="#">
                      <img src="<?php echo get_template_directory_uri(); ?>/img/icons/tg.svg" alt="" />
                    </a>
                    <a class="dash__social dash__social--vk" href="#">
                      <img src="<?php echo get_template_directory_uri(); ?>/img/icons/vk.svg" alt="" />
                    </a>
                    <a class="dash__social dash__social--mail" href="#">
                      <img src="<?php echo get_template_directory_uri(); ?>/img/icons/mail.svg" alt="" />
                    </a>
                    <a class="dash__social dash__social--phone" href="#">
                      <img src="<?php echo get_template_directory_uri(); ?>/img/icons/phone.svg" alt="" />
                    </a>
                  </div>
                </div>
              </div>
            </aside>
          </div>
        </div>
      </div>
    </div>
    <script>
      const sectionLinks = document.querySelectorAll('.dash__navItem[data-target]');
      const sections = document.querySelectorAll('.dash__section');

      const root = document.querySelector('.dash');

      const setActiveSection = (targetId) => {
        sections.forEach((section) => {
          const isActive = section.dataset.section === targetId;
          section.classList.toggle('is-active', isActive);
          section.setAttribute('aria-hidden', String(!isActive));
        });

        if (root) {
          root.dataset.activeSection = targetId;
        }

        sectionLinks.forEach((link) => {
          link.classList.toggle('is-active', link.dataset.target === targetId);
        });
      };

      sectionLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
          event.preventDefault();
          setActiveSection(link.dataset.target);
        });
      });
    </script>
  </body>
</html>