#!/bin/bash
# ============================================================
# EC2 Per-Project Provisioning — DB/user, Nginx vhost,
# Supervisor queue worker, Cron scheduler.
#
# Shared-service RAM tuning (MySQL, Redis, PHP-FPM) is NOT here —
# it lives in ec2-setup.sh and is applied once per server.
#
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

# ── 1. Database + User (per-project provisioning) ─
echo "[1/4] Creating database and user..."

# Create database and user
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "FLUSH PRIVILEGES;"
echo "  → Database '${DB_NAME}' and user '${DB_USER}' created."

# ── 2. Nginx Vhost ───────────────────────────
echo "[2/4] Creating Nginx vhost..."
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

# ── 3. Supervisor (Queue Worker) ─────────────
echo "[3/4] Creating Supervisor config..."

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

# ── 4. Cron (Scheduler) ──────────────────────
echo "[4/4] Setting up cron scheduler..."
CRON_LINE="* * * * * cd /var/www/${PROJECT_NAME} && php artisan schedule:run >> /dev/null 2>&1"
(sudo crontab -u www-data -l 2>/dev/null | grep -v "${PROJECT_NAME}" ; echo "${CRON_LINE}") | sudo crontab -u www-data -

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
