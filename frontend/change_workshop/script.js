// ============================================================
// ADMIN – Manage Workshops
// Calls:  GET    /workshops
//         POST   /workshops/upload-video  (ADMIN, multipart)
//         POST   /workshops              (ADMIN)
//         PATCH  /workshops/:id          (ADMIN)
//         DELETE /workshops/:id          (ADMIN)
// ============================================================

const API_BASE = '/backend/routes/api.php?rest=';
const WORKSHOPS_ENDPOINT = `${API_BASE}workshops`;

// ─── Guard: only logged-in users may use this page ──────────
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res = await fetch('/backend/routes/api.php?action=check-session', { credentials: 'include' });
        const data = await parseJsonSafe(res, {});
        if (!data.logged_in || data.role !== 'ADMIN') {
            window.location.href = '../auth/login.html';
            return;
        }
    } catch {
        window.location.href = '../auth/login.html';
        return;
    }
    loadWorkshops();
    bindAddForm();
    bindEditForm();
    bindDeleteModal();
});

// ─── Helpers ────────────────────────────────────────────────
function authHeaders() {
    return { 'Content-Type': 'application/json' };
}

async function parseJsonSafe(res, fallback = {}) {
    const contentType = (res.headers.get('content-type') || '').toLowerCase();
    if (!contentType.includes('application/json')) {
        return fallback;
    }
    return res.json().catch(() => fallback);
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type}`;
    setTimeout(() => toast.classList.add('hidden'), 3500);
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
}

function toInputDate(iso) {
    if (!iso) return '';
    return iso.slice(0, 10);
}

function setLoading(btnId, loading) {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    const text = btn.querySelector('.btn-text');
    const spin = btn.querySelector('.btn-spinner');
    btn.disabled = loading;
    if (text) text.classList.toggle('hidden', loading);
    if (spin) spin.classList.toggle('hidden', !loading);
}

function showProgress(progressId, show) {
    const el = document.getElementById(progressId);
    if (el) el.classList.toggle('hidden', !show);
}

// ─── Upload video directly to Cloudinary (signed via backend) ─
async function uploadVideo(file, progressId) {
    showProgress(progressId, true);

    // 1. Get a short-lived signature from our backend (secret never leaves server)
    const signRes = await fetch('/backend/cloudinary/sign.php', { credentials: 'include' });
    if (!signRes.ok) throw new Error('Could not get upload signature from server.');
    const { cloud_name, api_key, signature, timestamp, folder } = await signRes.json();

    // 2. Upload the file directly from the browser to Cloudinary
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('file',      file);
        formData.append('api_key',   api_key);
        formData.append('timestamp', timestamp);
        formData.append('signature', signature);
        formData.append('folder',    folder);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', `https://api.cloudinary.com/v1_1/${cloud_name}/video/upload`);

        // Real upload progress
        xhr.upload.addEventListener('progress', (e) => {
            if (!e.lengthComputable) return;
            const pct = Math.round((e.loaded / e.total) * 100);
            const txt = document.querySelector(`#${progressId} .upload-progress-text`);
            if (txt) txt.textContent = `Uploading… ${pct}%`;
        });

        xhr.addEventListener('load', () => {
            showProgress(progressId, false);
            if (xhr.status === 200) {
                const data = JSON.parse(xhr.responseText);
                resolve(data.secure_url);
            } else {
                let msg = 'Upload failed.';
                try { msg = JSON.parse(xhr.responseText)?.error?.message || msg; } catch {}
                reject(new Error(msg));
            }
        });

        xhr.addEventListener('error', () => {
            showProgress(progressId, false);
            reject(new Error('Network error during upload. Please try again.'));
        });

        xhr.send(formData);
    });
}

// ─── LOAD all workshops ──────────────────────────────────────
async function loadWorkshops() {
    const listEl = document.getElementById('workshops-list');
    const loadEl = document.getElementById('workshops-loading');
    const errorEl = document.getElementById('workshops-error');
    const countEl = document.getElementById('workshops-count');

    listEl.innerHTML = '';
    loadEl.classList.remove('hidden');
    errorEl.classList.add('hidden');

    try {
        const res = await fetch(WORKSHOPS_ENDPOINT, { credentials: 'include' });
        if (!res.ok) throw new Error('Failed to load workshops');
        const workshops = await parseJsonSafe(res, []);
        if (!Array.isArray(workshops)) throw new Error('Invalid workshops payload');

        loadEl.classList.add('hidden');
        countEl.textContent = `${workshops.length} workshop${workshops.length !== 1 ? 's' : ''}`;

        if (workshops.length === 0) {
            listEl.innerHTML = '<p class="state-message">No workshops yet. Add one above!</p>';
            return;
        }

        workshops.forEach(ws => listEl.appendChild(buildWorkshopRow(ws)));

    } catch (err) {
        loadEl.classList.add('hidden');
        errorEl.classList.remove('hidden');
        errorEl.textContent = err.message || 'Could not load workshops.';
    }
}

// ─── Build a workshop row card ───────────────────────────────
function buildWorkshopRow(ws) {
    const card = document.createElement('div');
    card.className = 'ws-row';
    card.dataset.id = ws.id;

    const videoHtml = ws.videoUrl
        ? `<div class="ws-row-video">
               <video src="${ws.videoUrl}" controls class="ws-video-player" preload="none"></video>
           </div>`
        : '';

    card.innerHTML = `
        <div class="ws-row-info">
            <span class="ws-row-title">${ws.title}</span>
            <span class="ws-row-meta">${ws.departement ?? '—'} &bull; ${formatDate(ws.date)}</span>
            <p class="ws-row-desc">${ws.description}</p>
            ${videoHtml}
        </div>
        <div class="ws-row-actions">
            <button class="btn-icon btn-edit" data-id="${ws.id}" title="Edit workshop">
                ✎ Edit
            </button>
            <button class="btn-icon btn-del" data-id="${ws.id}" title="Delete workshop">
                ✕ Delete
            </button>
        </div>
    `;

    card.querySelector('.btn-edit').addEventListener('click', () => openEditModal(ws));
    card.querySelector('.btn-del').addEventListener('click', () => openDeleteModal(ws));

    return card;
}

// ─── ADD FORM ────────────────────────────────────────────────
function bindAddForm() {
    document.getElementById('add-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const title = document.getElementById('new-title').value.trim();
        const date = document.getElementById('new-date').value;
        const departement = document.getElementById('new-departement').value;
        const description = document.getElementById('new-description').value.trim();
        const videoFile = document.getElementById('new-video').files[0];

        if (!title || !date || !departement || !description) {
            showToast('Please fill in all required fields.', 'error');
            return;
        }

        setLoading('add-btn', true);

        try {
            // 1. Upload video to Cloudinary (if provided)
            let videoUrl = null;
            if (videoFile) {
                videoUrl = await uploadVideo(videoFile, 'new-video-progress');
            }

            // 2. Create the workshop record
            const res = await fetch(WORKSHOPS_ENDPOINT, {
                method: 'POST',
                headers: authHeaders(),
                credentials: 'include',
                body: JSON.stringify({ title, date, departement, description, videoUrl }),
            });
            const data = await parseJsonSafe(res, {});
            if (!res.ok) throw new Error(data.message || 'Failed to create workshop');

            showToast(`"${title}" created successfully!`);
            document.getElementById('add-form').reset();
            await loadWorkshops();

        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            setLoading('add-btn', false);
        }
    });
}

// ─── EDIT MODAL ──────────────────────────────────────────────
// Tracks whether the admin clicked "Remove video" in the modal
let editVideoRemoved = false;

function openEditModal(ws) {
    editVideoRemoved = false;

    document.getElementById('edit-id').value = ws.id;
    document.getElementById('edit-title').value = ws.title;
    document.getElementById('edit-date').value = toInputDate(ws.date);
    document.getElementById('edit-departement').value = ws.departement ?? '';
    document.getElementById('edit-description').value = ws.description;

    // Show existing video preview if present
    const previewWrap = document.getElementById('edit-current-video');
    const previewVid = document.getElementById('edit-video-preview');
    if (ws.videoUrl) {
        previewVid.src = ws.videoUrl;
        previewWrap.classList.remove('hidden');
    } else {
        previewVid.src = '';
        previewWrap.classList.add('hidden');
    }

    // Clear the file input
    document.getElementById('edit-video').value = '';

    document.getElementById('edit-modal').classList.remove('hidden');
    document.getElementById('edit-title').focus();
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
}

function bindEditForm() {
    document.getElementById('modal-close-btn').addEventListener('click', closeEditModal);
    document.getElementById('modal-cancel-btn').addEventListener('click', closeEditModal);

    // Close on overlay click
    document.getElementById('edit-modal').addEventListener('click', (e) => {
        if (e.target === e.currentTarget) closeEditModal();
    });

    // "Remove video" button inside edit modal
    document.getElementById('edit-remove-video-btn').addEventListener('click', () => {
        editVideoRemoved = true;
        document.getElementById('edit-current-video').classList.add('hidden');
        document.getElementById('edit-video-preview').src = '';
    });

    document.getElementById('edit-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('edit-id').value;
        const title = document.getElementById('edit-title').value.trim();
        const date = document.getElementById('edit-date').value;
        const departement = document.getElementById('edit-departement').value;
        const description = document.getElementById('edit-description').value.trim();
        const videoFile = document.getElementById('edit-video').files[0];

        if (!title || !date || !departement || !description) {
            showToast('Please fill in all fields.', 'error');
            return;
        }

        const saveBtn = document.getElementById('edit-save-btn');
        saveBtn.disabled = true;

        try {
            // Determine the videoUrl to send:
            //  • new file selected  → upload it, send new URL
            //  • remove clicked     → send null to clear
            //  • nothing changed    → send undefined (service will keep existing)
            let videoUrl;
            if (videoFile) {
                videoUrl = await uploadVideo(videoFile, 'edit-video-progress');
            } else if (editVideoRemoved) {
                videoUrl = null;
            }
            // else: videoUrl stays undefined → not included in PATCH body

            const body = { title, date, departement, description };
            if (videoUrl !== undefined) body.videoUrl = videoUrl;

            const res = await fetch(`${WORKSHOPS_ENDPOINT}/${encodeURIComponent(id)}`, {
                method: 'PUT',
                headers: authHeaders(),
                credentials: 'include',
                body: JSON.stringify(body),
            });
            const data = await parseJsonSafe(res, {});
            if (!res.ok) throw new Error(data.message || 'Failed to update workshop');

            showToast(`"${title}" updated successfully!`);
            closeEditModal();
            await loadWorkshops();

        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            saveBtn.disabled = false;
        }
    });
}

// ─── DELETE MODAL ────────────────────────────────────────────
let pendingDeleteId = null;

function openDeleteModal(ws) {
    pendingDeleteId = ws.id;
    document.getElementById('delete-confirm-text').textContent =
        `Are you sure you want to permanently delete "${ws.title}"? This cannot be undone.`;
    document.getElementById('delete-modal').classList.remove('hidden');
}

function closeDeleteModal() {
    pendingDeleteId = null;
    document.getElementById('delete-modal').classList.add('hidden');
}

function bindDeleteModal() {
    document.getElementById('delete-cancel-btn').addEventListener('click', closeDeleteModal);
    document.getElementById('delete-modal').addEventListener('click', (e) => {
        if (e.target === e.currentTarget) closeDeleteModal();
    });

    document.getElementById('delete-confirm-btn').addEventListener('click', async () => {
        if (!pendingDeleteId) return;

        const btn = document.getElementById('delete-confirm-btn');
        btn.disabled = true;
        btn.textContent = 'Deleting…';

        try {
            const res = await fetch(`${WORKSHOPS_ENDPOINT}/${encodeURIComponent(pendingDeleteId)}`, {
                method: 'DELETE',
                headers: authHeaders(),
                credentials: 'include',
            });
            const data = await parseJsonSafe(res, {});
            if (!res.ok) throw new Error(data.message || 'Failed to delete workshop');

            showToast('Workshop deleted.');
            closeDeleteModal();
            await loadWorkshops();

        } catch (err) {
            showToast(err.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Delete';
        }
    });
}
