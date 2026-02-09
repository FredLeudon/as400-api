#!/usr/bin/env bash
set -u

PHP="/opt/homebrew/opt/php@8.4/bin/php"

GREEN="$(printf '\033[0;32m')"
RED="$(printf '\033[0;31m')"
RESET="$(printf '\033[0m')"

# Parcours récursif des fichiers PHP
# - exclusion du dossier .backup
find "$(pwd)" \
  -type d -name ".backup" -prune -o \
  -type f -name "*.php" -print0 |
while IFS= read -r -d '' file; do
  out="$("$PHP" -n -l "$file" 2>&1)"
  status=$?

  if [ $status -eq 0 ]; then
    printf "%s  %bOK%b\n" "$file" "$GREEN" "$RESET"
  else
    printf "%s  %bKO%b\n" "$file" "$RED" "$RESET"
    printf "%s\n" "$out" | sed 's/^/  /'
  fi
done
