<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Auto-correct case for Apache XAMPP subdirectory (crm -> CRM)
if (isset($_SERVER['REQUEST_URI']) && preg_match('#^/crm(/.*)?$#', $_SERVER['REQUEST_URI'], $matches)) {
    header('Location: /CRM' . ($matches[1] ?? ''));
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
