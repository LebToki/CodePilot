# CodePilot Deployment Guide

This guide provides instructions for deploying CodePilot to production environments.

## Prerequisites

- PHP 8.1+ with required extensions:
  - `curl`
  - `json`
  - `mbstring`
  - `fileinfo`
  - `session`
- Web server (Apache with mod_rewrite or Nginx)
- Composer (for PHP dependencies)
- Node.js (optional, for frontend build tools)

## Environment Configuration

### 1. Copy Environment File

```bash
cp .env.example .env
```

### 2. Configure Environment Variables

Edit `.env` with your production settings:

```env
# Application
APP_NAME=CodePilot
APP_ENV=production
APP_DEBUG=false
APP_VERSION=1.0.0

# Security
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=3600
MAX_FILE_SIZE=10485760

# Default Provider
DEFAULT_PROVIDER=deepseek
DEFAULT_MODEL=deepseek-chat

# API Keys (required for cloud providers)
DEEPSEEK_API_KEY=your_deepseek_key_here
GEMINI_API_KEY=your_gemini_key_here
HUGGINGFACE_API_KEY=your_hf_key_here

# Local Providers
OLLAMA_API_URL=http://localhost:11434/api

# Workspaces
WEB_WORKSPACE_PATH=/var/www/html
PLATFORM_WORKSPACE_PATH=/opt/projects

# Branding
DEVELOPER_NAME=Your Name
COMPANY_NAME=Your Company
COMPANY_URL=https://yourcompany.com
SUPPORT_EMAIL=support@yourcompany.com
```

## Web Server Configuration

### Apache Configuration

Create `.htaccess` in the `public/` directory:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Cache control for static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/x-javascript "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 month"
    ExpiresDefault "access plus 2 days"
</IfModule>
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/CodePilot/public;
    index index.php;

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Static assets caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
    }

    # Security: Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /(composer\.json|composer\.lock|\.env|\.git) {
        deny all;
    }
}
```

## Directory Permissions

Set proper permissions for the application:

```bash
# Set ownership (adjust user:group as needed)
sudo chown -R www-data:www-data /path/to/CodePilot

# Set directory permissions
find /path/to/CodePilot -type d -exec chmod 755 {} \;

# Set file permissions
find /path/to/CodePilot -type f -exec chmod 644 {} \;

# Make scripts executable
chmod +x /path/to/CodePilot/public/api/*.php

# Ensure data directory is writable
mkdir -p /path/to/CodePilot/data/logs
chmod -R 755 /path/to/CodePilot/data
```

## Database Setup (Optional)

CodePilot uses file-based storage by default, but you can configure a database for enhanced features:

```sql
CREATE DATABASE codepilot;
CREATE USER 'codepilot_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON codepilot.* TO 'codepilot_user'@'localhost';
FLUSH PRIVILEGES;
```

## SSL/HTTPS Configuration

### Let's Encrypt (Recommended)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx  # For Nginx
sudo apt install certbot python3-certbot-apache  # For Apache

# Obtain certificate
sudo certbot --nginx -d your-domain.com  # For Nginx
sudo certbot --apache -d your-domain.com  # For Apache

# Auto-renewal
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -
```

### Manual SSL Configuration

Update your web server configuration to use SSL certificates:

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    
    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
}
```

## Performance Optimization

### PHP Configuration

Update `php.ini` for production:

```ini
; Memory and execution limits
memory_limit = 256M
max_execution_time = 300
post_max_size = 10M
upload_max_filesize = 10M

; Security
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; OPcache for performance
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 60
```

### Caching

Enable Redis for caching:

```bash
# Install Redis
sudo apt install redis-server

# Configure PHP Redis extension
sudo apt install php-redis
```

Update configuration:

```env
CACHE_DRIVER=redis
CACHE_REDIS_HOST=127.0.0.1
CACHE_REDIS_PORT=6379
```

## Monitoring and Logging

### Log Rotation

Create logrotate configuration:

```bash
sudo nano /etc/logrotate.d/codepilot
```

```conf
/path/to/CodePilot/data/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload nginx
    endscript
}
```

### Health Checks

Create a health check endpoint:

```php
// public/health.php
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'extensions' => get_loaded_extensions(),
    'memory_usage' => memory_get_usage(true),
    'disk_free' => disk_free_space('/'),
];

echo json_encode($health);
```

### Monitoring Scripts

Create monitoring script:

```bash
#!/bin/bash
# monitor.sh

LOG_FILE="/var/log/codepilot_monitor.log"
APP_URL="https://your-domain.com/health.php"

check_app() {
    response=$(curl -s -o /dev/null -w "%{http_code}" $APP_URL)
    
    if [ $response -eq 200 ]; then
        echo "$(date): Application is healthy" >> $LOG_FILE
    else
        echo "$(date): Application is down (HTTP $response)" >> $LOG_FILE
        # Send alert (email, Slack, etc.)
    fi
}

check_disk() {
    usage=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ $usage -gt 90 ]; then
        echo "$(date): Disk usage is high: ${usage}%" >> $LOG_FILE
    fi
}

check_app
check_disk
```

Add to crontab:

```bash
# Check every 5 minutes
*/5 * * * * /path/to/monitor.sh
```

## Backup Strategy

### Database Backup

```bash
#!/bin/bash
# backup.sh

BACKUP_DIR="/backups/codepilot"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="codepilot"

mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u root -p $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Backup application files
tar -czf $BACKUP_DIR/app_backup_$DATE.tar.gz /path/to/CodePilot

# Keep only last 30 days
find $BACKUP_DIR -type f -mtime +30 -delete
```

### Automated Backups

```bash
# Add to crontab - daily at 2 AM
0 2 * * * /path/to/backup.sh
```

## Security Hardening

### Firewall Configuration

```bash
# Allow SSH, HTTP, HTTPS
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

### Fail2Ban Configuration

```bash
sudo apt install fail2ban

# Create jail configuration
sudo nano /etc/fail2ban/jail.local
```

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
port = http,https
logpath = /var/log/nginx/error.log

[php-url-fopen]
enabled = true
port = http,https
filter = php-url-fopen
logpath = /var/log/nginx/access.log
maxretry = 2
```

### Security Headers

Ensure your web server includes these headers:

```apache
# Apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' cdn.jsdelivr.net"
Header always set X-Permitted-Cross-Domain-Policies "none"
Header always set X-Download-Options "noopen"
```

## Deployment Checklist

- [ ] Environment variables configured
- [ ] Web server configured with SSL
- [ ] Directory permissions set correctly
- [ ] PHP extensions installed
- [ ] Database configured (if using)
- [ ] Caching enabled
- [ ] Log rotation configured
- [ ] Monitoring scripts in place
- [ ] Backup strategy implemented
- [ ] Security headers configured
- [ ] Firewall rules applied
- [ ] Fail2Ban configured
- [ ] Health checks working

## Troubleshooting

### Common Issues

1. **404 Errors**: Check web server rewrite rules
2. **Permission Denied**: Verify file/directory permissions
3. **API Keys Not Working**: Check environment variables
4. **Slow Performance**: Enable OPcache and Redis
5. **Memory Issues**: Increase PHP memory limit

### Debug Mode

For troubleshooting, enable debug mode temporarily:

```env
APP_DEBUG=true
APP_ENV=local
```

Remember to disable debug mode in production!

## Support

For additional support, check:
- [CodePilot Documentation](https://github.com/LebToki/CodePilot)
- [Issue Tracker](https://github.com/LebToki/CodePilot/issues)
- [Community Forum](https://github.com/LebToki/CodePilot/discussions)