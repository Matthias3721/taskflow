/**
 * Kategorie zadań – CRUD przez Fetch API
 */

const isAdmin = window.TaskFlowCategories?.isAdmin === true;

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('categories-list');
    const formWrap = document.getElementById('category-form-wrap');
    const form = document.getElementById('category-form');
    const btnNew = document.getElementById('btn-new-category');
    const btnCancel = document.getElementById('btn-cancel-category');
    const formError = document.getElementById('category-form-error');

    if (!list) {
        return;
    }

    loadCategories(list);

    if (isAdmin && btnNew && formWrap) {
        btnNew.addEventListener('click', () => openForm(formWrap, form, formError, null));
    }

    if (btnCancel && formWrap) {
        btnCancel.addEventListener('click', () => {
            formWrap.hidden = true;
            hideFormError(formError);
        });
    }

    if (isAdmin && form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideFormError(formError);

            const id = document.getElementById('category-id')?.value;
            const payload = {
                name: form.name.value.trim(),
                color: form.color.value,
            };

            const submitBtn = document.getElementById('category-submit-btn');
            submitBtn.disabled = true;

            try {
                if (id) {
                    await TaskFlow.fetchJson(`/api/categories/${id}`, {
                        method: 'PUT',
                        body: JSON.stringify(payload),
                    });
                } else {
                    await TaskFlow.fetchJson('/api/categories', {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                }
                formWrap.hidden = true;
                await loadCategories(list);
            } catch (err) {
                showFormError(formError, err.message || 'Nie udało się zapisać kategorii.');
            } finally {
                submitBtn.disabled = false;
            }
        });
    }
});

function openForm(formWrap, form, formError, category) {
    formWrap.hidden = false;
    hideFormError(formError);
    form.reset();

    const idInput = document.getElementById('category-id');
    const titleEl = document.getElementById('category-form-title');

    if (category) {
        if (idInput) idInput.value = String(category.id);
        form.name.value = category.name;
        form.color.value = category.color || '#3b82f6';
        if (titleEl) titleEl.textContent = 'Edycja kategorii';
    } else {
        if (idInput) idInput.value = '';
        form.color.value = '#3b82f6';
        if (titleEl) titleEl.textContent = 'Nowa kategoria';
    }

    document.getElementById('category-name')?.focus();
}

async function loadCategories(container) {
    container.innerHTML = '<p class="text-muted">Ładowanie kategorii…</p>';

    try {
        const data = await TaskFlow.fetchJson('/api/categories');
        renderCategories(container, data.categories || []);
    } catch (err) {
        container.innerHTML = '';
        TaskFlow.showMessage(container, err.message || 'Nie udało się pobrać kategorii.', 'error');
    }
}

function renderCategories(container, categories) {
    if (!categories.length) {
        container.innerHTML = '<p class="text-muted">Brak kategorii.</p>';
        return;
    }

    const table = document.createElement('table');
    table.className = 'users-table';
    table.innerHTML = `
        <thead>
            <tr>
                <th>Nazwa</th>
                <th>Kolor</th>
                ${isAdmin ? '<th>Akcje</th>' : ''}
            </tr>
        </thead>
        <tbody></tbody>
    `;

    const tbody = table.querySelector('tbody');

    categories.forEach((cat) => {
        const tr = document.createElement('tr');
        const actions = isAdmin
            ? `<td>
                <button type="button" class="btn btn-sm btn-edit-category" data-id="${cat.id}">Edytuj</button>
                <button type="button" class="btn btn-sm btn-delete-category" data-id="${cat.id}">Usuń</button>
               </td>`
            : '';

        tr.innerHTML = `
            <td>${escapeHtml(cat.name)}</td>
            <td><span class="category-swatch" style="background:${escapeHtml(cat.color || '#3b82f6')}"></span> ${escapeHtml(cat.color || '')}</td>
            ${actions}
        `;

        if (isAdmin) {
            tr.querySelector('.btn-edit-category')?.addEventListener('click', () => {
                const formWrap = document.getElementById('category-form-wrap');
                const form = document.getElementById('category-form');
                const formError = document.getElementById('category-form-error');
                openForm(formWrap, form, formError, cat);
            });

            tr.querySelector('.btn-delete-category')?.addEventListener('click', async () => {
                if (!confirm(`Usunąć kategorię „${cat.name}”?`)) {
                    return;
                }
                try {
                    await TaskFlow.fetchJson(`/api/categories/${cat.id}`, { method: 'DELETE' });
                    await loadCategories(container);
                } catch (err) {
                    TaskFlow.showMessage(container, err.message || 'Nie udało się usunąć.', 'error');
                }
            });
        }

        tbody.appendChild(tr);
    });

    container.innerHTML = '';
    container.appendChild(table);
}

function escapeHtml(text) {
    const el = document.createElement('div');
    el.textContent = String(text ?? '');
    return el.innerHTML;
}

function showFormError(el, message) {
    if (!el) return;
    el.textContent = message;
    el.hidden = false;
}

function hideFormError(el) {
    if (!el) return;
    el.textContent = '';
    el.hidden = true;
}
