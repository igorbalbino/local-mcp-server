<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Core;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public static function create(Config $config, string $basePath): LoggerInterface
    {
        $level = Level::fromName(strtoupper($config->string('LOG_LEVEL', 'info')));
        $logDir = $basePath . '/storage/logs';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $logger = new Logger('jarvis');
        $logger->pushHandler(new StreamHandler($logDir . '/app.log', $level));
        $logger->pushHandler(new StreamHandler('php://stderr', $level));

        return $logger;
    }
}
