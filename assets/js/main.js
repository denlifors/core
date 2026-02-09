// Main JavaScript file

// Update cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    initHeaderScroll();
    initHeroSlider();
});

// Header scroll hide/show
function initHeaderScroll() {
    const header = document.querySelector('.header');
    if (!header) return;
    
    let lastScrollTop = 0;
    let scrollThreshold = 50; // Minimum scroll distance before hiding
    let ticking = false;
    
    // Add initial visible class
    header.classList.add('header-visible');
    
    function updateHeader() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop < scrollThreshold) {
            // Near top - always show
            header.classList.remove('header-hidden');
            header.classList.add('header-visible');
        } else if (scrollTop > lastScrollTop) {
            // Scrolling down - hide header
            header.classList.remove('header-visible');
            header.classList.add('header-hidden');
        } else {
            // Scrolling up - show header
            header.classList.remove('header-hidden');
            header.classList.add('header-visible');
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        ticking = false;
    }
    
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(updateHeader);
            ticking = true;
        }
    }, { passive: true });
}

// Add to cart functionality
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-add-to-cart') || e.target.closest('.btn-add-to-cart')) {
        e.preventDefault();
        const button = e.target.classList.contains('btn-add-to-cart') ? e.target : e.target.closest('.btn-add-to-cart');
        const productId = button.getAttribute('data-product-id');
        
        if (productId) {
            addToCart(productId);
        }
    }
});

function addToCart(productId, quantity = 1) {
    fetch('api/cart-add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count badge
            const cartCountBadge = document.getElementById('cart-count-badge');
            if (cartCountBadge) {
                const count = data.cart_count || 0;
                if (count > 0) {
                    cartCountBadge.textContent = count;
                    cartCountBadge.style.display = 'flex';
                } else {
                    cartCountBadge.style.display = 'none';
                }
            } else {
                updateCartCount();
            }
            // Show success message
            showNotification('Товар добавлен в корзину', 'success');
        } else {
            showNotification(data.error || 'Ошибка при добавлении товара', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Ошибка при добавлении товара', 'error');
    });
}

function updateCartCount() {
    fetch('api/cart-count.php')
        .then(response => response.json())
        .then(data => {
            const cartCountBadge = document.getElementById('cart-count-badge');
            const count = data.count || 0;
            if (cartCountBadge) {
                if (count > 0) {
                    cartCountBadge.textContent = count;
                    cartCountBadge.style.display = 'flex';
                } else {
                    cartCountBadge.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function showNotification(message, type = 'success') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#48bb78' : '#f56565'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Hero slider - простой и рабочий вариант
function initHeroSlider() {
    const container = document.getElementById('hero-slides-container');
    const wrapper = container ? container.parentElement : null;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    
    if (!container || !wrapper || slides.length === 0) return;
    
    let currentSlide = 0;
    let autoSlideInterval = null;
    const totalSlides = slides.length;
    
    // Функция для обновления размеров
    function updateSizes() {
        // Получаем реальную ширину wrapper (с учетом ограничения контейнера)
        const wrapperWidth = wrapper.offsetWidth;
        
        // Устанавливаем ширину контейнера = количество слайдов * ширина wrapper
        container.style.width = `${totalSlides * wrapperWidth}px`;
        
        // Убеждаемся, что каждый слайд имеет ширину wrapper
        slides.forEach((slide) => {
            slide.style.width = `${wrapperWidth}px`;
            slide.style.flexShrink = '0';
            slide.style.flexBasis = `${wrapperWidth}px`;
        });
    }
    
    // Функция показа слайда
    function goToSlide(index) {
        if (index < 0) index = totalSlides - 1;
        if (index >= totalSlides) index = 0;
        
        currentSlide = index;
        const wrapperWidth = wrapper.offsetWidth;
        const offset = -currentSlide * wrapperWidth;
        container.style.transform = `translateX(${offset}px)`;
        
        // Обновляем индикаторы
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === currentSlide);
        });
    }
    
    // Обработчики для индикаторов
    dots.forEach((dot, index) => {
        dot.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            goToSlide(index);
            resetAutoSlide();
        });
    });
    
    // Автоматическая смена слайдов
    function resetAutoSlide() {
        if (autoSlideInterval) {
            clearInterval(autoSlideInterval);
        }
        if (totalSlides > 1) {
            autoSlideInterval = setInterval(function() {
                goToSlide(currentSlide + 1);
            }, 5000);
        }
    }
    
    // Обработка изменения размера окна
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            updateSizes();
            goToSlide(currentSlide);
        }, 250);
    });
    
    // Инициализация
    updateSizes();
    goToSlide(0);
    resetAutoSlide();
}
