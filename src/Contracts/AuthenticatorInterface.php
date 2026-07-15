<?php

declare(strict_types=1);

namespace LocalMcp\Contracts;

interface AuthenticatorInterface
{
    public function hasKeys(): bool;

    public function isValidKey(string $key): bool;

    public function authenticate(?string $authorizationHeader): bool;
}
