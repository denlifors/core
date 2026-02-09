// Banner preview and gradient update
document.addEventListener('DOMContentLoaded', function() {
    const gradientColor1 = document.getElementById('gradient-color1');
    const gradientColor2 = document.getElementById('gradient-color2');
    const gradientAngle = document.getElementById('gradient-angle');
    const gradientPreview = document.getElementById('gradient-preview');
    const bannerPreview = document.getElementById('banner-preview');
    
    const titleInput = document.getElementById('banner-title');
    const subtitleInput = document.getElementById('banner-subtitle');
    const descriptionInput = document.getElementById('banner-description');
    
    const previewTitle = document.getElementById('preview-title');
    const previewSubtitle = document.getElementById('preview-subtitle');
    const previewDescription = document.getElementById('preview-description');
    
    // Update gradient preview
    function updateGradientPreview() {
        const color1 = gradientColor1.value;
        const color2 = gradientColor2.value;
        const angle = gradientAngle.value;
        
        const gradientStyle = `linear-gradient(${angle}deg, ${color1} 0%, ${color2} 100%)`;
        
        if (gradientPreview) {
            gradientPreview.style.background = gradientStyle;
        }
        
        if (bannerPreview) {
            bannerPreview.style.background = gradientStyle;
        }
    }
    
    // Update text preview
    function updateTextPreview() {
        if (previewTitle && titleInput) {
            previewTitle.textContent = titleInput.value || 'Заголовок баннера';
        }
        
        if (previewSubtitle && subtitleInput) {
            if (subtitleInput.value.trim()) {
                previewSubtitle.textContent = subtitleInput.value;
                previewSubtitle.style.display = 'block';
            } else {
                previewSubtitle.style.display = 'none';
            }
        }
        
        if (previewDescription && descriptionInput) {
            if (descriptionInput.value.trim()) {
                previewDescription.textContent = descriptionInput.value;
                previewDescription.style.display = 'block';
            } else {
                previewDescription.style.display = 'none';
            }
        }
    }
    
    // Add event listeners
    if (gradientColor1) {
        gradientColor1.addEventListener('input', updateGradientPreview);
    }
    
    if (gradientColor2) {
        gradientColor2.addEventListener('input', updateGradientPreview);
    }
    
    if (gradientAngle) {
        gradientAngle.addEventListener('input', updateGradientPreview);
    }
    
    if (titleInput) {
        titleInput.addEventListener('input', updateTextPreview);
    }
    
    if (subtitleInput) {
        subtitleInput.addEventListener('input', updateTextPreview);
    }
    
    if (descriptionInput) {
        descriptionInput.addEventListener('input', updateTextPreview);
    }
    
    // Initial update
    updateGradientPreview();
    updateTextPreview();
});


