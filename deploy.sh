#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
WWW_USER="${WWW_USER:-www-data}"
CRON_USER="${CRON_USER:-}"
SEED_DEMO="${SEED_DEMO:-0}"
SKIP_ASSETS="${SKIP_ASSETS:-0}"
SKIP_CRON="${SKIP_CRON:-0}"
SET_OWNERSHIP="${SET_OWNERSHIP:-1}"

cd "$APP_DIR"

if [ ! -f ".env" ]; then
  cp .env.example .env
  echo "Created .env from .env.example."
  echo "Update your production values in .env, then rerun ./deploy.sh"
  exit 1
fi

echo "Installing PHP dependencies..."
"$COMPOSER_BIN" install --no-dev --optimize-autoloader

if [ "$SKIP_ASSETS" != "1" ] && command -v "$NPM_BIN" >/dev/null 2>&1; then
  echo "Installing frontend dependencies..."
  "$NPM_BIN" ci
  echo "Building frontend assets..."
  "$NPM_BIN" run build
else
  echo "Skipping frontend asset build."
fi

echo "Running COINGROW installer..."
INSTALL_ARGS=(artisan coingrow:install --force)
if [ "$SEED_DEMO" = "1" ]; then
  INSTALL_ARGS+=(--seed-demo)
fi
if [ "$SKIP_ASSETS" = "1" ]; then
  INSTALL_ARGS+=(--skip-assets)
fi
"$PHP_BIN" "${INSTALL_ARGS[@]}"

echo "Setting writable permissions..."
mkdir -p storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
find storage bootstrap/cache -type f -exec chmod 664 {} \;
find storage bootstrap/cache -type d -exec chmod 775 {} \;

if [ "$SET_OWNERSHIP" = "1" ] && [ "$(id -u)" -eq 0 ]; then
  echo "Setting ownership to ${WWW_USER}:${WWW_USER}..."
  chown -R "$WWW_USER:$WWW_USER" storage bootstrap/cache public/storage
fi

if [ "$SKIP_CRON" != "1" ]; then
  CRON_CMD="* * * * * cd $APP_DIR && $PHP_BIN artisan schedule:run >> /dev/null 2>&1"
  TMP_CRON="$(mktemp)"

  if [ -n "$CRON_USER" ] && [ "$(id -u)" -eq 0 ]; then
    crontab -u "$CRON_USER" -l 2>/dev/null | grep -Fv "artisan schedule:run" > "$TMP_CRON" || true
    echo "$CRON_CMD" >> "$TMP_CRON"
    crontab -u "$CRON_USER" "$TMP_CRON"
    echo "Scheduler cron installed for user $CRON_USER."
  else
    crontab -l 2>/dev/null | grep -Fv "artisan schedule:run" > "$TMP_CRON" || true
    echo "$CRON_CMD" >> "$TMP_CRON"
    crontab "$TMP_CRON"
    echo "Scheduler cron installed for current user."
  fi

  rm -f "$TMP_CRON"
else
  echo "Skipping cron configuration."
fi

echo "Deployment complete."
echo "Nginx config template: deploy/nginx/coingrow.conf"
echo "Apache vhost template: deploy/apache/coingrow-vhost.conf"
