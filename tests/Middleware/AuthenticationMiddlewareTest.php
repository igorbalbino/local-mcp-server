<?php

declare(strict_types=1);

namespace LocalMcp\Tests\Middleware;

use LocalMcp\Auth\ApiKeyAuthenticator;
use LocalMcp\Auth\RequestAuthenticator;
use LocalMcp\Core\Config;
use LocalMcp\Middleware\AuthenticationMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthenticationMiddlewareTest extends TestCase
{
    public function testReturnsUnauthorizedWithoutCredentials(): void
    {
        $middleware = new AuthenticationMiddleware(
            new RequestAuthenticator(
                ApiKeyAuthenticator::fromKeys(['secret']),
                new Config(['LOCAL_MCP_AUTH_MODE' => 'auto']),
            ),
            new Psr17Factory(),
        );

        $response = $middleware->process(
            new ServerRequest('POST', '/mcp'),
            $this->okHandler(),
        );

        self::assertSame(401, $response->getStatusCode());
    }

    public function testPassesThroughWithValidBearer(): void
    {
        $middleware = new AuthenticationMiddleware(
            new RequestAuthenticator(
                ApiKeyAuthenticator::fromKeys(['secret']),
                new Config(['LOCAL_MCP_AUTH_MODE' => 'auto']),
            ),
            new Psr17Factory(),
        );

        $request = (new ServerRequest('POST', '/mcp'))
            ->withHeader('Authorization', 'Bearer secret');

        $response = $middleware->process($request, $this->okHandler());

        self::assertSame(200, $response->getStatusCode());
    }

    public function testPassesThroughWithPathTokenAttribute(): void
    {
        $middleware = new AuthenticationMiddleware(
            new RequestAuthenticator(
                ApiKeyAuthenticator::fromKeys(['secret']),
                new Config(['LOCAL_MCP_AUTH_MODE' => 'auto']),
            ),
            new Psr17Factory(),
        );

        $request = (new ServerRequest('POST', '/mcp/secret'))
            ->withAttribute(AuthenticationMiddleware::PATH_TOKEN_ATTRIBUTE, 'secret');

        $response = $middleware->process($request, $this->okHandler());

        self::assertSame(200, $response->getStatusCode());
    }

    private function okHandler(): RequestHandlerInterface
    {
        $psr17 = new Psr17Factory();

        return new class ($psr17) implements RequestHandlerInterface {
            public function __construct(private Psr17Factory $factory)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->factory->createResponse(200);
            }
        };
    }
}
