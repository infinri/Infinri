# Deployment Guide

Complete deployment and configuration guide for production and local development environments.

## Prerequisites

**Required Software**
- PHP 8.4 with extensions: fpm, cli, mbstring, xml, curl, zip, opcache
- Caddy 2.x web server
- Composer for PHP dependencies
- Node.js and npm for asset building

**Required Accounts**
- Brevo account for email API (free tier: 300 emails/day)
- Domain name (for production)
- Server or VPS (Digital Ocean, Linode, etc.)

---

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

### 4. Initial Project Setup
```bash
# Clone repository
git clone https://github.com/infinri/Portfolio.git
cd Portfolio

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
nano .env  # Edit with your settings
```

### 5. Configure Environment Variables

Edit `.env` with your settings:

**Required Brevo Email Settings**
```env
BREVO_API_KEY=your-brevo-api-key-here
BREVO_SENDER_EMAIL=noreply@yourdomain.com
BREVO_SENDER_NAME=Infinri Portfolio
BREVO_RECIPIENT_EMAIL=your-email@example.com
BREVO_RECIPIENT_NAME=Your Name
```

**Why Brevo?** Digital Ocean blocks SMTP port 587, so we use Brevo's API (HTTPS port 443) instead.

To get your Brevo API key:
1. Sign up at https://www.brevo.com (free tier: 300 emails/day)
2. Go to Settings → SMTP & API → API Keys
3. Create a new API key
4. Verify your sender domain at Settings → Senders & IP

**Optional Settings**
```env
APP_ENV=development
APP_DEBUG=true
CSRF_ENABLED=true
HTTPS_ONLY=false  # Set true in production
SITE_URL=http://localhost:8080
```

### 6. Build Assets and Setup
```bash
# Build and minify assets, set permissions, clear cache
composer setup:upgrade

# Or run steps individually:
npm run build              # Build assets
composer assets:publish    # Copy to public directory
```

### 7. Customize Services (Optional)

Edit `config/services.php` to customize the contact form dropdown:

```php
return [
    'general' => 'General Inquiry',
    'your-service' => 'Your Service Name ($price)',
    'other' => 'Other / Not Sure Yet',
];
```

Changes take effect immediately. See `config/README.md` for examples.

### 8. Start Development Server
```bash
caddy run
```

Visit: http://localhost:8080

### 9. Stop Caddy
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
rsync -avz --exclude 'var/' --exclude '.git/' --exclude 'node_modules/' \
    /path/to/Portfolio/ root@your-droplet-ip:/var/www/portfolio/

# SSH into server and install dependencies
ssh root@your-droplet-ip
cd /var/www/portfolio

# Install Composer if not already installed
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js and npm
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install application dependencies
composer install --no-dev --optimize-autoloader
npm install --production

# Configure environment
cp .env.example .env
nano .env  # Set production values (HTTPS_ONLY=true, APP_ENV=production)

# Build assets and setup
composer setup:upgrade

# Set permissions
sudo chown -R caddy:caddy /var/www/portfolio
sudo chmod -R 755 /var/www/portfolio
sudo chmod -R 775 /var/www/portfolio/var
sudo chmod 600 /var/www/portfolio/.env
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

### Step 7: Configure Security

**Set Production Environment Variables**
```bash
nano /var/www/portfolio/.env
```

Set these for production:
```env
APP_ENV=production
APP_DEBUG=false
HTTPS_ONLY=true
CSRF_ENABLED=true
SITE_URL=https://yourdomain.com
```

**Verify Rate Limiting**
Rate limiting cache directory should be writable:
```bash
sudo chmod 775 /var/www/portfolio/var/cache
```

Contact form is protected by:
- CSRF token verification
- Rate limiting (5 attempts per 5 minutes per IP)
- Honeypot anti-spam field
- Input validation and sanitization

**Configure Session Security**
```bash
sudo chmod 770 /var/www/portfolio/var/sessions
sudo chown caddy:www-data /var/www/portfolio/var/sessions
```

### Step 8: Verify Deployment

Visit your domain - Caddy will automatically:
- Get SSL certificate from Let's Encrypt
- Enable HTTP/2 and HTTP/3
- Enable compression
- Set security headers

Test the contact form to ensure emails are delivered via Brevo API:
```bash
php tests/manual-email-test.php
```

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
# Caddy logs
sudo journalctl -u caddy -f
# or
sudo tail -f /var/log/caddy/portfolio.log

# Application logs
tail -f /var/www/portfolio/var/log/application.log

# PHP-FPM logs
sudo tail -f /var/log/php8.4-fpm.log
```

### Update Application Code
```bash
# From local machine - sync files
rsync -avz --exclude 'var/' --exclude 'node_modules/' \
    /path/to/Portfolio/ root@your-droplet-ip:/var/www/portfolio/

# On server - rebuild assets and clear cache
ssh root@your-droplet-ip
cd /var/www/portfolio
composer install --no-dev --optimize-autoloader
npm run build
composer setup:upgrade
sudo systemctl reload php8.4-fpm
```

### Clear Rate Limit Cache
```bash
# If needed to reset rate limits
rm /var/www/portfolio/var/cache/rate_limits.json
```

### Reload Services
```bash
# Reload Caddy configuration
sudo systemctl reload caddy

# Restart PHP-FPM (after code changes)
sudo systemctl restart php8.4-fpm
```

### Monitor Performance
```bash
# Check Caddy status
sudo systemctl status caddy

# Check PHP-FPM status
sudo systemctl status php8.4-fpm

# Check memory usage
free -h

# Check disk usage
df -h
```

---
