<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? ($config['app']['name'] ?? 'TaskFlow')) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="app-shell">
        <?php include dirname(__DIR__) . '/layouts/sidebar.php'; ?>
        <main class="main-content">
            <header class="page-header">
                <h1><?= htmlspecialchars($title ?? '') ?></h1>
            </header>
            <?= $content ?? '' ?>
        </main>
    </div>
    <script src="/assets/js/app.js" defer></script>
</body>
</html>
