<?php
/** @var string $title */
/** @var array{id: int, name: string, email: string, role: string}|null $currentUser */
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
?>
<section>
    <div class="section-toolbar">
        <?php if ($isAdmin): ?>
            <button type="button" class="btn btn-primary" id="btn-new-category">Nowa kategoria</button>
        <?php endif; ?>
    </div>

    <div id="category-form-wrap" class="project-form-wrap" hidden>
        <form id="category-form" class="form">
            <h3 id="category-form-title">Nowa kategoria</h3>
            <div id="category-form-error" class="form-error" hidden></div>
            <input type="hidden" id="category-id" name="id" value="">

            <label for="category-name">Nazwa</label>
            <input type="text" id="category-name" name="name" required maxlength="100">

            <label for="category-color">Kolor</label>
            <input type="color" id="category-color" name="color" value="#3b82f6">

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="category-submit-btn">Zapisz</button>
                <button type="button" class="btn" id="btn-cancel-category">Anuluj</button>
            </div>
        </form>
    </div>

    <div id="categories-list" class="data-list">
        <p class="text-muted">Ładowanie kategorii…</p>
    </div>
</section>
<script>
    window.TaskFlowCategories = { isAdmin: <?= $isAdmin ? 'true' : 'false' ?> };
</script>
<script src="/assets/js/categories.js" defer></script>
