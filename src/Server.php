<?php

declare(strict_types=1);

namespace LocalMcp;

use LocalMcp\Auth\RequestAuthenticator;
use LocalMcp\Core\Config;
use LocalMcp\Core\Container;
use LocalMcp\Core\ServiceProvider;
use LocalMcp\Core\Version;
use LocalMcp\DTO\HealthStatus;
use LocalMcp\Middleware\AuthenticationMiddleware;
use LocalMcp\Middleware\LoggingMiddleware;
use LocalMcp\Transport\TransportFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class Server
{
    private function __construct(
        private readonly Container $container,
        private readonly string $basePath,
    ) {
    }

    public static function boot(string $basePath): self
    {
        $provider = new ServiceProvider($basePath);

        return new self($provider->register(), $basePath);
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $normalized = rtrim($path, '/') ?: '/';

        if ($normalized === '/health') {
            return $this->healthResponse();
        }

        if ($request->getMethod() === 'OPTIONS') {
            return $this->optionsResponse();
        }

        $config = $this->container->get(Config::class);
        $route = $this->resolveMcpRoute($normalized, $config);
        if ($route === null) {
            return $this->notFoundResponse();
        }

        $request = $request->withAttribute(AuthenticationMiddleware::PATH_TOKEN_ATTRIBUTE, $route['token']);

        $auth = new AuthenticationMiddleware(
            new RequestAuthenticator(
                $this->container->get(\LocalMcp\Contracts\AuthenticatorInterface::class),
                $config,
            ),
        );
        $logging = new LoggingMiddleware($this->container->get(LoggerInterface::class));
        $transport = $this->container->get(TransportFactory::class);

        $mcpHandler = new class ($transport) implements RequestHandlerInterface {
            public function __construct(private TransportFactory $transport)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->transport->handle($request);
            }
        };

        $authHandler = new class ($auth, $mcpHandler) implements RequestHandlerInterface {
            public function __construct(
                private AuthenticationMiddleware $auth,
                private RequestHandlerInterface $next,
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->auth->process($request, $this->next);
            }
        };

        return $logging->process($request, $authHandler);
    }

    public function handleFromGlobals(): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $creator = new ServerRequestCreator($psr17, $psr17, $psr17, $psr17);

        return $this->handle($creator->fromGlobals());
    }

    /**
     * Canonical MCP paths:
     * - /mcp
     * - /mcp/{api-key} when LOCAL_MCP_AUTH_LOCATION includes path
     * - / (alias of /mcp)
     *
     * @return array{token: ?string}|null
     */
    private function resolveMcpRoute(string $normalized, Config $config): ?array
    {
        if ($normalized === '/' || $normalized === '/mcp') {
            return ['token' => null];
        }

        if (
            $config->allowsAuthLocation('path')
            && preg_match('#^/mcp/([^/]+)$#', $normalized, $matches) === 1
        ) {
            return ['token' => $matches[1]];
        }

        return null;
    }

    private function healthResponse(): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $health = new HealthStatus(
            status: 'ok',
            name: Version::NAME,
            version: Version::read($this->basePath),
            mcp: '/mcp',
        );
        $body = json_encode($health->toArray(), JSON_THROW_ON_ERROR);

        $response = $psr17->createResponse(200);
        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function optionsResponse(): ResponseInterface
    {
        $psr17 = new Psr17Factory();

        return $psr17->createResponse(204)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Mcp-Session-Id, Mcp-Protocol-Version, Last-Event-ID, Authorization, Accept')
            ->withHeader('Access-Control-Expose-Headers', 'Mcp-Session-Id');
    }

    private function notFoundResponse(): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $response = $psr17->createResponse(404);
        $response->getBody()->write(json_encode([
            'error' => 'Not Found',
            'message' => 'Use /mcp (or /mcp/<api-key> when path auth is enabled) for MCP, /health for status',
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
