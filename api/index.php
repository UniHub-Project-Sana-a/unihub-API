<?php

// 1. Prepare writable storage directories in /tmp for Vercel Serverless
$storagePaths = [
    '/tmp/storage',
    '/tmp/storage/app',
    '/tmp/storage/app/public',
    '/tmp/storage/framework',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($storagePaths as $path) {
    if (!is_dir($path)) {
        @mkdir($path, 0777, true);
    }
}

// 2. Prepare SQLite Database in /tmp with pre-seeded data
$tmpDatabase = '/tmp/database.sqlite';
if (!file_exists($tmpDatabase) || filesize($tmpDatabase) === 0) {
    $seededDatabase = __DIR__ . '/../database/database.sqlite';
    if (file_exists($seededDatabase) && filesize($seededDatabase) > 0) {
        @copy($seededDatabase, $tmpDatabase);
    } else {
        @touch($tmpDatabase);
    }
}

// 3. Ensure Passport OAuth Keys are accessible
$tmpOauthPrivate = '/tmp/oauth-private.key';
$tmpOauthPublic = '/tmp/oauth-public.key';
if (!file_exists($tmpOauthPrivate)) {
    $seedPrivate = __DIR__ . '/../storage/oauth-private.key';
    if (file_exists($seedPrivate)) {
        @copy($seedPrivate, $tmpOauthPrivate);
        @chmod($tmpOauthPrivate, 0600);
    }
}
if (!file_exists($tmpOauthPublic)) {
    $seedPublic = __DIR__ . '/../storage/oauth-public.key';
    if (file_exists($seedPublic)) {
        @copy($seedPublic, $tmpOauthPublic);
        @chmod($tmpOauthPublic, 0600);
    }
}

// 4. Override runtime environment variables
$envOverrides = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'true',
    'APP_KEY' => getenv('APP_KEY') ?: 'base64:J33kuE8MbjWUQdNDDK5rq36Ac5xBS7rPHpOj+yd44xU=',
    'APP_CONFIG_CACHE' => '/tmp/bootstrap/cache/config.php',
    'APP_EVENTS_CACHE' => '/tmp/bootstrap/cache/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/bootstrap/cache/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/bootstrap/cache/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/bootstrap/cache/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/storage/framework/views',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'cookie',
    'LOG_CHANNEL' => 'stderr',
];

$dbUrl = getenv('DATABASE_URL') ?: (getenv('POSTGRES_URL') ?: getenv('STORAGE_URL'));
if ($dbUrl) {
    $parsed = parse_url($dbUrl);
    $host = $parsed['host'] ?? '';
    $user = $parsed['user'] ?? '';
    $pass = $parsed['pass'] ?? '';
    $dbName = ltrim($parsed['path'] ?? '', '/');
    $port = $parsed['port'] ?? 5432;

    // Neon SNI compatibility via password prefix (recommended for Laravel PDO)
    $hostParts = explode('.', $host);
    $endpointId = $hostParts[0] ?? '';
    if ($endpointId && strpos($host, 'neon.tech') !== false) {
        if (strpos($pass, 'endpoint=') === false) {
            $pass = "endpoint={$endpointId};{$pass}";
        }
    }

    // Reconstruct clean database URL without query string 'options' to avoid PDO option array conflicts
    $cleanDbUrl = "postgresql://{$user}:" . urlencode($pass) . "@{$host}:{$port}/{$dbName}?sslmode=require";

    $envOverrides['DB_CONNECTION'] = 'pgsql';
    $envOverrides['DB_HOST'] = $host;
    $envOverrides['DB_PORT'] = (string)$port;
    $envOverrides['DB_DATABASE'] = $dbName;
    $envOverrides['DB_USERNAME'] = $user;
    $envOverrides['DB_PASSWORD'] = $pass;
    $envOverrides['DB_SSLMODE'] = 'require';
    $envOverrides['DATABASE_URL'] = $cleanDbUrl;
} else {
    $envOverrides['DB_CONNECTION'] = 'sqlite';
    $envOverrides['DB_DATABASE'] = $tmpDatabase;
}

foreach ($envOverrides as $k => $v) {
    putenv("{$k}={$v}");
    $_ENV[$k] = $v;
    $_SERVER[$k] = $v;
}

// Suppress deprecation warnings on PHP 8.5
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Fix SCRIPT_NAME and SCRIPT_FILENAME so Laravel correctly resolves /api routes
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// 5. Delegate request to Laravel's public/index.php
require __DIR__ . '/../public/index.php';

