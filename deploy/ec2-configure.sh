#!/bin/bash
# ============================================================
# EC2 Service Configuration — MySQL, Redis, PHP-FPM, Nginx,
# Supervisor, Cron tuned for t2.micro (1GB RAM)
# Run ONCE per project after ec2-setup.sh:
#   sudo bash deploy/ec2-configure.sh
# Or with explicit arguments:
#   sudo bash deploy/ec2-configure.sh study-tracker _ study_tracker st_user YOUR_DB_PASS 8081
# ============================================================
set -euo pipefail

# ╔════════════════════════════════════════════╗
# ║  EDIT THESE OR PASS AS ARGUMENTS           ║
# ╚════════════════════════════════════════════╝

PROJECT_NAME="${1:-study-tracker}"
DOMAIN="${2:-_}"
DB_NAME="${3:-study_tracker}"
DB_USER="${4:-st_user}"
DB_PASS="${5:-CHANGE_ME_STRONG_PASSWORD}"
APP_PORT="${6:-80}"

# If DB_PASS is still placeholder, prompt for it
if [ "$DB_PASS" = "CHANGE_ME_STRONG_PASSWORD" ]; then
    echo "Please provide a database password for user '${DB_USER}':"
    read -s DB_PASS
    echo ""
fi

echo "=========================================="
echo " Configuring services for: ${PROJECT_NAME}"
echo " Host match: ${DOMAIN}"
echo " Listen port: ${APP_PORT}"
echo "=========================================="

# ── 1. MySQL Tuning (low memory for t2.micro) ─
echo "[1/6] Tuning MySQL..."

# Only write MySQL tuning once (shared with other projects)
if [ ! -f /etc/mysql/mysql.conf.d/99-optimized.cnf ]; then
    cat > /etc/mysql/mysql.conf.d/99-optimized.cnf << 'EOF'
[mysqld]
# Memory-optimized for t2.micro (1GB RAM)
innodb_buffer_pool_size = 128M
innodb_log_file_size    = 32M
max_connections         = 50
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
    systemctl restart mysql
else
    echo "  MySQL tuning config already exists, skipping restart."
fi

# Create database and user
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "FLUSH PRIVILEGES;"
echo "  → Database '${DB_NAME}' and user '${DB_USER}' created."

# ── 2. Redis Tuning ──────────────────────────
echo "[2/6] Tuning Redis..."
if ! grep -q 'maxmemory 64mb' /etc/redis/redis.conf; then
    sed -i 's/^# maxmemory <bytes>.*/maxmemory 64mb/' /etc/redis/redis.conf
    sed -i 's/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
    systemctl restart redis-server
else
    echo "  Redis already tuned, skipping."
fi

# ── 3. PHP-FPM Tuning ────────────────────────
echo "[3/6] Tuning PHP 8.4-FPM..."

# Only write PHP ini once (shared across all projects on this PHP version)
if [ ! -f /etc/php/8.4/fpm/conf.d/99-production.ini ]; then
    cat > /etc/php/8.4/fpm/conf.d/99-production.ini << 'EOF'
; Production PHP settings
memory_limit         = 128M
max_execution_time   = 300
max_input_time       = 300
upload_max_filesize  = 50M
post_max_size        = 50M
max_file_uploads     = 20

display_errors         = Off
display_startup_errors = Off
log_errors             = On
error_reporting        = E_ALL & ~E_DEPRECATED & ~E_STRICT

date.timezone          = UTC
session.gc_maxlifetime = 7200

; OPcache
opcache.enable                  = 1
opcache.memory_consumption      = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files   = 10000
opcache.revalidate_freq         = 60
opcache.validate_timestamps     = 0
opcache.save_comments           = 1
EOF
fi

# PHP-FPM pool: ondemand saves RAM when idle
sed -i 's/^pm = dynamic/pm = ondemand/' /etc/php/8.4/fpm/pool.d/www.conf
sed -i 's/^pm.max_children = .*/pm.max_children = 5/' /etc/php/8.4/fpm/pool.d/www.conf

systemctl restart php8.4-fpm

# ── 4. Nginx Vhost ───────────────────────────
echo "[4/6] Creating Nginx vhost..."
cat > "/etc/nginx/sites-available/${PROJECT_NAME}" << NGINX_EOF
server {
    listen ${APP_PORT};
    server_name ${DOMAIN};

    root /var/www/${PROJECT_NAME}/public;
    index index.php index.html;

    # Security headers
    add_header X-Content-Type-Options "nosniff";
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    charset utf-8;
    client_max_body_size 50M;

    # Gzip
    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml text/plain;

    # Laravel front-controller
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Let Laravel handle admin system log URLs containing .log
    location ^~ /admin/system-logs/ {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP-FPM
    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
    }

    # Static assets — aggressive caching (Vite cache-busted filenames)
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|otf|eot|webp)\$ {
        expires 1y;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    # Health check
    location = /healthz {
        access_log off;
        return 200 "ok\n";
        add_header Content-Type text/plain;
    }

    # Deny hidden files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Deny sensitive files
    location ~* \.(env|log|ini|htaccess)\$ {
        deny all;
    }

    error_log  /var/log/nginx/${PROJECT_NAME}-error.log warn;
    access_log /var/log/nginx/${PROJECT_NAME}-access.log;
}
NGINX_EOF

ln -sf "/etc/nginx/sites-available/${PROJECT_NAME}" "/etc/nginx/sites-enabled/${PROJECT_NAME}"
# Remove default site only on a fresh server (guard avoids breaking other projects)
[ -f /etc/nginx/sites-enabled/default ] && rm -f /etc/nginx/sites-enabled/default || true
nginx -t && systemctl reload nginx

# ── 5. Supervisor (Queue Worker) ─────────────
echo "[5/6] Creating Supervisor config..."

# Supervisor validates stdout_logfile path on reread.
# Ensure app log directory exists even before first deploy.
mkdir -p "/var/www/${PROJECT_NAME}/storage/logs"
chown -R www-data:www-data "/var/www/${PROJECT_NAME}/storage" 2>/dev/null || true

cat > "/etc/supervisor/conf.d/${PROJECT_NAME}-worker.conf" << EOF
[program:${PROJECT_NAME}-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/${PROJECT_NAME}/artisan queue:work --queue=emails,default --sleep=3 --tries=3 --max-time=3600 --memory=128
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/${PROJECT_NAME}/storage/logs/worker.log
stopwaitsecs=3600
EOF

supervisorctl reread
supervisorctl update

# ── 6. Cron (Scheduler) ──────────────────────
echo "[6/6] Setting up cron scheduler..."
CRON_LINE="* * * * * cd /var/www/${PROJECT_NAME} && php artisan schedule:run >> /dev/null 2>&1"
(crontab -u www-data -l 2>/dev/null | grep -v "${PROJECT_NAME}" ; echo "${CRON_LINE}") | crontab -u www-data -

echo ""
echo "=========================================="
echo " Configuration complete!"
echo ""
echo " Next steps:"
echo "   1. sudo bash deploy/deploy.sh"
echo "   2. sudo -u www-data bash deploy/first-deploy.sh  (first time only)"
if [ "${DOMAIN}" != "_" ]; then
    echo "   3. sudo certbot --nginx -d ${DOMAIN}  (SSL)"
else
    echo "   3. Domain not set yet, skip SSL for now"
fi
echo "=========================================="
