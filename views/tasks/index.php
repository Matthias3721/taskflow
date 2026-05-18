<?php
/** @var string $title */
/** @var array{id: int, name: string, email: string, role: string}|null $currentUser */
$canCreateTask = in_array($currentUser['role'] ?? '', ['admin', 'project_manager'], true);
?>
<section>
    <div class="section-toolbar">
        <?php if ($canCreateTask): ?>
            <button type="button" class="btn btn-primary" id="btn-new-task">Nowe zadanie</button>
        <?php endif; ?>
    </div>

    <div id="task-form-wrap" class="project-form-wrap" hidden>
        <form id="task-form" class="form">
            <h3 id="task-form-title">Nowe zadanie</h3>
            <input type="hidden" id="task-id" name="id" value="">
            <div id="task-form-error" class="form-error" hidden></div>

            <label class="task-field-full" for="task-title">Tytuł</label>
            <input class="task-field-full" type="text" id="task-title" name="title" required maxlength="255">

            <label for="task-description">Opis</label>
            <textarea id="task-description" name="description" rows="3"></textarea>

            <label class="task-field-full" for="task-project">Projekt</label>
            <select class="task-field-full" id="task-project" name="project_id" required>
                <option value="">— wybierz projekt —</option>
            </select>

            <label class="task-field-full" for="task-assignee">Przypisany użytkownik</label>
            <select class="task-field-full" id="task-assignee" name="assignee_id">
                <option value="">— brak —</option>
            </select>

            <label for="task-status">Status</label>
            <select id="task-status" name="status">
                <option value="todo">Do zrobienia</option>
                <option value="in_progress">W trakcie</option>
                <option value="done">Zakończone</option>
            </select>

            <label class="task-field-full" for="task-category">Kategoria</label>
            <select class="task-field-full" id="task-category" name="category_id">
                <option value="">— brak —</option>
            </select>

            <label class="task-field-full" for="task-priority">Priorytet</label>
            <select class="task-field-full" id="task-priority" name="priority">
                <option value="low">Niski</option>
                <option value="medium" selected>Średni</option>
                <option value="high">Wysoki</option>
            </select>

            <label class="task-field-full" for="task-due-date">Termin</label>
            <input class="task-field-full" type="date" id="task-due-date" name="due_date">

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="task-submit-btn">Zapisz</button>
                <button type="button" class="btn" id="btn-cancel-task">Anuluj</button>
            </div>
        </form>
    </div>

    <div id="tasks-list" class="data-list">
        <p class="text-muted">Ładowanie zadań…</p>
    </div>
</section>
<script src="/assets/js/tasks.js" defer></script>
