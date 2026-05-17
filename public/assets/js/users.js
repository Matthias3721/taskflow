/**
 * Panel administratora – zarządzanie użytkownikami
 */

const ROLE_LABELS = {
    admin: 'Administrator',
    project_manager: 'Kierownik projektu',
    user: 'Użytkownik',
};

let currentUserId = null;

document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('users-list');
    if (!container) {
        return;
    }

    try {
        const me = await TaskFlow.fetchJson('/api/me');
        currentUserId = me.user?.id ?? null;
        await loadUsers(container);
    } catch (err) {
        container.innerHTML = '';
        TaskFlow.showMessage(container, err.message || 'Brak dostępu.', 'error');
    }
});

async function loadUsers(container) {
    container.innerHTML = '<p class="text-muted">Ładowanie użytkowników…</p>';

    try {
        const data = await TaskFlow.fetchJson('/api/users');
        renderUsersTable(container, data.users || []);
    } catch (err) {
        container.innerHTML = '';
        TaskFlow.showMessage(container, err.message || 'Nie udało się pobrać użytkowników.', 'error');
    }
}

function renderUsersTable(container, users) {
    if (!users.length) {
        container.innerHTML = '<p class="text-muted">Brak użytkowników.</p>';
        return;
    }

    const table = document.createElement('table');
    table.className = 'users-table';

    table.innerHTML = `
        <thead>
            <tr>
                <th>ID</th>
                <th>Imię i nazwisko</th>
                <th>E-mail</th>
                <th>Rola</th>
                <th>Status</th>
                <th>Utworzono</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody></tbody>
    `;

    const tbody = table.querySelector('tbody');

    users.forEach((user) => {
        const tr = document.createElement('tr');
        tr.dataset.userId = String(user.id);

        const isSelf = currentUserId !== null && user.id === currentUserId;
        const statusClass = user.is_active ? 'status-active' : 'status-inactive';
        const statusLabel = user.is_active ? 'Aktywny' : 'Nieaktywny';

        tr.innerHTML = `
            <td>${user.id}</td>
            <td>${escapeHtml(user.name)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td>
                <select class="user-role-select" data-user-id="${user.id}" ${isSelf ? 'disabled' : ''}>
                    ${roleOptions(user.role)}
                </select>
            </td>
            <td><span class="user-status ${statusClass}">${statusLabel}</span></td>
            <td>${formatDate(user.created_at)}</td>
            <td>
                <button type="button"
                    class="btn btn-sm user-toggle-status"
                    data-user-id="${user.id}"
                    data-active="${user.is_active ? '1' : '0'}"
                    ${isSelf && user.is_active ? 'disabled title="Nie możesz dezaktywować własnego konta"' : ''}>
                    ${user.is_active ? 'Dezaktywuj' : 'Aktywuj'}
                </button>
            </td>
        `;

        const roleSelect = tr.querySelector('.user-role-select');
        roleSelect?.addEventListener('change', () => updateRole(user.id, roleSelect.value, container));

        const toggleBtn = tr.querySelector('.user-toggle-status');
        toggleBtn?.addEventListener('click', () => {
            const newActive = toggleBtn.dataset.active !== '1';
            updateStatus(user.id, newActive, container);
        });

        tbody.appendChild(tr);
    });

    container.innerHTML = '';
    container.appendChild(table);
}

function roleOptions(selected) {
    return Object.entries(ROLE_LABELS)
        .map(([value, label]) => {
            const sel = value === selected ? ' selected' : '';
            return `<option value="${value}"${sel}>${label}</option>`;
        })
        .join('');
}

async function updateRole(userId, role, container) {
    try {
        await TaskFlow.fetchJson(`/api/users/${userId}/role`, {
            method: 'PUT',
            body: JSON.stringify({ role }),
        });
        await loadUsers(container);
    } catch (err) {
        TaskFlow.showMessage(container, err.message || 'Nie udało się zmienić roli.', 'error');
        await loadUsers(container);
    }
}

async function updateStatus(userId, isActive, container) {
    const payload = { is_active: isActive === true };

    try {
        await TaskFlow.fetchJson(`/api/users/${userId}/status`, {
            method: 'PUT',
            body: JSON.stringify(payload),
        });
        await loadUsers(container);
    } catch (err) {
        TaskFlow.showMessage(container, err.message || 'Nie udało się zmienić statusu.', 'error');
        await loadUsers(container);
    }
}

function formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value).slice(0, 10);
    }
    return date.toLocaleDateString('pl-PL');
}

function escapeHtml(text) {
    const el = document.createElement('div');
    el.textContent = String(text ?? '');
    return el.innerHTML;
}
