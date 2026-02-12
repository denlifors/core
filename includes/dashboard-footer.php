        </main>
        <aside class="dash__profile">
          <div class="dash__profileTop">
            <div class="dash__user">
              <div class="dash__userBadge">
                <img class="dash__rankTop" src="<?php echo $assetsImg; ?>/rank_top.png" alt="" />
                <img class="dash__rankMid" src="<?php echo $assetsImg; ?>/rank_mid.png" alt="" />
                <img class="dash__avatar" src="<?php echo $assetsImg; ?>/avatar.jpg" alt="" />
                <img class="dash__rankLabel" src="<?php echo $assetsImg; ?>/rank_label.png" alt="" />
                <div class="dash__rankText"><?php echo htmlspecialchars($currentUserRankLabel ?? 'Партнёр'); ?></div>
              </div>
              <div class="dash__userNameRow">
                <div class="dash__userName"><?php echo htmlspecialchars($fullName); ?></div>
                <span class="dash__onlineDot" aria-hidden="true"></span>
              </div>
              <div class="dash__userId">ID: <?php echo htmlspecialchars($registrationId ?: $userData['id']); ?></div>
            </div>

            <div class="dash__partnerLink">
              <div class="dash__partnerLinkLabel">Партнерская ссылка:</div>
              <div class="dash__partnerLinkRow">
                <div class="dash__partnerLinkValue">
                  <?php echo $partnerLink ? htmlspecialchars($partnerLink) : 'Ссылка появится после активации'; ?>
                </div>
                <div class="dash__partnerLinkIcons">
                  <button class="dash__miniIcon" type="button" aria-label="Копировать" data-copy-link="<?php echo htmlspecialchars($partnerLink); ?>">
                    <img src="<?php echo $assetsImg; ?>/icons/copy.svg" alt="" />
                  </button>
                  <button class="dash__miniIcon" type="button" aria-label="QR">
                    <img src="<?php echo $assetsImg; ?>/icons/qr.svg" alt="" />
                  </button>
                  <button class="dash__miniIcon" type="button" aria-label="Поделиться">
                    <img src="<?php echo $assetsImg; ?>/icons/share.svg" alt="" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="dash__profileMid">
            <div class="dash__kpis">
              <div class="dash__kpiRow">
                <div class="dash__kpiIcon dash__kpiIcon--cyan">
                  <img src="<?php echo $assetsImg; ?>/icons/credit-card.svg" alt="" />
                </div>
                <div class="dash__kpiText">
                  <div class="dash__kpiValueRow is-bold">
                    <span class="dash__kpiValue"><?php echo number_format((float)($dashMetrics['personalMonthDv'] ?? 0), 2, '.', ''); ?></span>
                    <span class="dash__kpiUnit">DV</span>
                  </div>
                  <div class="dash__kpiLabel">Общая сумма DV</div>
                </div>
              </div>

              <div class="dash__kpiRow">
                <div class="dash__kpiIcon dash__kpiIcon--magenta">
                  <img src="<?php echo $assetsImg; ?>/icons/coins 1 +.svg" alt="" />
                </div>
                <div class="dash__kpiText">
                  <div class="dash__kpiValueRow">
                    <span class="dash__kpiValue"><?php echo number_format((float)($dashBonusCashbackDv ?? 0), 2, '.', ''); ?></span>
                    <span class="dash__kpiUnit">DV</span>
                  </div>
                  <div class="dash__kpiLabel">«Накопительный» кешбэк</div>
                </div>
              </div>

              <div class="dash__kpiRow">
                <div class="dash__kpiIcon dash__kpiIcon--gold">
                  <img src="<?php echo $assetsImg; ?>/icons/coins 2 (1).svg" alt="" />
                </div>
                <div class="dash__kpiText">
                  <div class="dash__kpiValueRow">
                    <span class="dash__kpiValue"><?php echo number_format((float)($dashBonusInfluenceDv ?? 0), 2, '.', ''); ?></span>
                    <span class="dash__kpiUnit">DV</span>
                  </div>
                  <div class="dash__kpiLabel">Бонус «3 круга влияния»</div>
                </div>
              </div>

              <div class="dash__kpiRow">
                <div class="dash__kpiIcon dash__kpiIcon--green">
                  <img src="<?php echo $assetsImg; ?>/icons/coins 2.svg" alt="" />
                </div>
                <div class="dash__kpiText">
                  <div class="dash__kpiValueRow">
                    <span class="dash__kpiValue"><?php echo number_format((float)($dashBonusBalanceDv ?? 0), 2, '.', ''); ?></span>
                    <span class="dash__kpiUnit">DV</span>
                  </div>
                  <div class="dash__kpiLabel">Бонус «Баланс»</div>
                </div>
              </div>

              <div class="dash__kpiRow">
                <div class="dash__kpiIcon dash__kpiIcon--violet">
                  <img src="<?php echo $assetsImg; ?>/icons/coins 2 +.svg" alt="" />
                </div>
                <div class="dash__kpiText">
                  <div class="dash__kpiValueRow">
                    <span class="dash__kpiValue"><?php echo number_format((float)($dashBonusGrowthDv ?? 0), 2, '.', ''); ?></span>
                    <span class="dash__kpiUnit">DV</span>
                  </div>
                  <div class="dash__kpiLabel">Бонус «Роста»</div>
                </div>
              </div>

              <div class="dash__kpiRow">
                <div class="dash__kpiIcon dash__kpiIcon--cyan">
                  <img src="<?php echo $assetsImg; ?>/icons/coins.svg" alt="" />
                </div>
                <div class="dash__kpiText">
                  <div class="dash__kpiValueRow">
                    <span class="dash__kpiValue"><?php echo number_format((float)($dashBonusGlobalDv ?? 0), 2, '.', ''); ?></span>
                    <span class="dash__kpiUnit">DV</span>
                  </div>
                  <div class="dash__kpiLabel">«Глобальный» бонус</div>
                </div>
              </div>

              <div class="dash__kpiRow">
                <div class="dash__kpiIcon dash__kpiIcon--lilac">
                  <img src="<?php echo $assetsImg; ?>/icons/coins 2.svg" alt="" />
                </div>
                <div class="dash__kpiText">
                  <div class="dash__kpiValueRow">
                    <span class="dash__kpiValue"><?php echo number_format((float)($dashBonusRepresentativeDv ?? 0), 2, '.', ''); ?></span>
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
                  <img class="dash__rankTop" src="<?php echo $assetsImg; ?>/rank_top.png" alt="" />
                  <img class="dash__rankMid" src="<?php echo $assetsImg; ?>/rank_mid.png" alt="" />
                  <img class="dash__avatar" src="<?php echo $assetsImg; ?>/avatar.jpg" alt="" />
                  <img class="dash__rankLabel" src="<?php echo $assetsImg; ?>/rank_label.png" alt="" />
                  <div class="dash__rankText"><?php echo htmlspecialchars($consultantRankLabel ?? 'Партнёр'); ?></div>
                </div>
                <div class="dash__consultantInfo">
                  <div class="dash__consultantName"><?php echo htmlspecialchars($consultantName); ?></div>
                  <div class="dash__consultantId">ID: <?php echo htmlspecialchars($consultantId); ?></div>
                </div>
              </div>
              <div class="dash__consultantActions">
                <a class="dash__social dash__social--tg" href="#">
                  <img src="<?php echo $assetsImg; ?>/icons/tg.svg" alt="" />
                </a>
                <a class="dash__social dash__social--vk" href="#">
                  <img src="<?php echo $assetsImg; ?>/icons/vk.svg" alt="" />
                </a>
                <a class="dash__social dash__social--mail" href="#">
                  <img src="<?php echo $assetsImg; ?>/icons/mail.svg" alt="" />
                </a>
                <a class="dash__social dash__social--phone" href="#">
                  <img src="<?php echo $assetsImg; ?>/icons/phone.svg" alt="" />
                </a>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</div>
</div>
<script>
  document.querySelectorAll('[data-copy-link]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const link = btn.getAttribute('data-copy-link');
      if (!link) return;
      try {
        await navigator.clipboard.writeText(link);
        btn.classList.add('is-copied');
        setTimeout(() => btn.classList.remove('is-copied'), 1200);
      } catch (e) {
        const input = document.createElement('input');
        input.value = link;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
      }
    });
  });
</script>
</body>
</html>
