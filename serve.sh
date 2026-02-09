#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCROOT="${DOCROOT:-$ROOT/htdocs}"

ROUTER=""
if [[ -f "$ROOT/route.php" ]]; then
  ROUTER="$ROOT/route.php"
elif [[ -f "$DOCROOT/route.php" ]]; then
  ROUTER="$DOCROOT/route.php"
elif [[ -f "$DOCROOT/routes.php" ]]; then
  ROUTER="$DOCROOT/routes.php"
else
  echo "Router not found. Expected route.php or routes.php under $ROOT or $DOCROOT" >&2
  exit 1
fi

HOST="${HOST:-127.0.0.1}"
PORT="${PORT:-8080}"

exec php -S "${HOST}:${PORT}" -t "$DOCROOT" "$ROUTER"
