// Banner Visual Editor JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initBannerEditor();
});

function initBannerEditor() {
    const canvas = document.getElementById('banner-canvas');
    const elements = document.querySelectorAll('.editable-element');
    
    if (!canvas) return;
    
    let selectedElement = null;
    let isDragging = false;
    let currentX = 0;
    let currentY = 0;
    let initialX = 0;
    let initialY = 0;
    
    // Make elements draggable
    elements.forEach(element => {
        element.addEventListener('mousedown', dragStart);
        element.addEventListener('touchstart', dragStart, { passive: false });
        
        // Make content editable
        const content = element.querySelector('.element-content');
        if (content) {
            content.addEventListener('blur', function() {
                updateHiddenInputs();
            });
            
            content.addEventListener('input', function() {
                updateHiddenInputs();
            });
        }
    });
    
    function dragStart(e) {
        e.preventDefault();
        e.stopPropagation();
        
        selectedElement = e.currentTarget;
        selectedElement.classList.add('selected');
        
        // Remove selection from other elements
        elements.forEach(el => {
            if (el !== selectedElement) {
                el.classList.remove('selected');
            }
        });
        
        isDragging = true;
        
        const touch = e.touches ? e.touches[0] : e;
        initialX = touch.clientX - selectedElement.offsetLeft;
        initialY = touch.clientY - selectedElement.offsetTop;
        
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', dragEnd);
        document.addEventListener('touchmove', drag, { passive: false });
        document.addEventListener('touchend', dragEnd);
    }
    
    function drag(e) {
        if (!isDragging || !selectedElement) return;
        
        e.preventDefault();
        const touch = e.touches ? e.touches[0] : e;
        currentX = touch.clientX - initialX;
        currentY = touch.clientY - initialY;
        
        // Constrain to canvas bounds
        const canvasRect = canvas.getBoundingClientRect();
        const elementRect = selectedElement.getBoundingClientRect();
        const maxX = canvasRect.width - elementRect.width;
        const maxY = canvasRect.height - elementRect.height;
        
        currentX = Math.max(0, Math.min(currentX, maxX));
        currentY = Math.max(0, Math.min(currentY, maxY));
        
        // Calculate percentage for X
        const xPercent = (currentX / canvasRect.width) * 100;
        const yPx = currentY;
        
        selectedElement.style.left = xPercent + '%';
        selectedElement.style.top = yPx + 'px';
        
        updateHiddenInputs();
    }
    
    function dragEnd() {
        isDragging = false;
        document.removeEventListener('mousemove', drag);
        document.removeEventListener('mouseup', dragEnd);
        document.removeEventListener('touchmove', drag);
        document.removeEventListener('touchend', dragEnd);
    }
    
    // Click outside to deselect
    canvas.addEventListener('click', function(e) {
        if (e.target === canvas || e.target.classList.contains('canvas-background')) {
            elements.forEach(el => el.classList.remove('selected'));
            selectedElement = null;
        }
    });
    
    function updateHiddenInputs() {
        const canvasRect = canvas.getBoundingClientRect();
        
        // Title
        const titleEl = document.getElementById('title-element');
        if (titleEl) {
            const rect = titleEl.getBoundingClientRect();
            const xPercent = ((rect.left - canvasRect.left) / canvasRect.width) * 100;
            document.getElementById('title-input').value = titleEl.querySelector('.element-content').textContent.trim();
            document.getElementById('title-x-input').value = Math.round(xPercent);
            document.getElementById('title-y-input').value = Math.round(rect.top - canvasRect.top);
        }
        
        // Description
        const descEl = document.getElementById('desc-element');
        if (descEl) {
            const rect = descEl.getBoundingClientRect();
            const xPercent = ((rect.left - canvasRect.left) / canvasRect.width) * 100;
            document.getElementById('description-input').value = descEl.querySelector('.element-content').textContent.trim();
            document.getElementById('desc-x-input').value = Math.round(xPercent);
            document.getElementById('desc-y-input').value = Math.round(rect.top - canvasRect.top);
        }
        
        // Button
        const buttonEl = document.getElementById('button-element');
        if (buttonEl) {
            const rect = buttonEl.getBoundingClientRect();
            const xPercent = ((rect.left - canvasRect.left) / canvasRect.width) * 100;
            document.getElementById('button-text-input').value = buttonEl.querySelector('.element-content').textContent.trim();
            document.getElementById('button-x-input').value = Math.round(xPercent);
            document.getElementById('button-y-input').value = Math.round(rect.top - canvasRect.top);
        }
    }
    
    // Handle background image upload
    const bgUpload = document.getElementById('banner-bg-upload');
    const bgFileInput = document.getElementById('banner-bg-file');
    
    if (bgUpload && bgFileInput) {
        bgUpload.addEventListener('click', () => {
            bgFileInput.click();
        });
        
        bgFileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                uploadBannerBackground(e.target.files[0]);
            }
        });
    }
    
    function uploadBannerBackground(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        bgUpload.innerHTML = '<div class="upload-placeholder"><p>Загрузка...</p></div>';
        
        fetch('upload-banner.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.file) {
                document.getElementById('banner-image-input').value = data.file.name;
                const background = canvas.querySelector('.canvas-background');
                if (background) {
                    background.style.backgroundImage = `url('${data.file.url}')`;
                    background.classList.remove('canvas-placeholder');
                }
                bgUpload.innerHTML = `<img src="${data.file.url}" alt="Background" class="upload-preview"><input type="hidden" name="image" value="${data.file.name}" id="banner-image-input">`;
            } else {
                alert('Ошибка загрузки изображения');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка загрузки изображения');
        });
    }
}






