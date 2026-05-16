# TaskFlow

Aplikacja webowa do zarządzania projektami i zadaniami, zbudowana na potrzeby projektu z przedmiotu.

## Stack technologiczny

- **PHP 8.2** – programowanie obiektowe, bez frameworków (Laravel, Symfony itd.)
- **Architektura MVC** – własny router, kontrolery, modele, repozytoria i serwisy
- **PostgreSQL** – baza danych (PDO)
- **Docker Compose** – kontener `php-apache` + `postgres`
- **Front-end** – HTML5, własny CSS, JavaScript (Fetch API), bez Bootstrap/Tailwind/React/Vue

## Przygotowanie pod funkcje

Struktura projektu jest przygotowana pod:

- logowanie i sesje użytkowników
- role (`user`, `admin`)
- projekty, zadania i kategorie
- panel administratora

Na tym etapie działa szkielet aplikacji (routing, widoki, połączenie z bazą) – pełna logika biznesowa zostanie dodana w kolejnych krokach.

## Uruchomienie

### Wymagania

- [Docker](https://www.docker.com/) i Docker Compose

### Kroki

1. Sklonuj repozytorium i przejdź do katalogu projektu.

2. Skopiuj plik konfiguracyjny środowiska:

   ```bash
   cp .env.example .env
   ```

3. Zainstaluj zależności PHP (lokalnie lub w kontenerze):

   ```bash
   composer install
   ```

   Jeśli nie masz Composera na hoście (np. Windows):

   ```bash
   docker compose run --rm app composer install
   ```

4. Uruchom kontenery:

   ```bash
   docker compose up -d --build
   ```

5. Otwórz aplikację w przeglądarce:

   **http://localhost:8080**

### Zatrzymanie

```bash
docker compose down
```

Aby usunąć także wolumen bazy danych:

```bash
docker compose down -v
```

## Struktura katalogów

```
app/           – logika aplikacji (Controllers, Core, Models, Repositories, Services, Middleware)
config/        – konfiguracja (odczyt zmiennych z .env)
database/      – init.sql (schemat), seed.sql (dane testowe)
public/        – document root (index.php, assets)
views/         – szablony PHP (bez gotowych frameworków UI)
tests/         – testy PHPUnit (serwisy)
```

## Konta testowe (po seed)

| E-mail                 | Hasło    | Rola  |
|------------------------|----------|-------|
| admin@taskflow.local   | admin123 | admin |
| user@taskflow.local    | user123  | user  |

## Testy

```bash
composer install
./vendor/bin/phpunit
```

## Sprawdzenie endpointów

```bash
bash test-endpoints.sh
```

(lub w Git Bash / WSL na Windows)

## Licencja

MIT
