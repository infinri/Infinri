# Deployment Guide - Infinri Portfolio

## Local Development with Caddy

### 1. Install Caddy
```bash
# Ubuntu/Debian
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update
sudo apt install caddy

# macOS
brew install caddy

# Or download binary from: https://caddyserver.com/download
```

### 2. Install PHP 8.4 with FPM
```bash
# Ubuntu/Debian
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl

# macOS
brew install php@8.4
```

### 3. Start Caddy (Development)
```bash
# From project root (no sudo needed - uses port 8080)
caddy run

# Or in background
caddy start
```

**Note:** Local dev uses `./var/log/caddy.log` so no sudo is required. Port 8080 is unprivileged.

Visit: http://localhost:8080

### 4. Stop Caddy
```bash
caddy stop
```

---

## Production Deployment (Digital Ocean Droplet)

### Step 1: Prepare Server (Ubuntu 22.04/24.04)
```bash
# SSH into your droplet
ssh root@your-droplet-ip

# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.4 and extensions
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mbstring php8.4-xml \
    php8.4-curl php8.4-zip php8.4-opcache php8.4-intl

# Install Caddy
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update
sudo apt install caddy
```

### Step 2: Configure PHP-FPM for Performance
```bash
# Edit PHP-FPM pool config
sudo nano /etc/php/8.4/fpm/pool.d/www.conf

# Change these settings:
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

# Enable OPcache (edit php.ini)
sudo nano /etc/php/8.4/fpm/php.ini

# Add/uncomment these lines:
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

### Step 3: Deploy Application
```bash
# Create directory
sudo mkdir -p /var/www/portfolio
sudo chown -R caddy:caddy /var/www/portfolio

# Upload your files (from local machine)
rsync -avz --exclude 'var/' --exclude '.git/' \
    /path/to/Portfolio/ root@your-droplet-ip:/var/www/portfolio/

# Set permissions
sudo chown -R caddy:caddy /var/www/portfolio
sudo chmod -R 755 /var/www/portfolio
sudo chmod -R 775 /var/www/portfolio/var
```

### Step 4: Configure Caddy for Production
```bash
# Copy Caddyfile
sudo cp /var/www/portfolio/Caddyfile /etc/caddy/Caddyfile

# Edit for production
sudo nano /etc/caddy/Caddyfile

# Uncomment production block and replace:
# - yourdomain.com with your actual domain
# - /var/www/portfolio/pub with actual path
# - unix//run/php/php8.4-fpm.sock with correct socket path

# Test configuration
sudo caddy validate --config /etc/caddy/Caddyfile

# Reload Caddy (automatically gets SSL!)
sudo systemctl reload caddy
```

### Step 5: Configure Firewall
```bash
# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp  # SSH
sudo ufw enable
```

### Step 6: Point Domain DNS
In your domain registrar (e.g., Namecheap, Cloudflare):
```
A Record:  @ → your-droplet-ip
A Record:  www → your-droplet-ip
```

Wait 5-10 minutes for DNS propagation.

### Step 7: Verify
Visit your domain - Caddy will automatically:
- Get SSL certificate from Let's Encrypt
- Enable HTTP/2 and HTTP/3
- Enable compression
- Set security headers

---

## Performance Verification

### Test Compression
```bash
curl -H "Accept-Encoding: gzip" -I https://yourdomain.com/assets/base/css/variables.css
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
```

### Run Lighthouse
- Should score 95+ on Performance
- All compression/caching warnings gone
- Automatic HTTPS = 100 on Best Practices

---

## Maintenance

### View Logs
```bash
sudo journalctl -u caddy -f
# or
sudo tail -f /var/log/caddy/portfolio.log
```

### Reload Config
```bash
sudo systemctl reload caddy
```

### Update Application
```bash
# From local machine
rsync -avz --exclude 'var/' /path/to/Portfolio/ root@your-droplet-ip:/var/www/portfolio/
```

### Update PHP OPcache
```bash
# After code changes
sudo systemctl reload php8.4-fpm
```

---

## Performance Benchmarks (Expected)

With this setup, you should achieve:
- **Lighthouse Performance**: 95-100
- **LCP**: < 500ms
- **FID**: < 10ms
- **CLS**: < 0.1
- **Time to First Byte**: < 200ms
- **Page Load**: < 1 second

Caddy + PHP-FPM + OPcache = Blazing Fast! 🚀
