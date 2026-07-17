<?php

declare(strict_types=1);

namespace LocalMcp\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $started = microtime(true);
        $response = $handler->handle($request);
        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        $this->logger->info('HTTP request', [
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $elapsedMs,
        ]);

        return $response;
    }
}
