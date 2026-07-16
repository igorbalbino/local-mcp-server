# Server — Bootstrap HTTP e MCP

## Contexto

`LocalMcp\Server` é a facade: carrega o DI, roteia `/health`, valida auth e executa o protocolo MCP via `mcp/sdk` (`StreamableHttpTransport` + `FileSessionStore`). **GET** no `/mcp` abre um stream SSE (Streamable) — necessário para o cliente do Home Assistant; o SDK sozinho responde 405.

Entry point SAPI: `public/index.php` (Dotenv → `Server::boot` → `handleFromGlobals` → `SapiEmitter`).

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [auth](../auth/auth.md) | Gate (Bearer / path key / `none`) antes do MCP |
| [core](../core/core.md) | `boot()` / container / registry / logger |
| [tools](../tools/tools.md) | Cada tool vira `addTool()` no builder MCP |
| [contracts](../contracts/contracts.md) | Handlers usam `ToolInterface` |
| [exceptions](../exceptions/exceptions.md) | Erros de tool viram JSON `{ "error": "..." }` |
| [docker](../docker/docker.md) | FrankenPHP aponta para `public/` (sem gzip no Caddy) |

## Arquivos, classes e funções

| Arquivo | Classe / símbolo | Métodos |
|---------|------------------|---------|
| `public/index.php` | script | Carrega `.env`, emite resposta via `SapiEmitter` |
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
| `handleMcp()` | POST/DELETE Streamable via SDK; GET → `handleMcpGet()` |
| `handleMcpGet()` | SSE `text/event-stream` com `Mcp-Session-Id` (HA) |
| `createHandler(ToolInterface): Closure` | Lê `CallToolRequest` via `RequestContext` e chama `handle(array)` |

## Rotas

| Path / method | Auth | Comportamento |
|---------------|------|---------------|
| `GET /health` | Não | `{"name","version","mcp":"/mcp"}` |
| `OPTIONS *` | Não | CORS |
| `POST /mcp` | Conforme auth | Streamable HTTP (JSON-RPC) |
| `GET /mcp` | Conforme auth | Streamable SSE (`Accept: text/event-stream` + session) |
| `DELETE /mcp` | Conforme auth | Encerra sessão MCP |
| `/mcp/{api-key}` | Key no path | Mesmos métodos — plug-and-play Home Assistant |
| `/` | Conforme auth | Alias de `/mcp` |

## Sessions

Armazenadas em `storage/cache/sessions` (`FileSessionStore`) — necessário para HTTP stateless entre requests.
