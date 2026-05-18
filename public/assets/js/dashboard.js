/**
 * Panel główny – dane z GET /api/dashboard
 */

document.addEventListener('DOMContentLoaded', () => {
    const statsProjects = document.getElementById('stat-projects');
    const statsTasks = document.getElementById('stat-tasks');
    const statsDone = document.getElementById('stat-done');
    const progressList = document.getElementById('dashboard-progress');

    if (!progressList) {
        return;
    }

    loadDashboard(statsProjects, statsTasks, statsDone, progressList);
});

async function loadDashboard(statsProjects, statsTasks, statsDone, progressList) {
    try {
        const data = await TaskFlow.fetchJson('/api/dashboard');

        if (statsProjects) statsProjects.textContent = String(data.total_projects ?? 0);
        if (statsTasks) statsTasks.textContent = String(data.total_tasks ?? 0);
        if (statsDone) statsDone.textContent = String(data.done_tasks ?? 0);

        renderProgress(progressList, data.projects_progress || []);
    } catch (err) {
        progressList.innerHTML = '';
        TaskFlow.showMessage(
            progressList,
            err.message || 'Nie udało się załadować panelu.',
            'error',
        );
    }
}

function renderProgress(container, projects) {
    if (!projects.length) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon empty-state-icon--projects" aria-hidden="true"></div>
                <h3 class="empty-state-title">Brak projektów</h3>
                <p class="empty-state-text">Gdy dodasz projekty, zobaczysz tutaj postęp zadań w każdym z nich.</p>
            </div>
        `;
        return;
    }

    const ul = document.createElement('ul');
    ul.className = 'progress-list';

    projects.forEach((project) => {
        const li = document.createElement('li');
        li.className = 'progress-card';

        const percent = Number(project.progress_percent ?? 0);
        const done = project.done_tasks ?? 0;
        const total = project.total_tasks ?? 0;
        const width = Math.min(100, Math.max(0, percent));
        const ownerName = project.owner_name ? String(project.owner_name).trim() : '';

        const ownerHtml = ownerName
            ? `<span class="progress-card-owner">${escapeHtml(ownerName)}</span>`
            : '';

        li.innerHTML = `
            <div class="progress-card-header">
                <div class="progress-card-title-wrap">
                    <strong class="progress-card-title">${escapeHtml(project.project_name)}</strong>
                    ${ownerHtml}
                </div>
                <span class="progress-card-percent">${percent.toFixed(0)}%</span>
            </div>
            <div class="progress-bar" role="progressbar" aria-valuenow="${width}" aria-valuemin="0" aria-valuemax="100" aria-label="Postęp projektu ${escapeHtml(project.project_name)}">
                <div class="progress-bar-fill" style="width: ${width}%"></div>
            </div>
            <p class="progress-card-meta">${done} / ${total} zadań ukończonych</p>
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
