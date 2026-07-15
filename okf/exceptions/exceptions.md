# Exceptions — Erros tipados

## Contexto

Exceções de domínio para falhas previsíveis. Tools capturam `IntegrationException` e devolvem JSON de erro **seguro** ao modelo (sem stack/secrets).

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [auth](../auth/auth.md) | `AuthenticationException` |
| [config](../config/config.md) / [core](../core/core.md) | `ConfigurationException` (`Config::require`) |
| [clients](../clients/clients.md) / [tools](../tools/tools.md) | `IntegrationException` em HTTP/args inválidos |
| [server](../server/server.md) | `createHandler()` captura `IntegrationException` e `\Throwable` genérico |
| [core](../core/core.md) | `ServiceNotFoundException` no container PSR-11 |

## Arquivos e classes

| Arquivo | Classe | Uso |
|---------|--------|-----|
| `src/Exceptions/AuthenticationException.php` | `AuthenticationException` | Auth inválida (domínio) |
| `src/Exceptions/ConfigurationException.php` | `ConfigurationException` | Env obrigatória ausente |
| `src/Exceptions/IntegrationException.php` | `IntegrationException` | Falha de serviço externo / argumento |
| `src/Exceptions/ServiceNotFoundException.php` | `ServiceNotFoundException` | Serviço não registrado no DI |

Todas (exceto `ServiceNotFoundException`) estendem `RuntimeException`. `ServiceNotFoundException` implementa `Psr\Container\NotFoundExceptionInterface`.
