<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Contracts;

interface AuthenticatorInterface
{
    public function authenticate(?string $authorizationHeader): bool;
}
