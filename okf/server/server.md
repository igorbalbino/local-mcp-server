# Server — Bootstrap HTTP e MCP

## Contexto

`LocalMcp\Server` é a facade: carrega o DI, roteia `/health`, valida auth e executa o protocolo MCP via `mcp/sdk` (`StreamableHttpTransport` + `FileSessionStore`).

Entry point SAPI: `public/index.php` (Dotenv → `Server::boot` → `handleFromGlobals` → headers + body).

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [auth](../auth/auth.md) | Gate Bearer antes do MCP |
| [core](../core/core.md) | `boot()` / container / registry / logger |
| [tools](../tools/tools.md) | Cada tool vira `addTool()` no builder MCP |
| [contracts](../contracts/contracts.md) | Handlers usam `ToolInterface` |
| [exceptions](../exceptions/exceptions.md) | Erros de tool viram JSON `{ "error": "..." }` |
| [docker](../docker/docker.md) | PHP built-in server aponta para `public/` |

## Arquivos, classes e funções

| Arquivo | Classe / símbolo | Métodos |
|---------|------------------|---------|
| `public/index.php` | script | Carrega `.env`, emite resposta PSR-7 |
| `src/Server.php` | `Server` | ver tabela abaixo |

### Métodos de `Server`

| Método | Papel |
|--------|-------|
| `boot(string $basePath): self` | Factory: ServiceProvider + instance |
| `container(): Container` | Acesso ao DI |
| `handle(ServerRequestInterface): ResponseInterface` | Roteamento health / OPTIONS / auth / MCP |
| `handleFromGlobals(): ResponseInterface` | PSR-7 a partir de `$_SERVER` (Nyholm) |
| `isAuthenticated()` | Delega a `AuthenticatorInterface` |
| `healthResponse()` | JSON `{ status, server, tools }` |
| `optionsResponse()` | CORS preflight 204 |
| `unauthorizedResponse()` | 401 JSON |
| `handleMcp()` | Monta `Mcp\Server` builder, registra tools, roda transport |
| `createHandler(ToolInterface): Closure` | Lê `CallToolRequest` via `RequestContext` e chama `handle(array)` |

## Rotas

| Path / method | Auth | Comportamento |
|---------------|------|---------------|
| `GET /health` | Não | `{"name":"Local MCP Server","version":"..."}` a partir de `VERSION` |
| `OPTIONS *` | Não | CORS |
| Demais (MCP) | Sim | Streamable HTTP (`initialize`, `tools/list`, `tools/call`, …) |

## Sessions

Armazenadas em `storage/cache/sessions` (`FileSessionStore`) — necessário para HTTP stateless entre requests.
