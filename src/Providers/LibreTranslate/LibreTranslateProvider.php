<?php

declare(strict_types=1);

namespace LocalMcp\Providers\LibreTranslate;

use LocalMcp\Core\Config;
use LocalMcp\Providers\AbstractHttpProvider;

final class LibreTranslateProvider extends AbstractHttpProvider
{
    public function __construct(Config $config, ?\GuzzleHttp\Client $http = null)
    {
        parent::__construct(
            baseUrl: $config->libreTranslateUrl(),
            token: $config->libreTranslateApiKey(),
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
