# Contracts — Interfaces

## Contexto

Contratos que desacoplam camadas (ISP / DIP). Novas tools e clients implementam estas interfaces sem alterar o núcleo além do wiring em `ServiceProvider` e `config/tools.php`.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [tools](../tools/tools.md) | Toda tool implementa `ToolInterface` |
| [clients](../clients/clients.md) | Clients implementam `ServiceClientInterface` |
| [auth](../auth/auth.md) | `ApiKeyAuthenticator` implementa `AuthenticatorInterface` |
| [core](../core/core.md) | Registry e provider dependem só dos contratos |

## Arquivos, classes e métodos

| Arquivo | Interface | Métodos |
|---------|-----------|---------|
| `src/Contracts/ToolInterface.php` | `ToolInterface` | `name()`, `description()`, `inputSchema()`, `isEnabled()`, `handle(array)` |
| `src/Contracts/ServiceClientInterface.php` | `ServiceClientInterface` | `isConfigured(): bool` |
| `src/Contracts/AuthenticatorInterface.php` | `AuthenticatorInterface` | `authenticate(?string $authorizationHeader): bool` |

## Regras

- `inputSchema()` deve ser JSON Schema `type: object` (exigência do `mcp/sdk`)
- `isEnabled()` combina flag `ENABLE_*` + `client->isConfigured()`
- `handle()` **não** deve retornar tokens, headers ou URLs com secrets
