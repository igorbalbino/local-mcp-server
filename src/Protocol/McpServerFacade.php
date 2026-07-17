<?php

declare(strict_types=1);

namespace LocalMcp\Protocol;

use LocalMcp\Contracts\ToolInterface;
use LocalMcp\Core\Config;
use LocalMcp\Core\Container;
use LocalMcp\Core\ToolRegistry;
use LocalMcp\Core\Version;
use LocalMcp\Exceptions\IntegrationException;
use LocalMcp\Session\SessionStoreInterface;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server as McpServer;
use Mcp\Server\RequestContext;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Adapts the official mcp/sdk server: initialize, tools/list, tools/call, session, protocolVersion.
 */
final class McpServerFacade
{
    public function __construct(
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly ToolRegistry $registry,
        private readonly SessionStoreInterface $sessionStore,
        private readonly Container $container,
        private readonly string $basePath,
    ) {
    }

    public function run(StreamableHttpTransport $transport): ResponseInterface
    {
        $version = Version::read($this->basePath);

        $builder = McpServer::builder()
            ->setServerInfo(
                $this->config->mcpServerName(),
                $this->config->string('MCP_SERVER_VERSION', $version),
                'Local MCP Server — modular tools for AI agents',
            )
            ->setSession($this->sessionStore->mcpStore())
            ->setLogger($this->logger)
            ->setContainer($this->container)
            ->setInstructions('Use the available tools to interact with external services. Credentials are handled by the server.');

        foreach ($this->registry->all() as $tool) {
            $builder->addTool(
                handler: $this->createHandler($tool),
                name: $tool->name(),
                description: $tool->description(),
                inputSchema: $tool->inputSchema(),
            );
        }

        return $builder->build()->run($transport);
    }

    private function createHandler(ToolInterface $tool): \Closure
    {
        return function (RequestContext $context) use ($tool): string {
            $request = $context->getRequest();
            $arguments = $request instanceof CallToolRequest ? $request->arguments : [];

            try {
                $result = $tool->handle($arguments);

                return is_string($result)
                    ? $result
                    : json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (IntegrationException $e) {
                return json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $this->logger->error('Tool execution failed', [
                    'tool' => $tool->name(),
                    'message' => $e->getMessage(),
                ]);

                return json_encode(['error' => 'Tool execution failed'], JSON_THROW_ON_ERROR);
            }
        };
    }
}
