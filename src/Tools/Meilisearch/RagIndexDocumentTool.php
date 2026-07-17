<?php

declare(strict_types=1);

namespace LocalMcp\Tools\Meilisearch;

use LocalMcp\Providers\Meilisearch\MeilisearchProvider;
use LocalMcp\Core\Config;
use LocalMcp\Exceptions\IntegrationException;
use LocalMcp\Tools\AbstractTool;

final class RagIndexDocumentTool extends AbstractTool
{
    public function __construct(Config $config, MeilisearchProvider $provider)
    {
        parent::__construct($config, $provider, 'ENABLE_MEILISEARCH');
    }

    public function name(): string
    {
        return 'rag_index_document';
    }

    public function description(): string
    {
        return 'Index a document into Meilisearch for later RAG search. Provide an object with at least an id field.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'document' => [
                    'type' => 'object',
                    'description' => 'Document object to index (must include id)',
                ],
                'index' => [
                    'type' => 'string',
                    'description' => 'Optional index name (defaults to MEILI_INDEX)',
                ],
            ],
            'required' => ['document'],
        ];
    }

    public function handle(array $arguments): string|array
    {
        /** @var MeilisearchProvider $provider */
        $provider = $this->provider;

        if (!isset($arguments['document']) || !is_array($arguments['document'])) {
            throw new IntegrationException('Missing or invalid argument: document');
        }

        /** @var array<string, mixed> $document */
        $document = $arguments['document'];
        $index = $this->optionalString($arguments, 'index');

        $result = $provider->indexDocument($document, $index);

        return $this->json([
            'taskUid' => $result['taskUid'] ?? null,
            'indexUid' => $result['indexUid'] ?? $index ?? $provider->getDefaultIndex(),
            'status' => $result['status'] ?? 'enqueued',
        ]);
    }
}
