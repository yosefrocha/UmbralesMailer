<?php

declare(strict_types=1);

const BASE_PATH = __DIR__ . '/..';
const APP_PATH = BASE_PATH . '/app';
const CORE_PATH = BASE_PATH . '/core';
const CONFIG_PATH = BASE_PATH . '/config';
const STORAGE_PATH = BASE_PATH . '/storage';

$appConfig = require CONFIG_PATH . '/app.php';
if (!headers_sent()) {
    session_name($appConfig['session_name'] ?? 'app_session');
}
session_start();
date_default_timezone_set($appConfig['timezone'] ?? 'UTC');

spl_autoload_register(function (string $class): void {
    $paths = [
        APP_PATH . '/controllers/' . $class . '.php',
        APP_PATH . '/models/' . $class . '.php',
        APP_PATH . '/services/' . $class . '.php',
        CORE_PATH . '/' . $class . '.php',
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
