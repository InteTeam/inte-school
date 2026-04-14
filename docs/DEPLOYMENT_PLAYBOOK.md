# Deployment Playbook

Operational notes for staging and production deployments.

---

## Reverse Proxy / Trust Proxies

Laravel's `trustProxies` is configured in `bootstrap/app.php`.

### Current state (staging)

All proxies are trusted (`'*'`) because the app runs behind Docker's internal network where gateway IPs can change across container restarts.

### Before production go-live

Lock down `trustProxies` to specific known proxy IPs or CIDR ranges:

```php
$middleware->trustProxies(
    at: ['10.0.0.0/8', '172.16.0.0/12'],  // your actual proxy/load-balancer range
    headers: Request::HEADER_X_FORWARDED_FOR |
             Request::HEADER_X_FORWARDED_HOST |
             Request::HEADER_X_FORWARDED_PORT |
             Request::HEADER_X_FORWARDED_PROTO,
);
```

- Remove `Request::HEADER_X_FORWARDED_AWS_ELB` unless running behind AWS ELB.
- If using Cloudflare, use their published IP ranges instead of `'*'`.
- Trusting `'*'` in production with a public-facing app allows IP spoofing via `X-Forwarded-For`.

---

## Static Asset Reload

After `git pull` + `npm run build` on production, always use:

```bash
docker compose up -d --build
```

Plain `up -d` does **not** reload new static assets.

---

## Database

- `pgvector` extension must be enabled before migrations: handled by migration `001`.
- Always run `composer audit` + `npm audit` before deploying package changes.
