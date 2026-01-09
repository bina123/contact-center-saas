#!/bin/bash
set -e

echo "🚀 One-Command Fix for Contact Center SaaS"
echo "=========================================="
echo ""

# Stop containers
echo "⏹️  Stopping containers..."
docker-compose -f docker-compose.simple.yml down

# Rebuild
echo "🔨 Rebuilding with all fixes..."
docker-compose -f docker-compose.simple.yml build --no-cache

# Start
echo "▶️  Starting services..."
docker-compose -f docker-compose.simple.yml up -d

# Wait for MySQL
echo "⏳ Waiting for MySQL to be ready..."
sleep 40

# Check MySQL
echo "🔍 Checking MySQL..."
until docker-compose -f docker-compose.simple.yml exec mysql mysqladmin ping -h localhost -u root -psecret --silent; do
    echo "   MySQL not ready yet, waiting..."
    sleep 5
done
echo "✅ MySQL is ready!"

# Generate app key
echo "🔑 Generating application key..."
docker-compose -f docker-compose.simple.yml exec backend php artisan key:generate --force

# Run migrations
echo "🗄️  Running migrations..."
docker-compose -f docker-compose.simple.yml exec backend php artisan migrate --force

# Show status
echo ""
echo "=========================================="
echo "✅ Setup Complete!"
echo "=========================================="
echo ""
echo "🌐 Access URLs:"
echo "   Frontend: http://localhost:3000"
echo "   API:      http://localhost:8000"
echo ""
echo "📊 Check status:"
echo "   docker-compose -f docker-compose.simple.yml ps"
echo ""
echo "📝 View logs:"
echo "   docker-compose -f docker-compose.simple.yml logs -f"
echo ""
