<?php

declare(strict_types=1);

namespace LocalMcp\Tests\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LocalMcp\Core\Config;
use LocalMcp\Providers\HomeAssistant\HomeAssistantProvider;
use LocalMcp\Providers\SearXNG\SearXNGProvider;
use PHPUnit\Framework\TestCase;

final class ProvidersTest extends TestCase
{
    public function testHomeAssistantListStates(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                ['entity_id' => 'light.kitchen', 'state' => 'on', 'attributes' => ['friendly_name' => 'Kitchen']],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $provider = new HomeAssistantProvider(
            new Config([
                'HA_URL' => 'http://ha.test',
                'HA_TOKEN' => 'token',
            ]),
            new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]),
        );

        $states = $provider->listStates();

        self::assertCount(1, $states);
        self::assertSame('light.kitchen', $states[0]['entity_id']);
        self::assertTrue($provider->isConfigured());
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

        $provider = new SearXNGProvider(
            new Config(['SEARXNG_URL' => 'http://searx.test']),
            new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]),
        );

        $result = $provider->search('php mcp');

        self::assertSame('php mcp', $result['query']);
        self::assertCount(1, $result['results']);
    }

    public function testHomeAssistantNotConfiguredWithoutToken(): void
    {
        $provider = new HomeAssistantProvider(new Config(['HA_URL' => 'http://ha.test']));

        self::assertFalse($provider->isConfigured());
    }
}
