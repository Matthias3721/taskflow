<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? ($config['app']['name'] ?? 'TaskFlow')) ?></title>
    <link rel="stylesheet" href="/assets/css/main.css?v=3">
</head>
<body>
    <script>document.body.classList.remove('sidebar-open');</script>
    <div class="app-shell">
        <?php include dirname(__DIR__) . '/layouts/sidebar.php'; ?>
        <div class="main-wrap">
            <header class="mobile-topbar">
                <button type="button" id="sidebar-open-btn" class="mobile-nav-btn" aria-label="Otwórz menu" aria-expanded="false" aria-controls="app-sidebar">
                    <span class="mobile-nav-icon" aria-hidden="true"></span>
                </button>
                <span class="mobile-topbar-title"><?= htmlspecialchars($title ?? ($config['app']['name'] ?? 'TaskFlow')) ?></span>
            </header>
            <main class="main-content content">
                <header class="page-header">
                    <h1><?= htmlspecialchars($title ?? '') ?></h1>
                </header>
                <?= $content ?? '' ?>
            </main>
        </div>
        <button type="button" id="mobile-backdrop" class="mobile-backdrop" aria-label="Zamknij menu" tabindex="-1"></button>
    </div>
    <script src="/assets/js/app.js" defer></script>
</body>
</html>
