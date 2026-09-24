#!/bin/bash
set -e

echo "🚀 UniHub Deployment Started"
echo "============================="

# Clear cache
echo "🧹 Clearing cache..."
rm -rf bootstrap/cache/*.php 2>/dev/null || true
rm -rf storage/framework/cache/data/* 2>/dev/null || true

# Check environment
echo "🔍 Checking environment..."

if [ -z "$DATABASE_URL" ]; then
    echo "❌ DATABASE_URL missing!"
    exit 1
fi

if [ -z "$APP_KEY" ]; then
    echo "❌ APP_KEY missing!"
    exit 1
fi

# Parse DATABASE_URL
DB_HOST=$(echo $DATABASE_URL | sed -n 's|.*@\([^:/]*\).*|\1|p')
DB_PORT=$(echo $DATABASE_URL | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
DB_NAME=$(echo $DATABASE_URL | sed -n 's|.*/\([^?]*\).*|\1|p')
DB_USER=$(echo $DATABASE_URL | sed -n 's|.*://\([^:]*\):.*|\1|p')
DB_PASS=$(echo $DATABASE_URL | sed -n 's|.*://[^:]*:\([^@]*\)@.*|\1|p')

DB_PORT=${DB_PORT:-5432}

echo "✓ Database: $DB_NAME @ $DB_HOST"

# Wait for database
echo "⏳ Waiting for PostgreSQL..."
for i in {1..30}; do
    if PGPASSWORD=$DB_PASS psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c '\q' 2>/dev/null; then
        echo "✅ Database connected"
        break
    fi
    [ $i -eq 30 ] && echo "❌ Database timeout" && exit 1
    sleep 2
done

# Create .env
echo "📝 Creating .env..."
cat > .env << EOF
APP_NAME=UniHub
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
MAIL_HOST=${MAIL_HOST:-smtp.resend.com}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME:-resend}
MAIL_PASSWORD=$MAIL_PASSWORD
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-noreply@unihub.dev}
MAIL_FROM_NAME=UniHub

PASSPORT_CLIENT_ID=${PASSPORT_CLIENT_ID:-}
PASSPORT_CLIENT_SECRET=${PASSPORT_CLIENT_SECRET:-}

BCRYPT_ROUNDS=12
EOF

# Migrations
echo "📊 Running migrations..."
php artisan migrate --force 2>&1 | head -20

# Seeders
echo "🌱 Seeding database..."
php artisan db:seed --class=UserTypesSeeder --force 2>&1 | head -5
php artisan db:seed --class=PermissionsSeeder --force 2>&1 | head -5
php artisan db:seed --class=DaysSeeder --force 2>&1 | head -5
php artisan db:seed --class=SettingsSeeder --force 2>&1 | head -5
php artisan db:seed --class=DatabaseSeeder --force 2>&1 | head -5
php artisan db:seed --class=InitialCollegeSeeder --force 2>&1 | head -5
# ==========================================
# Passport Setup
# ==========================================
echo "🔐 Setting up Passport..."

# Generate keys if not exist
if [ ! -f storage/oauth-private.key ]; then
    echo "→ Generating keys..."
    timeout 30 php artisan passport:keys --force || echo "⚠️  Key generation timeout"
fi

# Fix permissions (مهم جداً!)
if [ -f storage/oauth-private.key ]; then
    chmod 600 storage/oauth-private.key
    chmod 600 storage/oauth-public.key
    chown www-data:www-data storage/oauth-*.key
    echo "✓ Keys permissions fixed"
fi

# Create client if needed
if [ -z "$PASSPORT_CLIENT_ID" ]; then
    echo "→ Creating client..."
    
    # Check which column exists (Laravel Passport version compatibility)
    COLUMN_CHECK=$(php artisan tinker --execute="
        echo Schema::hasColumn('oauth_clients', 'password_client') ? 'password_client' : 'personal_access_client';
    " 2>/dev/null || echo "personal_access_client")
    
    # Delete old password/personal access clients
    if [ "$COLUMN_CHECK" = "password_client" ]; then
        php artisan tinker --execute="
            DB::table('oauth_clients')->where('password_client', 1)->delete();
        " 2>/dev/null || true
    else
        php artisan tinker --execute="
            DB::table('oauth_clients')->where('personal_access_client', 1)->delete();
        " 2>/dev/null || true
    fi
    
    # Create new client with timeout
    OUTPUT=$(timeout 30 php artisan passport:client --personal --name="UniHub" --no-interaction 2>&1) || {
        echo "⚠️  Client creation timeout"
        OUTPUT=""
    }
    
    CLIENT_ID=$(echo "$OUTPUT" | grep -oP 'Client ID:\s*\K\d+' || echo "")
    CLIENT_SECRET=$(echo "$OUTPUT" | grep -oP 'Client secret:\s*\K\S+' || echo "")
    
    if [ -n "$CLIENT_ID" ] && [ -n "$CLIENT_SECRET" ]; then
        echo ""
        echo "======================================"
        echo "PASSPORT_CLIENT_ID=$CLIENT_ID"
        echo "PASSPORT_CLIENT_SECRET=$CLIENT_SECRET"
        echo "======================================"
        echo "⚠️  ADD TO RENDER ENV!"
    fi
fi

# Storage & Optimization
echo "⚡ Final steps..."
php artisan storage:link --force 2>/dev/null || true

# Optimize without view cache (faster)
php artisan config:cache 2>&1 | head -5
php artisan route:cache 2>&1 | head -5

echo ""
echo "✅ DEPLOYMENT SUCCESSFUL"
echo "🌐 $APP_URL"

# Start Apache
echo "🚀 Starting server..."
exec apache2-foreground