#!/usr/bin/env bash
set -euo pipefail

# =====================================================
# Configuration
# =====================================================
AS400_HOST="192.168.17.22"
AS400_USER="LEDUR"
REMOTE_ROOT="/www/apis"

LOCAL_ROOT="/Users/frichard/Développement/as400-apis"

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
ssh "${AS400_USER}@${AS400_HOST}" "mkdir -p '${REMOTE_ROOT}/app' '${REMOTE_ROOT}/htdocs' '${REMOTE_ROOT}/python'"

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

  rsync -avz --delete \
    --exclude='.backup/' \
    --exclude='.DS_Store' \
    --exclude='**/.DS_Store' \
    --exclude='.env' \
    --exclude='conf/httpd.conf' \
    --exclude='conf/httpd.aslilas.conf' \
    --exclude='conf/httpd.ordimajor.conf' \
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

ssh "${AS400_USER}@${AS400_HOST}" "chmod -R o+rX ${REMOTE_ROOT}"

echo "=================================================="
echo "✅ Synchronisation et permissions terminées"
echo "=================================================="

echo "=================================================="
echo "✅ Synchronisation terminée"
echo "=================================================="
