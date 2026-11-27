<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (! is_file(__DIR__.'/../vendor/autoload.php')) {
    http_response_code(500);
    exit('The "vendor" folder is missing. Upload the complete Daily Table package, including vendor/.');
}

// Uploaded with the whole folder in the web root (public_html) instead of pointing the domain at
// public/: the .htaccess next to app/ sends visitors into public/ without "/public" in the address.
// Tell Laravel the real base address so routes and links work, at the domain root or in a sub-folder.
if (preg_match('#^(.*)/public/index\.php$#', $_SERVER['SCRIPT_NAME'] ?? '', $m)) {
    $path = (string) strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    if (! str_starts_with($path, $m[1].'/public/') && $path !== $m[1].'/public') {
        $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $m[1].'/index.php';
    }
}

// Maintenance mode (php artisan down)
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
