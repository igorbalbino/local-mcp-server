<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Tests\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Jarvis\McpServer\Clients\HomeAssistantClient;
use Jarvis\McpServer\Clients\SearxngClient;
use Jarvis\McpServer\Core\Config;
use PHPUnit\Framework\TestCase;

final class ClientsTest extends TestCase
{
    public function testHomeAssistantListStates(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                ['entity_id' => 'light.kitchen', 'state' => 'on', 'attributes' => ['friendly_name' => 'Kitchen']],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = new HomeAssistantClient(
            new Config([
                'HA_URL' => 'http://ha.test',
                'HA_TOKEN' => 'token',
            ]),
            new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]),
        );

        $states = $client->listStates();

        self::assertCount(1, $states);
        self::assertSame('light.kitchen', $states[0]['entity_id']);
        self::assertTrue($client->isConfigured());
    }

    public function testSearxngSearch(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'query' => 'php mcp',
                'number_of_results' => 1,
                'results' => [
                    ['title' => 'MCP', 'url' => 'https://example.com', 'content' => 'docs'],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $client = new SearxngClient(
            new Config(['SEARXNG_URL' => 'http://searx.test']),
            new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]),
        );

        $result = $client->search('php mcp');

        self::assertSame('php mcp', $result['query']);
        self::assertCount(1, $result['results']);
    }

    public function testHomeAssistantNotConfiguredWithoutToken(): void
    {
        $client = new HomeAssistantClient(new Config(['HA_URL' => 'http://ha.test']));

        self::assertFalse($client->isConfigured());
    }
}
