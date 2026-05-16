-- TaskFlow – dane początkowe (środowisko deweloperskie)
-- Hasła: admin123, user123 (password_hash PASSWORD_DEFAULT / bcrypt)

INSERT INTO roles (name, description) VALUES
    ('admin', 'Administrator systemu – pełny dostęp'),
    ('project_manager', 'Kierownik projektu – zarządzanie projektami i zespołem'),
    ('user', 'Standardowy użytkownik');

INSERT INTO users (email, name, password_hash, role_id) VALUES
    (
        'admin@taskflow.local',
        'Administrator',
        '$2y$10$mPTajuPZ6uWWkLtr6Jlp/ez3oEQ25AHnrhjfHciDA2CKWPjWRY1vm',
        (SELECT id FROM roles WHERE name = 'admin')
    ),
    (
        'user@taskflow.local',
        'Jan Kowalski',
        '$2y$10$BnYRUFEImmOxFQ9W5j3wZehDVX0L66cql7M9wU1oquGqvK8aB.EDu',
        (SELECT id FROM roles WHERE name = 'user')
    );

INSERT INTO user_profiles (user_id, display_name, bio) VALUES
    (1, 'Administrator', 'Konto administratora systemu'),
    (2, 'Jan Kowalski', 'Konto użytkownika demonstracyjnego');

INSERT INTO categories (name, color) VALUES
    ('Błąd', '#ef4444'),
    ('Funkcja', '#22c55e'),
    ('Dokumentacja', '#8b5cf6');

INSERT INTO projects (name, description, status, owner_id) VALUES
    ('TaskFlow MVP', 'Główny projekt aplikacji', 'active', 1),
    ('Demo', 'Projekt demonstracyjny', 'active', 2);

INSERT INTO project_members (project_id, user_id) VALUES
    (1, 1),
    (1, 2),
    (2, 2);

INSERT INTO tasks (title, description, status, project_id, assignee_id, category_id) VALUES
    ('Konfiguracja Docker', 'Uruchomienie środowiska', 'done', 1, 1, 3),
    ('Router MVC', 'Podstawowy routing', 'in_progress', 1, 1, 2),
    ('Formularz logowania', 'UI + sesje', 'todo', 1, 2, 2);

INSERT INTO task_status_history (task_id, old_status, new_status, changed_by, changed_at) VALUES
    (1, NULL, 'todo', 1, CURRENT_TIMESTAMP - INTERVAL '3 days'),
    (1, 'todo', 'in_progress', 1, CURRENT_TIMESTAMP - INTERVAL '2 days'),
    (1, 'in_progress', 'done', 1, CURRENT_TIMESTAMP - INTERVAL '1 day'),
    (2, NULL, 'todo', 1, CURRENT_TIMESTAMP - INTERVAL '2 days'),
    (2, 'todo', 'in_progress', 1, CURRENT_TIMESTAMP - INTERVAL '1 day');

INSERT INTO activity_logs (user_id, action, entity_type, entity_id, metadata) VALUES
    (1, 'project.created', 'project', 1, '{"name": "TaskFlow MVP"}'),
    (1, 'task.status_changed', 'task', 1, '{"old_status": "in_progress", "new_status": "done"}'),
    (2, 'task.assigned', 'task', 3, '{"assignee_id": 2}');
