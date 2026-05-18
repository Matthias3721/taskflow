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
    const page = document.getElementById('projects-page');
    const list = document.getElementById('projects-list');
    const formWrap = document.getElementById('project-form-wrap');
    const form = document.getElementById('project-form');
    const btnNew = document.getElementById('btn-new-project');
    const btnCancel = document.getElementById('btn-cancel-project');
    const formError = document.getElementById('project-form-error');

    if (!list) {
        return;
    }

    const currentUserId = page ? Number(page.dataset.userId) : 0;
    const userRole = page?.dataset.userRole ?? '';

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
                await loadProjects(list, currentUserId, userRole);
            } catch (err) {
                TaskFlow.showMessage(list, err.message || 'Nie udało się usunąć projektu.', 'error');
            }
        }
    });

    loadProjects(list, currentUserId, userRole);

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
                await loadProjects(list, currentUserId, userRole);
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

async function loadProjects(container, currentUserId, userRole) {
    container.innerHTML = '<p class="loading-placeholder text-muted">Ładowanie projektów…</p>';

    try {
        const data = await TaskFlow.fetchJson('/api/projects');
        projectsCache = data.projects || [];
        renderProjects(container, projectsCache, currentUserId, userRole);
    } catch (err) {
        container.innerHTML = '';
        projectsCache = [];
        TaskFlow.showMessage(container, err.message || 'Nie udało się pobrać projektów.', 'error');
    }
}

function renderOwnerMeta(project, currentUserId, userRole) {
    if (project.owner_name) {
        return `<p class="project-card-owner">Właściciel: ${escapeHtml(project.owner_name)}</p>`;
    }

    const ownerId = Number(project.owner_id);
    if (!ownerId) {
        return '';
    }

    if (currentUserId && ownerId === currentUserId) {
        return '<p class="project-card-owner">Twój projekt</p>';
    }

    if (userRole === 'admin') {
        return `<p class="project-card-owner">Właściciel #${ownerId}</p>`;
    }

    return '';
}

function renderProjects(container, projects, currentUserId, userRole) {
    if (!projects.length) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon empty-state-icon--folder" aria-hidden="true"></div>
                <h3 class="empty-state-title">Brak projektów</h3>
                <p class="empty-state-text">Utwórz pierwszy projekt, aby zacząć organizować zadania.</p>
                <button type="button" class="btn btn-primary btn-empty-cta" data-action="new-project">Nowy projekt</button>
            </div>
        `;

        const cta = container.querySelector('[data-action="new-project"]');
        const btnNew = document.getElementById('btn-new-project');
        if (cta && btnNew) {
            cta.addEventListener('click', () => btnNew.click());
        }
        return;
    }

    const ul = document.createElement('ul');
    ul.className = 'project-cards-list';

    projects.forEach((project) => {
        const li = document.createElement('li');
        li.className = 'project-card';

        const status = String(project.status ?? 'active');
        const statusLabel = PROJECT_STATUS_LABELS[status] || status;
        const statusClass = ['active', 'on_hold', 'completed'].includes(status) ? status : 'active';

        const description = project.description
            ? `<p class="project-card-desc">${escapeHtml(project.description)}</p>`
            : '<p class="project-card-desc project-card-desc--empty">Brak opisu</p>';

        const ownerHtml = renderOwnerMeta(project, currentUserId, userRole);

        const actions = [];
        if (project.can_edit === true) {
            actions.push(
                `<button type="button" class="btn btn-sm btn-edit-project" data-project-id="${project.id}">Edytuj</button>`,
            );
        }
        if (project.can_delete === true) {
            actions.push(
                `<button type="button" class="btn btn-sm btn-danger btn-delete-project" data-project-id="${project.id}">Usuń</button>`,
            );
        }

        const actionsHtml = actions.length
            ? `<footer class="project-card-footer">${actions.join('')}</footer>`
            : '';

        li.innerHTML = `
            <div class="project-card-body">
                <header class="project-card-header">
                    <h3 class="project-card-title">${escapeHtml(project.name)}</h3>
                    <span class="status-badge status-badge--${statusClass}">${escapeHtml(statusLabel)}</span>
                </header>
                ${description}
                ${ownerHtml}
            </div>
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
