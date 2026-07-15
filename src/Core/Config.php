<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Core;

use Jarvis\McpServer\Exceptions\ConfigurationException;

final class Config
{
    /**
     * @param array<string, string|null> $values
     */
    public function __construct(
        private readonly array $values,
    ) {
    }

    public static function fromEnv(): self
    {
        /** @var array<string, string|null> $values */
        $values = [];

        foreach ($_ENV as $key => $value) {
            if (is_string($key) && (is_string($value) || $value === null)) {
                $values[$key] = $value;
            }
        }

        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && (is_string($value) || $value === null) && !array_key_exists($key, $values)) {
                $values[$key] = $value;
            }
        }

        return new self($values);
    }

    public function get(string $key, ?string $default = null): ?string
    {
        if (!array_key_exists($key, $this->values) || $this->values[$key] === null || $this->values[$key] === '') {
            return $default;
        }

        return $this->values[$key];
    }

    public function require(string $key): string
    {
        $value = $this->get($key);

        if ($value === null) {
            throw new ConfigurationException(sprintf('Missing required configuration: %s', $key));
        }

        return $value;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return list<string>
     */
    public function list(string $key, string $separator = ','): array
    {
        $value = $this->get($key, '');

        if ($value === null || trim($value) === '') {
            return [];
        }

        $items = array_map(trim(...), explode($separator, $value));

        return array_values(array_filter($items, static fn (string $item): bool => $item !== ''));
    }

    public function string(string $key, string $default = ''): string
    {
        return $this->get($key, $default) ?? $default;
    }
}
