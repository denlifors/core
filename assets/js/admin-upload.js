// Admin Upload JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initMainImageUpload();
    initGalleryUpload();
});

function initMainImageUpload() {
    const uploadArea = document.getElementById('main-image-upload');
    const fileInput = document.getElementById('main-image-file');
    const imageInput = document.getElementById('main-image-input');
    
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
            uploadMainImage(files[0]);
        }
    });
    
    // File input change
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            uploadMainImage(e.target.files[0]);
        }
    });
}

function uploadMainImage(file) {
    const uploadArea = document.getElementById('main-image-upload');
    const imageInput = document.getElementById('main-image-input');
    
    if (!file.type.startsWith('image/')) {
        alert('Пожалуйста, выберите изображение');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    
    // Show loading
    uploadArea.innerHTML = '<div class="upload-placeholder"><p>Загрузка...</p></div>';
    
    fetch('upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.file) {
            imageInput.value = data.file.name;
            uploadArea.innerHTML = `<img src="${data.file.url}" alt="Main image" class="upload-preview"><input type="hidden" name="image" value="${data.file.name}" id="main-image-input">`;
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

function initGalleryUpload() {
    const uploadArea = document.getElementById('gallery-upload');
    const fileInput = document.getElementById('gallery-files');
    const imagesInput = document.getElementById('images-json-input');
    
    if (!uploadArea || !fileInput) return;
    
    // Click to select files
    uploadArea.addEventListener('click', (e) => {
        if (e.target.closest('.gallery-item')) return;
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
        
        const files = Array.from(e.dataTransfer.files);
        uploadGalleryImages(files);
    });
    
    // File input change
    fileInput.addEventListener('change', (e) => {
        const files = Array.from(e.target.files);
        uploadGalleryImages(files);
    });
}

function uploadGalleryImages(files) {
    const imageFiles = files.filter(file => file.type.startsWith('image/'));
    
    if (imageFiles.length === 0) {
        alert('Пожалуйста, выберите изображения');
        return;
    }
    
    const formData = new FormData();
    imageFiles.forEach(file => {
        formData.append('files[]', file);
    });
    
    // Upload files one by one to handle properly
    const uploadPromises = imageFiles.map(file => {
        const fileFormData = new FormData();
        fileFormData.append('file', file);
        
        return fetch('upload.php', {
            method: 'POST',
            body: fileFormData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.file) {
                return data.file;
            }
            return null;
        });
    });
    
    Promise.all(uploadPromises)
        .then(files => {
            const validFiles = files.filter(f => f !== null);
            if (validFiles.length > 0) {
                addGalleryImages(validFiles);
            } else {
                alert('Ошибка загрузки изображений');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка загрузки изображений');
        });
}

function addGalleryImages(files) {
    const galleryItems = document.getElementById('gallery-items');
    const imagesInput = document.getElementById('images-json-input');
    
    let currentImages = [];
    if (imagesInput.value) {
        try {
            currentImages = JSON.parse(imagesInput.value);
        } catch (e) {
            currentImages = [];
        }
    }
    
    // Ensure gallery-items div exists
    if (!galleryItems) {
        const uploadArea = document.getElementById('gallery-upload');
        const newGalleryItems = document.createElement('div');
        newGalleryItems.className = 'gallery-items';
        newGalleryItems.id = 'gallery-items';
        uploadArea.insertBefore(newGalleryItems, uploadArea.firstChild);
    }
    
    files.forEach(file => {
        if (file && file.name) {
            currentImages.push(file.name);
            
            const item = document.createElement('div');
            item.className = 'gallery-item';
            item.innerHTML = `
                <img src="${file.url}" alt="Gallery image">
                <button type="button" class="remove-image" onclick="removeGalleryImage(this)">×</button>
            `;
            galleryItems.appendChild(item);
        }
    });
    
    imagesInput.value = JSON.stringify(currentImages);
}

function removeGalleryImage(button) {
    const galleryItem = button.closest('.gallery-item');
    if (!galleryItem) return;
    
    const imagesInput = document.getElementById('images-json-input');
    const img = galleryItem.querySelector('img');
    if (!img || !imagesInput) return;
    
    const imageSrc = img.src.split('/').pop();
    
    let currentImages = [];
    if (imagesInput.value) {
        try {
            currentImages = JSON.parse(imagesInput.value);
        } catch (e) {
            currentImages = [];
        }
    }
    
    currentImages = currentImages.filter(imgName => imgName !== imageSrc);
    imagesInput.value = JSON.stringify(currentImages);
    
    galleryItem.remove();
}

