# Auth — Autenticação cliente → Local MCP

## Contexto

Garante que **somente clientes autorizados** usem o endpoint MCP. A autenticação é por **API Key** no header:

```http
Authorization: Bearer <api-key>
```

As keys válidas vêm de `LOCAL_MCP_API_KEYS` (lista separada por vírgula). Comparação com `hash_equals` (tempo constante).

**Importante:** isso autentica o *cliente MCP* (Cursor, agent, etc.), não os serviços externos. Secrets de HA/SearXNG/etc. ficam nos [clients](../clients/clients.md).

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [server](../server/server.md) | `Server::handle()` chama auth antes do MCP; `/health` e `OPTIONS` não exigem key |
| [contracts](../contracts/contracts.md) | Implementa `AuthenticatorInterface` |
| [config](../config/config.md) | Lê `LOCAL_MCP_API_KEYS` via `Config` |
| [exceptions](../exceptions/exceptions.md) | `AuthenticationException` reservada para erros de auth |
| [core](../core/core.md) | `ApiKeyAuthenticator` registrado no `ServiceProvider` |

## Arquivos, classes e funções

| Arquivo | Classe | Métodos / papel |
|---------|--------|-----------------|
| `src/Contracts/AuthenticatorInterface.php` | `AuthenticatorInterface` | `authenticate(?string $authorizationHeader): bool` |
| `src/Auth/ApiKeyAuthenticator.php` | `ApiKeyAuthenticator` | `__construct(Config)`, `fromKeys(array)`, `authenticate(?)` |
| `src/Auth/AuthMiddleware.php` | `AuthMiddleware` | `process()` — PSR-15; 401 + `WWW-Authenticate` se inválido |
| `src/Server.php` | `Server` | `isAuthenticated()`, `unauthorizedResponse()` — gate HTTP atual (v0.3 do SDK não usa middleware no transport) |
| `src/Exceptions/AuthenticationException.php` | `AuthenticationException` | Tipo de erro de domínio |

## Fluxo

1. Request chega em `Server::handle()`
2. Se path ≠ `/health` e method ≠ `OPTIONS` → `isAuthenticated()`
3. `ApiKeyAuthenticator` parseia `Bearer <token>` e compara com keys do `.env`
4. Falha → `401 Unauthorized` JSON
5. Sucesso → `handleMcp()`

## Variáveis de ambiente

- `LOCAL_MCP_API_KEYS` — ex.: `key1,key2`

## Testes

- `tests/Auth/ApiKeyAuthenticatorTest.php`
- `tests/Auth/AuthMiddlewareTest.php`
