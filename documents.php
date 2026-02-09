<?php
require_once 'config/config.php';

$pageTitle = 'Документы';
include 'includes/header.php';
?>

<section class="documents-section">
    <div class="container">
        <h1 class="page-title">Документы</h1>
        
        <div class="documents-list">
            <div class="document-item">
                <h3>Пользовательское соглашение</h3>
                <p>Условия использования сайта и сервисов</p>
                <a href="terms.php" class="btn-secondary">Читать</a>
            </div>
            
            <div class="document-item">
                <h3>Политика конфиденциальности</h3>
                <p>Как мы обрабатываем персональные данные</p>
                <a href="privacy.php" class="btn-secondary">Читать</a>
            </div>
            
            <div class="document-item">
                <h3>Партнерское соглашение</h3>
                <p>Условия партнерской программы</p>
                <a href="#" class="btn-secondary">Скачать PDF</a>
            </div>
            
            <div class="document-item">
                <h3>Правила возврата товара</h3>
                <p>Условия возврата и обмена товаров</p>
                <a href="#" class="btn-secondary">Читать</a>
            </div>
            
            <div class="document-item">
                <h3>Лицензии и сертификаты</h3>
                <p>Документы на продукцию</p>
                <a href="#" class="btn-secondary">Смотреть</a>
            </div>
        </div>
    </div>
</section>

<?php
include 'includes/footer.php';
?>






