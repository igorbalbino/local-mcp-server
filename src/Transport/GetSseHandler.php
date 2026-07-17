<?php

declare(strict_types=1);

namespace LocalMcp\Transport;

use LocalMcp\Session\SessionStoreInterface;
use Mcp\Server\Transport\CallbackStream;
use Mcp\Server\Transport\StreamableHttpTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Streamable HTTP GET: open an SSE stream for server-initiated messages.
 * Required by Home Assistant's streamable_http_client (SDK returns 405 otherwise).
 */
final class GetSseHandler
{
    /** Max lifetime for a standalone GET SSE stream (seconds). */
    private const TIMEOUT_SECONDS = 300;

    public function __construct(
        private readonly SessionStoreInterface $sessionStore,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $psr17 = new Psr17Factory();
        $accept = strtolower($request->getHeaderLine('Accept'));

        if (!str_contains($accept, 'text/event-stream')) {
            return $this->jsonErrorResponse(
                405,
                'Accept must include text/event-stream for GET on the MCP endpoint',
            )->withHeader('Allow', 'POST, DELETE, OPTIONS, GET');
        }

        $sessionHeaders = $request->getHeader(StreamableHttpTransport::SESSION_HEADER);
        if (count($sessionHeaders) !== 1 || $sessionHeaders[0] === '') {
            return $this->jsonErrorResponse(400, StreamableHttpTransport::SESSION_HEADER . ' header is required for GET');
        }

        try {
            $sessionId = Uuid::fromString($sessionHeaders[0]);
        } catch (\InvalidArgumentException) {
            return $this->jsonErrorResponse(400, StreamableHttpTransport::SESSION_HEADER . ' header must be a valid UUID');
        }

        if (!$this->sessionStore->exists($sessionId)) {
            return $this->jsonErrorResponse(404, 'Unknown or expired MCP session');
        }

        $sessionIdString = $sessionId->toRfc4122();
        $timeout = self::TIMEOUT_SECONDS;
        $logger = $this->logger;

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
            ->withHeader(StreamableHttpTransport::SESSION_HEADER, $sessionIdString)
            ->withBody($stream);
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
}
