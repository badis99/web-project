const params = new URLSearchParams(window.location.search);
const id = params.get("id");
const profileElement = document.getElementById("profile");
const profileApiUrl = `/backend/Controllers/members_list/profile.php?id=${id}`;
const modifyApiUrl = "/backend/Controllers/members_list/modify_user.php";
const SUPABASE_PROFILE_PICTURES_BASE = "https://luiillhngqpddvlbeeay.supabase.co/storage/v1/object/public/profile-pictures/";

function getProfilePictureUrl(picture) {
    const value = typeof picture === "string" ? picture.trim() : "";

    if (!value) {
        return `${SUPABASE_PROFILE_PICTURES_BASE}default.png`;
    }

    if (/^https?:\/\//i.test(value)) {
        return value;
    }

    return `${SUPABASE_PROFILE_PICTURES_BASE}${value}`;
}

const editableFields = [
    { key: 'firstname', label: 'First Name', type: 'text', icon: 'fa-user' },
    { key: 'lastname', label: 'Last Name', type: 'text', icon: 'fa-user' },
    { key: 'birthdate', label: 'Birthdate', type: 'date', icon: 'fa-cake-candles' },
    { key: 'department', label: 'Department', type: 'text', icon: 'fa-building-columns' },
    { key: 'phone', label: 'Telephone', type: 'tel', icon: 'fa-phone' },
    { key: 'email', label: 'Email', type: 'email', icon: 'fa-envelope' },
    { key: 'fieldofstudy', label: 'Field Of Study', type: 'text', icon: 'fa-book-open' },
    { key: 'yearofstudy', label: 'Year Of Study', type: 'number', icon: 'fa-graduation-cap' }
];

function formatValue(value) {
    return value === null || value === undefined || value === '' ? '' : value;
}

function renderProfile(data) {
    const pictureUrl = getProfilePictureUrl(data.picture);

    const fieldsHtml = [];
    for (let i = 0; i < editableFields.length; i += 2) {
        const first = editableFields[i];
        const second = editableFields[i + 1];

        fieldsHtml.push(`
            <div class="section row">
                <div class="sub-section">
                    <div class="field-header">
                        <h3>${first.label} <i class="fa-solid ${first.icon}" style="color: #D4AF37;"></i></h3>
                        <button class="edit-btn" type="button" data-key="${first.key}">✏️</button>
                    </div>
                    <div class="field-row">
                        <span class="field-text" data-key="${first.key}">${formatValue(data[first.key]) || '-'}</span>
                        <input class="field-input hidden" name="${first.key}" type="${first.type}" value="${formatValue(data[first.key])}">
                    </div>
                </div>
                ${second ? `
                    <div class="sub-section">
                        <div class="field-header">
                            <h3>${second.label} <i class="fa-solid ${second.icon}" style="color: #D4AF37;"></i></h3>
                            <button class="edit-btn" type="button" data-key="${second.key}">✏️</button>
                        </div>
                        <div class="field-row">
                            <span class="field-text" data-key="${second.key}">${formatValue(data[second.key]) || '-'}</span>
                            <input class="field-input hidden" name="${second.key}" type="${second.type}" value="${formatValue(data[second.key])}">
                        </div>
                    </div>
                ` : ''}
            </div>
        `);
    }

    profileElement.innerHTML = `
        <div class="section first-sec">
            <div class="profile-photo">
                <img id="profile-picture" src="${pictureUrl}" alt="Profile Picture">
                <!-- <button class="photo-btn" type="button" data-key="picture">✏️</button> -->
                <!-- <input id="picture-input" name="picture" type="file" accept="image/*" class="hidden"> -->
            </div>
            <div class="profile-details">
                <h2>${formatValue(data.firstname) || 'First Name'}</h2>
                <h2>${formatValue(data.lastname) || 'Last Name'}</h2>
            </div>
        </div>

        <form id="profile-form">
            ${fieldsHtml.join('')}

            <div class="save-row">
                <button id="save-profile" class="submit-btn" type="button">Save Changes</button>
                <span id="save-status" class="save-status"></span>
            </div>
        </form>
    `;

    attachProfileListeners();
}

function attachProfileListeners() {
    // const pictureInput = document.getElementById('picture-input');
    // if (pictureInput) {
    //     pictureInput.addEventListener('change', event => {
    //         const file = event.target.files[0];
    //         if (!file) {
    //             return;
    //         }
    //         const picture = document.getElementById('profile-picture');
    //         picture.src = URL.createObjectURL(file);
    //     });
    // }

    const saveButton = document.getElementById('save-profile');
    if (saveButton) {
        saveButton.addEventListener('click', saveProfile);
    }
}

profileElement.addEventListener('click', event => {
    const button = event.target.closest('.edit-btn, .photo-btn');
    if (!button) {
        return;
    }

    const key = button.dataset.key;
    // if (key === 'picture') {
    //     const pictureInput = document.getElementById('picture-input');
    //     if (pictureInput) {
    //         pictureInput.click();
    //     }
    //     return;
    // }

    const input = profileElement.querySelector(`.field-input[name="${key}"]`);
    const text = profileElement.querySelector(`.field-text[data-key="${key}"]`);

    if (input && text) {
        input.classList.remove('hidden');
        text.classList.add('hidden');
        input.focus();
    }
});

async function saveProfile() {
    const statusElement = document.getElementById('save-status');
    statusElement.textContent = 'Saving...';

    const formData = new FormData();
    formData.append('id', id);

    editableFields.forEach(field => {
        const input = profileElement.querySelector(`.field-input[name="${field.key}"]`);
        if (input) {
            formData.append(field.key, input.value.trim());
        }
    });

    // const pictureInput = document.getElementById('picture-input');
    // if (pictureInput && pictureInput.files[0]) {
    //     formData.append('picture', pictureInput.files[0]);
    // }

    try {
        const response = await fetch(modifyApiUrl, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            statusElement.textContent = 'Unable to save profile.';
            return;
        }

        statusElement.textContent = 'Profile saved successfully.';
        setTimeout(() => {
            loadProfile();
        }, 800);
    } catch (error) {
        console.error(error);
        statusElement.textContent = 'Save failed. Check the console.';
    }
}

async function loadProfile() {
    try {
        const response = await fetch(profileApiUrl, { credentials: 'include' });
        const data = await response.json();
        renderProfile(data);
    } catch (error) {
        console.error(error);
        profileElement.innerHTML = '<p>Unable to load profile.</p>';
    }
}

loadProfile();