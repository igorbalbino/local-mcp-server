<?php

declare(strict_types=1);

namespace LocalMcp\DTO;

/**
 * Request-scoped auth context attached after AuthenticationMiddleware succeeds.
 */
final readonly class AuthContext
{
    public function __construct(
        public ?string $pathToken = null,
        public bool $authenticated = false,
    ) {
    }
}
