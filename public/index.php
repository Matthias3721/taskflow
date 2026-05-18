<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\ErrorHandler;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

$config = require $root . '/config/config.php';

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

ErrorHandler::register($config);

$app = new App();
$app->run();
