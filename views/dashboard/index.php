<?php /** @var string $title */ ?>
<section class="dashboard page-view">
    <header class="page-intro">
        <h1 class="page-intro-title">Panel główny</h1>
        <p class="page-intro-desc">Podsumowanie Twoich projektów i zadań w jednym miejscu.</p>
    </header>

    <div class="stats-grid" id="dashboard-stats" aria-label="Statystyki">
        <article class="stat-card stat-card--projects">
            <div class="stat-card-icon stat-card-icon--projects" aria-hidden="true"></div>
            <div class="stat-card-content">
                <h3 class="stat-card-label">Projekty</h3>
                <p class="stat-value" id="stat-projects">—</p>
            </div>
        </article>
        <article class="stat-card stat-card--tasks">
            <div class="stat-card-icon stat-card-icon--tasks" aria-hidden="true"></div>
            <div class="stat-card-content">
                <h3 class="stat-card-label">Zadania</h3>
                <p class="stat-value" id="stat-tasks">—</p>
            </div>
        </article>
        <article class="stat-card stat-card--done">
            <div class="stat-card-icon stat-card-icon--done" aria-hidden="true"></div>
            <div class="stat-card-content">
                <h3 class="stat-card-label">Ukończone</h3>
                <p class="stat-value" id="stat-done">—</p>
            </div>
        </article>
    </div>

    <section class="dashboard-section" aria-labelledby="dashboard-progress-heading">
        <h2 id="dashboard-progress-heading" class="section-heading">Postęp projektów</h2>
        <div id="dashboard-progress" class="progress-cards">
            <p class="loading-placeholder text-muted">Ładowanie…</p>
        </div>
    </section>
</section>
<script src="/assets/js/dashboard.js" defer></script>
