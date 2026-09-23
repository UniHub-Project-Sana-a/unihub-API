#!/bin/bash
set -e

echo "🚀 Starting University System Backend on Render..."

# ==========================================
# انتظار قاعدة البيانات PostgreSQL
# ==========================================
if [ -n "$DATABASE_URL" ]; then
    echo "⏳ Waiting for database..."
    
    # استخراج معلومات الاتصال من DATABASE_URL
    DB_HOST=$(echo $DATABASE_URL | sed -n 's/.*@\([^:\/]*\).*/\1/p')
    DB_PORT=$(echo $DATABASE_URL | sed -n 's/.*:\([0-9]*\)\/.*/\1/p')
    DB_NAME=$(echo $DATABASE_URL | sed -n 's/.*\/\([^?]*\).*/\1/p')
    DB_USER=$(echo $DATABASE_URL | sed -n 's/.*:\/\/\([^:]*\):.*/\1/p')
    DB_PASS=$(echo $DATABASE_URL | sed -n 's/.*:\/\/[^:]*:\([^@]*\)@.*/\1/p')
    
    DB_PORT=${DB_PORT:-5432}
    
    # انتظار PostgreSQL
    for i in {1..30}; do
        if PGPASSWORD=$DB_PASS psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c '\q' 2>/dev/null; then
            echo "✅ Database connected!"
            break
        fi
        echo "Attempt $i: Waiting for PostgreSQL..."
        sleep 2
    done
fi

# ==========================================
# إنشاء ملف .env من المتغيرات البيئية
# ==========================================
echo "📝 Creating .env file..."

cat > .env <<EOF
APP_NAME="${APP_NAME:-UniHub}"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL}
APP_TIMEZONE=UTC

FRONTEND_URL=${FRONTEND_URL:-https://unihub-react.vercel.app}

LOG_CHANNEL=errorlog
LOG_LEVEL=${LOG_LEVEL:-error}

DB_CONNECTION=pgsql
DATABASE_URL=${DATABASE_URL}

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

PASSPORT_CLIENT_ID=${PASSPORT_CLIENT_ID}
PASSPORT_CLIENT_SECRET=${PASSPORT_CLIENT_SECRET}

BCRYPT_ROUNDS=12

VITE_APP_NAME="${APP_NAME:-UniHub}"
EOF

echo "✅ .env file created"

# ==========================================
# توليد APP_KEY إذا لم يكن موجوداً
# ==========================================
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating APP_KEY..."
    php artisan key:generate --force
    echo "⚠️  IMPORTANT: Copy the generated APP_KEY and add it to Render environment variables!"
fi

# ==========================================
# تشغيل Migrations
# ==========================================
echo "📊 Running database migrations..."
php artisan migrate --force || {
    echo "❌ Migration failed!"
    php artisan migrate:status
    exit 1
}

# ==========================================
# تشغيل Seeders
# ==========================================
echo "🌱 Seeding database..."
php artisan db:seed --force --class=UserTypesSeeder || echo "⚠️  UserTypesSeeder already run"
php artisan db:seed --force --class=PermissionsSeeder || echo "⚠️  PermissionsSeeder already run"
php artisan db:seed --force --class=DaysSeeder || echo "⚠️  DaysSeeder already run"
php artisan db:seed --force --class=SettingsSeeder || echo "⚠️  SettingsSeeder already run"

# ==========================================
# توليد مفاتيح Passport
# ==========================================
echo "🔐 Setting up Passport..."
if [ ! -f storage/oauth-private.key ]; then
    echo "🔑 Generating Passport keys..."
    php artisan passport:keys --force
fi

# إنشاء Passport Client
if [ -z "$PASSPORT_CLIENT_ID" ] || [ -z "$PASSPORT_CLIENT_SECRET" ]; then
    echo "🔐 Creating Passport client..."
    OUTPUT=$(php artisan passport:client --password --name="UniHub Mobile Client" --no-interaction 2>&1) || true
    
    echo "$OUTPUT"
    
    CLIENT_ID=$(echo "$OUTPUT" | grep -oP 'Client ID: \K\d+' | head -1)
    CLIENT_SECRET=$(echo "$OUTPUT" | grep -oP 'Client secret: \K[^\s]+' | head -1)
    
    if [ -n "$CLIENT_ID" ] && [ -n "$CLIENT_SECRET" ]; then
        echo ""
        echo "========================================="
        echo "⚠️  CRITICAL - Save these credentials:"
        echo "========================================="
        echo "PASSPORT_CLIENT_ID=$CLIENT_ID"
        echo "PASSPORT_CLIENT_SECRET=$CLIENT_SECRET"
        echo "========================================="
        echo "⚠️  Add them to Render environment variables!"
        echo ""
    fi
fi

# ==========================================
# Storage link
# ==========================================
echo "🔗 Creating storage link..."
php artisan storage:link --force || echo "⚠️  Storage link already exists"

# ==========================================
# تحسين الأداء
# ==========================================
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ==========================================
# عرض معلومات النظام
# ==========================================
echo ""
echo "=================================="
echo "✅ Backend is ready!"
echo "=================================="
echo "🌐 Application URL: ${APP_URL}"
echo "🎯 Frontend URL: ${FRONTEND_URL}"
echo "📧 Mail From: ${MAIL_FROM_ADDRESS}"
echo "=================================="
echo ""
echo "🚀 Starting Apache server..."

# تشغيل Apache
exec apache2-foreground