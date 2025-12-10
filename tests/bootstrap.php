<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$rootPath = dirname(__DIR__);

require_once $rootPath . '/vendor/autoload.php';

Dotenv::createImmutable($rootPath)->safeLoad();
