# Deployment Guide

This guide covers deploying **Infinri** to a production-grade Linux server.  It assumes you have **PHP 8.4**, **PostgreSQL 16**, **Redis 7**, and a reverse-proxy (Nginx or Caddy).

---

## 1. Server Requirements

| Component | Recommended Version |
|-----------|--------------------|
| OS        | Ubuntu 22.04 LTS   |
| PHP       | 8.4 + extensions: `pdo_pgsql`, `intl`, `mbstring`, `openssl`, `sodium` |
| RoadRunner| 3.x               |
| Postgres  | 16                |
| Redis     | 7                 |
| Node      | 18 + Bun          |

---

## 2. Clone & Build

```bash
# As deploy user
cd /var/www
sudo -u deploy git clone https://github.com/infinri/Infinri.git app
cd app

composer install --optimize-autoloader --no-dev
bun install --production && bun run build
```

---

## 3. Environment

Copy the example file and set secure values:

```bash
cp .env.example .env
nano .env  # APP_KEY, DB creds, SMTP, Redis, etc.
```

Generate an application key if missing:

```bash
php app/cli.php key:generate
```

---

## 4. Database Migrations

```bash
php app/cli.php migrations:migrate --force
```

Seed data if you have seeders:

```bash
# php app/cli.php db:seed --force
```

---

## 5. RoadRunner Service

Create `/etc/systemd/system/roadrunner.service`:

```ini
[Unit]
Description=RoadRunner PHP Application Server
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/app
ExecStart=/var/www/app/rr serve -c .rr.yaml
Restart=always
Environment=APP_ENV=production

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now roadrunner
```

---

## 6. Reverse-Proxy (Caddy Example)

```caddyfile
infinri.com {
    encode gzip
    reverse_proxy 127.0.0.1:8080
    log {
        output file /var/log/caddy/infinri.access.log
    }
}
```

> Nginx template is provided under `deploy/nginx.conf`.

---

## 7. Monitoring & Logs

* **Loki** ‑ collect logs via Promtail.
* **Prometheus** ‑ scrape RoadRunner metrics endpoint (`:2112`).
* **Grafana** ‑ dashboards.

---

## 8. Zero-Downtime Deploys

1. `git pull`
2. `composer install --no-dev`
3. `bun install --production && bun run build`
4. `php app/cli.php migrations:migrate --force`
5. `systemctl reload roadrunner`  # graceful reload

---

## 9. Backup Strategy

* Nightly Postgres dumps → S3.
* Redis persistence enabled (AOF).
* `storage/` uploads → object storage.
* Quarterly restore drills.

---

You now have **Infinri** running in production. 🚀
