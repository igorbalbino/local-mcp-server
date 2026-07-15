# Tools — Padrão MCP e registro

## Contexto

Cada capacidade exposta ao agente é uma **tool** MCP: nome estável, descrição, JSON Schema de input e `handle()`. Só entram no protocolo se `isEnabled()` for verdadeiro (`ENABLE_*` + client configurado).

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [contracts](../contracts/contracts.md) | `ToolInterface` |
| [core](../core/core.md) | `ToolRegistry` + `ServiceProvider::resolveTool()` |
| [server](../server/server.md) | `addTool(handler, name, description, inputSchema)` |
| [clients](../clients/clients.md) | Tools delegam I/O aos clients |
| [config](../config/config.md) | Flags `ENABLE_*` |
| Domínios | pastas `okf/*` por integração |

## Arquivos, classes e funções

### Contrato e base

| Arquivo | Classe | Métodos |
|---------|--------|---------|
| `src/Contracts/ToolInterface.php` | `ToolInterface` | `name`, `description`, `inputSchema`, `isEnabled`, `handle` |
| `src/Tools/AbstractTool.php` | `AbstractTool` | `isEnabled()` (flag + client), `json()`, `requireString()`, `optionalString()`, `optionalInt()` |

### Catálogo

| Arquivo | Papel |
|---------|-------|
| `config/tools.php` | Lista ordenada de `class-string<ToolInterface>` |

### Wiring

| Arquivo | Função |
|---------|--------|
| `src/Core/ServiceProvider.php` | `resolveTool()` — `match` classe → new Tool(Config, Client) |
| `src/Core/ToolRegistry.php` | Ignora desabilitadas |
| `src/Server.php` | `createHandler()` — adapta `RequestContext` → `handle(array)` |

## Como adicionar uma tool nova

1. Client em `src/Clients/` (se serviço novo)
2. Classe em `src/Tools/SeuDominio/` estendendo `AbstractTool`
3. Entry em `config/tools.php`
4. `case` em `ServiceProvider::resolveTool()`
5. Vars em `.env.example` + doc em `okf/<assunto>/`
6. (Opcional) testes unitários

## Tools atuais (nomes MCP)

| Nome MCP | Domínio |
|----------|---------|
| `ha_list_states`, `ha_get_state`, `ha_call_service` | Home Assistant |
| `web_search` | SearXNG |
| `browser_screenshot`, `browser_pdf`, `browser_content` | Browserless |
| `rag_search`, `rag_index_document` | Meilisearch |
| `translate` | LibreTranslate |
