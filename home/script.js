
document.addEventListener('DOMContentLoaded', () => {
const shows = document.querySelectorAll('.title-upcoming-shows, .show');

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible'); // fade in + float
        }
    });
}, { threshold: 0.3 }); // trigger when 30% visible

shows.forEach(show => observer.observe(show));
});



