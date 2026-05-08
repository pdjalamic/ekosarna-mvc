<?php
/**
 * Ekošarna Admin — Entry Point
 * Sve zahtevi prolaze kroz ovaj fajl
 */

define('ROOT', __DIR__);
define('APP',  ROOT . '/app');

// Autoloader
spl_autoload_register(function (string $class): void {
    $file = APP . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

// Učitaj konfiguraciju
require_once APP . '/Core/Config.php';
require_once APP . '/helpers.php';

// Pokreni router
require_once APP . '/Core/Router.php';
$router = new Core\Router();
$router->dispatch();
