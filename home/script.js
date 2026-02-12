// ============================================
// COUNTER ANIMATION
// ============================================

function animateValue(element, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);

        // Easing function for smooth animation
        const easeOutQuad = progress * (2 - progress);

        element.textContent = Math.floor(easeOutQuad * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// ============================================
// INTERSECTION OBSERVER FOR SCROLL ANIMATIONS
// ============================================

const observerOptions = {
    threshold: 0.3,
    rootMargin: '0px'
};

// Counter Animation Observer
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
            const target = parseInt(entry.target.getAttribute('data-target'));
            animateValue(entry.target, 0, target, 2000);
            entry.target.classList.add('counted');
        }
    });
}, observerOptions);

// Department Text Animation Observer
const departmentObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
            entry.target.classList.add('animated');
            entry.target.style.animation = 'departmentTextDrop 1s ease-out forwards';
        }
    });
}, observerOptions);

// ============================================
// INITIALIZE OBSERVERS
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Observe all stat numbers
    const statNumbers = document.querySelectorAll('.stat-number[data-target]');
    statNumbers.forEach(stat => {
        counterObserver.observe(stat);
    });

    // Observe all department background texts
    const departmentTexts = document.querySelectorAll('.department-bg-text');
    departmentTexts.forEach(text => {
        departmentObserver.observe(text);
    });

    // Smooth scroll for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Video Polaroid Hover Effect
    const videoPolaroids = document.querySelectorAll('.video-polaroid');
    videoPolaroids.forEach(polaroid => {
        const video = polaroid.querySelector('.polaroid-video');

        if (video) {
            // Get custom start time or default to 0
            const startTime = parseFloat(video.getAttribute('data-start-time')) || 0;

            // Set initial start time when video loads
            video.addEventListener('loadedmetadata', () => {
                video.currentTime = startTime;
            });

            polaroid.addEventListener('mouseenter', () => {
                video.play();
            });

            polaroid.addEventListener('mouseleave', () => {
                video.pause();
                video.currentTime = startTime; // Reset to chosen frame
            });

            // Reset to custom start time when video ends
            video.addEventListener('ended', () => {
                video.pause();
                video.currentTime = startTime;
            });
        }
    });
});