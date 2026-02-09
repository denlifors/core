    </main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section footer-logo-section">
                    <div class="footer-logo">
                        <img src="<?php echo ASSETS_PATH; ?>/images/logo.svg" alt="<?php echo SITE_NAME; ?>" class="footer-logo-img">
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Партнёрство</h3>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>partnership.php#bonuses">Бонусы</a></li>
                        <li><a href="<?php echo BASE_URL; ?>partnership.php#how-to-start">Как начать</a></li>
                        <li><a href="<?php echo BASE_URL; ?>partnership.php#join-form">Регистрация</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Контакты</h3>
                    <ul>
                        <li>свяжитесь с нами</li>
                        <li>+7 (800) 000-00-00</li>
                        <li>почта</li>
                        <li><a href="mailto:info@denlifors.ru">info@mail.ru</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Компания</h3>
                    <ul>
                        <li>г. Москва, ул. Новая Арбатская, д. 21, офис 508</li>
                        <li>ИНН</li>
                        <li>ОГРН</li>
                        <li>КПП</li>
                    </ul>
                </div>
                
                <div class="footer-section footer-actions">
                    <a href="<?php echo BASE_URL; ?>documents.php" class="btn-certificates">
                        <span>Сертификаты</span>
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" class="btn-certificates-arrow">
                            <rect width="28" height="28" rx="14" fill="white"/>
                            <path d="M9.46289 18.5374L17.8892 10.1111M10.7593 9.46289H17.6208C18.127 9.46289 18.5374 9.8733 18.5374 10.3796V17.2411" stroke="#F34CFF" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <div class="social-links">
                        <a href="#" class="social-link social-whatsapp" title="WhatsApp">
                            <img src="<?php echo ASSETS_PATH; ?>/images/whatsapp-icon.svg" alt="WhatsApp" class="social-icon-img">
                        </a>
                        <a href="#" class="social-link social-telegram" title="Telegram">
                            <img src="<?php echo ASSETS_PATH; ?>/images/telegram-icon.svg" alt="Telegram" class="social-icon-img">
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <div class="copyright">© Copyright 2025</div>
                    <div class="footer-links">
                        <a href="<?php echo BASE_URL; ?>privacy.php">Политика конфиденциальности</a>
                        <a href="<?php echo BASE_URL; ?>terms.php">Пользовательское соглашение</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="<?php echo ASSETS_PATH; ?>/js/main.js"></script>
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo ASSETS_PATH; ?>/js/<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
