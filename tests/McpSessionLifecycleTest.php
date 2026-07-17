<?php

declare(strict_types=1);

namespace LocalMcp\Tests;

use LocalMcp\Protocol\McpServerFacade;
use LocalMcp\Server;
use LocalMcp\Session\SessionStoreInterface;
use Mcp\Server as McpServer;
use Mcp\Server\Transport\StreamableHttpTransport;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class McpSessionLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        Server::resetBoot();

        $_ENV['LOCAL_MCP_API_KEYS'] = 'test-key';
        $_SERVER['LOCAL_MCP_API_KEYS'] = 'test-key';
        $_ENV['LOCAL_MCP_AUTH_MODE'] = 'none';
        $_SERVER['LOCAL_MCP_AUTH_MODE'] = 'none';
        $_ENV['LOCAL_MCP_AUTH_LOCATION'] = 'header,path,query';
        $_SERVER['LOCAL_MCP_AUTH_LOCATION'] = 'header,path,query';
        $_ENV['LOCAL_MCP_ALLOWED_HOSTS'] = 'localhost,127.0.0.1';
        $_SERVER['LOCAL_MCP_ALLOWED_HOSTS'] = 'localhost,127.0.0.1';
    }

    protected function tearDown(): void
    {
        Server::resetBoot();
    }

    public function testBootReusesSameApplicationAndMcpServer(): void
    {
        $basePath = dirname(__DIR__);
        $a = Server::boot($basePath);
        $b = Server::boot($basePath);

        self::assertSame($a, $b);
        self::assertSame($a->container(), $b->container());

        $facadeA = $a->container()->get(McpServerFacade::class);
        $facadeB = $b->container()->get(McpServerFacade::class);
        self::assertSame($facadeA, $facadeB);
        self::assertSame($facadeA->server(), $facadeB->server());
        self::assertSame(
            $a->container()->get(McpServer::class),
            $facadeA->server(),
        );
    }

    public function testSessionStoreMcpStoreReturnsSamePersistentInstance(): void
    {
        $container = Server::boot(dirname(__DIR__))->container();
        $store = $container->get(SessionStoreInterface::class);

        self::assertSame($store, $store->mcpStore());
        self::assertSame($store, $container->get(SessionStoreInterface::class));
    }

    public function testInitializeEchoesIdAndReturnsSessionThenReuseWorks(): void
    {
        $server = Server::boot(dirname(__DIR__));

        $initBody = json_encode([
            'jsonrpc' => '2.0',
            'id' => 42,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-11-25',
                'capabilities' => new \stdClass(),
                'clientInfo' => ['name' => 'phpunit', 'version' => '0'],
            ],
        ], JSON_THROW_ON_ERROR);

        $initRequest = (new ServerRequest('POST', 'http://localhost/mcp'))
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json, text/event-stream')
            ->withBody((new \Nyholm\Psr7\Factory\Psr17Factory())->createStream($initBody));

        $initResponse = $server->handle($initRequest);

        self::assertSame(200, $initResponse->getStatusCode());
        self::assertTrue($initResponse->hasHeader(StreamableHttpTransport::SESSION_HEADER));

        $sessionId = $initResponse->getHeaderLine(StreamableHttpTransport::SESSION_HEADER);
        self::assertNotSame('', $sessionId);

        $payload = $this->decodeJsonRpcBody((string) $initResponse->getBody());
        self::assertSame(42, $payload['id']);
        self::assertArrayHasKey('result', $payload);
        self::assertSame('2025-11-25', $payload['result']['protocolVersion'] ?? null);

        $store = $server->container()->get(SessionStoreInterface::class);
        self::assertTrue($store->has($sessionId));

        $initializedBody = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'notifications/initialized',
        ], JSON_THROW_ON_ERROR);

        $initializedRequest = (new ServerRequest('POST', 'http://localhost/mcp'))
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json, text/event-stream')
            ->withHeader(StreamableHttpTransport::SESSION_HEADER, $sessionId)
            ->withBody((new \Nyholm\Psr7\Factory\Psr17Factory())->createStream($initializedBody));

        $initializedResponse = $server->handle($initializedRequest);
        self::assertNotSame(401, $initializedResponse->getStatusCode());
        self::assertNotSame(404, $initializedResponse->getStatusCode());

        $listBody = json_encode([
            'jsonrpc' => '2.0',
            'id' => 7,
            'method' => 'tools/list',
            'params' => new \stdClass(),
        ], JSON_THROW_ON_ERROR);

        $listRequest = (new ServerRequest('POST', 'http://localhost/mcp'))
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json, text/event-stream')
            ->withHeader(StreamableHttpTransport::SESSION_HEADER, $sessionId)
            ->withBody((new \Nyholm\Psr7\Factory\Psr17Factory())->createStream($listBody));

        $listResponse = $server->handle($listRequest);
        self::assertSame(200, $listResponse->getStatusCode());

        $listPayload = $this->decodeJsonRpcBody((string) $listResponse->getBody());
        self::assertSame(7, $listPayload['id']);
        self::assertArrayHasKey('result', $listPayload);
        self::assertArrayHasKey('tools', $listPayload['result']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonRpcBody(string $body): array
    {
        $trimmed = trim($body);
        if (str_starts_with($trimmed, 'event:') || str_contains($trimmed, 'data:')) {
            foreach (explode("\n", $trimmed) as $line) {
                if (str_starts_with($line, 'data:')) {
                    $trimmed = trim(substr($line, 5));
                    break;
                }
            }
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
