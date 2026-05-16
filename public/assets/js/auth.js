/**
 * Logowanie przez Fetch API
 */

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const errorBox = document.getElementById('login-error');

    if (registerForm) {
        registerForm.addEventListener('submit', (e) => e.preventDefault());
    }

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideError(errorBox);

            const email = loginForm.email.value.trim();
            const password = loginForm.password.value;
            const submitBtn = loginForm.querySelector('button[type="submit"]');

            submitBtn.disabled = true;

            try {
                await TaskFlow.fetchJson('/api/login', {
                    method: 'POST',
                    body: JSON.stringify({ email, password }),
                });
                window.location.href = '/';
            } catch (err) {
                showError(errorBox, err.message || 'Logowanie nie powiodło się.');
            } finally {
                submitBtn.disabled = false;
            }
        });
    }
});

function showError(el, message) {
    if (!el) return;
    el.textContent = message;
    el.hidden = false;
}

function hideError(el) {
    if (!el) return;
    el.textContent = '';
    el.hidden = true;
}
