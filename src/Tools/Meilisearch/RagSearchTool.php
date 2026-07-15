<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Tools\Meilisearch;

use Jarvis\McpServer\Clients\MeilisearchClient;
use Jarvis\McpServer\Core\Config;
use Jarvis\McpServer\Tools\AbstractTool;

final class RagSearchTool extends AbstractTool
{
    public function __construct(Config $config, MeilisearchClient $client)
    {
        parent::__construct($config, $client, 'ENABLE_MEILISEARCH');
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
        /** @var MeilisearchClient $client */
        $client = $this->client;
        $query = $this->requireString($arguments, 'query');
        $index = $this->optionalString($arguments, 'index');
        $limit = $this->optionalInt($arguments, 'limit', 10) ?? 10;

        $result = $client->search($query, $index, $limit);

        return $this->json([
            'query' => $query,
            'hits' => $result['hits'] ?? [],
            'estimatedTotalHits' => $result['estimatedTotalHits'] ?? $result['nbHits'] ?? null,
            'processingTimeMs' => $result['processingTimeMs'] ?? null,
        ]);
    }
}
