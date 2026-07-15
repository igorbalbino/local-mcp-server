<?php

declare(strict_types=1);

namespace LocalMcp\Auth;

use LocalMcp\Contracts\AuthenticatorInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthenticatorInterface $authenticator,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '') {
            $header = null;
        }

        if (!$this->authenticator->authenticate($header)) {
            $response = $this->responseFactory->createResponse(401);
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized',
                'message' => 'Valid Bearer API key required',
            ], JSON_THROW_ON_ERROR));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('WWW-Authenticate', 'Bearer');
        }

        return $handler->handle($request);
    }
}
