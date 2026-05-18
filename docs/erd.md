# Diagram ERD — TaskFlow

Schemat odpowiada plikowi [`database/init.sql`](../database/init.sql). Typy ENUM (`task_status`, `task_priority`, `project_status`) są atrybutami kolumn w tabelach `tasks` i `projects`.

## Diagram encji-relacji

```mermaid
erDiagram
    roles ||--o{ users : "ma"
    users ||--|| user_profiles : "profil 1:1"
    users ||--o{ projects : "właściciel (owner)"
    users ||--o{ project_members : "członek"
    projects ||--o{ project_members : "członkowie"
    projects ||--o{ tasks : "zawiera"
    users ||--o{ tasks : "przypisany (assignee)"
    categories ||--o{ tasks : "kategoryzuje"
    tasks ||--o{ task_status_history : "historia statusu"
    users ||--o{ task_status_history : "zmienił (changed_by)"
    users ||--o{ activity_logs : "wykonał akcję"

    roles {
        serial id PK
        varchar name UK
        text description
        timestamp created_at
    }

    users {
        serial id PK
        varchar email UK
        varchar name
        varchar password_hash
        int role_id FK
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    user_profiles {
        int user_id PK_FK
        varchar display_name
        text bio
        varchar avatar_url
        varchar phone
        varchar timezone
        timestamp created_at
        timestamp updated_at
    }

    projects {
        serial id PK
        varchar name
        text description
        project_status status
        int owner_id FK
        timestamp created_at
        timestamp updated_at
    }

    project_members {
        serial id PK
        int project_id FK
        int user_id FK
        timestamp joined_at
    }

    categories {
        serial id PK
        varchar name UK
        varchar color
        timestamp created_at
    }

    tasks {
        serial id PK
        varchar title
        text description
        task_status status
        task_priority priority
        int project_id FK
        int assignee_id FK
        int category_id FK
        date due_date
        timestamp created_at
        timestamp updated_at
    }

    task_status_history {
        serial id PK
        int task_id FK
        task_status old_status
        task_status new_status
        int changed_by FK
        timestamp changed_at
    }

    activity_logs {
        serial id PK
        int user_id FK
        varchar action
        varchar entity_type
        int entity_id
        jsonb metadata
        inet ip_address
        timestamp created_at
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
