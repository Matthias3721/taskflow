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
        container.innerHTML = '<p class="text-muted">Brak projektów do wyświetlenia.</p>';
        return;
    }

    const ul = document.createElement('ul');
    ul.className = 'progress-list';

    projects.forEach((project) => {
        const li = document.createElement('li');
        li.className = 'progress-item';

        const percent = Number(project.progress_percent ?? 0);
        const done = project.done_tasks ?? 0;
        const total = project.total_tasks ?? 0;
        const width = Math.min(100, Math.max(0, percent));

        li.innerHTML = `
            <div class="progress-item-header">
                <div>
                    <strong>${escapeHtml(project.project_name)}</strong>
                    <span class="progress-owner">${escapeHtml(project.owner_name)}</span>
                </div>
                <span class="progress-percent">${percent.toFixed(0)}%</span>
            </div>
            <div class="progress-bar" role="progressbar" aria-valuenow="${width}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar-fill" style="width: ${width}%"></div>
            </div>
            <p class="progress-meta">${done} / ${total} zadań ukończonych</p>
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
