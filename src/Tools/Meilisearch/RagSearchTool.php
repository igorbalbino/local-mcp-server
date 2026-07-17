<?php

declare(strict_types=1);

namespace LocalMcp\Tools\Meilisearch;

use LocalMcp\Providers\Meilisearch\MeilisearchProvider;
use LocalMcp\Core\Config;
use LocalMcp\Tools\AbstractTool;

final class RagSearchTool extends AbstractTool
{
    public function __construct(Config $config, MeilisearchProvider $provider)
    {
        parent::__construct($config, $provider, 'ENABLE_MEILISEARCH');
    }

    public function name(): string
    {
        return 'rag_search';
    }

    public function description(): string
    {
        return 'Search indexed documents in Meilisearch (RAG retrieval).';
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
                'index' => [
                    'type' => 'string',
                    'description' => 'Optional index name (defaults to MEILI_INDEX)',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Max results (default 10)',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function handle(array $arguments): string|array
    {
        /** @var MeilisearchProvider $provider */
        $provider = $this->provider;
        $query = $this->requireString($arguments, 'query');
        $index = $this->optionalString($arguments, 'index');
        $limit = $this->optionalInt($arguments, 'limit', 10) ?? 10;

        $result = $provider->search($query, $index, $limit);

        return $this->json([
            'query' => $query,
            'hits' => $result['hits'] ?? [],
            'estimatedTotalHits' => $result['estimatedTotalHits'] ?? $result['nbHits'] ?? null,
            'processingTimeMs' => $result['processingTimeMs'] ?? null,
        ]);
    }
}
