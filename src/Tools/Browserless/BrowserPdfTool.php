<?php

declare(strict_types=1);

namespace LocalMcp\Tools\Browserless;

use LocalMcp\Providers\Browserless\BrowserlessProvider;
use LocalMcp\Core\Config;
use LocalMcp\Tools\AbstractTool;

final class BrowserPdfTool extends AbstractTool
{
    public function __construct(Config $config, BrowserlessProvider $provider)
    {
        parent::__construct($config, $provider, 'ENABLE_BROWSERLESS');
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
        /** @var BrowserlessProvider $provider */
        $provider = $this->provider;
        $url = $this->requireString($arguments, 'url');

        return $this->json($provider->pdf($url));
    }
}
