<?php

declare(strict_types=1);

namespace LocalMcp\Tools\Browserless;

use LocalMcp\Providers\Browserless\BrowserlessProvider;
use LocalMcp\Core\Config;
use LocalMcp\Tools\AbstractTool;

final class BrowserContentTool extends AbstractTool
{
    public function __construct(Config $config, BrowserlessProvider $provider)
    {
        parent::__construct($config, $provider, 'ENABLE_BROWSERLESS');
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
        /** @var BrowserlessProvider $provider */
        $provider = $this->provider;
        $url = $this->requireString($arguments, 'url');

        return $this->json($provider->content($url));
    }
}
