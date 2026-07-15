<?php

declare(strict_types=1);

namespace LocalMcp\Tools\Browserless;

use LocalMcp\Clients\BrowserlessClient;
use LocalMcp\Core\Config;
use LocalMcp\Tools\AbstractTool;

final class BrowserContentTool extends AbstractTool
{
    public function __construct(Config $config, BrowserlessClient $client)
    {
        parent::__construct($config, $client, 'ENABLE_BROWSERLESS');
    }

    public function name(): string
    {
        return 'browser_content';
    }

    public function description(): string
    {
        return 'Fetch rendered HTML content of a URL via Browserless.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'description' => 'Page URL to fetch',
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

        return $this->json($client->content($url));
    }
}
