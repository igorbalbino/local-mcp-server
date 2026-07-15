<?php

declare(strict_types=1);

namespace LocalMcp\Tools\HomeAssistant;

use LocalMcp\Clients\HomeAssistantClient;
use LocalMcp\Core\Config;
use LocalMcp\Tools\AbstractTool;

final class HaGetStateTool extends AbstractTool
{
    public function __construct(Config $config, HomeAssistantClient $client)
    {
        parent::__construct($config, $client, 'ENABLE_HOME_ASSISTANT');
    }

    public function name(): string
    {
        return 'ha_get_state';
    }

    public function description(): string
    {
        return 'Get the current state of a Home Assistant entity by entity_id.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'entity_id' => [
                    'type' => 'string',
                    'description' => 'Entity ID (e.g. light.living_room)',
                ],
            ],
            'required' => ['entity_id'],
        ];
    }

    public function handle(array $arguments): string|array
    {
        /** @var HomeAssistantClient $client */
        $client = $this->client;
        $entityId = $this->requireString($arguments, 'entity_id');
        $state = $client->getState($entityId);

        return $this->json([
            'entity_id' => $state['entity_id'] ?? $entityId,
            'state' => $state['state'] ?? null,
            'attributes' => $state['attributes'] ?? [],
            'last_changed' => $state['last_changed'] ?? null,
            'last_updated' => $state['last_updated'] ?? null,
        ]);
    }
}
