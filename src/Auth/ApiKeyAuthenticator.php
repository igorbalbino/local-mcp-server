<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Auth;

use Jarvis\McpServer\Contracts\AuthenticatorInterface;
use Jarvis\McpServer\Core\Config;

final class ApiKeyAuthenticator implements AuthenticatorInterface
{
    /** @var list<string> */
    private array $apiKeys;

    public function __construct(Config $config)
    {
        $this->apiKeys = $config->list('JARVIS_API_KEYS');
    }

    /**
     * @param list<string> $apiKeys
     */
    public static function fromKeys(array $apiKeys): self
    {
        $config = new Config(['JARVIS_API_KEYS' => implode(',', $apiKeys)]);

        return new self($config);
    }

    public function authenticate(?string $authorizationHeader): bool
    {
        if ($this->apiKeys === []) {
            return false;
        }

        if ($authorizationHeader === null || $authorizationHeader === '') {
            return false;
        }

        if (!preg_match('/^Bearer\s+(\S+)$/i', $authorizationHeader, $matches)) {
            return false;
        }

        $provided = $matches[1];

        foreach ($this->apiKeys as $validKey) {
            if (hash_equals($validKey, $provided)) {
                return true;
            }
        }

        return false;
    }
}
