<?php

declare(strict_types=1);

namespace LocalMcp\Core;

use LocalMcp\Exceptions\ConfigurationException;

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

    /**
     * @return list<string>
     */
    public function mcpApiKeys(): array
    {
        return $this->list('LOCAL_MCP_API_KEYS');
    }

    public function authMode(): string
    {
        return strtolower($this->string('LOCAL_MCP_AUTH_MODE', 'auto'));
    }

    /**
     * @return list<string>
     */
    public function authLocations(): array
    {
        $locations = array_map(
            static fn (string $item): string => strtolower($item),
            $this->list('LOCAL_MCP_AUTH_LOCATION'),
        );

        if ($locations === []) {
            return ['header', 'path', 'query'];
        }

        return array_values(array_unique($locations));
    }

    public function allowsAuthLocation(string $location): bool
    {
        return in_array(strtolower($location), $this->authLocations(), true);
    }

    /**
     * @return list<string>
     */
    public function allowedHosts(): array
    {
        $hosts = $this->list('LOCAL_MCP_ALLOWED_HOSTS');
        $defaults = ['localhost', '127.0.0.1', '[::1]', 'local-mcp'];

        return array_values(array_unique([...$defaults, ...$hosts]));
    }

    /**
     * @return list<string>
     */
    public function corsOrigins(): array
    {
        $origins = $this->list('LOCAL_MCP_CORS_ORIGINS');

        return $origins === [] ? ['*'] : $origins;
    }

    public function mcpServerName(): string
    {
        return $this->string('MCP_SERVER_NAME', Version::NAME);
    }

    public function homeAssistantUrl(): string
    {
        return $this->string('HA_URL');
    }

    public function homeAssistantToken(): ?string
    {
        return $this->get('HA_TOKEN');
    }

    public function searxngUrl(): string
    {
        return $this->string('SEARXNG_URL');
    }

    public function searxngApiKey(): ?string
    {
        return $this->get('SEARXNG_API_KEY');
    }

    public function browserlessUrl(): string
    {
        return $this->string('BROWSERLESS_URL');
    }

    public function browserlessToken(): ?string
    {
        return $this->get('BROWSERLESS_TOKEN');
    }

    public function meilisearchUrl(): string
    {
        return $this->string('MEILI_URL');
    }

    public function meilisearchKey(): ?string
    {
        return $this->get('MEILI_KEY');
    }

    public function meilisearchIndex(): string
    {
        return $this->string('MEILI_INDEX', 'documents');
    }

    public function libreTranslateUrl(): string
    {
        return $this->string('LIBRETRANSLATE_URL');
    }

    public function libreTranslateApiKey(): ?string
    {
        return $this->get('LIBRETRANSLATE_API_KEY');
    }
}
