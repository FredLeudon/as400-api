#!/usr/bin/env bash
set -euo pipefail

# =====================================================
# Configuration
# =====================================================
AS400_HOST="aslilas.matfer.fr"
AS400_USER="LEDUR"

REMOTE_DIR="/www/apis/"

LOCAL_DIR="/Users/frichard/Développement/as400-apis"
LOCAL_HTTPD_CONF="$LOCAL_DIR/conf/httpd.conf"

DOCROOT="$LOCAL_DIR/htdocs"
ROUTER="$DOCROOT/routes.php"
PHP_PORT=8080

# =====================================================
# Vérifications
# =====================================================
command -v rsync >/dev/null 2>&1 || { echo "❌ rsync introuvable"; exit 1; }
command -v php   >/dev/null 2>&1 || { echo "❌ php introuvable"; exit 1; }
command -v ssh   >/dev/null 2>&1 || { echo "❌ ssh introuvable"; exit 1; }

[[ -d "$LOCAL_DIR" ]] || { echo "❌ Dossier local introuvable: $LOCAL_DIR"; exit 1; }

# =====================================================
# RSYNC depuis IBM i
# =====================================================
echo "=================================================="
echo "📥 Synchronisation depuis IBM i"
echo "Host : $AS400_USER@$AS400_HOST"
echo "From : $REMOTE_DIR"
echo "To   : $LOCAL_DIR"
echo "=================================================="

rsync -avz --delete \
  --exclude='.backup/' \
  --exclude='conf/httpd.conf' \
  --exclude='check.sh' \
  --exclude='.env' \
  --exclude='scripts/' \
  --exclude='.DS_Store' \
  -e "ssh" \
  "${AS400_USER}@${AS400_HOST}:${REMOTE_DIR}" \
  "${LOCAL_DIR}/"

echo "✅ Synchronisation terminée"
