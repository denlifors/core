<?php
// This file is included for each block in the article
$blockIndex = $index ?? 0;
$blockType = $block['template_type'] ?? '';
$blockContent = $block['content'] ?? [];
$blockId = $block['id'] ?? 'new-' . $blockIndex;
?>

<div class="article-block" data-block-id="<?php echo $blockId; ?>" data-block-index="<?php echo $blockIndex; ?>">
    <div class="article-block-header">
        <div class="article-block-type">
            <?php
            $templateNames = [
                'title_text' => 'Заголовок + Текст',
                'title_text_image_left' => 'Заголовок + Текст + Изображение (слева)',
                'title_text_image_right' => 'Заголовок + Текст + Изображение (справа)',
                'title_text_button' => 'Заголовок + Текст + Кнопка',
                'title_text_image_button' => 'Заголовок + Текст + Изображение + Кнопка',
                'title_list' => 'Заголовок + Список',
                'two_columns' => 'Два столбца',
                'image_full' => 'Изображение на всю ширину',
                'text_only' => 'Только текст'
            ];
            echo $templateNames[$blockType] ?? 'Неизвестный шаблон';
            ?>
        </div>
        <div class="article-block-actions">
            <button type="button" class="btn-small move-block-up" title="Переместить вверх">↑</button>
            <button type="button" class="btn-small move-block-down" title="Переместить вниз">↓</button>
            <button type="button" class="btn-small btn-danger remove-block" title="Удалить блок">×</button>
        </div>
    </div>
    
    <div class="article-block-content">
        <input type="hidden" name="blocks[<?php echo $blockIndex; ?>][template_type]" value="<?php echo htmlspecialchars($blockType); ?>">
        
        <?php if ($blockType === 'title_text'): ?>
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="blocks[<?php echo $blockIndex; ?>][content][title]" value="<?php echo htmlspecialchars($blockContent['title'] ?? ''); ?>" placeholder="Введите заголовок">
            </div>
            <div class="form-group">
                <label>Текст</label>
                <textarea name="blocks[<?php echo $blockIndex; ?>][content][text]" rows="6" placeholder="Введите текст"><?php echo htmlspecialchars($blockContent['text'] ?? ''); ?></textarea>
            </div>
            
        <?php elseif ($blockType === 'title_text_image_left' || $blockType === 'title_text_image_right'): ?>
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="blocks[<?php echo $blockIndex; ?>][content][title]" value="<?php echo htmlspecialchars($blockContent['title'] ?? ''); ?>" placeholder="Введите заголовок">
            </div>
            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label>Текст</label>
                    <textarea name="blocks[<?php echo $blockIndex; ?>][content][text]" rows="6" placeholder="Введите текст"><?php echo htmlspecialchars($blockContent['text'] ?? ''); ?></textarea>
                </div>
                <div class="form-group" style="flex: 0 0 300px;">
                    <label>Изображение</label>
                    <div class="block-image-upload" data-block-index="<?php echo $blockIndex; ?>">
                        <?php if (!empty($blockContent['image'])): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/articles/<?php echo htmlspecialchars($blockContent['image']); ?>" alt="Block image" class="upload-preview">
                            <input type="hidden" name="blocks[<?php echo $blockIndex; ?>][content][image]" value="<?php echo htmlspecialchars($blockContent['image']); ?>">
                        <?php else: ?>
                            <div class="upload-placeholder">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <p>Нажмите для загрузки</p>
                            </div>
                            <input type="hidden" name="blocks[<?php echo $blockIndex; ?>][content][image]" value="">
                        <?php endif; ?>
                        <input type="file" accept="image/*" class="block-image-file" style="display:none;">
                    </div>
                </div>
            </div>
            
        <?php elseif ($blockType === 'title_text_button'): ?>
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="blocks[<?php echo $blockIndex; ?>][content][title]" value="<?php echo htmlspecialchars($blockContent['title'] ?? ''); ?>" placeholder="Введите заголовок">
            </div>
            <div class="form-group">
                <label>Текст</label>
                <textarea name="blocks[<?php echo $blockIndex; ?>][content][text]" rows="6" placeholder="Введите текст"><?php echo htmlspecialchars($blockContent['text'] ?? ''); ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label>Текст кнопки</label>
                    <input type="text" name="blocks[<?php echo $blockIndex; ?>][content][button_text]" value="<?php echo htmlspecialchars($blockContent['button_text'] ?? ''); ?>" placeholder="Например: Заказать">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Ссылка кнопки</label>
                    <input type="text" name="blocks[<?php echo $blockIndex; ?>][content][button_link]" value="<?php echo htmlspecialchars($blockContent['button_link'] ?? ''); ?>" placeholder="catalog.php или http://...">
                </div>
            </div>
            
        <?php elseif ($blockType === 'title_text_image_button'): ?>
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="blocks[<?php echo $blockIndex; ?>][content][title]" value="<?php echo htmlspecialchars($blockContent['title'] ?? ''); ?>" placeholder="Введите заголовок">
            </div>
            <div class="form-group">
                <label>Текст</label>
                <textarea name="blocks[<?php echo $blockIndex; ?>][content][text]" rows="6" placeholder="Введите текст"><?php echo htmlspecialchars($blockContent['text'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Изображение</label>
                <div class="block-image-upload" data-block-index="<?php echo $blockIndex; ?>">
                    <?php if (!empty($blockContent['image'])): ?>
                        <img src="<?php echo BASE_URL; ?>uploads/articles/<?php echo htmlspecialchars($blockContent['image']); ?>" alt="Block image" class="upload-preview">
                        <input type="hidden" name="blocks[<?php echo $blockIndex; ?>][content][image]" value="<?php echo htmlspecialchars($blockContent['image']); ?>">
                    <?php else: ?>
                        <div class="upload-placeholder">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p>Нажмите для загрузки</p>
                        </div>
                        <input type="hidden" name="blocks[<?php echo $blockIndex; ?>][content][image]" value="">
                    <?php endif; ?>
                    <input type="file" accept="image/*" class="block-image-file" style="display:none;">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label>Текст кнопки</label>
                    <input type="text" name="blocks[<?php echo $blockIndex; ?>][content][button_text]" value="<?php echo htmlspecialchars($blockContent['button_text'] ?? ''); ?>" placeholder="Например: Заказать">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Ссылка кнопки</label>
                    <input type="text" name="blocks[<?php echo $blockIndex; ?>][content][button_link]" value="<?php echo htmlspecialchars($blockContent['button_link'] ?? ''); ?>" placeholder="catalog.php или http://...">
                </div>
            </div>
            
        <?php elseif ($blockType === 'title_list'): ?>
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="blocks[<?php echo $blockIndex; ?>][content][title]" value="<?php echo htmlspecialchars($blockContent['title'] ?? ''); ?>" placeholder="Введите заголовок">
            </div>
            <div class="form-group">
                <label>Элементы списка</label>
                <div class="list-items-container" data-block-index="<?php echo $blockIndex; ?>">
                    <?php if (!empty($blockContent['items']) && is_array($blockContent['items'])): ?>
                        <?php foreach ($blockContent['items'] as $itemIndex => $item): ?>
                            <div class="list-item">
                                <input type="text" name="blocks[<?php echo $blockIndex; ?>][content][items][]" value="<?php echo htmlspecialchars($item); ?>" placeholder="Элемент списка">
                                <button type="button" class="btn-small btn-danger remove-list-item">×</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-secondary add-list-item" data-block-index="<?php echo $blockIndex; ?>" style="margin-top: 0.5rem;">+ Добавить элемент</button>
            </div>
            
        <?php elseif ($blockType === 'two_columns'): ?>
            <div class="form-group">
                <label>Заголовок (опционально)</label>
                <input type="text" name="blocks[<?php echo $blockIndex; ?>][content][title]" value="<?php echo htmlspecialchars($blockContent['title'] ?? ''); ?>" placeholder="Введите заголовок">
            </div>
            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label>Левый столбец</label>
                    <textarea name="blocks[<?php echo $blockIndex; ?>][content][left_column]" rows="8" placeholder="Содержимое левого столбца"><?php echo htmlspecialchars($blockContent['left_column'] ?? ''); ?></textarea>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Правый столбец</label>
                    <textarea name="blocks[<?php echo $blockIndex; ?>][content][right_column]" rows="8" placeholder="Содержимое правого столбца"><?php echo htmlspecialchars($blockContent['right_column'] ?? ''); ?></textarea>
                </div>
            </div>
            
        <?php elseif ($blockType === 'image_full'): ?>
            <div class="form-group">
                <label>Изображение на всю ширину</label>
                <div class="block-image-upload" data-block-index="<?php echo $blockIndex; ?>">
                    <?php if (!empty($blockContent['image'])): ?>
                        <img src="<?php echo BASE_URL; ?>uploads/articles/<?php echo htmlspecialchars($blockContent['image']); ?>" alt="Block image" class="upload-preview">
                        <input type="hidden" name="blocks[<?php echo $blockIndex; ?>][content][image]" value="<?php echo htmlspecialchars($blockContent['image']); ?>">
                    <?php else: ?>
                        <div class="upload-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p>Нажмите для загрузки изображения</p>
                        </div>
                        <input type="hidden" name="blocks[<?php echo $blockIndex; ?>][content][image]" value="">
                    <?php endif; ?>
                    <input type="file" accept="image/*" class="block-image-file" style="display:none;">
                </div>
            </div>
            
        <?php elseif ($blockType === 'text_only'): ?>
            <div class="form-group">
                <label>Текст</label>
                <textarea name="blocks[<?php echo $blockIndex; ?>][content][text]" rows="8" placeholder="Введите текст"><?php echo htmlspecialchars($blockContent['text'] ?? ''); ?></textarea>
            </div>
        <?php endif; ?>
    </div>
</div>




