# OKF — Jarvis MCP Server

Pasta de conhecimento do projeto (**OKF**): cada assunto tem sua própria pasta com um `.md` descrevendo contexto, relações e o mapeamento para arquivos, classes e funções.

## Índice

| Assunto | Documento |
|---------|-----------|
| Visão geral / arquitetura | [overview/overview.md](overview/overview.md) |
| Autenticação | [auth/auth.md](auth/auth.md) |
| Core (DI, config, registry) | [core/core.md](core/core.md) |
| Contratos (interfaces) | [contracts/contracts.md](contracts/contracts.md) |
| Exceções | [exceptions/exceptions.md](exceptions/exceptions.md) |
| Server / MCP HTTP | [server/server.md](server/server.md) |
| Clients HTTP (base) | [clients/clients.md](clients/clients.md) |
| Tools (padrão e registry) | [tools/tools.md](tools/tools.md) |
| Home Assistant | [home-assistant/home-assistant.md](home-assistant/home-assistant.md) |
| SearXNG | [searxng/searxng.md](searxng/searxng.md) |
| Browserless | [browserless/browserless.md](browserless/browserless.md) |
| Meilisearch (RAG) | [meilisearch/meilisearch.md](meilisearch/meilisearch.md) |
| LibreTranslate | [libretranslate/libretranslate.md](libretranslate/libretranslate.md) |
| Configuração (.env) | [config/config.md](config/config.md) |
| Docker | [docker/docker.md](docker/docker.md) |

## Fluxo resumido

```
Cliente MCP → public/index.php → Server
  → Auth (Bearer API Key)
  → ToolRegistry (tools ENABLE_*)
  → Tool → Client HTTP → serviço externo
```

Credenciais dos serviços externos **nunca** vão ao modelo de IA; ficam apenas nos clients.
