#!/usr/bin/env bash
set -euo pipefail

# =====================================================
# Configuration
# =====================================================
AS400_HOST="192.168.17.21"
AS400_USER="LEDUR"
REMOTE_ROOT="/www/apis"

LOCAL_ROOT="/Users/frichard/Développement/as400-apis"

# Optionnel : renseigner AS400_PASS (ex. via export/direnv) + installer sshpass
# pour éviter de ressaisir le mot de passe. Préférez les clés SSH si possible.
AS400_PASS="${AS400_PASS:-}"
USE_SSHPASS=0
if [[ -n "$AS400_PASS" ]]; then
  if command -v sshpass >/dev/null 2>&1; then
    USE_SSHPASS=1
  else
    echo "⚠️  AS400_PASS défini mais sshpass introuvable. Installez-le (brew install hudochenkov/sshpass/sshpass) ou utilisez une clé SSH."
  fi
fi

SSH_CMD=(ssh)
RSYNC_CMD=(rsync)
if [[ $USE_SSHPASS -eq 1 ]]; then
  SSH_CMD=(sshpass -p "$AS400_PASS" ssh)
  RSYNC_CMD=(sshpass -p "$AS400_PASS" rsync)
fi

ssh_run() { "${SSH_CMD[@]}" "$@"; }

# Répertoires à synchroniser
DIRS=("app" "htdocs" "python")

# =====================================================
# Vérifications
# =====================================================
command -v rsync >/dev/null 2>&1 || { echo "❌ rsync introuvable"; exit 1; }
command -v ssh   >/dev/null 2>&1 || { echo "❌ ssh introuvable"; exit 1; }

[[ -d "$LOCAL_ROOT" ]] || { echo "❌ Dossier local introuvable: $LOCAL_ROOT"; exit 1; }

echo "=================================================="
echo "📤 Synchronisation vers IBM i"
echo "Host : $AS400_USER@$AS400_HOST"
echo "To   : $REMOTE_ROOT"
echo "From : $LOCAL_ROOT"
echo "Dirs : ${DIRS[*]}"
echo "=================================================="

# (Optionnel mais pratique) : s'assurer que les dossiers distants existent
ssh_run "${AS400_USER}@${AS400_HOST}" "mkdir -p '${REMOTE_ROOT}/app' '${REMOTE_ROOT}/htdocs' '${REMOTE_ROOT}/python'"

for d in "${DIRS[@]}"; do
  local_dir="${LOCAL_ROOT}/${d}"
  remote_dir="${REMOTE_ROOT}/${d}"

  if [[ ! -d "$local_dir" ]]; then
    echo "⚠️  Dossier local absent, ignoré: $local_dir"
    continue
  fi

  echo "--------------------------------------------------"
  echo "➡️  rsync ${d}/  ->  ${AS400_HOST}:${remote_dir}/"
  echo "--------------------------------------------------"

  "${RSYNC_CMD[@]}" -avz --delete \
      --exclude='.backup/' \
      --exclude='.DS_Store' \
      --exclude='**/.DS_Store' \
      --exclude='.env' \
      --exclude='conf/httpd.conf' \
      -e "ssh" \
      "${local_dir}/" \
      "${AS400_USER}@${AS400_HOST}:${remote_dir}/"

  echo "✅ ${d} synchronisé"
done

# =====================================================
# Permissions IFS pour Apache
# =====================================================
echo "--------------------------------------------------"
echo "🔐 Mise à jour des permissions IFS pour Apache"
echo "--------------------------------------------------"

ssh_run "${AS400_USER}@${AS400_HOST}" "chmod -R o+rX ${REMOTE_ROOT}"

echo "=================================================="
echo "✅ Synchronisation et permissions terminées"
echo "=================================================="

echo "=================================================="
echo "✅ Synchronisation terminée"
echo "=================================================="
