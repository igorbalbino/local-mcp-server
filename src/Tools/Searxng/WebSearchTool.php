<?php

declare(strict_types=1);

namespace LocalMcp\Tools\Searxng;

use LocalMcp\Providers\SearXNG\SearXNGProvider;
use LocalMcp\Core\Config;
use LocalMcp\Tools\AbstractTool;

final class WebSearchTool extends AbstractTool
{
    public function __construct(Config $config, SearXNGProvider $provider)
    {
        parent::__construct($config, $provider, 'ENABLE_SEARXNG');
    }

    public function name(): string
    {
        return 'web_search';
    }

    public function description(): string
    {
        return 'Search the web via SearXNG and return a list of results (title, url, content snippet).';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Search query',
                ],
                'pageno' => [
                    'type' => 'integer',
                    'description' => 'Page number (default 1)',
                    'minimum' => 1,
                ],
                'categories' => [
                    'type' => 'string',
                    'description' => 'Optional SearXNG categories (e.g. general, news)',
                ],
                'language' => [
                    'type' => 'string',
                    'description' => 'Optional language code (e.g. en, pt-BR)',
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function handle(array $arguments): string|array
    {
        /** @var SearXNGProvider $provider */
        $provider = $this->provider;
        $query = $this->requireString($arguments, 'query');
        $pageno = $this->optionalInt($arguments, 'pageno', 1) ?? 1;
        $categories = $this->optionalString($arguments, 'categories');
        $language = $this->optionalString($arguments, 'language');

        $raw = $provider->search($query, $pageno, $categories, $language);
        $results = [];

        if (isset($raw['results']) && is_array($raw['results'])) {
            foreach ($raw['results'] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $results[] = [
                    'title' => $item['title'] ?? null,
                    'url' => $item['url'] ?? null,
                    'content' => $item['content'] ?? null,
                    'engine' => $item['engine'] ?? null,
                ];
            }
        }

        return $this->json([
            'query' => $query,
            'number_of_results' => $raw['number_of_results'] ?? count($results),
            'results' => $results,
        ]);
    }
}
