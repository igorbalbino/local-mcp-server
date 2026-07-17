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
 * Holds a single mcp/sdk Server for the process lifetime.
 * Transport is created per HTTP request; the logical MCP server is not.
 */
final class McpServerFacade
{
    private readonly McpServer $server;

    public function __construct(
        Config $config,
        private readonly LoggerInterface $logger,
        ToolRegistry $registry,
        SessionStoreInterface $sessionStore,
        Container $container,
        string $basePath,
    ) {
        $version = Version::read($basePath);

        $builder = McpServer::builder()
            ->setServerInfo(
                $config->mcpServerName(),
                $config->string('MCP_SERVER_VERSION', $version),
                'Local MCP Server — modular tools for AI agents',
            )
            ->setSession($sessionStore->mcpStore())
            ->setLogger($logger)
            ->setContainer($container)
            ->setInstructions('Use the available tools to interact with external services. Credentials are handled by the server.');

        foreach ($registry->all() as $tool) {
            $builder->addTool(
                handler: $this->createHandler($tool),
                name: $tool->name(),
                description: $tool->description(),
                inputSchema: $tool->inputSchema(),
            );
        }

        $this->server = $builder->build();
    }

    public function server(): McpServer
    {
        return $this->server;
    }

    public function run(StreamableHttpTransport $transport): ResponseInterface
    {
        $result = $this->server->run($transport);

        if (!$result instanceof ResponseInterface) {
            throw new \RuntimeException('Expected PSR-7 ResponseInterface from StreamableHttpTransport');
        }

        return $result;
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
