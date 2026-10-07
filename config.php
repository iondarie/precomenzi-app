<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_name('precomenzi_app');
    session_start();
}

$envFile = __DIR__ . '/.env';
$env = [];
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile, true, INI_SCANNER_TYPED) ?: [];
}

$_ENV = array_merge([
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'precomenzi_app',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'APP_BASE_PATH' => '/precomenzi-app/public',
], $_ENV, $env);

define('DB_HOST', (string)($_ENV['DB_HOST'] ?? '127.0.0.1'));
define('DB_PORT', (string)($_ENV['DB_PORT'] ?? '3306'));
define('DB_NAME', (string)($_ENV['DB_NAME'] ?? 'precomenzi_app'));
define('DB_USER', (string)($_ENV['DB_USER'] ?? 'root'));
define('DB_PASS', (string)($_ENV['DB_PASS'] ?? ''));
define('APP_BASE_PATH', rtrim((string)($_ENV['APP_BASE_PATH'] ?? '/'), '/'));

define('APP_URL', ((($_SERVER['HTTPS'] ?? 'off') === 'on') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . APP_BASE_PATH);

require_once __DIR__ . '/vendor/autoload.php';
