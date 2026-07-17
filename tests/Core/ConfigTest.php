<?php

declare(strict_types=1);

namespace LocalMcp\Tests\Core;

use LocalMcp\Core\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testBoolAndListParsing(): void
    {
        $config = new Config([
            'ENABLE_SEARXNG' => 'true',
            'LOCAL_MCP_API_KEYS' => 'a, b, ,c',
            'EMPTY' => '',
        ]);

        self::assertTrue($config->bool('ENABLE_SEARXNG'));
        self::assertFalse($config->bool('MISSING'));
        self::assertSame(['a', 'b', 'c'], $config->list('LOCAL_MCP_API_KEYS'));
        self::assertNull($config->get('EMPTY'));
        self::assertSame('fallback', $config->string('MISSING', 'fallback'));
    }

    public function testTypedAccessors(): void
    {
        $config = new Config([
            'LOCAL_MCP_API_KEYS' => 'k1,k2',
            'LOCAL_MCP_AUTH_MODE' => 'Bearer',
            'LOCAL_MCP_AUTH_LOCATION' => 'header,path',
            'LOCAL_MCP_ALLOWED_HOSTS' => 'ha.local',
            'HA_URL' => 'http://ha',
            'HA_TOKEN' => 'tok',
        ]);

        self::assertSame(['k1', 'k2'], $config->mcpApiKeys());
        self::assertSame('bearer', $config->authMode());
        self::assertSame(['header', 'path'], $config->authLocations());
        self::assertTrue($config->allowsAuthLocation('header'));
        self::assertFalse($config->allowsAuthLocation('query'));
        self::assertContains('local-mcp', $config->allowedHosts());
        self::assertContains('ha.local', $config->allowedHosts());
        self::assertSame('http://ha', $config->homeAssistantUrl());
        self::assertSame('tok', $config->homeAssistantToken());
    }

    public function testAuthLocationsDefault(): void
    {
        $config = new Config([]);

        self::assertSame(['header', 'path', 'query'], $config->authLocations());
    }
}
