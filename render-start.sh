#!/bin/bash
set -e

echo "🚀 UniHub Deployment Started"
echo "============================="

# Clear cache
echo "🧹 Clearing cache..."
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*

# Check variables
echo "🔍 Checking environment..."

if [ -z "$DATABASE_URL" ]; then
    echo "❌ DATABASE_URL is missing!"
    exit 1
fi

if [ -z "$APP_KEY" ]; then
    echo "❌ APP_KEY is missing!"
    exit 1
fi

# Parse DATABASE_URL
DB_HOST=$(echo $DATABASE_URL | sed -n 's|.*@\([^:/]*\).*|\1|p')
DB_PORT=$(echo $DATABASE_URL | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
DB_NAME=$(echo $DATABASE_URL | sed -n 's|.*/\([^?]*\).*|\1|p')
DB_USER=$(echo $DATABASE_URL | sed -n 's|.*://\([^:]*\):.*|\1|p')
DB_PASS=$(echo $DATABASE_URL | sed -n 's|.*://[^:]*:\([^@]*\)@.*|\1|p')

echo "✓ Database: $DB_NAME @ $DB_HOST"

# Wait for database
echo "⏳ Waiting for PostgreSQL..."
for i in {1..30}; do
    if PGPASSWORD=$DB_PASS psql -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USER" -d "$DB_NAME" -c '\q' 2>/dev/null; then
        echo "✅ Database connected"
        break
    fi
    [ $i -eq 30 ] && echo "❌ Database timeout" && exit 1
    sleep 2
done

# Create .env
echo "📝 Creating .env..."
cat > .env << EOF
APP_NAME=$APP_NAME
APP_ENV=production
APP_KEY=$APP_KEY
APP_DEBUG=false
APP_URL=$APP_URL

FRONTEND_URL=$FRONTEND_URL

LOG_CHANNEL=errorlog
LOG_LEVEL=error

DB_CONNECTION=pgsql
DATABASE_URL=$DATABASE_URL

CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=480
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=$MAIL_HOST
MAIL_PORT=$MAIL_PORT
MAIL_USERNAME=$MAIL_USERNAME
MAIL_PASSWORD=$MAIL_PASSWORD
MAIL_ENCRYPTION=$MAIL_ENCRYPTION
MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS
MAIL_FROM_NAME=$MAIL_FROM_NAME

PASSPORT_CLIENT_ID=${PASSPORT_CLIENT_ID:-}
PASSPORT_CLIENT_SECRET=${PASSPORT_CLIENT_SECRET:-}

BCRYPT_ROUNDS=12
EOF

# Migrations
echo "📊 Running migrations..."
php artisan migrate --force || exit 1

# Seeders
echo "🌱 Seeding database..."
php artisan db:seed --class=UserTypesSeeder --force 2>&1 | head -2
php artisan db:seed --class=PermissionsSeeder --force 2>&1 | head -2
php artisan db:seed --class=DaysSeeder --force 2>&1 | head -2
php artisan db:seed --class=SettingsSeeder --force 2>&1 | head -2

# Passport
echo "🔐 Setting up Passport..."
if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --force
fi

if [ -z "$PASSPORT_CLIENT_ID" ]; then
    OUTPUT=$(php artisan passport:client --password --name="UniHub" 2>&1)
    CLIENT_ID=$(echo "$OUTPUT" | grep -oP 'Client ID:\s*\K\d+')
    CLIENT_SECRET=$(echo "$OUTPUT" | grep -oP 'Client secret:\s*\K\S+')
    
    if [ -n "$CLIENT_ID" ]; then
        echo ""
        echo "================================"
        echo "PASSPORT_CLIENT_ID=$CLIENT_ID"
        echo "PASSPORT_CLIENT_SECRET=$CLIENT_SECRET"
        echo "================================"
        echo "⚠️  ADD THESE TO ENVIRONMENT!"
    fi
fi

# Optimize
echo "⚡ Optimizing..."
php artisan storage:link --force 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "✅ DEPLOYMENT SUCCESSFUL!"
echo "🌐 $APP_URL"

exec apache2-foreground