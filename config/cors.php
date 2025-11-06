<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // أضف الأصول المحددة هنا إذا لم ترغب باستخدام الأنماط
    ],

    'allowed_origins_patterns' => [
        // اسمح بالوصول من localhost و IP جهازك على أي منفذ
        '/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/',
        '/^https?:\/\/192\.168\.0\.124(:\d+)?$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];