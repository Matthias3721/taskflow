# Diagram ERD — TaskFlow

Schemat odpowiada plikowi [`database/init.sql`](../database/init.sql). Typy ENUM (`task_status`, `task_priority`, `project_status`) są atrybutami kolumn w tabelach `tasks` i `projects`.

## Diagram encji-relacji

```mermaid
erDiagram
    ROLES ||--o{ USERS : has
    USERS ||--|| USER_PROFILES : has_profile
    USERS ||--o{ PROJECTS : owns
    USERS ||--o{ PROJECT_MEMBERS : belongs
    PROJECTS ||--o{ PROJECT_MEMBERS : has_members
    PROJECTS ||--o{ TASKS : has_tasks
    USERS ||--o{ TASKS : assigned
    CATEGORIES ||--o{ TASKS : groups
    TASKS ||--o{ TASK_STATUS_HISTORY : has_history
    USERS ||--o{ TASK_STATUS_HISTORY : changed_by
    USERS ||--o{ ACTIVITY_LOGS : creates

    ROLES {
        int id
        string name
        string description
        datetime created_at
    }

    USERS {
        int id
        string email
        string name
        string password_hash
        int role_id
        boolean is_active
        datetime created_at
        datetime updated_at
    }

    USER_PROFILES {
        int user_id
        string display_name
        string bio
        string avatar_url
        string phone
        string timezone
        datetime created_at
        datetime updated_at
    }

    PROJECTS {
        int id
        string name
        string description
        string status
        int owner_id
        datetime created_at
        datetime updated_at
    }

    PROJECT_MEMBERS {
        int id
        int project_id
        int user_id
        datetime joined_at
    }

    CATEGORIES {
        int id
        string name
        string color
        datetime created_at
    }

    TASKS {
        int id
        string title
        string description
        string status
        string priority
        int project_id
        int assignee_id
        int category_id
        date due_date
        datetime created_at
        datetime updated_at
    }

    TASK_STATUS_HISTORY {
        int id
        int task_id
        string old_status
        string new_status
        int changed_by
        datetime changed_at
    }

    ACTIVITY_LOGS {
        int id
        int user_id
        string action
        string entity_type
        int entity_id
        string metadata
        string ip_address
        datetime created_at
    }
```

## Relacje (skrót)

| Relacja | Opis | Implementacja |
|---------|------|----------------|
| **roles 1:N users** | Każdy użytkownik ma jedną rolę systemową | `users.role_id → roles.id` |
| **users 1:1 user_profiles** | Opcjonalny profil rozszerzony | `user_profiles.user_id` PK/FK → `users.id` |
| **users 1:N projects** | Właściciel projektu | `projects.owner_id → users.id` |
| **users N:M projects** | Członkostwo w projekcie | Tabela łącząca `project_members` (`UNIQUE (project_id, user_id)`) |
| **projects 1:N tasks** | Zadania w projekcie | `tasks.project_id → projects.id` |
| **users 1:N tasks** | Osoba przypisana do zadania | `tasks.assignee_id → users.id` (nullable) |
| **categories 1:N tasks** | Kategoria zadania | `tasks.category_id → categories.id` (nullable) |
| **tasks 1:N task_status_history** | Historia zmian statusu | `task_status_history.task_id → tasks.id` |
| **users 1:N activity_logs** | Log aktywności użytkownika | `activity_logs.user_id → users.id` (nullable) |

## Obiekty poza tabelami (init.sql)

| Obiekt | Typ | Opis |
|--------|-----|------|
| `view_project_progress` | VIEW | Postęp projektów (JOIN + agregacja zadań) |
| `view_user_task_summary` | VIEW | Podsumowanie zadań per użytkownik |
| `calculate_project_progress()` | FUNCTION | Procent zadań `done` w projekcie |
| `update_tasks_updated_at` | TRIGGER | Ustawia `tasks.updated_at` przy UPDATE |
| `log_task_status_change` | TRIGGER | Wpis do `task_status_history` przy zmianie statusu |

## Powiązane pliki

- Schemat: [`database/init.sql`](../database/init.sql)
- Dane testowe: [`database/seed.sql`](../database/seed.sql)
- Architektura aplikacji: [`architecture.md`](architecture.md)
