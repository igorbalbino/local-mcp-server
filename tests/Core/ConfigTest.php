<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Tests\Core;

use Jarvis\McpServer\Core\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testBoolAndListParsing(): void
    {
        $config = new Config([
            'ENABLE_SEARXNG' => 'true',
            'JARVIS_API_KEYS' => 'a, b, ,c',
            'EMPTY' => '',
        ]);

        self::assertTrue($config->bool('ENABLE_SEARXNG'));
        self::assertFalse($config->bool('MISSING'));
        self::assertSame(['a', 'b', 'c'], $config->list('JARVIS_API_KEYS'));
        self::assertNull($config->get('EMPTY'));
        self::assertSame('fallback', $config->string('MISSING', 'fallback'));
    }
}
