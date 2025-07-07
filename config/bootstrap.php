<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$pluginDir = dirname(__DIR__, 4);
if (file_exists($pluginEnvPath = $pluginDir . '/tests/TestApplication/.env')) {
    (new Dotenv())->bootEnv($pluginEnvPath);

    $_SERVER['APP_CACHE_DIR'] = $_SERVER['APP_CACHE_DIR'] ?? $pluginDir . '/var/cache';
    $_SERVER['APP_LOG_DIR'] = $_SERVER['APP_LOG_DIR'] ?? $pluginDir . '/var/log';
}

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
$_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_ENV'];
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int) $_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], \FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
