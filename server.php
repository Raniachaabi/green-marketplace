<?php

/**
 * Front controller for PHP's built-in web server.
 *
 * Laravel 11+ moved this file inside the framework and drives it through
 * `artisan serve`. When that wrapper misbehaves — as it does on some Windows
 * setups — running the built-in server directly against this script is an
 * exact substitute:
 *
 *     php -S 127.0.0.1:9090 -t public server.php
 *
 * Returning false hands the request back to the built-in server so it serves
 * the real file from the document root (public/); anything else is routed
 * into Laravel.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
