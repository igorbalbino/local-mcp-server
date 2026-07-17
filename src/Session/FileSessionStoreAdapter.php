<?php

declare(strict_types=1);

namespace LocalMcp\Session;

use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Session\SessionStoreInterface as McpSessionStoreInterface;
use Symfony\Component\Uid\Uuid;

final class FileSessionStoreAdapter implements SessionStoreInterface, McpSessionStoreInterface
{
    private readonly FileSessionStore $inner;

    public function __construct(string $directory, int $ttl = 3600)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $this->inner = new FileSessionStore($directory, $ttl);
    }

    public static function forBasePath(string $basePath, int $ttl = 3600): self
    {
        return new self(rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'sessions', $ttl);
    }

    public function exists(Uuid $id): bool
    {
        return $this->inner->exists($id);
    }

    public function has(string $sessionId): bool
    {
        try {
            return $this->exists(Uuid::fromString($sessionId));
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    public function read(Uuid $id): string|false
    {
        return $this->inner->read($id);
    }

    public function write(Uuid $id, string $data): bool
    {
        return $this->inner->write($id, $data);
    }

    public function destroy(Uuid $id): bool
    {
        return $this->inner->destroy($id);
    }

    public function gc(): array
    {
        return $this->inner->gc();
    }

    public function mcpStore(): McpSessionStoreInterface
    {
        return $this;
    }
}
