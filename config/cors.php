<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'v1/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // 🔴🔴🔴 هذا هو التعديل الأهم للسماح بالاتصال من واجهاتك
    'allowed_origins' => [
        'http://localhost:5173', // لواجهة React (Vite)
        'http://localhost:3000', // لواجهة React (Create React App)
        'http://localhost:8080', // منفذ Flutter Web الافتراضي
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];