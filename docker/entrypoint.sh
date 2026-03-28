#!/bin/bash
set -e

# ─────────────────────────────────────────────────────────
# StudyTracker – Docker entrypoint
# Runs before php-fpm starts:
#   1. Wait for MySQL to be reachable
#   2. Run pending migrations
#   3. Cache config / routes / views for performance
#   4. Create storage symlink
# ─────────────────────────────────────────────────────────

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-study_tracker}"
DB_USERNAME="${DB_USERNAME:-}"
DB_PASSWORD="${DB_PASSWORD:-}"

echo "──────────────────────────────────────────"
echo " StudyTracker – container initialisation"
echo "──────────────────────────────────────────"

# ── 1. Wait for MySQL ─────────────────────────
echo "Waiting for database on ${DB_HOST}:${DB_PORT} ..."

max_attempts=30
attempt=0
until php -r "
    \$attempts = 0;
    try {
        new PDO(
            'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}',
            '${DB_USERNAME}',
            '${DB_PASSWORD}',
            [PDO::ATTR_TIMEOUT => 3]
        );
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge "$max_attempts" ]; then
        echo "ERROR: could not connect to database after ${max_attempts} attempts. Aborting."
        exit 1
    fi
    echo "  → not ready yet (attempt ${attempt}/${max_attempts}), retrying in 3 s ..."
    sleep 3
done

echo "Database is ready!"

# ── 0. Restore default public assets that may be absent in fresh volumes ──
# user.png and other committed placeholder files live in the image but the
# public_data named volume may have been initialised before they were added.
# Rsync-style: only copy if destination file is missing.
for src in \
    /var/www/html/public/uploads/users/user.png \
    /var/www/html/public/uploads/index.html \
    /var/www/html/public/uploads/users/index.html \
    /var/www/html/public/uploads/site/index.html; do
    if [ ! -f "$src" ]; then
        # Reconstruct from image layer via /proc (image always has the file).
        # Simplest approach: copy from app image's own layer using tar.
        :  # file genuinely absent pre-volume; skip silently
    fi
done
# Concrete fix: seed user.png from a well-known location in the image.
DEFAULTS_SRC="/var/www/html-image-defaults"
if [ -d "$DEFAULTS_SRC" ]; then
    cp -rn "${DEFAULTS_SRC}/." /var/www/html/public/ 2>/dev/null || true
fi

# Ensure Laravel writable directories exist when using fresh Docker volumes.
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Ensure Passport encryption keys exist and are readable by php-fpm user.
if [ ! -f /var/www/html/storage/oauth-private.key ] || [ ! -f /var/www/html/storage/oauth-public.key ]; then
    echo "Generating Passport encryption keys ..."
    php artisan passport:keys --force --no-interaction || true
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

if [ -f /var/www/html/storage/oauth-private.key ]; then
    chown www-data:www-data /var/www/html/storage/oauth-private.key
    chmod 600 /var/www/html/storage/oauth-private.key
fi

if [ -f /var/www/html/storage/oauth-public.key ]; then
    chown www-data:www-data /var/www/html/storage/oauth-public.key
    chmod 600 /var/www/html/storage/oauth-public.key
fi

# ── 2. Run migrations ────────────────────────
echo "Running database migrations ..."
php artisan migrate --force --no-interaction

# ── 3. Cache configuration ───────────────────
echo "Caching config / routes / views ..."
php artisan config:cache
php artisan route:cache || true

# Some API-only setups may not have resources/views yet.
if [ -d "/var/www/html/resources/views" ]; then
    php artisan view:cache || true
fi

php artisan event:cache || true

# ── 4. Storage symlink ───────────────────────
echo "Creating storage symlink ..."
php artisan storage:link --force 2>/dev/null || true

echo "Initialisation complete. Starting PHP-FPM ..."
echo "──────────────────────────────────────────"

# Hand off to CMD (php-fpm)
exec "$@"
