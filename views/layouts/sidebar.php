<?php
/** @var array<string, mixed> $config */
/** @var array{id: int, name: string, email: string, role: string}|null $currentUser */
$user = $currentUser ?? null;
$isAdmin = $user !== null && ($user['role'] ?? '') === 'admin';
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="/"><?= htmlspecialchars($config['app']['name'] ?? 'TaskFlow') ?></a>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li><a href="/dashboard">Panel</a></li>
            <li><a href="/projects">Projekty</a></li>
            <li><a href="/tasks">Zadania</a></li>
            <li><a href="/categories">Kategorie</a></li>
            <?php if ($isAdmin): ?>
                <li><a href="/users">Użytkownicy</a></li>
            <?php endif; ?>
            <li class="nav-divider"></li>
            <?php if ($user !== null): ?>
                <li class="nav-user text-muted"><?= htmlspecialchars($user['name']) ?></li>
                <li><a href="#" id="logout-btn">Wyloguj</a></li>
            <?php else: ?>
                <li><a href="/login">Logowanie</a></li>
                <li><a href="/register">Rejestracja</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
