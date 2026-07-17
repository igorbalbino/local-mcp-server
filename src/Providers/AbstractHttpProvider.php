<?php

declare(strict_types=1);

namespace LocalMcp\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use LocalMcp\Contracts\ProviderInterface;
use LocalMcp\Exceptions\IntegrationException;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractHttpProvider implements ProviderInterface
{
    protected readonly Client $http;

    public function __construct(
        protected readonly string $baseUrl,
        protected readonly ?string $token = null,
        float $timeout = 30.0,
        ?Client $http = null,
    ) {
        $this->http = $http ?? new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'timeout' => $timeout,
            'http_errors' => false,
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '';
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        try {
            return $this->http->request($method, ltrim($uri, '/'), $options);
        } catch (GuzzleException $e) {
            throw new IntegrationException('External service request failed.', 0, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJson(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        if ($body === '') {
            return [];
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            return $data;
        } catch (\JsonException $e) {
            throw new IntegrationException('External service returned invalid JSON.', 0, $e);
        }
    }

    protected function ensureSuccess(ResponseInterface $response, string $service): void
    {
        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            throw new IntegrationException(sprintf('%s request failed with HTTP %d.', $service, $status));
        }
    }

    /**
     * @return array<string, string>
     */
    protected function bearerHeaders(?string $token = null): array
    {
        $token ??= $this->token;

        if ($token === null || $token === '') {
            return [];
        }

        return ['Authorization' => 'Bearer ' . $token];
    }
}
