<?php
// Render article block based on template type
$blockContent = $content;
$templateType = $templateType;
?>

<?php if ($templateType === 'title_text'): ?>
    <div class="article-block-render article-block-title-text">
        <?php if (!empty($blockContent['title'])): ?>
            <h2 class="article-block-title"><?php echo htmlspecialchars($blockContent['title']); ?></h2>
        <?php endif; ?>
        <?php if (!empty($blockContent['text'])): ?>
            <div class="article-block-text"><?php echo nl2br(htmlspecialchars($blockContent['text'])); ?></div>
        <?php endif; ?>
    </div>

<?php elseif ($templateType === 'title_text_image_left'): ?>
    <div class="article-block-render article-block-title-text-image">
        <div class="article-block-content-wrapper" style="display: flex; gap: 2rem; align-items: flex-start;">
            <div class="article-block-image" style="flex: 0 0 300px;">
                <?php if (!empty($blockContent['image'])): ?>
                    <img src="<?php echo BASE_URL; ?>uploads/articles/<?php echo htmlspecialchars($blockContent['image']); ?>" alt="<?php echo htmlspecialchars($blockContent['title'] ?? ''); ?>" style="width: 100%; border-radius: 8px;">
                <?php endif; ?>
            </div>
            <div class="article-block-text-content" style="flex: 1;">
                <?php if (!empty($blockContent['title'])): ?>
                    <h2 class="article-block-title"><?php echo htmlspecialchars($blockContent['title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($blockContent['text'])): ?>
                    <div class="article-block-text"><?php echo nl2br(htmlspecialchars($blockContent['text'])); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php elseif ($templateType === 'title_text_image_right'): ?>
    <div class="article-block-render article-block-title-text-image">
        <div class="article-block-content-wrapper" style="display: flex; gap: 2rem; align-items: flex-start; flex-direction: row-reverse;">
            <div class="article-block-image" style="flex: 0 0 300px;">
                <?php if (!empty($blockContent['image'])): ?>
                    <img src="<?php echo BASE_URL; ?>uploads/articles/<?php echo htmlspecialchars($blockContent['image']); ?>" alt="<?php echo htmlspecialchars($blockContent['title'] ?? ''); ?>" style="width: 100%; border-radius: 8px;">
                <?php endif; ?>
            </div>
            <div class="article-block-text-content" style="flex: 1;">
                <?php if (!empty($blockContent['title'])): ?>
                    <h2 class="article-block-title"><?php echo htmlspecialchars($blockContent['title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($blockContent['text'])): ?>
                    <div class="article-block-text"><?php echo nl2br(htmlspecialchars($blockContent['text'])); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php elseif ($templateType === 'title_text_button'): ?>
    <div class="article-block-render article-block-title-text-button">
        <?php if (!empty($blockContent['title'])): ?>
            <h2 class="article-block-title"><?php echo htmlspecialchars($blockContent['title']); ?></h2>
        <?php endif; ?>
        <?php if (!empty($blockContent['text'])): ?>
            <div class="article-block-text"><?php echo nl2br(htmlspecialchars($blockContent['text'])); ?></div>
        <?php endif; ?>
        <?php if (!empty($blockContent['button_text']) && !empty($blockContent['button_link'])): ?>
            <div class="article-block-button-wrapper" style="margin-top: 1.5rem;">
                <a href="<?php echo htmlspecialchars($blockContent['button_link']); ?>" class="btn-primary"><?php echo htmlspecialchars($blockContent['button_text']); ?></a>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($templateType === 'title_text_image_button'): ?>
    <div class="article-block-render article-block-title-text-image-button">
        <?php if (!empty($blockContent['title'])): ?>
            <h2 class="article-block-title"><?php echo htmlspecialchars($blockContent['title']); ?></h2>
        <?php endif; ?>
        <?php if (!empty($blockContent['text'])): ?>
            <div class="article-block-text"><?php echo nl2br(htmlspecialchars($blockContent['text'])); ?></div>
        <?php endif; ?>
        <?php if (!empty($blockContent['image'])): ?>
            <div class="article-block-image" style="margin: 1.5rem 0;">
                <img src="<?php echo BASE_URL; ?>uploads/articles/<?php echo htmlspecialchars($blockContent['image']); ?>" alt="<?php echo htmlspecialchars($blockContent['title'] ?? ''); ?>" style="width: 100%; max-width: 600px; border-radius: 8px;">
            </div>
        <?php endif; ?>
        <?php if (!empty($blockContent['button_text']) && !empty($blockContent['button_link'])): ?>
            <div class="article-block-button-wrapper" style="margin-top: 1.5rem;">
                <a href="<?php echo htmlspecialchars($blockContent['button_link']); ?>" class="btn-primary"><?php echo htmlspecialchars($blockContent['button_text']); ?></a>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($templateType === 'title_list'): ?>
    <div class="article-block-render article-block-title-list">
        <?php if (!empty($blockContent['title'])): ?>
            <h2 class="article-block-title"><?php echo htmlspecialchars($blockContent['title']); ?></h2>
        <?php endif; ?>
        <?php if (!empty($blockContent['items']) && is_array($blockContent['items'])): ?>
            <ul class="article-block-list" style="list-style: none; padding: 0; margin: 1rem 0;">
                <?php foreach ($blockContent['items'] as $item): ?>
                    <li style="padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0; display: flex; align-items: flex-start; gap: 0.75rem;">
                        <span style="color: #667eea; font-weight: bold; flex-shrink: 0;">•</span>
                        <span><?php echo htmlspecialchars($item); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

<?php elseif ($templateType === 'two_columns'): ?>
    <div class="article-block-render article-block-two-columns">
        <?php if (!empty($blockContent['title'])): ?>
            <h2 class="article-block-title"><?php echo htmlspecialchars($blockContent['title']); ?></h2>
        <?php endif; ?>
        <div class="article-block-columns" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.5rem;">
            <div class="article-block-column">
                <?php if (!empty($blockContent['left_column'])): ?>
                    <div class="article-block-text"><?php echo nl2br(htmlspecialchars($blockContent['left_column'])); ?></div>
                <?php endif; ?>
            </div>
            <div class="article-block-column">
                <?php if (!empty($blockContent['right_column'])): ?>
                    <div class="article-block-text"><?php echo nl2br(htmlspecialchars($blockContent['right_column'])); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php elseif ($templateType === 'image_full'): ?>
    <div class="article-block-render article-block-image-full">
        <?php if (!empty($blockContent['image'])): ?>
            <img src="<?php echo BASE_URL; ?>uploads/articles/<?php echo htmlspecialchars($blockContent['image']); ?>" alt="" style="width: 100%; border-radius: 12px; margin: 2rem 0;">
        <?php endif; ?>
    </div>

<?php elseif ($templateType === 'text_only'): ?>
    <div class="article-block-render article-block-text-only">
        <?php if (!empty($blockContent['text'])): ?>
            <div class="article-block-text"><?php echo nl2br(htmlspecialchars($blockContent['text'])); ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>




