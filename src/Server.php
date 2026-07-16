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
use Mcp\Server\Transport\CallbackStream;
use Mcp\Server\Transport\StreamableHttpTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use ReflectionObject;
use Symfony\Component\Uid\Uuid;

final class Server
{
    private const SESSION_HEADER = 'Mcp-Session-Id';

    /** Max lifetime for a standalone GET SSE stream (seconds). */
    private const SSE_GET_TIMEOUT_SECONDS = 300;

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
        if ($request->getMethod() === 'GET') {
            return $this->handleMcpGet($request);
        }

        $config = $this->container->get(Config::class);
        $logger = $this->container->get(LoggerInterface::class);
        $registry = $this->container->get(ToolRegistry::class);

        $sessionDir = $this->sessionDirectory();
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

        // Empty middleware disables SDK DNS-rebinding allowlist (localhost-only),
        // so Docker hostnames like local-mcp work. Auth stays in Server::handle.
        $transport = new StreamableHttpTransport(
            request: $request,
            responseFactory: $psr17,
            streamFactory: $psr17,
            logger: $logger,
            middleware: [],
        );

        $response = $mcpServer->run($transport);

        return $this->ensureSessionHeader($response, $transport);
    }

    /**
     * Streamable HTTP GET: open an SSE stream for server-initiated messages.
     * Required by Home Assistant's streamable_http_client (SDK returns 405 otherwise).
     */
    private function handleMcpGet(ServerRequestInterface $request): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $logger = $this->container->get(LoggerInterface::class);
        $accept = strtolower($request->getHeaderLine('Accept'));

        if (!str_contains($accept, 'text/event-stream')) {
            return $this->jsonErrorResponse(
                405,
                'Accept must include text/event-stream for GET on the MCP endpoint',
            )->withHeader('Allow', 'POST, DELETE, OPTIONS, GET');
        }

        $sessionHeaders = $request->getHeader(self::SESSION_HEADER);
        if (count($sessionHeaders) !== 1 || $sessionHeaders[0] === '') {
            return $this->jsonErrorResponse(400, self::SESSION_HEADER . ' header is required for GET');
        }

        try {
            $sessionId = Uuid::fromString($sessionHeaders[0]);
        } catch (\InvalidArgumentException) {
            return $this->jsonErrorResponse(400, self::SESSION_HEADER . ' header must be a valid UUID');
        }

        $sessionStore = new FileSessionStore($this->sessionDirectory());
        if (!$sessionStore->exists($sessionId)) {
            return $this->jsonErrorResponse(404, 'Unknown or expired MCP session');
        }

        $sessionIdString = $sessionId->toRfc4122();
        $timeout = self::SSE_GET_TIMEOUT_SECONDS;

        $stream = new CallbackStream(static function () use ($timeout, $logger): void {
            echo ": connected\n\n";
            @ob_flush();
            flush();

            $deadline = time() + $timeout;
            while (!connection_aborted() && time() < $deadline) {
                echo ": keepalive\n\n";
                @ob_flush();
                flush();
                usleep(1_000_000);
            }

            $logger->info('MCP GET SSE stream ended', [
                'aborted' => connection_aborted(),
                'timed_out' => time() >= $deadline,
            ]);
        }, $logger);

        return $psr17->createResponse(200)
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withHeader('X-Accel-Buffering', 'no')
            ->withHeader(self::SESSION_HEADER, $sessionIdString)
            ->withBody($stream);
    }

    private function sessionDirectory(): string
    {
        return $this->basePath . '/storage/cache/sessions';
    }

    private function jsonErrorResponse(int $status, string $message): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $body = json_encode([
            'jsonrpc' => '2.0',
            'id' => null,
            'error' => [
                'code' => -32600,
                'message' => $message,
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $psr17->createResponse($status);
        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Some SDK response paths omit Mcp-Session-Id; HA Streamable clients need it.
     */
    private function ensureSessionHeader(
        ResponseInterface $response,
        StreamableHttpTransport $transport,
    ): ResponseInterface {
        if ($response->hasHeader('Mcp-Session-Id')) {
            return $response;
        }

        $sessionId = $this->readTransportSessionId($transport);
        if ($sessionId === null) {
            return $response;
        }

        return $response->withHeader('Mcp-Session-Id', $sessionId);
    }

    private function readTransportSessionId(StreamableHttpTransport $transport): ?string
    {
        $reflection = new ReflectionObject($transport);
        if (!$reflection->hasProperty('sessionId')) {
            return null;
        }

        $property = $reflection->getProperty('sessionId');
        $property->setAccessible(true);
        $sessionId = $property->getValue($transport);

        if ($sessionId instanceof Uuid) {
            return $sessionId->toRfc4122();
        }

        return is_string($sessionId) && $sessionId !== '' ? $sessionId : null;
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
