<?php

declare(strict_types=1);

namespace LocalMcp\Tests\Auth;

use LocalMcp\Auth\ApiKeyAuthenticator;
use LocalMcp\Auth\AuthMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class AuthMiddlewareTest extends TestCase
{
    public function testReturnsUnauthorizedWithoutHeader(): void
    {
        $psr17 = new Psr17Factory();
        $middleware = new AuthMiddleware(ApiKeyAuthenticator::fromKeys(['secret']), $psr17);
        $request = new ServerRequest('POST', '/');

        $response = $middleware->process($request, new class ($psr17) implements \Psr\Http\Server\RequestHandlerInterface {
            public function __construct(private Psr17Factory $factory)
            {
            }

            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return $this->factory->createResponse(200);
            }
        });

        self::assertSame(401, $response->getStatusCode());
    }

    public function testPassesThroughWithValidKey(): void
    {
        $psr17 = new Psr17Factory();
        $middleware = new AuthMiddleware(ApiKeyAuthenticator::fromKeys(['secret']), $psr17);
        $request = new ServerRequest('POST', '/', ['Authorization' => 'Bearer secret']);

        $response = $middleware->process($request, new class ($psr17) implements \Psr\Http\Server\RequestHandlerInterface {
            public function __construct(private Psr17Factory $factory)
            {
            }

            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return $this->factory->createResponse(200);
            }
        });

        self::assertSame(200, $response->getStatusCode());
    }
}
