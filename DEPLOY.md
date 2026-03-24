# Deployment Guide — school.inte.team

Inte-School runs as a Docker Compose stack. Caddy handles HTTPS (auto-cert via Let's Encrypt), nginx serves static assets and proxies PHP-FPM, and Caddy routes WebSocket traffic to Reverb.

---

## Prerequisites

| Requirement | Version |
|---|---|
| Ubuntu | 22.04 or 24.04 LTS |
| Docker Engine | 24+ |
| Docker Compose plugin (v2) | 2.20+ |
| Open ports | 80, 443 |
| DNS | A record for `school.inte.team` → server IP |

```bash
# Verify Docker Compose v2 is present
docker compose version
```

---

## First-Time Installation

A single script handles everything: clone, secrets generation, database setup, VAPID keys, asset build, and cache warming.

```bash
# On the server, as root:
curl -fsSL https://raw.githubusercontent.com/inteteam/inte-school/main/install.sh | sudo bash
```

Or clone manually and run:

```bash
git clone git@github.com:inteteam/inte-school.git /opt/inte-school
cd /opt/inte-school
sudo bash install.sh
```

### What `install.sh` does

1. Creates the shared Docker network `proxy-tier` (if absent)
2. Clones the repository to `/opt/inte-school`
3. Copies `.env.production.example` → `.env` and generates:
   - `APP_KEY` (base64 random)
   - `DB_PASSWORD` (40-char random)
   - `REDIS_PASSWORD` (40-char random)
   - `REVERB_APP_ID / KEY / SECRET` (random)
4. Starts all production containers (`--profile prod`)
5. Runs `php artisan migrate --force` + `db:seed --force`
6. Generates VAPID keys via `php artisan vapid:generate`
7. Builds frontend assets via `docker compose run --rm npm run build`
8. Warms Laravel caches (config, route, view, event)
9. Restarts queue worker and Reverb

### Post-install secrets to set manually

Edit `/opt/inte-school/.env` and fill in:

```bash
# Mail — Resend (primary)
RESEND_API_KEY=re_...

# Mail — Mailgun (fallback)
MAILGUN_DOMAIN=mg.inte.school
MAILGUN_SECRET=key-...

# GCS — document & media storage (optional but recommended)
GCS_PROJECT_ID=inte-school-prod
GCS_KEY_FILE=/var/www/storage/gcs-key.json
GCS_BUCKET=inteschool-prod-media

# SMS — fallback notifications (optional)
SMS_PROVIDER=twilio
SMS_API_KEY=...
SMS_FROM=+441234567890
```

After editing, reload configuration:

```bash
cd /opt/inte-school
docker compose exec php-fpm php artisan config:cache
```

---

## Updating to a New Release

```bash
cd /opt/inte-school

# Pull latest code
git pull origin main

# Rebuild and restart (rebuilds PHP image, restarts all services)
docker compose --profile prod up -d --build

# Install any new PHP dependencies
docker compose exec php-fpm composer install --no-dev --optimize-autoloader --no-interaction

# Run any new migrations
docker compose exec php-fpm php artisan migrate --force

# Rebuild frontend assets (npm service is dev-only, pass --profile dev)
docker compose --profile dev run --rm npm sh -c "npm install && npm run build"

# Re-warm caches
docker compose exec php-fpm php artisan optimize

# Restart workers (queue + reverb pick up new code)
docker compose up -d --build queue-worker
docker compose restart reverb
```

> **Important:** `docker compose up -d` alone does NOT reload new static assets — always include `--build`.

---

## Architecture

```
Internet
   │  HTTPS :443
   ▼
Caddy (caddy:2-alpine, profile: prod)
   │  /app/*  WebSocket
   ├──────────────────► Reverb :8080  (Laravel broadcasting)
   │
   │  everything else
   └──────────────────► nginx :80
                           │
                           └──► php-fpm :9000
                                    │
                                    ├──► PostgreSQL :5432  (pgvector/pgvector:pg16)
                                    └──► Redis :6379

queue-worker  (Laravel Horizon — background jobs)
```

Caddy provisions TLS certificates from Let's Encrypt automatically on first request. No manual cert management required.

---

## Container Management

```bash
# Show running containers
docker compose --profile prod ps

# View logs
docker compose logs -f php-fpm
docker compose logs -f queue-worker
docker compose logs -f reverb
docker compose logs -f caddy

# Open Laravel shell
docker compose exec php-fpm php artisan tinker

# Restart a single service
docker compose restart php-fpm
docker compose restart caddy
```

---

## Database Backups

Manual backup:

```bash
docker compose exec postgresql \
    pg_dump -U inteschool inteschool | gzip \
    > /opt/backups/inteschool_$(date +%Y%m%d_%H%M%S).sql.gz
```

Scheduled daily backup (add to root crontab):

```bash
# crontab -e
0 3 * * * cd /opt/inte-school && docker compose exec -T postgresql \
    pg_dump -U inteschool inteschool | gzip \
    > /opt/backups/inteschool_$(date +\%Y\%m\%d).sql.gz \
    && find /opt/backups -name 'inteschool_*.sql.gz' -mtime +30 -delete
```

Restore from backup:

```bash
gunzip -c /opt/backups/inteschool_YYYYMMDD.sql.gz \
    | docker compose exec -T postgresql psql -U inteschool inteschool
```

---

## Environment Reference

| Variable | Description |
|---|---|
| `APP_KEY` | Laravel encryption key — never share |
| `DB_PASSWORD` | PostgreSQL password — generated by installer |
| `REDIS_PASSWORD` | Redis password — generated by installer |
| `REVERB_APP_KEY/SECRET` | Reverb WebSocket credentials |
| `VAPID_PUBLIC_KEY` | Web Push public key |
| `VAPID_PRIVATE_KEY` | Web Push private key — never share |
| `RESEND_API_KEY` | Primary transactional email |
| `MAILGUN_*` | Fallback email provider |
| `GCS_*` | Google Cloud Storage (documents, media) |
| `OLLAMA_HOST` | RAG pipeline endpoint |

Full variable reference: `.env.production.example`

---

## Troubleshooting

**Caddy not issuing certificate**
- Confirm DNS A record is set and propagated (`dig school.inte.team`)
- Port 80 must be open for the ACME HTTP-01 challenge
- Check logs: `docker compose logs caddy`

**Database connection refused**
- Ensure PostgreSQL container is running: `docker compose ps`
- Check `DB_PASSWORD` in `.env` matches what PostgreSQL started with
- If you regenerated `.env` after first start, stop + remove the volume and re-run migrations:
  ```bash
  docker compose down
  docker volume rm inte-school_postgresql_data
  docker compose --profile prod up -d
  docker compose exec php-fpm php artisan migrate --force
  ```

**WebSocket not connecting**
- Confirm `REVERB_HOST=school.inte.team`, `REVERB_PORT=443`, `REVERB_SCHEME=https` in `.env`
- Check Reverb is running: `docker compose ps reverb`
- Check Caddy is routing `/app/*` correctly: `docker compose logs caddy`

**Queue jobs not processing**
- Check Horizon: `docker compose logs queue-worker`
- Restart: `docker compose up -d --build queue-worker`

**Assets returning 404 after deploy**
- Run `docker compose run --rm npm run build` then `docker compose --profile prod up -d --build`

---

## Security Hardening (post-install)

- [ ] Rotate secrets: regenerate `APP_KEY`, `DB_PASSWORD`, `REDIS_PASSWORD` if installer output was logged
- [ ] Restrict SSH to key-based auth only (`PasswordAuthentication no`)
- [ ] Set up UFW: `ufw allow 22,80,443/tcp && ufw enable`
- [ ] Run `composer audit` and `npm audit` before each release
- [ ] Enable unattended-upgrades for OS security patches
- [ ] Store `/opt/inte-school/.env` in a secrets manager (e.g. Bitwarden, HashiCorp Vault) — never commit it

Full security reference: `docs/SECURITY.md`
