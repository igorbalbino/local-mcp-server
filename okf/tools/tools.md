# Tools — Padrão MCP e registro

## Contexto

Cada capacidade exposta ao agente é uma **tool** MCP: nome estável, descrição, JSON Schema de input e `handle()`. Só entram no protocolo se `isEnabled()` for verdadeiro (`ENABLE_*` + provider configurado).

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [contracts](../contracts/contracts.md) | `ToolInterface` |
| [core](../core/core.md) | `ToolRegistry` + `ServiceProvider::resolveTool()` |
| [server](../server/server.md) | Registradas via `McpServerFacade` |
| [providers](../providers/providers.md) | Tools delegam I/O aos providers |
| [config](../config/config.md) | Flags `ENABLE_*` |

## Como adicionar uma tool nova

1. Provider em `src/Providers/` (se serviço novo)
2. Classe em `src/Tools/SeuDominio/` estendendo `AbstractTool`
3. Entry em `config/tools.php`
4. `case` em `ServiceProvider::resolveTool()`
5. Vars em `.env.example` + doc em `okf/<assunto>/`

## Tools atuais (nomes MCP)

| Nome MCP | Domínio |
|----------|---------|
| `ha_list_states`, `ha_get_state`, `ha_call_service` | Home Assistant |
| `web_search` | SearXNG |
| `browser_screenshot`, `browser_pdf`, `browser_content` | Browserless |
| `rag_search`, `rag_index_document` | Meilisearch |
| `translate` | LibreTranslate |
