<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Contracts;

interface ServiceClientInterface
{
    public function isConfigured(): bool;
}
