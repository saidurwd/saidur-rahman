#!/usr/bin/env bash
#
# Deploy the latest code from GitHub on the server. Safe to run again at any time.
#
#   cd /var/www/saidur-rahman && ./deploy/deploy.sh
#
# Run as the `deploy` user. To roll back, check out an earlier tag and run it again:
#   git fetch --tags && git checkout phase-8 && ./deploy/deploy.sh      (then `git checkout main` when ready to move forward)
#
# Needs: git, PHP 8.4 CLI, Composer, Node 22. The only privileged step is reloading PHP-FPM, which uses one
# sudoers rule (see docs/DEPLOYMENT.md). Set SKIP_FPM_RELOAD=1 to leave that out.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BRANCH="${BRANCH:-main}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.4-fpm}"

cd "$APP_DIR"

step() { printf '\n\033[1m==> %s\033[0m\n' "$1"; }

# Bring the site back whatever happens, so a failed deploy never leaves visitors on the maintenance page.
cleanup() { php artisan up >/dev/null 2>&1 || true; }
trap cleanup EXIT

step "Pulling $BRANCH"
git fetch --prune --tags origin
if [[ "$(git rev-parse --abbrev-ref HEAD)" == "$BRANCH" ]]; then
    git merge --ff-only "origin/$BRANCH"
else
    echo "Not on $BRANCH (probably a rollback checkout); leaving the code as it is."
fi

step "Maintenance mode on"
php artisan down --retry=60 --refresh=10 || true

step "PHP dependencies (no dev packages)"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

step "Front-end build"
npm ci --no-audit --no-fund
npm run build

step "Database migrations"
php artisan migrate --force

step "Storage link and caches"
php artisan storage:link 2>/dev/null || true
php artisan optimize:clear
php artisan optimize
php artisan responsecache:clear
php artisan sitemap:generate

step "Restarting background workers"
php artisan queue:restart

if [[ "${SKIP_FPM_RELOAD:-0}" != "1" ]]; then
    step "Reloading PHP-FPM (clears OPcache)"
    sudo -n systemctl reload "$PHP_FPM_SERVICE"
fi

step "Maintenance mode off"
php artisan up

step "Checking the site"
php artisan launch:check || echo "(launch:check reported items above; the deploy itself succeeded)"

printf '\n\033[32mDeployed %s\033[0m\n' "$(git rev-parse --short HEAD)"
