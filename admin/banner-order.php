<?php
require_once '../config/admin-config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$db = getDBConnection();

// Handle order update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_order') {
        $order = json_decode($_POST['order'], true);
        
        if (is_array($order)) {
            // Update sort_order for each banner
            foreach ($order as $position => $bannerId) {
                $stmt = $db->prepare("UPDATE banners SET sort_order = :sort_order WHERE id = :id");
                $stmt->execute([
                    ':sort_order' => (int)$position + 1,
                    ':id' => (int)$bannerId
                ]);
            }
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid order data']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'move') {
        $bannerId = (int)$_POST['banner_id'];
        $direction = $_POST['direction']; // 'up' or 'down'
        
        // Get current banner order
        $stmt = $db->prepare("SELECT sort_order FROM banners WHERE id = :id");
        $stmt->execute([':id' => $bannerId]);
        $current = $stmt->fetch();
        
        if (!$current) {
            echo json_encode(['success' => false, 'error' => 'Banner not found']);
            exit;
        }
        
        $currentOrder = $current['sort_order'];
        $newOrder = $direction === 'up' ? $currentOrder - 1 : $currentOrder + 1;
        
        // Swap with banner at new position
        $stmt = $db->prepare("SELECT id FROM banners WHERE sort_order = :order LIMIT 1");
        $stmt->execute([':order' => $newOrder]);
        $swapBanner = $stmt->fetch();
        
        if ($swapBanner) {
            // Swap orders
            $db->beginTransaction();
            try {
                $db->prepare("UPDATE banners SET sort_order = 0 WHERE id = :id")->execute([':id' => $bannerId]);
                $db->prepare("UPDATE banners SET sort_order = :new_order WHERE id = :id")->execute([':new_order' => $currentOrder, ':id' => $swapBanner['id']]);
                $db->prepare("UPDATE banners SET sort_order = :new_order WHERE id = :id")->execute([':new_order' => $newOrder, ':id' => $bannerId]);
                $db->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Cannot move banner']);
        }
        exit;
    }
}

// Get all banners ordered by sort_order
$stmt = $db->query("SELECT * FROM banners ORDER BY sort_order ASC, created_at DESC");
$banners = $stmt->fetchAll();

$pageTitle = 'Порядок баннеров';
include '../includes/admin-header.php';
?>

<div class="admin-page">
    <div class="admin-page-header">
        <h1>Порядок баннеров</h1>
        <div style="display: flex; gap: 1rem;">
            <a href="banners.php" class="btn-secondary">Назад к баннерам</a>
        </div>
    </div>
    
    <div class="form-section">
        <h2>Управление порядком баннеров</h2>
        <p style="color: var(--text-light); margin-bottom: 2rem;">
            Баннеры отображаются в порядке списка. Первый баннер в списке будет главным. Используйте кнопки для изменения порядка.
        </p>
        
        <div class="banner-order-list" id="banner-order-list">
            <?php if (empty($banners)): ?>
                <p style="text-align: center; padding: 2rem; color: var(--text-light);">Нет баннеров для отображения</p>
            <?php else: ?>
                <?php foreach ($banners as $index => $banner): ?>
                    <div class="banner-order-item" data-banner-id="<?php echo $banner['id']; ?>" data-order="<?php echo $banner['sort_order'] ?? ($index + 1); ?>">
                        <div class="banner-order-number">
                            <span class="order-number"><?php echo $index + 1; ?></span>
                        </div>
                        <div class="banner-order-preview">
                            <?php if ($banner['image']): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/banners/<?php echo htmlspecialchars($banner['image']); ?>" 
                                     alt="Banner" 
                                     class="banner-preview-img">
                            <?php else: ?>
                                <div class="banner-preview-placeholder">Нет изображения</div>
                            <?php endif; ?>
                            <div class="banner-order-info">
                                <div class="banner-order-title">
                                    <strong><?php echo htmlspecialchars($banner['title'] ?: 'Баннер #' . $banner['id']); ?></strong>
                                </div>
                                <div class="banner-order-meta">
                                    <?php 
                                    $pageNames = [
                                        'home' => 'Главная',
                                        'partnership' => 'Партнёры',
                                        'catalog' => 'Каталог',
                                        'all' => 'Все страницы'
                                    ];
                                    $pageName = $pageNames[$banner['page'] ?? 'home'] ?? 'Главная';
                                    ?>
                                    <span class="banner-page"><?php echo htmlspecialchars($pageName); ?></span>
                                    <span class="banner-status status-<?php echo $banner['status']; ?>">
                                        <?php echo $banner['status'] === 'active' ? 'Активен' : 'Неактивен'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="banner-order-actions">
                            <button type="button" class="btn-move btn-move-up" 
                                    data-banner-id="<?php echo $banner['id']; ?>" 
                                    data-direction="up"
                                    <?php echo $index === 0 ? 'disabled' : ''; ?>
                                    title="Переместить вверх">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 15l-6-6-6 6"/>
                                </svg>
                            </button>
                            <button type="button" class="btn-move btn-move-down" 
                                    data-banner-id="<?php echo $banner['id']; ?>" 
                                    data-direction="down"
                                    <?php echo $index === count($banners) - 1 ? 'disabled' : ''; ?>
                                    title="Переместить вниз">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.banner-order-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
}

.banner-order-item {
    display: grid;
    grid-template-columns: 60px 1fr auto;
    gap: 1.5rem;
    align-items: center;
    padding: 1.5rem;
    background: var(--white);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.banner-order-item:hover {
    border-color: var(--primary-color);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}

.banner-order-item.moving {
    opacity: 0.5;
    transform: scale(0.98);
}

.banner-order-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    color: white;
    font-weight: 700;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.banner-order-preview {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex: 1;
}

.banner-preview-img {
    width: 120px;
    height: 75px;
    object-fit: cover;
    border-radius: 8px;
    flex-shrink: 0;
}

.banner-preview-placeholder {
    width: 120px;
    height: 75px;
    background: var(--bg-light);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-light);
    font-size: 0.85rem;
    flex-shrink: 0;
}

.banner-order-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex: 1;
}

.banner-order-title {
    font-size: 1.1rem;
    color: var(--text-dark);
}

.banner-order-meta {
    display: flex;
    gap: 1rem;
    align-items: center;
    font-size: 0.9rem;
}

.banner-page {
    color: var(--text-light);
    padding: 0.25rem 0.75rem;
    background: var(--bg-light);
    border-radius: 6px;
}

.banner-status {
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
}

.banner-status.status-active {
    background: #d4edda;
    color: #155724;
}

.banner-status.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.banner-order-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.btn-move {
    width: 40px;
    height: 40px;
    border: 2px solid var(--border-color);
    background: var(--white);
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    color: var(--text-dark);
}

.btn-move:hover:not(:disabled) {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

.btn-move:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .banner-order-item {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .banner-order-number {
        margin: 0 auto;
    }
    
    .banner-order-actions {
        flex-direction: row;
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderList = document.getElementById('banner-order-list');
    const moveButtons = document.querySelectorAll('.btn-move');
    
    moveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const bannerId = this.getAttribute('data-banner-id');
            const direction = this.getAttribute('data-direction');
            const item = this.closest('.banner-order-item');
            
            if (this.disabled) return;
            
            // Visual feedback
            item.classList.add('moving');
            
            // Make AJAX request
            const formData = new FormData();
            formData.append('action', 'move');
            formData.append('banner_id', bannerId);
            formData.append('direction', direction);
            
            fetch('banner-order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show new order
                    window.location.reload();
                } else {
                    alert('Ошибка: ' + (data.error || 'Не удалось изменить порядок'));
                    item.classList.remove('moving');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при изменении порядка');
                item.classList.remove('moving');
            });
        });
    });
});
</script>

<?php
include '../includes/admin-footer.php';
?>
