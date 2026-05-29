-- TaskFlow – dane początkowe (środowisko deweloperskie)
-- Hasła (PASSWORD_DEFAULT / bcrypt, password_verify): admin123, pm123, user123

INSERT INTO roles (name, description) VALUES
    ('admin', 'Administrator systemu – pełny dostęp'),
    ('project_manager', 'Kierownik projektu – zarządzanie projektami i zespołem'),
    ('user', 'Standardowy użytkownik');

INSERT INTO users (email, name, password_hash, role_id, is_active) VALUES
    (
        'admin@taskflow.local',
        'Administrator',
        '$2y$10$mPTajuPZ6uWWkLtr6Jlp/ez3oEQ25AHnrhjfHciDA2CKWPjWRY1vm',
        (SELECT id FROM roles WHERE name = 'admin'),
        TRUE
    ),
    (
        'pm@taskflow.local',
        'Anna Nowak',
        '$2y$10$Jsvehp0SNBna/QwUKdFA/.LiRfesddUOxQcLau54.qFJH8sfe3unu',
        (SELECT id FROM roles WHERE name = 'project_manager'),
        TRUE
    ),
    (
        'user@taskflow.local',
        'Jan Kowalski',
        '$2y$10$BnYRUFEImmOxFQ9W5j3wZehDVX0L66cql7M9wU1oquGqvK8aB.EDu',
        (SELECT id FROM roles WHERE name = 'user'),
        TRUE
    );

INSERT INTO user_profiles (user_id, display_name, bio) VALUES
    (1, 'Administrator', 'Konto administratora systemu'),
    (2, 'Anna Nowak', 'Kierownik projektu – zarządzanie harmonogramem i zespołem'),
    (3, 'Jan Kowalski', 'Członek zespołu – realizacja przypisanych zadań');

INSERT INTO categories (name, color) VALUES
    ('Development', '#3b82f6'),
    ('Bug', '#ef4444'),
    ('Design', '#a855f7'),
    ('Documentation', '#6366f1');

INSERT INTO projects (name, description, status, owner_id) VALUES
    (
        'TaskFlow MVP',
        'Główny produkt: aplikacja do zarządzania projektami i zadaniami',
        'active',
        (SELECT id FROM users WHERE email = 'admin@taskflow.local')
    ),
    (
        'Website Redesign',
        'Odświeżenie layoutu marketingowej strony i spójności wizualnej',
        'active',
        (SELECT id FROM users WHERE email = 'pm@taskflow.local')
    ),
    (
        'Mobile App Launch',
        'Przygotowanie wersji mobilnej i testy na urządzeniach',
        'on_hold',
        (SELECT id FROM users WHERE email = 'pm@taskflow.local')
    ),
    (
        'Documentation Update',
        'README, diagramy architektury i instrukcja uruchomienia',
        'active',
        (SELECT id FROM users WHERE email = 'admin@taskflow.local')
    );

INSERT INTO project_members (project_id, user_id) VALUES
    (1, (SELECT id FROM users WHERE email = 'user@taskflow.local')),
    (2, (SELECT id FROM users WHERE email = 'user@taskflow.local')),
    (3, (SELECT id FROM users WHERE email = 'user@taskflow.local')),
    (4, (SELECT id FROM users WHERE email = 'user@taskflow.local')),
    (4, (SELECT id FROM users WHERE email = 'pm@taskflow.local'));

INSERT INTO tasks (title, description, status, priority, project_id, assignee_id, category_id) VALUES
    (
        'Implementacja logowania',
        'Formularz logowania, sesja PHP i endpoint POST /api/login',
        'done',
        'high',
        1,
        (SELECT id FROM users WHERE email = 'user@taskflow.local'),
        (SELECT id FROM categories WHERE name = 'Development')
    ),
    (
        'Panel projektów',
        'Widok listy projektów, formularz CRUD i integracja z API',
        'in_progress',
        'high',
        1,
        (SELECT id FROM users WHERE email = 'user@taskflow.local'),
        (SELECT id FROM categories WHERE name = 'Development')
    ),
    (
        'Widok zadań',
        'Lista zadań z filtrami statusu i uprawnieniami per rola',
        'in_progress',
        'medium',
        2,
        (SELECT id FROM users WHERE email = 'pm@taskflow.local'),
        (SELECT id FROM categories WHERE name = 'Design')
    ),
    (
        'Testy endpointów',
        'Skrypt test-endpoints.sh i scenariusze 401/403',
        'todo',
        'medium',
        1,
        (SELECT id FROM users WHERE email = 'admin@taskflow.local'),
        (SELECT id FROM categories WHERE name = 'Development')
    ),
    (
        'Dokumentacja README',
        'Opis projektu, uruchomienie Docker, konta testowe i diagramy',
        'in_progress',
        'medium',
        4,
        (SELECT id FROM users WHERE email = 'user@taskflow.local'),
        (SELECT id FROM categories WHERE name = 'Documentation')
    ),
    (
        'Poprawa responsywności',
        'Sidebar mobilny, karty dashboardu i layout na małych ekranach',
        'todo',
        'high',
        2,
        (SELECT id FROM users WHERE email = 'user@taskflow.local'),
        (SELECT id FROM categories WHERE name = 'Design')
    );

INSERT INTO task_status_history (task_id, old_status, new_status, changed_by, changed_at) VALUES
    (1, NULL, 'todo', (SELECT id FROM users WHERE email = 'admin@taskflow.local'), CURRENT_TIMESTAMP - INTERVAL '5 days'),
    (1, 'todo', 'in_progress', (SELECT id FROM users WHERE email = 'user@taskflow.local'), CURRENT_TIMESTAMP - INTERVAL '4 days'),
    (1, 'in_progress', 'done', (SELECT id FROM users WHERE email = 'user@taskflow.local'), CURRENT_TIMESTAMP - INTERVAL '2 days'),
    (2, NULL, 'todo', (SELECT id FROM users WHERE email = 'admin@taskflow.local'), CURRENT_TIMESTAMP - INTERVAL '3 days'),
    (2, 'todo', 'in_progress', (SELECT id FROM users WHERE email = 'user@taskflow.local'), CURRENT_TIMESTAMP - INTERVAL '1 day'),
    (3, NULL, 'todo', (SELECT id FROM users WHERE email = 'pm@taskflow.local'), CURRENT_TIMESTAMP - INTERVAL '2 days'),
    (3, 'todo', 'in_progress', (SELECT id FROM users WHERE email = 'pm@taskflow.local'), CURRENT_TIMESTAMP - INTERVAL '12 hours'),
    (5, NULL, 'todo', (SELECT id FROM users WHERE email = 'admin@taskflow.local'), CURRENT_TIMESTAMP - INTERVAL '2 days'),
    (5, 'todo', 'in_progress', (SELECT id FROM users WHERE email = 'user@taskflow.local'), CURRENT_TIMESTAMP - INTERVAL '6 hours');

INSERT INTO activity_logs (user_id, action, entity_type, entity_id, metadata) VALUES
    (
        (SELECT id FROM users WHERE email = 'admin@taskflow.local'),
        'project.created',
        'project',
        1,
        '{"name": "TaskFlow MVP"}'
    ),
    (
        (SELECT id FROM users WHERE email = 'pm@taskflow.local'),
        'project.created',
        'project',
        2,
        '{"name": "Website Redesign"}'
    ),
    (
        (SELECT id FROM users WHERE email = 'user@taskflow.local'),
        'task.status_changed',
        'task',
        1,
        '{"old_status": "in_progress", "new_status": "done"}'
    ),
    (
        (SELECT id FROM users WHERE email = 'user@taskflow.local'),
        'task.assigned',
        'task',
        5,
        '{"assignee_id": 3}'
    );
