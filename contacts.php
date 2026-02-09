<?php
require_once 'config/config.php';

$pageTitle = 'Контакты';
include 'includes/header.php';
?>

<section class="contacts-hero">
    <div class="container">
        <div class="contacts-hero-content">
            <div class="contacts-hero-badge">
                <span>Связь с нами</span>
            </div>
            <h1>Свяжитесь с нами</h1>
            <p>Мы всегда рады помочь вам и ответить на ваши вопросы. Выберите удобный способ связи.</p>
        </div>
    </div>
    <div class="contacts-hero-decoration"></div>
</section>

<section class="contacts-section">
    <div class="container">
        <div class="contacts-grid">
            <div class="contact-card">
                <div class="contact-icon-wrapper gradient-blue">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </div>
                <h3>Телефон</h3>
                <p class="contact-main"><a href="tel:+78000000000">+7 (800) 000-00-00</a></p>
                <p class="contact-detail">Ежедневно с 9:00 до 21:00</p>
            </div>
            
            <div class="contact-card">
                <div class="contact-icon-wrapper gradient-pink">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </div>
                <h3>Email</h3>
                <p class="contact-main"><a href="mailto:info@denlifors.ru">info@denlifors.ru</a></p>
                <p class="contact-detail">Мы ответим в течение 24 часов</p>
            </div>
            
            <div class="contact-card">
                <div class="contact-icon-wrapper gradient-green">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
                <h3>Адрес</h3>
                <p class="contact-main">г. Москва, ул. Новая Арбатская, д. 21, офис 508</p>
                <p class="contact-detail">Приём по предварительной записи</p>
            </div>
        </div>
        
        <div class="contacts-layout">
            <div class="contacts-info-section">
                <h2>Реквизиты компании</h2>
                <div class="requisites-card">
                    <div class="requisite-item">
                        <span class="requisite-label">ИНН</span>
                        <span class="requisite-value">—</span>
                    </div>
                    <div class="requisite-item">
                        <span class="requisite-label">ОГРН</span>
                        <span class="requisite-value">—</span>
                    </div>
                    <div class="requisite-item">
                        <span class="requisite-label">КПП</span>
                        <span class="requisite-value">—</span>
                    </div>
                </div>
                
                <div class="social-contacts-section">
                    <h2>Мы в соцсетях</h2>
                    <div class="social-contacts-links">
                        <a href="#" class="social-contact-link social-whatsapp">
                            <img src="<?php echo ASSETS_PATH; ?>/images/whatsapp-icon.svg" alt="WhatsApp">
                            <span>WhatsApp</span>
                        </a>
                        <a href="#" class="social-contact-link social-telegram">
                            <img src="<?php echo ASSETS_PATH; ?>/images/telegram-icon.svg" alt="Telegram">
                            <span>Telegram</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="contact-form-wrapper">
                <h2>Напишите нам</h2>
                <p class="form-description">Заполните форму, и мы свяжемся с вами в ближайшее время</p>
                <form class="contact-form" method="POST" action="api/contact.php">
                    <div class="form-group">
                        <label>Ваше имя *</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="tel" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Сообщение *</label>
                        <textarea name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary btn-large">Отправить сообщение</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php
include 'includes/footer.php';
?>
