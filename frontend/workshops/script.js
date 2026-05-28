const WORKSHOPS_ENDPOINT = '/backend/routes/api.php?rest=workshops';
const REGISTRATION_ENDPOINT = '/backend/routes/api.php?rest=workshop-registrations';

const workshopsContainer = document.querySelector('.workshops-info-container');
const workshopSelect = document.getElementById('workshop-selection');
const form = document.getElementById('unified-workshop-form');

let workshops = [];

function bindPolaroidHover() {
    document.querySelectorAll('.video-polaroid').forEach(polaroid => {
        const video = polaroid.querySelector('.polaroid-video');
        if (!video || polaroid.dataset.hoverBound === 'true') return;

        polaroid.addEventListener('mouseenter', () => {
            video.play().catch(e => console.log('Video play failed:', e));
        });

        polaroid.addEventListener('mouseleave', () => {
            video.pause();
            video.currentTime = 0;
        });

        polaroid.dataset.hoverBound = 'true';
    });
}

function esc(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function renderWorkshops(items) {
    if (!workshopsContainer) return;

    if (!Array.isArray(items) || items.length === 0) {
        workshopsContainer.innerHTML = '<p class="section-description">No workshops available right now.</p>';
        return;
    }

    workshopsContainer.innerHTML = items.map(workshop => {
        const title = esc(workshop.title || 'Workshop');
        const description = esc(workshop.description || '');
        const videoUrl = workshop.videoUrl || workshop.video_url || '';
        const videoMarkup = videoUrl
            ? `<div class="workshop-polaroid">
                    <div class="polaroid activity-polaroid video-polaroid">
                        <div class="polaroid-img">
                            <video class="polaroid-video" loop muted playsinline>
                                <source src="${esc(videoUrl)}" type="video/mp4">
                                    Your browser does not support the video tag.
                            </video>
                        </div>
                    </div>
               </div>`
            : ``;

        return `
            <div class="workshop-info-section">
                <h3 class="workshop-title">${title}</h3>
                            ${videoMarkup}
                <div class="workshop-full-description">
                    <p>${description || 'No description provided yet.'}</p>
                </div>
            </div>
        `;
    }).join('');

    bindPolaroidHover();
}

function renderWorkshopSelect(items) {
    if (!workshopSelect) return;

    workshopSelect.innerHTML = '<option value="">Choose a workshop...</option>';
    items.forEach(workshop => {
        const option = document.createElement('option');
        option.value = workshop.id;
        option.textContent = workshop.title;
        workshopSelect.appendChild(option);
    });
}

async function fetchWorkshops() {
    const res = await fetch(WORKSHOPS_ENDPOINT, {
        credentials: 'include',
        headers: { Accept: 'application/json' },
    });
    if (!res.ok) return [];
    const contentType = (res.headers.get('content-type') || '').toLowerCase();
    if (!contentType.includes('application/json')) {
        return [];
    }
    const data = await res.json().catch(() => []);
    return Array.isArray(data) ? data : [];
}

if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const workshopId = String(formData.get('workshop') || '').trim();
        const rating = String(formData.get('rating') || '').trim();

        if (!workshopId) {
            alert('Please select a workshop.');
            return;
        }
        if (!rating) {
            alert('Please rate your commitment level by selecting stars.');
            return;
        }

        try {
            const res = await fetch(REGISTRATION_ENDPOINT, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idWorkshop: workshopId,
                    rating: Number(rating),
                }),
            });

            if (!res.ok) {
                const errData = await res.json().catch(() => ({}));
                throw new Error(errData.error || `HTTP ${res.status}`);
            }

            const workshop = workshops.find(w => w.id === workshopId);
            const workshopName = workshop?.title || 'Workshop';
            alert(`Registration successful!\n\nWorkshop: ${workshopName}\nCommitment Level: ${rating} star(s)\n\nWe look forward to seeing you at the workshop!`);
            form.reset();
        } catch (err) {
            alert(`Registration failed: ${err.message}`);
        }
    });
}

(async function initWorkshopsPage() {
    workshops = await fetchWorkshops();
    renderWorkshops(workshops);
    renderWorkshopSelect(workshops);
})();
