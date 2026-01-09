#!/bin/bash
echo "🏗️  Setting up Laravel directory structure in container..."

# Create all necessary directories
docker-compose -f docker-compose.simple.yml exec backend bash -c "
mkdir -p config
mkdir -p resources/views
mkdir -p resources/lang/en
mkdir -p app/Providers
mkdir -p app/Console/Commands
mkdir -p database/factories
mkdir -p database/seeders
mkdir -p tests/Feature
mkdir -p tests/Unit
"

# Copy config files from Laravel framework
docker-compose -f docker-compose.simple.yml exec backend bash -c "
cp -r vendor/laravel/framework/src/Illuminate/Foundation/Configuration/config/* config/ 2>/dev/null || true
"

# If that didn't work, create minimal config files
docker-compose -f docker-compose.simple.yml exec backend bash -c '
if [ ! -f config/app.php ]; then
  echo "Creating config files..."
  
  # Create config/app.php
  cat > config/app.php << "EOFCONFIG"
<?php
return [
    "name" => env("APP_NAME", "Laravel"),
    "env" => env("APP_ENV", "production"),
    "debug" => (bool) env("APP_DEBUG", false),
    "url" => env("APP_URL", "http://localhost"),
    "asset_url" => env("ASSET_URL"),
    "timezone" => "UTC",
    "locale" => "en",
    "fallback_locale" => "en",
    "faker_locale" => "en_US",
    "key" => env("APP_KEY"),
    "cipher" => "AES-256-CBC",
    "maintenance" => [
        "driver" => "file",
    ],
    "providers" => [
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
    ],
    "aliases" => [],
];
EOFCONFIG

  # Create config/database.php
  cat > config/database.php << "EOFCONFIG"
<?php
return [
    "default" => env("DB_CONNECTION", "mysql"),
    "connections" => [
        "mysql" => [
            "driver" => "mysql",
            "host" => env("DB_HOST", "127.0.0.1"),
            "port" => env("DB_PORT", "3306"),
            "database" => env("DB_DATABASE", "forge"),
            "username" => env("DB_USERNAME", "forge"),
            "password" => env("DB_PASSWORD", ""),
            "charset" => "utf8mb4",
            "collation" => "utf8mb4_unicode_ci",
            "prefix" => "",
            "strict" => true,
            "engine" => null,
        ],
    ],
    "migrations" => "migrations",
    "redis" => [
        "client" => env("REDIS_CLIENT", "phpredis"),
        "default" => [
            "host" => env("REDIS_HOST", "127.0.0.1"),
            "password" => env("REDIS_PASSWORD"),
            "port" => env("REDIS_PORT", "6379"),
            "database" => env("REDIS_DB", "0"),
        ],
    ],
];
EOFCONFIG

fi
'

echo "✅ Directory structure created!"
echo ""
echo "Now run:"
echo "docker-compose -f docker-compose.simple.yml exec backend php artisan key:generate"
echo "docker-compose -f docker-compose.simple.yml exec backend php artisan migrate --force"
