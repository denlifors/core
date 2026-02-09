// Admin Banner Upload JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initBannerImageUpload();
});

function initBannerImageUpload() {
    const uploadArea = document.getElementById('banner-image-upload');
    const fileInput = document.getElementById('banner-image-file');
    const imageInput = document.getElementById('banner-image-input');
    
    if (!uploadArea || !fileInput) return;
    
    // Click to select file
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });
    
    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('drag-over');
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            uploadBannerImage(files[0]);
        }
    });
    
    // File input change
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            uploadBannerImage(e.target.files[0]);
        }
    });
}

function uploadBannerImage(file) {
    const uploadArea = document.getElementById('banner-image-upload');
    const imageInput = document.getElementById('banner-image-input');
    
    if (!file.type.startsWith('image/')) {
        alert('Пожалуйста, выберите изображение');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    
    // Show loading
    uploadArea.innerHTML = '<div class="upload-placeholder"><p>Загрузка...</p></div>';
    
    fetch('upload-banner.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.file) {
            imageInput.value = data.file.name;
            uploadArea.innerHTML = `<img src="${data.file.url}" alt="Banner image" class="upload-preview"><input type="hidden" name="image" value="${data.file.name}" id="banner-image-input">`;
            
            // Update preview image
            const previewImage = document.getElementById('preview-image');
            const previewRight = document.querySelector('.banner-preview-right');
            if (previewImage) {
                previewImage.src = data.file.url;
                previewImage.style.display = 'block';
            }
            if (previewRight) {
                previewRight.style.display = 'flex';
            }
        } else {
            alert('Ошибка загрузки изображения');
            uploadArea.innerHTML = '<div class="upload-placeholder"><p>Ошибка загрузки</p></div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка загрузки изображения');
    });
}





