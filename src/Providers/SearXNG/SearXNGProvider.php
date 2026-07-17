<?php

declare(strict_types=1);

namespace LocalMcp\Providers\SearXNG;

use LocalMcp\Core\Config;
use LocalMcp\Providers\AbstractHttpProvider;

final class SearXNGProvider extends AbstractHttpProvider
{
    public function __construct(Config $config, ?\GuzzleHttp\Client $http = null)
    {
        parent::__construct(
            baseUrl: $config->searxngUrl(),
            token: $config->searxngApiKey(),
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

        $response = $this->request('GET', 'search', [
            'headers' => $this->bearerHeaders(),
            'query' => $queryParams,
        ]);
        $this->ensureSuccess($response, 'SearXNG');

        return $this->decodeJson($response);
    }
}
