/**
 * Projekty – placeholder pod Fetch API
 */

document.addEventListener('DOMContentLoaded', async () => {
    const list = document.getElementById('projects-list');
    const detail = document.getElementById('project-detail');

    if (list) {
        try {
            // await TaskFlow.fetchJson('/api/projects');
            console.info('projects.js – lista projektów (API w kolejnym etapie)');
        } catch (err) {
            TaskFlow.showMessage(list, err.message, 'error');
        }
    }

    if (detail) {
        console.info('projects.js – szczegóły projektu (API w kolejnym etapie)');
    }
});
