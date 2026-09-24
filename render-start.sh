#!/bin/bash
set -e

echo "🚀 Starting UniHub Backend on Render..."
echo "========================================"

# ==========================================
# حذف Cache القديم أولاً (قبل كل شيء!)
# ==========================================
echo "🧹 Removing old cached configs..."
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
echo "✅ Old cache removed"

# ==========================================
# التحقق من المتغيرات
# ==========================================
echo ""
echo "🔍 Checking environment variables..."

if [ -z "$DATABASE_URL" ]; then
    echo "❌ ERROR: DATABASE_URL is required!"
    exit 1
fi

if [ -z "$APP_KEY" ]; then
    echo "❌ ERROR: APP_KEY is required!"
    exit 1
fi

echo "✅ Required variables are set"

# ==========================================
# استخراج معلومات Database
# ==========================================
echo ""
echo "📊 Parsing DATABASE_URL..."

DB_USER=$(echo $DATABASE_URL | sed -n 's|.*://\([^:]*\):.*|\1|p')
DB_PASS=$(echo $DATABASE_URL | sed -n 's|.*://[^:]*:\([^@]*\)@.*|\1|p')
DB_HOST=$(echo $DATABASE_URL | sed -n 's|.*@\([^:/]*\).*|\1|p')
DB_PORT=$(echo $DATABASE_URL | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
DB_NAME=$(echo $DATABASE_URL | sed -n 's|.*/\([^?]*\).*|\1|p')

DB_PORT=${DB_PORT:-5432}

echo "  Host: $DB_HOST"
echo "  Port: $DB_PORT"
echo "  Database: $DB_NAME"

# ==========================================
# انتظار Database
# ==========================================
echo ""
echo "⏳ Waiting for PostgreSQL..."

MAX_TRIES=30
for i in $(seq 1 $MAX_TRIES); do
    if PGPASSWORD=$DB_PASS psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c '\q' 2>/dev/null; then
        echo "✅ Database ready!"
        break
    fi
    
    if [ $i -eq $MAX_TRIES ]; then
        echo "❌ Database timeout after $MAX_TRIES attempts"
        exit 1
    fi
    
    echo "  Attempt $i/$MAX_TRIES..."
    sleep 2
done

# ==========================================
# إنشاء .env
# ==========================================
echo ""
echo "📝 Creating .env file..."

cat > .env <<'ENVFILE'
APP_NAME=${APP_NAME}
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=${APP_URL}

FRONTEND_URL=${FRONTEND_URL}

LOG_CHANNEL=errorlog
LOG_LEVEL=error

DB_CONNECTION=pgsql
DATABASE_URL=${DATABASE_URL}

CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=480

QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT}
MAIL_USERNAME=${MAIL_USERNAME}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}
MAIL_FROM_NAME=${APP_NAME}

PASSPORT_CLIENT_ID=${PASSPORT_CLIENT_ID}
PASSPORT_CLIENT_SECRET=${PASSPORT_CLIENT_SECRET}

BCRYPT_ROUNDS=12
ENVFILE

# استبدال المتغيرات
sed -i "s|\${APP_NAME}|${APP_NAME:-UniHub}|g" .env
sed -i "s|\${APP_KEY}|${APP_KEY}|g" .env
sed -i "s|\${APP_URL}|${APP_URL}|g" .env
sed -i "s|\${FRONTEND_URL}|${FRONTEND_URL}|g" .env
sed -i "s|\${DATABASE_URL}|${DATABASE_URL}|g" .env
sed -i "s|\${MAIL_HOST}|${MAIL_HOST:-smtp.resend.com}|g" .env
sed -i "s|\${MAIL_PORT}|${MAIL_PORT:-587}|g" .env
sed -i "s|\${MAIL_USERNAME}|${MAIL_USERNAME:-resend}|g" .env
sed -i "s|\${MAIL_PASSWORD}|${MAIL_PASSWORD}|g" .env
sed -i "s|\${MAIL_ENCRYPTION}|${MAIL_ENCRYPTION:-tls}|g" .env
sed -i "s|\${MAIL_FROM_ADDRESS}|${MAIL_FROM_ADDRESS}|g" .env
sed -i "s|\${PASSPORT_CLIENT_ID}|${PASSPORT_CLIENT_ID:-}|g" .env
sed -i "s|\${PASSPORT_CLIENT_SECRET}|${PASSPORT_CLIENT_SECRET:-}|g" .env

echo "✅ .env created"

# ==========================================
# اختبار الاتصال
# ==========================================
echo ""
echo "🔌 Testing database connection..."

TEST_RESULT=$(PGPASSWORD=$DB_PASS psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT 'OK'" 2>&1)

if echo "$TEST_RESULT" | grep -q "OK"; then
    echo "✅ Direct connection successful"
else
    echo "❌ Connection test failed: $TEST_RESULT"
    exit 1
fi

# ==========================================
# Migrations
# ==========================================
echo ""
echo "📊 Running migrations..."

php artisan migrate --force || {
    echo "❌ Migration failed"
    exit 1
}

echo "✅ Migrations completed"

# ==========================================
# Seeders
# ==========================================
echo ""
echo "🌱 Seeding database..."

php artisan db:seed --class=UserTypesSeeder --force 2>&1 | head -1
php artisan db:seed --class=PermissionsSeeder --force 2>&1 | head -1
php artisan db:seed --class=DaysSeeder --force 2>&1 | head -1
php artisan db:seed --class=SettingsSeeder --force 2>&1 | head -1

echo "✅ Seeding completed"

# ==========================================
# Passport
# ==========================================
echo ""
echo "🔐 Setting up Passport..."

if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --force
    echo "✅ Keys generated"
fi

if [ -z "$PASSPORT_CLIENT_ID" ]; then
    echo "→ Creating Password Grant Client..."
    
    OUTPUT=$(php artisan passport:client --password --name="UniHub App" 2>&1)
    
    CLIENT_ID=$(echo "$OUTPUT" | grep -oP 'Client ID:\s*\K\d+')
    CLIENT_SECRET=$(echo "$OUTPUT" | grep -oP 'Client secret:\s*\K\S+')
    
    if [ -n "$CLIENT_ID" ]; then
        echo ""
        echo "╔══════════════════════════════════════╗"
        echo "║  🔑 PASSPORT CREDENTIALS            ║"
        echo "╠══════════════════════════════════════╣"
        echo "║ PASSPORT_CLIENT_ID=$CLIENT_ID"
        echo "║ PASSPORT_CLIENT_SECRET=$CLIENT_SECRET"
        echo "╚══════════════════════════════════════╝"
        echo ""
    fi
fi

# ==========================================
# Storage & Optimization
# ==========================================
echo ""
echo "⚡ Final setup..."

php artisan storage:link --force 2>/dev/null || true
php artisan config:cache
php artisan route:cache  
php artisan view:cache

echo ""
echo "╔══════════════════════════════════════╗"
echo "║  ✅ DEPLOYMENT SUCCESSFUL           ║"
echo "╠══════════════════════════════════════╣"
echo "║  URL: ${APP_URL}"
echo "║  Database: Connected ✓"
echo "╚══════════════════════════════════════╝"
echo ""
echo "🚀 Starting Apache..."

exec apache2-foreground