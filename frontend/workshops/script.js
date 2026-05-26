// Handle polaroid video hover
document.querySelectorAll('.video-polaroid').forEach(polaroid => {
    const video = polaroid.querySelector('.polaroid-video');
    if (video) {
        polaroid.addEventListener('mouseenter', () => {
            video.play().catch(e => console.log('Video play failed:', e));
        });

        polaroid.addEventListener('mouseleave', () => {
            video.pause();
            video.currentTime = 0;
        });
    }
});

// Handle unified form submission
const form = document.getElementById('unified-workshop-form');
if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const workshop = formData.get('workshop');
        const name = formData.get('name');
        const surname = formData.get('surname');
        const identifier = formData.get('identifier');
        const rating = formData.get('rating');

        // Validate all fields
        if (!workshop) {
            alert('Please select a workshop.');
            return;
        }

        if (!name || !surname || !identifier) {
            alert('Please fill in all required fields.');
            return;
        }

        if (!rating) {
            alert('Please rate your commitment level by selecting stars.');
            return;
        }

        try {
            const response = await fetch('http://localhost:8000/api/workshop-registrations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    workshop,
                    name,
                    surname,
                    identifier,
                    rating: parseInt(rating, 10)
                })
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => null);
                throw new Error(errorData?.message || 'Failed to register');
            }

            // Success message
            alert(`Registration successful!\n\nWorkshop: ${workshop}\nName: ${name} ${surname}\nMember ID: ${identifier}\nCommitment Level: ${rating} star(s)\n\nWe look forward to seeing you at the workshop!`);

            // Reset form
            form.reset();
        } catch (error) {
            console.error('Error during registration:', error);
            alert(`Registration failed: ${error.message}`);
        }
    });
}
