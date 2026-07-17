# Docker — Empacotamento e publicação (GHCR)

## Contexto

Runtime: **FrankenPHP** (Caddy + PHP 8.4 Alpine) servindo `public/` na porta **8080**. Sem `encode` no Caddyfile (evita buffer em streams).

PHP tuning em `docker/php/php.ini` (`conf.d/99-mcp.ini`):

- `max_execution_time = 0`
- `output_buffering = Off`
- `zlib.output_compression = Off`
- `memory_limit = 512M`

`ENV PHP_MAX_EXECUTION_TIME=0` no Dockerfile.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `docker/php/Dockerfile` | Multi-stage FrankenPHP |
| `docker/php/php.ini` | Streaming / SSE |
| `docker/php/Caddyfile` | Listen `:8080`, `php_server` |
| `docker-compose.yml` | Imagem GHCR |
| `compose.dev.yml` | Build local |

## Healthcheck

```bash
curl http://localhost:8090/health
# {"status":"ok","name":"Local MCP Server","version":"0.1.0","mcp":"/mcp"}
```
