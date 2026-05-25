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
    if (seat.classList.contains('occupied')) return;

    seat.classList.toggle('selected');

    console.log({
        section: seat.dataset.section,
        row: seat.dataset.row,
        seat: seat.dataset.seat,
        selected: seat.classList.contains('selected')
    });
}

// Initialize all sections
createSection('left', sectionConfig.left);
createSection('center', sectionConfig.center);
createSection('right', sectionConfig.right);

// Example: Mark some seats as occupied
function markSeatAsOccupied(section, row, seatNum) {
    const seat = document.querySelector(
        `.seat[data-section="${section}"][data-row="${row}"][data-seat="${seatNum}"]`
    );
    if (seat) {
        seat.classList.add('occupied');
    }
}

// Example usage:
markSeatAsOccupied('center', 'A', 5);
markSeatAsOccupied('left', 'B', 3);