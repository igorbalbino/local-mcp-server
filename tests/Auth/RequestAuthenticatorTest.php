<?php

declare(strict_types=1);

namespace LocalMcp\Tests\Auth;

use LocalMcp\Auth\ApiKeyAuthenticator;
use LocalMcp\Auth\RequestAuthenticator;
use LocalMcp\Core\Config;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class RequestAuthenticatorTest extends TestCase
{
    public function testPathTokenIsAccepted(): void
    {
        $auth = new RequestAuthenticator(
            ApiKeyAuthenticator::fromKeys(['secret-key']),
            new Config(['LOCAL_MCP_AUTH_MODE' => 'auto']),
        );

        $request = new ServerRequest('POST', 'http://localhost/mcp/secret-key');

        self::assertTrue($auth->authorize($request, 'secret-key'));
    }

    public function testQueryApiKeyIsAccepted(): void
    {
        $auth = new RequestAuthenticator(
            ApiKeyAuthenticator::fromKeys(['secret-key']),
            new Config(['LOCAL_MCP_AUTH_MODE' => 'auto']),
        );

        $request = new ServerRequest('POST', 'http://localhost/mcp?api_key=secret-key');

        self::assertTrue($auth->authorize($request, null));
    }

    public function testBearerHeaderStillWorks(): void
    {
        $auth = new RequestAuthenticator(
            ApiKeyAuthenticator::fromKeys(['secret-key']),
            new Config(['LOCAL_MCP_AUTH_MODE' => 'bearer']),
        );

        $request = (new ServerRequest('POST', 'http://localhost/mcp'))
            ->withHeader('Authorization', 'Bearer secret-key');

        self::assertTrue($auth->authorize($request, null));
    }

    public function testNoneModeIsOpen(): void
    {
        $auth = new RequestAuthenticator(
            ApiKeyAuthenticator::fromKeys(['secret-key']),
            new Config(['LOCAL_MCP_AUTH_MODE' => 'none']),
        );

        $request = new ServerRequest('POST', 'http://localhost/mcp');

        self::assertTrue($auth->authorize($request, null));
        self::assertFalse($auth->isRequired());
    }

    public function testAutoModeOpenWhenNoKeys(): void
    {
        $auth = new RequestAuthenticator(
            ApiKeyAuthenticator::fromKeys([]),
            new Config(['LOCAL_MCP_AUTH_MODE' => 'auto']),
        );

        self::assertFalse($auth->isRequired());
        self::assertTrue($auth->authorize(new ServerRequest('POST', 'http://localhost/mcp'), null));
    }

    public function testPathLocationDisabledRejectsPathToken(): void
    {
        $auth = new RequestAuthenticator(
            ApiKeyAuthenticator::fromKeys(['secret-key']),
            new Config([
                'LOCAL_MCP_AUTH_MODE' => 'auto',
                'LOCAL_MCP_AUTH_LOCATION' => 'header',
            ]),
        );

        $request = new ServerRequest('POST', 'http://localhost/mcp/secret-key');

        self::assertFalse($auth->authorize($request, 'secret-key'));
    }

    public function testHeaderLocationDisabledRejectsBearer(): void
    {
        $auth = new RequestAuthenticator(
            ApiKeyAuthenticator::fromKeys(['secret-key']),
            new Config([
                'LOCAL_MCP_AUTH_MODE' => 'auto',
                'LOCAL_MCP_AUTH_LOCATION' => 'path',
            ]),
        );

        $request = (new ServerRequest('POST', 'http://localhost/mcp'))
            ->withHeader('Authorization', 'Bearer secret-key');

        self::assertFalse($auth->authorize($request, null));
        self::assertTrue($auth->authorize($request, 'secret-key'));
    }
}
