<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Tests\Core;

use Jarvis\McpServer\Core\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testReadsVersionFile(): void
    {
        $basePath = dirname(__DIR__, 2);

        self::assertSame('0.1.0', Version::read($basePath));
        self::assertSame('Local MCP Server', Version::NAME);
    }
}
