# Operations Guide

This guide covers deployment, monitoring, maintenance, and troubleshooting for the Infinri framework.

## Table of Contents

1. [Deployment](#deployment)
2. [Environment Configuration](#environment-configuration)
3. [Performance Optimization](#performance-optimization)
4. [Monitoring & Health Checks](#monitoring--health-checks)
5. [Queue Workers](#queue-workers)
6. [Cache Management](#cache-management)
7. [Logging](#logging)
8. [Backup & Recovery](#backup--recovery)
9. [Troubleshooting](#troubleshooting)
10. [Security Checklist](#security-checklist)

---

## Deployment

### Prerequisites

- PHP 8.2+
- Composer
- Redis (for cache/session/queue)
- MySQL/PostgreSQL/SQLite
- Web server (Nginx/Apache)

### Deployment Steps

```bash
# 1. Clone/upload application
git clone your-repo.git /var/www/app
cd /var/www/app

# 2. Install dependencies (no dev)
composer install --no-dev --optimize-autoloader

# 3. Set permissions
chmod -R 755 var/
chmod -R 755 pub/assets/

# 4. Configure environment
cp .env.example .env
# Edit .env with production values

# 5. Build caches
php bin/console cache:build

# 6. Run migrations
php bin/console migrate

# 7. Verify installation
php bin/console health:check
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/app/pub;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml;

    # Static assets
    location /assets/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Main handler
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Timeouts
        fastcgi_read_timeout 60s;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
    }

    # Block sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ ^/(var|app|config|vendor)/ {
        deny all;
    }
}
```

### PHP-FPM Configuration

```ini
; /etc/php/8.2/fpm/pool.d/app.conf
[app]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm.sock

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Environment
env[APP_ENV] = production
```

---

## Environment Configuration

### Production .env

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password
REDIS_DB=0

# Drivers
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Session
SESSION_LIFETIME=7200

# Logging
LOG_LEVEL=warning
LOG_CHANNEL=file

# Security
CSRF_ENABLED=true
RATE_LIMIT_ENABLED=true
```

### Environment Variables Reference

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Environment (production/staging/development) | development |
| `APP_DEBUG` | Enable debug mode | false |
| `CACHE_DRIVER` | Cache backend (file/redis/array) | file |
| `SESSION_DRIVER` | Session backend (file/redis) | file |
| `QUEUE_CONNECTION` | Queue backend (sync/redis) | sync |
| `LOG_LEVEL` | Minimum log level | debug |
| `REDIS_PREFIX` | Redis key prefix | infinri: |

---

## Performance Optimization

### 1. Enable OPcache

```ini
; /etc/php/8.2/fpm/conf.d/10-opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  ; Disable in production
opcache.save_comments=1
opcache.enable_file_override=1
```

### 2. Build Framework Caches

```bash
# Build all caches
php bin/console cache:build

# Individual caches
php bin/console config:cache    # Configuration
php bin/console route:cache     # Routes
php bin/console view:cache      # Templates
php bin/console container:cache # DI container
```

### 3. Enable Preloading (PHP 8.2+)

```ini
; php.ini
opcache.preload=/var/www/app/var/cache/preload.php
opcache.preload_user=www-data
```

Generate preload file:
```bash
php bin/console preload:generate
```

### 4. Redis Optimization

```bash
# redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
tcp-keepalive 300
```

### 5. Database Optimization

```bash
# Enable query caching (application level)
php bin/console db:optimize

# Check slow queries
php bin/console db:slow-log
```

---

## Monitoring & Health Checks

### Health Endpoint

The framework provides a built-in health check endpoint:

```
GET /_health
```

Response:
```json
{
  "status": "healthy",
  "timestamp": "2025-11-29T10:00:00.000000+00:00",
  "app": {
    "name": "Infinri",
    "version": "0.1.0",
    "environment": "production"
  },
  "system": {
    "php_version": "8.2.0",
    "memory_usage_mb": 24.5,
    "memory_usage_percent": 19.1
  },
  "database": {
    "status": "connected",
    "driver": "mysql"
  },
  "redis": {
    "status": "connected",
    "version": "7.0.0",
    "memory_used": "1.2M"
  },
  "queue": {
    "status": "active",
    "driver": "redis",
    "pending": 5,
    "failed": 0
  },
  "cache": {
    "status": "active",
    "driver": "redis"
  }
}
```

### Status Codes

| Status | HTTP Code | Meaning |
|--------|-----------|---------|
| healthy | 200 | All systems operational |
| degraded | 200 | Non-critical issues |
| critical | 503 | System unavailable |

### Kubernetes Liveness/Readiness

```yaml
livenessProbe:
  httpGet:
    path: /_health
    port: 80
  initialDelaySeconds: 10
  periodSeconds: 30

readinessProbe:
  httpGet:
    path: /_health
    port: 80
  initialDelaySeconds: 5
  periodSeconds: 10
```

### Prometheus Metrics

```
GET /_metrics
```

Requires API key in production:
```
GET /_metrics?key=your_metrics_key
```

---

## Queue Workers

### Starting Workers

```bash
# Single worker
php bin/console queue:work

# With options
php bin/console queue:work \
    --queue=default,emails \
    --max-jobs=1000 \
    --max-time=3600 \
    --memory=256 \
    --sleep=3
```

### Supervisor Configuration

```ini
; /etc/supervisor/conf.d/infinri-worker.conf
[program:infinri-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/app/bin/console queue:work --max-jobs=1000 --max-time=3600
directory=/var/www/app
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/app/var/log/worker.log
stopwaitsecs=60
```

```bash
# Apply configuration
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start infinri-worker:*
```

### Queue Management

```bash
# Check queue status
php bin/console queue:status

# Retry failed jobs
php bin/console queue:retry --all

# Flush queue
php bin/console queue:flush --failed
```

---

## Cache Management

### Clear Caches

```bash
# Clear all caches
php bin/console cache:clear

# Clear specific caches
php bin/console cache:clear --config
php bin/console cache:clear --routes
php bin/console cache:clear --views
php bin/console cache:clear --data

# Rebuild after clearing
php bin/console cache:build
```

### Cache Warming

```bash
# Warm caches after deployment
php bin/console cache:warm
```

### Redis Cache Commands

```bash
# View cache stats
redis-cli INFO stats

# Clear application cache
redis-cli KEYS "infinri:cache:*" | xargs redis-cli DEL

# Monitor cache activity
redis-cli MONITOR
```

---

## Logging

### Log Locations

| Log | Path | Description |
|-----|------|-------------|
| Application | `var/log/app.log` | Main application log |
| Error | `var/log/error.log` | Errors and exceptions |
| Queue | `var/log/worker.log` | Queue worker output |
| Access | Nginx/Apache | HTTP access logs |

### Log Levels

Configure in `.env`:
```bash
LOG_LEVEL=warning  # debug, info, notice, warning, error, critical, alert, emergency
```

### Log Rotation

```bash
# /etc/logrotate.d/infinri
/var/www/app/var/log/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 0640 www-data www-data
}
```

### Viewing Logs

```bash
# Tail application log
tail -f var/log/app.log

# Search for errors
grep -i error var/log/app.log | tail -100

# JSON log parsing
cat var/log/app.log | jq 'select(.level == "error")'
```

---

## Backup & Recovery

### Database Backup

```bash
# MySQL
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql

# PostgreSQL
pg_dump -U user database > backup_$(date +%Y%m%d).sql

# Automated backup script
#!/bin/bash
BACKUP_DIR=/backups
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete
```

### Redis Backup

```bash
# Trigger RDB snapshot
redis-cli BGSAVE

# Copy dump file
cp /var/lib/redis/dump.rdb /backups/redis_$(date +%Y%m%d).rdb
```

### Application Files

```bash
# Backup uploads and user content
tar -czf /backups/uploads_$(date +%Y%m%d).tar.gz /var/www/app/pub/uploads

# Backup configuration
tar -czf /backups/config_$(date +%Y%m%d).tar.gz /var/www/app/.env
```

### Recovery

```bash
# Restore database
mysql -u user -p database < backup.sql

# Restore Redis
redis-cli SHUTDOWN NOSAVE
cp backup.rdb /var/lib/redis/dump.rdb
redis-server

# Rebuild caches after restore
php bin/console cache:clear
php bin/console cache:build
```

---

## Troubleshooting

### Common Issues

#### 500 Internal Server Error

1. Check PHP error log: `tail -f /var/log/php8.2-fpm.log`
2. Enable debug mode temporarily: `APP_DEBUG=true`
3. Check file permissions: `ls -la var/`
4. Verify .env exists and is readable

#### Redis Connection Failed

```bash
# Test connection
redis-cli -h $REDIS_HOST -p $REDIS_PORT ping

# Check if Redis is running
systemctl status redis

# Check firewall
sudo ufw status
```

#### Queue Jobs Not Processing

```bash
# Check worker status
supervisorctl status infinri-worker:*

# View worker logs
tail -f var/log/worker.log

# Check queue size
php bin/console queue:status

# Test job dispatch
php bin/console queue:test
```

#### Slow Performance

```bash
# Check OPcache status
php -i | grep opcache

# Check memory usage
free -m

# Check PHP-FPM status
curl http://localhost/fpm-status

# Profile with Blackfire/XHProf
```

### Debug Commands

```bash
# Application info
php bin/console about

# Environment check
php bin/console env:check

# Test database connection
php bin/console db:test

# Test Redis connection
php bin/console redis:test

# Clear and rebuild everything
php bin/console cache:clear && php bin/console cache:build
```

---

## Security Checklist

### Pre-Deployment

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Strong database password
- [ ] Redis password set
- [ ] `.env` not in version control
- [ ] `var/` not web-accessible
- [ ] HTTPS enforced
- [ ] CSRF protection enabled
- [ ] Rate limiting enabled

### Server Hardening

- [ ] Firewall configured (only 80/443 open)
- [ ] SSH key authentication only
- [ ] Fail2ban installed
- [ ] Regular security updates
- [ ] PHP `expose_php = Off`
- [ ] PHP `display_errors = Off`

### Application Security

- [ ] All inputs validated
- [ ] SQL queries use prepared statements
- [ ] XSS prevention (output escaping)
- [ ] CSRF tokens on forms
- [ ] Security headers configured
- [ ] Dependency audit passed

### Monitoring

- [ ] Error alerting configured
- [ ] Uptime monitoring
- [ ] Log aggregation
- [ ] Intrusion detection

### Regular Maintenance

```bash
# Weekly: Check for vulnerabilities
composer audit

# Weekly: Review error logs
grep -c ERROR var/log/app.log

# Monthly: Update dependencies
composer update --dry-run

# Monthly: Review access logs
goaccess /var/log/nginx/access.log
```
