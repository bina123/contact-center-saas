#!/bin/bash
echo "📋 Copying updated files to container..."

# Copy bootstrap/app.php
docker cp backend/bootstrap/app.php contact-center-backend:/var/www/bootstrap/app.php

# Copy Kernel files
docker cp backend/app/Http/Kernel.php contact-center-backend:/var/www/app/Http/Kernel.php
docker cp backend/app/Console/Kernel.php contact-center-backend:/var/www/app/Console/Kernel.php
docker cp backend/app/Exceptions/Handler.php contact-center-backend:/var/www/app/Exceptions/Handler.php

echo "✅ Files copied!"
echo ""
echo "Now run:"
echo "docker-compose -f docker-compose.simple.yml exec backend php artisan key:generate"
echo "docker-compose -f docker-compose.simple.yml exec backend php artisan migrate --force"
