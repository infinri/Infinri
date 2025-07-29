# Redis Setup Guide for Infinri Framework

This guide provides the commands needed to install Redis dependencies and resolve the IDE errors in SemanticMesh.php.

## Required Dependencies

The Infinri Framework requires:
1. Redis PHP extension (for Redis and RedisCluster classes)
2. PSR-3 logging interface (for LoggerInterface)
3. Optional: Predis library as alternative Redis client

## Installation Commands

### 1. Install Redis PHP Extension

**On Ubuntu/Debian:**
```bash
# Install Redis server and PHP extension
sudo apt update
sudo apt install redis-server php-redis

# Restart PHP-FPM (if using)
sudo systemctl restart php8.4-fpm
```

**On CentOS/RHEL:**
```bash
# Install Redis server and PHP extension
sudo yum install redis php-redis

# Start Redis service
sudo systemctl start redis
sudo systemctl enable redis
```

**Using PECL (alternative method):**
```bash
# Install via PECL
sudo pecl install redis

# Add to php.ini
echo "extension=redis" | sudo tee -a /etc/php/8.4/cli/php.ini
```

### 2. Install Composer Dependencies

Create a `composer.json` file in the project root:

```json
{
    "name": "infinri/infinri-framework",
    "description": "Digital Consciousness Platform with Swarm Framework",
    "type": "project",
    "require": {
        "php": ">=8.4",
        "psr/log": "^3.0",
        "predis/predis": "^2.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "Infinri\\": "src/"
        }
    },
    "config": {
        "optimize-autoloader": true,
        "sort-packages": true
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

Then run:
```bash
# Install dependencies
composer install

# Generate autoloader
composer dump-autoload
```

### 3. Verify Installation

Create a test script to verify Redis is working:

```php
<?php
// test_redis.php
try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $redis->set('test_key', 'Hello Infinri!');
    echo "Redis test: " . $redis->get('test_key') . "\n";
    echo "Redis extension is working!\n";
} catch (Exception $e) {
    echo "Redis error: " . $e->getMessage() . "\n";
}
?>
```

Run the test:
```bash
php test_redis.php
```

### 4. Configure Redis for Production

Edit `/etc/redis/redis.conf`:

```conf
# Performance settings for Infinri Framework
maxmemory 2gb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000

# Security settings
requirepass your_secure_password_here
bind 127.0.0.1

# Cluster settings (if using Redis Cluster)
cluster-enabled yes
cluster-config-file nodes.conf
cluster-node-timeout 5000
```

Restart Redis:
```bash
sudo systemctl restart redis
```

## Expected Results

After installation, the IDE errors should be resolved:
- ✅ `Redis` class will be recognized
- ✅ `RedisCluster` class will be recognized  
- ✅ `RedisException` class will be recognized
- ✅ `Psr\Log\LoggerInterface` will be recognized

## Troubleshooting

**If Redis extension is not loaded:**
```bash
# Check if extension is loaded
php -m | grep redis

# Check PHP configuration
php --ini

# Verify extension path
php -i | grep extension_dir
```

**If composer dependencies fail:**
```bash
# Clear composer cache
composer clear-cache

# Update dependencies
composer update

# Reinstall dependencies
rm -rf vendor composer.lock
composer install
```

## Framework Integration

Once Redis is installed, the SemanticMesh will provide:s
- O(1) mesh operations via Redis Cluster
- Namespace partitioning for multi-tenant support
- ACL enforcement for security
- Snapshot isolation for consistency
- Event publishing for reactive patterns

The Infinri Framework is now ready for production deployment with full Redis 7.x support.
