<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Apache Alias exposes the public directory below /unihub-api. Normalize
// that deployment prefix so Laravel matches the same routes as artisan serve.
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$deploymentPrefix = '/unihub-api';

if (str_starts_with($requestUri, $deploymentPrefix.'/')) {
    $_SERVER['REQUEST_URI'] = substr($requestUri, strlen($deploymentPrefix));
}

$app->handleRequest(Request::capture());
