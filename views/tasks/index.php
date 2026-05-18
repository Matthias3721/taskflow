<?php /** @var string $title */ ?>
<section>
    <div class="section-toolbar">
        <button type="button" class="btn btn-primary" id="btn-new-task">Nowe zadanie</button>
    </div>

    <div id="task-form-wrap" class="project-form-wrap" hidden>
        <form id="task-form" class="form">
            <h3>Nowe zadanie</h3>
            <div id="task-form-error" class="form-error" hidden></div>

            <label for="task-title">Tytuł</label>
            <input type="text" id="task-title" name="title" required maxlength="255">

            <label for="task-description">Opis</label>
            <textarea id="task-description" name="description" rows="3"></textarea>

            <label for="task-project">Projekt</label>
            <select id="task-project" name="project_id" required>
                <option value="">— wybierz projekt —</option>
            </select>

            <label for="task-assignee">Przypisany użytkownik</label>
            <select id="task-assignee" name="assignee_id">
                <option value="">— brak —</option>
            </select>

            <label for="task-status">Status</label>
            <select id="task-status" name="status">
                <option value="todo">Do zrobienia</option>
                <option value="in_progress">W trakcie</option>
                <option value="done">Zakończone</option>
            </select>

            <label for="task-category">Kategoria</label>
            <select id="task-category" name="category_id">
                <option value="">— brak —</option>
            </select>

            <label for="task-priority">Priorytet</label>
            <select id="task-priority" name="priority">
                <option value="low">Niski</option>
                <option value="medium" selected>Średni</option>
                <option value="high">Wysoki</option>
            </select>

            <label for="task-due-date">Termin</label>
            <input type="date" id="task-due-date" name="due_date">

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Zapisz</button>
                <button type="button" class="btn" id="btn-cancel-task">Anuluj</button>
            </div>
        </form>
    </div>

    <div id="tasks-list" class="data-list">
        <p class="text-muted">Ładowanie zadań…</p>
    </div>
</section>
<script src="/assets/js/tasks.js" defer></script>
