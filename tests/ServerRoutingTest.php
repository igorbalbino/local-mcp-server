<?php

declare(strict_types=1);

namespace LocalMcp\Tests;

use LocalMcp\Server;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class ServerRoutingTest extends TestCase
{
    protected function setUp(): void
    {
        Server::resetBoot();

        $_ENV['LOCAL_MCP_API_KEYS'] = 'test-key';
        $_SERVER['LOCAL_MCP_API_KEYS'] = 'test-key';
        $_ENV['LOCAL_MCP_AUTH_MODE'] = 'auto';
        $_SERVER['LOCAL_MCP_AUTH_MODE'] = 'auto';
        $_ENV['LOCAL_MCP_AUTH_LOCATION'] = 'header,path,query';
        $_SERVER['LOCAL_MCP_AUTH_LOCATION'] = 'header,path,query';
        $_ENV['LOCAL_MCP_ALLOWED_HOSTS'] = 'localhost,127.0.0.1';
        $_SERVER['LOCAL_MCP_ALLOWED_HOSTS'] = 'localhost,127.0.0.1';
    }

    protected function tearDown(): void
    {
        Server::resetBoot();
    }

    public function testHealthIsPublic(): void
    {
        $server = Server::boot(dirname(__DIR__));
        $response = $server->handle(new ServerRequest('GET', 'http://localhost/health'));

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ok', $body['status']);
        self::assertSame('/mcp', $body['mcp']);
    }

    public function testUnknownPathIsNotFound(): void
    {
        $server = Server::boot(dirname(__DIR__));
        $response = $server->handle(new ServerRequest('GET', 'http://localhost/nope'));

        self::assertSame(404, $response->getStatusCode());
    }

    public function testMcpWithoutCredentialsIsUnauthorized(): void
    {
        $server = Server::boot(dirname(__DIR__));
        $response = $server->handle(new ServerRequest('POST', 'http://localhost/mcp'));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testMcpPathTokenAuthorizes(): void
    {
        $server = Server::boot(dirname(__DIR__));
        $response = $server->handle(new ServerRequest('POST', 'http://localhost/mcp/test-key'));

        self::assertNotSame(401, $response->getStatusCode());
        self::assertNotSame(404, $response->getStatusCode());
    }

    public function testPathAuthDisabledReturnsNotFoundForKeySegment(): void
    {
        Server::resetBoot();

        $_ENV['LOCAL_MCP_AUTH_LOCATION'] = 'header';
        $_SERVER['LOCAL_MCP_AUTH_LOCATION'] = 'header';

        $server = Server::boot(dirname(__DIR__));
        $response = $server->handle(new ServerRequest('POST', 'http://localhost/mcp/test-key'));

        self::assertSame(404, $response->getStatusCode());
    }
}
