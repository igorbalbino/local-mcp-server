<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Tools\Browserless;

use Jarvis\McpServer\Clients\BrowserlessClient;
use Jarvis\McpServer\Core\Config;
use Jarvis\McpServer\Tools\AbstractTool;

final class BrowserPdfTool extends AbstractTool
{
    public function __construct(Config $config, BrowserlessClient $client)
    {
        parent::__construct($config, $client, 'ENABLE_BROWSERLESS');
    }

    public function name(): string
    {
        return 'browser_pdf';
    }

    public function description(): string
    {
        return 'Render a URL to PDF via Browserless. Returns base64-encoded PDF data.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'description' => 'Page URL to render as PDF',
                ],
            ],
            'required' => ['url'],
        ];
    }

    public function handle(array $arguments): string|array
    {
        /** @var BrowserlessClient $client */
        $client = $this->client;
        $url = $this->requireString($arguments, 'url');

        return $this->json($client->pdf($url));
    }
}
