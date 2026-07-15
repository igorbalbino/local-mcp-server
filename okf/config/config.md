# Config — Ambiente e feature flags

## Contexto

Toda configuração operacional passa por variáveis de ambiente (arquivo `.env` em runtime; `.env.example` como template). `vlucas/phpdotenv` carrega no `public/index.php`. A API tipada é `LocalMcp\Core\Config`.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [core](../core/core.md) | Classe `Config` |
| [auth](../auth/auth.md) | `LOCAL_MCP_API_KEYS` |
| [tools](../tools/tools.md) / integrações | `ENABLE_*` + URLs/tokens |
| [docker](../docker/docker.md) | `env_file: .env` no compose |
| [server](../server/server.md) | `MCP_SERVER_NAME`, `MCP_SERVER_VERSION`, `LOG_LEVEL` |

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `.env` | Secrets locais (**gitignored**) |
| `.env.example` | Template commitado |
| `config/tools.php` | Mapa estático de classes de tools (não é secret) |
| `src/Core/Config.php` | Leitura tipada |

## Variáveis globais

| Var | Default / notas |
|-----|-----------------|
| `APP_NAME` | Nome da app |
| `APP_ENV` | `production` / `local` |
| `LOG_LEVEL` | `info`, `debug`, … |
| `MCP_SERVER_NAME` | Identidade MCP |
| `MCP_SERVER_VERSION` | Semver |
| `LOCAL_MCP_API_KEYS` | Obrigatória para usar tools |

## Feature flags por integração

| Flag | URL / secrets |
|------|----------------|
| `ENABLE_HOME_ASSISTANT` | `HA_URL`, `HA_TOKEN` |
| `ENABLE_SEARXNG` | `SEARXNG_URL`, `SEARXNG_API_KEY` |
| `ENABLE_BROWSERLESS` | `BROWSERLESS_URL`, `BROWSERLESS_TOKEN` |
| `ENABLE_MEILISEARCH` | `MEILI_URL`, `MEILI_KEY`, `MEILI_INDEX` |
| `ENABLE_LIBRETRANSLATE` | `LIBRETRANSLATE_URL`, `LIBRETRANSLATE_API_KEY` |

Uma tool só registra se flag `true` **e** `client->isConfigured()`.

## API `Config`

| Método | Comportamento |
|--------|----------------|
| `fromEnv()` | Snapshot `$_ENV` / `$_SERVER` |
| `get($key, $default)` | `null` se ausente ou string vazia |
| `require($key)` | Lança `ConfigurationException` |
| `bool($key)` | `FILTER_VALIDATE_BOOLEAN` |
| `list($key)` | Split por vírgula + trim |
| `string($key, $default)` | Sempre string |
