#!/bin/bash
echo "🔧 Installing vendor dependencies in container..."
docker-compose -f docker-compose.simple.yml exec backend composer install --no-interaction --ignore-platform-reqs
echo "✅ Done! Now run migrations:"
echo "docker-compose -f docker-compose.simple.yml exec backend php artisan migrate --force"
