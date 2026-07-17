# Providers — HTTP para serviços externos

## Contexto

Providers Guzzle encapsulam **URL, token e headers**. Tools nunca montam Authorization para HA/Meili/etc.; só chamam métodos tipados do provider.

Base comum: `AbstractHttpProvider`.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [tools](../tools/tools.md) | Cada tool recebe um provider no construtor |
| [core](../core/core.md) | Providers registrados no `ServiceProvider` |
| [config](../config/config.md) | URLs/tokens via `Config` |
| [exceptions](../exceptions/exceptions.md) | Falhas HTTP → `IntegrationException` |
| Integrações | [home-assistant](../home-assistant/home-assistant.md), [searxng](../searxng/searxng.md), [browserless](../browserless/browserless.md), [meilisearch](../meilisearch/meilisearch.md), [libretranslate](../libretranslate/libretranslate.md) |

## Arquivos

### Base

| Arquivo | Classe |
|---------|--------|
| `src/Providers/AbstractHttpProvider.php` | `AbstractHttpProvider` |
| `src/Contracts/ProviderInterface.php` | `isConfigured()` |

### Por integração

| Arquivo | Classe |
|---------|--------|
| `src/Providers/HomeAssistant/HomeAssistantProvider.php` | `listStates`, `getState`, `callService` |
| `src/Providers/SearXNG/SearXNGProvider.php` | `search` |
| `src/Providers/Browserless/BrowserlessProvider.php` | `screenshot`, `pdf`, `content` |
| `src/Providers/Meilisearch/MeilisearchProvider.php` | `search`, `indexDocument` |
| `src/Providers/LibreTranslate/LibreTranslateProvider.php` | `translate` |

## Testes

- `tests/Providers/ProvidersTest.php`
