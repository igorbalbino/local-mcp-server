<?php

declare(strict_types=1);

namespace LocalMcp\Transport;

use LocalMcp\Core\Config;
use LocalMcp\Protocol\McpServerFacade;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Transport\Http\Middleware\ProtocolVersionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use ReflectionObject;
use Symfony\Component\Uid\Uuid;

final class TransportFactory
{
    public function __construct(
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly McpServerFacade $mcpServer,
        private readonly GetSseHandler $getSseHandler,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'GET') {
            return $this->getSseHandler->handle($request);
        }

        $psr17 = new Psr17Factory();
        $transport = new StreamableHttpTransport(
            request: $request,
            responseFactory: $psr17,
            streamFactory: $psr17,
            logger: $this->logger,
            middleware: $this->buildMiddleware($psr17),
        );

        $response = $this->mcpServer->run($transport);

        return $this->ensureSessionHeader($response, $transport);
    }

    /**
     * @return list<MiddlewareInterface>
     */
    private function buildMiddleware(Psr17Factory $psr17): array
    {
        return [
            new CorsMiddleware(allowedOrigins: $this->config->corsOrigins()),
            new DnsRebindingProtectionMiddleware(
                allowedHosts: $this->config->allowedHosts(),
                responseFactory: $psr17,
                streamFactory: $psr17,
            ),
            new ProtocolVersionMiddleware(
                supportedVersions: null,
                responseFactory: $psr17,
                streamFactory: $psr17,
            ),
        ];
    }

    private function ensureSessionHeader(
        ResponseInterface $response,
        StreamableHttpTransport $transport,
    ): ResponseInterface {
        if ($response->hasHeader(StreamableHttpTransport::SESSION_HEADER)) {
            return $response;
        }

        $sessionId = $this->readTransportSessionId($transport);
        if ($sessionId === null) {
            return $response;
        }

        return $response->withHeader(StreamableHttpTransport::SESSION_HEADER, $sessionId);
    }

    private function readTransportSessionId(StreamableHttpTransport $transport): ?string
    {
        $reflection = new ReflectionObject($transport);
        if (!$reflection->hasProperty('sessionId')) {
            return null;
        }

        $property = $reflection->getProperty('sessionId');
        $property->setAccessible(true);
        $sessionId = $property->getValue($transport);

        if ($sessionId instanceof Uuid) {
            return $sessionId->toRfc4122();
        }

        return is_string($sessionId) && $sessionId !== '' ? $sessionId : null;
    }
}
