<?php

declare(strict_types=1);

namespace LocalMcp\Tools\Browserless;

use LocalMcp\Clients\BrowserlessClient;
use LocalMcp\Core\Config;
use LocalMcp\Tools\AbstractTool;

final class BrowserScreenshotTool extends AbstractTool
{
    public function __construct(Config $config, BrowserlessClient $client)
    {
        parent::__construct($config, $client, 'ENABLE_BROWSERLESS');
    }

    public function name(): string
    {
        return 'browser_screenshot';
    }

    public function description(): string
    {
        return 'Take a screenshot of a URL via Browserless. Returns base64-encoded image data.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'description' => 'Page URL to capture',
                ],
                'width' => [
                    'type' => 'integer',
                    'description' => 'Optional viewport width',
                ],
                'height' => [
                    'type' => 'integer',
                    'description' => 'Optional viewport height',
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
        $width = $this->optionalInt($arguments, 'width');
        $height = $this->optionalInt($arguments, 'height');

        $result = $client->screenshot($url, $width, $height);

        return $this->json($result);
    }
}
