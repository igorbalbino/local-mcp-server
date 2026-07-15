# Clients — HTTP para serviços externos

## Contexto

Clients Guzzle encapsulam **URL, token e headers**. Tools nunca montam Authorization para HA/Meili/etc.; só chamam métodos tipados do client.

Base comum: `AbstractHttpClient`.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [tools](../tools/tools.md) | Cada tool recebe um client no construtor |
| [core](../core/core.md) | Clients registrados no `ServiceProvider` |
| [config](../config/config.md) | URLs/tokens via `Config` |
| [exceptions](../exceptions/exceptions.md) | Falhas HTTP → `IntegrationException` (mensagem genérica) |
| Integrações | [home-assistant](../home-assistant/home-assistant.md), [searxng](../searxng/searxng.md), [browserless](../browserless/browserless.md), [meilisearch](../meilisearch/meilisearch.md), [libretranslate](../libretranslate/libretranslate.md) |

## Arquivos, classes e funções

### Base

| Arquivo | Classe | Métodos |
|---------|--------|---------|
| `src/Clients/AbstractHttpClient.php` | `AbstractHttpClient` | `__construct(baseUrl, token, timeout, ?Client)`, `isConfigured()`, `request()`, `decodeJson()`, `ensureSuccess()`, `bearerHeaders()` |
| `src/Contracts/ServiceClientInterface.php` | interface | `isConfigured()` |

### Por integração

| Arquivo | Classe | Métodos principais |
|---------|--------|--------------------|
| `src/Clients/HomeAssistantClient.php` | `HomeAssistantClient` | `listStates()`, `getState()`, `callService()` |
| `src/Clients/SearxngClient.php` | `SearxngClient` | `search()` |
| `src/Clients/BrowserlessClient.php` | `BrowserlessClient` | `screenshot()`, `pdf()`, `content()` |
| `src/Clients/MeilisearchClient.php` | `MeilisearchClient` | `search()`, `indexDocument()`, `getDefaultIndex()` |
| `src/Clients/LibreTranslateClient.php` | `LibreTranslateClient` | `translate()` |

## Regra de segurança

- Tokens só como propriedade/`headers` internos
- Browserless usa `?token=` na path interna do client — a tool devolve apenas base64/HTML, sem a URL tokenizada
- Respostas de erro **não** incluem corpo bruto do upstream com detalhes sensíveis (apenas status HTTP genérico via `ensureSuccess`)

## Testes

- `tests/Clients/ClientsTest.php` — HA + SearXNG com `MockHandler`
