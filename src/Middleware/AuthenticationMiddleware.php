<?php

declare(strict_types=1);

namespace LocalMcp\Middleware;

use LocalMcp\Auth\RequestAuthenticator;
use LocalMcp\DTO\AuthContext;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthenticationMiddleware implements MiddlewareInterface
{
    public const PATH_TOKEN_ATTRIBUTE = 'local_mcp.path_token';
    public const AUTH_CONTEXT_ATTRIBUTE = 'local_mcp.auth_context';

    public function __construct(
        private readonly RequestAuthenticator $authenticator,
        private readonly ?Psr17Factory $psr17 = null,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pathToken = $request->getAttribute(self::PATH_TOKEN_ATTRIBUTE);
        $pathToken = is_string($pathToken) ? $pathToken : null;

        if (!$this->authenticator->authorize($request, $pathToken)) {
            return $this->unauthorizedResponse();
        }

        $request = $request->withAttribute(
            self::AUTH_CONTEXT_ATTRIBUTE,
            new AuthContext(pathToken: $pathToken, authenticated: true),
        );

        return $handler->handle($request);
    }

    private function unauthorizedResponse(): ResponseInterface
    {
        $psr17 = $this->psr17 ?? new Psr17Factory();
        $response = $psr17->createResponse(401);
        $response->getBody()->write(json_encode([
            'error' => 'Unauthorized',
            'message' => 'Provide a valid API key via Authorization Bearer, /mcp/<key>, or ?api_key=',
        ], JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('WWW-Authenticate', 'Bearer');
    }
}
