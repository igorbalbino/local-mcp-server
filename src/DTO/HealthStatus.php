<?php

declare(strict_types=1);

namespace LocalMcp\DTO;

final readonly class HealthStatus
{
    public function __construct(
        public string $status,
        public string $name,
        public string $version,
        public string $mcp = '/mcp',
    ) {
    }

    /**
     * @return array{status: string, name: string, version: string, mcp: string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'name' => $this->name,
            'version' => $this->version,
            'mcp' => $this->mcp,
        ];
    }
}
