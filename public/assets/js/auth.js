/**
 * Formularze logowania i rejestracji – placeholder pod Fetch API
 */

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            console.info('Logowanie – endpoint API zostanie podłączony w kolejnym etapie');
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            console.info('Rejestracja – endpoint API zostanie podłączony w kolejnym etapie');
        });
    }
});
