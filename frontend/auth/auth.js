/* ============================================
   AUTH.JS — Theatro INSAT
   Handles: CSRF token, form submissions,
   error/success toasts, session-check navbar,
   client-side rate limiting
   ============================================ */

'use strict';

// ============================================
// TOAST NOTIFICATIONS
// ============================================

function showToast(message, type = 'error') {
    // Remove any existing toast first
    const existing = document.getElementById('auth-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'auth-toast';
    toast.className = 'error-toast' + (type === 'success' ? ' success-toast' : '');

    const text = document.createElement('span');
    text.className = 'error-toast-text';
    text.textContent = message; // textContent — safe against XSS

    const closeBtn = document.createElement('button');
    closeBtn.className = 'error-toast-close';
    closeBtn.setAttribute('aria-label', 'Dismiss');
    closeBtn.textContent = '×';
    closeBtn.addEventListener('click', () => toast.remove());

    toast.appendChild(text);
    toast.appendChild(closeBtn);

    // Insert before the form
    const form = document.querySelector('.auth-form');
    if (form) form.insertAdjacentElement('beforebegin', toast);
    else document.querySelector('.auth-container').prepend(toast);
}

function hideToast() {
    const t = document.getElementById('auth-toast');
    if (t) t.remove();
}

// ============================================
// LOADING STATE
// ============================================

function setLoading(btn, loading) {
    if (loading) {
        btn.disabled = true;
        btn.dataset.originalText = btn.textContent;
        btn.innerHTML = '<span class="auth-btn-spinner"></span>Please wait...';
    } else {
        btn.disabled = false;
        btn.textContent = btn.dataset.originalText || btn.textContent;
    }
}

// ============================================
// CLIENT-SIDE RATE LIMITER
// Extra UX layer on top of the server rate limit.
// After 5 failed attempts in one window, the
// submit button locks for 5 minutes.
// ============================================

const RATE_KEY    = 'theatro_auth_attempts';
const RATE_WINDOW = 5 * 60 * 1000; // 5 minutes
const RATE_MAX    = 5;

function recordAttempt() {
    const raw  = localStorage.getItem(RATE_KEY);
    const data = raw ? JSON.parse(raw) : { count: 0, since: Date.now() };
    if (Date.now() - data.since > RATE_WINDOW) {
        data.count = 0;
        data.since = Date.now();
    }
    data.count++;
    localStorage.setItem(RATE_KEY, JSON.stringify(data));
    return data.count;
}

function isRateLimited() {
    const raw = localStorage.getItem(RATE_KEY);
    if (!raw) return false;
    const data = JSON.parse(raw);
    if (Date.now() - data.since > RATE_WINDOW) return false;
    return data.count >= RATE_MAX;
}

function resetAttempts() {
    localStorage.removeItem(RATE_KEY);
}

// ============================================
// CSRF TOKEN
// Fetched once per page load, cached in memory.
// ============================================

let _csrfToken = null;

async function getCsrfToken() {
    if (_csrfToken) return _csrfToken;
    try {
        const res  = await fetch('/backend/routes/api.php?action=csrf', { credentials: 'include' });
        const data = await res.json();
        _csrfToken = data.token;
        return _csrfToken;
    } catch {
        return '';
    }
}

// ============================================
// LOGIN FORM
// ============================================

const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideToast();

        if (isRateLimited()) {
            showToast('Too many attempts. Please wait a few minutes before trying again.');
            return;
        }

        const btn      = loginForm.querySelector('.auth-btn');
        const email    = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;

        if (!email || !password) {
            showToast('Please fill in all fields.');
            return;
        }

        setLoading(btn, true);

        try {
            const csrf = await getCsrfToken();
            const res  = await fetch('/backend/routes/api.php?action=login', {
                method:      'POST',
                credentials: 'include',
                headers:     { 'Content-Type': 'application/json' },
                body:        JSON.stringify({ email, password, csrf_token: csrf })
            });

            const data = await res.json();

            if (data.success) {
                resetAttempts();
                window.location.href = '/frontend/home/index.html';
            } else {
                recordAttempt();
                showToast(data.error || 'Invalid email or password.');
                document.getElementById('login-password').value = '';
            }

        } catch {
            showToast('Connection error. Please check your internet and try again.');
        } finally {
            setLoading(btn, false);
        }
    });
}

// ============================================
// SIGNUP FORM
// Uses FormData (multipart) because of picture upload.
// ============================================

const signupForm = document.getElementById('signupForm');
if (signupForm) {

    // ---- Show/hide "Other" custom text input ----
    const fieldSelect      = document.getElementById('su-field');
    const fieldOtherGroup  = document.getElementById('su-field-other-group');
    const fieldCustomInput = document.getElementById('su-field-custom');

    if (fieldSelect) {
        fieldSelect.addEventListener('change', () => {
            const isOther = fieldSelect.value === 'Other';
            fieldOtherGroup.style.display = isOther ? 'block' : 'none';
            if (isOther) {
                fieldCustomInput.focus();
                fieldCustomInput.required = true;
            } else {
                fieldCustomInput.required = false;
                fieldCustomInput.value = '';
            }
        });
    }

    signupForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideToast();

        const btn             = signupForm.querySelector('.auth-btn');
        const firstname       = document.getElementById('su-firstname').value.trim();
        const lastname        = document.getElementById('su-lastname').value.trim();
        const email           = document.getElementById('su-email').value.trim();
        const phone           = document.getElementById('su-phone').value.trim();
        const yearofstudy     = document.getElementById('su-year').value.trim();
        const department      = document.getElementById('su-department').value;
        const password        = document.getElementById('su-password').value;
        const passwordConfirm = document.getElementById('su-password-confirm').value;
        const birthdate       = document.getElementById('su-birthdate').value;
        const pictureInput    = document.getElementById('su-picture');
        const whyjoin         = document.getElementById('su-whyjoin').value.trim();

        // Resolve field of study: if "Other" was chosen, use the custom text input
        const fieldSelectVal = document.getElementById('su-field').value;
        const fieldofstudy   = fieldSelectVal === 'Other'
            ? (document.getElementById('su-field-custom').value.trim())
            : fieldSelectVal;

        // Client-side validation
        if (!firstname || !lastname || !email || !password || !department || !whyjoin) {
            showToast('Please fill in all required fields.');
            return;
        }
        if (fieldSelectVal === 'Other' && !fieldofstudy) {
            showToast('Please specify your field of study.');
            return;
        }
        if (password.length < 8) {
            showToast('Password must be at least 8 characters long.');
            return;
        }
        if (password !== passwordConfirm) {
            showToast('Passwords do not match. Please check and try again.');
            return;
        }
        if (whyjoin.length < 10) {
            showToast('Please tell us why you want to join.');
            return;
        }

        setLoading(btn, true);

        try {
            const csrf = await getCsrfToken();

            // Build FormData — required for multipart/form-data file upload
            const formData = new FormData();
            formData.append('csrf_token',       csrf);
            formData.append('firstname',        firstname);
            formData.append('lastname',         lastname);
            formData.append('email',            email);
            formData.append('phone',            phone);
            formData.append('yearofstudy',      yearofstudy ? parseInt(yearofstudy, 10) : '');
            formData.append('fieldofstudy',     fieldofstudy);
            formData.append('department',       department);
            formData.append('password',         password);
            formData.append('password_confirm', passwordConfirm);
            formData.append('birthdate',        birthdate);
            formData.append('whyjoin',          whyjoin);

            // Append picture only if one was selected
            if (pictureInput && pictureInput.files[0]) {
                formData.append('picture', pictureInput.files[0]);
            }

            // Do NOT set Content-Type header — browser sets it with correct multipart boundary
            const res = await fetch('/backend/routes/api.php?action=signup', {
                method:      'POST',
                credentials: 'include',
                body:        formData
            });

            const data = await res.json();

            if (data.success) {
                showToast(
                    data.message || 'Application submitted! Your registration is pending admin approval.',
                    'success'
                );
                signupForm.reset();
                // Reset the "Other" field group visibility
                if (fieldOtherGroup) fieldOtherGroup.style.display = 'none';
            } else {
                showToast(data.error || 'Registration failed. Please try again.');
            }

        } catch {
            showToast('Connection error. Please check your internet and try again.');
        } finally {
            setLoading(btn, false);
        }
    });
}

// ============================================
// FORGOT PASSWORD FORM
// ============================================

const forgotForm = document.getElementById('forgotForm');
if (forgotForm) {
    forgotForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideToast();

        const btn   = forgotForm.querySelector('.auth-btn');
        const email = document.getElementById('forgot-email').value.trim();

        if (!email) {
            showToast('Please enter your email address.');
            return;
        }

        setLoading(btn, true);

        try {
            const csrf = await getCsrfToken();
            const res  = await fetch('/backend/routes/api.php?action=forgot-password', {
                method:      'POST',
                credentials: 'include',
                headers:     { 'Content-Type': 'application/json' },
                body:        JSON.stringify({ email, csrf_token: csrf })
            });

            const data = await res.json();

            if (data.success) {
                showToast('Code sent! Check your inbox. Redirecting…', 'success');
                sessionStorage.setItem('theatro_reset_email', email);
                forgotForm.reset();
                setTimeout(() => { window.location.href = 'reset-password.html'; }, 2000);
            } else {
                showToast(data.error || 'Something went wrong. Please try again.');
            }

        } catch {
            showToast('Connection error. Please check your internet and try again.');
        } finally {
            setLoading(btn, false);
        }
    });
}

// ============================================
// RESET PASSWORD FORM (6-digit code)
// ============================================

const resetForm = document.getElementById('resetForm');
if (resetForm) {
    // Pre-fill email if we came from forgot-password page
    const savedEmail = sessionStorage.getItem('theatro_reset_email');
    if (savedEmail) {
        const emailInput = document.getElementById('reset-email');
        if (emailInput) emailInput.value = savedEmail;
    }

    // ---- "Send again" — resends code without leaving the page ----
    const resendBtn = document.getElementById('resend-code-btn');
    if (resendBtn) {
        resendBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const email = document.getElementById('reset-email').value.trim()
                       || sessionStorage.getItem('theatro_reset_email');

            if (!email) {
                showToast('Please enter your email address first.');
                document.getElementById('reset-email').focus();
                return;
            }

            resendBtn.textContent = 'Sending…';
            resendBtn.style.pointerEvents = 'none';

            try {
                const csrf = await getCsrfToken();
                await fetch('/backend/routes/api.php?action=forgot-password', {
                    method:      'POST',
                    credentials: 'include',
                    headers:     { 'Content-Type': 'application/json' },
                    body:        JSON.stringify({ email, csrf_token: csrf })
                });
                showToast('A new code has been sent to your inbox.', 'success');
                sessionStorage.setItem('theatro_reset_email', email);
            } catch {
                showToast('Connection error. Please try again.');
            } finally {
                resendBtn.textContent = 'Send again';
                resendBtn.style.pointerEvents = '';
            }
        });
    }

    // Auto-format code input as "123 456" while typing
    const codeInput = document.getElementById('reset-code');
    if (codeInput) {
        codeInput.addEventListener('input', () => {
            let val = codeInput.value.replace(/\D/g, '').slice(0, 6);
            if (val.length > 3) val = val.slice(0, 3) + ' ' + val.slice(3);
            codeInput.value = val;
        });
    }

    resetForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideToast();

        const btn         = resetForm.querySelector('.auth-btn');
        const email       = document.getElementById('reset-email').value.trim();
        const code        = document.getElementById('reset-code').value.replace(/\s/g, '');
        const password    = document.getElementById('reset-password').value;
        const passConfirm = document.getElementById('reset-password-confirm').value;

        if (!email || !code || !password || !passConfirm) {
            showToast('Please fill in all fields.');
            return;
        }
        if (!/^\d{6}$/.test(code)) {
            showToast('Please enter the 6-digit code from your email.');
            return;
        }
        if (password.length < 8) {
            showToast('Password must be at least 8 characters.');
            return;
        }
        if (password !== passConfirm) {
            showToast('Passwords do not match.');
            return;
        }

        setLoading(btn, true);

        try {
            const csrf = await getCsrfToken();
            const res  = await fetch('/backend/routes/api.php?action=reset-password', {
                method:      'POST',
                credentials: 'include',
                headers:     { 'Content-Type': 'application/json' },
                body:        JSON.stringify({ email, code, password, password_confirm: passConfirm, csrf_token: csrf })
            });

            const data = await res.json();

            if (data.success) {
                showToast('Password updated! Redirecting to login…', 'success');
                setTimeout(() => { window.location.href = 'login.html'; }, 2000);
            } else {
                showToast(data.error || 'Invalid or expired code.');
            }
        } catch {
            showToast('Connection error. Please try again.');
        } finally {
            setLoading(btn, false);
        }
    });
}

// ============================================
// SESSION CHECK — runs on every auth page
// 1. Shows/hides the Workshops nav link based on login state.
// 2. Redirects already-logged-in users away from login/signup.
// ============================================

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res  = await fetch('/backend/routes/api.php?action=check-session', { credentials: 'include' });
        const data = await res.json();

        // Show Workshops link only for logged-in users
        const workshopsLi = document.getElementById('nav-workshops');
        if (workshopsLi) {
            workshopsLi.style.display = data.logged_in ? '' : 'none';
        }
        document.querySelectorAll('.nav-join-link, .auth-join-link').forEach(el => {
            el.style.display = data.logged_in ? 'none' : '';
        });

        // If already logged in and visiting login or signup → go home
        if (data.logged_in) {
            const path = window.location.pathname;
            if (path.includes('login.html') || path.includes('signup.html')) {
                window.location.href = '/frontend/home/index.html';
            }
        }
    } catch {
        // Session check is best-effort — silently ignore network errors
    }
});
