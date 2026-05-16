/**
 * Projekty – lista i tworzenie przez Fetch API
 */

const PROJECT_STATUS_LABELS = {
    active: 'Aktywny',
    on_hold: 'Wstrzymany',
    completed: 'Zakończony',
};

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('projects-list');
    const formWrap = document.getElementById('project-form-wrap');
    const form = document.getElementById('project-form');
    const btnNew = document.getElementById('btn-new-project');
    const btnCancel = document.getElementById('btn-cancel-project');
    const formError = document.getElementById('project-form-error');

    if (!list) {
        return;
    }

    loadProjects(list);

    if (btnNew && formWrap) {
        btnNew.addEventListener('click', () => {
            formWrap.hidden = false;
            form?.reset();
            hideFormError(formError);
            document.getElementById('project-name')?.focus();
        });
    }

    if (btnCancel && formWrap) {
        btnCancel.addEventListener('click', () => {
            formWrap.hidden = true;
            hideFormError(formError);
        });
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideFormError(formError);

            const payload = {
                name: form.name.value.trim(),
                description: form.description.value.trim() || null,
                status: form.status.value,
            };

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            try {
                await TaskFlow.fetchJson('/api/projects', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                formWrap.hidden = true;
                form.reset();
                await loadProjects(list);
            } catch (err) {
                showFormError(formError, err.message || 'Nie udało się utworzyć projektu.');
            } finally {
                submitBtn.disabled = false;
            }
        });
    }
});

async function loadProjects(container) {
    container.innerHTML = '<p class="text-muted">Ładowanie projektów…</p>';

    try {
        const data = await TaskFlow.fetchJson('/api/projects');
        renderProjects(container, data.projects || []);
    } catch (err) {
        container.innerHTML = '';
        TaskFlow.showMessage(container, err.message || 'Nie udało się pobrać projektów.', 'error');
    }
}

function renderProjects(container, projects) {
    if (!projects.length) {
        container.innerHTML = '<p class="text-muted">Brak projektów. Utwórz pierwszy projekt.</p>';
        return;
    }

    const ul = document.createElement('ul');
    ul.className = 'project-items';

    projects.forEach((project) => {
        const li = document.createElement('li');
        li.className = 'project-item';

        const statusLabel = PROJECT_STATUS_LABELS[project.status] || project.status;
        const description = project.description
            ? `<p class="project-desc">${escapeHtml(project.description)}</p>`
            : '';

        li.innerHTML = `
            <div class="project-item-header">
                <strong>${escapeHtml(project.name)}</strong>
                <span class="project-badge">${escapeHtml(statusLabel)}</span>
            </div>
            ${description}
        `;
        ul.appendChild(li);
    });

    container.innerHTML = '';
    container.appendChild(ul);
}

function escapeHtml(text) {
    const el = document.createElement('div');
    el.textContent = text;
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
