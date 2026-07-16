<?php

declare(strict_types=1);

use LocalMcp\Server;
use Dotenv\Dotenv;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

require dirname(__DIR__) . '/vendor/autoload.php';

$basePath = dirname(__DIR__);

if (is_file($basePath . '/.env')) {
    Dotenv::createImmutable($basePath)->safeLoad();
}

$server = Server::boot($basePath);
$response = $server->handleFromGlobals();

(new SapiEmitter())->emit($response);
