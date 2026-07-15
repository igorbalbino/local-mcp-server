<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Clients;

use Jarvis\McpServer\Core\Config;

final class BrowserlessClient extends AbstractHttpClient
{
    public function __construct(Config $config, ?\GuzzleHttp\Client $http = null)
    {
        parent::__construct(
            baseUrl: $config->string('BROWSERLESS_URL'),
            token: $config->get('BROWSERLESS_TOKEN'),
            timeout: 60.0,
            http: $http,
        );
    }

    /**
     * @return array{content_type: string, data_base64: string}
     */
    public function screenshot(string $url, ?int $width = null, ?int $height = null): array
    {
        $payload = ['url' => $url];

        if ($width !== null || $height !== null) {
            $payload['options'] = array_filter([
                'viewport' => array_filter([
                    'width' => $width,
                    'height' => $height,
                ], static fn ($v) => $v !== null),
            ]);
        }

        $response = $this->request('POST', $this->tokenizedPath('screenshot'), [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $payload,
        ]);
        $this->ensureSuccess($response, 'Browserless');

        return [
            'content_type' => $response->getHeaderLine('Content-Type') ?: 'image/png',
            'data_base64' => base64_encode((string) $response->getBody()),
        ];
    }

    /**
     * @return array{content_type: string, data_base64: string}
     */
    public function pdf(string $url): array
    {
        $response = $this->request('POST', $this->tokenizedPath('pdf'), [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['url' => $url],
        ]);
        $this->ensureSuccess($response, 'Browserless');

        return [
            'content_type' => $response->getHeaderLine('Content-Type') ?: 'application/pdf',
            'data_base64' => base64_encode((string) $response->getBody()),
        ];
    }

    /**
     * @return array{html: string}
     */
    public function content(string $url): array
    {
        $response = $this->request('POST', $this->tokenizedPath('content'), [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['url' => $url],
        ]);
        $this->ensureSuccess($response, 'Browserless');

        $body = (string) $response->getBody();
        $contentType = $response->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $data = $this->decodeJson($response);
            $html = isset($data['data']) && is_string($data['data']) ? $data['data'] : $body;

            return ['html' => $html];
        }

        return ['html' => $body];
    }

    private function tokenizedPath(string $path): string
    {
        if ($this->token === null || $this->token === '') {
            return $path;
        }

        return $path . '?token=' . rawurlencode($this->token);
    }
}
