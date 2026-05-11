<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

require dirname(__DIR__).'/vendor/autoload.php';

$cacheTestDir = dirname(__DIR__).'/var/cache/test';
if (is_dir($cacheTestDir)) {
    (new Filesystem())->remove($cacheTestDir);
}

$varDir = dirname(__DIR__).'/var';
if (!is_dir($varDir)) {
    mkdir($varDir, 0775, true);
}
$dbPath = str_replace('\\', '/', $varDir.'/test.db');
$dsn = 'sqlite:///'.$dbPath;
$_SERVER['DATABASE_URL'] = $dsn;
$_ENV['DATABASE_URL'] = $dsn;
if (\function_exists('putenv')) {
    putenv('DATABASE_URL='.$dsn);
}

// Всегда test: в Docker Compose у app задан APP_ENV=dev, иначе не подключается framework.test и нет test.service_container.
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';
if (\function_exists('putenv')) {
    putenv('APP_ENV=test');
    putenv('APP_DEBUG=0');
}

$projectDir = dirname(__DIR__);
$envFile = $projectDir.'/.env';
if (!is_file($envFile)) {
    $example = $projectDir.'/.env.example';
    if (is_file($example)) {
        $envFile = $example;
    }
}

if (method_exists(Dotenv::class, 'bootEnv') && is_file($envFile)) {
    (new Dotenv())->bootEnv($envFile);
}

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $dsn;
if (\function_exists('putenv')) {
    putenv('APP_ENV=test');
    putenv('APP_DEBUG=0');
    putenv('DATABASE_URL='.$dsn);
}
