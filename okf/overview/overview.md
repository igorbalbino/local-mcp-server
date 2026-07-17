# Overview — Arquitetura Local MCP Server

## Contexto

O **Local MCP Server** é um servidor [Model Context Protocol](https://modelcontextprotocol.io) genérico, independente do modelo de IA (Cursor, OpenAI, Gemini, Ollama, Home Assistant, etc.). Ele expõe **tools** autenticadas para agentes, encapsulando integrações externas sem vazar secrets ao LLM.

Decisões centrais:

- SDK oficial `mcp/sdk` com transport **Streamable HTTP** (+ GET SSE para HA)
- Auth cliente→Local MCP por **API Key** (Bearer / path / query) via middleware
- PHP 8.4+, Composer, Guzzle, FrankenPHP
- Clean Architecture: Transport · Protocol · Session · Middleware · Providers · Tools

## Relacionamentos

| Depende de | Relação |
|------------|---------|
| [auth](../auth/auth.md) | Gate de entrada para rotas MCP |
| [core](../core/core.md) | Bootstrap, DI, config, registry |
| [server](../server/server.md) | Orquestra HTTP, health e MCP |
| [tools](../tools/tools.md) | Capacidades expostas ao agente |
| [providers](../providers/providers.md) | Chamadas autenticadas a serviços |
| [config](../config/config.md) | Feature flags e secrets via `.env` |
| [docker](../docker/docker.md) | Runtime de produção/local |

## Mapa de pastas principais

| Pasta / arquivo | Papel |
|-----------------|-------|
| `public/index.php` | Entry point HTTP (SAPI) |
| `src/Server.php` | Facade / orquestrador |
| `src/Transport/` | Streamable HTTP + GET SSE |
| `src/Protocol/` | Adapter do `mcp/sdk` |
| `src/Session/` | Persistência de sessão MCP |
| `src/Middleware/` | Auth + Logging (app) |
| `src/Providers/` | Integrações externas |
| `src/Tools/` | Tools MCP por domínio |
| `src/Contracts/` / `src/DTO/` | Interfaces e DTOs |
| `src/Core/` | Config, Container, ToolRegistry |
| `.env` / `.env.example` | Configuração |

## Diagrama

```
AI Agent / Home Assistant
   │  Bearer / path key / none
   ▼
public/index.php
   ▼
LocalMcp\Server
    ├─ /health
    ├─ OPTIONS
    ├─ LoggingMiddleware
    ├─ AuthenticationMiddleware
    └─ TransportFactory
          ├─ GET  → GetSseHandler
          └─ POST → StreamableHttpTransport (SDK middleware)
                └─ McpServerFacade → Tools → Providers
```
