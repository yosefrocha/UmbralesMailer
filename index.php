<?php
declare(strict_types=1);
const BASE_PATH    = __DIR__;
const APP_PATH     = BASE_PATH . '/app';
const CORE_PATH    = BASE_PATH . '/core';
const CONFIG_PATH  = BASE_PATH . '/config';
const STORAGE_PATH = BASE_PATH . '/storage';

$appConfig = require CONFIG_PATH . '/app.php';

if (!empty($appConfig['debug'])) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

if (!headers_sent()) {
    session_name($appConfig['session_name'] ?? 'app_session');
}

session_start();

// Verificar expiración de sesión por inactividad
if (isset($_SESSION['user_id'])) {
    if (!Session::checkExpiry()) {
        $_SESSION = [];
        session_destroy();
        header('Location: /login?expired=1');
        exit;
    }
}

// Headers de seguridad
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

date_default_timezone_set($appConfig['timezone'] ?? 'America/Mexico_City');

mb_internal_encoding('UTF-8');

spl_autoload_register(function (string $class): void {
    $paths = [
        APP_PATH  . '/controllers/' . $class . '.php',
        APP_PATH  . '/models/'      . $class . '.php',
        APP_PATH  . '/services/'    . $class . '.php',
        CORE_PATH . '/'             . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});

$app = new App();
$app->run();