<?php

declare(strict_types=1);

namespace LocalMcp\Contracts;

interface AuthenticatorInterface
{
    public function authenticate(?string $authorizationHeader): bool;
}
