<?php

declare(strict_types=1);

namespace LocalMcp\Tools\HomeAssistant;

use LocalMcp\Providers\HomeAssistant\HomeAssistantProvider;
use LocalMcp\Core\Config;
use LocalMcp\Tools\AbstractTool;

final class HaListStatesTool extends AbstractTool
{
    public function __construct(Config $config, HomeAssistantProvider $provider)
    {
        parent::__construct($config, $provider, 'ENABLE_HOME_ASSISTANT');
    }

    public function name(): string
    {
        return 'ha_list_states';
    }

    public function description(): string
    {
        return 'List entity states from Home Assistant. Optionally filter by domain (e.g. light, switch, sensor).';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'domain' => [
                    'type' => 'string',
                    'description' => 'Optional entity domain filter (e.g. light, switch, sensor)',
                ],
            ],
        ];
    }

    public function handle(array $arguments): string|array
    {
        /** @var HomeAssistantProvider $provider */
        $provider = $this->provider;
        $states = $provider->listStates();
        $domain = $this->optionalString($arguments, 'domain');

        if ($domain !== null) {
            $prefix = $domain . '.';
            $states = array_values(array_filter(
                $states,
                static fn (array $state): bool => isset($state['entity_id'])
                    && is_string($state['entity_id'])
                    && str_starts_with($state['entity_id'], $prefix),
            ));
        }

        $summary = array_map(static function (array $state): array {
            return [
                'entity_id' => $state['entity_id'] ?? null,
                'state' => $state['state'] ?? null,
                'friendly_name' => $state['attributes']['friendly_name'] ?? null,
            ];
        }, $states);

        return $this->json(['count' => count($summary), 'states' => $summary]);
    }
}
