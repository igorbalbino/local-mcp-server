<?php

declare(strict_types=1);

namespace LocalMcp\Clients;

use LocalMcp\Core\Config;

final class SearxngClient extends AbstractHttpClient
{
    public function __construct(Config $config, ?\GuzzleHttp\Client $http = null)
    {
        parent::__construct(
            baseUrl: $config->string('SEARXNG_URL'),
            token: $config->get('SEARXNG_API_KEY'),
            http: $http,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function search(string $query, int $pageno = 1, ?string $categories = null, ?string $language = null): array
    {
        $queryParams = [
            'q' => $query,
            'format' => 'json',
            'pageno' => $pageno,
        ];

        if ($categories !== null && $categories !== '') {
            $queryParams['categories'] = $categories;
        }

        if ($language !== null && $language !== '') {
            $queryParams['language'] = $language;
        }

        $headers = $this->bearerHeaders();

        $response = $this->request('GET', 'search', [
            'headers' => $headers,
            'query' => $queryParams,
        ]);
        $this->ensureSuccess($response, 'SearXNG');

        return $this->decodeJson($response);
    }
}
