#!/bin/bash
set -e

echo "🚀 Starting UniHub Backend on Render..."
echo "========================================"

# ==========================================
# التحقق من المتغيرات الأساسية
# ==========================================
echo "🔍 Checking required environment variables..."

if [ -z "$DATABASE_URL" ]; then
    echo "❌ ERROR: DATABASE_URL is required!"
    exit 1
fi

if [ -z "$APP_KEY" ]; then
    echo "❌ ERROR: APP_KEY is required!"
    echo "Run locally: php artisan key:generate --show"
    exit 1
fi

echo "✅ All required variables are set"

# ==========================================
# استخراج معلومات قاعدة البيانات
# ==========================================
echo ""
echo "📊 Extracting database credentials from DATABASE_URL..."

# Parse DATABASE_URL
DB_USER=$(echo $DATABASE_URL | sed -n 's|.*://\([^:]*\):.*|\1|p')
DB_PASS=$(echo $DATABASE_URL | sed -n 's|.*://[^:]*:\([^@]*\)@.*|\1|p')
DB_HOST=$(echo $DATABASE_URL | sed -n 's|.*@\([^:/]*\).*|\1|p')
DB_PORT=$(echo $DATABASE_URL | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
DB_NAME=$(echo $DATABASE_URL | sed -n 's|.*/\([^?]*\).*|\1|p')

DB_PORT=${DB_PORT:-5432}

echo "✓ Host: $DB_HOST"
echo "✓ Port: $DB_PORT"
echo "✓ Database: $DB_NAME"
echo "✓ User: $DB_USER"

# ==========================================
# انتظار قاعدة البيانات
# ==========================================
echo ""
echo "⏳ Waiting for PostgreSQL to be ready..."

MAX_TRIES=30
COUNTER=0

until PGPASSWORD=$DB_PASS psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c '\q' 2>/dev/null; do
    COUNTER=$((COUNTER + 1))
    if [ $COUNTER -eq $MAX_TRIES ]; then
        echo "❌ Database connection failed after $MAX_TRIES attempts"
        echo "Database URL: ${DATABASE_URL%%:*}://***@$DB_HOST:$DB_PORT/$DB_NAME"
        exit 1
    fi
    echo "Attempt $COUNTER/$MAX_TRIES..."
    sleep 2
done

echo "✅ Database connection successful!"

# ==========================================
# إنشاء ملف .env
# ==========================================
echo ""
echo "📝 Creating production .env file..."

cat > .env <<EOF
APP_NAME="${APP_NAME:-UniHub}"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL}

FRONTEND_URL=${FRONTEND_URL}

LOG_CHANNEL=errorlog
LOG_LEVEL=${LOG_LEVEL:-error}

# Database - استخدام DATABASE_URL فقط
DB_CONNECTION=pgsql
DATABASE_URL=${DATABASE_URL}

CACHE_STORE=database
CACHE_PREFIX=unihub_

SESSION_DRIVER=database
SESSION_LIFETIME=480
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

QUEUE_CONNECTION=database

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=${MAIL_HOST:-smtp.resend.com}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME:-resend}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}
MAIL_FROM_NAME="${APP_NAME:-UniHub}"

PASSPORT_CLIENT_ID=${PASSPORT_CLIENT_ID:-}
PASSPORT_CLIENT_SECRET=${PASSPORT_CLIENT_SECRET:-}

BCRYPT_ROUNDS=12
EOF

echo "✅ Environment file created"

# ==========================================
# مسح Cache القديم
# ==========================================
echo ""
echo "🧹 Clearing old cache..."
rm -f bootstrap/cache/*.php
php artisan config:clear || true
php artisan cache:clear || true

# ==========================================
# اختبار الاتصال بقاعدة البيانات
# ==========================================
echo ""
echo "🔌 Testing Laravel database connection..."

php artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPdo();
    echo '✅ Laravel connected to: ' . DB::connection()->getDatabaseName() . PHP_EOL;
} catch (\Exception \$e) {
    echo '❌ Connection failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

# ==========================================
# Migrations
# ==========================================
echo ""
echo "📊 Running database migrations..."

php artisan migrate --force || {
    echo ""
    echo "❌ Migration failed!"
    echo "Showing current database status..."
    php artisan migrate:status || true
    exit 1
}

echo "✅ Migrations completed"

# ==========================================
# Seeders
# ==========================================
echo ""
echo "🌱 Seeding initial data..."

SEEDERS=(
    "UserTypesSeeder"
    "PermissionsSeeder"
    "DaysSeeder"
    "SettingsSeeder"
)

for seeder in "${SEEDERS[@]}"; do
    echo "→ Running $seeder..."
    php artisan db:seed --class=$seeder --force 2>&1 | grep -v "already exists\|Nothing to seed" || echo "  ✓ Done"
done

echo "✅ Seeding completed"

# ==========================================
# Laravel Passport
# ==========================================
echo ""
echo "🔐 Setting up Laravel Passport..."

# توليد المفاتيح
if [ ! -f storage/oauth-private.key ]; then
    echo "→ Generating encryption keys..."
    php artisan passport:keys --force
    echo "  ✓ Keys generated"
else
    echo "  ✓ Keys already exist"
fi

# إنشاء Password Grant Client
if [ -z "$PASSPORT_CLIENT_ID" ] || [ -z "$PASSPORT_CLIENT_SECRET" ]; then
    echo "→ Creating Password Grant Client..."
    
    # حذف الـ clients القديمة
    php artisan tinker --execute="DB::table('oauth_clients')->where('password_client', 1)->delete();" 2>/dev/null || true
    
    # إنشاء client جديد
    CLIENT_OUTPUT=$(php artisan passport:client --password --name="UniHub Mobile App" 2>&1)
    
    CLIENT_ID=$(echo "$CLIENT_OUTPUT" | grep -oP 'Client ID:\s*\K\d+' | head -1)
    CLIENT_SECRET=$(echo "$CLIENT_OUTPUT" | grep -oP 'Client secret:\s*\K\S+' | head -1)
    
    if [ -n "$CLIENT_ID" ] && [ -n "$CLIENT_SECRET" ]; then
        echo ""
        echo "╔════════════════════════════════════════╗"
        echo "║   🔑 PASSPORT CREDENTIALS             ║"
        echo "╠════════════════════════════════════════╣"
        echo "║ PASSPORT_CLIENT_ID=$CLIENT_ID"
        echo "║ PASSPORT_CLIENT_SECRET=$CLIENT_SECRET"
        echo "╚════════════════════════════════════════╝"
        echo ""
        echo "⚠️  IMPORTANT: Add these to Render Environment!"
    else
        echo "  ⚠️  Could not extract credentials (will use existing)"
    fi
else
    echo "  ✓ Passport already configured"
fi

# ==========================================
# Storage Link
# ==========================================
echo ""
echo "🔗 Creating storage link..."
php artisan storage:link --force 2>&1 | grep -v "already exists" || echo "  ✓ Link created"

# ==========================================
# Production Optimization
# ==========================================
echo ""
echo "⚡ Optimizing for production..."

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "  ✓ Optimization complete"

# ==========================================
# Final Summary
# ==========================================
echo ""
echo "╔════════════════════════════════════════╗"
echo "║  ✅ DEPLOYMENT SUCCESSFUL             ║"
echo "╠════════════════════════════════════════╣"
echo "║ App URL: ${APP_URL}"
echo "║ Frontend: ${FRONTEND_URL}"
echo "║ Database: Connected ✓"
echo "║ Passport: $([ -n "$PASSPORT_CLIENT_ID" ] && echo 'Ready ✓' || echo 'Needs Setup ⚠️')"
echo "╚════════════════════════════════════════╝"
echo ""
echo "🚀 Starting Apache server..."

# Start Apache
exec apache2-foreground