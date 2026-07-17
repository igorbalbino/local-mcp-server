# Contracts — Interfaces

## Contexto

Contratos que desacoplam camadas (ISP / DIP). Novas tools e providers implementam estas interfaces sem alterar o núcleo além do wiring em `ServiceProvider` e `config/tools.php`.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [tools](../tools/tools.md) | Toda tool implementa `ToolInterface` |
| [providers](../providers/providers.md) | Providers implementam `ProviderInterface` |
| [auth](../auth/auth.md) | `ApiKeyAuthenticator` implementa `AuthenticatorInterface` |
| [core](../core/core.md) | Registry e provider DI dependem só dos contratos |

## Arquivos

| Arquivo | Interface | Métodos |
|---------|-----------|---------|
| `src/Contracts/ToolInterface.php` | `ToolInterface` | `name()`, `description()`, `inputSchema()`, `isEnabled()`, `handle(array)` |
| `src/Contracts/ProviderInterface.php` | `ProviderInterface` | `isConfigured(): bool` |
| `src/Contracts/AuthenticatorInterface.php` | `AuthenticatorInterface` | `hasKeys()`, `isValidKey()`, `authenticate(?string)` |
| `src/Session/SessionStoreInterface.php` | Session store | `exists`, `has`, `read`, `write`, `destroy`, `mcpStore` |

## Regras

- `inputSchema()` deve ser JSON Schema `type: object`
- `isEnabled()` combina flag `ENABLE_*` + `provider->isConfigured()`
- `handle()` **não** deve retornar tokens, headers ou URLs com secrets
