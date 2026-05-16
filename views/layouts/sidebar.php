<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="/"><?= htmlspecialchars($config['app']['name'] ?? 'TaskFlow') ?></a>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li><a href="/">Panel</a></li>
            <li><a href="/projects">Projekty</a></li>
            <li><a href="/tasks">Zadania</a></li>
            <li><a href="/users">Użytkownicy</a></li>
            <li class="nav-divider"></li>
            <li><a href="/login">Logowanie</a></li>
            <li><a href="/register">Rejestracja</a></li>
        </ul>
    </nav>
</aside>
