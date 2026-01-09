# 🐳 Docker Setup Guide - No Local PHP Required

## ✅ Simple Working Setup (Recommended)

This is the **easiest way** to get everything running without local PHP.

### Prerequisites
- Docker Desktop or Docker Engine
- Docker Compose
- That's it! No PHP, no Composer needed locally

### Step-by-Step Setup

**1. Extract and Navigate**
```bash
tar -xzf contact-center-saas.tar.gz
cd contact-center-saas
```

**2. Create Environment File**
```bash
# Copy the example .env
cp backend/.env.example backend/.env

# The default values work with Docker!
```

**3. Build and Start Services**
```bash
# Use the simple docker-compose
docker-compose -f docker-compose.simple.yml build

# Start all services
docker-compose -f docker-compose.simple.yml up -d

# Watch logs (optional)
docker-compose -f docker-compose.simple.yml logs -f
```

**4. Wait for MySQL to Start**
```bash
# Wait 30-60 seconds for MySQL to initialize
# Check if it's ready:
docker-compose -f docker-compose.simple.yml exec mysql mysqladmin ping -h localhost -u root -psecret
```

**5. Run Migrations**
```bash
# Create tables
docker-compose -f docker-compose.simple.yml exec backend php artisan migrate --force

# Generate app key
docker-compose -f docker-compose.simple.yml exec backend php artisan key:generate
```

**6. Access the Application**
- Frontend: http://localhost:3000
- Backend API: http://localhost:8000
- MySQL: localhost:3306 (user: contact_center, pass: secret)

**That's it! You're running! 🎉**

---

## 🔧 Useful Docker Commands

### View Logs
```bash
# All services
docker-compose -f docker-compose.simple.yml logs -f

# Specific service
docker-compose -f docker-compose.simple.yml logs -f backend
docker-compose -f docker-compose.simple.yml logs -f mysql
docker-compose -f docker-compose.simple.yml logs -f frontend
```

### Run Artisan Commands
```bash
# Any artisan command
docker-compose -f docker-compose.simple.yml exec backend php artisan [command]

# Examples:
docker-compose -f docker-compose.simple.yml exec backend php artisan migrate
docker-compose -f docker-compose.simple.yml exec backend php artisan db:seed
docker-compose -f docker-compose.simple.yml exec backend php artisan tinker
docker-compose -f docker-compose.simple.yml exec backend php artisan route:list
```

### Access MySQL
```bash
docker-compose -f docker-compose.simple.yml exec mysql mysql -u root -psecret contact_center
```

### Restart Services
```bash
# Restart all
docker-compose -f docker-compose.simple.yml restart

# Restart specific service
docker-compose -f docker-compose.simple.yml restart backend
```

### Stop Services
```bash
docker-compose -f docker-compose.simple.yml stop
```

### Stop and Remove Everything
```bash
# Stop and remove containers (keeps data)
docker-compose -f docker-compose.simple.yml down

# Remove everything including volumes (deletes database!)
docker-compose -f docker-compose.simple.yml down -v
```

### Rebuild After Code Changes
```bash
# Rebuild specific service
docker-compose -f docker-compose.simple.yml build backend

# Rebuild all
docker-compose -f docker-compose.simple.yml build

# No cache rebuild
docker-compose -f docker-compose.simple.yml build --no-cache
```

---

## 🐛 Troubleshooting

### Build Fails with "composer install" Error

**Problem:** Dependency conflicts during Docker build

**Solution:**
```bash
# The simple docker-compose has all dependencies already fixed!
# Just use: docker-compose.simple.yml
```

### MySQL Connection Refused

**Problem:** Backend starts before MySQL is ready

**Solution:**
```bash
# Wait for MySQL healthcheck
docker-compose -f docker-compose.simple.yml ps

# When mysql shows "healthy", restart backend:
docker-compose -f docker-compose.simple.yml restart backend
```

### Port Already in Use

**Problem:** 3306, 8000, or 3000 already in use

**Solution - Edit docker-compose.simple.yml:**
```yaml
# Change the HOST port (before the colon):
ports:
  - "8001:8000"  # Changed from 8000:8000
  - "3001:3000"  # Changed from 3000:3000
  - "3307:3306"  # Changed from 3306:3306
```

### Container Keeps Restarting

**Check logs:**
```bash
docker-compose -f docker-compose.simple.yml logs backend
```

**Common issues:**
- Missing .env file: `cp backend/.env.example backend/.env`
- Wrong permissions: `chmod -R 777 backend/storage`
- Database not ready: Wait 60 seconds, then restart

### Frontend Won't Start

```bash
# Check logs
docker-compose -f docker-compose.simple.yml logs frontend

# Often fixes: Rebuild frontend
docker-compose -f docker-compose.simple.yml build frontend
docker-compose -f docker-compose.simple.yml up -d frontend
```

---

## 📊 Service Details

### What's Running?

```bash
docker-compose -f docker-compose.simple.yml ps
```

You should see:
- `mysql` - Database (port 3306)
- `redis` - Cache/Queue (port 6379)
- `backend` - Laravel API (PHP-FPM)
- `nginx` - Web server (port 8000)
- `queue` - Queue worker
- `frontend` - React app (port 3000)

### Resource Usage

```bash
docker stats
```

Shows CPU, memory, and network usage for each container.

---

## 🚀 Production Deployment

For production, use the main docker-compose.yml:

```bash
# Build production images
docker-compose build

# Start with production settings
APP_ENV=production docker-compose up -d

# Run optimizations
docker-compose exec backend php artisan config:cache
docker-compose exec backend php artisan route:cache
docker-compose exec backend php artisan view:cache
```

---

## 🎯 Development Workflow

### Making Code Changes

**Backend (Laravel):**
1. Edit files in `backend/` directory
2. Changes are live-mounted (no rebuild needed)
3. Clear cache if needed:
   ```bash
   docker-compose -f docker-compose.simple.yml exec backend php artisan cache:clear
   ```

**Frontend (React):**
1. Edit files in `frontend/` directory
2. Vite hot-reload works automatically
3. See changes instantly in browser

### Adding New Dependencies

**Backend:**
```bash
# Add package
docker-compose -f docker-compose.simple.yml exec backend composer require package/name

# Rebuild if composer.json changes
docker-compose -f docker-compose.simple.yml build backend
```

**Frontend:**
```bash
# Add package
docker-compose -f docker-compose.simple.yml exec frontend npm install package-name

# Or rebuild
docker-compose -f docker-compose.simple.yml build frontend
```

---

## 🔐 Security Notes

### Default Credentials (CHANGE IN PRODUCTION!)

- MySQL root password: `secret`
- MySQL user: `contact_center`
- MySQL password: `secret`
- Database name: `contact_center`

### Update for Production

Edit `backend/.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=your-generated-key

DB_PASSWORD=strong-random-password

# Add other production settings
```

---

## 📈 Performance Tips

### Speed Up Builds

```bash
# Use BuildKit for faster builds
DOCKER_BUILDKIT=1 docker-compose -f docker-compose.simple.yml build
```

### Reduce Container Size

Use production Dockerfile with optimizations:
```dockerfile
FROM php:8.2-fpm-alpine  # Alpine is smaller
# ... install only production dependencies
```

### Database Optimization

```bash
# Inside MySQL container
docker-compose -f docker-compose.simple.yml exec mysql mysql -u root -psecret

# Run:
SET GLOBAL innodb_buffer_pool_size=268435456; # 256MB
```

---

## ✅ Health Checks

### Check All Services

```bash
# Service status
docker-compose -f docker-compose.simple.yml ps

# Health status
docker-compose -f docker-compose.simple.yml exec backend php artisan about

# Test API
curl http://localhost:8000/api/user
# Should return JSON (401 is OK - means API works)

# Test frontend
curl http://localhost:3000
# Should return HTML
```

---

## 🎓 Common Tasks

### Create New User
```bash
docker-compose -f docker-compose.simple.yml exec backend php artisan tinker

# Inside tinker:
$user = new App\Models\User();
$user->name = 'Admin';
$user->email = 'admin@example.com';
$user->password = Hash::make('password');
$user->save();
```

### Reset Database
```bash
# Drop and recreate
docker-compose -f docker-compose.simple.yml exec backend php artisan migrate:fresh

# With seeders
docker-compose -f docker-compose.simple.yml exec backend php artisan migrate:fresh --seed
```

### Backup Database
```bash
docker-compose -f docker-compose.simple.yml exec mysql mysqldump -u root -psecret contact_center > backup.sql
```

### Restore Database
```bash
cat backup.sql | docker-compose -f docker-compose.simple.yml exec -T mysql mysql -u root -psecret contact_center
```

---

## 🆘 Complete Reset (Nuclear Option)

If everything is broken:

```bash
# Stop everything
docker-compose -f docker-compose.simple.yml down -v

# Remove all images
docker-compose -f docker-compose.simple.yml down --rmi all

# Clean Docker system
docker system prune -a

# Start fresh
docker-compose -f docker-compose.simple.yml build
docker-compose -f docker-compose.simple.yml up -d
docker-compose -f docker-compose.simple.yml exec backend php artisan migrate
```

---

## 📞 Support

**Still having issues?**

1. Check logs: `docker-compose -f docker-compose.simple.yml logs`
2. Verify all containers running: `docker-compose -f docker-compose.simple.yml ps`
3. Check disk space: `df -h`
4. Check Docker version: `docker --version` (should be 20.10+)

**Share this info when asking for help:**
```bash
docker --version
docker-compose --version
docker-compose -f docker-compose.simple.yml ps
docker-compose -f docker-compose.simple.yml logs backend --tail=50
```

---

**With Docker, no local PHP installation needed! 🐳🚀**
