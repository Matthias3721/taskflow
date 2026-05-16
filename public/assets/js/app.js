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
            const message = contentType.includes('json')
                ? (await response.json()).message
                : response.statusText;
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

document.addEventListener('DOMContentLoaded', () => {
    console.info('TaskFlow – aplikacja załadowana');
});
