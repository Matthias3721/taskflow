<section class="error-page">
    <h1>500</h1>
    <p>Wystąpił błąd serwera.</p>
    <?php if (!empty($errorMessage)): ?>
        <p class="text-muted"><?= htmlspecialchars($errorMessage) ?></p>
    <?php endif; ?>
    <a href="/">Wróć na stronę główną</a>
</section>
