const rowLabels = ['O', 'N', 'M', 'L', 'K', 'J', 'I', 'H', 'G', 'F', 'E', 'D', 'C', 'B', 'A'];

const sectionConfig = {
    left: {
        rows: 15,
        seatsPerRow: [6, 7, 8, 9, 10, 11, 12, 14, 15, 16, 16, 16, 16, 16, 16],
        alignment: 'right'
    },
    center: {
        rows: 15,
        seatsPerRow: [8, 11, 13, 14, 15, 16, 16, 16, 16, 16, 16, 16, 16, 16, 16],
        alignment: 'center'
    },
    right: {
        rows: 15,
        seatsPerRow: [6, 7, 8, 9, 10, 11, 12, 14, 15, 16, 16, 16, 16, 16, 16],
        alignment: 'left'
    }
};

const API_BASE = '/backend/routes/api.php?rest=';
const selectedSeats = new Map(); // key: section:row:number -> {section,row,number}
const releaseSelectedSeats = new Map(); // occupied seats to release
let releaseMode = false;

function createSection(sectionId, config) {
    const container = document.getElementById(`${sectionId}-section`);
    const leftLabels = document.getElementById(`${sectionId}-labels-left`);
    const rightLabels = document.getElementById(`${sectionId}-labels-right`);
    const numbersContainer = document.getElementById(`${sectionId}-numbers`);

    const maxSeats = Math.max(...config.seatsPerRow);
    const seatWidth = 16;
    const gapWidth = 2.5;

    // Create seat numbers (always show all 16 numbers)
    for (let i = 1; i <= maxSeats; i++) {
        const numDiv = document.createElement('div');
        numDiv.className = 'seat-number';
        numDiv.textContent = i;
        numbersContainer.appendChild(numDiv);
    }

    // Create rows
    config.seatsPerRow.forEach((seatCount, rowIndex) => {
        const row = document.createElement('div');
        row.className = 'seat-row';

        let paddingLeft = 0;
        let startSeatNumber = 1;

        // Calculate padding based on alignment
        if (config.alignment === 'center') {
            // Calculate total width of current row
            const currentRowWidth = (seatCount * seatWidth) + ((seatCount - 1) * gapWidth);
            // Calculate total width of max row
            const maxRowWidth = (maxSeats * seatWidth) + ((maxSeats - 1) * gapWidth);
            // Center the current row
            paddingLeft = (maxRowWidth - currentRowWidth) / 2;

            // Calculate which seat number to start from based on padding
            startSeatNumber = Math.round(paddingLeft / (seatWidth + gapWidth)) + 1;
        } else if (config.alignment === 'right') {
            const currentRowWidth = (seatCount * seatWidth) + ((seatCount - 1) * gapWidth);
            const maxRowWidth = (maxSeats * seatWidth) + ((maxSeats - 1) * gapWidth);
            paddingLeft = maxRowWidth - currentRowWidth;
            startSeatNumber = Math.round(paddingLeft / (seatWidth + gapWidth)) + 1;
        } else if (config.alignment === 'left') {
            paddingLeft = 0;
            startSeatNumber = 1;
        }

        // Apply padding as a style instead of invisible divs
        if (paddingLeft > 0) {
            row.style.paddingLeft = `${paddingLeft}px`;
        }

        // Add seats
        for (let i = 0; i < seatCount; i++) {
            const seat = document.createElement('div');
            seat.className = 'seat';
            seat.dataset.row = rowLabels[rowIndex];
            seat.dataset.seat = startSeatNumber + i;
            seat.dataset.section = sectionId;

            seat.addEventListener('click', toggleSeat);
            row.appendChild(seat);
        }

        container.appendChild(row);

        // Add row labels
        const leftLabel = document.createElement('div');
        leftLabel.className = 'row-label';
        leftLabel.textContent = rowLabels[rowIndex];
        leftLabels.appendChild(leftLabel);

        const rightLabel = document.createElement('div');
        rightLabel.className = 'row-label';
        rightLabel.textContent = rowLabels[rowIndex];
        rightLabels.appendChild(rightLabel);
    });
}

function toggleSeat(e) {
    const seat = e.target;
    if (seat.classList.contains('occupied')) {
        if (!releaseMode) return;

        const section = String(seat.dataset.section || '');
        const row = String(seat.dataset.row || '');
        const number = parseInt(String(seat.dataset.seat || '0'), 10);
        const key = `${section}:${row}:${number}`;

        if (seat.classList.contains('release-selected')) {
            seat.classList.remove('release-selected');
            releaseSelectedSeats.delete(key);
            return;
        }

        seat.classList.add('release-selected');
        releaseSelectedSeats.set(key, { section, row, number });
        return;
    }

    const section = String(seat.dataset.section || '');
    const row = String(seat.dataset.row || '');
    const number = parseInt(String(seat.dataset.seat || '0'), 10);
    const key = `${section}:${row}:${number}`;

    if (seat.classList.contains('selected')) {
        seat.classList.remove('selected');
        selectedSeats.delete(key);
        return;
    }

    seat.classList.add('selected');
    selectedSeats.set(key, { section, row, number });
}

function markSeatAsOccupied(section, row, seatNum) {
    const seat = document.querySelector(
        `.seat[data-section="${section}"][data-row="${row}"][data-seat="${seatNum}"]`
    );
    if (seat) {
        seat.classList.add('occupied');
        seat.classList.remove('selected');
    }
}

async function apiFetch(path, options = {}) {
    const restPath = String(path || '').replace(/^\//, '');
    // IMPORTANT: don't encode '/' in the rest path, otherwise backend routing won't match.
    // Encode each segment, then re-join with '/'.
    const safeRestPath = restPath
        .split('/')
        .map(seg => encodeURIComponent(seg))
        .join('/');

    const res = await fetch(`${API_BASE}${safeRestPath}`, {
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
        ...options
    });
    let data = null;
    try { data = await res.json(); } catch (_) {}
    if (!res.ok) {
        const msg = data?.error || `Request failed (${res.status})`;
        const err = new Error(msg);
        err.status = res.status;
        throw err;
    }
    return data;
}

function getQueryParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        showId: params.get('show_id'),
        showName: params.get('show_name')
    };
}

function setShowIdInUrl(showId) {
    const url = new URL(window.location.href);
    url.searchParams.set('show_id', showId);
    url.searchParams.delete('show_name');
    window.history.replaceState({}, '', url.toString());
}

async function resolveShowId() {
    const { showId, showName } = getQueryParams();
    if (showId) return showId;

    if (showName) {
        // Try resolve by name first (public), then ensure if not found (admin).
        try {
            const show = await apiFetch(`/shows/name/${encodeURIComponent(showName)}`);
            const id = show?.id;
            if (id) {
                setShowIdInUrl(id);
                return id;
            }
        } catch (e) {
            // ignore and fallback to ensure
        }

        const ensured = await apiFetch('/shows/ensure', {
            method: 'POST',
            body: JSON.stringify({ name: showName })
        });
        const id = ensured?.id;
        if (!id) throw new Error('Failed to resolve show id.');
        setShowIdInUrl(id);
        return id;
    }

    const shows = await apiFetch('/shows');
    if (!Array.isArray(shows) || shows.length === 0) {
        throw new Error('No shows available.');
    }
    const id = shows[0].id;
    setShowIdInUrl(id);
    return id;
}

async function hydrateOccupiedSeats(showId) {
    const seats = await apiFetch(`/shows/${encodeURIComponent(showId)}/seats`);
    if (!Array.isArray(seats)) return;

    seats.forEach(s => {
        if (s?.is_occupied) {
            markSeatAsOccupied(String(s.section), String(s.row), String(s.number));
        }
    });
}

async function confirmBooking(showId) {
    const seats = Array.from(selectedSeats.values());
    if (seats.length === 0) return;

    const requests = seats.map(s =>
        apiFetch('/tickets', {
            method: 'POST',
            body: JSON.stringify({
                show_id: showId,
                section: s.section,
                row: s.row,
                number: s.number,
                status: 'reserved'
            })
        }).then(() => ({ ok: true, seat: s }))
            .catch(err => ({ ok: false, seat: s, err }))
    );

    const results = await Promise.all(requests);
    let hadConflict = false;
    let hadOtherError = false;

    results.forEach(r => {
        const key = `${r.seat.section}:${r.seat.row}:${r.seat.number}`;
        if (r.ok) {
            markSeatAsOccupied(r.seat.section, r.seat.row, r.seat.number);
            selectedSeats.delete(key);
            return;
        }
        if (r.err?.status === 409) {
            hadConflict = true;
            markSeatAsOccupied(r.seat.section, r.seat.row, r.seat.number);
            selectedSeats.delete(key);
            return;
        }
        hadOtherError = true;
    });

    if (hadConflict) {
        alert('Some selected seats were already booked. The chart was updated.');
    } else if (hadOtherError) {
        alert('Some seats could not be booked. Please try again.');
    }
}

async function releaseSeats(showId) {
    const seats = Array.from(releaseSelectedSeats.values());
    if (seats.length === 0) return;

    const requests = seats.map(s =>
        apiFetch('/tickets/release', {
            method: 'POST',
            body: JSON.stringify({
                show_id: showId,
                section: s.section,
                row: s.row,
                number: s.number
            })
        }).then(() => ({ ok: true, seat: s }))
          .catch(err => ({ ok: false, seat: s, err }))
    );

    const results = await Promise.all(requests);
    let hadError = false;

    results.forEach(r => {
        const key = `${r.seat.section}:${r.seat.row}:${r.seat.number}`;
        if (r.ok) {
            const el = document.querySelector(`.seat[data-section="${r.seat.section}"][data-row="${r.seat.row}"][data-seat="${r.seat.number}"]`);
            if (el) {
                el.classList.remove('occupied');
                el.classList.remove('release-selected');
            }
            releaseSelectedSeats.delete(key);
        } else {
            hadError = true;
        }
    });

    if (hadError) {
        alert('Some seats could not be released. Please try again.');
    }
}

function ensureConfirmButton() {
    // If the HTML doesn’t already have it, inject a floating button.
    let btn = document.getElementById('confirm-booking-btn');
    if (btn) return btn;

    btn = document.createElement('button');
    btn.id = 'confirm-booking-btn';
    btn.textContent = 'Confirm Booking';
    btn.style.position = 'fixed';
    btn.style.right = '24px';
    btn.style.bottom = '24px';
    btn.style.zIndex = '300';
    btn.style.padding = '12px 18px';
    btn.style.borderRadius = '10px';
    btn.style.border = '1px solid rgba(255,255,255,0.25)';
    btn.style.background = 'rgba(255,255,255,0.08)';
    btn.style.color = 'white';
    btn.style.cursor = 'pointer';
    document.body.appendChild(btn);
    return btn;
}

async function boot() {
    createSection('left', sectionConfig.left);
    createSection('center', sectionConfig.center);
    createSection('right', sectionConfig.right);

    const showId = await resolveShowId();
    await hydrateOccupiedSeats(showId);

    const btn = ensureConfirmButton();
    btn.addEventListener('click', async () => {
        btn.disabled = true;
        try {
            await confirmBooking(showId);
        } catch (e) {
            alert(e?.message || 'Booking failed.');
        } finally {
            btn.disabled = false;
        }
    });

    const releaseBtn = document.getElementById('release-booking-btn');
    if (releaseBtn) {
        releaseBtn.addEventListener('click', async () => {
            // First click toggles release mode, second click performs release (if any selected)
            if (!releaseMode) {
                releaseMode = true;
                releaseBtn.textContent = 'Confirm release';
                return;
            }

            releaseBtn.disabled = true;
            try {
                await releaseSeats(showId);
            } catch (e) {
                alert(e?.message || 'Release failed.');
            } finally {
                releaseBtn.disabled = false;
                releaseMode = false;
                releaseBtn.textContent = 'Release selected seats';
            }
        });
    }
}

boot().catch(err => {
    alert(err?.message || 'Failed to load booking page.');
});
