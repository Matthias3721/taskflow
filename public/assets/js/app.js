/**
 * TaskFlow – wspólne funkcje front-end (Fetch API)
 */

const TaskFlow = {
    async fetchJson(url, options = {}) {
        const defaults = {
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
        };

        const response = await fetch(url, { ...defaults, ...options });
        const contentType = response.headers.get('Content-Type') || '';

        if (!response.ok) {
            let message = response.statusText;
            try {
                const errBody = await response.json();
                if (errBody && typeof errBody.message === 'string') {
                    message = errBody.message;
                }
            } catch {
                // odpowiedź nie-JSON – zostaw statusText
            }
            throw new Error(message || `HTTP ${response.status}`);
        }

        if (contentType.includes('json')) {
            return response.json();
        }

        return response.text();
    },

    showMessage(container, text, type = 'info') {
        if (!container) return;
        const el = document.createElement('p');
        el.className = `message message-${type}`;
        el.textContent = text;
        container.prepend(el);
    },
};

function closeMobileSidebar() {
    document.body.classList.remove('sidebar-open');
    const openBtn = document.getElementById('sidebar-open-btn');
    if (openBtn) {
        openBtn.setAttribute('aria-expanded', 'false');
    }
}

function openMobileSidebar() {
    document.body.classList.add('sidebar-open');
    const openBtn = document.getElementById('sidebar-open-btn');
    if (openBtn) {
        openBtn.setAttribute('aria-expanded', 'true');
    }
}

function initMobileSidebar() {
    const openBtn = document.getElementById('sidebar-open-btn');
    const closeBtn = document.getElementById('sidebar-close-btn');
    const backdrop = document.getElementById('mobile-backdrop');
    const sidebar = document.getElementById('app-sidebar');

    if (!openBtn) {
        return;
    }

    closeMobileSidebar();

    openBtn.addEventListener('click', () => openMobileSidebar());
    closeBtn?.addEventListener('click', () => closeMobileSidebar());
    backdrop?.addEventListener('click', () => closeMobileSidebar());

    sidebar?.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) {
            return;
        }
        const href = link.getAttribute('href');
        if (href && href !== '#') {
            closeMobileSidebar();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeMobileSidebar();
        }
    });
}

closeMobileSidebar();

document.addEventListener('DOMContentLoaded', () => {
    closeMobileSidebar();
    initMobileSidebar();

    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            closeMobileSidebar();
            try {
                await TaskFlow.fetchJson('/api/logout', { method: 'POST' });
                window.location.href = '/login';
            } catch (err) {
                console.error('Wylogowanie nie powiodło się:', err.message);
            }
        });
    }
});

window.addEventListener('pageshow', () => closeMobileSidebar());
window.addEventListener('beforeunload', () => closeMobileSidebar());
