/**
 * Projekty – lista, tworzenie i edycja przez Fetch API
 */

const PROJECT_STATUS_LABELS = {
    active: 'Aktywny',
    on_hold: 'Wstrzymany',
    completed: 'Zakończony',
};

/** @type {Array<Record<string, unknown>>} */
let projectsCache = [];

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

    list.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.btn-edit-project');
        if (editBtn) {
            const projectId = Number(editBtn.dataset.projectId);
            const project = projectsCache.find((p) => Number(p.id) === projectId);
            if (project && project.can_edit === true) {
                openProjectForm(formWrap, form, formError, project);
            }
            return;
        }

        const deleteBtn = e.target.closest('.btn-delete-project');
        if (deleteBtn) {
            const projectId = Number(deleteBtn.dataset.projectId);
            const project = projectsCache.find((p) => Number(p.id) === projectId);
            if (!project || project.can_delete !== true) {
                return;
            }
            if (!confirm(`Usunąć projekt „${project.name}”?`)) {
                return;
            }
            try {
                await TaskFlow.fetchJson(`/api/projects/${projectId}`, { method: 'DELETE' });
                await loadProjects(list);
            } catch (err) {
                TaskFlow.showMessage(list, err.message || 'Nie udało się usunąć projektu.', 'error');
            }
        }
    });

    loadProjects(list);

    if (btnNew && formWrap) {
        btnNew.addEventListener('click', () => openProjectForm(formWrap, form, formError, null));
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

            const editId = document.getElementById('project-id')?.value.trim() ?? '';
            const payload = {
                name: document.getElementById('project-name')?.value.trim() ?? '',
                description: document.getElementById('project-description')?.value.trim() || null,
                status: document.getElementById('project-status')?.value ?? 'active',
            };

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            try {
                if (editId) {
                    await TaskFlow.fetchJson(`/api/projects/${editId}`, {
                        method: 'PUT',
                        body: JSON.stringify(payload),
                    });
                } else {
                    await TaskFlow.fetchJson('/api/projects', {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                }
                formWrap.hidden = true;
                await loadProjects(list);
            } catch (err) {
                TaskFlow.showMessage(list, err.message || 'Nie udało się zapisać projektu.', 'error');
            } finally {
                submitBtn.disabled = false;
            }
        });
    }
});

function openProjectForm(formWrap, form, formError, project) {
    if (!formWrap || !form) {
        return;
    }

    formWrap.hidden = false;
    hideFormError(formError);

    const idInput = document.getElementById('project-id');
    const nameInput = document.getElementById('project-name');
    const descInput = document.getElementById('project-description');
    const statusInput = document.getElementById('project-status');
    const titleEl = document.getElementById('project-form-title');

    if (project) {
        if (idInput) idInput.value = String(project.id);
        if (nameInput) nameInput.value = project.name ?? '';
        if (descInput) descInput.value = project.description ?? '';
        if (statusInput) statusInput.value = project.status ?? 'active';
        if (titleEl) titleEl.textContent = 'Edycja projektu';
    } else {
        form.reset();
        if (idInput) idInput.value = '';
        if (statusInput) statusInput.value = 'active';
        if (titleEl) titleEl.textContent = 'Nowy projekt';
    }

    nameInput?.focus();
}

async function loadProjects(container) {
    container.innerHTML = '<p class="text-muted">Ładowanie projektów…</p>';

    try {
        const data = await TaskFlow.fetchJson('/api/projects');
        projectsCache = data.projects || [];
        renderProjects(container, projectsCache);
    } catch (err) {
        container.innerHTML = '';
        projectsCache = [];
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

        const actions = [];
        if (project.can_edit === true) {
            actions.push(
                `<button type="button" class="btn btn-sm btn-edit-project" data-project-id="${project.id}">Edytuj</button>`,
            );
        }
        if (project.can_delete === true) {
            actions.push(
                `<button type="button" class="btn btn-sm btn-delete-project" data-project-id="${project.id}">Usuń</button>`,
            );
        }

        const actionsHtml = actions.length
            ? `<div class="project-item-actions">${actions.join(' ')}</div>`
            : '';

        li.innerHTML = `
            <div class="project-item-header">
                <strong>${escapeHtml(project.name)}</strong>
                <span class="project-badge">${escapeHtml(statusLabel)}</span>
            </div>
            ${description}
            ${actionsHtml}
        `;

        ul.appendChild(li);
    });

    container.innerHTML = '';
    container.appendChild(ul);
}

function escapeHtml(text) {
    const el = document.createElement('div');
    el.textContent = String(text ?? '');
    return el.innerHTML;
}

function hideFormError(el) {
    if (!el) return;
    el.textContent = '';
    el.hidden = true;
}
