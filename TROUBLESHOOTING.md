# 🔧 Troubleshooting Guide

## ⚡ Quick Fix for Your Error

**Error:** `Could not open input file: artisan`

**Solution - Use the Setup Script:**

```bash
cd backend
chmod +x setup.sh
./setup.sh
```

This script handles everything automatically!

---

## 🎯 Alternative: Manual Setup

If the script doesn't work, follow these steps:

```bash
cd backend

# 1. Create directories
mkdir -p storage/{app,framework,logs}
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p bootstrap/cache

# 2. Set permissions  
chmod -R 775 storage bootstrap/cache

# 3. Create .env
cp .env.example .env

# 4. Install Composer (skip scripts first)
composer install --no-scripts

# 5. Generate autoload
composer dump-autoload

# 6. Now run artisan
php artisan key:generate
```

---

## Common Issues

### 1. PHP Version Too Old
```bash
php -v  # Should be 8.2+

# Ubuntu
sudo add-apt-repository ppa:ondrej/php
sudo apt install php8.2
```

### 2. Missing PHP Extensions
```bash
# Install required extensions
sudo apt install php8.2-{mbstring,xml,bcmath,curl,zip,mysql,redis}
```

### 3. Permission Denied
```bash
# Fix permissions
sudo chown -R $USER:$USER backend/
chmod -R 775 backend/storage
chmod -R 775 backend/bootstrap/cache
```

### 4. Database Connection Failed
```bash
# Start MySQL with Docker
docker-compose up -d mysql

# Wait 30 seconds
sleep 30

# Or check if MySQL is running
sudo systemctl status mysql
```

### 5. Port Already in Use
```bash
# Find what's using port 8000
lsof -i :8000

# Kill it or use different port
php artisan serve --port=8001
```

---

## 🐳 Docker Installation (Easiest)

**Recommended if having issues:**

```bash
# Start everything with Docker
docker-compose up -d

# Run migrations
docker-compose exec backend php artisan migrate

# That's it! Access at http://localhost:8000
```

---

## ✅ Verification Steps

After installation, verify everything works:

```bash
# 1. Check PHP
php -v

# 2. Check artisan
php artisan --version

# 3. Check database
php artisan migrate:status

# 4. Start server
php artisan serve
```

---

## 🆘 Still Having Issues?

### Run This Diagnostic:

```bash
cd backend

echo "=== PHP Version ==="
php -v

echo "=== Required Extensions ==="
php -m | grep -E "PDO|mbstring|xml|bcmath"

echo "=== Directory Permissions ==="
ls -la storage/
ls -la bootstrap/cache/

echo "=== Composer ==="
composer --version

echo "=== .env File ==="
test -f .env && echo ".env exists" || echo ".env missing"

echo "=== Artisan ==="
test -f artisan && echo "artisan exists" || echo "artisan missing"
```

Copy this output if you need help debugging!

---

## 📋 Complete Fresh Install

**Nuclear option - start from scratch:**

```bash
# 1. Remove everything
cd backend
rm -rf vendor/ composer.lock
rm -rf storage/ bootstrap/cache/

# 2. Run setup script
chmod +x setup.sh
./setup.sh

# Done!
```

---

**Need more help? Check the full logs:**
```bash
cat storage/logs/laravel.log
```
