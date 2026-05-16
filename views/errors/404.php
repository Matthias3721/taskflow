<section class="error-page">
    <h1>404</h1>
    <p>Strona nie została znaleziona.</p>
    <?php if (!empty($errorMessage)): ?>
        <p class="text-muted"><?= htmlspecialchars($errorMessage) ?></p>
    <?php endif; ?>
    <a href="/">Wróć na stronę główną</a>
</section>
