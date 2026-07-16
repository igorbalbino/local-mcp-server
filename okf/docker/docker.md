# Docker — Empacotamento e publicação (GHCR)

## Contexto

Runtime: **FrankenPHP** (Caddy + PHP 8.4 Alpine) servindo `public/` na porta **8080** — concorrente (necessário para o cliente MCP do Home Assistant). A imagem é publicada em **GitHub Container Registry**:

`ghcr.io/igorbalbino/local-mcp`

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [server](../server/server.md) | Entry `public/index.php`; health `/health` lê `VERSION` |
| [config](../config/config.md) | `env_file: .env` |
| [overview](../overview/overview.md) | Deployment |

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `docker/php/Dockerfile` | Multi-stage FrankenPHP + labels OCI |
| `docker/php/Caddyfile` | Listen `:8080`, `root public/`, `php_server` |
| `docker-compose.yml` | Consome imagem GHCR (`latest`) |
| `compose.dev.yml` | Build local + volume do código |
| `.dockerignore` | Exclui `.git`, `.github`, `vendor`, `tests`, logs, `.env*`, `README.md` |
| `.github/workflows/docker.yml` | Build/push automático no GHCR |
| `VERSION` | Semver da release (ex.: `0.1.0`) |

## Labels OCI (Dockerfile)

- `org.opencontainers.image.title=Local MCP Server`
- `org.opencontainers.image.description=Generic MCP Server for AI Agents`
- `org.opencontainers.image.source=https://github.com/igorbalbino/local-mcp-server`
- `org.opencontainers.image.licenses=MIT`

## Comandos

```bash
# produção / pull
docker compose up -d

# desenvolvimento
docker compose -f compose.dev.yml up --build -d

curl http://localhost:8090/health
# {"name":"Local MCP Server","version":"0.1.0"}
```
