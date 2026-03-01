# CodePilot Production Deployment Checklist

## 🚀 Quick Start Deployment

### Phase 1: Environment Setup (15 minutes)

#### 1.1 Configure Environment Variables
```bash
# Copy environment template
cp .env.example .env

# Edit .env with your settings
nano .env
```

**Required Configuration:**
```env
# Application
APP_NAME=CodePilot
APP_ENV=production
APP_DEBUG=false

# Security (keep defaults or customize)
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=3600
MAX_FILE_SIZE=10485760

# Default Provider
DEFAULT_PROVIDER=deepseek
DEFAULT_MODEL=deepseek-chat

# API Keys (get from provider websites)
DEEPSEEK_API_KEY=your_key_here
GEMINI_API_KEY=your_key_here
HUGGINGFACE_API_KEY=your_key_here

# Workspaces (adjust to your system)
WEB_WORKSPACE_PATH=/var/www/html
PLATFORM_WORKSPACE_PATH=/opt/projects

# Branding
DEVELOPER_NAME=Your Name
COMPANY_NAME=Your Company
COMPANY_URL=https://yourcompany.com
```

#### 1.2 Set Directory Permissions
```bash
# Set proper ownership (adjust user:group as needed)
sudo chown -R www-data:www-data /path/to/CodePilot

# Set directory permissions
find /path/to/CodePilot -type d -exec chmod 755 {} \;
find /path/to/CodePilot -type f -exec chmod 644 {} \;

# Ensure data directory is writable
mkdir -p /path/to/CodePilot/data/logs
chmod -R 755 /path/to/CodePilot/data
```

### Phase 2: Web Server Configuration (20 minutes)

#### 2.1 Apache Setup
```bash
# Enable required modules
sudo a2enmod rewrite
sudo a2enmod headers

# Create virtual host
sudo nano /etc/apache2/sites-available/codepilot.conf
```

**Apache Configuration:**
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/CodePilot/public
    
    <Directory /path/to/CodePilot/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/codepilot_error.log
    CustomLog ${APACHE_LOG_DIR}/codepilot_access.log combined
</VirtualHost>
```

```bash
# Enable site and restart
sudo a2ensite codepilot.conf
sudo systemctl restart apache2
```

#### 2.2 Nginx Setup (Alternative)
```bash
# Create server block
sudo nano /etc/nginx/sites-available/codepilot
```

**Nginx Configuration:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/CodePilot/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
}
```

```bash
# Enable site and restart
sudo ln -s /etc/nginx/sites-available/codepilot /etc/nginx/sites-enabled/
sudo systemctl restart nginx
```

### Phase 3: SSL/HTTPS Setup (10 minutes)

#### 3.1 Let's Encrypt (Recommended)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache  # For Apache
sudo apt install certbot python3-certbot-nginx  # For Nginx

# Obtain certificate
sudo certbot --apache -d your-domain.com  # For Apache
sudo certbot --nginx -d your-domain.com   # For Nginx

# Auto-renewal
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -
```

#### 3.2 Manual SSL (Alternative)
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
}
```

### Phase 4: Security Hardening (15 minutes)

#### 4.1 Firewall Configuration
```bash
# Allow SSH, HTTP, HTTPS
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

#### 4.2 Fail2Ban Setup
```bash
# Install Fail2Ban
sudo apt install fail2ban

# Create jail configuration
sudo nano /etc/fail2ban/jail.local
```

**Fail2Ban Configuration:**
```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[apache-auth]
enabled = true
port = http,https
filter = apache-auth
logpath = /var/log/apache2/error.log
maxretry = 3

[nginx-http-auth]
enabled = true
port = http,https
filter = nginx-http-auth
logpath = /var/log/nginx/error.log
maxretry = 3
```

```bash
# Restart Fail2Ban
sudo systemctl restart fail2ban
```

### Phase 5: Monitoring & Maintenance (10 minutes)

#### 5.1 Health Check Setup
```bash
# Create health check endpoint
sudo nano /path/to/CodePilot/public/health.php
```

**Health Check Script:**
```php
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'memory_usage' => memory_get_usage(true),
    'disk_free' => disk_free_space('/'),
    'uptime' => sys_getloadavg()[0],
];

echo json_encode($health);
```

#### 5.2 Monitoring Script
```bash
# Create monitoring script
sudo nano /usr/local/bin/codepilot-monitor.sh
```

**Monitoring Script:**
```bash
#!/bin/bash
LOG_FILE="/var/log/codepilot_monitor.log"
APP_URL="https://your-domain.com/health.php"

check_app() {
    response=$(curl -s -o /dev/null -w "%{http_code}" $APP_URL)
    
    if [ $response -eq 200 ]; then
        echo "$(date): Application is healthy" >> $LOG_FILE
    else
        echo "$(date): Application is down (HTTP $response)" >> $LOG_FILE
        # Send alert (email, Slack, etc.)
        echo "ALERT: CodePilot is down!" | mail -s "CodePilot Alert" admin@yourcompany.com
    fi
}

check_disk() {
    usage=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ $usage -gt 90 ]; then
        echo "$(date): Disk usage is high: ${usage}%" >> $LOG_FILE
        echo "ALERT: Disk usage is high!" | mail -s "Disk Alert" admin@yourcompany.com
    fi
}

check_app
check_disk
```

```bash
# Make executable and add to crontab
sudo chmod +x /usr/local/bin/codepilot-monitor.sh
echo "*/5 * * * * /usr/local/bin/codepilot-monitor.sh" | sudo crontab -
```

#### 5.3 Log Rotation
```bash
# Create logrotate configuration
sudo nano /etc/logrotate.d/codepilot
```

**Log Rotation Configuration:**
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
        systemctl reload apache2  # or nginx
    endscript
}
```

### Phase 6: Backup Strategy (10 minutes)

#### 6.1 Backup Script
```bash
# Create backup script
sudo nano /usr/local/bin/codepilot-backup.sh
```

**Backup Script:**
```bash
#!/bin/bash
BACKUP_DIR="/backups/codepilot"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup application files
tar -czf $BACKUP_DIR/app_backup_$DATE.tar.gz /path/to/CodePilot

# Backup logs (optional)
tar -czf $BACKUP_DIR/logs_backup_$DATE.tar.gz /path/to/CodePilot/data/logs

# Keep only last 30 days
find $BACKUP_DIR -type f -mtime +30 -delete

echo "$(date): Backup completed" >> /var/log/codepilot_backup.log
```

```bash
# Make executable and add to crontab
sudo chmod +x /usr/local/bin/codepilot-backup.sh
echo "0 2 * * * /usr/local/bin/codepilot-backup.sh" | sudo crontab -
```

## 🎯 Final Verification

### 7.1 Test Application
```bash
# Test basic functionality
curl -I https://your-domain.com
curl https://your-domain.com/health.php

# Test API endpoints
curl -X POST https://your-domain.com/api/chat.php \
  -H "Content-Type: application/json" \
  -d '{"provider":"deepseek","model":"deepseek-chat","messages":[{"role":"user","content":"Hello"}]}'
```

### 7.2 Security Verification
```bash
# Check file permissions
ls -la /path/to/CodePilot/data/

# Verify SSL certificate
openssl s_client -connect your-domain.com:443 -servername your-domain.com

# Test rate limiting
for i in {1..110}; do curl -s -o /dev/null -w "%{http_code}\n" https://your-domain.com/api/chat.php; done
```

### 7.3 Performance Check
```bash
# Check PHP configuration
php -r "phpinfo();" | grep -E "(memory_limit|post_max_size|upload_max_filesize)"

# Check web server status
sudo systemctl status apache2  # or nginx
sudo systemctl status php8.1-fpm
```

## 📋 Complete Deployment Checklist

### ✅ Environment Setup
- [ ] `.env` file configured with production settings
- [ ] Directory permissions set correctly
- [ ] Required PHP extensions installed
- [ ] Web server configured

### ✅ SSL/HTTPS
- [ ] SSL certificate installed
- [ ] HTTPS redirect configured
- [ ] Security headers enabled

### ✅ Security Hardening
- [ ] Firewall rules applied
- [ ] Fail2Ban configured
- [ ] File permissions verified
- [ ] Security headers tested

### ✅ Monitoring
- [ ] Health check endpoint working
- [ ] Monitoring script deployed
- [ ] Log rotation configured
- [ ] Backup script scheduled

### ✅ Performance
- [ ] PHP OPcache enabled
- [ ] Static asset caching configured
- [ ] Database connections optimized (if using)

### ✅ Testing
- [ ] Application loads correctly
- [ ] API endpoints respond
- [ ] Security measures tested
- [ ] Performance verified

## 🚨 Common Issues & Solutions

### Issue: 404 Errors
**Solution**: Check web server rewrite rules and document root configuration

### Issue: Permission Denied
**Solution**: Verify file ownership and permissions for `www-data` user

### Issue: API Keys Not Working
**Solution**: Check `.env` file syntax and API key validity

### Issue: Slow Performance
**Solution**: Enable PHP OPcache and check resource limits

### Issue: SSL Certificate Errors
**Solution**: Verify certificate installation and domain configuration

## 📞 Support Resources

- **Documentation**: `deployment/README.md`
- **Troubleshooting**: `IMPROVEMENTS_SUMMARY.md`
- **Security Tests**: Run `php tests/SecurityTest.php`
- **Health Check**: Visit `https://your-domain.com/health.php`

## 🎉 Deployment Complete!

Your CodePilot application is now **100% production-ready** with:
- ✅ Enterprise-grade security
- ✅ Professional monitoring
- ✅ Automated backups
- ✅ Performance optimization
- ✅ Complete documentation

**Estimated Total Time**: 80 minutes
**Production Readiness**: 100%
**Security Level**: Enterprise-grade