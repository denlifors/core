<?php
require_once 'config/config.php';

$db = getDBConnection();

// Get FAQ from database or use default
$stmt = $db->query("SELECT * FROM pages WHERE slug = 'faq' AND status = 'published' LIMIT 1");
$faqPage = $stmt->fetch();

$faqs = [
    [
        'question' => 'Как оформить заказ?',
        'answer' => 'Выберите товары в каталоге, добавьте их в корзину и перейдите к оформлению заказа. Заполните контактную информацию и адрес доставки, выберите способ оплаты и подтвердите заказ.'
    ],
    [
        'question' => 'Какие способы оплаты доступны?',
        'answer' => 'Мы принимаем оплату банковскими картами, наложенным платежом при получении и онлайн-платежи через различные платежные системы.'
    ],
    [
        'question' => 'Как долго длится доставка?',
        'answer' => 'Срок доставки зависит от вашего региона. По Москве - 1-2 дня, по России - 3-7 рабочих дней. Точный срок доставки будет указан при оформлении заказа.'
    ],
    [
        'question' => 'Можно ли вернуть товар?',
        'answer' => 'Да, вы можете вернуть товар в течение 14 дней с момента покупки при условии сохранения товарного вида и упаковки. Возврат оформляется через ваш личный кабинет.'
    ],
    [
        'question' => 'Нужен ли рецепт для покупки БАДов?',
        'answer' => 'Нет, для покупки биологически активных добавок рецепт не требуется. Однако мы рекомендуем проконсультироваться с врачом перед применением.'
    ],
    [
        'question' => 'Как стать партнером?',
        'answer' => 'Для участия в партнерской программе перейдите на страницу "Партнерство" и заполните форму регистрации. Наш менеджер свяжется с вами для дальнейших инструкций.'
    ]
];

$pageTitle = 'Часто задаваемые вопросы';
include 'includes/header.php';
?>

<section class="faq-section">
    <div class="container">
        <h1 class="page-title">Часто задаваемые вопросы</h1>
        
        <div class="faq-list">
            <?php foreach ($faqs as $index => $faq): ?>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(<?php echo $index; ?>)">
                        <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer" id="faq-answer-<?php echo $index; ?>">
                        <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
function toggleFaq(index) {
    const answer = document.getElementById('faq-answer-' + index);
    const question = event.currentTarget;
    const toggle = question.querySelector('.faq-toggle');
    
    const isActive = answer.classList.contains('active');
    
    // Close all FAQs
    document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('.faq-toggle').forEach(t => t.textContent = '+');
    
    // Toggle current FAQ
    if (!isActive) {
        answer.classList.add('active');
        toggle.textContent = '−';
    }
}
</script>

<?php
include 'includes/footer.php';
?>






