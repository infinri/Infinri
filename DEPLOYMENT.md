# Deployment Guide

Complete deployment guide for Infinri using **RoadRunner** (high-performance PHP application server) and **Caddy** (automatic HTTPS).

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Quick Start (Docker)](#quick-start-docker)
3. [Local Development (Native)](#local-development-native)
4. [Production Deployment](#production-deployment)
5. [Maintenance](#maintenance)
6. [Performance Verification](#performance-verification)

---

## Prerequisites

### Production Server
- Linux (Ubuntu 22.04/24.04 recommended)
- PHP 8.4 with extensions: cli, mbstring, xml, curl, zip, opcache, pdo_pgsql, redis
- RoadRunner 2024.x (binary)
- Caddy 2.x (reverse proxy + auto SSL)
- PostgreSQL 16+
- Redis 7+
- ⚠️ **Node.js NOT required on server** (assets pre-built locally)

### Local Development
- PHP 8.4+ 
- Composer 2.x
- Node.js 18+ (for asset building only)
- Docker (recommended) or native install
- Git

### Accounts Required
- Brevo account for email API (free tier: 300 emails/day)
- Domain name (for production)
- Server/VPS (DigitalOcean, Linode, Hetzner, etc.)

---

## Quick Start (Docker)

The fastest way to get started:

```bash
# Clone and enter project
git clone https://github.com/infinri/Infinri.git
cd Infinri

# Copy environment file
cp .env.example .env

# Start all services (RoadRunner, PostgreSQL, Redis, Caddy)
docker compose up -d

# View logs
docker compose logs -f app
```

**Access:** http://localhost

### Docker Services

| Service | Port | Description |
|---------|------|-------------|
| `app` | 8080 | RoadRunner PHP server |
| `caddy` | 80, 443 | Reverse proxy + static files |
| `postgres` | 5432 | PostgreSQL database |
| `redis` | 6379 | Cache and sessions |

### Docker Commands

```bash
# Start services
docker compose up -d

# Stop services
docker compose down

# View logs
docker compose logs -f app

# Rebuild after changes
docker compose build --no-cache app

# Execute command in container
docker compose exec app php bin/console s:up

# Shell into container
docker compose exec app sh
```

---

## Local Development (Native)

### 1. Install RoadRunner

```bash
# Download latest RoadRunner binary
# Linux/macOS
curl -L https://github.com/roadrunner-server/roadrunner/releases/latest/download/roadrunner-linux-amd64.tar.gz | tar xz
sudo mv roadrunner /usr/local/bin/rr

# Or via Go
go install github.com/roadrunner-server/roadrunner/v2024/cmd/rr@latest

# Verify installation
rr --version
```

### 2. Install Dependencies

```bash
# Clone repository
git clone https://github.com/infinri/Infinri.git
cd Infinri

# Install PHP dependencies
composer install

# Install Node dependencies (for asset building)
npm install

# Copy environment config
cp .env.example .env
```

### 3. Configure Environment

Edit `.env`:

```env
APP_ENV=development
APP_DEBUG=true
HTTPS_ONLY=false
SITE_URL=http://localhost

# Database
DB_HOST=127.0.0.1
DB_DATABASE=infinri
DB_USERNAME=postgres
DB_PASSWORD=secret

# Redis
REDIS_HOST=127.0.0.1

# Brevo Email (get key at brevo.com)
BREVO_API_KEY=your-api-key
BREVO_SENDER_EMAIL=noreply@yourdomain.com
BREVO_RECIPIENT_EMAIL=your-email@example.com
```

### 4. Start Development Server

**Option A: RoadRunner only (port 8080)**
```bash
rr serve -c .rr.yaml
```
Access: http://localhost:8080

**Option B: Caddy + RoadRunner (port 80, static file caching)**
```bash
# Terminal 1: Start RoadRunner
rr serve -c .rr.yaml

# Terminal 2: Start Caddy
caddy run
```
Access: http://localhost

### 5. Run Setup

```bash
php bin/console s:up
```

### 6. Stop Services

```bash
# Stop RoadRunner
rr stop

# Stop Caddy
caddy stop
```

---

## Production Deployment

### Step 1: Prepare Server

```bash
# SSH into server
ssh root@your-server-ip

# Update system
apt update && apt upgrade -y

# Install PHP 8.4 (no FPM needed!)
apt install -y php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl \
    php8.4-zip php8.4-opcache php8.4-intl php8.4-pgsql php8.4-redis

# Install Caddy
apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | tee /etc/apt/sources.list.d/caddy-stable.list
apt update && apt install caddy

# Install RoadRunner
curl -L https://github.com/roadrunner-server/roadrunner/releases/latest/download/roadrunner-linux-amd64.tar.gz | tar xz
mv roadrunner /usr/local/bin/rr
chmod +x /usr/local/bin/rr

# Install PostgreSQL
apt install -y postgresql postgresql-contrib

# Install Redis
apt install -y redis-server
systemctl enable redis-server
```

### Step 2: Configure PostgreSQL

```bash
sudo -u postgres psql
```

```sql
CREATE USER infinri WITH PASSWORD 'your-secure-password';
CREATE DATABASE infinri OWNER infinri;
GRANT ALL PRIVILEGES ON DATABASE infinri TO infinri;
\q
```

### Step 3: Deploy Application

```bash
# Create directory
mkdir -p /var/www/infinri
cd /var/www/infinri

# Clone or upload code
git clone https://github.com/infinri/Infinri.git .
# Or: rsync -avz --exclude 'var/' --exclude 'node_modules/' ./ root@server:/var/www/infinri/

# Install dependencies (production)
composer install --no-dev --optimize-autoloader

# Configure environment
cp .env.example .env
nano .env
```

Set production values in `.env`:
```env
APP_ENV=production
APP_DEBUG=false
HTTPS_ONLY=true
SITE_URL=https://yourdomain.com

DB_HOST=127.0.0.1
DB_DATABASE=infinri
DB_USERNAME=infinri
DB_PASSWORD=your-secure-password

REDIS_HOST=127.0.0.1
```

### Step 4: Set Permissions

```bash
# Create log directory
mkdir -p /var/log/infinri

# Set ownership
chown -R www-data:www-data /var/www/infinri
chown -R www-data:www-data /var/log/infinri

# Set permissions
chmod -R 755 /var/www/infinri
chmod -R 775 /var/www/infinri/var
chmod 600 /var/www/infinri/.env
```

### Step 5: Install RoadRunner Service

```bash
# Copy systemd service
cp /var/www/infinri/docker/infinri-rr.service /etc/systemd/system/

# Reload systemd
systemctl daemon-reload

# Enable and start
systemctl enable infinri-rr
systemctl start infinri-rr

# Check status
systemctl status infinri-rr
```

### Step 6: Configure Caddy

```bash
# Edit production Caddyfile
nano /etc/caddy/Caddyfile
```

Replace with:
```caddyfile
yourdomain.com {
    root * /var/www/infinri/pub
    
    @static {
        path *.css *.js *.ico *.gif *.jpg *.jpeg *.png *.svg *.woff *.woff2 *.webp
    }
    handle @static {
        file_server
        header Cache-Control "public, max-age=31536000, immutable"
    }

    handle {
        reverse_proxy localhost:8080 {
            health_uri /health
            health_interval 10s
        }
    }

    encode gzip zstd

    header {
        X-Frame-Options "SAMEORIGIN"
        X-Content-Type-Options "nosniff"
        Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
        -Server
    }

    @blocked {
        path /.* /composer.* /var/* /vendor/* /.rr.yaml /worker.php
    }
    respond @blocked 404

    log {
        output file /var/log/caddy/infinri.log
        format json
    }
}

www.yourdomain.com {
    redir https://yourdomain.com{uri} permanent
}
```

```bash
# Test and reload
caddy validate --config /etc/caddy/Caddyfile
systemctl reload caddy
```

### Step 7: Configure Firewall

```bash
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 22/tcp
ufw enable
```

### Step 8: Point DNS

Add these records at your domain registrar:
```
A     @      your-server-ip
A     www    your-server-ip
```

Wait 5-10 minutes for propagation. Caddy will automatically obtain SSL certificates.

---

## Maintenance

### View Logs

```bash
# RoadRunner logs
journalctl -u infinri-rr -f
tail -f /var/log/infinri/roadrunner.log

# Caddy logs
journalctl -u caddy -f
tail -f /var/log/caddy/infinri.log

# Application logs
tail -f /var/www/infinri/var/log/application.log
```

### Restart Services

```bash
# Restart RoadRunner (graceful reload)
systemctl reload infinri-rr

# Hard restart
systemctl restart infinri-rr

# Restart Caddy
systemctl reload caddy
```

### Deploy Updates

```bash
cd /var/www/infinri

# Pull changes
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
php bin/console cache:clear

# Reload RoadRunner (picks up new code)
systemctl reload infinri-rr
```

### Health Checks

```bash
# RoadRunner health
curl http://localhost:2114/health

# Prometheus metrics
curl http://localhost:2112/metrics

# Application health
curl https://yourdomain.com/health
```

### Monitor Performance

```bash
# Service status
systemctl status infinri-rr
systemctl status caddy

# Resource usage
htop
free -h
df -h

# RoadRunner workers
rr workers -c /var/www/infinri/.rr.yaml
```

---

## Performance Verification

### Test Response Time
```bash
# Should be < 50ms for dynamic pages
curl -w "%{time_total}s\n" -o /dev/null -s https://yourdomain.com
```

### Test Compression
```bash
curl -H "Accept-Encoding: gzip" -I https://yourdomain.com
# Should see: Content-Encoding: gzip
```

### Test Cache Headers
```bash
curl -I https://yourdomain.com/assets/base/css/variables.css
# Should see: Cache-Control: public, max-age=31536000, immutable
```

### Test SSL
```bash
curl -I https://yourdomain.com
# Should see: HTTP/2 200
# Should see: strict-transport-security header
```

### Lighthouse Scores
Expected scores with RoadRunner:
- **Performance**: 95+
- **Accessibility**: 95+
- **Best Practices**: 100
- **SEO**: 95+

---

## RoadRunner vs PHP-FPM

| Metric | PHP-FPM | RoadRunner |
|--------|---------|------------|
| Request latency | 20-50ms | 2-10ms |
| Memory per worker | 20-50MB | 10-20MB |
| Bootstrap cost | Every request | Once |
| Concurrent requests | Spawn processes | Thread pool |
| File watching | No | Yes (dev) |
| Health checks | External | Built-in |

**Why RoadRunner?**
- 5-10x faster response times
- Lower memory usage
- Built-in metrics (Prometheus)
- Graceful restarts without dropping requests
- No need for PHP-FPM configuration

---

## Troubleshooting

### RoadRunner won't start
```bash
# Check logs
journalctl -u infinri-rr -n 50

# Verify config
rr serve -c .rr.yaml -d  # dry run

# Check worker.php
php worker.php  # should wait for input
```

### 502 Bad Gateway
```bash
# Check if RoadRunner is running
systemctl status infinri-rr

# Check if port is listening
ss -tlnp | grep 8080
```

### Memory issues
```bash
# Reduce workers in .rr.yaml
# Edit: num_workers: 2

# Or set via environment
export RR_NUM_WORKERS=2
systemctl restart infinri-rr
```

### Permission denied
```bash
# Fix ownership
chown -R www-data:www-data /var/www/infinri
chmod -R 775 /var/www/infinri/var
```

---

*Last Updated: 2025-11-29*
