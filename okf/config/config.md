# Config — Ambiente e feature flags

## Contexto

Toda configuração operacional passa por variáveis de ambiente. `vlucas/phpdotenv` carrega no `public/index.php`. A API tipada é `LocalMcp\Core\Config`.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [core](../core/core.md) | Classe `Config` |
| [auth](../auth/auth.md) | Keys, mode, location |
| [tools](../tools/tools.md) / providers | `ENABLE_*` + URLs/tokens |
| [docker](../docker/docker.md) | `env_file: .env` |

## Variáveis globais

| Var | Default / notas |
|-----|-----------------|
| `LOCAL_MCP_API_KEYS` | Keys do cliente MCP (URL-safe); vazio no `.env.example` |
| `LOCAL_MCP_AUTH_MODE` | `auto` / `none` / `bearer` |
| `LOCAL_MCP_AUTH_LOCATION` | `header,path,query` |
| `LOCAL_MCP_ALLOWED_HOSTS` | Extra hosts (além de localhost + `local-mcp`) |
| `LOCAL_MCP_CORS_ORIGINS` | `*` default |
| `MCP_SERVER_NAME` / `MCP_SERVER_VERSION` | Identidade MCP |

## Feature flags

| Flag | URL / secrets |
|------|----------------|
| `ENABLE_HOME_ASSISTANT` | `HA_URL`, `HA_TOKEN` |
| `ENABLE_SEARXNG` | `SEARXNG_URL`, `SEARXNG_API_KEY` |
| `ENABLE_BROWSERLESS` | `BROWSERLESS_URL`, `BROWSERLESS_TOKEN` |
| `ENABLE_MEILISEARCH` | `MEILI_URL`, `MEILI_KEY`, `MEILI_INDEX` |
| `ENABLE_LIBRETRANSLATE` | `LIBRETRANSLATE_URL`, `LIBRETRANSLATE_API_KEY` |

## API `Config`

Além de `get` / `require` / `bool` / `list` / `string`, accessors tipados: `mcpApiKeys()`, `authMode()`, `authLocations()`, `allowedHosts()`, `corsOrigins()`, `homeAssistantUrl()`, `searxngUrl()`, `browserlessUrl()`, `meilisearchUrl()`, `libreTranslateUrl()`, etc.
