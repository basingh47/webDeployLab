#!/bin/sh
# Renew the Let's Encrypt certificate (only does something when < 30 days left), then reload Nginx.
# Run daily from cron on the server.

cd "$(dirname "$0")/../.." || exit 1

docker run --rm \
  -v "$PWD/public:/webroot" \
  -v /etc/letsencrypt:/etc/letsencrypt \
  -v /var/lib/letsencrypt:/var/lib/letsencrypt \
  certbot/certbot renew --webroot -w /webroot --quiet

docker compose exec -T web nginx -s reload
