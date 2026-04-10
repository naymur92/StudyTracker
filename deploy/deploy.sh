#!/bin/bash
# ============================================================
# Deploy Script — Study Tracker
# Run on EC2 for each deployment: sudo bash deploy/deploy.sh
# With custom repo: sudo bash deploy/deploy.sh REPO_URL BRANCH
# CI/CD mode: CI=true bash deploy/deploy.sh
# ============================================================
set -euo pipefail

# CI/CD mode - provides better logging for GitHub Actions
CI_MODE="${CI:-false}"

PROJECT_NAME="study-tracker"
PROJECT_DIR="/var/www/${PROJECT_NAME}"
REPO_URL="${1:-https://github.com/naymur92/StudyTracker.git}"
BRANCH="${2:-main}"

echo "=========================================="
echo " Deploying: ${PROJECT_NAME}"
echo "=========================================="

echo "Using PHP: $(php -v | head -n 1)"

# ── 1. Get code ──────────────────────────────
if [ ! -d "$PROJECT_DIR/.git" ]; then
    echo "[1/10] Cloning repository..."
    git clone -b "$BRANCH" "$REPO_URL" "$PROJECT_DIR"
else
    echo "[1/10] Pulling latest changes..."
    cd "$PROJECT_DIR"
    git fetch origin
    git reset --hard "origin/${BRANCH}"
fi

cd "$PROJECT_DIR"

# ── 2. Install PHP dependencies ──────────────
echo "[2/10] Installing Composer dependencies..."
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction

# ── 3. Build frontend ────────────────────────
echo "[3/10] Building frontend assets..."
npm ci --ignore-scripts
npm run build

# ── 4. Environment setup (first deploy) ──────
if [ ! -f .env ]; then
    echo "[4/10] Creating .env from template..."
    cp deploy/.env.production .env 2>/dev/null || cp .env.example .env
    php artisan key:generate --force
    echo ""
    echo "  ╔══════════════════════════════════════════════╗"
    echo "  ║  STOP: Edit .env with production values!     ║"
    echo "  ║  Then re-run this script.                    ║"
    echo "  ╚══════════════════════════════════════════════╝"
    exit 0
else
    echo "[4/10] .env exists, skipping."
fi

# ── 5. Create directories ────────────────────
echo "[5/10] Ensuring directories exist..."
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p public/uploads

# ── 6. Passport keys ─────────────────────────
if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    echo "[6/10] Generating Passport keys..."
    php artisan passport:keys --force --no-interaction
else
    echo "[6/10] Passport keys exist, skipping."
fi

# ── 7. Permissions ───────────────────────────
echo "[7/10] Setting file permissions..."
chown -R www-data:www-data "$PROJECT_DIR"
find "$PROJECT_DIR" -type d -exec chmod 755 {} \;
find "$PROJECT_DIR" -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache public/uploads
chmod +x artisan
[ -f storage/oauth-private.key ] && chmod 600 storage/oauth-private.key
[ -f storage/oauth-public.key ] && chmod 600 storage/oauth-public.key

# ── 8. Database ──────────────────────────────
echo "[8/10] Running migrations..."
php artisan migrate --force --no-interaction

# ── 9. Laravel caching ───────────────────────
echo "[9/10] Caching config/routes/views..."
php artisan storage:link --force 2>/dev/null || true
php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true
php artisan event:cache || true

# ── 10. Restart workers ──────────────────────
echo "[10/10] Restarting queue workers..."
php artisan queue:restart
supervisorctl restart "${PROJECT_NAME}-worker:*" 2>/dev/null || true

echo ""
echo "=========================================="
echo " Deployment complete!"
APP_URL=$(grep '^APP_URL=' .env | cut -d= -f2)
echo " Site: ${APP_URL}"
echo "=========================================="

# CI/CD summary
if [ "$CI_MODE" = "true" ]; then
    echo ""
    echo "CI/CD Summary:"
    echo "  PHP Version: $(php -r 'echo PHP_VERSION;')"
    echo "  Disk Usage:"
    du -sh . 2>/dev/null || echo "    (unable to determine)"
    echo "  Git SHA: $(git rev-parse HEAD 2>/dev/null || echo 'unknown')"
fi
