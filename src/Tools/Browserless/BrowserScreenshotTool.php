<?php

declare(strict_types=1);

namespace LocalMcp\Tools\Browserless;

use LocalMcp\Providers\Browserless\BrowserlessProvider;
use LocalMcp\Core\Config;
use LocalMcp\Tools\AbstractTool;

final class BrowserScreenshotTool extends AbstractTool
{
    public function __construct(Config $config, BrowserlessProvider $provider)
    {
        parent::__construct($config, $provider, 'ENABLE_BROWSERLESS');
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
        /** @var BrowserlessProvider $provider */
        $provider = $this->provider;
        $url = $this->requireString($arguments, 'url');
        $width = $this->optionalInt($arguments, 'width');
        $height = $this->optionalInt($arguments, 'height');

        $result = $provider->screenshot($url, $width, $height);

        return $this->json($result);
    }
}
