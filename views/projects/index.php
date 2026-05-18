<?php
/** @var string $title */
/** @var array{id: int, name: string, email: string, role: string}|null $currentUser */
$user = $currentUser ?? null;
?>
<section
    class="projects-page page-view"
    id="projects-page"
    data-user-id="<?= $user !== null ? (int) $user['id'] : 0 ?>"
    data-user-role="<?= htmlspecialchars($user['role'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
    <header class="page-intro page-intro--toolbar">
        <div class="page-intro-text">
            <h1 class="page-intro-title">Projekty</h1>
            <p class="page-intro-desc">Zarządzanie projektami</p>
        </div>
        <button type="button" class="btn btn-primary" id="btn-new-project">Nowy projekt</button>
    </header>

    <div id="project-form-wrap" class="project-form-wrap card-panel" hidden>
        <form id="project-form" class="form form-card">
            <h2 class="form-card-title" id="project-form-title">Nowy projekt</h2>
            <input type="hidden" id="project-id" value="">
            <div id="project-form-error" class="form-error" hidden></div>
            <label for="project-name">Nazwa</label>
            <input type="text" id="project-name" name="name" required maxlength="255">

            <label for="project-description">Opis</label>
            <textarea id="project-description" name="description" rows="3"></textarea>

            <label for="project-status">Status</label>
            <select id="project-status" name="status">
                <option value="active">Aktywny</option>
                <option value="on_hold">Wstrzymany</option>
                <option value="completed">Zakończony</option>
            </select>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Zapisz</button>
                <button type="button" class="btn" id="btn-cancel-project">Anuluj</button>
            </div>
        </form>
    </div>

    <div id="projects-list" class="project-cards">
        <p class="loading-placeholder text-muted">Ładowanie projektów…</p>
    </div>
</section>
<script src="/assets/js/projects.js" defer></script>
