#!/bin/bash
# ============================================================
# First Deploy Helper — run AFTER deploy.sh on first deployment
# Seeds the database and creates the Passport OAuth client
# Usage: sudo -u www-data bash deploy/first-deploy.sh
# ============================================================
set -euo pipefail

PROJECT_DIR="/var/www/study-tracker"
cd "$PROJECT_DIR"

echo "=========================================="
echo " First Deploy Setup"
echo "=========================================="

# Seed database
echo "[1/3] Seeding database..."
php artisan db:seed --no-interaction

# Create Passport personal access client
echo "[2/3] Creating Passport OAuth clients..."
echo ""
php artisan passport:client --password --name="Study Tracker App" --no-interaction
echo ""
echo "  ╔══════════════════════════════════════════════╗"
echo "  ║  Copy the Client ID and Secret from above    ║"
echo "  ║  into .env:                                  ║"
echo "  ║    VITE_OAUTH_CLIENT_ID=                     ║"
echo "  ║    VITE_OAUTH_CLIENT_SECRET=                 ║"
echo "  ║                                              ║"
echo "  ║  Then rebuild frontend and cache config:     ║"
echo "  ║    npm run build                             ║"
echo "  ║    php artisan config:cache                  ║"
echo "  ╚══════════════════════════════════════════════╝"

echo ""
echo "[3/3] Done! Follow the instructions above."
