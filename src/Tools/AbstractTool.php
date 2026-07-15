<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Tools;

use Jarvis\McpServer\Contracts\ServiceClientInterface;
use Jarvis\McpServer\Contracts\ToolInterface;
use Jarvis\McpServer\Core\Config;
use Jarvis\McpServer\Exceptions\IntegrationException;

abstract class AbstractTool implements ToolInterface
{
    public function __construct(
        protected readonly Config $config,
        protected readonly ServiceClientInterface $client,
        protected readonly string $enableFlag,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->bool($this->enableFlag) && $this->client->isConfigured();
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function json(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    protected function requireString(array $arguments, string $key): string
    {
        if (!isset($arguments[$key]) || !is_string($arguments[$key]) || $arguments[$key] === '') {
            throw new IntegrationException(sprintf('Missing or invalid argument: %s', $key));
        }

        return $arguments[$key];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    protected function optionalString(array $arguments, string $key): ?string
    {
        if (!isset($arguments[$key]) || !is_string($arguments[$key]) || $arguments[$key] === '') {
            return null;
        }

        return $arguments[$key];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    protected function optionalInt(array $arguments, string $key, ?int $default = null): ?int
    {
        if (!isset($arguments[$key])) {
            return $default;
        }

        if (is_int($arguments[$key])) {
            return $arguments[$key];
        }

        if (is_numeric($arguments[$key])) {
            return (int) $arguments[$key];
        }

        return $default;
    }
}
