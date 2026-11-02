#!/bin/sh
# Update the live site to the latest code on GitHub.
# Run by GitHub Actions over SSH on every push to main (.github/workflows/deploy.yml),
# or by hand on the server: ~/webDeployLab/docker/deploy.sh
# CSS/JS (public/build) is built by GitHub Actions and copied here before this runs.
set -e    # stop at the first failing command

cd "$(dirname "$0")/.." || exit 1

echo "==> Pull latest code"
git pull --ff-only

echo "==> Start / update containers (rebuilds the PHP image only if docker/php changed)"
docker compose up -d --build

echo "==> PHP packages"
docker compose exec -T app composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Database migrations"
docker compose exec -T app php artisan migrate --force

echo "==> Laravel caches"
docker compose exec -T app php artisan optimize

echo "==> Deployed $(git log -1 --format='%h %s')"
