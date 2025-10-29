<?php

return [

    // طبّق CORS على مسارات الـ API ومسار CSRF إن لزم
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    // اسمح بكل الطرق (GET/POST/PUT/DELETE/OPTIONS...)
    'allowed_methods' => ['*'],

    // اتركها فارغة لأننا سنستخدم الأنماط (patterns) بالأسفل
    'allowed_origins' => [],

    // اسمح بالوصول من localhost/127.0.0.1 و IP جهازك على أي منفذ
    // لاحظ أننا ندعم http و https وأي port
    'allowed_origins_patterns' => [
        '/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/',
        '/^https?:\/\/192\.168\.0\.124(:\d+)?$/',
    ],

    // اسمح بكل الترويسات بما فيها Authorization و Content-Type
    'allowed_headers' => ['*'],

    // لا نعرض ترويسات مخصّصة
    'exposed_headers' => [],

    'max_age' => 0,

    // أبقها false طالما لا تستخدم Cookies عبر المتصفح
    'supports_credentials' => false,
];