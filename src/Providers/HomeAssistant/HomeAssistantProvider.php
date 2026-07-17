<?php

declare(strict_types=1);

namespace LocalMcp\Providers\HomeAssistant;

use LocalMcp\Core\Config;
use LocalMcp\Providers\AbstractHttpProvider;

final class HomeAssistantProvider extends AbstractHttpProvider
{
    public function __construct(Config $config, ?\GuzzleHttp\Client $http = null)
    {
        parent::__construct(
            baseUrl: $config->homeAssistantUrl(),
            token: $config->homeAssistantToken(),
            http: $http,
        );
    }

    public function isConfigured(): bool
    {
        return parent::isConfigured() && $this->token !== null && $this->token !== '';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listStates(): array
    {
        $response = $this->request('GET', 'api/states', [
            'headers' => $this->bearerHeaders(),
        ]);
        $this->ensureSuccess($response, 'Home Assistant');

        $data = $this->decodeJson($response);

        /** @var list<array<string, mixed>> $states */
        $states = array_values($data);

        return $states;
    }

    /**
     * @return array<string, mixed>
     */
    public function getState(string $entityId): array
    {
        $response = $this->request('GET', 'api/states/' . rawurlencode($entityId), [
            'headers' => $this->bearerHeaders(),
        ]);
        $this->ensureSuccess($response, 'Home Assistant');

        return $this->decodeJson($response);
    }

    /**
     * @param array<string, mixed> $serviceData
     *
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function callService(string $domain, string $service, array $serviceData = []): array
    {
        $response = $this->request('POST', sprintf('api/services/%s/%s', rawurlencode($domain), rawurlencode($service)), [
            'headers' => array_merge($this->bearerHeaders(), ['Content-Type' => 'application/json']),
            'json' => $serviceData,
        ]);
        $this->ensureSuccess($response, 'Home Assistant');

        $data = $this->decodeJson($response);

        if ($data === [] && (string) $response->getBody() === '') {
            return ['ok' => true];
        }

        return $data;
    }
}
