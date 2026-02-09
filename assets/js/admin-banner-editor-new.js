// Banner Visual Editor JavaScript (New Layout)

document.addEventListener('DOMContentLoaded', function() {
    initBannerEditorNew();
});

function initBannerEditorNew() {
    const canvas = document.getElementById('banner-canvas');
    const titleEl = document.getElementById('title-element');
    const descEl = document.getElementById('desc-element');
    const imagePlaceholder = document.getElementById('image-placeholder');
    const imageFileInput = document.getElementById('banner-image-file');
    const titleInput = document.getElementById('title-input');
    const descInput = document.getElementById('description-input');
    const imageInput = document.getElementById('banner-image-input');
    
    if (!canvas) return;
    
    // Make content editable
    if (titleEl) {
        const titleContent = titleEl.querySelector('.element-content');
        if (titleContent) {
            titleContent.addEventListener('blur', updateHiddenInputs);
            titleContent.addEventListener('input', updateHiddenInputs);
        }
    }
    
    if (descEl) {
        const descContent = descEl.querySelector('.element-content');
        if (descContent) {
            descContent.addEventListener('blur', updateHiddenInputs);
            descContent.addEventListener('input', updateHiddenInputs);
        }
    }
    
    function updateHiddenInputs() {
        if (titleEl) {
            const titleContent = titleEl.querySelector('.element-content');
            if (titleContent) {
                titleInput.value = titleContent.textContent.trim();
            }
        }
        
        if (descEl) {
            const descContent = descEl.querySelector('.element-content');
            if (descContent) {
                descInput.value = descContent.textContent.trim();
            }
        }
    }
    
    // Image upload
    if (imagePlaceholder && imageFileInput) {
        imagePlaceholder.addEventListener('click', () => {
            imageFileInput.click();
        });
        
        imageFileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                uploadBannerImage(e.target.files[0]);
            }
        });
    }
    
    function uploadBannerImage(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        imagePlaceholder.innerHTML = '<p>Загрузка...</p>';
        
        fetch('upload-banner.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.file) {
                imageInput.value = data.file.name;
                const img = document.createElement('img');
                img.src = data.file.url;
                img.className = 'editor-image-preview';
                img.id = 'image-preview';
                imagePlaceholder.innerHTML = '';
                imagePlaceholder.appendChild(img);
                imagePlaceholder.classList.add('has-image');
            } else {
                alert('Ошибка загрузки изображения');
                imagePlaceholder.innerHTML = '<p>Кликните для загрузки изображения</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка загрузки изображения');
            imagePlaceholder.innerHTML = '<p>Кликните для загрузки изображения</p>';
        });
    }
    
    // Gradient controls
    const gradientColor1 = document.getElementById('gradient-color1');
    const gradientColor2 = document.getElementById('gradient-color2');
    const gradientAngle = document.getElementById('gradient-angle');
    const gradientPreview = document.getElementById('gradient-preview');
    const canvasGradient = document.getElementById('canvas-gradient');
    const resetGradientBtn = document.getElementById('reset-gradient');
    
    function updateGradient() {
        const color1 = gradientColor1.value;
        const color2 = gradientColor2.value;
        const angle = gradientAngle.value;
        const gradient = `linear-gradient(${angle}deg, ${color1} 0%, ${color2} 100%)`;
        
        if (gradientPreview) {
            gradientPreview.style.background = gradient;
        }
        if (canvasGradient) {
            canvasGradient.style.background = gradient;
        }
    }
    
    if (gradientColor1) gradientColor1.addEventListener('input', updateGradient);
    if (gradientColor2) gradientColor2.addEventListener('input', updateGradient);
    if (gradientAngle) gradientAngle.addEventListener('input', updateGradient);
    
    if (resetGradientBtn) {
        resetGradientBtn.addEventListener('click', () => {
            gradientColor1.value = '#667eea';
            gradientColor2.value = '#764ba2';
            gradientAngle.value = 135;
            updateGradient();
        });
    }
}






