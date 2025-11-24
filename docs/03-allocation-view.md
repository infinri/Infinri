# Allocation View - Deployment & Infrastructure

**Type:** Deployment View  
**Purpose:** Show how software maps to hardware and execution environment  
**Audience:** DevOps, system administrators, cloud engineers

---

## 📐 Overview

This view shows **physical deployment** - how Infinri runs on DigitalOcean Droplets, file system layout, resource allocation, and scaling strategy.

---

## 🖥️ Deployment Topology (Single Droplet - Default)

```
┌─────────────────────────────────────────────────────────────┐
│              DigitalOcean Droplet (Ubuntu 22.04)             │
│                  2 vCPU, 4GB RAM, 80GB SSD                   │
│                      $24/month (Basic)                        │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌────────────────────────────────────────────────────┐     │
│  │  Caddy Web Server (Port 80, 443)                   │     │
│  │  - Automatic TLS (Let's Encrypt)                   │     │
│  │  - Static file serving (/pub/assets/)              │     │
│  │  - Reverse proxy to RoadRunner                     │     │
│  │  - HTTP/2 + gzip compression                       │     │
│  └───────────────────┬────────────────────────────────┘     │
│                      │                                        │
│                      ↓ Proxy :8080                            │
│  ┌────────────────────────────────────────────────────┐     │
│  │  RoadRunner Application Server (Port 8080)         │     │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐         │     │
│  │  │Worker #1 │  │Worker #2 │  │Worker #N │         │     │
│  │  │  PHP 8.4 │  │  PHP 8.4 │  │  PHP 8.4 │         │     │
│  │  │ 128MB    │  │ 128MB    │  │ 128MB    │         │     │
│  │  └──────────┘  └──────────┘  └──────────┘         │     │
│  │                                                     │     │
│  │  Pool: 4 workers, 1000 req/worker, 1h lifetime    │     │
│  └───────────────────┬────────────────────────────────┘     │
│                      │                                        │
│                      ↓ PDO Connection                         │
│  ┌────────────────────────────────────────────────────┐     │
│  │  PostgreSQL 16 (Local)                             │     │
│  │  - Port: 5432 (localhost only)                     │     │
│  │  - Max connections: 50                             │     │
│  │  - Shared buffers: 1GB                             │     │
│  │  - Effective cache: 3GB                            │     │
│  └────────────────────────────────────────────────────┘     │
│                                                               │
│  ┌────────────────────────────────────────────────────┐     │
│  │  Redis (Optional - Cache)                          │     │
│  │  - Port: 6379 (localhost only)                     │     │
│  │  - Max memory: 512MB                               │     │
│  │  - Eviction: allkeys-lru                           │     │
│  └────────────────────────────────────────────────────┘     │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

**Resource Allocation:**
- **Caddy:** ~50MB RAM, ~5% CPU
- **RoadRunner:** ~512MB RAM (4 workers × 128MB), ~30-50% CPU
- **PostgreSQL:** ~1.5GB RAM, ~20-30% CPU
- **Redis:** ~512MB RAM, ~5% CPU
- **System:** ~512MB RAM reserved
- **Available:** ~1GB RAM for spikes

---

## 🗂️ File System Allocation

### Directory Structure on Droplet

```
/var/www/infinri/                           # Application root
├── app/                                     # Application code
│   ├── base/                                # 2MB
│   │   ├── core/                            # Framework core
│   │   ├── helpers/                         # Helper classes
│   │   └── view/                            # Base templates
│   └── modules/                             # 5MB
│       ├── about/
│       ├── blog/
│       ├── contact/
│       └── ...
├── pub/                                     # Public root
│   ├── assets/                              # 10MB (CSS/JS)
│   │   ├── base/
│   │   ├── frontend/
│   │   └── modules/
│   ├── uploads/                             # 500MB (user uploads)
│   └── index.php                            # Entry point
├── var/                                     # Runtime data
│   ├── cache/                               # 100MB (file cache)
│   ├── log/                                 # 50MB (application logs)
│   ├── sessions/                            # 20MB (PHP sessions)
│   └── tmp/                                 # 50MB (temp files)
├── vendor/                                  # 20MB (Composer packages)
├── config/                                  # <1MB (configuration)
├── bin/                                     # <1MB (CLI tools)
└── .env                                     # Environment config

/etc/caddy/Caddyfile                        # Caddy configuration
/etc/systemd/system/roadrunner.service      # RoadRunner service
/var/log/caddy/                             # Caddy logs (100MB)
/var/log/roadrunner/                        # RoadRunner logs (50MB)
```

**Disk Usage:**
- Application: ~40MB
- Dependencies: ~20MB
- Assets: ~10MB
- Cache: ~100MB
- Logs: ~200MB
- Uploads: ~500MB
- PostgreSQL data: ~2GB
- **Total:** ~3GB used, ~77GB free

---

## 🔧 Process Mapping

### System Processes

| Process | User | Memory | CPU | Priority |
|---------|------|--------|-----|----------|
| `caddy` | caddy | 50MB | 5% | Normal |
| `roadrunner` | www-data | 512MB | 40% | Normal |
| `postgres` | postgres | 1.5GB | 25% | High |
| `redis-server` | redis | 512MB | 5% | Normal |
| `systemd` | root | 100MB | 1% | High |

**Total System Load:**
- **Memory:** ~2.7GB / 4GB (68% used)
- **CPU:** ~76% / 200% (38% used, dual-core)
- **Disk I/O:** Low (~10 MB/s peak)
- **Network:** Low (~5 Mbps peak)

---

## 🌐 Network Configuration

### Port Allocation

| Port | Service | Protocol | Access | Purpose |
|------|---------|----------|--------|---------|
| 80 | Caddy | HTTP | Public | HTTP redirect to 443 |
| 443 | Caddy | HTTPS | Public | Main application entry |
| 8080 | RoadRunner | HTTP | Localhost | Application server |
| 5432 | PostgreSQL | TCP | Localhost | Database |
| 6379 | Redis | TCP | Localhost | Cache |
| 22 | SSH | TCP | Admin IP | Server management |

**Firewall Rules (UFW):**
```bash
# Allow HTTP/HTTPS
ufw allow 80/tcp
ufw allow 443/tcp

# Allow SSH (admin IP only)
ufw allow from 203.0.113.0/24 to any port 22

# Deny all other incoming
ufw default deny incoming
ufw default allow outgoing
```

---

## 📊 Resource Limits

### Per-Process Limits

**RoadRunner Workers:**
```yaml
# .rr.yaml
server:
  command: "php pub/index.php"

http:
  address: "0.0.0.0:8080"
  pool:
    num_workers: 4
    max_jobs: 1000
    allocate_timeout: 60s
    destroy_timeout: 60s
  
  middleware: ["gzip"]
  
  uploads:
    forbid: [".php", ".exe", ".bat"]
  
limits:
  services:
    http:
      interval: 1s
      max_memory: 128
      ttl: 3600s
```

**PostgreSQL:**
```conf
# /etc/postgresql/16/main/postgresql.conf
max_connections = 50
shared_buffers = 1GB
effective_cache_size = 3GB
work_mem = 16MB
maintenance_work_mem = 256MB
wal_buffers = 16MB
```

**Redis:**
```conf
# /etc/redis/redis.conf
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

---

## 🔄 Scaling Strategy

### Vertical Scaling (Single Droplet)

**Current: $24/mo (2 vCPU, 4GB RAM)**

**Upgrade Path:**
1. **$48/mo** - 4 vCPU, 8GB RAM → 2x capacity
2. **$96/mo** - 8 vCPU, 16GB RAM → 4x capacity
3. **$192/mo** - 16 vCPU, 32GB RAM → 8x capacity

**When to scale:**
- Memory usage > 85% sustained
- CPU usage > 70% sustained
- Response time > 200ms average
- Error rate > 1%

---

### Horizontal Scaling (Multi-Droplet)

```
                    ┌─────────────┐
                    │  CloudFlare │  ← CDN + DDoS protection
                    └──────┬──────┘
                           │
                    ┌──────▼──────┐
                    │Load Balancer│  ← DigitalOcean LB
                    └──────┬──────┘
                           │
        ┌──────────────────┼──────────────────┐
        ↓                  ↓                   ↓
┌───────────────┐  ┌───────────────┐  ┌───────────────┐
│  Droplet #1   │  │  Droplet #2   │  │  Droplet #N   │
│  (App Server) │  │  (App Server) │  │  (App Server) │
└───────┬───────┘  └───────┬───────┘  └───────┬───────┘
        │                  │                   │
        └──────────────────┼───────────────────┘
                           │
                    ┌──────▼──────┐
                    │ PostgreSQL  │  ← Managed Database
                    │   Cluster   │
                    └─────────────┘
```

**Shared Services:**
- **Database:** DigitalOcean Managed PostgreSQL ($15/mo+)
- **Cache:** DigitalOcean Managed Redis ($15/mo+)
- **Storage:** DigitalOcean Spaces ($5/mo for 250GB)
- **Load Balancer:** $12/mo

**Cost (3 app servers):**
- App servers: 3 × $24 = $72/mo
- Database: $15/mo
- Redis: $15/mo
- Load balancer: $12/mo
- **Total: ~$114/mo** (vs $24/mo single)

**Capacity:**
- **Requests:** ~150k/day → ~450k/day (3x)
- **Concurrent users:** ~50 → ~150 (3x)
- **Redundancy:** 99.99% uptime (LB failover)

---

## 🚀 Deployment Process

### Deployment Topology

```
Developer Workstation
       │
       ↓ git push
┌─────────────┐
│   GitHub    │
└──────┬──────┘
       │
       ↓ webhook (optional)
┌─────────────┐
│GitHub Actions│  ← CI/CD pipeline
└──────┬──────┘
       │
       ↓ SSH deploy
┌─────────────┐
│   Droplet   │
└──────┬──────┘
       │
       ↓ deployment script
┌─────────────────────────────┐
│ 1. git pull                  │
│ 2. composer install --no-dev │
│ 3. npm run build             │
│ 4. php bin/console assets:pub│
│ 5. php bin/console cache:clear│
│ 6. systemctl reload roadrunner│
└─────────────────────────────┘
```

**Deployment Time:** ~30 seconds  
**Downtime:** 0 seconds (graceful reload)

### Zero-Downtime Deployment

```bash
#!/bin/bash
# deploy.sh

# 1. Maintenance mode (optional)
# touch var/maintenance.flag

# 2. Update code
git pull origin main

# 3. Install dependencies
composer install --no-dev --optimize-autoloader

# 4. Build assets
npm ci --production
npm run build

# 5. Publish assets
php bin/console assets:publish

# 6. Run migrations
php bin/console schema:upgrade

# 7. Clear caches
php bin/console cache:clear

# 8. Reload workers (graceful)
systemctl reload roadrunner

# 9. Remove maintenance mode
# rm var/maintenance.flag

echo "✓ Deployment complete"
```

**RoadRunner Graceful Reload:**
- Old workers finish current requests
- New workers start with new code
- No dropped connections
- ~1-2 second transition

---

## 💾 Backup Strategy

### Database Backups

**Automated (cron):**
```bash
# /etc/cron.daily/backup-postgres
#!/bin/bash
pg_dump -U backup infinri | gzip > /backups/postgres/infinri-$(date +%Y%m%d).sql.gz

# Retention: 7 daily, 4 weekly, 12 monthly
```

**Storage:**
- **Local:** `/backups/` (7 days)
- **Remote:** DigitalOcean Spaces (90 days)
- **Size:** ~50MB compressed

**Recovery Time Objective (RTO):** 15 minutes  
**Recovery Point Objective (RPO):** 24 hours

---

### File Backups

**Automated (DigitalOcean Snapshots):**
- **Frequency:** Weekly
- **Retention:** 4 weeks
- **Cost:** ~$1/mo per snapshot
- **Restore time:** 5-10 minutes

**Critical Files (borg backup):**
```bash
# /etc/cron.weekly/backup-files
borg create /backups/borg::$(date +%Y%m%d) \
  /var/www/infinri/pub/uploads \
  /var/www/infinri/.env \
  /etc/caddy \
  --exclude '*.log'
```

---

## 🔍 Monitoring & Health Checks

### Service Health Checks

**RoadRunner:**
```bash
# Prometheus metrics
curl http://localhost:2112/metrics

# Health endpoint
curl http://localhost:8080/health
```

**PostgreSQL:**
```bash
# Connection check
pg_isready -U postgres

# Slow query log
tail -f /var/log/postgresql/postgresql-16-main.log
```

**Caddy:**
```bash
# Access log
tail -f /var/log/caddy/access.log

# Error log
tail -f /var/log/caddy/error.log
```

### Alerting

**Alerts (via email/webhook):**
- CPU > 80% for 5 minutes
- Memory > 90% for 5 minutes
- Disk usage > 85%
- PostgreSQL connections > 40
- HTTP error rate > 5%
- Response time > 500ms (P95)

---

## 🎯 Performance Targets

### Response Time (P95)

| Endpoint Type | Target | Current |
|---------------|--------|---------|
| Static files | <50ms | ~10ms |
| Database read | <100ms | ~60ms |
| Database write | <200ms | ~120ms |
| API endpoint | <150ms | ~80ms |
| Full page | <300ms | ~150ms |

### Throughput

| Metric | Target | Current |
|--------|--------|---------|
| Requests/second | 100 | ~20 |
| Concurrent users | 50 | ~10 |
| Daily requests | 50k | ~5k |
| Daily uniques | 1k | ~200 |

### Resource Utilization

| Resource | Target | Current |
|----------|--------|---------|
| CPU | <70% | ~40% |
| Memory | <85% | ~68% |
| Disk I/O | <50MB/s | ~10MB/s |
| Network | <100Mbps | ~5Mbps |

---

## 🌍 Geographic Distribution (Future)

### Multi-Region Deployment

```
         ┌──────────────┐
         │  CloudFlare  │  ← Global CDN
         └──────┬───────┘
                │
    ┌───────────┼───────────┐
    │           │           │
    ↓           ↓           ↓
┌────────┐  ┌────────┐  ┌────────┐
│ NYC1   │  │ SFO3   │  │ LON1   │  ← Regional droplets
└────────┘  └────────┘  └────────┘
    │           │           │
    └───────────┼───────────┘
                ↓
         ┌──────────────┐
         │ PostgreSQL   │  ← Primary database
         │   Primary    │
         │   (NYC1)     │
         └──────────────┘
```

**Benefits:**
- Lower latency globally
- Geographic redundancy
- DDoS mitigation
- Compliance (data residency)

**Costs:**
- 3 regions × $24 = $72/mo (apps)
- Database replication: +$30/mo
- CDN: $5/mo
- **Total: ~$107/mo**

---

**Version:** 1.0  
**Last Updated:** November 24, 2025  
**Next Review:** After production deployment
