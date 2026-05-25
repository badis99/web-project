const CONFIG = {
    registrationPeriod: {
        openDate: "2026-09-01T00:00:00",
        closeDate: "2026-09-30T23:59:59",
        testMode: true
    },
    fieldsOfStudy: {
        firstYear: ["MPI", "CBA","OTHER"],
        upperYears: ["GL", "RT", "IIA", "BIO", "CH", "IMI","OTHER"]
    },
    slideshow: {
        interval: 3000,
        transitionDuration: 1000
    }
};

function initialize() {
    checkRegistrationStatus();
    setupEventListeners();
    initializeSlideshow();
}

function checkRegistrationStatus() {
    const now = new Date();
    const openDate = new Date(CONFIG.registrationPeriod.openDate);
    const closeDate = new Date(CONFIG.registrationPeriod.closeDate);

    const isOpen = CONFIG.registrationPeriod.testMode || (now >= openDate && now <= closeDate);

    const closedScreen = document.getElementById('closedScreen');
    const formScreen = document.getElementById('formScreen');

    if (isOpen) {
        closedScreen.classList.add('hidden');
        formScreen.classList.remove('hidden');
    } else {
        closedScreen.classList.remove('hidden');
        formScreen.classList.add('hidden');
    }
}

function initializeSlideshow() {
    const slideshowContainers = document.querySelectorAll('.slideshow-container');
    
    slideshowContainers.forEach(container => {
        const images = container.querySelectorAll('.slideshow-image');
        
        if (images.length <= 1) {
            console.warn('Slideshow needs at least 2 images to work properly');
            return;
        }
        
        let currentIndex = 0;
        
        setInterval(() => {
            images[currentIndex].classList.remove('active');
            currentIndex = (currentIndex + 1) % images.length;
            images[currentIndex].classList.add('active');
        }, CONFIG.slideshow.interval);
    });
}

function setupEventListeners() {
    const form = document.getElementById('registrationForm');
    const yearSelect = document.getElementById('yearOfStudy');
    const fieldSelect = document.getElementById('fieldOfStudy');
    const closeModalBtn = document.getElementById('closeModal');
    const modalOkBtn = document.getElementById('modalOkBtn');

    form.addEventListener('submit', handleFormSubmit);
    yearSelect.addEventListener('change', handleYearChange);
    closeModalBtn.addEventListener('click', closeModal);
    modalOkBtn.addEventListener('click', closeModal);

    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('input', () => clearFieldError(input));
    });

    document.addEventListener('click', (e) => {
        const modal = document.getElementById('successModal');
        const backdrop = modal.querySelector('.modal-backdrop');
        if (e.target === backdrop) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('successModal');
            if (!modal.classList.contains('hidden')) {
                closeModal();
            }
        }
    });
}

function handleYearChange(e) {
    const year = e.target.value;
    const fieldSelect = document.getElementById('fieldOfStudy');
    
    fieldSelect.innerHTML = '<option value="">Select field</option>';
    
    if (!year) {
        fieldSelect.disabled = true;
        fieldSelect.innerHTML = '<option value="">Select year first</option>';
        return;
    }
    
    fieldSelect.disabled = false;
    
    let fields = [];
    if (year === '1st Year') {
        fields = CONFIG.fieldsOfStudy.firstYear;
    } else if (year !== 'Graduate') {
        fields = CONFIG.fieldsOfStudy.upperYears;
    } else {
        fieldSelect.innerHTML = '<option value="">N/A for Graduate</option>';
        fieldSelect.disabled = true;
        return;
    }
    
    fields.forEach(field => {
        const option = document.createElement('option');
        option.value = field;
        option.textContent = field;
        fieldSelect.appendChild(option);
    });
}

function handleFormSubmit(e) {
    e.preventDefault();

    const form = e.target;
    let isValid = true;

    const fields = [
        'fullName',
        'email',
        'phone',
        'yearOfStudy',
        'fieldOfStudy',
        'department',
        'whyJoin'
    ];

    fields.forEach(fieldName => {
        const field = form.elements[fieldName];
        if (!validateField(field)) {
            isValid = false;
        }
    });

    if (!isValid) {
        return;
    }

    const formData = {
        fullName: form.elements.fullName.value,
        email: form.elements.email.value,
        phone: form.elements.phone.value,
        yearOfStudy: form.elements.yearOfStudy.value,
        fieldOfStudy: form.elements.fieldOfStudy.value,
        department: form.elements.department.value,
        whyJoin: form.elements.whyJoin.value,
        timestamp: new Date().toISOString()
    };

    console.log('Form submitted:', formData);

    showSuccessModal();

    form.reset();
    
    const fieldSelect = document.getElementById('fieldOfStudy');
    fieldSelect.disabled = true;
    fieldSelect.innerHTML = '<option value="">Select year first</option>';
}

function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    let errorMessage = '';

    if (field.disabled) {
        return true;
    }

    switch (fieldName) {
        case 'fullName':
            if (!value) {
                errorMessage = 'Full name is required';
            }
            break;

        case 'email':
            if (!value) {
                errorMessage = 'Email is required';
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                errorMessage = 'Invalid email format';
            }
            break;

        case 'phone':
            if (!value) {
                errorMessage = 'Phone number is required';
            }
            break;

        case 'yearOfStudy':
            if (!value) {
                errorMessage = 'Year of study is required';
            }
            break;

        case 'fieldOfStudy':
            const yearSelect = document.getElementById('yearOfStudy');
            if (yearSelect.value && yearSelect.value !== 'Graduate' && !value) {
                errorMessage = 'Field of study is required';
            }
            break;

        case 'department':
            if (!value) {
                errorMessage = 'Department selection is required';
            }
            break;

        case 'whyJoin':
            if (!value) {
                errorMessage = 'Please tell us why you want to join';
            } else if (value.length < 20) {
                errorMessage = 'Please provide at least 20 characters';
            }
            break;
    }

    if (errorMessage) {
        showFieldError(field, errorMessage);
        return false;
    } else {
        clearFieldError(field);
        return true;
    }
}

function showFieldError(field, message) {
    field.classList.add('error');
    const errorSpan = field.parentElement.querySelector('.error-message');
    if (errorSpan) {
        errorSpan.textContent = message;
    }
}

function clearFieldError(field) {
    field.classList.remove('error');
    const errorSpan = field.parentElement.querySelector('.error-message');
    if (errorSpan) {
        errorSpan.textContent = '';
    }
}

function showSuccessModal() {
    const modal = document.getElementById('successModal');
    modal.classList.remove('hidden');
    
    createConfetti();
    
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('successModal');
    modal.classList.add('hidden');
    
    const confettiContainer = document.querySelector('.confetti-container');
    confettiContainer.innerHTML = '';
    
    document.body.style.overflow = '';
}

function createConfetti() {
    const confettiContainer = document.querySelector('.confetti-container');
    const colors = ['#DAA520', '#FFD700', '#F4C430', '#FFA500'];
    const confettiCount = 30;

    for (let i = 0; i < confettiCount; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        
        const startX = (Math.random() - 0.5) * 200;
        const startY = (Math.random() - 0.5) * 200;
        
        confetti.style.transform = `translate(${startX}px, ${startY}px)`;
        
        confettiContainer.appendChild(confetti);
        
        setTimeout(() => {
            confetti.style.transition = 'all 1.5s ease-out';
            confetti.style.opacity = '1';
            
            setTimeout(() => {
                confetti.style.opacity = '0';
            }, 100);
        }, 300 + i * 50);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
} else {
    initialize();
}