# webDeployLab

Learning to deploy websites with Docker, GitHub and Google Cloud — one step at a time.

## Stage 2: Laravel with Nginx + PHP-FPM

Two containers:

```
browser → :80 → web (Nginx) → app (PHP-FPM, Laravel)
```

- `docker-compose.yml` — the two services
- `docker/php/Dockerfile` — PHP 8.4 FPM + Composer
- `docker/nginx/default.conf` — serves `public/`, sends `.php` to `app`

### First run on the server

```bash
git clone https://github.com/basingh47/webDeployLab.git
cd webDeployLab
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan key:generate
touch database/database.sqlite
docker compose exec app php artisan migrate --force
```

In `.env` on the server set `APP_ENV=production`, `APP_DEBUG=false` and `APP_URL=http://SERVER-IP`.
If your user id is not 1000 (`id -u`), set `HOST_UID` / `HOST_GID` in `.env`.

Open `http://SERVER-IP` → `Hello World from Laravel`.

### Update the live site

```bash
# on PC
git add . && git commit -m "update" && git push

# on the server
git pull
docker compose exec app composer install --no-dev --optimize-autoloader   # only if composer.lock changed
docker compose exec app php artisan migrate --force                      # only if there are new migrations
```

## Stage 3: HTTPS with Let's Encrypt

Domain `basingh.duckdns.org` (DuckDNS) → server IP. Server-only files:

- `docker-compose.prod.yml` — adds port 443 and mounts `/etc/letsencrypt`
- `docker/nginx/ssl.conf` — HTTPS site, `http://` redirects to `https://`
- `docker/certbot/renew.sh` — renews the certificate, reloads Nginx

### Get the certificate (once, site must be running on http)

```bash
docker run --rm \
  -v "$PWD/public:/webroot" \
  -v /etc/letsencrypt:/etc/letsencrypt \
  -v /var/lib/letsencrypt:/var/lib/letsencrypt \
  certbot/certbot certonly --webroot -w /webroot \
  -d basingh.duckdns.org --agree-tos -m basingh47@gmail.com --no-eff-email
```

### Turn on HTTPS

In the server's `.env`:

```
COMPOSE_FILE=docker-compose.yml:docker-compose.prod.yml
APP_URL=https://basingh.duckdns.org
```

```bash
docker compose up -d
docker compose exec app php artisan config:cache
```

### Auto-renew (certificate lasts 90 days)

`crontab -e`, add:

```
0 3 * * * $HOME/webDeployLab/docker/certbot/renew.sh >> $HOME/certbot-renew.log 2>&1
```

### Local PC

Port 80 is busy on the PC, so `.env` has `APP_PORT=8000` → `http://localhost:8000`.
