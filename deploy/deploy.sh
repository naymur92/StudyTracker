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
    echo "[1/11] Cloning repository..."
    git clone -b "$BRANCH" "$REPO_URL" "$PROJECT_DIR"
else
    echo "[1/11] Pulling latest changes..."
    cd "$PROJECT_DIR"
    git fetch origin
    git reset --hard "origin/${BRANCH}"
fi

cd "$PROJECT_DIR"

# ── 2. Install PHP dependencies ──────────────
echo "[2/11] Installing Composer dependencies..."
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction

# ── 3. Build frontend ────────────────────────
echo "[3/11] Building frontend assets..."
npm ci --ignore-scripts
npm run build

# ── 4. Environment setup (first deploy) ──────
if [ ! -f .env ]; then
    echo "[4/11] Creating .env from template..."
    cp deploy/.env.production .env 2>/dev/null || cp .env.example .env
    php artisan key:generate --force
    echo ""
    echo "  ╔══════════════════════════════════════════════╗"
    echo "  ║  STOP: Edit .env with production values!     ║"
    echo "  ║  Then re-run this script.                    ║"
    echo "  ╚══════════════════════════════════════════════╝"
    exit 0
else
    echo "[4/11] .env exists, skipping."
fi

# ── 5. Create directories ────────────────────
echo "[5/11] Ensuring directories exist..."
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p public/uploads

# App root must be traversable by the PHP-FPM user so Passport can read keys
# from storage/oauth-*.key through the full path.
chmod 755 "$PROJECT_DIR"
chmod 755 storage bootstrap public

# ── 6. Passport keys ─────────────────────────
if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    echo "[6/11] Generating Passport keys..."
    php artisan passport:keys --force --no-interaction
else
    echo "[6/11] Passport keys exist, skipping."
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
echo "[7/11] Setting file permissions..."
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
echo "[8/11] Running migrations..."
php artisan migrate --force --no-interaction

# ── 9. Laravel caching ───────────────────────
echo "[9/11] Caching config/routes/views..."
php artisan storage:link --force 2>/dev/null || true
php artisan optimize:clear
php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true
php artisan event:cache || true

# ── 10. Restart workers ──────────────────────
echo "[10/11] Restarting queue workers..."
php artisan queue:restart
supervisorctl restart "${PROJECT_NAME}-worker:*" 2>/dev/null || true

# OPcache runs with validate_timestamps=0 (see ec2-setup.sh), so PHP-FPM keeps
# serving the previous release's code until it is reloaded.
echo "Reloading PHP-FPM to flush OPcache..."
if [ "$(id -u)" -eq 0 ]; then
    systemctl reload php8.4-fpm
elif command -v sudo >/dev/null 2>&1; then
    sudo systemctl reload php8.4-fpm
else
    echo "WARNING: Cannot reload php8.4-fpm (need root or sudo); stale code may be served."
fi

# ── 11. Cleanup ──────────────────────────────
# node_modules is only needed for the frontend build (step 3) and takes
# heavy storage on the server. The next deploy's `npm ci` recreates it.
echo "[11/11] Removing node_modules to free disk space..."
rm -rf "$PROJECT_DIR/node_modules"

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
