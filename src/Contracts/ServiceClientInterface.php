<?php

declare(strict_types=1);

namespace LocalMcp\Contracts;

interface ServiceClientInterface
{
    public function isConfigured(): bool;
}
