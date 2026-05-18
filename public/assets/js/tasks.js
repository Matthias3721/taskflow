/**
 * Zadania – lista, tworzenie i edycja przez Fetch API
 */

const TASK_STATUS_LABELS = {
    todo: 'Do zrobienia',
    in_progress: 'W trakcie',
    done: 'Zakończone',
};

const TASK_PRIORITY_LABELS = {
    low: 'Niski',
    medium: 'Średni',
    high: 'Wysoki',
};

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('tasks-list');
    const formWrap = document.getElementById('task-form-wrap');
    const form = document.getElementById('task-form');
    const btnNew = document.getElementById('btn-new-task');
    const btnCancel = document.getElementById('btn-cancel-task');
    const formError = document.getElementById('task-form-error');
    const projectSelect = document.getElementById('task-project');
    const assigneeSelect = document.getElementById('task-assignee');
    const categorySelect = document.getElementById('task-category');

    if (!list) {
        return;
    }

    loadTasks(list);

    if (btnNew && formWrap) {
        btnNew.addEventListener('click', async () => {
            await openTaskForm(formWrap, form, formError, null, projectSelect, assigneeSelect, categorySelect);
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

            const id = document.getElementById('task-id')?.value;
            const limited = form.dataset.limitedEdit === 'true';
            const payload = limited
                ? {
                    status: form.status.value,
                    description: form.description.value.trim() || null,
                }
                : {
                    title: form.title.value.trim(),
                    description: form.description.value.trim() || null,
                    project_id: parseInt(form.project_id.value, 10),
                    assignee_id: form.assignee_id.value === ''
                        ? null
                        : parseInt(form.assignee_id.value, 10),
                    category_id: form.category_id.value === ''
                        ? null
                        : parseInt(form.category_id.value, 10),
                    status: form.status.value,
                    priority: form.priority.value,
                    due_date: form.due_date.value || null,
                };

            const submitBtn = document.getElementById('task-submit-btn');
            submitBtn.disabled = true;

            try {
                if (id) {
                    await TaskFlow.fetchJson(`/api/tasks/${id}`, {
                        method: 'PUT',
                        body: JSON.stringify(payload),
                    });
                } else {
                    await TaskFlow.fetchJson('/api/tasks', {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                }
                formWrap.hidden = true;
                await loadTasks(list);
            } catch (err) {
                TaskFlow.showMessage(list, err.message || 'Nie udało się zapisać zadania.', 'error');
            } finally {
                submitBtn.disabled = false;
            }
        });
    }
});

async function openTaskForm(formWrap, form, formError, task, projectSelect, assigneeSelect, categorySelect) {
    formWrap.hidden = false;
    hideFormError(formError);
    form.reset();

    const idInput = document.getElementById('task-id');
    const titleEl = document.getElementById('task-form-title');
    const limited = task?.can_edit_limited === true;

    form.dataset.limitedEdit = limited ? 'true' : 'false';
    setLimitedFormMode(limited);

    if (!limited) {
        await loadFormOptions(projectSelect, assigneeSelect, categorySelect);
    }

    if (task) {
        if (idInput) idInput.value = String(task.id);
        if (titleEl) titleEl.textContent = limited ? 'Edycja statusu i opisu' : 'Edycja zadania';

        form.description.value = task.description || '';
        form.status.value = task.status;

        if (!limited) {
            form.title.value = task.title;
            if (projectSelect) projectSelect.value = String(task.project_id);
            if (assigneeSelect) {
                assigneeSelect.value = task.assignee_id ? String(task.assignee_id) : '';
            }
            if (categorySelect) {
                categorySelect.value = task.category_id ? String(task.category_id) : '';
            }
            form.priority.value = task.priority || 'medium';
            form.due_date.value = task.due_date || '';
        }
    } else {
        if (idInput) idInput.value = '';
        form.priority.value = 'medium';
        if (titleEl) titleEl.textContent = 'Nowe zadanie';
    }

    (limited ? document.getElementById('task-description') : document.getElementById('task-title'))?.focus();
}

function setLimitedFormMode(limited) {
    document.querySelectorAll('.task-field-full').forEach((el) => {
        el.hidden = limited;
    });

    const titleInput = document.getElementById('task-title');
    const projectSelect = document.getElementById('task-project');
    if (titleInput) titleInput.required = !limited;
    if (projectSelect) projectSelect.required = !limited;
}

async function loadTasks(container) {
    container.innerHTML = '<p class="text-muted">Ładowanie zadań…</p>';

    try {
        const data = await TaskFlow.fetchJson('/api/tasks');
        renderTasks(container, data.tasks || []);
    } catch (err) {
        container.innerHTML = '';
        TaskFlow.showMessage(container, err.message || 'Nie udało się pobrać zadań.', 'error');
    }
}

async function loadFormOptions(projectSelect, assigneeSelect, categorySelect) {
    const requests = [
        TaskFlow.fetchJson('/api/projects'),
        TaskFlow.fetchJson('/api/users/options'),
        TaskFlow.fetchJson('/api/categories'),
    ];

    const [projectsData, usersData, categoriesData] = await Promise.all(requests);

    if (projectSelect) {
        fillSelect(projectSelect, projectsData.projects || [], 'id', 'name', '— wybierz projekt —');
    }
    if (assigneeSelect) {
        fillSelect(assigneeSelect, usersData.users || [], 'id', 'name', '— brak —', true);
    }
    if (categorySelect) {
        fillSelect(categorySelect, categoriesData.categories || [], 'id', 'name', '— brak —', true);
    }
}

function fillSelect(select, items, valueKey, labelKey, placeholder, allowEmpty = false) {
    select.innerHTML = '';
    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = placeholder;
    select.appendChild(placeholderOption);

    items.forEach((item) => {
        const option = document.createElement('option');
        option.value = String(item[valueKey]);
        option.textContent = item[labelKey];
        select.appendChild(option);
    });

    if (!allowEmpty && items.length > 0) {
        select.value = String(items[0][valueKey]);
    }
}

function renderTasks(container, tasks) {
    if (!tasks.length) {
        container.innerHTML = '<p class="text-muted">Brak zadań. Utwórz pierwsze zadanie.</p>';
        return;
    }

    const ul = document.createElement('ul');
    ul.className = 'project-items';

    tasks.forEach((task) => {
        const li = document.createElement('li');
        li.className = 'project-item';

        const statusLabel = TASK_STATUS_LABELS[task.status] || task.status;
        const priorityLabel = TASK_PRIORITY_LABELS[task.priority] || task.priority;
        const description = task.description
            ? `<p class="project-desc">${escapeHtml(task.description)}</p>`
            : '';
        const dueDate = task.due_date
            ? `<p class="project-desc">Termin: ${escapeHtml(task.due_date)}</p>`
            : '';
        const categoryLine = task.category_name
            ? `<p class="project-desc">Kategoria: <span class="category-swatch" style="background:${escapeHtml(task.category_color || '#3b82f6')}"></span> ${escapeHtml(task.category_name)}</p>`
            : '';

        const actions = [];
        if (task.can_edit) {
            actions.push(
                `<button type="button" class="btn btn-sm btn-edit-task" data-id="${task.id}">Edytuj</button>`,
            );
        }
        if (task.can_delete) {
            actions.push(
                `<button type="button" class="btn btn-sm btn-delete-task" data-id="${task.id}">Usuń</button>`,
            );
        }

        const actionsHtml = actions.length
            ? `<div class="project-item-actions">${actions.join(' ')}</div>`
            : '';

        li.innerHTML = `
            <div class="project-item-header">
                <strong>${escapeHtml(task.title)}</strong>
                <span class="project-badge">${escapeHtml(statusLabel)}</span>
            </div>
            <p class="project-desc">Projekt #${task.project_id} · ${escapeHtml(priorityLabel)}</p>
            ${categoryLine}
            ${description}
            ${dueDate}
            ${actionsHtml}
        `;

        if (task.can_edit) {
            li.querySelector('.btn-edit-task')?.addEventListener('click', async () => {
                const formWrap = document.getElementById('task-form-wrap');
                const form = document.getElementById('task-form');
                const formError = document.getElementById('task-form-error');
                const projectSelect = document.getElementById('task-project');
                const assigneeSelect = document.getElementById('task-assignee');
                const categorySelect = document.getElementById('task-category');
                await openTaskForm(
                    formWrap,
                    form,
                    formError,
                    task,
                    projectSelect,
                    assigneeSelect,
                    categorySelect,
                );
            });
        }

        if (task.can_delete) {
            li.querySelector('.btn-delete-task')?.addEventListener('click', async () => {
                if (!confirm(`Usunąć zadanie „${task.title}”?`)) {
                    return;
                }
                try {
                    await TaskFlow.fetchJson(`/api/tasks/${task.id}`, { method: 'DELETE' });
                    await loadTasks(container);
                } catch (err) {
                    TaskFlow.showMessage(container, err.message || 'Nie udało się usunąć zadania.', 'error');
                }
            });
        }

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
