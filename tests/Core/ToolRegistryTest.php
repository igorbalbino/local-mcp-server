<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Tests\Core;

use Jarvis\McpServer\Contracts\ToolInterface;
use Jarvis\McpServer\Core\ToolRegistry;
use PHPUnit\Framework\TestCase;

final class ToolRegistryTest extends TestCase
{
    public function testRegistersOnlyEnabledTools(): void
    {
        $enabled = $this->createTool('enabled_tool', true);
        $disabled = $this->createTool('disabled_tool', false);

        $registry = new ToolRegistry([$enabled, $disabled]);

        self::assertSame(1, $registry->count());
        self::assertTrue($registry->has('enabled_tool'));
        self::assertFalse($registry->has('disabled_tool'));
    }

    public function testRegisterIgnoresDisabledTool(): void
    {
        $registry = new ToolRegistry();
        $registry->register($this->createTool('off', false));

        self::assertSame(0, $registry->count());
    }

    private function createTool(string $name, bool $enabled): ToolInterface
    {
        return new class ($name, $enabled) implements ToolInterface {
            public function __construct(
                private readonly string $toolName,
                private readonly bool $enabled,
            ) {
            }

            public function name(): string
            {
                return $this->toolName;
            }

            public function description(): string
            {
                return 'test';
            }

            public function inputSchema(): array
            {
                return ['type' => 'object', 'properties' => []];
            }

            public function isEnabled(): bool
            {
                return $this->enabled;
            }

            public function handle(array $arguments): string|array
            {
                return 'ok';
            }
        };
    }
}
