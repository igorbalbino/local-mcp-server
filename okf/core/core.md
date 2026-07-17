# Core — Config, DI, Registry e Logging

## Contexto

Camada de infraestrutura leve (sem framework): carrega ambiente, resolve dependências, registra tools habilitadas e produz logs.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [server](../server/server.md) | `Server::boot()` instancia `ServiceProvider` |
| [config](../config/config.md) | `Config` é a API tipada sobre `$_ENV` |
| [auth](../auth/auth.md) | Authenticator resolvido pelo container |
| [tools](../tools/tools.md) | `ToolRegistry` filtra por `isEnabled()` |
| [providers](../providers/providers.md) | Providers construídos no `ServiceProvider` |
| [contracts](../contracts/contracts.md) | Tipagem das tools e do authenticator |

## Arquivos, classes e funções

### Config

| Arquivo | Classe | Métodos |
|---------|--------|---------|
| `src/Core/Config.php` | `Config` | `fromEnv()`, `get()`, `require()`, `bool()`, `list()`, `string()` |

### Container (PSR-11)

| Arquivo | Classe | Métodos |
|---------|--------|---------|
| `src/Core/Container.php` | `Container` | `set()`, `get()`, `has()` |
| `src/Exceptions/ServiceNotFoundException.php` | `ServiceNotFoundException` | NotFound PSR-11 |

### ServiceProvider

| Arquivo | Classe | Métodos |
|---------|--------|---------|
| `src/Core/ServiceProvider.php` | `ServiceProvider` | `register(): Container`, `resolveTool(Container, class-string)` |

Registra:

- `Config`, `LoggerInterface`, `AuthenticatorInterface`
- Todos os `*Provider`
- `ToolRegistry` a partir de `config/tools.php`

### ToolRegistry

| Arquivo | Classe | Métodos |
|---------|--------|---------|
| `src/Core/ToolRegistry.php` | `ToolRegistry` | `register()`, `all()`, `count()`, `has()` |

Só adiciona a tool se `ToolInterface::isEnabled()` for `true`.

### Logger

| Arquivo | Classe | Métodos |
|---------|--------|---------|
| `src/Core/LoggerFactory.php` | `LoggerFactory` | `create(Config, basePath)` — Monolog → `storage/logs/app.log` + stderr |

## Fluxo de boot

```
Server::boot($basePath)
  → ServiceProvider::register()
      → Config::fromEnv()
      → set providers / auth / logger
      → require config/tools.php
      → ToolRegistry(tools enabled)
  → new Server(container, basePath)
```
