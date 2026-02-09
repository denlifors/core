// Documentation file upload
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('documentation-upload');
    const fileInput = document.getElementById('documentation-file');
    const fileInputHidden = document.getElementById('documentation-file-input');
    const removeBtn = document.getElementById('remove-documentation');
    
    if (!uploadArea || !fileInput) return;
    
    // Click to select file
    uploadArea.addEventListener('click', (e) => {
        if (!e.target.closest('.documentation-remove') && !e.target.closest('.documentation-info')) {
            fileInput.click();
        }
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
            uploadDocumentation(files[0]);
        }
    });
    
    // File input change
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            uploadDocumentation(e.target.files[0]);
        }
    });
    
    // Remove button
    if (removeBtn) {
        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (confirm('Удалить файл документации?')) {
                fileInputHidden.value = '';
                uploadArea.innerHTML = `
                    <div class="upload-placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <p>Перетащите файл документации или нажмите для выбора</p>
                        <small>PDF, DOC, DOCX, JPG, PNG до 10MB</small>
                    </div>
                    <input type="hidden" name="documentation_file" value="" id="documentation-file-input">
                    <input type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" id="documentation-file" style="display:none;">
                `;
                // Reinitialize
                setTimeout(() => {
                    const newFileInput = document.getElementById('documentation-file');
                    const newUploadArea = document.getElementById('documentation-upload');
                    if (newFileInput && newUploadArea) {
                        newFileInput.addEventListener('change', function(e) {
                            if (e.target.files.length > 0) {
                                uploadDocumentation(e.target.files[0]);
                            }
                        });
                        newUploadArea.addEventListener('click', () => newFileInput.click());
                    }
                }, 100);
            }
        });
    }
});

function uploadDocumentation(file) {
    const uploadArea = document.getElementById('documentation-upload');
    const fileInputHidden = document.getElementById('documentation-file-input');
    
    if (!file.type.match(/(pdf|msword|vnd\.openxmlformats|jpeg|jpg|png)/i)) {
        alert('Пожалуйста, выберите файл PDF, DOC, DOCX, JPG или PNG');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    
    // Show loading
    uploadArea.innerHTML = '<div class="upload-placeholder"><p>Загрузка...</p></div>';
    
    fetch('upload-documentation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.file) {
            fileInputHidden.value = data.file.name;
            uploadArea.innerHTML = `
                <div class="documentation-preview">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <div class="documentation-info">
                        <p class="documentation-name">${data.file.originalName}</p>
                        <button type="button" class="documentation-remove" id="remove-documentation">Удалить</button>
                    </div>
                </div>
                <input type="hidden" name="documentation_file" value="${data.file.name}" id="documentation-file-input">
                <input type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" id="documentation-file" style="display:none;">
            `;
            
            // Reinitialize remove button
            const newRemoveBtn = document.getElementById('remove-documentation');
            if (newRemoveBtn) {
                newRemoveBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (confirm('Удалить файл документации?')) {
                        const hiddenInput = document.getElementById('documentation-file-input');
                        if (hiddenInput) hiddenInput.value = '';
                        uploadArea.innerHTML = `
                            <div class="upload-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <p>Перетащите файл документации или нажмите для выбора</p>
                                <small>PDF, DOC, DOCX, JPG, PNG до 10MB</small>
                            </div>
                            <input type="hidden" name="documentation_file" value="" id="documentation-file-input">
                            <input type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" id="documentation-file" style="display:none;">
                        `;
                    }
                });
            }
        } else {
            alert(data.error || 'Ошибка загрузки файла');
            uploadArea.innerHTML = '<div class="upload-placeholder"><p>Ошибка загрузки</p></div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка загрузки файла');
    });
}


