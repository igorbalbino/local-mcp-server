<?php

declare(strict_types=1);

namespace LocalMcp\Core;

use LocalMcp\Contracts\ToolInterface;

final class ToolRegistry
{
    /** @var list<ToolInterface> */
    private array $tools = [];

    /**
     * @param iterable<ToolInterface> $tools
     */
    public function __construct(iterable $tools = [])
    {
        foreach ($tools as $tool) {
            $this->register($tool);
        }
    }

    public function register(ToolInterface $tool): void
    {
        if (!$tool->isEnabled()) {
            return;
        }

        $this->tools[] = $tool;
    }

    /**
     * @return list<ToolInterface>
     */
    public function all(): array
    {
        return $this->tools;
    }

    public function count(): int
    {
        return count($this->tools);
    }

    public function has(string $name): bool
    {
        foreach ($this->tools as $tool) {
            if ($tool->name() === $name) {
                return true;
            }
        }

        return false;
    }
}
