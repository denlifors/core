            </div>
        </main>
    </div>
    
    <script>
    // Define BASE_URL for JavaScript
    window.BASE_URL = '<?php echo BASE_URL; ?>';
    
    // Search Modal
    document.addEventListener('DOMContentLoaded', function() {
        const searchToggle = document.getElementById('dashboard-search-toggle');
        const searchModal = document.getElementById('dashboard-search-modal');
        const searchClose = document.getElementById('dashboard-search-close');
        
        if (searchToggle && searchModal) {
            searchToggle.addEventListener('click', function() {
                searchModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                const input = searchModal.querySelector('.dashboard-search-input');
                if (input) {
                    setTimeout(() => input.focus(), 100);
                }
            });
        }
        
        if (searchClose && searchModal) {
            searchClose.addEventListener('click', function() {
                searchModal.style.display = 'none';
                document.body.style.overflow = '';
            });
        }
        
        if (searchModal) {
            searchModal.addEventListener('click', function(e) {
                if (e.target === searchModal) {
                    searchModal.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        }
        
        // Update cart count on load
        updateDashboardCartCount();
    });
    
    function updateDashboardCartCount() {
        const baseUrl = window.BASE_URL || '<?php echo BASE_URL; ?>';
        fetch(baseUrl + 'api/cart-count.php')
            .then(response => response.json())
            .then(data => {
                const cartCount = document.getElementById('dashboard-cart-count');
                const count = data.count || 0;
                if (cartCount) {
                    if (count > 0) {
                        cartCount.textContent = count;
                        cartCount.style.display = 'flex';
                    } else {
                        cartCount.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    // Global function for showing notifications
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `dashboard-notification dashboard-notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: ${type === 'success' ? '#48bb78' : '#f56565'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10001;
            animation: slideIn 0.3s ease;
            font-weight: 500;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    // Become Partner Modal
    <?php if (isset($isClient) && $isClient): ?>
    (function() {
        const becomePartnerBtn = document.getElementById('become-partner-btn');
        const becomePartnerModal = document.getElementById('become-partner-modal');
        const closeModal = document.getElementById('close-become-partner-modal');
        const cancelBtn = document.getElementById('become-partner-cancel');
        const submitBtn = document.getElementById('become-partner-submit');
        const checkboxes = document.querySelectorAll('.become-partner-doc-checkbox');
        
        if (becomePartnerBtn && becomePartnerModal) {
            becomePartnerBtn.addEventListener('click', function() {
                becomePartnerModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            });
        }
        
        function closeModalFunc() {
            if (becomePartnerModal) {
                becomePartnerModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }
        
        if (closeModal) {
            closeModal.addEventListener('click', closeModalFunc);
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModalFunc);
        }
        
        if (becomePartnerModal) {
            becomePartnerModal.addEventListener('click', function(e) {
                if (e.target === becomePartnerModal) {
                    closeModalFunc();
                }
            });
        }
        
        // Проверка всех чекбоксов
        function checkAllChecked() {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            if (submitBtn) {
                submitBtn.disabled = !allChecked;
            }
        }
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', checkAllChecked);
        });
        
        // Подписание документов
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                if (!allChecked) {
                    alert('Необходимо согласиться со всеми документами');
                    return;
                }
                
                if (!confirm('Вы уверены, что хотите стать партнёром? После подписания ваш статус изменится на "Партнёр".')) {
                    return;
                }
                
                const baseUrl = window.BASE_URL || '<?php echo BASE_URL; ?>';
                fetch(baseUrl + 'api/become-partner.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'become_partner'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Поздравляем! Теперь вы партнёр ДенЛиФорс');
                        location.reload();
                    } else {
                        alert('Ошибка: ' + (data.message || 'Не удалось изменить статус'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка при изменении статуса');
                });
            });
        }
    })();
    <?php endif; ?>
    </script>
</body>
</html>

