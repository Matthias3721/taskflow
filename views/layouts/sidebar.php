<?php
/** @var array<string, mixed> $config */
/** @var array{id: int, name: string, email: string, role: string}|null $currentUser */
$user = $currentUser ?? null;
$isAdmin = $user !== null && ($user['role'] ?? '') === 'admin';

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$currentPath = rtrim($currentPath, '/') ?: '/';

$navActive = static function (string $path) use ($currentPath): string {
    if ($path === '/dashboard' && ($currentPath === '/' || $currentPath === '/dashboard')) {
        return ' is-active';
    }
    if ($path !== '/' && ($currentPath === $path || str_starts_with($currentPath, $path . '/'))) {
        return ' is-active';
    }

    return '';
};
?>
<aside id="app-sidebar" class="sidebar">
    <div class="sidebar-brand">
        <a href="/" class="sidebar-brand-link">
            <span class="sidebar-logo" aria-hidden="true"></span>
            <span class="sidebar-brand-text">
                <strong><?= htmlspecialchars($config['app']['name'] ?? 'TaskFlow') ?></strong>
                <small>The Cognitive Sanctuary</small>
            </span>
        </a>
        <button type="button" id="sidebar-close-btn" class="sidebar-close" aria-label="Zamknij menu">&times;</button>
    </div>
    <nav class="sidebar-nav" aria-label="Główne menu">
        <ul>
            <li>
                <a href="/dashboard" class="sidebar-link<?= $navActive('/dashboard') ?>">
                    <span class="sidebar-icon sidebar-icon-dashboard" aria-hidden="true"></span>
                    Panel
                </a>
            </li>
            <li>
                <a href="/projects" class="sidebar-link<?= $navActive('/projects') ?>">
                    <span class="sidebar-icon sidebar-icon-projects" aria-hidden="true"></span>
                    Projekty
                </a>
            </li>
            <li>
                <a href="/tasks" class="sidebar-link<?= $navActive('/tasks') ?>">
                    <span class="sidebar-icon sidebar-icon-tasks" aria-hidden="true"></span>
                    Zadania
                </a>
            </li>
            <li>
                <a href="/categories" class="sidebar-link<?= $navActive('/categories') ?>">
                    <span class="sidebar-icon sidebar-icon-categories" aria-hidden="true"></span>
                    Kategorie
                </a>
            </li>
            <?php if ($isAdmin): ?>
                <li>
                    <a href="/users" class="sidebar-link<?= $navActive('/users') ?>">
                        <span class="sidebar-icon sidebar-icon-users" aria-hidden="true"></span>
                        Użytkownicy
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <?php if ($user !== null): ?>
            <div class="sidebar-user">
                <span class="sidebar-user-avatar" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
                <span class="sidebar-user-meta">
                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                    <small><?= htmlspecialchars($user['email']) ?></small>
                </span>
            </div>
            <a href="#" id="logout-btn" class="sidebar-link sidebar-link-muted">
                <span class="sidebar-icon sidebar-icon-logout" aria-hidden="true"></span>
                Wyloguj
            </a>
        <?php else: ?>
            <a href="/login" class="sidebar-link<?= $navActive('/login') ?>">Logowanie</a>
            <a href="/register" class="sidebar-link<?= $navActive('/register') ?>">Rejestracja</a>
        <?php endif; ?>
    </div>
</aside>
