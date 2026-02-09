// Product and Category Carousels

document.addEventListener('DOMContentLoaded', function() {
    initCarousels();
});

function initCarousels() {
    const carousels = document.querySelectorAll('[data-carousel]');
    
    carousels.forEach(carousel => {
        const track = carousel.querySelector('.products-carousel-track, .categories-carousel-track');
        const prevBtn = carousel.querySelector('.carousel-prev');
        const nextBtn = carousel.querySelector('.carousel-next');
        
        if (!track || !prevBtn || !nextBtn) return;
        
        const itemWidth = track.querySelector('.product-card, .category-card')?.offsetWidth || 280;
        const gap = 32; // gap between items (2rem = 32px)
        const scrollAmount = itemWidth + gap;
        
        let canScroll = true;
        
        // Check if can scroll and update button visibility
        function checkScroll() {
            const maxScroll = track.scrollWidth - track.clientWidth;
            const canScrollLeft = track.scrollLeft > 1;
            const canScrollRight = track.scrollLeft < maxScroll - 1;
            
            // Show/hide buttons based on scroll position
            if (canScrollLeft && canScrollRight) {
                // Can scroll both ways - show both buttons
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'flex';
                prevBtn.style.opacity = '1';
                nextBtn.style.opacity = '1';
                prevBtn.style.pointerEvents = 'auto';
                nextBtn.style.pointerEvents = 'auto';
            } else if (canScrollRight) {
                // Can only scroll right - show only next button
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'flex';
                nextBtn.style.opacity = '1';
                nextBtn.style.pointerEvents = 'auto';
            } else if (canScrollLeft) {
                // Can only scroll left - show only prev button
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'none';
                prevBtn.style.opacity = '1';
                prevBtn.style.pointerEvents = 'auto';
            } else {
                // Cannot scroll - hide both buttons
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            }
        }
        
        // Initial check on load
        setTimeout(() => {
            checkScroll();
        }, 100);
        
        prevBtn.addEventListener('click', () => {
            if (!canScroll) return;
            canScroll = false;
            track.scrollBy({
                left: -scrollAmount,
                behavior: 'smooth'
            });
            setTimeout(() => {
                canScroll = true;
                checkScroll();
            }, 500);
        });
        
        nextBtn.addEventListener('click', () => {
            if (!canScroll) return;
            canScroll = false;
            track.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
            setTimeout(() => {
                canScroll = true;
                checkScroll();
            }, 500);
        });
        
        track.addEventListener('scroll', checkScroll);
        
        // Recheck on window resize
        window.addEventListener('resize', () => {
            setTimeout(checkScroll, 100);
        });
        
        // Touch/swipe support
        let isDown = false;
        let startX;
        let scrollLeft;
        
        track.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - track.offsetLeft;
            scrollLeft = track.scrollLeft;
            track.style.cursor = 'grabbing';
        });
        
        track.addEventListener('mouseleave', () => {
            isDown = false;
            track.style.cursor = 'grab';
        });
        
        track.addEventListener('mouseup', () => {
            isDown = false;
            track.style.cursor = 'grab';
            checkScroll();
        });
        
        track.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - track.offsetLeft;
            const walk = (x - startX) * 2;
            track.scrollLeft = scrollLeft - walk;
        });
        
        // Touch events
        let touchStartX = 0;
        let touchScrollLeft = 0;
        
        track.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].pageX - track.offsetLeft;
            touchScrollLeft = track.scrollLeft;
        });
        
        track.addEventListener('touchmove', (e) => {
            if (!touchStartX) return;
            const x = e.touches[0].pageX - track.offsetLeft;
            const walk = (x - touchStartX) * 2;
            track.scrollLeft = touchScrollLeft - walk;
        });
        
        track.addEventListener('touchend', () => {
            touchStartX = 0;
            checkScroll();
        });
    });
}
