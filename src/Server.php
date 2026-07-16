<?php

declare(strict_types=1);

namespace LocalMcp;

use LocalMcp\Auth\RequestAuthenticator;
use LocalMcp\Contracts\AuthenticatorInterface;
use LocalMcp\Contracts\ToolInterface;
use LocalMcp\Core\Config;
use LocalMcp\Core\Container;
use LocalMcp\Core\ServiceProvider;
use LocalMcp\Core\ToolRegistry;
use LocalMcp\Core\Version;
use LocalMcp\Exceptions\IntegrationException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server as McpServer;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\StreamableHttpTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class Server
{
    private function __construct(
        private readonly Container $container,
        private readonly string $basePath,
    ) {
    }

    public static function boot(string $basePath): self
    {
        $provider = new ServiceProvider($basePath);

        return new self($provider->register(), $basePath);
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $normalized = rtrim($path, '/') ?: '/';

        if ($normalized === '/health') {
            return $this->healthResponse();
        }

        if ($request->getMethod() === 'OPTIONS') {
            return $this->optionsResponse();
        }

        $route = $this->resolveMcpRoute($normalized);
        if ($route === null) {
            return $this->notFoundResponse();
        }

        $authenticator = $this->requestAuthenticator();
        if (!$authenticator->authorize($request, $route['token'])) {
            return $this->unauthorizedResponse();
        }

        return $this->handleMcp($request);
    }

    public function handleFromGlobals(): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $creator = new ServerRequestCreator($psr17, $psr17, $psr17, $psr17);

        return $this->handle($creator->fromGlobals());
    }

    /**
     * Canonical MCP paths:
     * - /mcp
     * - /mcp/{api-key}   (Home Assistant / clients without custom headers)
     * - /                 (alias of /mcp)
     *
     * @return array{token: ?string}|null
     */
    private function resolveMcpRoute(string $normalized): ?array
    {
        if ($normalized === '/' || $normalized === '/mcp') {
            return ['token' => null];
        }

        if (preg_match('#^/mcp/([^/]+)$#', $normalized, $matches) === 1) {
            return ['token' => $matches[1]];
        }

        return null;
    }

    private function requestAuthenticator(): RequestAuthenticator
    {
        return new RequestAuthenticator(
            $this->container->get(AuthenticatorInterface::class),
            $this->container->get(Config::class),
        );
    }

    private function healthResponse(): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $body = json_encode([
            'name' => Version::NAME,
            'version' => Version::read($this->basePath),
            'mcp' => '/mcp',
        ], JSON_THROW_ON_ERROR);

        $response = $psr17->createResponse(200);
        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function optionsResponse(): ResponseInterface
    {
        $psr17 = new Psr17Factory();

        return $psr17->createResponse(204)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Mcp-Session-Id, Mcp-Protocol-Version, Last-Event-ID, Authorization, Accept')
            ->withHeader('Access-Control-Expose-Headers', 'Mcp-Session-Id');
    }

    private function unauthorizedResponse(): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $response = $psr17->createResponse(401);
        $response->getBody()->write(json_encode([
            'error' => 'Unauthorized',
            'message' => 'Provide a valid API key via Authorization Bearer, /mcp/<key>, or ?api_key=',
        ], JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('WWW-Authenticate', 'Bearer');
    }

    private function notFoundResponse(): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $response = $psr17->createResponse(404);
        $response->getBody()->write(json_encode([
            'error' => 'Not Found',
            'message' => 'Use /mcp (or /mcp/<api-key>) for MCP, /health for status',
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function handleMcp(ServerRequestInterface $request): ResponseInterface
    {
        $config = $this->container->get(Config::class);
        $logger = $this->container->get(LoggerInterface::class);
        $registry = $this->container->get(ToolRegistry::class);

        $sessionDir = $this->basePath . '/storage/cache/sessions';
        if (!is_dir($sessionDir)) {
            mkdir($sessionDir, 0775, true);
        }

        $version = Version::read($this->basePath);

        $builder = McpServer::builder()
            ->setServerInfo(
                $config->string('MCP_SERVER_NAME', Version::NAME),
                $config->string('MCP_SERVER_VERSION', $version),
                'Local MCP Server — modular tools for AI agents',
            )
            ->setSession(new FileSessionStore($sessionDir))
            ->setLogger($logger)
            ->setContainer($this->container)
            ->setInstructions('Use the available tools to interact with external services. Credentials are handled by the server.');

        foreach ($registry->all() as $tool) {
            $builder->addTool(
                handler: $this->createHandler($tool),
                name: $tool->name(),
                description: $tool->description(),
                inputSchema: $tool->inputSchema(),
            );
        }

        $mcpServer = $builder->build();
        $psr17 = new Psr17Factory();

        $transport = new StreamableHttpTransport(
            request: $request,
            responseFactory: $psr17,
            streamFactory: $psr17,
            logger: $logger,
        );

        return $mcpServer->run($transport);
    }

    /**
     * Arguments come from CallToolRequest so tools keep a uniform handle(array) API.
     */
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
                $this->container->get(LoggerInterface::class)->error('Tool execution failed', [
                    'tool' => $tool->name(),
                    'message' => $e->getMessage(),
                ]);

                return json_encode(['error' => 'Tool execution failed'], JSON_THROW_ON_ERROR);
            }
        };
    }
}
