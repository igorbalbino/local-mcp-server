<?php

declare(strict_types=1);

namespace LocalMcp\Auth;

use LocalMcp\Contracts\AuthenticatorInterface;
use LocalMcp\Core\Config;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves whether an MCP request is allowed.
 *
 * Modes (LOCAL_MCP_AUTH_MODE):
 * - auto (default): no auth if LOCAL_MCP_API_KEYS empty; otherwise require a key
 * - none: always open (trusted LAN / docker network)
 * - bearer: always require a valid key (fails closed if no keys configured)
 *
 * Accepted credentials (any one):
 * - Authorization: Bearer <key>
 * - Path: /mcp/<key>
 * - Query: ?api_key=<key> or ?token=<key>
 */
final class RequestAuthenticator
{
    public function __construct(
        private readonly AuthenticatorInterface $authenticator,
        private readonly Config $config,
    ) {
    }

    public function isRequired(): bool
    {
        $mode = strtolower($this->config->string('LOCAL_MCP_AUTH_MODE', 'auto'));

        return match ($mode) {
            'none' => false,
            'bearer' => true,
            default => $this->authenticator->hasKeys(),
        };
    }

    public function authorize(ServerRequestInterface $request, ?string $pathToken = null): bool
    {
        if (!$this->isRequired()) {
            return true;
        }

        if ($pathToken !== null && $pathToken !== '' && $this->authenticator->isValidKey(rawurldecode($pathToken))) {
            return true;
        }

        $query = $request->getQueryParams();
        foreach (['api_key', 'token'] as $param) {
            $value = $query[$param] ?? null;
            if (is_string($value) && $this->authenticator->isValidKey($value)) {
                return true;
            }
        }

        $header = $request->getHeaderLine('Authorization');

        return $this->authenticator->authenticate($header !== '' ? $header : null);
    }
}
