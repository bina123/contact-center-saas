#!/bin/bash

echo "🚀 Contact Center SaaS - Fresh Installation Script"
echo "=================================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "→ $1"
}

# Check if we're in the backend directory
if [ ! -f "composer.json" ]; then
    print_error "Please run this script from the backend directory"
    exit 1
fi

# Step 1: Create necessary directories
print_info "Creating Laravel directory structure..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
mkdir -p public
mkdir -p database/seeders
mkdir -p database/factories
print_success "Directory structure created"

# Step 2: Set permissions
print_info "Setting correct permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
print_success "Permissions set"

# Step 3: Copy environment file
if [ ! -f ".env" ]; then
    print_info "Creating .env file..."
    if [ -f ".env.example" ]; then
        cp .env.example .env
        print_success ".env file created"
    else
        print_warning ".env.example not found, creating basic .env"
        cat > .env << 'EOF'
APP_NAME="Contact Center SaaS"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=contact_center
DB_USERNAME=contact_center
DB_PASSWORD=secret

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PORT=6379

BROADCAST_DRIVER=reverb
REVERB_APP_ID=local-app-id
REVERB_APP_KEY=local-app-key
REVERB_APP_SECRET=local-app-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=6001
EOF
        print_success "Basic .env file created"
    fi
else
    print_warning ".env file already exists, skipping"
fi

# Step 4: Install Composer dependencies
print_info "Installing Composer dependencies (this may take a few minutes)..."
if command -v composer &> /dev/null; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
    if [ $? -eq 0 ]; then
        print_success "Composer dependencies installed"
    else
        print_error "Composer install failed"
        print_info "Try running manually: composer install --ignore-platform-reqs"
        exit 1
    fi
else
    print_error "Composer not found. Please install Composer first."
    exit 1
fi

# Step 5: Generate application key
print_info "Generating application key..."
php artisan key:generate --force
if [ $? -eq 0 ]; then
    print_success "Application key generated"
else
    print_warning "Could not generate key automatically. Run: php artisan key:generate"
fi

# Step 6: Check database connection
print_info "Checking database connection..."
php artisan db:show 2>/dev/null
if [ $? -eq 0 ]; then
    print_success "Database connection successful"
    
    # Step 7: Run migrations
    print_info "Running database migrations..."
    php artisan migrate --force
    if [ $? -eq 0 ]; then
        print_success "Migrations completed"
        
        # Step 8: Seed database (optional)
        read -p "Do you want to seed the database with sample data? (y/N) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            php artisan db:seed --force
            print_success "Database seeded"
        fi
    else
        print_warning "Migrations failed - this is normal on first run without database"
    fi
else
    print_warning "Database not available yet - run migrations later with: php artisan migrate"
fi

# Step 9: Create storage link
print_info "Creating storage symbolic link..."
php artisan storage:link 2>/dev/null
print_success "Storage link created"

# Step 10: Clear and cache configuration
print_info "Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
print_success "Cache cleared"

echo ""
echo "=================================================="
print_success "Installation Complete!"
echo "=================================================="
echo ""
echo "Next steps:"
echo "  1. Start the development server:"
echo "     php artisan serve"
echo ""
echo "  2. In another terminal, start the queue worker:"
echo "     php artisan queue:work"
echo ""
echo "  3. In another terminal, start Reverb (WebSocket):"
echo "     php artisan reverb:start"
echo ""
echo "  4. Access the API at: http://localhost:8000"
echo ""
echo "Or use Docker:"
echo "  docker-compose up -d"
echo ""
print_warning "Note: Make sure your database is running before running migrations!"
echo ""
