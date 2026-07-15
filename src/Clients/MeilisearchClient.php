<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Clients;

use Jarvis\McpServer\Core\Config;

final class MeilisearchClient extends AbstractHttpClient
{
    private readonly string $defaultIndex;

    public function __construct(Config $config, ?\GuzzleHttp\Client $http = null)
    {
        parent::__construct(
            baseUrl: $config->string('MEILI_URL'),
            token: $config->get('MEILI_KEY'),
            http: $http,
        );
        $this->defaultIndex = $config->string('MEILI_INDEX', 'documents');
    }

    public function getDefaultIndex(): string
    {
        return $this->defaultIndex;
    }

    public function isConfigured(): bool
    {
        return parent::isConfigured() && $this->defaultIndex !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function search(string $query, ?string $index = null, int $limit = 10): array
    {
        $indexName = $index ?: $this->defaultIndex;

        $response = $this->request('POST', sprintf('indexes/%s/search', rawurlencode($indexName)), [
            'headers' => array_merge($this->authHeaders(), ['Content-Type' => 'application/json']),
            'json' => [
                'q' => $query,
                'limit' => $limit,
            ],
        ]);
        $this->ensureSuccess($response, 'Meilisearch');

        return $this->decodeJson($response);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    public function indexDocument(array $document, ?string $index = null): array
    {
        $indexName = $index ?: $this->defaultIndex;

        $response = $this->request('POST', sprintf('indexes/%s/documents', rawurlencode($indexName)), [
            'headers' => array_merge($this->authHeaders(), ['Content-Type' => 'application/json']),
            'json' => [$document],
        ]);
        $this->ensureSuccess($response, 'Meilisearch');

        return $this->decodeJson($response);
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        if ($this->token === null || $this->token === '') {
            return [];
        }

        return ['Authorization' => 'Bearer ' . $this->token];
    }
}
