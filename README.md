# webDeployLab

Learning to deploy websites with Docker, GitHub and Google Cloud — one step at a time.

## Stage 1: simple HTML with Nginx

```bash
docker compose up -d
```

Open `http://SERVER-IP` (on a server) — the page comes from `html/index.html`.

## Update the live site

```bash
# on PC
git add . && git commit -m "update page" && git push

# on the server
git pull
```

No restart needed: `html/` is shared with the Nginx container.
