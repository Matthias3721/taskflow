<?php /** @var string $title */ /** @var mixed $projectId */ ?>
<section>
    <p class="text-muted">Projekt #<?= htmlspecialchars((string) ($projectId ?? '')) ?></p>
    <div id="project-detail" class="detail-card">
        <p>Szczegóły projektu zostaną załadowane przez Fetch API.</p>
    </div>
</section>
<script src="/assets/js/projects.js" defer></script>
