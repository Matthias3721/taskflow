<?php /** @var string $title */ ?>
<section class="dashboard">
    <p class="text-muted">Podsumowanie Twoich projektów i zadań.</p>

    <div class="stats-grid" id="dashboard-stats">
        <article class="stat-card">
            <h3>Projekty</h3>
            <p class="stat-value" id="stat-projects">—</p>
        </article>
        <article class="stat-card">
            <h3>Zadania</h3>
            <p class="stat-value" id="stat-tasks">—</p>
        </article>
        <article class="stat-card">
            <h3>Ukończone</h3>
            <p class="stat-value" id="stat-done">—</p>
        </article>
    </div>

    <h2 class="dashboard-section-title">Postęp projektów</h2>
    <div id="dashboard-progress" class="data-list">
        <p class="text-muted">Ładowanie…</p>
    </div>
</section>
<script src="/assets/js/dashboard.js" defer></script>
