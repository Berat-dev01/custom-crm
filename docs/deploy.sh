#!/usr/bin/env bash
# Örnek production deploy betiği (git pull tabanlı, tek sunucu).
# Kullanım: ./deploy.sh  (uygulama kök dizininde, www-data ile)
set -euo pipefail

echo "→ Maintenance mode"
php artisan down --retry=30 || true

echo "→ Pull latest release"
git fetch --tags origin
git checkout "${1:-main}"
git pull --ff-only

echo "→ Install dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Migrate"
php artisan migrate --force

echo "→ Rebuild caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "→ Restart queue workers"
php artisan queue:restart

echo "→ Health check"
php artisan crm:doctor

echo "→ Live"
php artisan up

echo "Deploy complete: $(git describe --tags --always)"
