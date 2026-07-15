<?php

declare(strict_types=1);

use LocalMcp\Server;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$basePath = dirname(__DIR__);

if (is_file($basePath . '/.env')) {
    Dotenv::createImmutable($basePath)->safeLoad();
}

$server = Server::boot($basePath);
$response = $server->handleFromGlobals();

http_response_code($response->getStatusCode());

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

echo $response->getBody();
