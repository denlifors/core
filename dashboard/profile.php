<?php
$birthDateText = !empty($userData['birth_date']) ? date('d.m.Y', strtotime($userData['birth_date'])) : '—';
$phoneText = !empty($userData['phone']) ? $userData['phone'] : '—';
$emailText = !empty($userData['email']) ? $userData['email'] : '—';
?>
<div class="profile">
    <div class="profile__panel">
        <div class="profile__userBadge">
            <img class="profile__rankTop" src="<?php echo $assetsImg; ?>/rank_top.png" alt="" />
            <img class="profile__rankMid" src="<?php echo $assetsImg; ?>/rank_mid.png" alt="" />
            <img class="profile__avatar" src="<?php echo $assetsImg; ?>/avatar.jpg" alt="" />
            <img class="profile__rankLabel" src="<?php echo $assetsImg; ?>/rank_label.png" alt="" />
        </div>
        <div class="profile__name"><?php echo htmlspecialchars($fullName); ?></div>
        <div class="profile__regRow">
            <img class="profile__regIcon" src="<?php echo $assetsImg; ?>/icons/document-7.svg" alt="" />
            <span>Регистрационный номер: <?php echo htmlspecialchars($registrationId ?: $userData['id']); ?></span>
        </div>

        <div class="profile__divider"></div>

        <div class="profile__consultant">
            <div class="profile__consultantHead">Ваш консультант</div>
            <div class="profile__consultantBody">
                <div class="profile__consultantBadge">
                    <img class="profile__rankTop" src="<?php echo $assetsImg; ?>/rank_top.png" alt="" />
                    <img class="profile__rankMid" src="<?php echo $assetsImg; ?>/rank_mid.png" alt="" />
                    <img class="profile__avatar" src="<?php echo $assetsImg; ?>/avatar.jpg" alt="" />
                    <img class="profile__rankLabel" src="<?php echo $assetsImg; ?>/rank_label.png" alt="" />
                </div>
                <div class="profile__consultantInfo">
                    <div class="profile__consultantName">Данные появятся позже</div>
                    <div class="profile__consultantId">ID: —</div>
                </div>
            </div>
            <div class="profile__consultantActions">
                <a class="profile__social profile__social--tg" href="#">
                    <img src="<?php echo $assetsImg; ?>/icons/tg.svg" alt="" />
                </a>
                <a class="profile__social profile__social--vk" href="#">
                    <img src="<?php echo $assetsImg; ?>/icons/vk.svg" alt="" />
                </a>
                <a class="profile__social profile__social--mail" href="#">
                    <img src="<?php echo $assetsImg; ?>/icons/mail.svg" alt="" />
                </a>
                <a class="profile__social profile__social--phone" href="#">
                    <img src="<?php echo $assetsImg; ?>/icons/phone.svg" alt="" />
                </a>
            </div>
        </div>
    </div>

    <div class="profile__content">
        <div class="profile__title">Мои данные</div>

        <div class="profile__row profile__row--top">
            <div class="profile__card profile__card--half">
                <div class="profile__cardIcon profile__cardIcon--magenta">
                    <img src="<?php echo $assetsImg; ?>/icons/user-circle.svg" alt="" />
                </div>
                <div class="profile__cardText">
                    <div class="profile__cardLabel">Имя и фамилия</div>
                    <div class="profile__cardValue"><?php echo htmlspecialchars($fullName); ?></div>
                </div>
            </div>

            <div class="profile__card profile__card--half">
                <div class="profile__cardIcon profile__cardIcon--orange">
                    <img src="<?php echo $assetsImg; ?>/icons/calendar.svg" alt="" />
                </div>
                <div class="profile__cardText">
                    <div class="profile__cardLabel">Дата рождения</div>
                    <div class="profile__cardValue"><?php echo htmlspecialchars($birthDateText); ?></div>
                </div>
            </div>
        </div>

        <div class="profile__card profile__card--full profile__card--email">
            <div class="profile__cardIcon profile__cardIcon--green">
                <img src="<?php echo $assetsImg; ?>/icons/mail.svg" alt="" />
            </div>
            <div class="profile__cardText">
                <div class="profile__cardLabel">E-mail</div>
                <div class="profile__cardValue"><?php echo htmlspecialchars($emailText); ?></div>
            </div>
            <div class="profile__cardStatus profile__cardStatus--ok">Подтвержден</div>
        </div>

        <div class="profile__card profile__card--full profile__card--phone">
            <div class="profile__cardIcon profile__cardIcon--cyan">
                <img src="<?php echo $assetsImg; ?>/icons/phone.svg" alt="" />
            </div>
            <div class="profile__cardText">
                <div class="profile__cardLabel">Номер телефона</div>
                <div class="profile__cardValue"><?php echo htmlspecialchars($phoneText); ?></div>
            </div>
            <div class="profile__cardDivider"></div>
            <div class="profile__cardIcon profile__cardIcon--violet">
                <img src="<?php echo $assetsImg; ?>/icons/document-box.svg" alt="" />
            </div>
            <div class="profile__cardText">
                <div class="profile__cardLabel">Страна, город и адрес доставки</div>
                <div class="profile__cardValue">—</div>
            </div>
            <div class="profile__cardStatus profile__cardStatus--warn">Не подтвержден</div>
        </div>
    </div>
</div>


