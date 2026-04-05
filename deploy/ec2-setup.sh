#!/bin/bash
# ============================================================
# EC2 Initial Setup — Ubuntu 24.04 LTS (t2.micro free tier)
# Run ONCE on a fresh EC2 instance: sudo bash deploy/ec2-setup.sh
#
# Safe to run on a server already set up for another project —
# all steps are idempotent (swap check, apt install -y, etc.).
# ============================================================
set -euo pipefail

echo "=========================================="
echo " EC2 Server Setup"
echo "=========================================="

# ── 1. Swap (critical for 1GB RAM) ───────────
if [ ! -f /swapfile ]; then
    echo "[1/8] Creating 2GB swap file..."
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    echo 'vm.swappiness=10' >> /etc/sysctl.conf
    sysctl vm.swappiness=10
else
    echo "[1/8] Swap already exists, skipping."
fi

# ── 2. System update ─────────────────────────
echo "[2/8] Updating system packages..."
apt-get update && apt-get upgrade -y

# ── 3. PHP 8.3 + Extensions ──────────────────
echo "[3/8] Installing PHP 8.3..."
apt-get install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get install -y \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-bcmath \
    php8.3-gd \
    php8.3-zip \
    php8.3-intl \
    php8.3-opcache \
    php8.3-redis \
    php8.3-curl \
    php8.3-readline

# ── 4. Nginx ─────────────────────────────────
echo "[4/8] Installing Nginx..."
apt-get install -y nginx

# ── 5. MySQL 8 ───────────────────────────────
echo "[5/8] Installing MySQL 8..."
apt-get install -y mysql-server

# ── 6. Redis ─────────────────────────────────
echo "[6/8] Installing Redis..."
apt-get install -y redis-server

# ── 7. Supervisor, Git, Certbot, unzip ───────
echo "[7/8] Installing Supervisor, Git, Certbot..."
apt-get install -y supervisor git unzip certbot python3-certbot-nginx

# ── 8. Composer ──────────────────────────────
echo "[8/8] Installing Composer..."
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

# ── Enable & start services ──────────────────
systemctl enable --now nginx mysql redis-server php8.3-fpm supervisor

# ── Create web root ──────────────────────────
mkdir -p /var/www
chown www-data:www-data /var/www

echo ""
echo "=========================================="
echo " Base setup complete!"
echo ""
echo " Next steps:"
echo "   1. Edit deploy/ec2-configure.sh arguments (DB_PASS, DOMAIN)"
echo "   2. Run: sudo bash deploy/ec2-configure.sh"
echo "=========================================="
