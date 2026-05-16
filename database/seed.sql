-- TaskFlow – dane początkowe (środowisko deweloperskie)

INSERT INTO users (email, name, password_hash, role) VALUES
    ('admin@taskflow.local', 'Administrator', crypt('admin123', gen_salt('bf')), 'admin'),
    ('user@taskflow.local', 'Jan Kowalski', crypt('user123', gen_salt('bf')), 'user');

INSERT INTO categories (name, color) VALUES
    ('Błąd', '#ef4444'),
    ('Funkcja', '#22c55e'),
    ('Dokumentacja', '#8b5cf6');

INSERT INTO projects (name, description, owner_id) VALUES
    ('TaskFlow MVP', 'Główny projekt aplikacji', 1),
    ('Demo', 'Projekt demonstracyjny', 2);

INSERT INTO tasks (title, description, status, project_id, assignee_id, category_id) VALUES
    ('Konfiguracja Docker', 'Uruchomienie środowiska', 'done', 1, 1, 3),
    ('Router MVC', 'Podstawowy routing', 'in_progress', 1, 1, 2),
    ('Formularz logowania', 'UI + sesje', 'todo', 1, 2, 2);
