# TaskFlow

**TaskFlow** to aplikacja webowa do zarządzania projektami i zadaniami. Umożliwia pracę zespołową w ramach projektów, śledzenie statusów zadań, kategoryzację oraz podgląd postępu na panelu głównym. Projekt powstał w ramach zajęć — implementacja w **PHP 8.2 (OOP)**, architektura **MVC**, baza **PostgreSQL**, uruchomienie w **Docker Compose**, interfejs w **HTML5 / CSS / JavaScript (Fetch API)**.

**Autor:** Mateusz Więcek

---

## 1. Opis projektu

TaskFlow łączy panel administracyjny z codzienną pracą użytkownika: logowanie oparte o sesję, widoki HTML renderowane po stronie serwera oraz operacje CRUD wykonywane asynchronicznie przez REST API (`/api/...`) z poziomu przeglądarki. Aplikacja **nie korzysta z frameworków** PHP ani JS (brak Laravel, Symfony, React, Vue, Bootstrap itd.) — routing, warstwa HTTP, repozytoria i serwisy są zaimplementowane ręcznie.

Główne obszary systemu:

- **Uwierzytelnianie** — rejestracja, logowanie, wylogowanie, sesja PHP
- **Projekty** — tworzenie, edycja, usuwanie (zależnie od roli), statusy: `active`, `on_hold`, `completed`
- **Zadania** — przypisanie do projektu, statusy `todo` / `in_progress` / `done`, priorytety, kategorie, termin
- **Kategorie** — słownik kategorii zadań (zarządzanie przez administratora)
- **Panel główny** — statystyki i postęp projektów (widok SQL `view_project_progress`)
- **Użytkownicy** — lista i zmiana ról/statusu (tylko administrator)

Aplikacja dostępna domyślnie pod adresem: **http://localhost:8080**

---

## 2. Główne funkcjonalności

| Obszar | Opis |
|--------|------|
| **Auth** | `POST /api/login`, `POST /api/logout`, `GET /api/me`, strony `/login`, `/register` |
| **Dashboard** | Statystyki projektów/zadań, lista postępu z widoku `view_project_progress` |
| **Projekty** | Lista, formularz tworzenia/edycji, usuwanie; API REST pod `/api/projects` |
| **Zadania** | Lista z filtrami uprawnień, CRUD przez `/api/tasks`; ograniczona edycja dla roli `user` |
| **Kategorie** | CRUD kategorii (`/api/categories`) — mutacje tylko dla `admin` |
| **Użytkownicy** | Panel `/users` i API `/api/users` — wyłącznie `admin` |
| **Błędy** | Strony HTML: 400, 401, 403, 404, 500; API zwraca JSON z komunikatem |
| **Testy** | PHPUnit (serwisy), skrypt `test-endpoints.sh` (integracja API) |

Front-end: responsywny layout (sidebar, widoki Dashboard i Projects w jasnym stylu SaaS), komunikacja wyłącznie przez **Fetch API** i wspólny moduł `TaskFlow` w `public/assets/js/app.js`.

---

## 3. Role użytkowników i uprawnienia

W bazie zdefiniowane są role w tabeli `roles`: `admin`, `project_manager`, `user`. Logika uprawnień znajduje się w `app/Core/Authorization.php` oraz serwisach (`ProjectService`, `TaskService`, `CategoryService`).

### Administrator (`admin`)

- Pełny wgląd we wszystkie projekty i zadania
- Edycja i usuwanie dowolnego projektu
- Pełne zarządzanie zadaniami w każdym projekcie
- Zarządzanie kategoriami (tworzenie, edycja, usuwanie)
- Panel użytkowników (`/users`, `/api/users`)
- Dashboard ze wszystkimi projektami

### Kierownik projektu (`project_manager`)

- Widzi projekty, których jest **właścicielem** (`owner_id`)
- Może edytować i usuwać **tylko własne** projekty
- Zarządza zadaniami w projektach, których jest właścicielem
- Nie ma dostępu do panelu użytkowników ani mutacji kategorii (jak zwykły użytkownik przy kategoriach)

> **Uwaga:** W pliku `database/seed.sql` nie ma konta e-mail `pm@taskflow.local`. Rolę `project_manager` można przypisać ręcznie w bazie lub przez panel admina po jego utworzeniu.

### Użytkownik (`user`)

- Widzi projekty, do których ma dostęp (właściciel lub członek w `project_members`)
- Może **tworzyć** nowe projekty (staje się ich właścicielem)
- **Nie** edytuje ani nie usuwa projektów zarządzanych przez innych (brak `can_edit` / `can_delete` w API)
- Widzi zadania **przypisane do siebie** (`assignee_id`)
- Może **ograniczenie edytować** przypisane zadanie (status, opis) — flaga `can_edit_limited` w API
- Nie usuwa zadań; brak dostępu do `/users` i mutacji kategorii

### Macierz skrócona

| Akcja | admin | project_manager | user |
|-------|:-----:|:---------------:|:----:|
| Lista wszystkich projektów | ✓ | własne | dostępne |
| Edycja/usunięcie projektu | ✓ | własne | — |
| Tworzenie projektu | ✓ | ✓ | ✓ |
| Pełne CRUD zadań | ✓ | w swoich projektach | ograniczona edycja |
| Kategorie (zapis) | ✓ | — | — |
| Panel użytkowników | ✓ | — | — |

---

## 4. Technologie

| Warstwa | Technologia |
|---------|-------------|
| Backend | PHP 8.2+, OOP, PSR-4 (`App\`) |
| Architektura | MVC (własny router, kontrolery, modele, repozytoria, serwisy) |
| Baza danych | PostgreSQL 16 |
| Dostęp do DB | PDO |
| Konteneryzacja | Docker, Docker Compose |
| Serwer WWW | Apache (obraz `php-apache` w `Dockerfile`) |
| Front-end | HTML5, CSS3, JavaScript (ES6+, Fetch API) |
| Testy | PHPUnit 10 |
| Kontrola wersji | Git |
| Zależności PHP | Composer (`composer.json`) |

**Świadomie nieużywane:** Laravel, Symfony, Slim, React, Vue, Angular, Bootstrap, Tailwind, jQuery.

---

## 5. Architektura aplikacji MVC

```
Przeglądarka (HTML + JS Fetch)
        │
        ▼
public/index.php  ──►  App::run()
        │
        ├── Session (autoryzacja)
        ├── Router (dopasowanie URI → kontroler@metoda)
        └── Controller
                ├── Services (reguły biznesowe, uprawnienia)
                ├── Repositories (zapytania SQL / PDO)
                └── Response
                        ├── HTML (views/*.php + layouts)
                        └── JSON (/api/*)
```

**Model** — klasy w `app/Models/` (encje: `User`, `Project`, `Task`, `Category`) oraz warstwa persistence w repozytoriach.

**View** — szablony PHP w `views/` (layout `views/layouts/main.php`, widoki funkcjonalne, strony błędów).

**Controller** — klasy w `app/Controllers/`, cienka warstwa: walidacja wejścia HTTP, wywołanie serwisu, zwrot `Response`.

Przepływ typowego żądania API:

1. `Request::capture()` — metoda, URI, nagłówki, JSON body  
2. Kontroler sprawdza sesję (`requireAuthJson()` / przekierowanie na `/login`)  
3. Serwis stosuje reguły `Authorization` i wywołuje repozytorium  
4. `Response::json()` lub `Response::html()` + `ErrorHandler` przy wyjątkach  

Diagram architektury (Mermaid): [`docs/architecture.md`](docs/architecture.md)  
Opcjonalny eksport PNG: [`docs/architecture.png`](docs/architecture.png)

---

## 6. Struktura katalogów

```
TaskFlow/
├── app/
│   ├── Controllers/      # Kontrolery MVC (Auth, Dashboard, Project, Task, Category, User)
│   ├── Core/             # App, Router, Request, Response, Database, Session, Authorization, ErrorHandler
│   ├── Middleware/       # RoleMiddleware (np. dostęp admina do /users)
│   ├── Models/           # Encje domenowe
│   ├── Repositories/     # Dostęp do PostgreSQL (PDO)
│   └── Services/         # Logika biznesowa
├── config/
│   └── config.php        # Konfiguracja (odczyt .env / zmiennych środowiskowych)
├── database/
│   ├── init.sql          # Schemat, widoki, funkcje, triggery
│   ├── seed.sql          # Dane testowe
│   └── migrations/       # Migracje inkrementalne
├── docs/                 # Diagramy Mermaid (architecture.md, erd.md), screeny — sekcje 13–14
├── public/
│   ├── index.php         # Front controller
│   ├── .htaccess
│   └── assets/
│       ├── css/style.css
│       └── js/           # app.js, auth.js, dashboard.js, projects.js, tasks.js, …
├── tests/                # PHPUnit — testy serwisów
├── views/
│   ├── layouts/          # main.php, sidebar.php
│   ├── auth/             # login, register
│   ├── dashboard/
│   ├── projects/
│   ├── tasks/
│   ├── categories/
│   ├── users/
│   └── errors/           # 400, 401, 403, 404, 500
├── .env.example
├── composer.json
├── docker-compose.yml
├── Dockerfile
├── phpunit.xml
├── test-endpoints.sh
└── README.md
```

---

## 7. Model bazy danych

### Tabele główne

| Tabela | Opis |
|--------|------|
| `roles` | Role systemowe (`admin`, `project_manager`, `user`) |
| `users` | Konta użytkowników (`role_id` → `roles`) |
| `user_profiles` | Profil 1:1 do użytkownika (display_name, bio, avatar, …) |
| `projects` | Projekty (`owner_id` → `users`, status ENUM) |
| `project_members` | Członkostwo N:M użytkownik ↔ projekt |
| `categories` | Kategorie zadań (nazwa, kolor) |
| `tasks` | Zadania (`project_id`, `assignee_id`, `category_id`, status, priority) |
| `task_status_history` | Historia zmian statusu zadania |
| `activity_logs` | Log aktywności (JSON metadata) |

### Typy ENUM

- `task_status`: `todo`, `in_progress`, `done`
- `task_priority`: `low`, `medium`, `high`
- `project_status`: `active`, `on_hold`, `completed`

### Widoki i funkcje (skrót)

- **`view_project_progress`** — postęp projektów (JOIN `projects` + `users` + agregacja `tasks`)
- **`view_user_task_summary`** — podsumowanie zadań per użytkownik (LEFT JOIN)
- **`calculate_project_progress(project_id)`** — funkcja SQL zwracająca % ukończonych zadań

Diagram ERD (Mermaid): [`docs/erd.md`](docs/erd.md)  
Opcjonalny eksport PNG: [`docs/erd.png`](docs/erd.png)

---

## 8. Wymagania bazodanowe

Poniżej zestawienie wymagań akademickich względem faktycznej implementacji w `database/init.sql` (oraz migracji `003_database_requirements.sql`).

### Relacje

| Typ | Przykład w TaskFlow |
|-----|---------------------|
| **1:1** | `users` ↔ `user_profiles` (`user_id` PK/FK) |
| **1:N** | `users` → `projects` (`owner_id`); `projects` → `tasks` (`project_id`); `categories` → `tasks` |
| **N:M** | `projects` ↔ `users` przez `project_members` (`UNIQUE (project_id, user_id)`) |

### Widoki SQL

- [x] `view_project_progress` — `INNER JOIN users`, `LEFT JOIN tasks`, agregacja `COUNT`, `FILTER`
- [x] `view_user_task_summary` — `LEFT JOIN tasks` po `assignee_id`

### Funkcja SQL

- [x] `calculate_project_progress(p_project_id INT) RETURNS NUMERIC` — procent zadań ze statusem `done`

### Triggery

- [x] `update_tasks_updated_at` — `BEFORE UPDATE ON tasks` → ustawienie `updated_at`
- [x] `log_task_status_change` — `AFTER UPDATE ON tasks` → wpis do `task_status_history` przy zmianie statusu

### Transakcje

- [x] Bloki `BEGIN … END` w funkcjach i triggerach PL/pgSQL (atomowość operacji w triggerze)
- [ ] Jawne transakcje PDO (`beginTransaction` / `commit` / `rollBack`) w warstwie PHP — **do uzupełnienia**, jeśli wymagane przez regulamin jako osobny punkt w aplikacji

### JOIN

- [x] Zapytania z `INNER JOIN`, `LEFT JOIN` w widokach (`init.sql`)
- [x] Zapytania z JOIN w repozytoriach (np. `DashboardRepository`, `TaskRepository`, `ProjectRepository.findAccessibleForUser`)

### Postać normalna (3NF)

Projekt dąży do **3NF**:

- Dane o rolach w osobnej tabeli `roles` (brak powielania nazwy roli przy każdym użytkowniku)
- Profil użytkownika wydzielony do `user_profiles` (zależność 1:1 od klucza `user_id`)
- Kategorie jako słownik; zadania odwołują się przez `category_id`
- Brak przechowywania zdenormalizowanych agregatów w tabelach bazowych — postęp projektu liczony w widoku / funkcji

Szczegółowy opis zależności funkcjonalnych: [ ] dokument PDF/Markdown przy ERD — **do uzupełnienia w dokumentacji projektowej**

---

## 9. Instrukcja uruchomienia

### Wymagania

- [Docker](https://www.docker.com/) i Docker Compose (v2)
- Opcjonalnie: Git, Composer (lokalnie; można użyć kontenera `app`)

### Kroki

1. Sklonuj repozytorium i przejdź do katalogu projektu.

2. Skopiuj konfigurację środowiska:

   ```bash
   cp .env.example .env
   ```

   Domyślne wartości są zgodne z `docker-compose.yml` (baza: host `postgres`, port `5432`, baza `taskflow`).

3. Zbuduj i uruchom kontenery:

   ```bash
   docker compose up -d --build
   ```

   Przy **pierwszym** uruchomieniu PostgreSQL wykonuje skrypty z `database/init.sql` i `database/seed.sql` (wolumen `postgres_data`).

4. Zainstaluj zależności PHP (jeśli katalog `vendor/` nie istnieje):

   ```bash
   docker compose run --rm app composer install
   ```

5. Otwórz aplikację: **http://localhost:8080**

### Zatrzymanie

```bash
docker compose down
```

Usunięcie danych bazy (reset):

```bash
docker compose down -v
docker compose up -d --build
```

### Porty

| Usługa | Port na hoście |
|--------|----------------|
| Aplikacja (Apache) | 8080 |
| PostgreSQL | 5432 |

---

## 10. Konta testowe

Hasła są zahashowane w `seed.sql` (bcrypt). Po świeżym seedzie:

| E-mail | Hasło | Rola w systemie |
|--------|-------|-----------------|
| `admin@taskflow.local` | `admin123` | `admin` |
| `user@taskflow.local` | `user123` | `user` |

**Konto `pm@taskflow.local`:** **nie występuje** w `database/seed.sql`. Rola `project_manager` istnieje w tabeli `roles` — aby ją przetestować, utwórz użytkownika ręcznie lub zmień rolę istniejącego konta w panelu administratora.

Przykładowe dane demonstracyjne po seedzie: projekty „TaskFlow MVP”, „Demo”, przykładowe zadania i kategorie (Błąd, Funkcja, Dokumentacja).

---

## 11. Testy

### PHPUnit (testy jednostkowe serwisów)

Z kontenera aplikacji:

```bash
docker compose run --rm app composer test
```

Równoważnie:

```bash
docker compose run --rm app ./vendor/bin/phpunit
```

Pliki testów: `tests/AuthServiceTest.php`, `ProjectServiceTest.php`, `TaskServiceTest.php`, `UserServiceTest.php`.

### Test integracyjny endpointów

Przy działającej aplikacji (`docker compose up -d`):

```bash
docker compose exec app bash test-endpoints.sh
```

Skrypt sprawdza m.in.: `401` bez sesji, logowanie admina i usera, `403` dla usera na `/api/users` i `/users`, dostęp admina do chronionych endpointów.

---

## 12. Scenariusz testowy krok po kroku

Poniższy scenariusz można wykonać w przeglądarce (http://localhost:8080) lub częściowo przez API (np. DevTools / curl).

### A. Logowanie i sesja

1. Wejdź na `/login`.
2. Zaloguj się jako **admin** (`admin@taskflow.local` / `admin123`) — przekierowanie na dashboard, w menu widoczna pozycja „Użytkownicy”.
3. Wyloguj (przycisk w sidebarze) — powrót do stanu niezalogowanego.
4. Zaloguj się jako **user** (`user@taskflow.local` / `user123`) — brak pozycji „Użytkownicy” w menu.

### B. Role i odmowa dostępu

5. Jako **user** otwórz w przeglądarce `/users` — oczekiwana strona **403**.
6. Jako **user** wywołaj `GET /api/users` (np. DevTools → Network po wejściu na stronę, lub curl z ciasteczkiem sesji) — odpowiedź **403** JSON.
7. Wywołaj `GET /api/me` **bez** ciasteczka sesji — **401**.

### C. CRUD projektów

8. Jako **admin** przejdź do `/projects`, kliknij **Nowy projekt**, uzupełnij formularz, zapisz — projekt na liście.
9. Edytuj projekt (status, opis) — zapis przez API `PUT`.
10. Usuń projekt testowy — potwierdzenie w UI, `DELETE /api/projects/{id}`.
11. Jako **user** utwórz własny projekt — powinien się pojawić na liście z możliwością edycji (właściciel).
12. Jako **user** sprawdź, że przy projekcie należącym do admina (z seeda) **nie** ma przycisków Edytuj/Usuń (jeśli nie jesteś właścicielem).

### D. CRUD zadań

13. Przejdź do `/tasks` jako **admin** — widoczne zadania ze wszystkich projektów.
14. Utwórz zadanie: tytuł, projekt, opcjonalnie kategoria, assignee — `POST /api/tasks`.
15. Zmień status na `in_progress`, potem `done` — w bazie powinien powstać wpis w `task_status_history` (trigger).
16. Jako **user** zaloguj się ponownie — widać zadania przypisane do Jan Kowalski (seed); edycja statusu/opisu dozwolona, usuwanie zablokowane.

### E. Kategorie

17. Jako **admin** wejdź na `/categories` — dodaj, edytuj, usuń kategorię.
18. Jako **user** spróbuj utworzyć kategorię przez API — **403** / komunikat o braku uprawnień.

### F. Dashboard

19. Jako **admin** otwórz `/dashboard` — kafelki statystyk i lista postępu projektów (pasek z procentem).
20. Jako **user** — dashboard ograniczony do projektów, do których masz dostęp (właściciel lub `project_members`).

### G. Podsumowanie 403 / 401

21. Uruchom `docker compose exec app bash test-endpoints.sh` — wszystkie asercje `[PASS]`.
22. [ ] Scenariusz dla roli `project_manager` — **do uzupełnienia** po utworzeniu konta testowego PM.

---

## 13. Zrzuty ekranu

Umieść pliki w katalogu `docs/screenshots/` (obecnie katalog może być pusty — dodaj zrzuty przed oddaniem projektu):

| Plik | Opis |
|------|------|
| [`docs/screenshots/login.png`](docs/screenshots/login.png) | Strona logowania |
| [`docs/screenshots/dashboard-desktop.png`](docs/screenshots/dashboard-desktop.png) | Panel główny — widok desktop |
| [`docs/screenshots/projects-desktop.png`](docs/screenshots/projects-desktop.png) | Lista projektów — desktop |
| [`docs/screenshots/tasks-desktop.png`](docs/screenshots/tasks-desktop.png) | Lista zadań — desktop |
| [`docs/screenshots/dashboard-mobile.png`](docs/screenshots/dashboard-mobile.png) | Panel główny — widok mobilny |

Przykład w README (po dodaniu pliku):

```markdown
![Logowanie](docs/screenshots/login.png)
```

- [ ] Wszystkie wymagane screeny dodane do repozytorium

---

## 14. Diagramy

| Plik | Zawartość |
|------|-----------|
| [`docs/architecture.md`](docs/architecture.md) | Architektura MVC (diagram Mermaid: Browser → index.php → Router → Controller → Service → Repository → PostgreSQL; HTML + JSON) |
| [`docs/erd.md`](docs/erd.md) | Diagram ERD bazy danych (Mermaid, zgodny z `database/init.sql`) |
| [`docs/architecture.png`](docs/architecture.png) | Opcjonalny eksport diagramu architektury |
| [`docs/erd.png`](docs/erd.png) | Opcjonalny eksport diagramu ERD |

Diagramy w Markdown renderują się na GitHubie/GitLabie oraz w podglądzie IDE z obsługą Mermaid.

- [x] Diagram architektury w `docs/architecture.md`
- [x] Diagram ERD w `docs/erd.md`
- [ ] Opcjonalne pliki PNG (`docs/architecture.png`, `docs/erd.png`) — eksport z podglądu Mermaid

---

## 15. Checklista wymagań z regulaminu

> Oznaczenia: **[x]** — spełnione w repozytorium; **[ ]** — do uzupełnienia / weryfikacji z prowadzącym.  
> Jeśli regulamin przewiduje dodatkowe punkty, dopisz je na końcu listy.

### Aplikacja i kod

- [x] PHP 8.2+, programowanie obiektowe
- [x] Architektura MVC bez frameworków PHP
- [x] Własny router i warstwa HTTP (`app/Core`)
- [x] Front-end: HTML5, CSS, JavaScript (Fetch API), bez frameworków UI
- [x] REST API JSON pod `/api/...`
- [x] Uwierzytelnianie i sesje użytkownika
- [x] System ról (`admin`, `project_manager`, `user`)
- [x] CRUD projektów, zadań, kategorii (z ograniczeniami ról)
- [x] Panel dashboard ze statystykami
- [x] Obsługa błędów HTTP (401, 403, 404, 500) — HTML i JSON
- [x] Repozytorium Git z historią zmian
- [ ] Pełna dokumentacja użytkownika / instrukcja PDF — **do uzupełnienia**, jeśli wymagana regulaminem

### Baza danych

- [x] PostgreSQL
- [x] Relacja 1:1 (`user_profiles`)
- [x] Relacje 1:N (np. `projects` → `tasks`)
- [x] Relacja N:M (`project_members`)
- [x] Co najmniej jeden widok SQL (`view_project_progress`, `view_user_task_summary`)
- [x] Funkcja SQL (`calculate_project_progress`)
- [x] Triggery na tabeli `tasks`
- [x] Zapytania z JOIN
- [x] Projekt schematu zgodny z 3NF (opis / diagram — patrz sekcja 8)
- [ ] Jawne transakcje w kodzie PHP (PDO) — **do uzupełnienia**, jeśli wymagane osobno od triggerów

### Środowisko i jakość

- [x] Docker Compose (`app` + `postgres`)
- [x] Plik `.env.example` i instrukcja uruchomienia w README
- [x] Testy PHPUnit (`composer test`)
- [x] Skrypt testowy endpointów (`test-endpoints.sh`)
- [x] Dane testowe (`database/seed.sql`)

### Dokumentacja i zaliczanie

- [x] README.md po polsku z opisem projektu
- [ ] Zrzuty ekranu w `docs/screenshots/` (wszystkie wymagane pliki)
- [x] Diagram ERD (`docs/erd.md`)
- [x] Diagram architektury (`docs/architecture.md`)
- [ ] Opcjonalny eksport PNG diagramów
- [ ] Konto testowe `project_manager` (np. `pm@taskflow.local`) — **brak w seedzie**
- [ ] Punkty specyficzne regulaminu przedmiotu — **uzupełnij według aktualnego regulaminu**

---

## Autor

**Mateusz Więcek**

---

## Licencja

Projekt udostępniony na licencji **MIT** (patrz `composer.json`).
