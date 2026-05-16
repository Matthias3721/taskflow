#!/usr/bin/env bash
# Podstawowe sprawdzenie dostępności stron TaskFlow

BASE_URL="${BASE_URL:-http://localhost:8080}"

routes=(
    "/"
    "/login"
    "/register"
    "/projects"
    "/tasks"
    "/users"
    "/nieistniejaca-strona"
)

echo "TaskFlow – test endpointów (${BASE_URL})"
echo "----------------------------------------"

for route in "${routes[@]}"; do
    status=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}${route}")
    if [[ "$route" == "/nieistniejaca-strona" ]]; then
        expected="404"
    else
        expected="200"
    fi
    if [[ "$status" == "$expected" ]]; then
        echo "[OK] ${route} -> ${status}"
    else
        echo "[FAIL] ${route} -> ${status} (oczekiwano ${expected})"
    fi
done
