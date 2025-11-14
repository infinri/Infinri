#!/bin/bash
# PHP-FPM Socket Permission Fix for Caddy
# This configures PHP-FPM socket to be accessible by your user

echo "=== Configuring PHP-FPM for Caddy ==="
echo ""

# Backup original config
sudo cp /etc/php/8.4/fpm/pool.d/www.conf /etc/php/8.4/fpm/pool.d/www.conf.backup
echo "✓ Backed up original config"

# Update socket permissions to allow group access
sudo sed -i 's/^;listen.mode = 0660/listen.mode = 0666/' /etc/php/8.4/fpm/pool.d/www.conf
echo "✓ Set socket permissions to 0666 (readable/writable by all)"

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
echo "✓ Restarted PHP-FPM"

# Verify
echo ""
echo "=== Verification ==="
if systemctl is-active --quiet php8.4-fpm; then
    echo "✓ PHP-FPM is running"
else
    echo "✗ PHP-FPM failed to start"
    exit 1
fi

if [ -S /run/php/php8.4-fpm.sock ]; then
    echo "✓ Socket exists: /run/php/php8.4-fpm.sock"
    ls -la /run/php/php8.4-fpm.sock
else
    echo "✗ Socket not found"
    exit 1
fi

echo ""
echo "=== Ready! ==="
echo "Now restart Caddy with: caddy run"
echo ""
