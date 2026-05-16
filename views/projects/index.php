<?php /** @var string $title */ ?>
<section>
    <div class="section-toolbar">
        <button type="button" class="btn btn-primary" id="btn-new-project">Nowy projekt</button>
    </div>

    <div id="project-form-wrap" class="project-form-wrap" hidden>
        <form id="project-form" class="form">
            <h3>Nowy projekt</h3>
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

    <div id="projects-list" class="data-list">
        <p class="text-muted">Ładowanie projektów…</p>
    </div>
</section>
<script src="/assets/js/projects.js" defer></script>
