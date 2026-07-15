<?php

declare(strict_types=1);

namespace LocalMcp\Auth;

use LocalMcp\Contracts\AuthenticatorInterface;
use LocalMcp\Core\Config;

final class ApiKeyAuthenticator implements AuthenticatorInterface
{
    /** @var list<string> */
    private array $apiKeys;

    public function __construct(Config $config)
    {
        $this->apiKeys = $config->list('LOCAL_MCP_API_KEYS');
    }

    /**
     * @param list<string> $apiKeys
     */
    public static function fromKeys(array $apiKeys): self
    {
        $config = new Config(['LOCAL_MCP_API_KEYS' => implode(',', $apiKeys)]);

        return new self($config);
    }

    public function hasKeys(): bool
    {
        return $this->apiKeys !== [];
    }

    public function isValidKey(string $key): bool
    {
        if ($key === '' || !$this->hasKeys()) {
            return false;
        }

        foreach ($this->apiKeys as $validKey) {
            if (hash_equals($validKey, $key)) {
                return true;
            }
        }

        return false;
    }

    public function authenticate(?string $authorizationHeader): bool
    {
        if ($authorizationHeader === null || $authorizationHeader === '') {
            return false;
        }

        if (!preg_match('/^Bearer\s+(\S+)$/i', $authorizationHeader, $matches)) {
            return false;
        }

        return $this->isValidKey($matches[1]);
    }
}
