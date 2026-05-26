/* ============================================
   CONTACT/SCRIPT.JS — Theatro INSAT
   Handles contact form submission with real backend
   ============================================ */

'use strict';

/* Fetch and cache CSRF token for secure form submission */
let _csrfToken = null;

async function getCsrfToken() {
    if (_csrfToken) return _csrfToken;
    try {
        const res  = await fetch('/routes/api.php?action=csrf', { credentials: 'include' });
        const data = await res.json();
        _csrfToken = data.token;
        return _csrfToken;
    } catch {
        return '';
    }
}

/* Show a user-facing message inside the form area */
function showContactMessage(message, type = 'error') {
    const existing = document.getElementById('contact-feedback');
    if (existing) existing.remove();

    const el = document.createElement('div');
    el.id = 'contact-feedback';
    el.className = type === 'success' ? 'contact-msg contact-msg--success' : 'contact-msg contact-msg--error';
    el.textContent = message;

    const form = document.getElementById('contactForm');
    if (form) form.insertAdjacentElement('beforebegin', el);
}

/* Contact form submission — posts JSON to backend */
const contactForm = document.getElementById('contactForm');
if (contactForm) {
    contactForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = contactForm.querySelector('button[type="submit"]');
        const fullname  = document.getElementById('fullname').value.trim();
        const email     = document.getElementById('email').value.trim();
        const message   = document.getElementById('message').value.trim();

        // Basic client-side check before hitting the network
        if (!fullname || !email || !message) {
            showContactMessage('Please fill in all fields.');
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending…';
        }

        try {
            const csrf = await getCsrfToken();
            const res  = await fetch('/routes/api.php?action=send-message', {
                method:      'POST',
                credentials: 'include',
                headers:     { 'Content-Type': 'application/json' },
                body:        JSON.stringify({ fullname, email, message, csrf_token: csrf })
            });

            const data = await res.json();

            if (data.success) {
                showContactMessage(
                    data.message || 'Your message has been sent! We will get back to you soon.',
                    'success'
                );
                contactForm.reset();
            } else {
                showContactMessage(data.error || 'Failed to send your message. Please try again.');
            }

        } catch {
            showContactMessage('Connection error. Please check your internet and try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Message';
            }
        }
    });
}

/* Initialize */
document.addEventListener('DOMContentLoaded', function () {
    // Nothing extra needed — star container is already CSS-driven
});
