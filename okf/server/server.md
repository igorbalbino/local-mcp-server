# Server — Bootstrap HTTP e MCP

## Contexto

`LocalMcp\Server` é a facade fina: carrega o DI, roteia `/health`, resolve `/mcp`, aplica middleware de app e delega ao `TransportFactory`.

O protocolo MCP (`initialize`, `tools/list`, `tools/call`, eco de `id`, `protocolVersion`) é responsabilidade do `mcp/sdk` via `Protocol\McpServerFacade`.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [auth](../auth/auth.md) | `AuthenticationMiddleware` |
| [core](../core/core.md) | `boot()` / container |
| [tools](../tools/tools.md) | Registradas no facade |
| [docker](../docker/docker.md) | FrankenPHP → `public/` |

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `public/index.php` | Dotenv → `Server::boot` → emit |
| `src/Server.php` | Roteamento + pipeline |
| `src/Transport/TransportFactory.php` | POST/DELETE Streamable + middleware SDK |
| `src/Transport/GetSseHandler.php` | GET SSE (HA) |
| `src/Protocol/McpServerFacade.php` | Builder `mcp/sdk` |
| `src/Session/FileSessionStoreAdapter.php` | Sessões em disco |

## Rotas

| Path / method | Auth | Comportamento |
|---------------|------|---------------|
| `GET /health` | Não | `{"status":"ok","name","version","mcp"}` |
| `OPTIONS *` | Não | CORS preflight |
| `POST /mcp` | Conforme auth | Streamable HTTP |
| `GET /mcp` | Conforme auth | SSE keepalive + session |
| `DELETE /mcp` | Conforme auth | Encerra sessão |
| `/mcp/{api-key}` | Se `path` ∈ AUTH_LOCATION | Mesmos métodos |
| `/` | Conforme auth | Alias de `/mcp` |
