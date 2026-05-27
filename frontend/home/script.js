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

//UPCOMING SHOW FLOATING EFFECT
document.addEventListener('DOMContentLoaded', () => {
    const section = document.querySelector('.upcoming-shows');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                section.classList.add('visible');  // section is in viewport → animate
            } else {
                section.classList.remove('visible'); // section left viewport → reset: bech ki naawed I scroll back w narjaa lel section floating yaawed ysir
            }
        });
    }, { threshold: 0.2 });

    observer.observe(section);
});

//UPCOMING SHOW SWITCH EEFECT
document.addEventListener('DOMContentLoaded', () => {

    const section = document.querySelector('.upcoming-shows');

    // Matrix of 2 images per box
    const imageGroups = [
        ['../assets/0.jpg', '../assets/10.jpg'],
        ['../assets/1.jpg', '../assets/11.jpg'],
        ['../assets/2.jpg', '../assets/12.jpg'],
        ['../assets/3.jpg', '../assets/13.jpg'],
        ['../assets/4.jpg', '../assets/14.jpg'],
        ['../assets/5.jpg', '../assets/15.jpg'],
        ['../assets/6.jpg', '../assets/16.jpg'],
        ['../assets/7.jpg', '../assets/17.jpg'],
        ['../assets/8.jpg', '../assets/18.jpg'],
        ['../assets/9.jpg', '../assets/19.jpg']
    ];

    const boxes = document.querySelectorAll(
        '.upcoming-shows .box1, .upcoming-shows .box2, .upcoming-shows .box3, .upcoming-shows .box4, .upcoming-shows .box5, .upcoming-shows .box6, .upcoming-shows .box7, .upcoming-shows .box8, .upcoming-shows .box9, .upcoming-shows .box10'
    );

    // ---------------------------
    // Preload all images
    // ---------------------------
    imageGroups.forEach(group => {
        group.forEach(src => {
            const img = new Image();
            img.src = src;
        });
    });

    // ---------------------------
    // Floating animation observer
    // ---------------------------
let hasAnimated = false;

const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !hasAnimated) {
            section.classList.add('animated'); // 👈 NEW class
            hasAnimated = true;
            observer.disconnect();
        }
    });
}, { threshold: 0.2 });

observer.observe(section);

    // ---------------------------
    // Smooth slideshow inside boxes
    // ---------------------------
    const currentIndexes = Array(boxes.length).fill(0);

setInterval(() => {
    boxes.forEach((box, i) => {
        const img = box.querySelector('img');
        if (!img) return;

        // Prevent stacking listeners if a transition is still in progress
        if (img.dataset.transitioning === 'true') return;
        img.dataset.transitioning = 'true';

        // Step 1: fade out
        img.style.opacity = '0';

        // Step 2: swap ONLY after the fade-out transition actually ends
        img.addEventListener('transitionend', function onFadeOut(e) {
            if (e.propertyName !== 'opacity') return; // ignore non-opacity transitions
            img.removeEventListener('transitionend', onFadeOut);

            // Swap image
            currentIndexes[i] = (currentIndexes[i] + 1) % imageGroups[i].length;
            const nextSrc = imageGroups[i][currentIndexes[i]];

            // Step 3: fade in only after the new image is ready
            const preloaded = new Image();
            preloaded.onload = () => {
                img.src = nextSrc;
                // Force a reflow so the browser registers opacity: 0 before transitioning to 1
                void img.offsetWidth;
                img.style.opacity = '1';

                img.addEventListener('transitionend', function onFadeIn(e) {
                    if (e.propertyName !== 'opacity') return;
                    img.removeEventListener('transitionend', onFadeIn);
                    img.dataset.transitioning = 'false';
                });
            };
            preloaded.src = nextSrc; // hits cache instantly if preloaded
        });
    });
}, 3500);

});

/* PREVIOUS SHOWS
   The static import from ../../node_modules breaks on typical PHP hosting (node_modules not served),
   which prevents this whole module from executing (and kills earlier counter animations).
   Use a dynamic import so the rest of the home animations still work even if AnimeJS isn't available.
*/
(async () => {
  try {
    const mod = await import('https://cdn.jsdelivr.net/npm/animejs@4.0.2/+esm');
    const { animate, stagger, splitText, svg } = mod;

    const titles = document.querySelectorAll('.section-title');
    const allChars = [];
    titles.forEach(title => {
      const { chars } = splitText(title, { words: false, chars: true });
      allChars.push(...chars);
    });

    animate(allChars, {
      y: [
        { to: '-2.75rem', ease: 'outExpo', duration: 600 },
        { to: 0, ease: 'outBounce', duration: 800, delay: 100 }
      ],
      rotate: { from: '-1turn', delay: 0 },
      delay: stagger(50),
      ease: 'inOutCirc',
      loopDelay: 600,
      loop: true
    });

    animate(svg.createDrawable('#arrow-path'), {
      draw: '0 1',
      ease: 'linear',
      duration: 5000,
      loop: true,
    });
  } catch (e) {
    // ignore: keeps rest of page animations working
  }
})();

