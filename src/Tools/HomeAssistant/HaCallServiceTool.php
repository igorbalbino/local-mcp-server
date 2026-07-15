<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Tools\HomeAssistant;

use Jarvis\McpServer\Clients\HomeAssistantClient;
use Jarvis\McpServer\Core\Config;
use Jarvis\McpServer\Tools\AbstractTool;

final class HaCallServiceTool extends AbstractTool
{
    public function __construct(Config $config, HomeAssistantClient $client)
    {
        parent::__construct($config, $client, 'ENABLE_HOME_ASSISTANT');
    }

    public function name(): string
    {
        return 'ha_call_service';
    }

    public function description(): string
    {
        return 'Call a Home Assistant service (e.g. domain=light, service=turn_on).';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'domain' => [
                    'type' => 'string',
                    'description' => 'Service domain (e.g. light, switch, script)',
                ],
                'service' => [
                    'type' => 'string',
                    'description' => 'Service name (e.g. turn_on, turn_off)',
                ],
                'service_data' => [
                    'type' => 'object',
                    'description' => 'Optional service data payload (e.g. entity_id, brightness)',
                ],
            ],
            'required' => ['domain', 'service'],
        ];
    }

    public function handle(array $arguments): string|array
    {
        /** @var HomeAssistantClient $client */
        $client = $this->client;
        $domain = $this->requireString($arguments, 'domain');
        $service = $this->requireString($arguments, 'service');

        /** @var array<string, mixed> $serviceData */
        $serviceData = [];
        if (isset($arguments['service_data']) && is_array($arguments['service_data'])) {
            /** @var array<string, mixed> $serviceData */
            $serviceData = $arguments['service_data'];
        }

        $result = $client->callService($domain, $service, $serviceData);

        return $this->json(['result' => $result]);
    }
}
