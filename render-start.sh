#!/bin/bash
set -e

echo "🚀 Starting University System Backend on Render..."

# ==========================================
# التحقق من المتغيرات المطلوبة
# ==========================================
if [ -z "$DATABASE_URL" ]; then
    echo "❌ ERROR: DATABASE_URL is not set!"
    exit 1
fi

if [ -z "$APP_KEY" ]; then
    echo "❌ ERROR: APP_KEY is not set!"
    exit 1
fi

# ==========================================
# انتظار قاعدة البيانات PostgreSQL
# ==========================================
echo "⏳ Waiting for database connection..."

# استخراج معلومات الاتصال من DATABASE_URL
# Format: postgresql://user:password@host:port/database
DB_USER=$(echo $DATABASE_URL | sed -n 's|.*://\([^:]*\):.*|\1|p')
DB_PASS=$(echo $DATABASE_URL | sed -n 's|.*://[^:]*:\([^@]*\)@.*|\1|p')
DB_HOST=$(echo $DATABASE_URL | sed -n 's|.*@\([^:/]*\).*|\1|p')
DB_PORT=$(echo $DATABASE_URL | sed -n 's|.*:\([0-9]*\)/.*|\1|p')
DB_NAME=$(echo $DATABASE_URL | sed -n 's|.*/\([^?]*\).*|\1|p')

# استخدام القيم الافتراضية
DB_PORT=${DB_PORT:-5432}

echo "Database Host: $DB_HOST"
echo "Database Port: $DB_PORT"
echo "Database Name: $DB_NAME"
echo "Database User: $DB_USER"

# انتظار حتى يصبح PostgreSQL جاهزاً
MAX_TRIES=30
COUNTER=0

until PGPASSWORD=$DB_PASS psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c '\q' 2>/dev/null; do
    COUNTER=$((COUNTER + 1))
    if [ $COUNTER -eq $MAX_TRIES ]; then
        echo "❌ Could not connect to database after $MAX_TRIES attempts"
        exit 1
    fi
    echo "Attempt $COUNTER/$MAX_TRIES: Waiting for PostgreSQL..."
    sleep 2
done

echo "✅ Database connected successfully!"

# ==========================================
# إنشاء ملف .env
# ==========================================
echo "📝 Creating .env file..."

cat > .env <<EOF
APP_NAME="${APP_NAME:-UniHub}"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL}
APP_TIMEZONE=UTC

FRONTEND_URL=${FRONTEND_URL}

LOG_CHANNEL=errorlog
LOG_LEVEL=${LOG_LEVEL:-error}

# Database - استخدام DATABASE_URL مباشرة
DB_CONNECTION=pgsql
DATABASE_URL=${DATABASE_URL}

# تعطيل المعلومات الفردية لأننا نستخدم DATABASE_URL
# DB_HOST=
# DB_PORT=
# DB_DATABASE=
# DB_USERNAME=
# DB_PASSWORD=

CACHE_STORE=database
CACHE_PREFIX=unihub_cache

SESSION_DRIVER=database
SESSION_LIFETIME=480
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none

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

VITE_APP_NAME="${APP_NAME:-UniHub}"
EOF

echo "✅ .env file created"

# ==========================================
# عرض معلومات الاتصال للتأكد
# ==========================================
echo "🔍 Testing database connection..."
php artisan tinker --execute="echo 'DB Connection: ' . config('database.default') . PHP_EOL; echo 'DB URL: ' . (config('database.connections.pgsql.url') ? 'Set' : 'Not Set') . PHP_EOL;" || echo "⚠️  Config check skipped"

# ==========================================
# إنشاء جدول migrations
# ==========================================
echo "📊 Setting up migrations table..."
php artisan migrate:install --force || echo "ℹ️  Migrations table already exists"

# ==========================================
# تشغيل Migrations
# ==========================================
echo "📊 Running database migrations..."
php artisan migrate --force || {
    echo "❌ Migration failed!"
    echo "Attempting to show migration status..."
    php artisan migrate:status || true
    exit 1
}

echo "✅ Migrations completed successfully"

# ==========================================
# تشغيل Seeders
# ==========================================
echo "🌱 Seeding database..."

SEEDERS=("UserTypesSeeder" "PermissionsSeeder" "DaysSeeder" "SettingsSeeder")

for seeder in "${SEEDERS[@]}"; do
    echo "Running $seeder..."
    php artisan db:seed --force --class=$seeder 2>&1 | grep -v "already exists" || echo "✓ $seeder completed"
done

echo "✅ Database seeding completed"

# ==========================================
# Passport Setup
# ==========================================
echo "🔐 Setting up Laravel Passport..."

# توليد المفاتيح
if [ ! -f storage/oauth-private.key ]; then
    echo "🔑 Generating Passport encryption keys..."
    php artisan passport:keys --force
    echo "✅ Passport keys generated"
else
    echo "✓ Passport keys already exist"
fi

# إنشاء Client
if [ -z "$PASSPORT_CLIENT_ID" ] || [ -z "$PASSPORT_CLIENT_SECRET" ]; then
    echo "🔐 Creating Password Grant Client..."
    
    # حذف الـ clients القديمة لتجنب التكرار
    php artisan tinker --execute="DB::table('oauth_clients')->where('password_client', 1)->delete();" 2>/dev/null || true
    
    # إنشاء client جديد
    CLIENT_OUTPUT=$(php artisan passport:client --password --name="UniHub Mobile Client" --no-interaction 2>&1)
    
    echo "$CLIENT_OUTPUT"
    
    # استخراج الـ credentials
    CLIENT_ID=$(echo "$CLIENT_OUTPUT" | grep -oP 'Client ID:\s*\K\d+' | head -1)
    CLIENT_SECRET=$(echo "$CLIENT_OUTPUT" | grep -oP 'Client secret:\s*\K\S+' | head -1)
    
    if [ -n "$CLIENT_ID" ] && [ -n "$CLIENT_SECRET" ]; then
        echo ""
        echo "========================================="
        echo "🔑 PASSPORT CREDENTIALS GENERATED"
        echo "========================================="
        echo "PASSPORT_CLIENT_ID=$CLIENT_ID"
        echo "PASSPORT_CLIENT_SECRET=$CLIENT_SECRET"
        echo "========================================="
        echo ""
        echo "⚠️  IMPORTANT: Add these to Render Environment Variables!"
        echo "⚠️  Then redeploy the service"
        echo ""
    else
        echo "⚠️  Could not extract Passport credentials"
        echo "⚠️  You may need to create them manually later"
    fi
else
    echo "✓ Passport client credentials already configured"
fi

# ==========================================
# Storage Link
# ==========================================
echo "🔗 Creating storage symbolic link..."
php artisan storage:link --force 2>&1 | grep -v "already exists" || echo "✓ Storage link created"

# ==========================================
# تحسين الأداء
# ==========================================
echo "⚡ Optimizing application for production..."

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Optimization completed"

# ==========================================
# عرض ملخص النظام
# ==========================================
echo ""
echo "========================================="
echo "✅ UniHub Backend Deployment Complete!"
echo "========================================="
echo "🌐 Application URL: ${APP_URL}"
echo "🎯 Frontend URL: ${FRONTEND_URL}"
echo "📧 Mail From: ${MAIL_FROM_ADDRESS}"
echo "🗄️  Database: Connected"
echo "🔐 Passport: $([ -n "$PASSPORT_CLIENT_ID" ] && echo 'Configured' || echo 'Needs Configuration')"
echo "========================================="
echo ""
echo "🚀 Starting Apache HTTP Server..."
echo ""

# تشغيل Apache
exec apache2-foreground