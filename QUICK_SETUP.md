# 🚀 Quick Setup Guide - Choose Your Path

## 🎯 Recommended: Local Development (Easiest)

This avoids Docker build issues and is faster for development.

### Prerequisites
- PHP 8.2+ (`php -v` to check)
- Composer (`composer --version` to check)
- MySQL or MariaDB
- Redis (optional for queues)
- Node.js 18+

### Step-by-Step Setup

**1. Start Database Services**
```bash
# Option A: Use Docker for just MySQL and Redis
docker-compose -f docker-compose.dev.yml up -d mysql redis

# Option B: Use local MySQL
# Make sure MySQL is running on your machine
sudo systemctl start mysql  # Linux
brew services start mysql   # macOS
```

**2. Setup Backend**
```bash
cd backend

# Run the automated setup script
chmod +x setup.sh
./setup.sh

# This will:
# - Create directories
# - Install dependencies
# - Generate app key
# - Run migrations
```

**3. Start Backend Services**
```bash
# Terminal 1: API Server
php artisan serve

# Terminal 2: Queue Worker (optional)
php artisan queue:work

# Terminal 3: WebSocket Server (optional)
php artisan reverb:start
```

**4. Setup Frontend**
```bash
cd frontend
npm install
npm run dev
```

**5. Access Application**
- Frontend: http://localhost:3000
- Backend API: http://localhost:8000/api
- WebSocket: ws://localhost:6001

---

## 🐳 Alternative: Docker (If You Prefer)

### Fix the Docker Build Issue

The error is due to dependency version conflicts. Here's how to fix:

**Option 1: Use Development Docker Compose (No Backend Build)**
```bash
# This only runs MySQL and Redis in Docker
# You run Laravel locally (faster development)
docker-compose -f docker-compose.dev.yml up -d

# Then run backend locally
cd backend
php artisan serve
```

**Option 2: Fix Composer Dependencies**
```bash
cd backend

# Delete composer.lock if it exists
rm -f composer.lock

# Update dependencies to compatible versions
composer update --ignore-platform-reqs

# Commit the new composer.lock
git add composer.lock

# Now Docker build will work
docker-compose build
docker-compose up -d
```

**Option 3: Use PHP 8.3 Docker Image**

Edit `docker/php/Dockerfile`:
```dockerfile
FROM php:8.3-fpm  # Change from 8.2 to 8.3
```

Then build:
```bash
docker-compose build --no-cache
docker-compose up -d
```

---

## 📋 Comparison: Local vs Docker

| Feature | Local Development | Docker |
|---------|------------------|--------|
| Setup Speed | ⚡ Fast | 🐌 Slower (build time) |
| Development | ⚡ Hot reload works | 🔄 Needs restart |
| Resource Usage | 💚 Low | 🔴 Higher |
| Portability | ⚠️ Need to install tools | ✅ Everything included |
| Best For | Development | Production/Deployment |

**Recommendation:** Use **local development** for coding, Docker for deployment.

---

## ✅ Verification Checklist

After setup, verify everything works:

```bash
# 1. Check PHP
php -v
# Should show: PHP 8.2.x or higher

# 2. Check Database Connection
cd backend
php artisan tinker
>>> DB::connection()->getPdo();
# Should connect without error

# 3. Check Migrations
php artisan migrate:status
# Should show migration status

# 4. Test API
curl http://localhost:8000/api/user
# Should return JSON (401 unauthorized is OK)

# 5. Check Frontend
curl http://localhost:3000
# Should return HTML
```

---

## 🔧 Troubleshooting

### Backend Issues

**Composer Install Fails:**
```bash
# Use ignore platform requirements
composer install --ignore-platform-reqs
```

**Missing Extensions:**
```bash
# Ubuntu/Debian
sudo apt install php8.2-{mysql,mbstring,xml,bcmath,curl,zip,redis}

# macOS
brew install php@8.2
brew install redis
```

**Database Connection:**
```bash
# Update .env
DB_HOST=127.0.0.1  # Not localhost!
DB_PORT=3306
DB_DATABASE=contact_center
DB_USERNAME=root
DB_PASSWORD=your_password

# Create database
mysql -u root -p
CREATE DATABASE contact_center;
```

### Frontend Issues

**Node Modules:**
```bash
cd frontend
rm -rf node_modules package-lock.json
npm install
```

**Port In Use:**
```bash
# Change port in package.json
"dev": "vite --port 3001"
```

---

## 🎯 Quick Start Commands

### Absolute Fastest Setup
```bash
# 1. Backend
cd backend
./setup.sh
php artisan serve &

# 2. Frontend  
cd ../frontend
npm install
npm run dev

# Done! Access at http://localhost:3000
```

### With Docker (Services Only)
```bash
# 1. Start MySQL and Redis
docker-compose -f docker-compose.dev.yml up -d

# 2. Backend
cd backend
./setup.sh
php artisan serve &

# 3. Frontend
cd ../frontend
npm install
npm run dev
```

### Full Docker (After Fixing Dependencies)
```bash
# After running: composer update in backend/
docker-compose build
docker-compose up -d
docker-compose exec backend php artisan migrate
```

---

## 📚 Next Steps

After successful setup:

1. **Read Documentation:**
   - Start with `GETTING_STARTED.md`
   - Then `IMPLEMENTATION_GUIDE.md`
   - Finally `INTERVIEW_CHEATSHEET.md`

2. **Test Features:**
   - Visit http://localhost:3000
   - Try API endpoints
   - Check WebSocket connection

3. **Understand Code:**
   - Explore `backend/app/Models/`
   - Review `backend/app/Services/`
   - Study `frontend/src/components/`

4. **Prepare for Interview:**
   - Practice explaining architecture
   - Draw system diagram
   - Prepare to demo features

---

## 💡 Pro Tips

1. **Use Local Dev:** Docker is great for production, but local PHP is faster for development

2. **Multiple Terminals:** You need:
   - Terminal 1: `php artisan serve`
   - Terminal 2: `npm run dev` (frontend)
   - Terminal 3: `php artisan queue:work` (optional)
   - Terminal 4: `php artisan reverb:start` (optional)

3. **Auto-Restart:** Install `nodemon` to auto-restart PHP:
   ```bash
   npm install -g nodemon
   nodemon --exec php artisan serve
   ```

4. **Database GUI:** Use TablePlus, phpMyAdmin, or MySQL Workbench to view data

---

## 🆘 Still Stuck?

1. **Run Diagnostics:**
   ```bash
   cd backend
   php artisan about
   ```

2. **Check Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Reset Everything:**
   ```bash
   cd backend
   rm -rf vendor composer.lock
   ./setup.sh
   ```

4. **Ask for Help:** Include output from:
   ```bash
   php -v
   composer --version
   php artisan --version
   cat .env | grep DB_
   ```

---

**Choose local development for the fastest setup! 🚀**
