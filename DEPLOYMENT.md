# Deployment Guide - Contact Center SaaS Platform

## 📦 Quick Start (Development)

### Prerequisites
- Docker & Docker Compose
- Git

### One-Command Setup

```bash
# Clone repository
git clone <your-repo-url> contact-center-saas
cd contact-center-saas

# Start all services
docker-compose up -d

# Wait for services to be healthy (30 seconds)
sleep 30

# Run migrations and seeders
docker-compose exec backend php artisan migrate --seed

# Generate application key
docker-compose exec backend php artisan key:generate

# Access the application
# Frontend: http://localhost:3000
# Backend API: http://localhost:8000/api
# WebSocket: ws://localhost:6001
```

---

## 🚀 Production Deployment

### Option 1: Docker Deployment (Recommended)

**1. Prepare Server**
```bash
# Install Docker and Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

**2. Clone and Configure**
```bash
# Clone repository
git clone <your-repo-url> /var/www/contact-center
cd /var/www/contact-center

# Copy and configure environment
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# Edit environment variables
nano backend/.env
nano frontend/.env
```

**3. Build and Deploy**
```bash
# Build production images
docker-compose -f docker-compose.prod.yml build

# Start services
docker-compose -f docker-compose.prod.yml up -d

# Run migrations
docker-compose exec backend php artisan migrate --force

# Generate key
docker-compose exec backend php artisan key:generate

# Optimize
docker-compose exec backend php artisan config:cache
docker-compose exec backend php artisan route:cache
docker-compose exec backend php artisan view:cache

# Build frontend
docker-compose exec frontend npm run build
```

**4. Setup SSL (Let's Encrypt)**
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

---

### Option 2: Manual Deployment

**Server Requirements:**
- Ubuntu 22.04 LTS
- PHP 8.2+
- MySQL 8.0+
- Redis 7.0+
- Nginx
- Node.js 18+
- Composer

**1. Install Dependencies**
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo add-apt-repository ppa:ondrej/php
sudo apt install php8.2-fpm php8.2-mysql php8.2-redis php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip -y

# Install MySQL
sudo apt install mysql-server -y
sudo mysql_secure_installation

# Install Redis
sudo apt install redis-server -y
sudo systemctl enable redis-server

# Install Nginx
sudo apt install nginx -y
sudo systemctl enable nginx

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**2. Setup Application**
```bash
# Clone repository
cd /var/www
sudo git clone <your-repo-url> contact-center
sudo chown -R www-data:www-data contact-center
cd contact-center

# Backend setup
cd backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend setup
cd ../frontend
npm install
npm run build
```

**3. Configure Nginx**
```nginx
# /etc/nginx/sites-available/contact-center
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/contact-center/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Frontend
server {
    listen 80;
    server_name app.yourdomain.com;
    root /var/www/contact-center/frontend/dist;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

**Enable site:**
```bash
sudo ln -s /etc/nginx/sites-available/contact-center /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

**4. Setup Queue Workers**
```bash
# Create systemd service
sudo nano /etc/systemd/system/contact-center-worker.service
```

```ini
[Unit]
Description=Contact Center Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/contact-center/backend
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable contact-center-worker
sudo systemctl start contact-center-worker
```

**5. Setup Laravel Reverb (WebSocket)**
```bash
sudo nano /etc/systemd/system/contact-center-reverb.service
```

```ini
[Unit]
Description=Contact Center Reverb Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/contact-center/backend
ExecStart=/usr/bin/php artisan reverb:start --host=0.0.0.0 --port=6001
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable contact-center-reverb
sudo systemctl start contact-center-reverb
```

**6. Setup Cron Jobs**
```bash
sudo crontab -e -u www-data
```

Add:
```cron
* * * * * cd /var/www/contact-center/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔧 Environment Configuration

### Backend (.env)

**Production Settings:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=your-db-host
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=strong-password

REDIS_HOST=your-redis-host

STRIPE_KEY=your-stripe-key
STRIPE_SECRET=your-stripe-secret

AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_BUCKET=your-s3-bucket
```

### Frontend (.env)

```env
VITE_API_URL=https://yourdomain.com/api
VITE_WS_URL=wss://yourdomain.com:6001
```

---

## 📊 Monitoring & Maintenance

### Log Monitoring
```bash
# Application logs
tail -f backend/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# Queue worker logs
sudo journalctl -u contact-center-worker -f

# Reverb logs
sudo journalctl -u contact-center-reverb -f
```

### Database Backups
```bash
# Create backup script
sudo nano /usr/local/bin/backup-database.sh
```

```bash
#!/bin/bash
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/var/backups/mysql"
DB_NAME="contact_center"

mkdir -p $BACKUP_DIR
mysqldump -u root -p $DB_NAME | gzip > $BACKUP_DIR/backup_$TIMESTAMP.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete
```

```bash
sudo chmod +x /usr/local/bin/backup-database.sh

# Add to crontab (daily at 2 AM)
0 2 * * * /usr/local/bin/backup-database.sh
```

### Performance Monitoring

**Install Monitoring Tools:**
```bash
# htop for system monitoring
sudo apt install htop

# Redis monitoring
redis-cli monitor

# MySQL monitoring
sudo apt install mytop
mytop -u root -p

# Laravel Telescope (already included)
# Access at: https://yourdomain.com/telescope
```

---

## 🔒 Security Checklist

- [ ] Change all default passwords
- [ ] Setup firewall (UFW)
- [ ] Enable fail2ban
- [ ] Configure SSL/TLS
- [ ] Set proper file permissions
- [ ] Disable directory listing
- [ ] Setup automated backups
- [ ] Configure rate limiting
- [ ] Enable audit logging
- [ ] Regular security updates

**Firewall Setup:**
```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 6001/tcp  # WebSocket
sudo ufw enable
```

---

## 📈 Scaling Considerations

### Horizontal Scaling

**Load Balancer Setup (Nginx):**
```nginx
upstream backend_servers {
    server backend1.local:8000;
    server backend2.local:8000;
    server backend3.local:8000;
}

server {
    listen 80;
    server_name yourdomain.com;

    location / {
        proxy_pass http://backend_servers;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### Database Read Replicas

Configure in `config/database.php`:
```php
'mysql' => [
    'read' => [
        'host' => ['replica1.host', 'replica2.host'],
    ],
    'write' => [
        'host' => ['primary.host'],
    ],
    // ... other config
],
```

---

## 🆘 Troubleshooting

### Common Issues

**1. Queue not processing:**
```bash
# Check worker status
sudo systemctl status contact-center-worker

# Restart worker
sudo systemctl restart contact-center-worker

# Clear failed jobs
php artisan queue:flush
```

**2. WebSocket connection fails:**
```bash
# Check Reverb status
sudo systemctl status contact-center-reverb

# Check firewall
sudo ufw status

# Test connection
curl http://localhost:6001
```

**3. Database connection issues:**
```bash
# Test MySQL connection
mysql -u username -p -h hostname database_name

# Check MySQL status
sudo systemctl status mysql
```

**4. Permission errors:**
```bash
# Fix storage permissions
sudo chown -R www-data:www-data backend/storage
sudo chmod -R 775 backend/storage
sudo chmod -R 775 backend/bootstrap/cache
```

---

## 📞 Support

For issues or questions:
- Check logs in `backend/storage/logs/`
- Review Laravel Telescope: `/telescope`
- Check system logs: `sudo journalctl -xe`

---

**Deployment checklist completed! Your Contact Center SaaS is ready for production! 🚀**
