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

$_SERVER['APP_ENV'] ??= 'test';
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'];
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';
if (\function_exists('putenv')) {
    putenv('APP_DEBUG=0');
}

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}
