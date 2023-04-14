// assets/js/main.js
document.addEventListener('DOMContentLoaded', () => {
    // --- Search Functionality ---
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const searchTerm = e.target.querySelector('input').value;
            if (searchTerm) {
                alert(`Searching for: "${searchTerm}"`);
            }
        });
    }
    
    // --- Carousel Functionality ---
    const track = document.querySelector('.carousel-track');
    if (track) {
        const slides = Array.from(track.children);
        const nextButton = document.querySelector('.carousel-button--right');
        const prevButton = document.querySelector('.carousel-button--left');
        const dotsNav = document.querySelector('.carousel-nav');
        
        if (slides.length === 0) return;

        slides.forEach((slide, index) => {
            const dot = document.createElement('button');
            dot.classList.add('carousel-indicator');
            if (index === 0) dot.classList.add('current-slide');
            dotsNav.appendChild(dot);
        });
        
        const dots = Array.from(dotsNav.children);
        const slideWidth = slides[0].getBoundingClientRect().width;

        const moveToSlide = (currentSlide, targetSlide) => {
            track.style.transform = 'translateX(-' + targetSlide.style.left + ')';
            currentSlide.classList.remove('current-slide');
            targetSlide.classList.add('current-slide');
        };

        const updateDots = (currentDot, targetDot) => {
            currentDot.classList.remove('current-slide');
            targetDot.classList.add('current-slide');
        };
        
        const setSlidePosition = (slide, index) => {
            slide.style.left = slideWidth * index + 'px';
        };
        slides.forEach(setSlidePosition);

        nextButton.addEventListener('click', e => {
            const currentSlide = track.querySelector('.current-slide');
            let nextSlide = currentSlide.nextElementSibling;
            if (!nextSlide) nextSlide = slides[0];
            const currentDot = dotsNav.querySelector('.current-slide');
            const nextDot = dots[slides.indexOf(nextSlide)];
            moveToSlide(currentSlide, nextSlide);
            updateDots(currentDot, nextDot);
        });

        prevButton.addEventListener('click', e => {
            const currentSlide = track.querySelector('.current-slide');
            let prevSlide = currentSlide.previousElementSibling;
            if (!prevSlide) prevSlide = slides[slides.length - 1];
            const currentDot = dotsNav.querySelector('.current-slide');
            const prevDot = dots[slides.indexOf(prevSlide)];
            moveToSlide(currentSlide, prevSlide);
            updateDots(currentDot, prevDot);
        });
        
        dotsNav.addEventListener('click', e => {
            const targetDot = e.target.closest('button.carousel-indicator');
            if (!targetDot) return;
            const currentSlide = track.querySelector('.current-slide');
            const currentDot = dotsNav.querySelector('.current-slide');
            const targetIndex = dots.findIndex(dot => dot === targetDot);
            const targetSlide = slides[targetIndex];
            moveToSlide(currentSlide, targetSlide);
            updateDots(currentDot, targetDot);
        });

        let autoPlayInterval = setInterval(() => { nextButton.click(); }, 5000);
        
        const carousel = document.querySelector('.carousel');
        carousel.addEventListener('mouseenter', () => clearInterval(autoPlayInterval));
        carousel.addEventListener('mouseleave', () => {
            autoPlayInterval = setInterval(() => { nextButton.click(); }, 5000);
        });
    }
});