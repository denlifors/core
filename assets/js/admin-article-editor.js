// Article Editor with Block Templates
const BASE_URL = window.BASE_URL || '';

// Available templates
const templates = [
    {
        type: 'title_text',
        name: 'Заголовок + Текст',
        icon: '📝',
        description: 'Простой блок с заголовком и текстом'
    },
    {
        type: 'title_text_image_left',
        name: 'Заголовок + Текст + Изображение (слева)',
        icon: '🖼️',
        description: 'Заголовок, текст и изображение слева'
    },
    {
        type: 'title_text_image_right',
        name: 'Заголовок + Текст + Изображение (справа)',
        icon: '🖼️',
        description: 'Заголовок, текст и изображение справа'
    },
    {
        type: 'title_text_button',
        name: 'Заголовок + Текст + Кнопка',
        icon: '🔘',
        description: 'Заголовок, текст и кнопка действия'
    },
    {
        type: 'title_text_image_button',
        name: 'Заголовок + Текст + Изображение + Кнопка',
        icon: '🎯',
        description: 'Полный блок с заголовком, текстом, изображением и кнопкой'
    },
    {
        type: 'title_list',
        name: 'Заголовок + Список',
        icon: '📋',
        description: 'Заголовок и список элементов'
    },
    {
        type: 'two_columns',
        name: 'Два столбца',
        icon: '📊',
        description: 'Два столбца с содержимым'
    },
    {
        type: 'image_full',
        name: 'Изображение на всю ширину',
        icon: '🖼️',
        description: 'Изображение на всю ширину страницы'
    },
    {
        type: 'text_only',
        name: 'Только текст',
        icon: '📄',
        description: 'Простой текстовый блок'
    }
];

let blockCounter = 0;

function initArticleEditor(existingBlocks = []) {
    // Set block counter based on existing blocks
    const container = document.getElementById('article-blocks-container');
    if (container) {
        blockCounter = container.querySelectorAll('.article-block').length;
    }
    
    // Initialize template modal
    initTemplateModal();
    
    // Initialize add block button
    const addBlockBtn = document.getElementById('add-block-btn');
    if (addBlockBtn) {
        addBlockBtn.addEventListener('click', openTemplateModal);
    }
    
    // Initialize block controls
    initBlockControls();
    
    // Initialize image uploads
    initImageUploads();
    
    // Initialize list items
    initListItems();
}

function initTemplateModal() {
    const modal = document.getElementById('template-modal');
    const optionsContainer = document.getElementById('template-options');
    
    if (!modal || !optionsContainer) return;
    
    // Populate template options
    templates.forEach(template => {
        const option = document.createElement('div');
        option.className = 'template-option';
        option.style.cssText = 'border: 2px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; cursor: pointer; transition: all 0.3s; text-align: center;';
        option.innerHTML = `
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">${template.icon}</div>
            <h3 style="margin: 0.5rem 0; font-size: 1rem; color: #2d3748;">${template.name}</h3>
            <p style="margin: 0; font-size: 0.85rem; color: #718096;">${template.description}</p>
        `;
        
        option.addEventListener('mouseenter', () => {
            option.style.borderColor = '#667eea';
            option.style.transform = 'translateY(-2px)';
            option.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.15)';
        });
        
        option.addEventListener('mouseleave', () => {
            option.style.borderColor = '#e2e8f0';
            option.style.transform = 'translateY(0)';
            option.style.boxShadow = 'none';
        });
        
        option.addEventListener('click', () => {
            addBlock(template.type);
            closeTemplateModal();
        });
        
        optionsContainer.appendChild(option);
    });
    
    // Close modal on background click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeTemplateModal();
        }
    });
}

function openTemplateModal() {
    const modal = document.getElementById('template-modal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeTemplateModal() {
    const modal = document.getElementById('template-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function addBlock(templateType) {
    const container = document.getElementById('article-blocks-container');
    if (!container) return;
    
    const blockIndex = blockCounter++;
    const block = {
        template_type: templateType,
        content: {}
    };
    
    // Create block HTML
    const blockElement = createBlockElement(blockIndex, block);
    container.appendChild(blockElement);
    
    // Reinitialize controls for new block
    initBlockControls();
    initImageUploads();
    initListItems();
    
    // Scroll to new block
    blockElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function createBlockElement(blockIndex, block) {
    const div = document.createElement('div');
    div.className = 'article-block';
    div.setAttribute('data-block-id', 'new-' + blockIndex);
    div.setAttribute('data-block-index', blockIndex);
    
    const template = templates.find(t => t.type === block.template_type);
    const templateName = template ? template.name : 'Неизвестный шаблон';
    
    div.innerHTML = `
        <div class="article-block-header">
            <div class="article-block-type">${templateName}</div>
            <div class="article-block-actions">
                <button type="button" class="btn-small move-block-up" title="Переместить вверх">↑</button>
                <button type="button" class="btn-small move-block-down" title="Переместить вниз">↓</button>
                <button type="button" class="btn-small btn-danger remove-block" title="Удалить блок">×</button>
            </div>
        </div>
        <div class="article-block-content">
            ${getBlockContentHTML(blockIndex, block)}
        </div>
    `;
    
    return div;
}

function getBlockContentHTML(blockIndex, block) {
    const type = block.template_type;
    const content = block.content || {};
    
    let html = `<input type="hidden" name="blocks[${blockIndex}][template_type]" value="${type}">`;
    
    switch(type) {
        case 'title_text':
            html += `
                <div class="form-group">
                    <label>Заголовок</label>
                    <input type="text" name="blocks[${blockIndex}][content][title]" value="${escapeHtml(content.title || '')}" placeholder="Введите заголовок">
                </div>
                <div class="form-group">
                    <label>Текст</label>
                    <textarea name="blocks[${blockIndex}][content][text]" rows="6" placeholder="Введите текст">${escapeHtml(content.text || '')}</textarea>
                </div>
            `;
            break;
            
        case 'title_text_image_left':
        case 'title_text_image_right':
            html += `
                <div class="form-group">
                    <label>Заголовок</label>
                    <input type="text" name="blocks[${blockIndex}][content][title]" value="${escapeHtml(content.title || '')}" placeholder="Введите заголовок">
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Текст</label>
                        <textarea name="blocks[${blockIndex}][content][text]" rows="6" placeholder="Введите текст">${escapeHtml(content.text || '')}</textarea>
                    </div>
                    <div class="form-group" style="flex: 0 0 300px;">
                        <label>Изображение</label>
                        <div class="block-image-upload" data-block-index="${blockIndex}">
                            ${content.image ? `
                                <img src="${BASE_URL}uploads/articles/${escapeHtml(content.image)}" alt="Block image" class="upload-preview">
                                <input type="hidden" name="blocks[${blockIndex}][content][image]" value="${escapeHtml(content.image)}">
                            ` : `
                                <div class="upload-placeholder">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <p>Нажмите для загрузки</p>
                                </div>
                                <input type="hidden" name="blocks[${blockIndex}][content][image]" value="">
                            `}
                            <input type="file" accept="image/*" class="block-image-file" style="display:none;">
                        </div>
                    </div>
                </div>
            `;
            break;
            
        case 'title_text_button':
            html += `
                <div class="form-group">
                    <label>Заголовок</label>
                    <input type="text" name="blocks[${blockIndex}][content][title]" value="${escapeHtml(content.title || '')}" placeholder="Введите заголовок">
                </div>
                <div class="form-group">
                    <label>Текст</label>
                    <textarea name="blocks[${blockIndex}][content][text]" rows="6" placeholder="Введите текст">${escapeHtml(content.text || '')}</textarea>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Текст кнопки</label>
                        <input type="text" name="blocks[${blockIndex}][content][button_text]" value="${escapeHtml(content.button_text || '')}" placeholder="Например: Заказать">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Ссылка кнопки</label>
                        <input type="text" name="blocks[${blockIndex}][content][button_link]" value="${escapeHtml(content.button_link || '')}" placeholder="catalog.php или http://...">
                    </div>
                </div>
            `;
            break;
            
        case 'title_text_image_button':
            html += `
                <div class="form-group">
                    <label>Заголовок</label>
                    <input type="text" name="blocks[${blockIndex}][content][title]" value="${escapeHtml(content.title || '')}" placeholder="Введите заголовок">
                </div>
                <div class="form-group">
                    <label>Текст</label>
                    <textarea name="blocks[${blockIndex}][content][text]" rows="6" placeholder="Введите текст">${escapeHtml(content.text || '')}</textarea>
                </div>
                <div class="form-group">
                    <label>Изображение</label>
                    <div class="block-image-upload" data-block-index="${blockIndex}">
                        ${content.image ? `
                            <img src="${BASE_URL}uploads/articles/${escapeHtml(content.image)}" alt="Block image" class="upload-preview">
                            <input type="hidden" name="blocks[${blockIndex}][content][image]" value="${escapeHtml(content.image)}">
                        ` : `
                            <div class="upload-placeholder">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <p>Нажмите для загрузки</p>
                            </div>
                            <input type="hidden" name="blocks[${blockIndex}][content][image]" value="">
                        `}
                        <input type="file" accept="image/*" class="block-image-file" style="display:none;">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Текст кнопки</label>
                        <input type="text" name="blocks[${blockIndex}][content][button_text]" value="${escapeHtml(content.button_text || '')}" placeholder="Например: Заказать">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Ссылка кнопки</label>
                        <input type="text" name="blocks[${blockIndex}][content][button_link]" value="${escapeHtml(content.button_link || '')}" placeholder="catalog.php или http://...">
                    </div>
                </div>
            `;
            break;
            
        case 'title_list':
            html += `
                <div class="form-group">
                    <label>Заголовок</label>
                    <input type="text" name="blocks[${blockIndex}][content][title]" value="${escapeHtml(content.title || '')}" placeholder="Введите заголовок">
                </div>
                <div class="form-group">
                    <label>Элементы списка</label>
                    <div class="list-items-container" data-block-index="${blockIndex}">
                        ${(content.items || []).map((item, itemIndex) => `
                            <div class="list-item">
                                <input type="text" name="blocks[${blockIndex}][content][items][]" value="${escapeHtml(item)}" placeholder="Элемент списка">
                                <button type="button" class="btn-small btn-danger remove-list-item">×</button>
                            </div>
                        `).join('')}
                    </div>
                    <button type="button" class="btn-secondary add-list-item" data-block-index="${blockIndex}" style="margin-top: 0.5rem;">+ Добавить элемент</button>
                </div>
            `;
            break;
            
        case 'two_columns':
            html += `
                <div class="form-group">
                    <label>Заголовок (опционально)</label>
                    <input type="text" name="blocks[${blockIndex}][content][title]" value="${escapeHtml(content.title || '')}" placeholder="Введите заголовок">
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Левый столбец</label>
                        <textarea name="blocks[${blockIndex}][content][left_column]" rows="8" placeholder="Содержимое левого столбца">${escapeHtml(content.left_column || '')}</textarea>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Правый столбец</label>
                        <textarea name="blocks[${blockIndex}][content][right_column]" rows="8" placeholder="Содержимое правого столбца">${escapeHtml(content.right_column || '')}</textarea>
                    </div>
                </div>
            `;
            break;
            
        case 'image_full':
            html += `
                <div class="form-group">
                    <label>Изображение на всю ширину</label>
                    <div class="block-image-upload" data-block-index="${blockIndex}">
                        ${content.image ? `
                            <img src="${BASE_URL}uploads/articles/${escapeHtml(content.image)}" alt="Block image" class="upload-preview">
                            <input type="hidden" name="blocks[${blockIndex}][content][image]" value="${escapeHtml(content.image)}">
                        ` : `
                            <div class="upload-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <p>Нажмите для загрузки изображения</p>
                            </div>
                            <input type="hidden" name="blocks[${blockIndex}][content][image]" value="">
                        `}
                        <input type="file" accept="image/*" class="block-image-file" style="display:none;">
                    </div>
                </div>
            `;
            break;
            
        case 'text_only':
            html += `
                <div class="form-group">
                    <label>Текст</label>
                    <textarea name="blocks[${blockIndex}][content][text]" rows="8" placeholder="Введите текст">${escapeHtml(content.text || '')}</textarea>
                </div>
            `;
            break;
    }
    
    return html;
}

function initBlockControls() {
    // Remove block buttons
    document.querySelectorAll('.remove-block').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Удалить этот блок?')) {
                this.closest('.article-block').remove();
                updateBlockIndices();
            }
        });
    });
    
    // Move up buttons
    document.querySelectorAll('.move-block-up').forEach(btn => {
        btn.addEventListener('click', function() {
            const block = this.closest('.article-block');
            const prev = block.previousElementSibling;
            if (prev) {
                block.parentNode.insertBefore(block, prev);
                updateBlockIndices();
            }
        });
    });
    
    // Move down buttons
    document.querySelectorAll('.move-block-down').forEach(btn => {
        btn.addEventListener('click', function() {
            const block = this.closest('.article-block');
            const next = block.nextElementSibling;
            if (next) {
                block.parentNode.insertBefore(next, block);
                updateBlockIndices();
            }
        });
    });
}

function updateBlockIndices() {
    const container = document.getElementById('article-blocks-container');
    if (!container) return;
    
    const blocks = container.querySelectorAll('.article-block');
    blocks.forEach((block, index) => {
        block.setAttribute('data-block-index', index);
        
        // Update all input names
        block.querySelectorAll('input, textarea, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name && name.startsWith('blocks[')) {
                const newName = name.replace(/blocks\[\d+\]/, `blocks[${index}]`);
                input.setAttribute('name', newName);
            }
        });
        
        // Update data-block-index attributes
        block.querySelectorAll('[data-block-index]').forEach(el => {
            el.setAttribute('data-block-index', index);
        });
    });
}

function initImageUploads() {
    document.querySelectorAll('.block-image-upload').forEach(uploadArea => {
        const fileInput = uploadArea.querySelector('.block-image-file');
        const hiddenInput = uploadArea.querySelector('input[type="hidden"]');
        
        if (!fileInput) return;
        
        // Remove existing listeners by cloning
        const newUploadArea = uploadArea.cloneNode(true);
        uploadArea.parentNode.replaceChild(newUploadArea, uploadArea);
        
        const newFileInput = newUploadArea.querySelector('.block-image-file');
        const newHiddenInput = newUploadArea.querySelector('input[type="hidden"]');
        
        newUploadArea.addEventListener('click', () => {
            if (newFileInput) newFileInput.click();
        });
        
        newFileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                uploadBlockImage(e.target.files[0], newUploadArea, newHiddenInput);
            }
        });
        
        // Drag and drop
        newUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            newUploadArea.style.borderColor = '#667eea';
        });
        
        newUploadArea.addEventListener('dragleave', () => {
            newUploadArea.style.borderColor = '#e2e8f0';
        });
        
        newUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            newUploadArea.style.borderColor = '#e2e8f0';
            if (e.dataTransfer.files.length > 0) {
                uploadBlockImage(e.dataTransfer.files[0], newUploadArea, newHiddenInput);
            }
        });
    });
}

function uploadBlockImage(file, uploadArea, hiddenInput) {
    const formData = new FormData();
    formData.append('image', file);
    
    uploadArea.style.opacity = '0.6';
    uploadArea.style.pointerEvents = 'none';
    
    fetch('upload-article.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        uploadArea.style.opacity = '1';
        uploadArea.style.pointerEvents = 'auto';
        
        if (data.success && data.filename) {
            if (hiddenInput) {
                hiddenInput.value = data.filename;
            }
            
            const existingPreview = uploadArea.querySelector('.upload-preview');
            const placeholder = uploadArea.querySelector('.upload-placeholder');
            
            if (existingPreview) {
                existingPreview.src = BASE_URL + 'uploads/articles/' + data.filename;
            } else {
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
                const img = document.createElement('img');
                img.src = BASE_URL + 'uploads/articles/' + data.filename;
                img.alt = 'Block image';
                img.className = 'upload-preview';
                uploadArea.insertBefore(img, uploadArea.firstChild);
            }
        } else {
            alert(data.error || 'Ошибка при загрузке изображения');
        }
    })
    .catch(error => {
        uploadArea.style.opacity = '1';
        uploadArea.style.pointerEvents = 'auto';
        console.error('Error:', error);
        alert('Ошибка при загрузке изображения');
    });
}

function initListItems() {
    // Add list item buttons
    document.querySelectorAll('.add-list-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const blockIndex = this.getAttribute('data-block-index');
            const container = this.previousElementSibling;
            
            if (container) {
                const newItem = document.createElement('div');
                newItem.className = 'list-item';
                newItem.innerHTML = `
                    <input type="text" name="blocks[${blockIndex}][content][items][]" value="" placeholder="Элемент списка">
                    <button type="button" class="btn-small btn-danger remove-list-item">×</button>
                `;
                container.appendChild(newItem);
                
                // Add remove listener
                newItem.querySelector('.remove-list-item').addEventListener('click', function() {
                    newItem.remove();
                });
            }
        });
    });
    
    // Remove list item buttons
    document.querySelectorAll('.remove-list-item').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.list-item').remove();
        });
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initArticleEditor === 'function') {
            initArticleEditor();
        }
    });
} else {
    if (typeof initArticleEditor === 'function') {
        initArticleEditor();
    }
}




