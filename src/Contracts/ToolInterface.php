<?php

declare(strict_types=1);

namespace LocalMcp\Contracts;

interface ToolInterface
{
    public function name(): string;

    public function description(): string;

    /**
     * @return array<string, mixed>
     */
    public function inputSchema(): array;

    public function isEnabled(): bool;

    /**
     * @param array<string, mixed> $arguments
     *
     * @return string|array<string, mixed>
     */
    public function handle(array $arguments): string|array;
}
