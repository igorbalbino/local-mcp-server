<?php

declare(strict_types=1);

namespace LocalMcp\Clients;

use LocalMcp\Core\Config;

final class LibreTranslateClient extends AbstractHttpClient
{
    public function __construct(Config $config, ?\GuzzleHttp\Client $http = null)
    {
        parent::__construct(
            baseUrl: $config->string('LIBRETRANSLATE_URL'),
            token: $config->get('LIBRETRANSLATE_API_KEY'),
            http: $http,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function translate(string $text, string $source, string $target): array
    {
        $payload = [
            'q' => $text,
            'source' => $source,
            'target' => $target,
            'format' => 'text',
        ];

        if ($this->token !== null && $this->token !== '') {
            $payload['api_key'] = $this->token;
        }

        $response = $this->request('POST', 'translate', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $payload,
        ]);
        $this->ensureSuccess($response, 'LibreTranslate');

        return $this->decodeJson($response);
    }
}
