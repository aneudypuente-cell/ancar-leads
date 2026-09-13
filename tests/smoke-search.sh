#!/usr/bin/env bash
set -euo pipefail

PORT=8089
php -S 127.0.0.1:${PORT} -t . >/tmp/ancar-leads-php.log 2>&1 &
PID=$!
trap 'kill ${PID} >/dev/null 2>&1 || true' EXIT
sleep 1

response=$(curl -sS -i -X POST \
  -H 'Content-Type: application/json' \
  --data '{}' \
  "http://127.0.0.1:${PORT}/api/search.php")

printf '%s\n' "$response" | grep -q 'HTTP/1.1 400'
printf '%s\n' "$response" | grep -q 'query required'

echo 'Leads 2 search endpoint smoke test: PASS'
