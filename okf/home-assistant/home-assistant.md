# Home Assistant

## Contexto

Integração com a REST API do Home Assistant: listar estados, consultar entidade e chamar services (`light.turn_on`, etc.). Token de longa duração (`HA_TOKEN`) fica só no client.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [providers](../providers/providers.md) | `HomeAssistantProvider` |
| [tools](../tools/tools.md) | Três tools HA |
| [config](../config/config.md) | `ENABLE_HOME_ASSISTANT`, `HA_URL`, `HA_TOKEN` |
| [auth](../auth/auth.md) | Auth Local MCP (independente do token HA) |

## Variáveis de ambiente

| Var | Uso |
|-----|-----|
| `ENABLE_HOME_ASSISTANT` | Feature flag |
| `HA_URL` | Base URL (ex.: `http://homeassistant.local:8123`) |
| `HA_TOKEN` | Long-Lived Access Token |

`isConfigured()` exige URL **e** token.

## Arquivos, classes e funções

### Provider

| Arquivo | Classe | Métodos |
|---------|--------|---------|
| `src/Providers/HomeAssistant/HomeAssistantProvider.php` | `HomeAssistantProvider` | `listStates()`, `getState(entityId)`, `callService(domain, service, serviceData)`, `isConfigured()` |

Endpoints internos: `GET api/states`, `GET api/states/{id}`, `POST api/services/{domain}/{service}` com `Authorization: Bearer`.

### Tools

| Arquivo | Classe | Nome MCP | `handle` faz |
|---------|--------|----------|--------------|
| `src/Tools/HomeAssistant/HaListStatesTool.php` | `HaListStatesTool` | `ha_list_states` | Lista resumida; filtro opcional `domain` |
| `src/Tools/HomeAssistant/HaGetStateTool.php` | `HaGetStateTool` | `ha_get_state` | Estado + attributes de um `entity_id` |
| `src/Tools/HomeAssistant/HaCallServiceTool.php` | `HaCallServiceTool` | `ha_call_service` | `domain` + `service` + `service_data` opcional |

## Wiring

- `config/tools.php` — três classes HA
- `ServiceProvider::resolveTool()` — cases `Ha*Tool`
