<?php

declare(strict_types=1);

namespace LocalMcp\Session;

use Symfony\Component\Uid\Uuid;

/**
 * Application session store. Implementations may wrap the MCP SDK FileSessionStore.
 */
interface SessionStoreInterface
{
    public function exists(Uuid $id): bool;

    public function has(string $sessionId): bool;

    public function read(Uuid $id): string|false;

    public function write(Uuid $id, string $data): bool;

    public function destroy(Uuid $id): bool;

    /**
     * @return list<Uuid>
     */
    public function gc(): array;

    /**
     * Underlying MCP SDK session store for Mcp\Server::builder()->setSession().
     */
    public function mcpStore(): \Mcp\Server\Session\SessionStoreInterface;
}
