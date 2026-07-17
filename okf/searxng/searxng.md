# SearXNG

## Contexto

Busca web privativa via instância SearXNG (`format=json`). Resultado sanitizado para o modelo: title, url, content snippet, engine.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [providers](../providers/providers.md) | `SearXNGProvider` |
| [tools](../tools/tools.md) | `WebSearchTool` |
| [config](../config/config.md) | `ENABLE_SEARXNG`, `SEARXNG_URL`, `SEARXNG_API_KEY` |
| [auth](../auth/auth.md) | Auth Local MCP separada |

## Variáveis de ambiente

| Var | Uso |
|-----|-----|
| `ENABLE_SEARXNG` | Feature flag |
| `SEARXNG_URL` | Base URL |
| `SEARXNG_API_KEY` | Opcional; se preenchida, envia Bearer |

## Arquivos, classes e funções

| Arquivo | Classe | Métodos / nome MCP |
|---------|--------|-------------------|
| `src/Providers/SearXNG/SearXNGProvider.php` | `SearXNGProvider` | `search(query, pageno, categories?, language?)` → `GET search` |
| `src/Tools/Searxng/WebSearchTool.php` | `WebSearchTool` | MCP `web_search` — args: `query`, `pageno`, `categories`, `language` |

## Wiring

- `config/tools.php` → `WebSearchTool::class`
- `ServiceProvider` → `new WebSearchTool($config, SearXNGProvider)`
