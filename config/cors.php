<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['*'], // <-- للتجربة، اسمح بكل شيء مؤقتًا
'allowed_origins_patterns' => [],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => false, // <-- تطبيقات الموبايل لا تحتاج هذا عادةً مثل المتصفح

];