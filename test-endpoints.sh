#!/usr/bin/env bash
# TaskFlow – testy integracyjne endpointów API i uprawnień

set -euo pipefail

resolve_base_url() {
    if [[ -n "${BASE_URL:-}" ]]; then
        echo "$BASE_URL"
        return
    fi

    for url in "http://localhost:8080" "http://localhost"; do
        local code
        code=$(curl -s -o /dev/null -w "%{http_code}" "${url}/api/me" 2>/dev/null || true)
        if [[ "$code" =~ ^[0-9]+$ && "$code" != "000" ]]; then
            echo "$url"
            return
        fi
    done

    echo "http://localhost:8080"
}

BASE_URL="$(resolve_base_url)"
COOKIE_JAR="$(mktemp)"
ADMIN_BODY="$(mktemp)"
USER_BODY="$(mktemp)"
trap 'rm -f "$COOKIE_JAR" "$ADMIN_BODY" "$USER_BODY" "${USER_COOKIE:-}"' EXIT

PASS=0
FAIL=0

assert_status() {
    local name="$1"
    local expected="$2"
    local actual="$3"

    if [[ "$actual" == "$expected" ]]; then
        echo "[PASS] ${name} (HTTP ${actual})"
        PASS=$((PASS + 1))
    else
        echo "[FAIL] ${name} (HTTP ${actual}, oczekiwano ${expected})"
        FAIL=$((FAIL + 1))
    fi
}

echo "TaskFlow – test endpointów (${BASE_URL})"
echo "========================================"

# --- Bez sesji ---
status=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/api/me" || true)
assert_status "GET /api/me bez logowania" "401" "$status"

status=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/api/nieistniejacy" || true)
assert_status "GET /api/nieistniejacy" "404" "$status"

status=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/nieistniejaca-strona" || true)
assert_status "GET /nieistniejaca-strona (HTML)" "404" "$status"

# --- Logowanie admina ---
status=$(curl -s -o "$ADMIN_BODY" -w "%{http_code}" -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
    -X POST "${BASE_URL}/api/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"admin@taskflow.local","password":"admin123"}' || true)
assert_status "POST /api/login (admin)" "200" "$status"

if grep -q '"role":"admin"' "$ADMIN_BODY" 2>/dev/null || grep -q '"role": "admin"' "$ADMIN_BODY" 2>/dev/null; then
    echo "[PASS] POST /api/login zwraca użytkownika admin"
    PASS=$((PASS + 1))
else
    echo "[FAIL] POST /api/login – brak roli admin w odpowiedzi"
    FAIL=$((FAIL + 1))
fi

# --- Admin: API chronione ---
status=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    "${BASE_URL}/api/me" -H "Accept: application/json" || true)
assert_status "GET /api/me (admin)" "200" "$status"

status=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    "${BASE_URL}/api/dashboard" -H "Accept: application/json" || true)
assert_status "GET /api/dashboard (admin)" "200" "$status"

status=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    "${BASE_URL}/api/projects" -H "Accept: application/json" || true)
assert_status "GET /api/projects (admin)" "200" "$status"

status=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    "${BASE_URL}/api/tasks" -H "Accept: application/json" || true)
assert_status "GET /api/tasks (admin)" "200" "$status"

status=$(curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    "${BASE_URL}/api/users" -H "Accept: application/json" || true)
assert_status "GET /api/users (admin)" "200" "$status"

# --- Logowanie usera ---
USER_COOKIE="$(mktemp)"

status=$(curl -s -o "$USER_BODY" -w "%{http_code}" -c "$USER_COOKIE" -b "$USER_COOKIE" \
    -X POST "${BASE_URL}/api/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"user@taskflow.local","password":"user123"}' || true)
assert_status "POST /api/login (user)" "200" "$status"

status=$(curl -s -o /dev/null -w "%{http_code}" -b "$USER_COOKIE" -c "$USER_COOKIE" \
    "${BASE_URL}/api/users" -H "Accept: application/json" || true)
assert_status "GET /api/users (user – brak uprawnień)" "403" "$status"

status=$(curl -s -o /dev/null -w "%{http_code}" -b "$USER_COOKIE" -c "$USER_COOKIE" \
    "${BASE_URL}/users" -H "Accept: text/html" || true)
assert_status "GET /users (user – brak uprawnień, HTML)" "403" "$status"

# --- Podsumowanie ---
echo "----------------------------------------"
echo "Wynik: ${PASS} zaliczonych, ${FAIL} niezaliczonych"

if [[ "$FAIL" -gt 0 ]]; then
    exit 1
fi

exit 0
