<?php

declare(strict_types=1);

namespace LocalMcp\Contracts;

interface ProviderInterface
{
    public function isConfigured(): bool;
}
