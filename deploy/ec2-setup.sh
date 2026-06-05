#!/bin/bash
# ============================================================
# EC2 Initial Setup — Ubuntu 24.04 LTS (t4g.small, 2GB RAM, ARM64)
# Run ONCE on a fresh EC2 instance: sudo bash deploy/ec2-setup.sh
#
# Safe to run on a server already set up for another project -
# all steps are idempotent (swap check, apt install -y, key/keyring
# self-heal, tuning "write once" guards). Re-running repairs a
# half-finished previous run.
# ============================================================
set -euo pipefail

echo "=========================================="
echo " EC2 Server Setup"
echo "=========================================="

# ── 1. Swap (critical headroom for 2GB RAM) ──
if [ ! -f /swapfile ]; then
    echo "[1/9] Creating 2GB swap file..."
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    echo 'vm.swappiness=10' >> /etc/sysctl.conf
    sysctl vm.swappiness=10
else
    echo "[1/9] Swap already exists, skipping."
fi

# ── Self-heal: drop a stale/broken ondrej PPA source from a prior run ──
# A half-finished earlier run can leave an ondrej source list pointing at an
# empty keyring. Remove it so the apt-get update below doesn't abort on an
# unsigned repo; step 3 recreates it correctly once the keys are imported.
if [ -f /etc/apt/sources.list.d/ondrej-php.list ] \
   && ! gpg --show-keys /usr/share/keyrings/ondrej-php.gpg 2>/dev/null | grep -q '^pub'; then
    echo "  Removing stale ondrej/php source (keyring missing/empty)..."
    rm -f /etc/apt/sources.list.d/ondrej-php.list
fi

# ── 2. System update ─────────────────────────
echo "[2/9] Updating system packages..."
apt-get update && apt-get upgrade -y

# ── 3. PHP 8.4 + Extensions (ondrej PPA) ─────
echo "[3/9] Installing PHP 8.4..."
# dirmngr is required for any gpg keyserver fetch — installing it (plus a
# writable GNUPGHOME below) is what makes the key import reliable on a fresh box.
apt-get install -y software-properties-common ca-certificates apt-transport-https \
    gnupg dirmngr curl lsb-release

ONDREJ_KEYRING=/usr/share/keyrings/ondrej-php.gpg
# The PPA InRelease is signed by TWO keys (Launchpad per-PPA signing migration).
ONDREJ_KEYS="B8DC7E53946656EFBCE4C1DD71DAEAAB4AD4CAB6 4F4EA0AAE5267A6C"

# Self-healing guard: (re)import if the keyring is missing OR holds no keys.
# We deliberately guard on the keyring, NOT on the source-list file, so a prior
# half-finished run (stale .list + empty keyring) is repaired on re-run.
if ! gpg --show-keys "$ONDREJ_KEYRING" 2>/dev/null | grep -q '^pub'; then
    echo "  Importing ondrej/php signing keys..."
    # Fresh, writable home so gpg never depends on /root/.gnupg existing.
    GNUPGHOME="$(mktemp -d)"; export GNUPGHOME; chmod 700 "$GNUPGHOME"

    # Primary: keyserver over HTTPS (needs dirmngr, installed above).
    gpg --batch --no-default-keyring --keyring "$GNUPGHOME/ring.gpg" \
        --keyserver hkps://keyserver.ubuntu.com --recv-keys $ONDREJ_KEYS || true

    # Fallback: download armored keys over plain HTTPS (no dirmngr needed).
    if ! gpg --no-default-keyring --keyring "$GNUPGHOME/ring.gpg" --list-keys 2>/dev/null | grep -q '^pub'; then
        for k in $ONDREJ_KEYS; do
            curl -fsSL "https://keyserver.ubuntu.com/pks/lookup?op=get&options=mr&search=0x${k}" \
                | gpg --batch --no-default-keyring --keyring "$GNUPGHOME/ring.gpg" --import || true
        done
    fi

    # Export to the binary keyring apt will trust via signed-by.
    gpg --batch --no-default-keyring --keyring "$GNUPGHOME/ring.gpg" --export $ONDREJ_KEYS > "$ONDREJ_KEYRING"

    # Verify before trusting the repo — fail loudly rather than write a broken source.
    gpg --show-keys "$ONDREJ_KEYRING" 2>/dev/null | grep -q '^pub' || {
        echo "ERROR: could not import ondrej/php signing keys (keyserver unreachable?)." >&2
        exit 1
    }
    rm -rf "$GNUPGHOME"; unset GNUPGHOME
    echo "  → ondrej/php keys imported."
else
    echo "  ondrej/php keys already present, skipping import."
fi

# apt verifies signatures as the unprivileged _apt user, which cannot read a
# 600 keyring (gpg creates them that way) — that shows up as a false NO_PUBKEY
# even though the key is present. Force world-readable on every run.
chmod 0644 "$ONDREJ_KEYRING"

# Always (re)write the apt source so it stays correct and idempotent.
echo "deb [signed-by=$ONDREJ_KEYRING] https://ppa.launchpadcontent.net/ondrej/php/ubuntu $(lsb_release -sc) main" \
    > /etc/apt/sources.list.d/ondrej-php.list

apt-get update
apt-get install -y \
    php8.4-fpm \
    php8.4-cli \
    php8.4-mysql \
    php8.4-mbstring \
    php8.4-xml \
    php8.4-bcmath \
    php8.4-gd \
    php8.4-zip \
    php8.4-intl \
    php8.4-opcache \
    php8.4-redis \
    php8.4-curl \
    php8.4-readline

# ── 4. Nginx ─────────────────────────────────
echo "[4/9] Installing Nginx..."
apt-get install -y nginx

# ── 5. MySQL 8 ───────────────────────────────
echo "[5/9] Installing MySQL 8..."
apt-get install -y mysql-server

# ── 6. Redis ─────────────────────────────────
echo "[6/9] Installing Redis..."
apt-get install -y redis-server

# ── 7. Supervisor, Git, Certbot, unzip ───────
echo "[7/9] Installing Supervisor, Git, Certbot..."
apt-get install -y supervisor git unzip certbot python3-certbot-nginx

# ── 8. Composer ──────────────────────────────
echo "[8/9] Installing Composer..."
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
else
    echo "  Composer already installed, skipping."
fi

# ── Node.js 20 (build-time only) ─────────────
if ! command -v node &>/dev/null; then
    echo "[bonus] Installing Node.js 20..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
else
    echo "[bonus] Node.js already installed ($(node -v)), skipping."
fi

# ── 9. Shared-service RAM tuning ─────────────
# These services are installed ONCE per server and shared by all projects,
# so their memory tuning lives here (not in the per-project configure script).
# Sized for a t4g.small (2GB RAM) hosting 4-5 small Laravel projects.
echo "[9/9] Tuning shared services for 2GB RAM..."

# MySQL — low-memory tuning (shared across all project databases)
if [ ! -f /etc/mysql/mysql.conf.d/99-optimized.cnf ]; then
cat > /etc/mysql/mysql.conf.d/99-optimized.cnf << 'EOF'
[mysqld]
# Memory-optimized for t4g.small (2GB RAM), shared by multiple projects
innodb_buffer_pool_size = 256M
innodb_log_file_size    = 64M
max_connections         = 75
max_allowed_packet      = 64M
performance_schema      = OFF
character-set-server    = utf8mb4
collation-server        = utf8mb4_unicode_ci

# Slow query log
slow_query_log          = 1
slow_query_log_file     = /var/log/mysql/slow.log
long_query_time         = 2

[client]
default-character-set = utf8mb4
EOF
    echo "  → MySQL tuning written."
else
    echo "  MySQL tuning config already exists, skipping."
fi

# Redis — cap memory and evict under pressure (shared cache/sessions/queues)
if ! grep -q 'maxmemory 128mb' /etc/redis/redis.conf; then
    sed -i 's/^# maxmemory <bytes>.*/maxmemory 128mb/' /etc/redis/redis.conf
    sed -i 's/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
    echo "  → Redis tuning written."
else
    echo "  Redis already tuned, skipping."
fi

# PHP-FPM — production ini (shared across all projects on this PHP version)
if [ ! -f /etc/php/8.4/fpm/conf.d/99-production.ini ]; then
cat > /etc/php/8.4/fpm/conf.d/99-production.ini << 'EOF'
; Production PHP settings
memory_limit         = 128M
max_execution_time   = 300
max_input_time       = 300
upload_max_filesize  = 50M
post_max_size        = 50M
max_file_uploads     = 20

display_errors       = Off
display_startup_errors = Off
log_errors           = On
error_reporting      = E_ALL & ~E_DEPRECATED & ~E_STRICT

date.timezone        = UTC
session.gc_maxlifetime = 7200

; OPcache
opcache.enable                 = 1
opcache.memory_consumption     = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files  = 10000
opcache.revalidate_freq        = 60
opcache.validate_timestamps    = 0
opcache.save_comments          = 1
EOF
    echo "  → PHP-FPM ini written."
else
    echo "  PHP-FPM ini already exists, skipping."
fi

# PHP-FPM shared www pool — ondemand saves RAM when idle
sed -i 's/^pm = dynamic/pm = ondemand/' /etc/php/8.4/fpm/pool.d/www.conf
sed -i 's/^pm.max_children = .*/pm.max_children = 10/' /etc/php/8.4/fpm/pool.d/www.conf
# Recycle idle/old workers to cap memory growth (append only if not already set)
grep -q '^pm.process_idle_timeout' /etc/php/8.4/fpm/pool.d/www.conf \
    || echo 'pm.process_idle_timeout = 10s' >> /etc/php/8.4/fpm/pool.d/www.conf
grep -q '^pm.max_requests' /etc/php/8.4/fpm/pool.d/www.conf \
    || echo 'pm.max_requests = 500' >> /etc/php/8.4/fpm/pool.d/www.conf

# ── Enable & start services ──────────────────
systemctl enable --now nginx mysql redis-server php8.4-fpm supervisor
# Apply the tuning written above (harmless on a fresh boot)
systemctl restart mysql redis-server php8.4-fpm

# ── Create web root ──────────────────────────
mkdir -p /var/www
chown "$USER":www-data /var/www

echo ""
echo "=========================================="
echo " Base setup complete!"
echo ""
echo " Next steps:"
echo "   1. Edit deploy/ec2-configure.sh arguments (DB_PASS, DOMAIN)"
echo "   2. Run: sudo bash deploy/ec2-configure.sh"
echo "=========================================="
