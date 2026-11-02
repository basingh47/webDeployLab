# Live server manual

Commands to run on the VM (`ssh` into it first). Everything happens in the project folder:

```bash
cd ~/webDeployLab
```

## A. Fix the site now (500 "Vite manifest not found")

The site is already running. The only thing missing is the built CSS and JS (`public/build`).

```bash
# 1. latest code
git pull

# 2. build CSS/JS → public/build  (Node runs in a temporary container, the VM has no Node)
docker run --rm -v "$PWD:/app" -w /app node:22-alpine \
  sh -c "npm ci && npm run build && chown -R $(id -u):$(id -g) node_modules public/build"

# 3. refresh Laravel caches
docker compose exec app php artisan optimize
```

Check that it worked:

```bash
ls public/build/manifest.json                 # file exists
curl -I https://basingh.duckdns.org           # HTTP/2 200
```

Then open https://basingh.duckdns.org. You should see the white card titled "Hello World from Laravel".

## B. Every update: automatic with GitHub Actions

On every `git push` to `main`, [.github/workflows/deploy.yml](.github/workflows/deploy.yml):

1. builds the CSS/JS (`public/build`) on GitHub's machine. If the build fails, it stops here and the live site is untouched.
2. copies `public/build` to the VM.
3. SSHes into the VM and runs [docker/deploy.sh](docker/deploy.sh) (the steps below).

You can follow it under the repo's **Actions** tab. The VM never builds CSS/JS any more.

To run the server steps by hand on the VM: `~/webDeployLab/docker/deploy.sh`. This doesn't build CSS/JS; use the command in section A for that.

### One-time setup (so GitHub can log in to the VM)

Make an SSH key on the VM and save it in GitHub Secrets (`SSH_HOST`, `SSH_USER`, `SSH_KEY`). The steps, an explanation of how the key works, and fixes for errors are in [SSH-KEY.md](SSH-KEY.md).

### What deploy.sh does (same as by hand)

```bash
cd ~/webDeployLab
git pull

# start / update containers (rebuilds the PHP image only if docker/php changed)
docker compose up -d --build

# only if composer.lock changed
docker compose exec app composer install --no-dev --optimize-autoloader

# only if there are new migrations
docker compose exec app php artisan migrate --force

# always
docker compose exec app php artisan optimize
```

The script runs all of them every time, because they're safe to repeat.

## C. When something is wrong

| Problem | Command |
|---|---|
| Are the containers running? | `docker compose ps` |
| Nginx / PHP-FPM logs | `docker compose logs --tail=50 web` / `docker compose logs --tail=50 app` |
| Laravel errors | `grep ERROR storage/logs/laravel.log \| tail -5` |
| Changed `.env` | `docker compose exec app php artisan optimize` |
| Changed `docker-compose*.yml` or nginx conf | `docker compose up -d` |
| Changed `docker/php/Dockerfile` | `docker compose up -d --build` (slow on e2-micro) |
| Go inside the PHP container | `docker compose exec app sh` (`exit` to leave) |
