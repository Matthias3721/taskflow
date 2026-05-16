/**
 * Zadania – lista i tworzenie przez Fetch API
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

    if (!list) {
        return;
    }

    loadTasks(list);

    if (btnNew && formWrap) {
        btnNew.addEventListener('click', async () => {
            formWrap.hidden = false;
            form?.reset();
            hideFormError(formError);
            await loadFormOptions(projectSelect, assigneeSelect);
            document.getElementById('task-title')?.focus();
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

            const assigneeValue = form.assignee_id.value;
            const payload = {
                title: form.title.value.trim(),
                description: form.description.value.trim() || null,
                project_id: parseInt(form.project_id.value, 10),
                assignee_id: assigneeValue === '' ? null : parseInt(assigneeValue, 10),
                status: form.status.value,
                priority: form.priority.value,
                due_date: form.due_date.value || null,
            };

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            try {
                await TaskFlow.fetchJson('/api/tasks', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                formWrap.hidden = true;
                form.reset();
                await loadTasks(list);
            } catch (err) {
                showFormError(formError, err.message || 'Nie udało się utworzyć zadania.');
            } finally {
                submitBtn.disabled = false;
            }
        });
    }
});

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

async function loadFormOptions(projectSelect, assigneeSelect) {
    if (!projectSelect || !assigneeSelect) {
        return;
    }

    const [projectsData, usersData] = await Promise.all([
        TaskFlow.fetchJson('/api/projects'),
        TaskFlow.fetchJson('/api/users/options'),
    ]);

    fillSelect(projectSelect, projectsData.projects || [], 'id', 'name', '— wybierz projekt —');
    fillSelect(assigneeSelect, usersData.users || [], 'id', 'name', '— brak —', true);
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

        li.innerHTML = `
            <div class="project-item-header">
                <strong>${escapeHtml(task.title)}</strong>
                <span class="project-badge">${escapeHtml(statusLabel)}</span>
            </div>
            <p class="project-desc">Projekt #${task.project_id} · ${escapeHtml(priorityLabel)}</p>
            ${description}
            ${dueDate}
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
