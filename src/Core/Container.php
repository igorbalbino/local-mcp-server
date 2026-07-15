<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Core;

use Jarvis\McpServer\Exceptions\ServiceNotFoundException;
use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface
{
    /** @var array<string, callable(self): object> */
    private array $factories = [];

    /** @var array<string, object> */
    private array $instances = [];

    /**
     * @param callable(self): object $factory
     */
    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            /** @var T */
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new ServiceNotFoundException(sprintf('Service not registered: %s', $id));
        }

        $instance = ($this->factories[$id])($this);
        $this->instances[$id] = $instance;

        /** @var T */
        return $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || isset($this->instances[$id]);
    }
}
