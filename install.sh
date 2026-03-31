#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

if [ ! -f ".env" ]; then
  cp .env.example .env
  echo "Created .env from .env.example."
  echo "Update your production values in .env, then rerun ./install.sh"
  exit 1
fi

echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

if command -v npm >/dev/null 2>&1; then
  echo "Installing frontend dependencies..."
  npm ci
  echo "Building frontend assets..."
  npm run build
else
  echo "npm not found. Skipping frontend asset build."
fi

echo "Running COINGROW installer..."
php artisan coingrow:install --force "$@"

echo "COINGROW server installation finished."
