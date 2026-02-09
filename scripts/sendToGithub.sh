#!/usr/bin/env bash
set -euo pipefail

# Usage:
#   ./scripts/sendToGithub.sh [commit_message] [branch] [remote]
#
# Exemples:
#   ./scripts/sendToGithub.sh
#   ./scripts/sendToGithub.sh "feat: update routes"
#   ./scripts/sendToGithub.sh "chore: fin de journee" main origin

REMOTE="${3:-origin}"
MSG="${1:-chore: update $(date '+%Y-%m-%d %H:%M')}"

if ! command -v git >/dev/null 2>&1; then
  echo "❌ git introuvable"
  exit 1
fi

if ! ROOT="$(git rev-parse --show-toplevel 2>/dev/null)"; then
  echo "❌ Ce dossier n'est pas un depot git."
  echo "   Initialise d'abord: git init -b main"
  exit 1
fi

cd "$ROOT"

if ! git rev-parse --verify HEAD >/dev/null 2>&1; then
  echo "❌ Aucun commit detecte."
  echo "   Fais un premier commit avant d'utiliser ce script."
  exit 1
fi

if [[ -n "$(git diff --name-only --diff-filter=U)" ]]; then
  echo "❌ Conflits Git detectes. Resolus-les avant de continuer."
  exit 1
fi

if ! git remote get-url "$REMOTE" >/dev/null 2>&1; then
  echo "❌ Remote '$REMOTE' introuvable."
  echo "   Ajoute-le avec: git remote add $REMOTE <url>"
  exit 1
fi

if [[ -n "${2:-}" ]]; then
  BRANCH="$2"
else
  BRANCH="$(git symbolic-ref --quiet --short HEAD || true)"
fi

if [[ -z "$BRANCH" ]]; then
  echo "❌ Branche courante introuvable (HEAD detachee)."
  echo "   Passe la branche en parametre 2."
  exit 1
fi

echo "=================================================="
echo "📦 Envoi GitHub"
echo "Repo   : $ROOT"
echo "Remote : $REMOTE"
echo "Branch : $BRANCH"
echo "=================================================="

echo "➡️  git add -A"
git add -A

if git diff --cached --name-only | grep -Eq '(^|/)\.env($|[.])'; then
  echo "❌ Fichier .env detecte dans le commit. Commit annule."
  echo "   Retire-le avec: git restore --staged .env"
  exit 1
fi

if git diff --cached --quiet; then
  echo "ℹ️  Aucun changement a committer."
else
  echo "➡️  git commit -m \"$MSG\""
  git commit -m "$MSG"
fi

echo "➡️  git pull --rebase $REMOTE $BRANCH"
git pull --rebase "$REMOTE" "$BRANCH"

echo "➡️  git push $REMOTE $BRANCH"
git push "$REMOTE" "$BRANCH"

echo "=================================================="
echo "✅ Envoi termine"
echo "=================================================="
