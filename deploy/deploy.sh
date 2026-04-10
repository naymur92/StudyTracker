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

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="${PROJECT_DIR:-$(cd "${SCRIPT_DIR}/.." && pwd)}"
PROJECT_NAME="${PROJECT_NAME:-$(basename "$PROJECT_DIR")}"

# Fallback repo URL for first-time clone; prefer existing origin if available.
if git -C "$PROJECT_DIR" remote get-url origin >/dev/null 2>&1; then
    DEFAULT_REPO_URL="$(git -C "$PROJECT_DIR" remote get-url origin)"
else
    DEFAULT_REPO_URL="https://github.com/naymur92/StudyTracker.git"
fi
REPO_URL="${1:-$DEFAULT_REPO_URL}"
BRANCH="${2:-main}"

echo "=========================================="
echo " Deploying: ${PROJECT_NAME}"
echo " Directory: ${PROJECT_DIR}"
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
# Set key permissions immediately after generation (script runs as root;
# keys are readable only by www-data, never world-readable).
if [ -f storage/oauth-private.key ] && [ -f storage/oauth-public.key ]; then
    if [ "$(id -u)" -eq 0 ]; then
        chown www-data:www-data storage/oauth-private.key storage/oauth-public.key
        chmod 600 storage/oauth-private.key
        chmod 640 storage/oauth-public.key
    elif command -v sudo >/dev/null 2>&1; then
        sudo chown www-data:www-data storage/oauth-private.key storage/oauth-public.key
        sudo chmod 600 storage/oauth-private.key
        sudo chmod 640 storage/oauth-public.key
    else
        echo "ERROR: Cannot set OAuth key owner/perms (need root or sudo)."
        exit 1
    fi
fi

# ── 7. Permissions ───────────────────────────
echo "[7/10] Setting file permissions..."
# Change ownership only for runtime-writable paths.
# On shared servers, SSH deploy users often cannot chown the whole repo tree.
chown -R www-data:www-data storage bootstrap/cache public/uploads 2>/dev/null || true

# Writable directories keep group-write (storage, cache, uploads)
find storage bootstrap/cache public/uploads -type d -exec chmod 775 {} \;

# Writable files under storage (logs, sessions, cache files): group can write
find storage -type f \
    ! -name 'oauth-private.key' \
    ! -name 'oauth-public.key' \
    -exec chmod 664 {} \;

# Other upload/public files can be readable by all.
find public/uploads -type f -exec chmod 664 {} \; 2>/dev/null || true

chmod +x artisan

# Key files: set last — nothing above can override them
if [ "$(id -u)" -eq 0 ]; then
    chown www-data:www-data storage/oauth-private.key storage/oauth-public.key
    chmod 600 storage/oauth-private.key
    chmod 640 storage/oauth-public.key
elif command -v sudo >/dev/null 2>&1; then
    sudo chown www-data:www-data storage/oauth-private.key storage/oauth-public.key
    sudo chmod 600 storage/oauth-private.key
    sudo chmod 640 storage/oauth-public.key
else
    echo "ERROR: Cannot enforce OAuth key owner/perms at end of deploy (need root or sudo)."
    exit 1
fi

# Verification: app user must be able to read keys
if command -v sudo >/dev/null 2>&1; then
    sudo -u www-data test -r storage/oauth-private.key || {
        echo "ERROR: www-data cannot read storage/oauth-private.key"
        exit 1
    }
    sudo -u www-data test -r storage/oauth-public.key || {
        echo "ERROR: www-data cannot read storage/oauth-public.key"
        exit 1
    }
fi

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
