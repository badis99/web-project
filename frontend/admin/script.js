/**
 * THEATRO — Admin: Workshop Registrations
 *
 * Fetches GET /api/workshop-registrations and renders
 * a sortable, filterable, searchable table with stats.
 */

// ── Config ──────────────────────────────────────────────────────────────────
// Adjust this to your backend's base URL (empty string = same origin)
const API_BASE = '';
const ENDPOINT = `${API_BASE}/api/workshop-registrations`;

// ── State ────────────────────────────────────────────────────────────────────
let allRegistrations = [];  // raw data from API
let filtered = [];          // after search + filter
let currentFilter = 'all';
let currentSort = 'date-desc';
let searchQuery = '';

// ── DOM refs ─────────────────────────────────────────────────────────────────
const loadingEl   = document.getElementById('loading-state');
const errorEl     = document.getElementById('error-state');
const errorMsgEl  = document.getElementById('error-message');
const emptyEl     = document.getElementById('empty-state');
const wrapperEl   = document.getElementById('table-wrapper');
const tableFooter = document.getElementById('table-footer');
const tbody       = document.getElementById('table-body');
const countEl     = document.getElementById('result-count');
const searchInput = document.getElementById('search-input');
const sortSelect  = document.getElementById('sort-select');
const exportBtn   = document.getElementById('export-btn');
const retryBtn    = document.getElementById('retry-btn');

// Stats
const statTotalEl  = document.getElementById('stat-total-val');
const statActingEl = document.getElementById('stat-acting-val');
const statDanceEl  = document.getElementById('stat-dancing-val');
const statRateEl   = document.getElementById('stat-rating-val');

// Filter buttons
const filterBtns = document.querySelectorAll('.filter-btn');

// ── Fetch ─────────────────────────────────────────────────────────────────────
async function fetchRegistrations() {
    showState('loading');
    try {
        const res = await fetch(ENDPOINT, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        const data = await res.json();
        allRegistrations = Array.isArray(data) ? data : [];
        buildStats(allRegistrations);
        applyFiltersAndRender();
    } catch (err) {
        showState('error');
        errorMsgEl.textContent = `Could not load registrations: ${err.message}`;
        console.error('[Admin] Fetch error:', err);
    }
}

// ── Stats ─────────────────────────────────────────────────────────────────────
function buildStats(data) {
    const total   = data.length;
    const acting  = data.filter(r => r.workshop === 'Acting Workshop').length;
    const dancing = data.filter(r => r.workshop === 'Dancing Workshop').length;
    const avgRate = total > 0
        ? (data.reduce((s, r) => s + Number(r.rating || 0), 0) / total).toFixed(1)
        : '—';

    statTotalEl.textContent  = total;
    statActingEl.textContent = acting;
    statDanceEl.textContent  = dancing;
    statRateEl.textContent   = total > 0 ? `${avgRate} ★` : '—';
}

// ── Filter + Sort ─────────────────────────────────────────────────────────────
function applyFiltersAndRender() {
    let data = [...allRegistrations];

    // Workshop filter
    if (currentFilter !== 'all') {
        data = data.filter(r => r.workshop === currentFilter);
    }

    // Search
    if (searchQuery) {
        const q = searchQuery.toLowerCase();
        data = data.filter(r =>
            (r.name      || '').toLowerCase().includes(q) ||
            (r.surname   || '').toLowerCase().includes(q) ||
            (r.identifier|| '').toLowerCase().includes(q) ||
            (r.workshop  || '').toLowerCase().includes(q)
        );
    }

    // Sort
    data.sort((a, b) => {
        switch (currentSort) {
            case 'date-desc': return dateOf(b) - dateOf(a);
            case 'date-asc':  return dateOf(a) - dateOf(b);
            case 'rating-desc': return Number(b.rating || 0) - Number(a.rating || 0);
            case 'rating-asc':  return Number(a.rating || 0) - Number(b.rating || 0);
            case 'name-asc':
                return (a.name || '').localeCompare(b.name || '');
            default: return 0;
        }
    });

    filtered = data;
    renderTable(filtered);
}

function dateOf(r) {
    return r.createdAt ? new Date(r.createdAt).getTime() : 0;
}

// ── Render Table ──────────────────────────────────────────────────────────────
function renderTable(data) {
    tbody.innerHTML = '';

    if (data.length === 0) {
        showState('empty');
        return;
    }

    showState('table');

    data.forEach((reg, idx) => {
        const tr = document.createElement('tr');
        tr.style.animationDelay = `${idx * 0.03}s`;
        tr.innerHTML = `
            <td class="cell-id">${idx + 1}</td>
            <td>${workshopBadge(reg.workshop)}</td>
            <td class="cell-name">${esc(reg.name)}</td>
            <td>${esc(reg.surname)}</td>
            <td class="cell-identifier">${esc(reg.identifier)}</td>
            <td>${starRating(reg.rating)}</td>
            <td class="cell-date">${formatDate(reg.createdAt)}</td>
        `;
        tbody.appendChild(tr);
    });

    countEl.textContent = `${data.length} result${data.length !== 1 ? 's' : ''}`;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function esc(str) {
    if (str == null) return '—';
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}

function workshopBadge(workshop) {
    if (!workshop) return '<span class="workshop-badge other">—</span>';
    const lower = workshop.toLowerCase();
    const cls   = lower.includes('acting') ? 'acting'
                : lower.includes('danc')   ? 'dancing'
                : 'other';
    const icon  = cls === 'acting' ? '🎭' : cls === 'dancing' ? '💃' : '✦';
    return `<span class="workshop-badge ${cls}">${icon} ${esc(workshop)}</span>`;
}

function starRating(rating) {
    const n = Math.min(5, Math.max(0, Number(rating) || 0));
    let html = '<span class="star-rating">';
    for (let i = 1; i <= 5; i++) {
        html += `<span class="star ${i <= n ? 'filled' : ''}">★</span>`;
    }
    html += '</span>';
    return html;
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        if (isNaN(d)) return '—';
        return d.toLocaleDateString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    } catch { return '—'; }
}

// ── State Machine ─────────────────────────────────────────────────────────────
function showState(state) {
    loadingEl.classList.add('hidden');
    errorEl.classList.add('hidden');
    emptyEl.classList.add('hidden');
    wrapperEl.classList.add('hidden');
    tableFooter.classList.add('hidden');

    if (state === 'loading') loadingEl.classList.remove('hidden');
    if (state === 'error')   errorEl.classList.remove('hidden');
    if (state === 'empty')   emptyEl.classList.remove('hidden');
    if (state === 'table') {
        wrapperEl.classList.remove('hidden');
        tableFooter.classList.remove('hidden');
    }
}

// ── Export CSV ────────────────────────────────────────────────────────────────
function exportCSV() {
    if (!filtered.length) return;
    const headers = ['#', 'Workshop', 'Name', 'Surname', 'Identifier', 'Rating', 'Date'];
    const rows = filtered.map((r, i) => [
        i + 1,
        csvCell(r.workshop),
        csvCell(r.name),
        csvCell(r.surname),
        csvCell(r.identifier),
        r.rating ?? '',
        r.createdAt ? new Date(r.createdAt).toISOString() : ''
    ]);

    const csv = [headers, ...rows]
        .map(row => row.join(','))
        .join('\r\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `workshop-registrations-${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function csvCell(val) {
    if (val == null) return '';
    const s = String(val).replace(/"/g, '""');
    return s.includes(',') || s.includes('"') || s.includes('\n') ? `"${s}"` : s;
}

// ── Event Listeners ───────────────────────────────────────────────────────────

// Search
let searchTimer;
searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        searchQuery = searchInput.value.trim();
        applyFiltersAndRender();
    }, 200);
});

// Filter buttons
filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = btn.dataset.filter;
        applyFiltersAndRender();
    });
});

// Sort
sortSelect.addEventListener('change', () => {
    currentSort = sortSelect.value;
    applyFiltersAndRender();
});

// Export
exportBtn.addEventListener('click', exportCSV);

// Retry
retryBtn.addEventListener('click', fetchRegistrations);

// ── Boot ──────────────────────────────────────────────────────────────────────
fetchRegistrations();
